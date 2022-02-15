<?php

/**
 * Removes Stripe from active gateways if Recurring version is < 2.9.
 *
 * @since 2.7.0
 *
 * @param array $enabled_gateways Enabled gateways that allow purchasing.
 * @return array
 */
function csx_require_recurring_290( $enabled_gateways ) {
	if ( 
		isset( $enabled_gateways['stripe'] ) &&
		defined( 'CS_RECURRING_VERSION' ) &&
		! version_compare( CS_RECURRING_VERSION, '2.8.8', '>' )
	) {
		unset( $enabled_gateways['stripe'] );
	}

	return $enabled_gateways;
}
add_filter( 'cs_enabled_payment_gateways', 'csx_require_recurring_290', 20 );

/**
 * Register our new payment status labels for CS
 *
 * @since 1.6
 * @return array
 */
function csx_payment_status_labels( $statuses ) {
	$statuses['preapproval']         = __( 'Preapproved', 'commercestore' );
	$statuses['preapproval_pending'] = __( 'Preapproval Pending', 'commercestore' );
	$statuses['cancelled']           = __( 'Cancelled', 'commercestore' );
	return $statuses;
}
add_filter( 'cs_payment_statuses', 'csx_payment_status_labels' );

/**
 * Sets the stripe-checkout parameter if the direct parameter is present in the [purchase_link] short code
 *
 * @since  2.0
 * @return array
 */
function cs_stripe_purchase_link_shortcode_atts( $out, $pairs, $atts ) {

	if( ! empty( $out['direct'] ) ) {

		$out['stripe-checkout'] = true;
		$out['direct'] = true;

	} else {

		foreach( $atts as $key => $value ) {
			if( false !== strpos( $value, 'stripe-checkout' ) ) {
				$out['stripe-checkout'] = true;
				$out['direct'] = true;
			}
		}

	}

	return $out;
}
add_filter( 'shortcode_atts_purchase_link', 'cs_stripe_purchase_link_shortcode_atts', 10, 3 );

/**
 * Sets the stripe-checkout parameter if the direct parameter is present in cs_get_purchase_link()
 *
 * @since  2.0
 * @return array
 */
function cs_stripe_purchase_link_atts( $args ) {

	if( ! empty( $args['direct'] ) && cs_is_gateway_active( 'stripe' ) ) {

		$args['stripe-checkout'] = true;
		$args['direct'] = true;
	}

	return $args;
}
add_filter( 'cs_purchase_link_args', 'cs_stripe_purchase_link_atts', 10 );

/**
 * Injects the Stripe token and customer email into the pre-gateway data
 *
 * @since  2.0
 * @return array
 */
function cs_stripe_straight_to_gateway_data( $purchase_data ) {

	$gateways = cs_get_enabled_payment_gateways();

	if ( isset( $gateways['stripe'] ) ) {
		$_REQUEST['cs-gateway']  = 'stripe';
		$purchase_data['gateway'] = 'stripe';
	}

	return $purchase_data;
}
add_filter( 'cs_straight_to_gateway_purchase_data', 'cs_stripe_straight_to_gateway_data' );

/**
 * Process the POST Data for the Credit Card Form, if a token wasn't supplied
 *
 * @since  2.2
 * @return array The credit card data from the $_POST
 */
function csx_process_post_data( $purchase_data ) {
	if ( ! isset( $purchase_data['gateway'] ) || 'stripe' !== $purchase_data['gateway'] ) {
		return;
	}

	if ( isset( $_POST['cs_stripe_existing_card'] ) && 'new' !== $_POST['cs_stripe_existing_card'] ) {
		return;
	}

	// Require a name for new cards.
	if ( ! isset( $_POST['card_name'] ) || strlen( trim( $_POST['card_name'] ) ) === 0 ) {
		cs_set_error( 'no_card_name', __( 'Please enter a name for the credit card.', 'commercestore' ) );
	}
}
add_action( 'cs_checkout_error_checks', 'csx_process_post_data' );

/**
 * Retrieves the locale used for Checkout modal window
 *
 * @since  2.5
 * @return string The locale to use
 */
function csx_get_stripe_checkout_locale() {
	return apply_filters( 'cs_stripe_checkout_locale', 'auto' );
}