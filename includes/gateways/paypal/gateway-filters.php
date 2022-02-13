<?php
/**
 * PayPal Commerce Gateway Filters
 *
 * @package    commercestore
 * @subpackage Gateways\PayPal
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    GPL2+
 */

namespace CS\Gateways\PayPal;

/**
 * Removes PayPal Standard from the list of available gateways while we're on the CommerceStore Settings page.
 * This prevents PayPal Standard from being enabled as a gateway if:
 *
 *      - The store owner has never used PayPal Standard; or
 *      - The store Owner used PayPal Standard previously but has now been onboarded to PayPal Commerce.
 *
 * @param array $gateways
 *
 * @since 2.11
 * @return array
 */
function maybe_remove_paypal_standard( $gateways ) {
	if ( function_exists( 'cs_is_admin_page' ) && cs_is_admin_page( 'settings' ) && ! paypal_standard_enabled() ) {
		unset( $gateways['paypal'] );
	}

	return $gateways;
}

add_filter( 'cs_payment_gateways', __NAMESPACE__ . '\maybe_remove_paypal_standard' );

/**
 * Creates a link to the transaction within PayPal.
 *
 * @param string $transaction_id PayPal transaction ID.
 * @param int    $payment_id     ID of the payment.
 *
 * @since 2.11
 * @return string
 */
function link_transaction_id( $transaction_id, $payment_id ) {
	if ( empty( $transaction_id ) ) {
		return $transaction_id;
	}

	$payment = cs_get_payment( $payment_id );

	if ( ! $payment ) {
		return $transaction_id;
	}

	$subdomain       = ( 'test' === $payment->mode ) ? 'sandbox.' : '';
	$transaction_url = 'https://' . urlencode( $subdomain ) . 'paypal.com/activity/payment/' . urlencode( $transaction_id );

	return '<a href="' . esc_url( $transaction_url ) . '" target="_blank">' . esc_html( $transaction_id ) . '</a>';
}

add_filter( 'cs_payment_details_transaction_id-paypal_commerce', __NAMESPACE__ . '\link_transaction_id', 10, 2 );

/**
 * By default, CS_Payment converts an empty transaction ID to be the ID of the payment.
 * We don't want that to happen... Empty should be empty.
 *
 * @since 2.11
 */
add_filter( 'cs_get_payment_transaction_id-paypal_commerce', '__return_false' );
