<?php
/**
 * PayPal Commerce Checkout Actions
 *
 * @package    commercestore
 * @subpackage Gateways\PayPal
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    GPL2+
 * @since      2.11
 */

namespace CS\Gateways\PayPal;

use CS\Gateways\PayPal\Exceptions\API_Exception;
use CS\Gateways\PayPal\Exceptions\Authentication_Exception;
use CS\Gateways\PayPal\Exceptions\Gateway_Exception;

/**
 * Removes the credit card form for PayPal Commerce
 *
 * @access private
 * @since  2.11
 */
add_action( 'cs_paypal_commerce_cc_form', '__return_false' );

/**
 * Replaces the "Submit" button with a PayPal smart button.
 *
 * @param string $button
 *
 * @since 2.11
 * @return string
 */
function override_purchase_button( $button ) {
	if ( 'paypal_commerce' === cs_get_chosen_gateway() && cs_get_cart_total() ) {
		ob_start();
		if ( ready_to_accept_payments() ) {
			wp_nonce_field( 'cs_process_paypal', 'cs_process_paypal_nonce' );
			$timestamp = time();
			?>
			<input type="hidden" name="cs-process-paypal-token" data-timestamp="<?php echo esc_attr( $timestamp ); ?>" data-token="<?php echo esc_attr( \CS\Utils\Tokenizer::tokenize( $timestamp ) ); ?>" />
			<div id="cs-paypal-errors-wrap"></div>
			<div id="cs-paypal-container"></div>
			<div id="cs-paypal-spinner" style="display: none;">
				<span class="cs-loading-ajax cs-loading"></span>
			</div>
			<?php
			/**
			 * Triggers right below the button container.
			 *
			 * @since 2.11
			 */
			do_action( 'cs_paypal_after_button_container' );
		} else {
			$error_message = current_user_can( 'manage_options' )
				? __( 'Please connect your PayPal account in the gateway settings.', 'commercestore' )
				: __( 'Unexpected authentication error. Please contact a site administrator.', 'commercestore' );
			?>
			<div class="cs_errors cs-alert cs-alert-error">
				<p class="cs_error">
					<?php echo esc_html( $error_message ); ?>
				</p>
			</div>
			<?php
		}

		return ob_get_clean();
	}

	return $button;
}

add_filter( 'cs_checkout_button_purchase', __NAMESPACE__ . '\override_purchase_button', 10000 );

/**
 * Sends checkout error messages via AJAX.
 *
 * This overrides the normal error behaviour in `cs_process_purchase_form()` because we *always*
 * want to send errors back via JSON.
 *
 * @param array $user       User data.
 * @param array $valid_data Validated form data.
 * @param array $posted     Raw $_POST data.
 *
 * @since 2.11
 * @return void
 */
function send_ajax_errors( $user, $valid_data, $posted ) {
	if ( empty( $valid_data['gateway'] ) || 'paypal_commerce' !== $valid_data['gateway'] ) {
		return;
	}

	$errors = cs_get_errors();
	if ( $errors ) {
		cs_clear_errors();

		wp_send_json_error( cs_build_errors_html( $errors ) );
	}
}

add_action( 'cs_checkout_user_error_checks', __NAMESPACE__ . '\send_ajax_errors', 99999, 3 );

/**
 * Creates a new order in PayPal and CS.
 *
 * @param array $purchase_data
 *
 * @since 2.11
 * @return void
 */
function create_order( $purchase_data ) {
	cs_debug_log( 'PayPal - create_order()' );

	if ( ! ready_to_accept_payments() ) {
		cs_record_gateway_error(
			__( 'PayPal Gateway Error', 'commercestore' ),
			__( 'Account not ready to accept payments.', 'commercestore' )
		);

		$error_message = current_user_can( 'manage_options' )
			? __( 'Please connect your PayPal account in the gateway settings.', 'commercestore' )
			: __( 'Unexpected authentication error. Please contact a site administrator.', 'commercestore' );

		wp_send_json_error( cs_build_errors_html( array(
			'paypal-error' => $error_message
		) ) );
	}

	try {
		// Create pending payment in CS.
		$payment_args = array(
			'price'        => $purchase_data['price'],
			'date'         => $purchase_data['date'],
			'user_email'   => $purchase_data['user_email'],
			'purchase_key' => $purchase_data['purchase_key'],
			'currency'     => cs_get_currency(),
			'downloads'    => $purchase_data['downloads'],
			'cart_details' => $purchase_data['cart_details'],
			'user_info'    => $purchase_data['user_info'],
			'status'       => 'pending',
			'gateway'      => 'paypal_commerce'
		);

		$payment_id = cs_insert_payment( $payment_args );

		if ( ! $payment_id ) {
			throw new Gateway_Exception(
				__( 'An unexpected error occurred. Please try again.', 'commercestore' ),
				500,
				sprintf(
					'Payment creation failed before sending buyer to PayPal. Payment data: %s',
					json_encode( $payment_args )
				)
			);
		}

		$order_data = array(
			'intent'               => 'CAPTURE',
			'purchase_units'       => get_order_purchase_units( $payment_id, $purchase_data, $payment_args ),
			'application_context'  => array(
				//'locale'              => get_locale(), // PayPal doesn't like this. Might be able to replace `_` with `-`
				'shipping_preference' => 'NO_SHIPPING',
				'user_action'         => 'PAY_NOW',
				'return_url'          => cs_get_checkout_uri(),
				'cancel_url'          => cs_get_failed_transaction_uri( '?payment-id=' . urlencode( $payment_id ) )
			),
			'payment_instructions' => array(
				'disbursement_mode' => 'INSTANT'
			)
		);

		// Add payer data if we have it. We won't have it when using Buy Now buttons.
		if ( ! empty( $purchase_data['user_email'] ) ) {
			$order_data['payer']['email_address'] = $purchase_data['user_email'];
		}
		if ( ! empty( $purchase_data['user_info']['first_name'] ) ) {
			$order_data['payer']['name']['given_name'] = $purchase_data['user_info']['first_name'];
		}
		if ( ! empty( $purchase_data['user_info']['last_name'] ) ) {
			$order_data['payer']['name']['surname'] = $purchase_data['user_info']['last_name'];
		}

		/**
		 * Filters the arguments sent to PayPal.
		 *
		 * @param array $order_data    API request arguments.
		 * @param array $purchase_data Purchase data.
		 * @param int   $payment_id    ID of the CommerceStore payment.
		 *
		 * @since 2.11
		 */
		$order_data = apply_filters( 'cs_paypal_order_arguments', $order_data, $purchase_data, $payment_id );

		try {
			$api      = new API();
			$response = $api->make_request( 'v2/checkout/orders', $order_data );

			if ( ! isset( $response->id ) && _is_item_total_mismatch( $response ) ) {

				cs_record_gateway_error(
					__( 'PayPal Gateway Warning', 'commercestore' ),
					sprintf(
						/* Translators: %s - Original order data sent to PayPal. */
						__( 'PayPal could not complete the transaction with the itemized breakdown. Original order data sent: %s', 'commercestore' ),
						json_encode( $order_data )
					),
					$payment_id
				);

				// Try again without the item breakdown. That way if we have an error in our totals the whole API request won't fail.
				$order_data['purchase_units'] = array(
					get_order_purchase_units_without_breakdown( $payment_id, $purchase_data, $payment_args )
				);

				// Re-apply the filter.
				$order_data = apply_filters( 'cs_paypal_order_arguments', $order_data, $purchase_data, $payment_id );

				$response = $api->make_request( 'v2/checkout/orders', $order_data );
			}

			if ( ! isset( $response->id ) ) {
				throw new Gateway_Exception(
					__( 'An error occurred while communicating with PayPal. Please try again.', 'commercestore' ),
					$api->last_response_code,
					sprintf(
						'Unexpected response when creating order: %s',
						json_encode( $response )
					)
				);
			}

			cs_debug_log( sprintf( '-- Successful PayPal response. PayPal order ID: %s; CommerceStore order ID: %d', esc_html( $response->id ), $payment_id ) );

			cs_update_payment_meta( $payment_id, 'paypal_order_id', sanitize_text_field( $response->id ) );

			/*
			 * Send successfully created order ID back.
			 * We also send back a new nonce, for verification in the next step: `capture_order()`.
			 * If the user was just logged into a new account, the previously sent nonce may have
			 * become invalid.
			 */
			$timestamp = time();
			wp_send_json_success( array(
				'paypal_order_id' => $response->id,
				'cs_order_id'    => $payment_id,
				'nonce'           => wp_create_nonce( 'cs_process_paypal' ),
				'timestamp'       => $timestamp,
				'token'           =>  \CS\Utils\Tokenizer::tokenize( $timestamp ),
			) );
		} catch ( Authentication_Exception $e ) {
			throw new Gateway_Exception( __( 'An authentication error occurred. Please try again.', 'commercestore' ), $e->getCode(), $e->getMessage() );
		} catch ( API_Exception $e ) {
			throw new Gateway_Exception( __( 'An error occurred while communicating with PayPal. Please try again.', 'commercestore' ), $e->getCode(), $e->getMessage() );
		}
	} catch ( Gateway_Exception $e ) {
		if ( ! isset( $payment_id ) ) {
			$payment_id = 0;
		}

		$e->record_gateway_error( $payment_id );

		wp_send_json_error( cs_build_errors_html( array(
			'paypal-error' => $e->getMessage()
		) ) );
	}
}

add_action( 'cs_gateway_paypal_commerce', __NAMESPACE__ . '\create_order', 9 );

/**
 * Captures the order in PayPal
 *
 * @since 2.11
 */
function capture_order() {
	cs_debug_log( 'PayPal - capture_order()' );
	try {

		$token     = isset( $_POST['token'] )     ? sanitize_text_field( $_POST['token'] )     : '';
		$timestamp = isset( $_POST['timestamp'] ) ? sanitize_text_field( $_POST['timestamp'] ) : '';

		if ( ! empty( $timestamp ) && ! empty( $token ) ) {
			if ( !\CS\Utils\Tokenizer::is_token_valid( $token, $timestamp ) ) {
				throw new Gateway_Exception(
					__('A validation error occurred. Please try again.', 'commercestore'),
					403,
					'Token validation failed.'
				);
			}
		} elseif ( empty( $token ) && ! empty( $_POST['cs_process_paypal_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_POST['cs_process_paypal_nonce'], 'cs_process_paypal' ) ) {
				throw new Gateway_Exception(
					__( 'A validation error occurred. Please try again.', 'commercestore' ),
					403,
					'Nonce validation failed.'
				);
			}
		} else {
			throw new Gateway_Exception(
				__( 'A validation error occurred. Please try again.', 'commercestore' ),
				400,
				'Missing validation fields.'
			);
		}

		if ( empty( $_POST['paypal_order_id'] ) ) {
			throw new Gateway_Exception(
				__( 'An unexpected error occurred. Please try again.', 'commercestore' ),
				400,
				'Missing PayPal order ID during capture.'
			);
		}

		try {
			$api      = new API();
			$response = $api->make_request( 'v2/checkout/orders/' . urlencode( $_POST['paypal_order_id'] ) . '/capture' );

			cs_debug_log( sprintf( '-- PayPal Response code: %d; order ID: %s', $api->last_response_code, esc_html( $_POST['paypal_order_id'] ) ) );

			if ( ! in_array( $api->last_response_code, array( 200, 201 ) ) ) {
				$message = ! empty( $response->message ) ? $response->message : __( 'Failed to process payment. Please try again.', 'commercestore' );

				/*
				 * If capture failed due to funding source, we want to send a `restart` back to PayPal.
				 * @link https://developer.paypal.com/docs/checkout/integration-features/funding-failure/
				 */
				if ( ! empty( $response->details ) && is_array( $response->details ) ) {
					foreach ( $response->details as $detail ) {
						if ( isset( $detail->issue ) && 'INSTRUMENT_DECLINED' === $detail->issue ) {
							$message = __( 'Unable to complete your order with your chosen payment method. Please choose a new funding source.', 'commercestore' );
							$retry = true;
							break;
						}
					}
				}

				throw new Gateway_Exception(
					$message,
					400,
					sprintf( 'Order capture failure. PayPal response: %s', json_encode( $response ) )
				);
			}

			$payment = $transaction_id = false;
			if ( isset( $response->purchase_units ) && is_array( $response->purchase_units ) ) {
				foreach ( $response->purchase_units as $purchase_unit ) {
					if ( ! empty( $purchase_unit->reference_id ) ) {
						$payment        = cs_get_payment_by( 'key', $purchase_unit->reference_id );
						$transaction_id = isset( $purchase_unit->payments->captures[0]->id ) ? $purchase_unit->payments->captures[0]->id : false;

						if ( ! empty( $payment ) && isset( $purchase_unit->payments->captures[0]->status ) ) {
							if ( 'COMPLETED' === strtoupper( $purchase_unit->payments->captures[0]->status ) ) {
								$payment->status = 'complete';
							} elseif( 'DECLINED' === strtoupper( $purchase_unit->payments->captures[0]->status ) ) {
								$payment->status = 'failed';
							}
						}
						break;
					}
				}
			}

			if ( ! empty( $payment ) ) {
				/**
				 * Buy Now Button
				 *
				 * Fill in missing data when using "Buy Now". This bypasses checkout so not all information
				 * was collected prior to payment. Instead, we pull it from the PayPal info.
				 */
				if ( empty( $payment->email ) ) {
					if ( ! empty( $response->payer->email_address ) ) {
						$payment->email = sanitize_text_field( $response->payer->email_address );
					}
					if ( empty( $payment->first_name ) && ! empty( $response->payer->name->given_name ) ) {
						$payment->first_name = sanitize_text_field( $response->payer->name->given_name );
					}
					if ( empty( $payment->last_name ) && ! empty( $response->payer->name->surname ) ) {
						$payment->last_name = sanitize_text_field( $response->payer->name->surname );
					}

					if ( empty( $payment->customer_id ) && ! empty( $payment->email ) ) {
						$customer = new \CS_Customer( $payment->email );

						if ( $customer->id < 1 ) {
							$customer->create( array(
								'email'   => $payment->email,
								'name'    => trim( sprintf( '%s %s', $payment->first_name, $payment->last_name ) ),
								'user_id' => $payment->user_id
							) );
						}
					}
				}

				if ( ! empty( $transaction_id ) ) {
					$payment->transaction_id = sanitize_text_field( $transaction_id );

					cs_insert_payment_note( $payment->ID, sprintf(
					/* Translators: %s - transaction ID */
						__( 'PayPal Transaction ID: %s', 'commercestore' ),
						esc_html( $transaction_id )
					) );
				}

				$payment->save();

				if ( 'failed' === $payment->status ) {
					$retry = true;
					throw new Gateway_Exception(
						__( 'Your payment was declined. Please try a new payment method.', 'commercestore' ),
						400,
						sprintf( 'Order capture failure. PayPal response: %s', json_encode( $response ) )
					);
				}
			}

			wp_send_json_success( array( 'redirect_url' => cs_get_success_page_uri() ) );
		} catch ( Authentication_Exception $e ) {
			throw new Gateway_Exception( __( 'An authentication error occurred. Please try again.', 'commercestore' ), $e->getCode(), $e->getMessage() );
		} catch ( API_Exception $e ) {
			throw new Gateway_Exception( __( 'An error occurred while communicating with PayPal. Please try again.', 'commercestore' ), $e->getCode(), $e->getMessage() );
		}
	} catch ( Gateway_Exception $e ) {
		if ( ! isset( $payment_id ) ) {
			$payment_id = 0;
		}

		$e->record_gateway_error( $payment_id );

		wp_send_json_error( array(
			'message' => cs_build_errors_html( array(
				'paypal_capture_failure' => $e->getMessage()
			) ),
			'retry'   => isset( $retry ) ? $retry : false
		) );
	}
}

add_action( 'wp_ajax_nopriv_cs_capture_paypal_order', __NAMESPACE__ . '\capture_order' );
add_action( 'wp_ajax_cs_capture_paypal_order', __NAMESPACE__ . '\capture_order' );

/**
 * Gets a fresh set of gateway options when a PayPal order is cancelled.
 * @link https://github.com/awesomemotive/commercestore/issues/8883
 *
 * @since 2.11.3
 * @return void
 */
function cancel_order() {
	$nonces   = array();
	$gateways = cs_get_enabled_payment_gateways( true );
	foreach ( $gateways as $gateway_id => $gateway ) {
		$nonces[ $gateway_id ] = wp_create_nonce( 'cs-gateway-selected-' . esc_attr( $gateway_id ) );
	}

	wp_send_json_success(
		array(
			'nonces' => $nonces,
		)
	);
}
add_action( 'wp_ajax_nopriv_cs_cancel_paypal_order', __NAMESPACE__ . '\cancel_order' );
add_action( 'wp_ajax_cs_cancel_paypal_order', __NAMESPACE__ . '\cancel_order' );
