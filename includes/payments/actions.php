<?php
/**
 * Payment Actions
 *
 * @package     CS
 * @subpackage  Payments
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Complete a purchase
 *
 * Performs all necessary actions to complete a purchase.
 * Triggered by the cs_update_payment_status() function.
 *
 * @since 1.0.8.3
 * @since 3.0 Updated to use new order methods.
 *
 * @param int    $order_id   Order ID.
 * @param string $new_status New order status.
 * @param string $old_status Old order status.
*/
function cs_complete_purchase( $order_id, $new_status, $old_status ) {

	// Make sure that payments are only completed once.
	if ( 'publish' === $old_status || 'complete' === $old_status || 'completed' === $old_status ) {
		return;
	}

	// Make sure the payment completion is only processed when new status is complete.
	if ( 'publish' !== $new_status && 'complete' !== $new_status && 'completed' !== $new_status ) {
		return;
	}

	$order = cs_get_order( $order_id );

	if ( ! $order ) {
		return;
	}

	$completed_date = empty( $order->date_completed )
		? null
		: $order->date_completed;

	$customer_id = $order->customer_id;
	$amount      = $order->total;
	$order_items = $order->items;

	do_action( 'cs_pre_complete_purchase', $order_id );

	if ( is_array( $order_items ) ) {

		// Increase purchase count and earnings.
		foreach ( $order_items as $item ) {

			// "bundle" or "default"
			$download_type = cs_get_download_type( $item->product_id );

			// Increase earnings and fire actions once per quantity number.
			for ( $i = 0; $i < $item->quantity; $i++ ) {

				// Ensure these actions only run once, ever.
				if ( empty( $completed_date ) ) {

					// For backwards compatibility purposes, we need to construct an array and pass it
					// to cs_complete_download_purchase.
					$item_fees = array();

					foreach ( $item->get_fees() as $key => $item_fee ) {
						/** @var CS\Orders\Order_Adjustment $item_fee */

						$download_id = $item->product_id;
						$price_id    = $item->price_id;
						$no_tax      = (bool) 0.00 === $item_fee->tax;
						$id          = is_null( $item_fee->type_key ) ? $item_fee->id : $item_fee->type_key;
						if ( array_key_exists( $id, $item_fees ) ) {
							$id .= '_2';
						}

						$item_fees[ $id ] = array(
							'amount'      => $item_fee->amount,
							'label'       => $item_fee->description,
							'no_tax'      => $no_tax ? $no_tax : false,
							'type'        => 'fee',
							'download_id' => $download_id,
							'price_id'    => $price_id ? $price_id : null,
						);
					}

					$item_options = array(
						'quantity' => $item->quantity,
						'price_id' => $item->price_id,
					);

					/*
					 * For backwards compatibility from pre-3.0: add in order item meta prefixed with `_option_`.
					 * While saving, we've migrated these values to order item meta, but people may still be looking
					 * for them in this cart details array, so we need to fill them back in.
					 */
					$order_item_meta = cs_get_order_item_meta( $item->id );
					if ( ! empty( $order_item_meta ) ) {
						foreach ( $order_item_meta as $item_meta_key => $item_meta_value ) {
							if ( '_option_' === substr( $item_meta_key, 0, 8 ) && isset( $item_meta_value[0] ) ) {
								$item_options[ str_replace( '_option_', '', $item_meta_key ) ] = $item_meta_value[0];
							}
						}
					}

					$cart_details = array(
						'name'        => $item->product_name,
						'id'          => $item->product_id,
						'item_number' => array(
							'id'       => $item->product_id,
							'quantity' => $item->quantity,
							'options'  => $item_options,
						),
						'item_price'  => $item->amount,
						'quantity'    => $item->quantity,
						'discount'    => $item->discount,
						'subtotal'    => $item->subtotal,
						'tax'         => $item->tax,
						'fees'        => $item_fees,
						'price'       => $item->amount,
					);

					do_action( 'cs_complete_download_purchase', $item->product_id, $order_id, $download_type, $cart_details, $item->cart_index );
				}
			}

			// Increase the earnings for this download ID
			cs_increase_earnings( $item->product_id, $item->total );
			cs_increase_purchase_count( $item->product_id, $item->quantity );
		}

		// Clear the total earnings cache
		delete_transient( 'cs_earnings_total' );

		// Clear the This Month earnings (this_monththis_month is NOT a typo)
		delete_transient( md5( 'cs_earnings_this_monththis_month' ) );
		delete_transient( md5( 'cs_earnings_todaytoday' ) );
	}

	// Increase the customer's purchase stats
	$customer = new CS_Customer( $customer_id );
	$customer->recalculate_stats();

	cs_increase_total_earnings( $amount );

	// Check for discount codes and increment their use counts
	$discounts = $order->get_discounts();
	foreach ( $discounts as $adjustment ) {
		/** @var CS\Orders\Order_Adjustment $adjustment */

		cs_increase_discount_usage( $adjustment->description );
	}

	// Ensure this action only runs once ever
	if ( empty( $completed_date ) ) {
		$date = CS()->utils->date()->format( 'mysql' );

		$date_refundable = cs_get_refund_date( $date );
		$date_refundable = false === $date_refundable
			? ''
			: $date_refundable;

		// Save the completed date
		cs_update_order( $order_id, array(
			'date_completed'  => $date,
			'date_refundable' => $date_refundable,
		) );

		// Required for backwards compatibility.
		$payment = cs_get_payment( $order_id );

		/**
		 * Runs **when** a purchase is marked as "complete".
		 *
		 * @since 2.8 Added CS_Payment and CS_Customer object to action.
		 *
		 * @param int          $order_id Payment ID.
		 * @param CS_Payment  $payment    CS_Payment object containing all payment data.
		 * @param CS_Customer $customer   CS_Customer object containing all customer data.
		 */
		do_action( 'cs_complete_purchase', $order_id, $payment, $customer );

		// If cron doesn't work on a site, allow the filter to use __return_false and run the events immediately.
		$use_cron = apply_filters( 'cs_use_after_payment_actions', true, $order_id );
		if ( false === $use_cron ) {
			/**
			 * Runs **after** a purchase is marked as "complete".
			 *
			 * @see cs_process_after_payment_actions()
			 *
			 * @since 2.8 - Added CS_Payment and CS_Customer object to action.
			 *
			 * @param int          $order_id Payment ID.
			 * @param CS_Payment  $payment    CS_Payment object containing all payment data.
			 * @param CS_Customer $customer   CS_Customer object containing all customer data.
			 */
			do_action( 'cs_after_payment_actions', $order_id, $payment, $customer );
		}
	}

	// Empty the shopping cart
	cs_empty_cart();
}
add_action( 'cs_update_payment_status', 'cs_complete_purchase', 100, 3 );

/**
 * Schedules the one time event via WP_Cron to fire after purchase actions.
 *
 * Is run on the cs_complete_purchase action.
 *
 * @since 2.8
 * @param $payment_id
 */
function cs_schedule_after_payment_action( $payment_id ) {
	$use_cron = apply_filters( 'cs_use_after_payment_actions', true, $payment_id );
	if ( $use_cron ) {
		$after_payment_delay = apply_filters( 'cs_after_payment_actions_delay', 30, $payment_id );

		// Use time() instead of current_time( 'timestamp' ) to avoid scheduling the event in the past when server time
		// and WordPress timezone are different.
		wp_schedule_single_event( time() + $after_payment_delay, 'cs_after_payment_scheduled_actions', array( $payment_id, false ) );
	}
}
add_action( 'cs_complete_purchase', 'cs_schedule_after_payment_action', 10, 1 );

/**
 * Executes the one time event used for after purchase actions.
 *
 * @since 2.8
 * @param $payment_id
 * @param $force
 */
function cs_process_after_payment_actions( $payment_id = 0, $force = false ) {
	if ( empty( $payment_id ) ) {
		return;
	}

	$payment   = new CS_Payment( $payment_id );
	$has_fired = $payment->get_meta( '_cs_complete_actions_run' );
	if ( ! empty( $has_fired ) && false === $force ) {
		return;
	}

	$payment->add_note( __( 'After payment actions processed.', 'commercestore' ) );
	$payment->update_meta( '_cs_complete_actions_run', time() ); // This is in GMT

	do_action( 'cs_after_payment_actions', $payment_id, $payment, new CS_Customer( $payment->customer_id ) );
}
add_action( 'cs_after_payment_scheduled_actions', 'cs_process_after_payment_actions', 10, 1 );

/**
 * Record order status change
 *
 * @since 3.0
 * @param string $old_status the status of the order prior to this change.
 * @param string $new_status The new order status.
 * @param int    $order_id the ID number of the order.
 * @return void
 */
function cs_record_order_status_change( $old_status, $new_status, $order_id ) {

	// Get the list of statuses so that status in the payment note can be translated.
	$stati      = cs_get_payment_statuses();
	$old_status = isset( $stati[ $old_status ] ) ? $stati[ $old_status ] : $old_status;
	$new_status = isset( $stati[ $new_status ] ) ? $stati[ $new_status ] : $new_status;

	$status_change = sprintf(
		/* translators: %1$s Old order status. %2$s New order status. */
		__( 'Status changed from %1$s to %2$s', 'commercestore' ),
		$old_status,
		$new_status
	);

	cs_insert_payment_note( $order_id, $status_change );
}
add_action( 'cs_transition_order_status', 'cs_record_order_status_change', 100, 3 );

/**
 * Triggers `cs_update_payment_status` hook when an order status changes
 * for backwards compatibility.
 *
 * @since 3.0
 * @param string $old_status the status of the order prior to this change.
 * @param string $new_status The new order status.
 * @param int    $order_id the ID number of the order.
 * @return void
 */
add_action( 'cs_transition_order_status', function( $old_status, $new_status, $order_id ) {
	// Trigger the payment status action hook for backwards compatibility.
	do_action( 'cs_update_payment_status', $order_id, $new_status, $old_status );
	if ( 'complete' === $old_status ) {
		// Trigger the action again to account for add-ons listening for status changes from "publish".
		do_action( 'cs_update_payment_status', $order_id, $new_status, 'publish' );
	}
}, 10, 3 );

/**
 * Flushes the current user's purchase history transient when a payment status
 * is updated
 *
 * @since 1.2.2
 *
 * @param int $payment_id the ID number of the payment
 * @param string $new_status the status of the payment, probably "publish"
 * @param string $old_status the status of the payment prior to being marked as "complete", probably "pending"
 */
function cs_clear_user_history_cache( $payment_id, $new_status, $old_status ) {
	$payment = new CS_Payment( $payment_id );

	if( ! empty( $payment->user_id ) ) {
		delete_transient( 'cs_user_' . $payment->user_id . '_purchases' );
	}
}
add_action( 'cs_update_payment_status', 'cs_clear_user_history_cache', 10, 3 );

/**
 * Updates all old payments, prior to 1.2, with new
 * meta for the total purchase amount
 *
 * This is so that payments can be queried by their totals
 *
 * @since 1.2
 * @param array $data Arguments passed
 * @return void
*/
function cs_update_old_payments_with_totals( $data ) {
	if ( ! wp_verify_nonce( $data['_wpnonce'], 'cs_upgrade_payments_nonce' ) ) {
		return;
	}

	if ( get_option( 'cs_payment_totals_upgraded' ) ) {
		return;
	}

	$payments = cs_get_payments( array(
		'offset' => 0,
		'number' => 9999999,
		'mode'   => 'all',
	) );

	if ( $payments ) {
		foreach ( $payments as $payment ) {

			$payment = new CS_Payment( $payment->ID );
			$meta    = $payment->get_meta();

			$payment->total = $meta['amount'];
			$payment->save();
		}
	}

	add_option( 'cs_payment_totals_upgraded', 1 );
}
add_action( 'cs_upgrade_payments', 'cs_update_old_payments_with_totals' );

/**
 * Updates week-old+ 'pending' orders to 'abandoned'
 *
 *  This function is only intended to be used by WordPress cron.
 *
 * @since 1.6
 * @return void
*/
function cs_mark_abandoned_orders() {

	// Bail if not in WordPress cron
	if ( ! cs_doing_cron() ) {
		return;
	}

	$args = array(
		'status' => 'pending',
		'number' => 9999999,
		'output' => 'cs_payments',
	);

	add_filter( 'posts_where', 'cs_filter_where_older_than_week' );

	$payments = cs_get_payments( $args );

	remove_filter( 'posts_where', 'cs_filter_where_older_than_week' );

	if( $payments ) {
		foreach( $payments as $payment ) {
			if( 'pending' === $payment->post_status ) {
				$payment->status = 'abandoned';
				$payment->save();
			}
		}
	}
}
add_action( 'cs_weekly_scheduled_events', 'cs_mark_abandoned_orders' );

/**
 * Listens to the updated_postmeta hook for our backwards compatible payment_meta updates, and runs through them
 *
 * @since  2.3
 * @param  int $meta_id    The Meta ID that was updated
 * @param  int $object_id  The Object ID that was updated (post ID)
 * @param  string $meta_key   The Meta key that was updated
 * @param  string|int|float $meta_value The Value being updated
 * @return bool|int             If successful the number of rows updated, if it fails, false
 */
function cs_update_payment_backwards_compat( $meta_id, $object_id, $meta_key, $meta_value ) {

	$meta_keys = array( '_cs_payment_meta', '_cs_payment_tax' );

	if ( ! in_array( $meta_key, $meta_keys ) ) {
		return;
	}

	global $wpdb;
	switch( $meta_key ) {

		case '_cs_payment_meta':
			$meta_value   = maybe_unserialize( $meta_value );

			if( ! isset( $meta_value['tax'] ) ){
				return;
			}

			$tax_value    = $meta_value['tax'];

			$data         = array( 'meta_value' => $tax_value );
			$where        = array( 'post_id'  => $object_id, 'meta_key' => '_cs_payment_tax' );
			$data_format  = array( '%f' );
			$where_format = array( '%d', '%s' );
			break;

		case '_cs_payment_tax':
			$tax_value    = ! empty( $meta_value ) ? $meta_value : 0;
			$current_meta = cs_get_payment_meta( $object_id, '_cs_payment_meta', true );

			$current_meta['tax'] = $tax_value;
			$new_meta            = maybe_serialize( $current_meta );

			$data         = array( 'meta_value' => $new_meta );
			$where        = array( 'post_id' => $object_id, 'meta_key' => '_cs_payment_meta' );
			$data_format  = array( '%s' );
			$where_format = array( '%d', '%s' );

			break;

	}

	$updated = $wpdb->update( $wpdb->postmeta, $data, $where, $data_format, $where_format );

	if ( ! empty( $updated ) ) {
		// Since we did a direct DB query, clear the postmeta cache.
		wp_cache_delete( $object_id, 'post_meta' );
	}

	return $updated;


}
add_action( 'updated_postmeta', 'cs_update_payment_backwards_compat', 10, 4 );

/**
 * Deletes cs_stats_ transients that have expired to prevent database clogs
 *
 * @since 2.6.7
 * @return void
*/
function cs_cleanup_stats_transients() {
	global $wpdb;

	if ( defined( 'WP_SETUP_CONFIG' ) ) {
		return;
	}

	if ( defined( 'WP_INSTALLING' ) ) {
		return;
	}

	$now        = current_time( 'timestamp' );
	$transients = $wpdb->get_results( "SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '%\_transient_timeout\_cs\_stats\_%' AND option_value+0 < $now LIMIT 0, 200;" );
	$to_delete  = array();

	if( ! empty( $transients ) ) {

		foreach( $transients as $transient ) {

			$to_delete[] = $transient->option_name;
			$to_delete[] = str_replace( '_timeout', '', $transient->option_name );

		}

	}

	if ( ! empty( $to_delete ) ) {

		$option_names = implode( "','", $to_delete );
		$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name IN ('$option_names')"  );

	}

}
add_action( 'cs_daily_scheduled_events', 'cs_cleanup_stats_transients' );

/**
 * Process an attempt to complete a recoverable payment.
 *
 * @since  2.7
 * @return void
 */
function cs_recover_payment() {
	if ( empty( $_GET['payment_id'] ) ) {
		return;
	}

	$payment = new CS_Payment( $_GET['payment_id'] );
	if ( $payment->ID !== (int) $_GET['payment_id'] ) {
		return;
	}

	if ( ! $payment->is_recoverable() ) {
		return;
	}

	if (
		// Logged in, but wrong user ID
		( is_user_logged_in() && $payment->user_id != get_current_user_id() )

		// ...OR...
		||

		// Logged out, but payment is for a user
		( ! is_user_logged_in() && ! empty( $payment->user_id ) )
	) {
		$redirect = get_permalink( cs_get_option( 'purchase_history_page' ) );
		cs_set_error( 'cs-payment-recovery-user-mismatch', __( 'Error resuming payment.', 'commercestore' ) );
		cs_redirect( $redirect );
	}

	$payment->add_note( __( 'Payment recovery triggered URL', 'commercestore' ) );

	// Empty out the cart.
	CS()->cart->empty_cart();

	// Recover any downloads.
	foreach ( $payment->cart_details as $download ) {
		cs_add_to_cart( $download['id'], $download['item_number']['options'] );

		// Recover any item specific fees.
		if ( ! empty( $download['fees'] ) ) {
			foreach ( $download['fees'] as $key => $fee ) {
				$fee['id'] = ! empty( $fee['id'] ) ? $fee['id'] : $key;
				CS()->fees->add_fee( $fee );
			}
		}
	}

	// Recover any global fees.
	foreach ( $payment->fees as $key => $fee ) {
		$fee['id'] = ! empty( $fee['id'] ) ? $fee['id'] : $key;
		CS()->fees->add_fee( $fee );
	}

	// Recover any discounts.
	if ( 'none' !== $payment->discounts && ! empty( $payment->discounts ) ){
		$discounts = ! is_array( $payment->discounts ) ? explode( ',', $payment->discounts ) : $payment->discounts;

		foreach ( $discounts as $discount ) {
			cs_set_cart_discount( $discount );
		}
	}

	CS()->session->set( 'cs_resume_payment', $payment->ID );

	$redirect_args = array( 'payment-mode' => $payment->gateway );
	$redirect      = add_query_arg( $redirect_args, cs_get_checkout_uri() );
	cs_redirect( $redirect );
}
add_action( 'cs_recover_payment', 'cs_recover_payment' );

/**
 * If the payment trying to be recovered has a User ID associated with it, be sure it's the same user.
 *
 * @since  2.7
 * @return void
 */
function cs_recovery_user_mismatch() {
	if ( ! cs_is_checkout() ) {
		return;
	}

	$resuming_payment = CS()->session->get( 'cs_resume_payment' );
	if ( $resuming_payment ) {
		$payment = new CS_Payment( $resuming_payment );
		if ( is_user_logged_in() && $payment->user_id != get_current_user_id() ) {
			cs_empty_cart();
			cs_set_error( 'cs-payment-recovery-user-mismatch', __( 'Error resuming payment.', 'commercestore' ) );
			cs_redirect( get_permalink( cs_get_option( 'purchase_page' ) ) );
		}
	}
}
add_action( 'template_redirect', 'cs_recovery_user_mismatch' );

/**
 * If the payment trying to be recovered has a User ID associated with it, we need them to log in.
 *
 * @since  2.7
 * @return void
 */
function cs_recovery_force_login_fields() {
	$resuming_payment = CS()->session->get( 'cs_resume_payment' );
	if ( $resuming_payment ) {
		$payment        = new CS_Payment( $resuming_payment );
		$requires_login = cs_no_guest_checkout();
		if ( ( $requires_login && ! is_user_logged_in() ) && ( $payment->user_id > 0 && ( ! is_user_logged_in() ) ) ) {
			?>
			<div class="cs-alert cs-alert-info">
				<p><?php _e( 'To complete this payment, please login to your account.', 'commercestore' ); ?></p>
				<p>
					<a href="<?php echo esc_url( cs_get_lostpassword_url() ); ?>" title="<?php esc_attr_e( 'Lost Password', 'commercestore' ); ?>">
						<?php _e( 'Lost Password?', 'commercestore' ); ?>
					</a>
				</p>
			</div>
			<?php
			$show_register_form = cs_get_option( 'show_register_form', 'none' );

			if ( 'both' === $show_register_form || 'login' === $show_register_form ) {
				return;
			}
			do_action( 'cs_purchase_form_login_fields' );
		}
	}
}
add_action( 'cs_purchase_form_before_register_login', 'cs_recovery_force_login_fields' );

/**
 * When processing the payment, check if the resuming payment has a user id and that it matches the logged in user.
 *
 * @since 2.7
 * @param $verified_data
 * @param $post_data
 */
function cs_recovery_verify_logged_in( $verified_data, $post_data ) {
	$resuming_payment = CS()->session->get( 'cs_resume_payment' );
	if ( $resuming_payment ) {
		$payment    = new CS_Payment( $resuming_payment );
		$same_user  = ! empty( $payment->user_id ) && ( is_user_logged_in() && $payment->user_id == get_current_user_id() );
		$same_email = strtolower( $payment->email ) === strtolower( $post_data['cs_email'] );

		if ( ( is_user_logged_in() && ! $same_user ) || ( ! is_user_logged_in() && (int) $payment->user_id > 0 && ! $same_email ) ) {
			cs_set_error( 'recovery_requires_login', __( 'To complete this payment, please login to your account.', 'commercestore' ) );
		}
	}
}
add_action( 'cs_checkout_error_checks', 'cs_recovery_verify_logged_in', 10, 2 );
