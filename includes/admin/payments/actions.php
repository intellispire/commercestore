<?php
/**
 * Admin Payment Actions
 *
 * @package     CS
 * @subpackage  Admin/Payments
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.9
*/

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Handle order item changes
 *
 * @since 3.0
 *
 * @param array $request
 *
 * @return boolean
 */
function cs_handle_order_item_change( $request = array() ) {

	// Bail if missing necessary properties
	if (
		empty( $request['_wpnonce']   ) ||
		empty( $request['id']         ) ||
		empty( $request['order_item'] )
	) {
		return false;
	}

	// Bail if nonce check fails
	if ( ! wp_verify_nonce( $request['_wpnonce'], 'cs_order_item_nonce' ) ) {
		return false;
	}

	// Default data
	$data = array();

	// Maybe add status to data to update
	if ( ! empty( $request['status'] ) && ( 'inherit' === $request['status'] ) || in_array( $request['status'], array_keys( cs_get_payment_statuses() ), true )) {
		$data['status'] = sanitize_key( $request['status'] );
	}

	// Update order item
	if ( ! empty( $data ) ) {
		cs_update_order_item( $request['order_item'], $data );
		cs_redirect( cs_get_admin_url( array(
			'page' => 'cs-payment-history',
			'view' => 'view-order-details',
			'id'   => absint( $request['id'] )
		) ) );
	}
}
add_action( 'cs_handle_order_item_change', 'cs_handle_order_item_change' );

/**
 * Process the changes from the `View Order Details` page.
 *
 * @since 1.9
 * @since 3.0 Refactored to use new core objects and query methods.
 *
 * @param array $data Order data.
*/
function cs_update_payment_details( $data = array() ) {

	// Bail if an empty array is passed.
	if ( empty( $data ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this payment record', 'commercestore' ), esc_html__( 'Error', 'commercestore' ), array( 'response' => 403 ) );
	}

	// Bail if the user does not have the correct permissions.
	if ( ! current_user_can( 'edit_shop_payments', $data['cs_payment_id'] ) ) {
		wp_die( esc_html__( 'You do not have permission to edit this payment record', 'commercestore' ), esc_html__( 'Error', 'commercestore' ), array( 'response' => 403 ) );
	}

	check_admin_referer( 'cs_update_payment_details_nonce' );

	// Retrieve the order ID and set up the order.
	$order_id = absint( $data['cs_payment_id'] );
	$order    = cs_get_order( $order_id );

	$order_update_args = array();

	$unlimited  = isset( $data['cs-unlimited-downloads'] ) ? '1' : null;
	$new_status = sanitize_key( $data['cs-payment-status'] );
	$date       = sanitize_text_field( $data['cs-payment-date'] );
	$hour       = sanitize_text_field( $data['cs-payment-time-hour'] );

	// Restrict to our high and low
	if ( $hour > 23 ) {
		$hour = 23;
	} elseif ( $hour < 0 ) {
		$hour = 00;
	}

	$minute = sanitize_text_field( $data['cs-payment-time-min'] );

	// Restrict to our high and low
	if ( $minute > 59 ) {
		$minute = 59;
	} elseif ( $minute < 0 ) {
		$minute = 00;
	}

	// The date is entered in the WP timezone. We need to convert it to UTC prior to saving now.
	$date = cs_get_utc_equivalent_date( CS()->utils->date( $date . ' ' . $hour . ':' . $minute . ':00', cs_get_timezone_id(), false ) );
	$date = $date->format( 'Y-m-d H:i:s' );

	$order_update_args['date_created'] = $date;

	// Customer
	$curr_customer_id = sanitize_text_field( $data['current-customer-id'] );
	$new_customer_id  = sanitize_text_field( $data['customer-id'] );

	// Create a new customer.
	if ( isset( $data['cs-new-customer'] ) && 1 === (int) $data['cs-new-customer'] ) {
		$email = isset( $data['cs-new-customer-email'] )
			? sanitize_text_field( $data['cs-new-customer-email'] )
			: '';

		// Sanitize first name
		$first_name = isset( $data['cs-new-customer-first-name'] )
			? sanitize_text_field( $data['cs-new-customer-first-name'] )
			: '';

		// Sanitize last name
		$last_name = isset( $data['cs-new-customer-last-name'] )
			? sanitize_text_field( $data['cs-new-customer-last-name'] )
			: '';

		// Combine
		$name = $first_name . ' ' . $last_name;

		if ( empty( $email ) || empty( $name ) ) {
			wp_die( esc_html__( 'New Customers require a name and email address', 'commercestore' ) );
		}

		$customer = new CS_Customer( $email );

		if ( empty( $customer->id ) ) {
			$customer_data = array(
				'name'  => $name,
				'email' => $email,
			);

			$user_id = email_exists( $email );

			if ( false !== $user_id ) {
				$customer_data['user_id'] = $user_id;
			}

			if ( ! $customer->create( $customer_data ) ) {
				$customer = new CS_Customer( $curr_customer_id );
				cs_set_error( 'cs-payment-new-customer-fail', __( 'Error creating new customer', 'commercestore' ) );
			}
		} else {
			wp_die( sprintf( __( 'A customer with the email address %s already exists. Please go back and assign this payment to them.', 'commercestore' ), $email ) );
		}

		$previous_customer = new CS_Customer( $curr_customer_id );
	} elseif ( $curr_customer_id !== $new_customer_id ) {
		$customer = new CS_Customer( $new_customer_id );

		$previous_customer = new CS_Customer( $curr_customer_id );
	} else {
		$customer = new CS_Customer( $curr_customer_id );
	}

	// Remove the stats and payment from the previous customer and attach it to the new customer
	if ( isset( $previous_customer ) ) {
		$previous_customer->remove_payment( $order_id, false );
		$customer->attach_payment( $order_id, false );

		// If purchase was completed and not ever refunded, adjust stats of customers
		if ( 'revoked' === $new_status || 'complete' === $new_status ) {
			$previous_customer->recalculate_stats();

			if ( ! empty( $customer ) ) {
				$customer->recalculate_stats();
			}
		}

		$order_update_args['customer_id'] = $customer->id;
	}

	$order_update_args['user_id'] = $customer->user_id;
	$order_update_args['email']   = $customer->email;

	// Address
	$address = $data['cs_order_address'];

	cs_update_order_address( absint( $address['address_id'] ), array(
		'name'        => $customer->name,
		'address'     => $address['address'],
		'address2'    => $address['address2'],
		'city'        => $address['city'],
		'region'      => $address['region'],
		'postal_code' => $address['postal_code'],
		'country'     => $address['country'],
	) );

	// Unlimited downloads.
	if ( 1 === (int) $unlimited ) {
		cs_update_order_meta( $order_id, 'unlimited_downloads', $unlimited );
	} else {
		cs_delete_order_meta( $order_id, 'unlimited_downloads' );
	}

	// Don't set the status in the update call (for back compat)
	unset( $order_update_args['status'] );

	// Attempt to update the order
	$updated = cs_update_order( $order_id, $order_update_args );

	// Bail if an error occurred
	if ( false === $updated ) {
		wp_die( __( 'Error updating order.', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 400 ) );
	}

	// Check if the status has changed, if so, we need to invoke the pertinent
	// status processing method (for back compat)
	if ( $new_status !== $order->status ) {
		cs_update_order_status( $order_id, $new_status );
	}

	do_action( 'cs_updated_edited_purchase', $order_id );

	cs_redirect( cs_get_admin_url( array(
		'page'        => 'cs-payment-history',
		'view'        => 'view-order-details',
		'cs-message' => 'payment-updated',
		'id'          => $order_id
	) ) );
}
add_action( 'cs_update_payment_details', 'cs_update_payment_details' );

/**
 * New in 3.0, permanently destroys an order, and all its data, and related data.
 *
 * @since 3.0
 *
 * @param array $data Arguments passed.
 */
function cs_trigger_destroy_order( $data ) {

	if ( wp_verify_nonce( $data['_wpnonce'], 'cs_payment_nonce' ) ) {

		$payment_id = absint( $data['purchase_id'] );

		if ( ! current_user_can( 'delete_shop_payments', $payment_id ) ) {
			wp_die( esc_html__( 'You do not have permission to edit this order.', 'commercestore' ), esc_html__( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}

		cs_destroy_order( $payment_id );

		$redirect_link = add_query_arg(
			array(
				'page'        => 'cs-payment-history',
				'cs-message' => 'payment_deleted',
				'status'      => 'trash',
			),
			cs_get_admin_base_url()
		);
		cs_redirect( $redirect_link );
	}
}
add_action( 'cs_delete_order', 'cs_trigger_destroy_order' );

/**
 * Trigger the action of moving an order to the 'trash' status
 *
 * @since 3.0
 *
 * @param $data
 * @return void
 */
function cs_trigger_trash_order( $data ) {
	if ( wp_verify_nonce( $data['_wpnonce'], 'cs_payment_nonce' ) ) {

		$payment_id = absint( $data['purchase_id'] );

		if ( ! current_user_can( 'delete_shop_payments', $payment_id ) ) {
			wp_die( __( 'You do not have permission to edit this payment record', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}

		cs_trash_order( $payment_id );

		$redirect = cs_get_admin_url( array(
			'page'        => 'cs-payment-history',
			'cs-message' => 'order_trashed',
			'order_type'  => esc_attr( $data['order_type'] ),
		) );

		cs_redirect( esc_url_raw( $redirect ) );
	}
}
add_action( 'cs_trash_order', 'cs_trigger_trash_order' );

/**
 * Trigger the action of restoring an order from the 'trash' status
 *
 * @since 3.0
 *
 * @param $data
 * @return void
 */
function cs_trigger_restore_order( $data ) {
	if ( wp_verify_nonce( $data['_wpnonce'], 'cs_payment_nonce' ) ) {

		$payment_id = absint( $data['purchase_id'] );

		if ( ! current_user_can( 'delete_shop_payments', $payment_id ) ) {
			wp_die( __( 'You do not have permission to edit this payment record', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}

		cs_restore_order( $payment_id );

		$redirect = cs_get_admin_url( array(
			'page'        => 'cs-payment-history',
			'cs-message' => 'order_restored',
			'order_type'  => esc_attr( $data['order_type'] ),
		) );

		cs_redirect( esc_url_raw( $redirect ) );
	}
}
add_action( 'cs_restore_order', 'cs_trigger_restore_order' );

/**
 * Retrieves a new download link for a purchased file
 *
 * @since 2.0
 * @return string
*/
function cs_ajax_generate_file_download_link() {

	$customer_view_role = apply_filters( 'cs_view_customers_role', 'view_shop_reports' );
	if ( ! current_user_can( $customer_view_role ) ) {
		die( '-1' );
	}

	$payment_id  = absint( $_POST['payment_id'] );
	$download_id = absint( $_POST['download_id'] );
	$price_id    = absint( $_POST['price_id'] );

	if ( empty( $payment_id ) ) {
		die( '-2' );
	}

	if ( empty( $download_id ) ) {
		die( '-3' );
	}

	$payment_key = cs_get_payment_key( $payment_id );
	$email       = cs_get_payment_user_email( $payment_id );

	$limit = cs_get_file_download_limit( $download_id );
	if ( ! empty( $limit ) ) {
		// Increase the file download limit when generating new links
		cs_set_file_download_limit_override( $download_id, $payment_id );
	}

	$files = cs_get_download_files( $download_id, $price_id );
	if ( ! $files ) {
		die( '-4' );
	}

	$file_urls = '';

	foreach( $files as $file_key => $file ) {
		$file_urls .= cs_get_download_file_url( $payment_key, $email, $file_key, $download_id, $price_id );
		$file_urls .= "\n\n";
	}

	die( $file_urls );
}
add_action( 'wp_ajax_cs_get_file_download_link', 'cs_ajax_generate_file_download_link' );

/**
 * Renders the refund form that is used to process a refund.
 *
 * @since 3.0
 *
 * @return void
 */
function cs_ajax_generate_refund_form() {

	// Verify we have a logged user.
	if ( ! is_user_logged_in() ) {
		$return = array(
			'success' => false,
			'message' => __( 'You must be logged in to perform this action.', 'commercestore' ),
		);

		wp_send_json( $return, 401 );
	}

	// Verify the logged in user has permission to edit shop payments.
	if ( ! current_user_can( 'edit_shop_payments' ) ) {
		$return = array(
			'success' => false,
			'message' => __( 'Your account does not have permission to perform this action.', 'commercestore' ),
		);

		wp_send_json( $return, 401 );
	}

	$order_id = isset( $_POST['order_id'] ) && is_numeric( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : false;

	if ( empty( $order_id ) ) {
		$return = array(
			'success' => false,
			'message' => __( 'Invalid order ID', 'commercestore' ),
		);

		wp_send_json( $return, 400 );
	}

	$order = cs_get_order( $order_id );
	if ( empty( $order ) ) {
		$return = array(
			'success' => false,
			'message' => __( 'Invalid order', 'commercestore' ),
		);

		wp_send_json( $return, 404 );
	}

	if ( 'refunded' === $order->status ) {
		$return = array(
			'success' => false,
			'message' => __( 'Order is already refunded', 'commercestore' ),
		);

		wp_send_json( $return, 404 );
	}

	if ( 'refund' === $order->type ) {
		$return = array(
			'success' => false,
			'message' => __( 'Cannot refund an order that is already refunded.', 'commercestore' ),
		);

		wp_send_json( $return, 404 );
	}

	// Output buffer the form before we include it in the JSON response.
	ob_start();
	?>
	<div id="cs-submit-refund-status" style="display: none;">
		<span class="cs-submit-refund-message"></span>
		<a class="cs-submit-refund-url" href=""><?php _e( 'View Refund', 'commercestore' ); ?></a>
	</div>
	<form id="cs-submit-refund-form" method="POST">
		<?php
		// Load list table if not already loaded
		if ( ! class_exists( '\\CS\\Admin\\Refund_Items_Table' ) ) {
			require_once 'class-refund-items-table.php';
		}

		$refund_items = new CS\Admin\Refund_Items_Table();
		$refund_items->prepare_items();
		$refund_items->display();
		?>
	</form>
	<?php
	$html = trim( ob_get_clean() );

	$return = array(
		'success' => true,
		'html'    => $html,
	);

	wp_send_json( $return, 200 );

}
add_action( 'wp_ajax_cs_generate_refund_form', 'cs_ajax_generate_refund_form' );

/**
 * Processes the results from the Submit Refund form
 *
 * @since 3.0
 * @return void
 */
function cs_ajax_process_refund_form() {

	// Verify we have a logged user.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( __( 'You must be logged in to perform this action.', 'commercestore' ), 401 );
	}

	// Verify the logged in user has permission to edit shop payments.
	if ( ! current_user_can( 'edit_shop_payments' ) ) {
		wp_send_json_error( __( 'Your account does not have permission to perform this action.', 'commercestore' ), 401 );
	}

	if ( empty( $_POST['data'] ) || empty( $_POST['order_id'] ) ) {
		wp_send_json_error( __( 'Missing form data or order ID.', 'commercestore' ), 400 );
	}

	// Get our data out of the serialized string.
	parse_str( $_POST['data'], $form_data );

	// Verify the nonce.
	$nonce = ! empty( $form_data['cs_process_refund'] ) ? sanitize_text_field( $form_data['cs_process_refund'] ) : false;
	if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'cs_process_refund' ) ) {
		wp_send_json_error( __( 'Nonce validation failed when submitting refund.', 'commercestore' ), 401 );
	}

	// Collect selected order items.
	$order_items = array();
	if ( ! empty( $form_data['refund_order_item'] ) && is_array( $form_data['refund_order_item'] ) ) {
		foreach( $form_data['refund_order_item'] as $order_item_id => $order_item ) {
			// If there's no quantity or subtotal - bail.
			if ( empty( $order_item['quantity'] ) || empty( $order_item['subtotal'] ) ) {
				continue;
			}

			$order_items[] = array(
				'order_item_id' => absint( $order_item_id ),
				'quantity'      => intval( $order_item['quantity'] ),
				'subtotal'      => cs_sanitize_amount( $order_item['subtotal'] ),
				'tax'           => ! empty( $order_item['tax'] ) ? cs_sanitize_amount( $order_item['tax'] ) : 0.00
			);
		}
	}

	// Collect selected adjustments.
	$adjustments = array();
	if ( ! empty( $form_data['refund_order_adjustment'] ) && is_array( $form_data['refund_order_adjustment'] ) ) {
		foreach( $form_data['refund_order_adjustment'] as $adjustment_id => $adjustment ) {
			// If there's no quantity or subtotal - bail.
			if ( empty( $adjustment['quantity'] ) || empty( $adjustment['subtotal'] ) ) {
				continue;
			}

			$adjustments[] = array(
				'adjustment_id' => absint( $adjustment_id ),
				'quantity'      => intval( $adjustment['quantity'] ),
				'subtotal'      => floatval( cs_sanitize_amount( $adjustment['subtotal'] ) ),
				'tax'           => ! empty( $adjustment['tax'] ) ? floatval( cs_sanitize_amount( $adjustment['tax'] ) ) : 0.00
			);
		}
	}

	$order_id  = absint( $_POST['order_id'] );
	$refund_id = cs_refund_order( $order_id, $order_items, $adjustments );

	if ( is_wp_error( $refund_id ) ) {
		wp_send_json_error( $refund_id->get_error_message() );
	} elseif ( ! empty( $refund_id ) ) {
		$return = array(
			'refund_id'  => $refund_id,
			'message'    => sprintf( __( 'Refund successfully processed.', 'commercestore' ) ),
			'refund_url' => cs_get_admin_url(
				array(
					'page'      => 'cs-payment-history',
					'view'      => 'view-refund-details',
					'id'        => urlencode( $refund_id ),
				)
			)
		);
		wp_send_json_success( $return, 200 );
	} else {
		wp_send_json_error( __( 'Unable to process refund.', 'commercestore' ), 401 );
	}
}
add_action( 'wp_ajax_cs_process_refund_form', 'cs_ajax_process_refund_form' );

/**
 * Process Orders list table bulk actions. This is necessary because we need to
 * redirect to ensure filters do not get applied when bulk actions are being
 * processed. This processing cannot happen within the `CS_Payment_History_Table`
 * class as at that point, it is too late to do a redirect.
 *
 * @since 3.0
 */
function cs_orders_list_table_process_bulk_actions() {

	// Bail if this method was called directly.
	if ( 'load-download_page_cs-payment-history' !== current_action() ) {
		_doing_it_wrong( __FUNCTION__, 'This method is not meant to be called directly.', 'CS 3.0' );
	}

	$action = isset( $_REQUEST['action'] ) // WPCS: CSRF ok.
		? sanitize_text_field( $_REQUEST['action'] )
		: '';

	// Bail if we aren't processing bulk actions.
	if ( '-1' === $action ) {
		return;
	}

	$ids = isset( $_GET['order'] ) // WPCS: CSRF ok.
		? $_GET['order']
		: false;

	if ( ! is_array( $ids ) ) {
		$ids = array( $ids );
	}

	if ( empty( $action ) ) {
		return;
	}

	$ids = wp_parse_id_list( $ids );

	foreach ( $ids as $id ) {
		switch ( $action ) {
			case 'trash':
				cs_trash_order( $id );
				break;
			case 'restore':
				cs_restore_order( $id );
				break;
			case 'set-status-complete':
				cs_update_payment_status( $id, 'complete' );
				break;

			case 'set-status-pending':
				cs_update_payment_status( $id, 'pending' );
				break;

			case 'set-status-processing':
				cs_update_payment_status( $id, 'processing' );
				break;

			case 'set-status-revoked':
				cs_update_payment_status( $id, 'revoked' );
				break;

			case 'set-status-failed':
				cs_update_payment_status( $id, 'failed' );
				break;

			case 'set-status-abandoned':
				cs_update_payment_status( $id, 'abandoned' );
				break;

			case 'set-status-preapproval':
				cs_update_payment_status( $id, 'preapproval' );
				break;

			case 'set-status-cancelled':
				cs_update_payment_status( $id, 'cancelled' );
				break;

			case 'resend-receipt':
				cs_email_purchase_receipt( $id, false );
				break;
		}

		do_action( 'cs_payments_table_do_bulk_action', $id, $action );
	}

	wp_redirect( wp_get_referer() );
}
add_action( 'load-download_page_cs-payment-history', 'cs_orders_list_table_process_bulk_actions' );
