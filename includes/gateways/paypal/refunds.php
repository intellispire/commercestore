<?php
/**
 * PayPal Commerce Refunds
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
use CS\Orders\Order;

/**
 * Shows a checkbox to automatically refund payments in PayPal.
 *
 * @param Order $order
 *
 * @since 3.0
 */
add_action( 'cs_after_submit_refund_table', function( Order $order ) {
	if ( 'paypal_commerce' !== $order->gateway ) {
		return;
	}

	$mode = ( 'live' === $order->mode ) ? API::MODE_LIVE : API::MODE_SANDBOX;

	try {
		new API( $mode );
	} catch ( Exceptions\Authentication_Exception $e ) {
		// If we don't have credentials.
		return;
	}
	?>
	<div class="cs-form-group cs-paypal-refund-transaction">
		<div class="cs-form-group__control">
			<input type="checkbox" id="cs-paypal-commerce-refund" name="cs-paypal-commerce-refund" class="cs-form-group__input" value="1">
			<label for="cs-paypal-commerce-refund" class="cs-form-group__label">
				<?php esc_html_e( 'Refund transaction in PayPal', 'commercestore' ); ?>
			</label>
		</div>
	</div>
	<?php
} );

/**
 * If selected, refunds a transaction in PayPal when creating a new refund record.
 *
 * @param int $order_id ID of the order we're processing a refund for.
 * @param int $refund_id ID of the newly created refund record.
 * @param bool $all_refunded Whether or not this was a full refund.
 *
 * @since 3.0
 */
add_action( 'cs_refund_order', function( $order_id, $refund_id, $all_refunded ) {
	if ( ! current_user_can( 'edit_shop_payments', $order_id ) ) {
		return;
	}

	if ( empty( $_POST['data'] ) ) {
		return;
	}

	$order = cs_get_order( $order_id );
	if ( empty( $order->gateway ) || 'paypal_commerce' !== $order->gateway ) {
		return;
	}

	// Get our data out of the serialized string.
	parse_str( $_POST['data'], $form_data );

	if ( empty( $form_data['cs-paypal-commerce-refund'] ) ) {
		cs_add_note( array(
			'object_id'   => $order_id,
			'object_type' => 'order',
			'user_id'     => is_admin() ? get_current_user_id() : 0,
			'content'     => __( 'Transaction not refunded in PayPal, as checkbox was not selected.', 'commercestore' )
		) );

		return;
	}

	$refund = cs_get_order( $refund_id );
	if ( empty( $refund->total ) ) {
		return;
	}

	try {
		refund_transaction( $order, $refund );
	} catch ( \Exception $e ) {
		cs_debug_log( sprintf(
			'Failure when processing refund #%d. Message: %s',
			$refund->id,
			$e->getMessage()
		), true );

		cs_add_note( array(
			'object_id'   => $order->id,
			'object_type' => 'order',
			'user_id'     => is_admin() ? get_current_user_id() : 0,
			'content'     => sprintf(
				/* Translators: %d - ID of the refund; %s - error message from PayPal */
				__( 'Failure when processing PayPal refund #%d: %s', 'commercestore' ),
				$refund->id,
				$e->getMessage()
			)
		) );
	}
}, 10, 3 );

/**
 * Refunds a transaction in PayPal.
 *
 * @link  https://developer.paypal.com/docs/api/payments/v2/#captures_refund
 *
 * @param \CS_Payment|Order $payment_or_order
 * @param Order|null         $refund_object
 *
 * @since 2.11
 * @throws Authentication_Exception
 * @throws API_Exception
 * @throws \Exception
 */
function refund_transaction( $payment_or_order, Order $refund_object = null ) {
	/*
	 * Internally we want to work with an Order object, but we also need
	 * an CS_Payment object for backwards compatibility in the hooks.
	 */
	$order = $payment = false;
	if ( $payment_or_order instanceof Order ) {
		$order = $payment_or_order;
		$payment = cs_get_payment( $order->id );
	} elseif ( $payment_or_order instanceof \CS_Payment ) {
		$payment = $payment_or_order;
		$order   = cs_get_order( $payment->ID );
	}

	if ( empty( $order ) || ! $order instanceof Order ) {
		return;
	}

	$transaction_id = $order->get_transaction_id();

	if ( empty( $transaction_id ) ) {
		throw new \Exception( __( 'Missing transaction ID.', 'commercestore' ) );
	}

	$mode = ( 'live' === $order->mode ) ? API::MODE_LIVE : API::MODE_SANDBOX;

	$api = new API( $mode );

	$args = $refund_object instanceof Order ? array( 'invoice_id' => $refund_object->id ) : array();
	if ( $refund_object instanceof Order && abs( $refund_object->total ) !== abs( $order->total ) ) {
		$args['amount'] = array(
			'value'         => abs( $refund_object->total ),
			'currency_code' => $refund_object->currency,
		);
	}

	$response = $api->make_request(
		'v2/payments/captures/' . urlencode( $transaction_id ) . '/refund',
		$args,
		array(
			'Prefer' => 'return=representation',
		)
	);

	if ( 201 !== $api->last_response_code ) {
		throw new API_Exception( sprintf(
		/* Translators: %d - The HTTP response code; %s - Full API response from PayPal */
			__( 'Unexpected response code: %d. Response: %s', 'commercestore' ),
			$api->last_response_code,
			json_encode( $response )
		), $api->last_response_code );
	}

	if ( empty( $response->status ) || 'COMPLETED' !== strtoupper( $response->status ) ) {
		throw new API_Exception( sprintf(
		/* Translators: %s - API response from PayPal */
			__( 'Missing or unexpected refund status. Response: %s', 'commercestore' ),
			json_encode( $response )
		) );
	}

	// At this point we can assume it was successful.
	cs_update_order_meta( $order->id, '_cs_paypal_refunded', true );

	if ( ! empty( $response->id ) ) {
		// Add a note to the original order, and, if provided, the new refund object.
		if ( isset( $response->amount->value ) ) {
			$note_message = sprintf(
				/* Translators: %1$s - amount refunded; %$2$s - transaction ID. */
				__( '%1$s refunded in PayPal. Refund transaction ID: %2$s', 'commercestore' ),
				cs_currency_filter( cs_format_amount( $response->amount->value ), $order->currency ),
				esc_html( $response->id )
			);
		} else {
			$note_message = sprintf(
				/* Translators: %s - ID of the refund in PayPal */
				__( 'Successfully refunded in PayPal. Refund transaction ID: %s', 'commercestore' ),
				esc_html( $response->id )
			);
		}

		$note_object_ids = array( $order->id );
		if ( $refund_object instanceof Order ) {
			$note_object_ids[] = $refund_object->id;
		}

		foreach ( $note_object_ids as $note_object_id ) {
			cs_add_note( array(
				'object_id'   => $note_object_id,
				'object_type' => 'order',
				'user_id'     => is_admin() ? get_current_user_id() : 0,
				'content'     => $note_message
			) );
		}

		// Add a negative transaction.
		if ( $refund_object instanceof Order && isset( $response->amount->value ) ) {
			cs_add_order_transaction( array(
				'object_id'      => $refund_object->id,
				'object_type'    => 'order',
				'transaction_id' => sanitize_text_field( $response->id ),
				'gateway'        => 'paypal_commerce',
				'status'         => 'complete',
				'total'          => cs_negate_amount( $response->amount->value ),
			) );
		}
	}

	/**
	 * Triggers after a successful refund.
	 *
	 * @param \CS_Payment $payment
	 */
	do_action( 'cs_paypal_refund_purchase', $payment );
}
