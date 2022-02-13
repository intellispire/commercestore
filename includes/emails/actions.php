<?php
/**
 * Email Actions
 *
 * @package     CS
 * @subpackage  Emails
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0.8.2
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Triggers Purchase Receipt to be sent after the payment status is updated
 *
 * @since 1.0.8.4
 * @since 2.8 - Add parameters for CS_Payment and CS_Customer object.
 *
 * @param int          $payment_id Payment ID.
 * @param CS_Payment  $payment    Payment object for payment ID.
 * @param CS_Customer $customer   Customer object for associated payment.
 * @return void
 */
function cs_trigger_purchase_receipt( $payment_id = 0, $payment = null, $customer = null ) {
	// Make sure we don't send a purchase receipt while editing a payment
	if ( isset( $_POST['cs-action'] ) && 'edit_payment' == $_POST['cs-action'] ) {
		return;
	}
	if ( null === $payment ) {
		$payment = new CS_Payment( $payment_id );
	}
	if ( $payment->order instanceof \CS\Orders\Order && 'refund' === $payment->order->type ) {
		return;
	}

	// Send email with secure download link
	cs_email_purchase_receipt( $payment_id, true, '', $payment, $customer );
}
add_action( 'cs_complete_purchase', 'cs_trigger_purchase_receipt', 999, 3 );

/**
 * Resend the Email Purchase Receipt. (This can be done from the Payment History page)
 *
 * @since 1.0
 * @param array $data Payment Data
 * @return void
 */
function cs_resend_purchase_receipt( $data ) {

	$purchase_id = absint( $data['purchase_id'] );

	if( empty( $purchase_id ) ) {
		return;
	}

	if( ! current_user_can( 'edit_shop_payments' ) ) {
		wp_die( __( 'You do not have permission to edit this payment record', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
	}

	$email = ! empty( $_GET['email'] ) ? sanitize_email( $_GET['email'] ) : '';

	if( empty( $email ) ) {
		$customer = new CS_Customer( cs_get_payment_customer_id( $purchase_id ) );
		$email    = $customer->email;
	}

	$sent = cs_email_purchase_receipt( $purchase_id, false, $email );

	// Grab all downloads of the purchase and update their file download limits, if needed
	// This allows admins to resend purchase receipts to grant additional file downloads
	$downloads = cs_get_payment_meta_cart_details( $purchase_id, true );

	if ( is_array( $downloads ) ) {
		foreach ( $downloads as $download ) {
			$limit = cs_get_file_download_limit( $download['id'] );
			if ( ! empty( $limit ) ) {
				cs_set_file_download_limit_override( $download['id'], $purchase_id );
			}
		}
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'cs-message' => $sent ? 'email_sent' : 'email_send_failed',
				'cs-action'  => false,
				'purchase_id' => false,
			)
		)
	);
	exit;
}
add_action( 'cs_email_links', 'cs_resend_purchase_receipt' );

/**
 * Trigger the sending of a Test Email
 *
 * @since 1.5
 * @param array $data Parameters sent from Settings page
 * @return void
 */
function cs_send_test_email( $data ) {
	if ( ! wp_verify_nonce( $data['_wpnonce'], 'cs-test-email' ) ) {
		return;
	}

	// Send a test email
	cs_email_test_purchase_receipt();

	$url = add_query_arg(
		array(
			'page'        => 'cs-settings',
			'tab'         => 'emails',
			'section'     => 'purchase_receipts',
			'cs-message' => 'test-purchase-email-sent',
		),
		cs_get_admin_base_url()
	);
	cs_redirect( $url );
}
add_action( 'cs_send_test_email', 'cs_send_test_email' );
