<?php
/**
 * Order Action Functions
 *
 * @package     CS
 * @subpackage  Orders
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
use CS\Adjustments\Adjustment;

defined( 'ABSPATH' ) || exit;

/**
 * Manually add an order.
 *
 * @since 3.0
 *
 * @param array $args Order form data.
 * @return void
 */
function cs_add_manual_order( $args = array() ) {
	// Bail if user cannot manage shop settings or no data was passed.
	if ( empty( $args ) || ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	// Set up parameters.
	$nonce = isset( $_POST['cs_add_order_nonce'] )
		? sanitize_text_field( $_POST['cs_add_order_nonce'] )
		: '';

	// Bail if nonce fails.
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'cs_add_order_nonce' ) ) {
		return;
	}

	// Get now one time to avoid microsecond issues
	$now = CS()->utils->date( 'now', null, true )->timestamp;

	// Parse args.
	$data = wp_parse_args( $args, array(
		'downloads'               => array(),
		'adjustments'             => array(),
		'subtotal'                => 0.00,
		'tax'                     => 0.00,
		'total'                   => 0.00,
		'discount'                => 0.00,
		'cs-payment-status'      => 'complete',
		'payment_key'             => '',
		'gateway'                 => '',
		'transaction_id'          => '',
		'receipt'                 => '',
		'cs-payment-date'        => date( 'Y-m-d', $now ),
		'cs-payment-time-hour'   => date( 'G',     $now ),
		'cs-payment-time-min'    => date( 'i',     $now ),
		'cs-unlimited-downloads' => 0,
	) );

	/** Customer data *********************************************************/

	// Defaults
	$customer_id = 0;
	$user_id     = 0;
	$email       = '';
	$name        = '';

	// Create a new customer record.
	if ( isset( $data['cs-new-customer'] ) && 1 === absint( $data['cs-new-customer'] ) ) {

		// Sanitize first name
		$first_name = isset( $data['cs-new-customer-first-name'] )
			? sanitize_text_field( $data['cs-new-customer-first-name'] )
			: '';

		// Sanitize last name
		$last_name = isset( $data['cs-new-customer-last-name'] )
			? sanitize_text_field( $data['cs-new-customer-last-name'] )
			: '';

		// Combine
		$name = trim( $first_name . ' ' . $last_name );

		// Sanitize the email address
		$email = isset( $data['cs-new-customer-email'] )
			? sanitize_email( $data['cs-new-customer-email'] )
			: '';

		// Save to database.
		$customer_id = cs_add_customer( array(
			'name'  => $name,
			'email' => $email,
		) );

		$customer = cs_get_customer( $customer_id );

	// Existing customer.
	} elseif ( isset( $data['cs-new-customer'] ) && 0 === absint( $data['cs-new-customer'] ) && isset( $data['customer-id'] ) ) {
		$customer_id = absint( $data['customer-id'] );

		$customer = cs_get_customer( $customer_id );

		if ( $customer ) {
			$email   = $customer->email;
			$user_id = $customer->user_id;
			$name    = $customer->name;
		}
	}

	/** Insert order **********************************************************/

	// Parse order status.
	$status = sanitize_text_field( $data['cs-payment-status'] );

	if ( empty( $status ) || ! in_array( $status, array_keys( cs_get_payment_statuses() ), true ) ) {
		$status = 'complete';
	}

	// Parse date.
	$date = sanitize_text_field( $data['cs-payment-date'] );
	$hour = sanitize_text_field( $data['cs-payment-time-hour'] );

	// Restrict to our high and low.
	if ( $hour > 23 ) {
		$hour = 23;
	} elseif ( $hour < 0 ) {
		$hour = 00;
	}

	$minute = sanitize_text_field( $data['cs-payment-time-min'] );

	// Restrict to our high and low.
	if ( $minute > 59 ) {
		$minute = 59;
	} elseif ( $minute < 0 ) {
		$minute = 00;
	}

	// The date is entered in the WP timezone. We need to convert it to UTC prior to saving now.
	$date = cs_get_utc_equivalent_date( CS()->utils->date( $date . ' ' . $hour . ':' . $minute . ':00', cs_get_timezone_id(), false ) );
	$date = $date->format( 'Y-m-d H:i:s' );

	// Get mode
	$mode = cs_is_test_mode()
		? 'test'
		: 'live';

	// Amounts
	$order_subtotal = floatval( $data['subtotal'] );
	$order_tax      = floatval( $data['tax'] );
	$order_discount = floatval( $data['discount'] );
	$order_total    = floatval( $data['total'] );

	$tax_rate  = false;
	// If taxes are enabled, get the tax rate for the order location.
	if ( cs_use_taxes() ) {
		$country = ! empty( $data['cs_order_address']['country'] )
			? $data['cs_order_address']['country']
			: false;

		$region = ! empty( $data['cs_order_address']['region'] )
			? $data['cs_order_address']['region']
			: false;

		$tax_rate = cs_get_tax_rate_by_location(
			array(
				'country' => $country,
				'region'  => $region,
			)
		);
	}

	// Add the order ID
	$order_id = cs_add_order(
		array(
			'status'       => 'pending', // Always insert as pending initially.
			'user_id'      => $user_id,
			'customer_id'  => $customer_id,
			'email'        => $email,
			'ip'           => sanitize_text_field( $data['ip'] ),
			'gateway'      => sanitize_text_field( $data['gateway'] ),
			'mode'         => $mode,
			'currency'     => cs_get_currency(),
			'payment_key'  => $data['payment_key'] ? sanitize_text_field( $data['payment_key'] ) : cs_generate_order_payment_key( $email ),
			'tax_rate_id'  => ! empty( $tax_rate->id ) ? $tax_rate->id : null,
			'subtotal'     => $order_subtotal,
			'tax'          => $order_tax,
			'discount'     => $order_discount,
			'total'        => $order_total,
			'date_created' => $date,
		)
	);

	// Attach order to the customer record.
	if ( ! empty( $customer ) ) {
		$customer->attach_payment( $order_id, false );
	}

	// If we have tax, but no tax rate, manually save the percentage.
	if ( empty( $tax_rate->id ) && $order_tax > 0 ) {
		$tax_rate_percentage = $data['tax_rate'];
		if ( ! empty( $tax_rate_percentage ) ) {
			if ( $tax_rate_percentage > 0 && $tax_rate_percentage < 1 ) {
				$tax_rate_percentage = $tax_rate_percentage * 100;
			}

			cs_update_order_meta( $order_id, 'tax_rate', $tax_rate_percentage );
		}
	}

	/** Insert order address **************************************************/

	if ( isset( $data['cs_order_address'] ) ) {

		// Parse args
		$address = wp_parse_args( $data['cs_order_address'], array(
			'name'        => $name,
			'address'     => '',
			'address2'    => '',
			'city'        => '',
			'postal_code' => '',
			'country'     => '',
			'region'      => '',
		) );

		$order_address_data             = $address;
		$order_address_data['order_id'] = $order_id;

		// Remove empty data.
		$order_address_data = array_filter( $order_address_data );

		// Add to cs_order_addresses table.
		cs_add_order_address( $order_address_data );

		// Maybe add the address to the cs_customer_addresses.
		$customer_address_data = $order_address_data;

		// We don't need to pass this data to cs_maybe_add_customer_address().
		unset( $customer_address_data['order_id'] );

		cs_maybe_add_customer_address( $customer->id, $customer_address_data );
	}

	/** Insert order items ****************************************************/

	// Any adjustments specific to an order item need to be added to the item.
	foreach ( $data['adjustments'] as $key => $adjustment ) {
		if ( 'order_item' === $adjustment['object_type'] ) {
			$data['downloads'][ $adjustment['object_id'] ]['adjustments'][] = $adjustment;

			unset( $data['adjustments'][ $key ] );
		}
	}

	if ( ! empty( $data['downloads'] ) ) {

		// Re-index downloads.
		$data['downloads'] = array_values( $data['downloads'] );

		$downloads = array_reverse( $data['downloads'] );

		foreach ( $downloads as $cart_key => $download ) {
			$d = cs_get_download( absint( $download['id'] ) );

			// Skip if download no longer exists
			if ( empty( $d ) ) {
				continue;
			}

			// Quantity.
			$quantity = isset( $download['quantity'] )
				? absint( $download['quantity'] )
				: 1;

			// Price ID.
			$price_id = isset( $download['price_id'] ) && is_numeric( $download['price_id'] )
				? absint( $download['price_id'] )
				: null;

			// Amounts.
			$amount = isset( $download[ 'amount' ] )
				? floatval( $download[ 'amount' ] )
				: 0.00;

			$subtotal = isset( $download[ 'subtotal' ] )
				? floatval( $download[ 'subtotal' ] )
				: 0.00;

			$discount = isset( $download[ 'discount' ] )
				? floatval( $download[ 'discount' ] )
				: 0.00;

			$tax = isset( $download[ 'tax' ] )
				? floatval( $download[ 'tax' ] )
				: 0.00;

			$total = isset( $download[ 'total' ] )
				? floatval( $download[ 'total' ] )
				: 0.00;

			// Add to cs_order_items table.
			$order_item_id = cs_add_order_item( array(
				'order_id'     => $order_id,
				'product_id'   => absint( $download['id'] ),
				'product_name' => cs_get_download_name( $download['id'], absint( $price_id ) ),
				'price_id'     => $price_id,
				'cart_index'   => $cart_key,
				'type'         => 'download',
				'status'       => 'complete',
				'quantity'     => $quantity,
				'amount'       => $amount,
				'subtotal'     => $subtotal,
				'discount'     => $discount,
				'tax'          => $tax,
				'total'        => $total,
			) );

			if ( false !== $order_item_id ) {
				if ( isset( $download['adjustments'] ) ) {
					$order_item_adjustments = array_reverse( $download['adjustments'] );

					foreach ( $order_item_adjustments as $index => $order_item_adjustment ) {

						// Discounts are not tracked at the Order Item level.
						if ( 'discount' === $order_item_adjustment['type'] ) {
							continue;
						}

						$type_key = ! empty( $order_item_adjustment['description'] )
							? sanitize_text_field( strtolower( sanitize_title( $order_item_adjustment['description'] ) ) )
							: $index;

							$order_item_adjustment_subtotal = floatval( $order_item_adjustment['subtotal'] );
							$order_item_adjustment_tax      = floatval( $order_item_adjustment['tax'] );
							$order_item_adjustment_total    = floatval( $order_item_adjustment['total'] );

						cs_add_order_adjustment( array(
							'object_id'   => $order_item_id,
							'object_type' => 'order_item',
							'type'        => sanitize_text_field( $order_item_adjustment['type'] ),
							'type_key'    => $type_key,
							'description' => sanitize_text_field( $order_item_adjustment['description'] ),
							'subtotal'    => $order_item_adjustment_subtotal,
							'tax'         => $order_item_adjustment_tax,
							'total'       => $order_item_adjustment_total,
						) );
					}
				}
			}
		}
	}

	/** Insert adjustments ****************************************************/

	// Adjustments.
	if ( isset( $data['adjustments'] ) ) {
		$adjustments = array_reverse( $data['adjustments'] );

		foreach ( $adjustments as $index => $adjustment ) {
			if ( 'order_item' === $adjustment['object_type'] ) {
				continue;
			}

			$type_key = ! empty( $adjustment['description'] )
				? sanitize_text_field( strtolower( sanitize_title( $adjustment['description'] ) ) )
				: $index;

			$adjustment_subtotal = floatval( $adjustment['subtotal'] );
			$adjustment_tax      = floatval( $adjustment['tax'] );
			$adjustment_total    = floatval( $adjustment['total'] );

			cs_add_order_adjustment( array(
				'object_id'   => $order_id,
				'object_type' => 'order',
				'type'        => sanitize_text_field( $adjustment['type'] ),
				'type_key'    => $type_key,
				'description' => sanitize_text_field( $adjustment['description'] ),
				'subtotal'    => $adjustment_subtotal,
				'tax'         => $adjustment_tax,
				'total'       => $adjustment_total,
			) );
		}
	}

	// Discounts.
	if ( isset( $data['discounts'] ) ) {
		$discounts = array_reverse( $data['discounts'] );

		foreach ( $discounts as $discount ) {
			$d = cs_get_discount( absint( $discount['type_id'] ) );

			if ( empty( $d ) ) {
				continue;
			}

			$discount_subtotal = floatval( $discount['subtotal'] );
			$discount_total    = floatval( $discount['total'] );

			// Store discount.
			cs_add_order_adjustment( array(
				'object_id'   => $order_id,
				'object_type' => 'order',
				'type_id'     => intval( $discount['type_id'] ),
				'type'        => 'discount',
				'description' => sanitize_text_field( $discount['code'] ),
				'subtotal'    => $discount_subtotal,
				'total'       => $discount_total,
			) );
		}
	}

	// Insert transaction ID.
	if ( ! empty( $data['transaction_id'] ) ) {
		cs_add_order_transaction( array(
			'object_id'      => $order_id,
			'object_type'    => 'order',
			'transaction_id' => sanitize_text_field( $data['transaction_id'] ),
			'gateway'        => sanitize_text_field( $data['gateway'] ),
			'status'         => 'complete',
			'total'          => $order_total,
		) );
	}

	// Unlimited downloads.
	if ( isset( $data['cs-unlimited-downloads'] ) && 1 === (int) $data['cs-unlimited-downloads'] ) {
		cs_update_order_meta( $order_id, 'unlimited_downloads', 1 );
	}

	// Setup order number.
	$order_number = '';

	if ( cs_get_option( 'enable_sequential' ) ) {
		$number = cs_get_next_payment_number();

		$order_number = cs_format_payment_number( $number );

		update_option( 'cs_last_payment_number', $number );

		// Update totals & maybe add order number.
		cs_update_order( $order_id, array(
			'order_number' => $order_number,
		) );
	}

	// Stop purchase receipt from being sent.
	if ( ! isset( $data['cs_order_send_receipt'] ) ) {
		remove_action( 'cs_complete_purchase', 'cs_trigger_purchase_receipt', 999 );
	}

	// Trigger cs_complete_purchase.
	if ( 'complete' === $status ) {
		cs_update_order_status( $order_id, $status );
	}

	// Redirect to `Edit Order` page.
	cs_redirect( cs_get_admin_url( array(
		'page'        => 'cs-payment-history',
		'view'        => 'view-order-details',
		'id'          => urlencode( $order_id ),
		'cs-message' => 'order_added',
	) ) );
}
add_action( 'cs_add_order', 'cs_add_manual_order' );
