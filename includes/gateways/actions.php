<?php
/**
 * Gateway Actions
 *
 * @package     CS
 * @subpackage  Gateways
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.7
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Processes gateway select on checkout. Only for users without ajax / javascript
 *
 * @since 1.7
 *
 * @param $data
 */
function cs_process_gateway_select( $data ) {
	if( isset( $_POST['gateway_submit'] ) ) {
		cs_redirect( add_query_arg( 'payment-mode', $_POST['payment-mode'] ) );
	}
}
add_action( 'cs_gateway_select', 'cs_process_gateway_select' );

/**
 * Loads a payment gateway via AJAX.
 *
 * @since 1.3.4
 * @since 2.9.4 Added nonce verification prior to loading the purchase form.
 */
function cs_load_ajax_gateway() {
	if ( ! isset( $_POST['nonce'] ) ) {
		cs_debug_log( __( 'Missing nonce when loading the gateway fields. Please read the following for more information: https://commercestore.com/development/2018/07/05/important-update-to-ajax-requests-in-commercestore-2-9-4', 'commercestore' ), true );
	}

	if ( isset( $_POST['cs_payment_mode'] ) && isset( $_POST['nonce'] ) ) {
		$payment_mode = sanitize_text_field( $_POST['cs_payment_mode'] );
		$nonce        = sanitize_text_field( $_POST['nonce'] );

		$nonce_verified = wp_verify_nonce( $nonce, 'cs-gateway-selected-' . $payment_mode );

		if ( false !== $nonce_verified ) {
			do_action( 'cs_purchase_form' );
		}

		exit();
	}
}
add_action( 'wp_ajax_cs_load_gateway', 'cs_load_ajax_gateway' );
add_action( 'wp_ajax_nopriv_cs_load_gateway', 'cs_load_ajax_gateway' );

/**
 * Sets an error on checkout if no gateways are enabled
 *
 * @since 1.3.4
 * @return void
 */
function cs_no_gateway_error() {
	$gateways = cs_get_enabled_payment_gateways();

	if ( empty( $gateways ) && cs_get_cart_total() > 0 ) {
		remove_action( 'cs_after_cc_fields', 'cs_default_cc_address_fields' );
		remove_action( 'cs_cc_form', 'cs_get_cc_form' );
		cs_set_error( 'no_gateways', __( 'You must enable a payment gateway to use CommerceStore', 'commercestore' ) );
	} else {
		cs_unset_error( 'no_gateways' );
	}
}
add_action( 'init', 'cs_no_gateway_error' );
