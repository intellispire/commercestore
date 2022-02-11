<?php
/**
 * Manual Gateway
 *
 * @package     CS
 * @subpackage  Gateways
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Manual Gateway does not need a CC form, so remove it.
 *
 * @since 1.0
 * @return void
 */
add_action( 'cs_manual_cc_form', '__return_false' );

/**
 * Processes the purchase data and uses the Manual Payment gateway to record
 * the transaction in the Purchase History
 *
 * @since 1.0
 * @param array $purchase_data Purchase Data
 * @return void
*/
function cs_manual_payment( $purchase_data ) {
	if( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'cs-gateway' ) ) {
		wp_die( __( 'Nonce verification has failed', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
	}

	/*
	* Purchase data comes in like this
	*
	$purchase_data = array(
		'downloads' => array of download IDs,
		'price' => total price of cart contents,
		'purchase_key' =>  // Random key
		'user_email' => $user_email,
		'date' => date('Y-m-d H:i:s'),
		'user_id' => $user_id,
		'post_data' => $_POST,
		'user_info' => array of user's information and used discount code
		'cart_details' => array of cart details,
	);
	*/

	$payment_data = array(
		'price'        => $purchase_data['price'],
		'date'         => $purchase_data['date'],
		'user_email'   => $purchase_data['user_email'],
		'purchase_key' => $purchase_data['purchase_key'],
		'currency'     => cs_get_currency(),
		'downloads'    => $purchase_data['downloads'],
		'user_info'    => $purchase_data['user_info'],
		'cart_details' => $purchase_data['cart_details'],
		'status'       => 'pending',
	);

	// Record the pending payment
	$payment = cs_insert_payment( $payment_data );

	if ( $payment ) {
		cs_update_payment_status( $payment, 'complete' );
		// Empty the shopping cart
		cs_empty_cart();
		cs_send_to_success_page();
	} else {
		cs_record_gateway_error( __( 'Payment Error', 'commercestore' ), sprintf( __( 'Payment creation failed while processing a manual (free or test) purchase. Payment data: %s', 'commercestore' ), json_encode( $payment_data ) ), $payment );
		// If errors are present, send the user back to the purchase page so they can be corrected
		cs_send_back_to_checkout( '?payment-mode=' . $purchase_data['post_data']['cs-gateway'] );
	}
}
add_action( 'cs_gateway_manual', 'cs_manual_payment' );
