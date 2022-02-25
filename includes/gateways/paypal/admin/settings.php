<?php
/**
 * PayPal Settings
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 * @since     2.11
 */

namespace CS\Gateways\PayPal\Admin;

/**
 * Returns the URL to the PayPal Commerce settings page.
 *
 * @since 2.11
 *
 * @return string
 */
function get_settings_url() {
	return admin_url( 'edit.php?post_type=' . CS_POST_TYPE . '&page=cs-settings&tab=gateways&section=paypal_commerce' );
}

/**
 * Register the PayPal Standard gateway subsection
 *
 * @param array $gateway_sections Current Gateway Tab subsections
 *
 * @since 2.11
 * @return array                    Gateway subsections with PayPal Standard
 */
function register_paypal_gateway_section( $gateway_sections ) {
	$gateway_sections['paypal_commerce'] = __( 'PayPal', 'commercestore' );

	return $gateway_sections;
}

add_filter( 'cs_settings_sections_gateways', __NAMESPACE__ . '\register_paypal_gateway_section', 1, 1 );

/**
 * Registers the PayPal Standard settings for the PayPal Standard subsection
 *
 * @param array $gateway_settings Gateway tab settings
 *
 * @since 2.11
 * @return array Gateway tab settings with the PayPal Standard settings
 */
function register_gateway_settings( $gateway_settings ) {

	$paypal_settings = array(
		'paypal_settings'              => array(
			'id'   => 'paypal_settings',
			'name' => '<h3>' . __( 'PayPal Settings', 'commercestore' ) . '</h3>',
			'type' => 'header',
		),
		'paypal_documentation'         => array(
			'id'   => 'paypal_documentation',
			'name' => __( 'Documentation', 'commercestore' ),
			'desc' => documentation_settings_field(),
			'type' => 'descriptive_text'
		),
		'paypal_connect_button'        => array(
			'id'    => 'paypal_connect_button',
			'name'  => __( 'Connection Status', 'commercestore' ),
			'desc'  => connect_settings_field(),
			'type'  => 'descriptive_text',
			'class' => 'cs-paypal-connect-row'
		),
		'paypal_sandbox_client_id'     => array(
			'id'    => 'paypal_sandbox_client_id',
			'name'  => __( 'Test Client ID', 'commercestore' ),
			'desc' => __( 'Enter your test client ID.', 'commercestore' ),
			'type' => 'text',
			'size' => 'regular',
			'class' => 'cs-hidden'
		),
		'paypal_sandbox_client_secret' => array(
			'id'   => 'paypal_sandbox_client_secret',
			'name' => __( 'Test Client Secret', 'commercestore' ),
			'desc' => __( 'Enter your test client secret.', 'commercestore' ),
			'type' => 'password',
			'size' => 'regular',
			'class' => 'cs-hidden'
		),
		'paypal_live_client_id'        => array(
			'id'   => 'paypal_live_client_id',
			'name' => __( 'Live Client ID', 'commercestore' ),
			'desc' => __( 'Enter your live client ID.', 'commercestore' ),
			'type' => 'text',
			'size' => 'regular',
			'class' => 'cs-hidden'
		),
		'paypal_live_client_secret'    => array(
			'id'   => 'paypal_live_client_secret',
			'name' => __( 'Live Client Secret', 'commercestore' ),
			'desc' => __( 'Enter your live client secret.', 'commercestore' ),
			'type' => 'password',
			'size' => 'regular',
			'class' => 'cs-hidden'
		),
	);

	/**
	 * Filters the PayPal Settings.
	 *
	 * @param array $paypal_settings
	 */
	$paypal_settings                     = apply_filters( 'cs_paypal_settings', $paypal_settings );
	$gateway_settings['paypal_commerce'] = $paypal_settings;

	return $gateway_settings;
}

add_filter( 'cs_settings_gateways', __NAMESPACE__ . '\register_gateway_settings', 1, 1 );

/**
 * Returns the content for the documentation settings.
 *
 * @since 2.11
 * @return string
 */
function documentation_settings_field() {
	ob_start();
	?>
	<p>
		<?php
		echo wp_kses( sprintf(
			__( 'To learn more about the PayPal gateway, visit <a href="%s" target="_blank">our documentation</a>.', 'commercestore' ),
			'https://docs.commercestore.com/article/2410-paypal'
		), array( 'a' => array( 'href' => true, 'target' => true ) ) )
		?>
	</p>
	<?php
	if ( ! is_ssl() ) {
		?>
		<div class="notice notice-warning inline">
			<p>
				<?php
				echo wp_kses( sprintf(
					__( 'PayPal requires an SSL certificate to accept payments. You can learn more about obtaining an SSL certificate in our <a href="%s" target="_blank">SSL setup article</a>.', 'commercestore' ),
					'https://docs.commercestore.com/article/994-how-to-set-up-ssl'
				), array( 'a' => array( 'href' => true, 'target' => true ) ) );
				?>
			</p>
		</div>
		<?php
	}

	return ob_get_clean();
}
