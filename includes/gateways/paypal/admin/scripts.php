<?php
/**
 * PayPal Commerce Admin Scripts
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 * @since     2.11
 */

namespace CS\Gateways\PayPal\Admin;

/**
 * Enqueue PayPal connect admin JS.
 *
 * @since 2.11
 */
function enqueue_connect_scripts() {
	if ( cs_is_admin_page( 'settings' ) && isset( $_GET['section'] ) && 'paypal_commerce' === $_GET['section'] ) {
		\CS\Gateways\PayPal\maybe_enqueue_polyfills();

		$subdomain = cs_is_test_mode() ? 'sandbox.' : '';

		wp_enqueue_script(
			'sandhills-paypal-partner-js',
			'https://www.' . $subdomain . 'paypal.com/webapps/merchantboarding/js/lib/lightbox/partner.js',
			array(),
			null,
			true
		);

		wp_localize_script( 'cs-admin-settings', 'csPayPalConnectVars', array(
			'defaultError' => esc_html__( 'An unexpected error occurred. Please refresh the page and try again.', 'commercestore' )
		) );
	}
}

add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\enqueue_connect_scripts' );
