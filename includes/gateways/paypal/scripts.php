<?php
/**
 * PayPal Commerce Scripts
 *
 * @package    Sandhills Development, LLC
 * @subpackage Gateways\PayPal
 * @copyright  Copyright (c) 2021, Ashley Gibson
 * @license    GPL2+
 * @since      2.11
 */

namespace CS\Gateways\PayPal;

use CS\Gateways\PayPal\Exceptions\Authentication_Exception;

/**
 * Enqueues polyfills for Promise and Fetch.
 *
 * @since 2.11
 */
function maybe_enqueue_polyfills() {
	/**
	 * Filters whether or not IE11 polyfills should be loaded.
	 * Note: This filter may have its default changed at any time, or may entirely
	 * go away at one point.
	 *
	 * @since 2.11
	 */
	if ( ! apply_filters( 'cs_load_ie11_polyfills', true ) ) {
		return;
	}

	global $wp_version;
	if ( version_compare( $wp_version, '5.0', '>=' ) ) {
		wp_enqueue_script( 'wp-polyfill' );
	} else {
		wp_enqueue_script(
			'wp-polyfill',
			CS_PLUGIN_URL . 'assets/js/wp-polyfill.min.js',
			array(),
			false,
			false
		);
	}
}

/**
 * Registers PayPal JavaScript
 *
 * @param bool $force_load
 *
 * @since 2.11
 * @return void
 */
function register_js( $force_load = false ) {
	if ( ! cs_is_gateway_active( 'paypal_commerce' ) ) {
		return;
	}

	if ( ! ready_to_accept_payments() ) {
		return;
	}

	try {
		$api = new API();
	} catch ( Authentication_Exception $e ) {
		return;
	}

	/**
	 * Filters the query arguments added to the SDK URL.
	 *
	 * @link  https://developer.paypal.com/docs/checkout/reference/customize-sdk/#query-parameters
	 *
	 * @since 2.11
	 */
	$sdk_query_args = apply_filters( 'cs_paypal_js_sdk_query_args', array(
		'client-id'       => urlencode( $api->client_id ),
		'currency'        => urlencode( strtoupper( cs_get_currency() ) ),
		'intent'          => 'capture',
		'disable-funding' => 'card,credit,bancontact,blik,eps,giropay,ideal,mercadopago,mybank,p24,sepa,sofort,venmo'
	) );

	wp_register_script(
		'sandhills-paypal-js-sdk',
		add_query_arg( array_filter( $sdk_query_args ), 'https://www.paypal.com/sdk/js' )
	);

	wp_register_script(
		'cs-paypal',
		CS_PLUGIN_URL . 'assets/js/paypal-checkout.js',
		array(
			'sandhills-paypal-js-sdk',
			'jquery',
			'cs-ajax'
		),
		CS_VERSION,
		true
	);

	if ( cs_is_checkout() || $force_load ) {
		maybe_enqueue_polyfills();

		wp_enqueue_script( 'sandhills-paypal-js-sdk' );
		wp_enqueue_script( 'cs-paypal' );

		$paypal_script_vars = array(
			/**
			 * Filters the order approval handler.
			 *
			 * @since 2.11
			 */
			'approvalAction' => apply_filters( 'cs_paypal_on_approve_action', 'cs_capture_paypal_order' ),
			'defaultError'   => cs_build_errors_html( array(
				'paypal-error' => esc_html__( 'An unexpected error occurred. Please try again.', 'commercestore' )
			) ),
			'intent'         => ! empty( $sdk_query_args['intent'] ) ? $sdk_query_args['intent'] : 'capture',
			'style'          => get_button_styles()
		);

		wp_localize_script( 'cs-paypal', 'csPayPalVars', $paypal_script_vars );
	}
}

add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\register_js', 100 );

/**
 * Removes the "?ver=" query arg from the PayPal JS SDK URL, because PayPal will throw an error
 * if it's included.
 *
 * @param string $url
 *
 * @since 2.11
 * @return string
 */
function remove_ver_query_arg( $url ) {
	$sdk_url = 'https://www.paypal.com/sdk/js';

	if ( false !== strpos( $url, $sdk_url ) ) {
		$new_url = preg_split( "/(&ver|\?ver)/", $url );

		return $new_url[0];
	}

	return $url;
}

add_filter( 'script_loader_src', __NAMESPACE__ . '\remove_ver_query_arg', 100 );

/**
 * Adds data attributes to the PayPal JS SDK <script> tag.
 *
 * @link  https://developer.paypal.com/docs/checkout/reference/customize-sdk/#script-parameters
 *
 * @since 2.11
 *
 * @param string $script_tag HTML <script> tag.
 * @param string $handle     Registered handle.
 * @param string $src        Script SRC value.
 *
 * @return string
 */
function add_data_attributes( $script_tag, $handle, $src ) {
	if ( 'sandhills-paypal-js-sdk' !== $handle ) {
		return $script_tag;
	}

	/**
	 * Filters the data attributes to add to the <script> tag.
	 *
	 * @since 2.11
	 *
	 * @param array $data_attributes
	 */
	$data_attributes = apply_filters( 'cs_paypal_js_sdk_data_attributes', array(
		'partner-attribution-id' => CS_PAYPAL_PARTNER_ATTRIBUTION_ID
	) );

	if ( empty( $data_attributes ) || ! is_array( $data_attributes ) ) {
		return $script_tag;
	}

	$formatted_attributes = array_map( function ( $key, $value ) {
		return sprintf( 'data-%s="%s"', sanitize_html_class( $key ), esc_attr( $value ) );
	}, array_keys( $data_attributes ), $data_attributes );

	return str_replace( ' src', ' ' . implode( ' ', $formatted_attributes ) . ' src', $script_tag );
}

add_filter( 'script_loader_tag', __NAMESPACE__ . '\add_data_attributes', 10, 3 );
