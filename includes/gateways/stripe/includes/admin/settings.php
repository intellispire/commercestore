<?php

/**
* Register our settings section
*
* @return array
*/
function csx_settings_section( $sections ) {
	$sections['cs-stripe'] = __( 'Stripe', 'commercestore' );

	return $sections;
}
add_filter( 'cs_settings_sections_gateways', 'csx_settings_section' );

/**
 * Register the gateway settings
 *
 * @access      public
 * @since       1.0
 * @return      array
 */

function csx_add_settings( $settings ) {

	// Build the Stripe Connect OAuth URL
	$stripe_connect_url = add_query_arg( array(
		'live_mode' => (int) ! cs_is_test_mode(),
		'state' => str_pad( wp_rand( wp_rand(), PHP_INT_MAX ), 100, wp_rand(), STR_PAD_BOTH ),
		'customer_site_url' => admin_url( 'edit.php?post_type=' . CS_POST_TYPE . '&page=cs-settings&tab=gateways&section=cs-stripe' ),
	), 'https://commercestore.com/?cs_gateway_connect_init=stripe_connect' );

	$test_mode = cs_is_test_mode();

	$test_key = cs_get_option( 'test_publishable_key' );
	$live_key = cs_get_option( 'live_publishable_key' );

	$live_text = _x( 'live', 'current value for test mode', 'commercestore' );
	$test_text = _x( 'test', 'current value for test mode', 'commercestore' );

	$mode = $live_text;
	if( $test_mode ) {
		$mode = $test_text;
	}

	$stripe_connect_account_id = cs_get_option( 'stripe_connect_account_id' );

	if( empty( $stripe_connect_account_id ) || ( ( empty( $test_key ) && $test_mode ) || ( empty( $live_key ) && ! $test_mode ) ) ) {
		$stripe_connect_desc = '<a href="'. esc_url( $stripe_connect_url ) .'" class="cs-stripe-connect"><span>' . __( 'Connect with Stripe', 'commercestore' ) . '</span></a>';
		$stripe_connect_desc .= '<p>' . sprintf( __( 'Have questions about connecting with Stripe? See the <a href="%s" target="_blank" rel="noopener noreferrer">documentation</a>.', 'commercestore' ), 'https://docs.commercestore.com/article/2039-how-does-stripe-connect-affect-me' ) . '</p>';
	} else {
		$stripe_connect_desc = sprintf( __( 'Your Stripe account is connected in %s mode. If you need to reconnect in %s mode, <a href="%s">click here</a>.', 'commercestore' ), '<strong>' . $mode . '</strong>', $mode, esc_url( $stripe_connect_url ) );
	}

	$stripe_connect_desc .= '<p id="csx-api-keys-row-reveal">' . __( '<a href="#">Click here</a> to manage your API keys manually.', 'commercestore' ) . '</p>';
	$stripe_connect_desc .= '<p id="csx-api-keys-row-hide" class="cs-hidden">' . __( '<a href="#">Click here</a> to hide your API keys.', 'commercestore' ) . '</p>';

	$stripe_settings = array(
		array(
			'id' => 'stripe_connect_button',
			'name' => __( 'Connection Status', 'commercestore' ),
			'desc' => $stripe_connect_desc,
			'type' => 'descriptive_text',
			'class' => 'cs-stripe-connect-row',
		),
		array(
			'id'   => 'test_publishable_key',
			'name'  => __( 'Test Publishable Key', 'commercestore' ),
			'desc'  => __( 'Enter your test publishable key, found in your Stripe Account Settings', 'commercestore' ),
			'type'  => 'text',
			'size'  => 'regular',
			'class' => 'cs-hidden csx-api-key-row',
		),
		array(
			'id'   => 'test_secret_key',
			'name'  => __( 'Test Secret Key', 'commercestore' ),
			'desc'  => __( 'Enter your test secret key, found in your Stripe Account Settings', 'commercestore' ),
			'type'  => 'text',
			'size'  => 'regular',
			'class' => 'cs-hidden csx-api-key-row',
		),
		array(
			'id'   => 'live_publishable_key',
			'name'  => __( 'Live Publishable Key', 'commercestore' ),
			'desc'  => __( 'Enter your live publishable key, found in your Stripe Account Settings', 'commercestore' ),
			'type'  => 'text',
			'size'  => 'regular',
			'class' => 'cs-hidden csx-api-key-row',
		),
		array(
			'id'   => 'live_secret_key',
			'name'  => __( 'Live Secret Key', 'commercestore' ),
			'desc'  => __( 'Enter your live secret key, found in your Stripe Account Settings', 'commercestore' ),
			'type'  => 'text',
			'size'  => 'regular',
			'class' => 'cs-hidden csx-api-key-row',
		),
		array(
			'id'    => 'stripe_webhook_description',
			'type'  => 'descriptive_text',
			'name'  => __( 'Webhooks', 'commercestore' ),
			'desc'  =>
				'<p>' . sprintf( __( 'In order for Stripe to function completely, you must configure your Stripe webhooks. Visit your <a href="%s" target="_blank">account dashboard</a> to configure them. Please add a webhook endpoint for the URL below.', 'commercestore' ), 'https://dashboard.stripe.com/account/webhooks' ) . '</p>' .
				'<p><strong>' . sprintf( __( 'Webhook URL: %s', 'commercestore' ), home_url( 'index.php?cs-listener=stripe' ) ) . '</strong></p>' .
				'<p>' . sprintf( __( 'See our <a href="%s">documentation</a> for more information.', 'commercestore' ), 'http://docs.commercestore.com/article/405-setup-documentation-for-stripe-payment-gateway' ) . '</p>'
		),
		array(
			'id'    => 'stripe_billing_fields',
			'name'  => __( 'Billing Address Display', 'commercestore' ),
			'desc'  => __( 'Select how you would like to display the billing address fields on the checkout form. <p><strong>Notes</strong>:</p><p>If taxes are enabled, this option cannot be changed from "Full address".</p><p>If set to "No address fields", you <strong>must</strong> disable "zip code verification" in your Stripe account.</p>', 'commercestore' ),
			'type'  => 'select',
			'options' => array(
				'full'        => __( 'Full address', 'commercestore' ),
				'zip_country' => __( 'Zip / Postal Code and Country only', 'commercestore' ),
				'none'        => __( 'No address fields', 'commercestore' )
			),
			'std'   => 'full'
		),
 		array(
 			'id'   => 'stripe_statement_descriptor',
 			'name' => __( 'Statement Descriptor', 'commercestore' ),
 			'desc' => __( 'Choose how charges will appear on customer\'s credit card statements. <em>Max 22 characters</em>', 'commercestore' ),
 			'type' => 'text',
 		),
 		array(
			'id'   => 'stripe_use_existing_cards',
			'name' => __( 'Show Previously Used Cards', 'commercestore' ),
			'desc' => __( 'Provides logged in customers with a list of previous used payment methods for faster checkout.', 'commercestore' ),
			'type' => 'checkbox'
		),
		array(
			'id'   => 'stripe_preapprove_only',
			'name'  => __( 'Preapproved Payments', 'commercestore' ),
			'desc'  => __( 'Authorize payments for processing and collection at a future date.', 'commercestore' ),
			'type'  => 'checkbox',
			'tooltip_title' => __( 'What does checking preapprove do?', 'commercestore' ),
			'tooltip_desc'  => __( 'If you choose this option, Stripe will not charge the customer right away after checkout, and the payment status will be set to preapproved in Easy Digital Downloads. You (as the admin) can then manually change the status to Complete by going to Payment History and changing the status of the payment to Complete. Once you change it to Complete, the customer will be charged. Note that most typical stores will not need this option.', 'commercestore' ),
		),
		array(
			'id' => 'stripe_restrict_assets',
			'name' => ( __( 'Restrict Stripe Assets', 'commercestore' ) ),
			'desc' => ( __( 'Only load Stripe.com hosted assets on pages that specifically utilize Stripe functionality.', 'commercestore' ) ),
			'type' => 'checkbox',
			'tooltip_title' => __( 'Loading Javascript from Stripe', 'commercestore' ),
			'tooltip_desc' => __( 'Stripe advises that their Javascript library be loaded on every page to take advantage of their advanced fraud detection rules. If you are not concerned with this, enable this setting to only load the Javascript when necessary. Read more about Stripe\'s recommended setup here: https://stripe.com/docs/web/setup.', 'commercestore' ),
		)
	);

	if ( cs_get_option( 'stripe_checkout' ) ) {
		$stripe_settings[] = array(
			'id'    => 'stripe_checkout',
			'name'  => '<strong>' . __( 'Stripe Checkout', 'commercestore' ) . '</strong>',
			'type'  => 'stripe_checkout_notice',
			'desc'  => wp_kses(
				sprintf(
					/* translators: %1$s Opening anchor tag, do not translate. %2$s Closing anchor tag, do not translate. */
					esc_html__( 'To ensure your website is compliant with the new %1$sStrong Customer Authentication%2$s (SCA) regulations, the legacy Stripe Checkout modal is no longer supported. Payments are still securely accepted through through Stripe on the standard Easy Digital Downloads checkout page. "Buy Now" buttons will also automatically redirect to the standard checkout page.', 'commercestore' ),
					'<a href="https://stripe.com/en-ca/guides/strong-customer-authentication" target="_blank" rel="noopener noreferrer">',
					'</a>'
				),
				array(
					'a' => array(
						'href'   => true,
						'rel'    => true,
						'target' => true,
					)
				)
			),
		);
	}

	if ( version_compare( CS_VERSION, 2.5, '>=' ) ) {
		$stripe_settings = array( 'cs-stripe' => $stripe_settings );

		// Set up the new setting field for the Test Mode toggle notice
		$notice = array(
			'stripe_connect_test_mode_toggle_notice' => array(
				'id' => 'stripe_connect_test_mode_toggle_notice',
				'desc' => '<p>' . __( 'You just toggled the test mode option. Save your changes using the Save Changes button below, then connect your Stripe account using the "Connect with Stripe" button when the page reloads.', 'commercestore' ) . '</p>',
				'type' => 'stripe_connect_notice',
				'field_class' => 'cs-hidden',
			)
		);

		// Insert the new setting after the Test Mode checkbox
		$position = array_search( 'test_mode', array_keys( $settings['main'] ), true );
		$settings = array_merge(
			array_slice( $settings['main'], $position, 1, true ),
			$notice,
			$settings
		);
	}

	return array_merge( $settings, $stripe_settings );
}
add_filter( 'cs_settings_gateways', 'csx_add_settings' );

/**
 * Force full billing address display when taxes are enabled
 *
 * @access      public
 * @since       2.5
 * @return      string
 */
function cs_stripe_sanitize_stripe_billing_fields_save( $value, $key ) {

	if( 'stripe_billing_fields' == $key && cs_use_taxes() ) {

		$value = 'full';

	}

	return $value;

}
add_filter( 'cs_settings_sanitize_select', 'cs_stripe_sanitize_stripe_billing_fields_save', 10, 2 );

/**
 * Filter the output of the statement descriptor option to add a max length to the text string
 *
 * @since 2.6
 * @param $html string The full html for the setting output
 * @param $args array  The original arguments passed in to output the html
 *
 * @return string
 */
function cs_stripe_max_length_statement_descriptor( $html, $args ) {
	if ( 'stripe_statement_descriptor' !== $args['id'] ) {
		return $html;
	}

	$html = str_replace( '<input type="text"', '<input type="text" maxlength="22"', $html );

	return $html;
}
add_filter( 'cs_after_setting_output', 'cs_stripe_max_length_statement_descriptor', 10, 2 );

/**
 * Callback for the stripe_connect_notice field type.
 *
 * @since 2.6.14
 *
 * @param array $args The setting field arguments
 */
function cs_stripe_connect_notice_callback( $args ) {

	$value = isset( $args['desc'] ) ? $args['desc'] : '';

	$class = cs_sanitize_html_class( $args['field_class'] );

	$html = '<div class="'.$class.'" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']">' . $value . '</div>';

	echo $html;
}

/**
 * Callback for the stripe_checkout_notice field type.
 *
 * @since 2.7.0
 *
 * @param array $args The setting field arguments
 */
function cs_stripe_checkout_notice_callback( $args ) {
	$value = isset( $args['desc'] ) ? $args['desc'] : '';

	$html = '<div class="notice notice-warning inline' . cs_sanitize_html_class( $args['field_class'] ) . '" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']">' . wpautop( $value ) . '</div>';

	echo $html;
}

/**
 * Listens for Stripe Connect completion requests and saves the Stripe API keys.
 *
 * @since 2.6.14
 */
function csx_process_gateway_connect_completion() {

	if( ! isset( $_GET['cs_gateway_connect_completion'] ) || 'stripe_connect' !== $_GET['cs_gateway_connect_completion'] || ! isset( $_GET['state'] ) ) {
		return;
	}

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	if( headers_sent() ) {
		return;
	}

	$cs_credentials_url = add_query_arg( array(
		'live_mode' => (int) ! cs_is_test_mode(),
		'state' => sanitize_text_field( $_GET['state'] ),
		'customer_site_url' => admin_url( 'edit.php?post_type=' . CS_POST_TYPE ),
	), 'https://commercestore.com/?cs_gateway_connect_credentials=stripe_connect' );

	$response = wp_remote_get( esc_url_raw( $cs_credentials_url ) );

	if( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		$message = '<p>' . sprintf( __( 'There was an error getting your Stripe credentials. Please <a href="%s">try again</a>. If you continue to have this problem, please contact support.', 'commercestore' ), esc_url( admin_url( 'edit.php?post_type=' . CS_POST_TYPE . '&page=cs-settings&tab=gateways&section=cs-stripe' ) ) ) . '</p>';
		wp_die( $message );
	}

	$data = json_decode( $response['body'], true );
	$data = $data['data'];

	if( cs_is_test_mode() ) {
		cs_update_option( 'test_publishable_key', sanitize_text_field( $data['publishable_key'] ) );
		cs_update_option( 'test_secret_key', sanitize_text_field( $data['secret_key'] ) );
	} else {
		cs_update_option( 'live_publishable_key', sanitize_text_field( $data['publishable_key'] ) );
		cs_update_option( 'live_secret_key', sanitize_text_field( $data['secret_key'] ) );
	}

	cs_update_option( 'stripe_connect_account_id', sanitize_text_field( $data['stripe_user_id'] ) );
	wp_redirect( esc_url_raw( admin_url( 'edit.php?post_type=' . CS_POST_TYPE . '&page=cs-settings&tab=gateways&section=cs-stripe' ) ) );
	exit;

}
add_action( 'admin_init', 'csx_process_gateway_connect_completion' );
