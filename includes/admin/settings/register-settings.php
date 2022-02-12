<?php
/**
 * Register Settings
 *
 * @package     CS
 * @subpackage  Admin/Settings
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get an option
 *
 * Looks to see if the specified setting exists, returns default if not
 *
 * @since 1.8.4
 * @global $cs_options Array of all the CommerceStore Options
 * @return mixed
 */
function cs_get_option( $key = '', $default = false ) {
	global $cs_options;

	$value = $default;

	if ( isset( $cs_options[ $key ] ) ) {
		if ( is_numeric( $cs_options[ $key ] ) ) {
			$value = $cs_options[ $key ];
		} else {
			$value = ! empty( $cs_options[ $key ] ) ? $cs_options[ $key ] : $default;
		}
	}

	$value = apply_filters( 'cs_get_option', $value, $key, $default );

	return apply_filters( 'cs_get_option_' . $key, $value, $key, $default );
}

/**
 * Update an option
 *
 * Updates an cs setting value in both the db and the global variable.
 * Warning: Passing in an empty, false or null string value will remove
 *          the key from the cs_options array.
 *
 * @since 2.3
 *
 * @param string          $key         The Key to update
 * @param string|bool|int $value       The value to set the key to
 *
 * @global                $cs_options Array of all the CommerceStore Options
 * @return boolean True if updated, false if not.
 */
function cs_update_option( $key = '', $value = false ) {
	global $cs_options;

	// If no key, exit
	if ( empty( $key ) ) {
		return false;
	}

	if ( empty( $value ) ) {
		$remove_option = cs_delete_option( $key );

		return $remove_option;
	}

	// First let's grab the current settings
	$options = get_option( 'cs_settings' );

	// Let's let devs alter that value coming in
	$value = apply_filters( 'cs_update_option', $value, $key );

	// Next let's try to update the value
	$options[ $key ] = $value;
	$did_update      = update_option( 'cs_settings', $options );

	// If it updated, let's update the global variable
	if ( $did_update ) {
		$cs_options[ $key ] = $value;
	}

	return $did_update;
}

/**
 * Remove an option
 *
 * Removes an cs setting value in both the db and the global variable.
 *
 * @since 2.3
 *
 * @param string $key         The Key to delete
 *
 * @global       $cs_options Array of all the CommerceStore Options
 * @return boolean True if removed, false if not.
 */
function cs_delete_option( $key = '' ) {
	global $cs_options;

	// If no key, exit
	if ( empty( $key ) ) {
		return false;
	}

	// First let's grab the current settings
	$options = get_option( 'cs_settings' );

	// Next let's try to update the value
	if ( isset( $options[ $key ] ) ) {

		unset( $options[ $key ] );

	}

	// Remove this option from the global CommerceStore settings to the array_merge in cs_settings_sanitize() doesn't re-add it.
	if ( isset( $cs_options[ $key ] ) ) {
		unset( $cs_options[ $key ] );
	}

	$did_update = update_option( 'cs_settings', $options );

	// If it updated, let's update the global variable
	if ( $did_update ) {
		$cs_options = $options;
	}

	return $did_update;
}

/**
 * Get Settings
 *
 * Retrieves all plugin settings
 *
 * @since 1.0
 * @return array CommerceStore settings
 */
function cs_get_settings() {

	// Get the option key
	$settings = get_option( 'cs_settings' );

	// Look for old option keys
	if ( empty( $settings ) ) {

		// Old option keys
		$old_keys = array(
			'cs_settings_general',
			'cs_settings_gateways',
			'cs_settings_emails',
			'cs_settings_styles',
			'cs_settings_taxes',
			'cs_settings_extensions',
			'cs_settings_licenses',
			'cs_settings_misc'
		);

		// Merge old keys together
		foreach ( $old_keys as $key ) {
			$settings[ $key ] = get_option( $key, array() );
		}

		// Remove empties
		$settings = array_filter( array_values( $settings ) );

		// Update the main option
		update_option( 'cs_settings', $settings );
	}

	// Filter & return
	return apply_filters( 'cs_get_settings', $settings );
}

/**
 * Add all settings sections and fields
 *
 * @since 1.0
 * @return void
 */
function cs_register_settings() {

	// Get registered settings
	$cs_settings = cs_get_registered_settings();

	// Loop through settings
	foreach ( $cs_settings as $tab => $sections ) {

		// Loop through sections
		foreach ( $sections as $section => $settings ) {

			// Check for backwards compatibility
			$section_tabs = cs_get_settings_tab_sections( $tab );
			if ( ! is_array( $section_tabs ) || ! array_key_exists( $section, $section_tabs ) ) {
				$section  = 'main';
				$settings = $sections;
			}

			// Current page
			$page = "cs_settings_{$tab}_{$section}";

			// Add the settings section
			add_settings_section(
				$page,
				__return_null(),
				'__return_false',
				$page
			);

			foreach ( $settings as $option ) {

				// For backwards compatibility
				if ( empty( $option['id'] ) ) {
					continue;
				}

				// Parse args
				$args = wp_parse_args( $option, array(
					'section'       => $section,
					'id'            => null,
					'desc'          => '',
					'name'          => '',
					'size'          => null,
					'options'       => '',
					'std'           => '',
					'min'           => null,
					'max'           => null,
					'step'          => null,
					'chosen'        => null,
					'multiple'      => null,
					'placeholder'   => null,
					'allow_blank'   => true,
					'readonly'      => false,
					'faux'          => false,
					'tooltip_title' => false,
					'tooltip_desc'  => false,
					'field_class'   => '',
					'label_for'     => false
				) );

				// Callback fallback
				$func     = 'cs_' . $args['type'] . '_callback';
				$callback = ! function_exists( $func )
					? 'cs_missing_callback'
					: $func;

				// Link the label to the form field
				if ( empty( $args['label_for'] ) ) {
					$args['label_for'] = 'cs_settings[' . $args['id'] . ']';
				}

				// Add the settings field
				add_settings_field(
					'cs_settings[' . $args['id'] . ']',
					$args['name'],
					$callback,
					$page,
					$page,
					$args
				);
			}
		}
	}

	// Register our setting in the options table
	register_setting( 'cs_settings', 'cs_settings', 'cs_settings_sanitize' );
}
add_action( 'admin_init', 'cs_register_settings' );

/**
 * Retrieve the array of plugin settings
 *
 * @since 1.8
 * @since 3.0 Use a static variable internally to store registered settings
 * @return array
 */
function cs_get_registered_settings() {
	static $cs_settings = null;

	/**
	 * 'Whitelisted' CommerceStore settings, filters are provided for each settings
	 * section to allow extensions and other plugins to add their own settings
	 */

	// Only build settings if not already build
	if ( null === $cs_settings ) {
		$options = array(
			'none'      => __( 'Do Nothing', 'commercestore' ),
			'anonymize' => __( 'Anonymize',  'commercestore' ),
			'delete'    => __( 'Delete',     'commercestore' )
		);
		$debug_log_url    = cs_get_admin_url( array( 'page' => 'cs-tools', 'tab' => 'debug_log' ) );
		$debug_log_link   = '<a href="' . esc_url( $debug_log_url ) . '">' . __( 'View the Log', 'commercestore' ) . '</a>';
		$payment_statuses = cs_get_payment_statuses();
		$pages            = cs_get_pages();
		$gateways         = cs_get_payment_gateways();
		$admin_email      = get_bloginfo( 'admin_email' );
		$site_name        = get_bloginfo( 'name' );
		$site_hash        = substr( md5( $site_name ), 0, 10 );
		$cs_settings     = array(

			// General Settings
			'general' => apply_filters( 'cs_settings_general', array(
				'main' => array(
					'business_settings' => array(
						'id'            => 'business_settings',
						'name'          => '<h3>' . __( 'Business Info', 'commercestore' ) . '</h3>',
						'desc'          => '',
						'type'          => 'header',
						'tooltip_title' => __( 'Business Information', 'commercestore' ),
						'tooltip_desc'  => __( 'CommerceStore uses the following business information for things like pre-populating tax fields, and connecting third-party services with the same information.', 'commercestore' ),
					),
					'entity_name' => array(
						'id'          => 'entity_name',
						'name'        => __( 'Business Name', 'commercestore' ),
						'desc'        => __( 'The official (legal) name of your store. Defaults to Site Title if empty.', 'commercestore' ),
						'type'        => 'text',
						'std'         => $site_name,
						'placeholder' => $site_name
					),
					'entity_type' => array(
						'id'          => 'entity_type',
						'name'        => __( 'Business Type', 'commercestore' ),
						'desc'        => __( 'Choose "Individual" if you do not have an official/legal business ID, or "Company" if a regisitered business entity exists.', 'commercestore' ),
						'type'        => 'select',
						'options'     => array(
							'individual' => esc_html__( 'Individual', 'commercestore' ),
							'company'    => esc_html__( 'Company',    'commercestore' )
						)
					),
					'business_address' => array(
						'id'          => 'business_address',
						'name'        => __( 'Business Address', 'commercestore' ),
						//'desc'        => __( 'Your company or home address, based on business type above.', 'commercestore' ),
						'type'        => 'text',
						'placeholder' => ''
					),
					'business_address_2' => array(
						'id'          => 'business_address_2',
						'name'        => __( 'Business Address (Extra)', 'commercestore' ),
						//'desc'        => __( 'Anything requiring an extra line (suite, attention, etc...)', 'commercestore' ),
						'type'        => 'text',
						'placeholder' => ''
					),
					'business_city' => array(
						'id'          => 'business_city',
						'name'        => __( 'Business City', 'commercestore' ),
						//'desc'        => __( 'The physical city your company or home is in.', 'commercestore' ),
						'type'        => 'text',
						'placeholder' => ''
					),
					'business_postal_code' => array(
						'id'          => 'business_postal_code',
						'name'        => __( 'Business Postal Code', 'commercestore' ),
						//'desc'        => __( 'The zip/postal code for your company or home address.', 'commercestore' ),
						'type'        => 'text',
						'size'        => 'medium',
						'placeholder' => ''
					),
					'base_country' => array(
						'id'          => 'base_country',
						'name'        => __( 'Business Country', 'commercestore' ),
						//'desc'        => __( 'The country your company or home is in.', 'commercestore' ),
						'type'        => 'select',
						'options'     => cs_get_country_list(),
						'chosen'      => true,
						'field_class' => 'cs_countries_filter',
						'placeholder' => __( 'Select a country', 'commercestore' ),
						'data'        => array(
							'nonce' => wp_create_nonce( 'cs-country-field-nonce' )
						)
					),
					'base_state' => array(
						'id'          => 'base_state',
						'name'        => __( 'Business Region', 'commercestore' ),
						//'desc'        => __( 'The state/province/territory your company or home is in.', 'commercestore' ),
						'type'        => 'shop_states',
						'chosen'      => true,
						'field_class' => 'cs_regions_filter',
						'placeholder' => __( 'Select a region', 'commercestore' ),
					),
				),
				'pages' => array(
					'page_settings' => array(
						'id'            => 'page_settings',
						'name'          => '<h3>' . __( 'Pages', 'commercestore' ) . '</h3>',
						'desc'          => '',
						'type'          => 'header',
						'tooltip_title' => __( 'Page Settings', 'commercestore' ),
						'tooltip_desc'  => __( 'CommerceStore uses the pages below for handling the display of checkout, purchase confirmation, purchase history, and purchase failures. If pages are deleted or removed in some way, they can be recreated manually from the Pages menu. When re-creating the pages, enter the shortcode shown in the page content area.', 'commercestore' ),
					),
					'purchase_page' => array(
						'id'          => 'purchase_page',
						'name'        => __( 'Primary Checkout Page', 'commercestore' ),
						'desc'        => __( 'This is the checkout page where buyers will complete their purchases.<br>The <code>[download_checkout]</code> shortcode must be on this page.', 'commercestore' ),
						'type'        => 'select',
						'options'     => $pages,
						'chosen'      => true,
						'placeholder' => __( 'Select a page', 'commercestore' ),
					),
					'success_page' => array(
						'id'          => 'success_page',
						'name'        => __( 'Success Page', 'commercestore' ),
						'desc'        => __( 'This is the page buyers are sent to after completing their purchases.<br>The <code>[cs_receipt]</code> shortcode should be on this page.', 'commercestore' ),
						'type'        => 'select',
						'options'     => $pages,
						'chosen'      => true,
						'placeholder' => __( 'Select a page', 'commercestore' ),
					),
					'failure_page' => array(
						'id'          => 'failure_page',
						'name'        => __( 'Failed Transaction Page', 'commercestore' ),
						'desc'        => __( 'This is the page buyers are sent to if their transaction is cancelled or fails.', 'commercestore' ),
						'type'        => 'select',
						'options'     => $pages,
						'chosen'      => true,
						'placeholder' => __( 'Select a page', 'commercestore' ),
					),
					'purchase_history_page' => array(
						'id'          => 'purchase_history_page',
						'name'        => __( 'Purchase History Page', 'commercestore' ),
						'desc'        => __( 'This page shows a complete purchase history for the current user, including download links.<br>The <code>[purchase_history]</code> shortcode should be on this page.', 'commercestore' ),
						'type'        => 'select',
						'options'     => $pages,
						'chosen'      => true,
						'placeholder' => __( 'Select a page', 'commercestore' ),
					),
					'login_redirect_page' => array(
						'id'          => 'login_redirect_page',
						'name'        => __( 'Login Redirect Page', 'commercestore' ),
						'desc'        => sprintf(
							__( 'If a customer logs in using the <code>[cs_login]</code> shortcode, this is the page they will be redirected to.<br>Note: override using the redirect shortcode attribute: <code>[cs_login redirect="%s"]</code>.', 'commercestore' ),
							trailingslashit( home_url() )
						),
						'type'        => 'select',
						'options'     => $pages,
						'chosen'      => true,
						'placeholder' => __( 'Select a page', 'commercestore' ),
					)
				),
				'currency' => array(
					'currency_settings' => array(
						'id'            => 'currency_settings',
						'name'          => '<h3>' . __( 'Currency', 'commercestore' ) . '</h3>',
						'desc'          => '',
						'type'          => 'header',
						'tooltip_title' => __( 'Currency Settings', 'commercestore' ),
						'tooltip_desc'  => __( 'Different countries use different formatting for their currency. You will want to pick what most of your users will expect to use.', 'commercestore' ),
					),
					'currency' => array(
						'id'      => 'currency',
						'name'    => __( 'Currency', 'commercestore' ),
						'desc'    => __( 'Choose your currency. Note that some payment gateways have currency restrictions.', 'commercestore' ),
						'type'    => 'select',
						'chosen'  => true,
						'options' => cs_get_currencies(),
					),
					'currency_position' => array(
						'id'      => 'currency_position',
						'name'    => __( 'Currency Position', 'commercestore' ),
						'desc'    => __( 'Choose the location of the currency sign.', 'commercestore' ),
						'type'    => 'select',
						'options' => array(
							'before' => __( 'Before ($10)', 'commercestore' ),
							'after'  => __( 'After (10$)',  'commercestore' )
						),
					),
					'thousands_separator' => array(
						'id'          => 'thousands_separator',
						'name'        => __( 'Thousandths Separator', 'commercestore' ),
						'desc'        => __( 'The symbol to separate thousandths. Usually <code>,</code> or <code>.</code>.', 'commercestore' ),
						'type'        => 'text',
						'size'        => 'small',
						'field_class' => 'code',
						'std'         => ',',
						'placeholder' => ','
					),
					'decimal_separator' => array(
						'id'          => 'decimal_separator',
						'name'        => __( 'Decimal Separator', 'commercestore' ),
						'desc'        => __( 'The symbol to separate decimal points. Usually <code>,</code> or <code>.</code>.', 'commercestore' ),
						'type'        => 'text',
						'size'        => 'small',
						'field_class' => 'code',
						'std'         => '.',
						'placeholder' => '.'
					),
				),
				'moderation' => array(
					'moderation_settings' => array(
						'id'   => 'moderation_settings',
						'name' => '<h3>' . __( 'Moderation', 'commercestore' ) . '</h3>',
						'desc' => '',
						'type' => 'header',
						'tooltip_title' => __( 'Moderation', 'commercestore' ),
						'tooltip_desc'  => __( 'It is sometimes necessary to temporarily prevent certain potential customers from checking out. Use these settings to control who can make purchases.', 'commercestore' ),
					),
					'banned_emails' => array(
						'id'    => 'banned_emails',
						'name'  => __( 'Banned Emails', 'commercestore' ),
						'desc'  => __( 'Emails placed in the box above will not be allowed to make purchases.', 'commercestore' ) . '<br>' . __( 'One per line, enter: email addresses, domains (<code>@example.com</code>), or TLDs (<code>.gov</code>).', 'commercestore' ),
						'type'  => 'textarea',
						'placeholder' => __( '@example.com', 'commercestore' )
					)
				),
				'refunds' => array(
					'refunds_settings' => array(
						'id'   => 'refunds_settings',
						'name' => '<h3>' . __( 'Refunds', 'commercestore' ) . '</h3>',
						'desc' => '',
						'type' => 'header',
						'tooltip_title' => __( 'Refunds', 'commercestore' ),
						'tooltip_desc'  => __( 'As a shop owner, sometimes refunds are necessary. Use these settings to decide how refunds will work in your shop.', 'commercestore' ),
					),
					'refundability' => array(
						'id'      => 'refundability',
						'name'    => __( 'Default Status', 'commercestore' ),
						'desc'    => __( 'Products without an explicit setting will default to this.', 'commercestore' ),
						'type'    => 'select',
						'std'     => 'refundable',
						'options' => cs_get_refundability_types(),
					),
					'refund_window' => array(
						'id'   => 'refund_window',
						'name' => __( 'Refund Window', 'commercestore' ),
						'desc' => __( 'Number of days (after a sale) when refunds can be processed.<br>Default is <code>30</code> days. Set to <code>0</code> for infinity. Overridden on a per-product basis.', 'commercestore' ),
						'std'  => 30,
						'type' => 'number',
						'size' => 'small',
						'max'  => 3650, // Ten year maximum, because why explicitly support longer
						'min'  => 0,
						'step' => 1,
					),
				),
				'api' => array(
					'api_settings' => array(
						'id'            => 'api_settings',
						'name'          => '<h3>' . __( 'API', 'commercestore' ) . '</h3>',
						'desc'          => '',
						'type'          => 'header',
						'tooltip_title' => __( 'API Settings', 'commercestore' ),
						'tooltip_desc'  => __( 'The CommerceStore REST API provides access to store data through our API endpoints. Enable this setting if you would like all user accounts to be able to generate their own API keys.', 'commercestore' ),
					),
					'api_allow_user_keys' => array(
						'id'    => 'api_allow_user_keys',
						'name'  => __( 'Allow User Keys', 'commercestore' ),
						'check' => __( 'Check this box to allow all users to generate API keys.', 'commercestore' ),
						'desc'  => __( 'Users who can <code>manage_shop_settings</code> are always allowed to generate keys.', 'commercestore' ),
						'type'  => 'checkbox_description',
					),
					'api_help' => array(
						'id'   => 'api_help',
						'desc' => sprintf( __( 'Visit the <a href="%s" target="_blank">REST API documentation</a> for further information.', 'commercestore' ), 'http://docs.commercestore.com/article/1131-cs-rest-api-introduction' ),
						'type' => 'descriptive_text',
					),
				),
				'tracking' => array(
					'tracking_settings' => array(
						'id'   => 'tracking_settings',
						'name' => '<h3>' . __( 'Tracking', 'commercestore' ) . '</h3>',
						'desc' => '',
						'type' => 'header',
					),
					'allow_tracking' => array(
						'id'    => 'allow_tracking',
						'name'  => __( 'Usage Tracking', 'commercestore' ),
						'check' => __( 'Allow',          'commercestore' ),
						'desc'  => sprintf(
							/* translators: %1$s Link to tracking information, do not translate. %2$s Link to CommerceStore newsleter, do not translate. %3$s Link to CommerceStore extensions, do not translate */
							__( 'Help us make CommerceStore better by opting into anonymous usage tracking. <a href="%1$s" target="_blank">Here is what we track</a>.<br>If you opt-in here and to <a href="%2$s" target="_blank">our newsletter</a>, we will email you a discount code for our <a href="%3$s" target="_blank">extension shop</a>.', 'commercestore' ),
							esc_url( 'https://docs.commercestore.com/article/1419-what-information-will-be-tracked-by-opting-into-usage-tracking' ),
							esc_url( 'https://commercestore.com/subscribe/?utm_source=' . $site_hash . '&utm_medium=admin&utm_term=settings&utm_campaign=CSUsageTracking' ),
							esc_url( 'https://commercestore.com/downloads/?utm_source=' . $site_hash . '&utm_medium=admin&utm_term=settings&utm_campaign=CSUsageTracking' )
						),
						'type' => 'checkbox_description',
					)
				),
			) ),

			// Payment Gateways Settings
			'gateways' => apply_filters( 'cs_settings_gateways', array(
				'main' => array(
					'test_mode' => array(
						'id'    => 'test_mode',
						'name'  => __( 'Test Mode', 'commercestore' ),
						'check' => __( 'Enabled',   'commercestore' ),
						'desc'  => __( 'While test mode is enabled, no live transactions are processed.<br>Use test mode in conjunction with the sandbox/test account for the payment gateways to test.', 'commercestore' ),
						'type'  => 'checkbox_description'
					),
					'gateways' => array(
						'id'      => 'gateways',
						'name'    => __( 'Active Gateways', 'commercestore' ),
						'desc'    => __( 'Choose the payment gateways you want to enable.', 'commercestore' ),
						'type'    => 'gateways',
						'options' => $gateways,
					),
					'default_gateway' => array(
						'id'      => 'default_gateway',
						'name'    => __( 'Default Gateway', 'commercestore' ),
						'desc'    => __( 'Automatically select this gateway on checkout pages.<br>If empty, the first active gateway is selected instead.', 'commercestore' ),
						'type'    => 'gateway_select',
						'options' => $gateways,
					),
					'accepted_cards' => array(
						'id'      => 'accepted_cards',
						'name'    => __( 'Payment Method Icons', 'commercestore' ),
						'desc'    => __( 'Display icons for the selected payment methods.', 'commercestore' ) . '<br/>' . __( 'You will also need to configure your gateway settings if you are accepting credit cards.', 'commercestore' ),
						'type'    => 'payment_icons',
						'options' => apply_filters( 'cs_accepted_payment_icons', array(
							'mastercard'      => 'Mastercard',
							'visa'            => 'Visa',
							'americanexpress' => 'American Express',
							'discover'        => 'Discover',
							'paypal'          => 'PayPal'
						) ),
					),
				),
				'checkout' => array(
					'enforce_ssl' => array(
						'id'    => 'enforce_ssl',
						'name'  => __( 'Enforce SSL on Checkout', 'commercestore' ),
						'check' => __( 'Enforced',                'commercestore' ),
						'desc'  => __( 'Redirect all customers to the secure checkout page. You must have an SSL certificate installed to use this option.', 'commercestore' ),
						'type'  => 'checkbox_description',
					),
					'redirect_on_add'    => array(
						'id'            => 'redirect_on_add',
						'name'          => __( 'Redirect to Checkout', 'commercestore' ),
						'desc'          => __( 'Immediately redirect to checkout after adding an item to the cart?', 'commercestore' ),
						'type'          => 'checkbox',
						'tooltip_title' => __( 'Redirect to Checkout', 'commercestore' ),
						'tooltip_desc'  => __( 'When enabled, once an item has been added to the cart, the customer will be redirected directly to your checkout page. This is useful for stores that sell single items.', 'commercestore' ),
					),
					'logged_in_only' => array(
						'id'            => 'logged_in_only',
						'name'          => __( 'Require Login', 'commercestore' ),
						'desc'          => __( 'Require that users be logged-in to purchase files.', 'commercestore' ),
						'type'          => 'checkbox',
						'tooltip_title' => __( 'Require Login', 'commercestore' ),
						'tooltip_desc'  => __( 'You can require that customers create and login to user accounts prior to purchasing from your store by enabling this option. When unchecked, users can purchase without being logged in by using their name and email address.', 'commercestore' ),
					),
					'show_register_form' => array(
						'id'      => 'show_register_form',
						'name'    => __( 'Show Register / Login Form', 'commercestore' ),
						'desc'    => __( 'Display the registration and login forms on the checkout page for non-logged-in users.', 'commercestore' ),
						'type'    => 'select',
						'std'     => 'none',
						'options' => array(
							'both'         => __( 'Registration and Login Forms', 'commercestore' ),
							'registration' => __( 'Registration Form Only', 'commercestore' ),
							'login'        => __( 'Login Form Only', 'commercestore' ),
							'none'         => __( 'None', 'commercestore' ),
						),
					),
					'enable_cart_saving' => array(
						'id'            => 'enable_cart_saving',
						'name'          => __( 'Enable Cart Saving', 'commercestore' ),
						'desc'          => __( 'Check this to enable cart saving on the checkout.', 'commercestore' ),
						'type'          => 'checkbox',
						'tooltip_title' => __( 'Cart Saving', 'commercestore' ),
						'tooltip_desc'  => __( 'Cart saving allows shoppers to create a temporary link to their current shopping cart so they can come back to it later, or share it with someone.', 'commercestore' ),
					),
				),
				'accounting' => array(
					'enable_skus' => array(
						'id'    => 'enable_skus',
						'name'  => __( 'Enable SKU Entry', 'commercestore' ),
						'check' => __( 'Check this box to allow entry of product SKUs.', 'commercestore' ),
						'desc'  => __( 'SKUs will be shown on purchase receipt and exported purchase histories.', 'commercestore' ),
						'type'  => 'checkbox_description',
					),
					'enable_sequential' => array(
						'id'    => 'enable_sequential',
						'name'  => __( 'Sequential Order Numbers', 'commercestore' ),
						'check' => __( 'Check this box to enable sequential order numbers.', 'commercestore' ),
						'desc'  => __( 'Does not impact previous orders. Future orders will be sequential.', 'commercestore' ),
						'type'  => 'checkbox_description',
					),
					'sequential_start' => array(
						'id'   => 'sequential_start',
						'name' => __( 'Sequential Starting Number', 'commercestore' ),
						'desc' => __( 'The number at which the sequence should begin.', 'commercestore' ),
						'type' => 'number',
						'size' => 'small',
						'std'  => '1',
					),
					'sequential_prefix' => array(
						'id'   => 'sequential_prefix',
						'name' => __( 'Sequential Number Prefix', 'commercestore' ),
						'desc' => __( 'A prefix to prepend to all sequential order numbers.', 'commercestore' ),
						'type' => 'text',
					),
					'sequential_postfix' => array(
						'id'   => 'sequential_postfix',
						'name' => __( 'Sequential Number Postfix', 'commercestore' ),
						'desc' => __( 'A postfix to append to all sequential order numbers.', 'commercestore' ),
						'type' => 'text',
					)
				),
			) ),

			// Emails Settings
			'emails' => apply_filters( 'cs_settings_emails', array(
				'main' => array(
					'email_header' => array(
						'id'   => 'email_header',
						'name' => '<strong>' . __( 'Email Configuration', 'commercestore' ) . '</strong>',
						'type' => 'header',
					),
					'email_template' => array(
						'id'      => 'email_template',
						'name'    => __( 'Template', 'commercestore' ),
						'desc'    => __( 'Choose a template. Click "Save Changes" then "Preview Purchase Receipt" to see the new template.', 'commercestore' ),
						'type'    => 'select',
						'options' => cs_get_email_templates(),
					),
					'email_logo' => array(
						'id'      => 'email_logo',
						'name'    => __( 'Logo', 'commercestore' ),
						'desc'    => __( 'Upload or choose a logo to be displayed at the top of sales receipt emails. Displayed on HTML emails only.', 'commercestore' ),
						'type'    => 'upload',
					),
					'from_name' => array(
						'id'          => 'from_name',
						'name'        => __( 'From Name', 'commercestore' ),
						'desc'        => __( 'This should be your site or shop name. Defaults to Site Title if empty.', 'commercestore' ),
						'type'        => 'text',
						'std'         => $site_name,
						'placeholder' => $site_name
					),
					'from_email' => array(
						'id'          => 'from_email',
						'name'        => __( 'From Email', 'commercestore' ),
						'desc'        => __( 'This will act as the "from" and "reply-to" addresses.', 'commercestore' ),
						'type'        => 'email',
						'std'         => $admin_email,
						'placeholder' => $admin_email
					),
					'email_settings' => array(
						'id'      => 'email_settings',
						'name'    => '',
						'desc'    => '',
						'type'    => 'hook',
					),
				),
				'purchase_receipts' => array(
					'purchase_receipt_email_settings' => array(
						'id'   => 'purchase_receipt_email_settings',
						'name' => '',
						'desc' => '',
						'type' => 'hook',
					),
					'purchase_subject' => array(
						'id'   => 'purchase_subject',
						'name' => __( 'Purchase Email Subject', 'commercestore' ),
						'desc' => __( 'Enter the subject line for the purchase receipt email.', 'commercestore' ),
						'type' => 'text',
						'std'  => __( 'Purchase Receipt', 'commercestore' ),
					),
					'purchase_heading' => array(
						'id'   => 'purchase_heading',
						'name' => __( 'Purchase Email Heading', 'commercestore' ),
						'desc' => __( 'Enter the heading for the purchase receipt email.', 'commercestore' ),
						'type' => 'text',
						'std'  => __( 'Purchase Receipt', 'commercestore' ),
					),
					'purchase_receipt' => array(
						'id'   => 'purchase_receipt',
						'name' => __( 'Purchase Receipt', 'commercestore' ),
						'desc' => __( 'Text to email customers after completing a purchase. Personalize with HTML and <code>{tag}</code> markers.', 'commercestore' ) . '<br/><br/>' . cs_get_emails_tags_list(),
						'type' => 'rich_editor',
						'std'  => __( "Dear", "commercestore" ) . " {name},\n\n" . __( "Thank you for your purchase. Please click on the link(s) below to download your files.", "commercestore" ) . "\n\n{download_list}\n\n{sitename}",
					),
				),
				'sale_notifications' => array(
					'sale_notification_subject' => array(
						'id'   => 'sale_notification_subject',
						'name' => __( 'Sale Notification Subject', 'commercestore' ),
						'desc' => __( 'Enter the subject line for the sale notification email.', 'commercestore' ),
						'type' => 'text',
						'std'  => 'New download purchase - Order #{payment_id}',
					),
					'sale_notification_heading' => array(
						'id'   => 'sale_notification_heading',
						'name' => __( 'Sale Notification Heading', 'commercestore' ),
						'desc' => __( 'Enter the heading for the sale notification email.', 'commercestore' ),
						'type' => 'text',
						'std'  => __( 'New Sale!', 'commercestore' ),
					),
					'sale_notification' => array(
						'id'   => 'sale_notification',
						'name' => __( 'Sale Notification', 'commercestore' ),
						'desc' => __( 'Text to email as a notification for every completed purchase. Personalize with HTML and <code>{tag}</code> markers.', 'commercestore' ) . '<br/><br/>' . cs_get_emails_tags_list(),
						'type' => 'rich_editor',
						'std'  => cs_get_default_sale_notification_email(),
					),
					'admin_notice_emails' => array(
						'id'   => 'admin_notice_emails',
						'name' => __( 'Sale Notification Emails', 'commercestore' ),
						'desc' => __( 'Enter the email address(es) that should receive a notification anytime a sale is made. One per line.', 'commercestore' ),
						'type' => 'textarea',
						'std'  => $admin_email,
					),
					'disable_admin_notices' => array(
						'id'   => 'disable_admin_notices',
						'name' => __( 'Disable Admin Notifications', 'commercestore' ),
						'desc' => __( 'Check this box if you do not want to receive sales notification emails.', 'commercestore' ),
						'type' => 'checkbox',
					),
				),
			) ),

			// Marketing Settings
			'marketing'  => apply_filters(
				'cs_settings_marketing',
				array(
					'main' => array(
						'recapture'                => array(
							'id'   => 'recapture',
							'name' => __( 'Abandoned Cart Recovery', 'commercestore' ),
							'desc' => '',
							'type' => 'recapture',
						),
						'allow_multiple_discounts' => array(
							'id'   => 'allow_multiple_discounts',
							'name' => __( 'Multiple Discounts', 'commercestore' ),
							'desc' => __( 'Allow customers to use multiple discounts on the same purchase?', 'commercestore' ),
							'type' => 'checkbox',
						),
					),
				)
			),

			// Taxes Settings
			'taxes' => apply_filters( 'cs_settings_taxes', array(
				'main' => array(
					'enable_taxes' => array(
						'id'            => 'enable_taxes',
						'name'          => __( 'Taxes', 'commercestore' ),
						'check'         => __( 'Enabled', 'commercestore' ),
						'desc'          => __( 'Check this to enable taxes on purchases.', 'commercestore' ),
						'type'          => 'checkbox_description',
						'tooltip_title' => __( 'Enabling Taxes', 'commercestore' ),
						'tooltip_desc'  => __( 'With taxes enabled, customers will be taxed based on the rates you define, and are required to input their address on checkout so rates can be calculated accordingly.', 'commercestore' ),
					),
					'tax_help' => array(
						'id'   => 'tax_help',
						'name' => '',
						'desc' => sprintf( __( 'Visit the <a href="%s" target="_blank">Tax setup documentation</a> for further information. <p class="description">If you need VAT support, there are options listed on the documentation page.</p>', 'commercestore' ), 'http://docs.commercestore.com/article/238-tax-settings' ),
						'type' => 'descriptive_text',
					),
					'prices_include_tax' => array(
						'id'            => 'prices_include_tax',
						'name'          => __( 'Prices Include Tax', 'commercestore' ),
						'desc'          => __( 'This option affects how you enter prices.', 'commercestore' ),
						'type'          => 'radio',
						'std'           => 'no',
						'options'       => array(
							'yes' => __( 'Yes, I will enter prices inclusive of tax', 'commercestore' ),
							'no'  => __( 'No, I will enter prices exclusive of tax', 'commercestore' ),
						),
						'tooltip_title' => __( 'Prices Inclusive of Tax', 'commercestore' ),
						'tooltip_desc'  => __( 'When using prices inclusive of tax, you will be entering your prices as the total amount you want a customer to pay for the download, including tax. CommerceStore will calculate the proper amount to tax the customer for the defined total price.', 'commercestore' ),
					),
					'display_tax_rate' => array(
						'id'    => 'display_tax_rate',
						'name'  => __( 'Show Tax Rate on Prices', 'commercestore' ),
						'check' => __( 'Show', 'commercestore' ),
						'desc'  => __( 'Some countries require a notice that product prices include tax.', 'commercestore' ),
						'type' => 'checkbox_description',
					),
					'checkout_include_tax' => array(
						'id'            => 'checkout_include_tax',
						'name'          => __( 'Show in Checkout', 'commercestore' ),
						'desc'          => __( 'Should prices on the checkout page be shown with or without tax?', 'commercestore' ),
						'type'          => 'select',
						'std'           => 'no',
						'options'       => array(
							'yes' => __( 'Including tax', 'commercestore' ),
							'no'  => __( 'Excluding tax', 'commercestore' ),
						),
						'tooltip_title' => __( 'Taxes Displayed for Products on Checkout', 'commercestore' ),
						'tooltip_desc'  => __( 'This option will determine whether the product price displays with or without tax on checkout.', 'commercestore' ),
					),
				),
				'rates' => array(
					'tax_rates' => array(
						'id'   => 'tax_rates',
						'name' => '<strong>' . __( 'Regional Rates', 'commercestore' ) . '</strong>',
						'desc' => __( 'Configure rates for each region you wish to collect sales tax in.', 'commercestore' ),
						'type' => 'tax_rates',
					),
				)
			) ),

			// Extension Settings
			'extensions' => apply_filters( 'cs_settings_extensions', array() ),
			'licenses'   => apply_filters( 'cs_settings_licenses',   array() ),

			// Misc Settings
			'misc' => apply_filters( 'cs_settings_misc', array(
				'main' => array(
					'debug_mode' => array(
						'id'    => 'debug_mode',
						'name'  => __( 'Debug Mode', 'commercestore' ),
						'check' => __( 'Enabled',    'commercestore' ),
						'desc'  => sprintf( __( 'Check this to enable logging of certain behaviors to a file. %s', 'commercestore' ), $debug_log_link ),
						'type'  => 'checkbox_description',
					),
					'disable_styles' => array(
						'id'            => 'disable_styles',
						'name'          => __( 'Disable Styles', 'commercestore' ),
						'check'         => __( 'Check this box to disable all included styling.', 'commercestore' ),
						'desc'          => __( 'This includes buttons, checkout fields, product pages, and all other elements', 'commercestore' ),
						'type'          => 'checkbox_description',
						'tooltip_title' => __( 'Disabling Styles', 'commercestore' ),
						'tooltip_desc'  => __( "If your theme has a complete custom CSS file for CommerceStore, you may wish to disable our default styles. This is not recommended unless you're sure your theme has a complete custom CSS.", 'commercestore' ),
					),
					'item_quantities' => array(
						'id'   => 'item_quantities',
						'name' => __( 'Cart Item Quantities', 'commercestore' ),
						'desc' => sprintf( __( 'Allow quantities to be adjusted when adding %s to the cart, and while viewing the checkout cart.', 'commercestore' ), cs_get_label_plural( true ) ),
						'type' => 'checkbox',
					),
					'uninstall_on_delete' => array(
						'id'   => 'uninstall_on_delete',
						'name' => __( 'Remove Data on Uninstall', 'commercestore' ),
						'desc' => __( 'Check this box if you would like CommerceStore to completely remove all of its data when the plugin is deleted.', 'commercestore' ),
						'type' => 'checkbox',
					),
				),
				'button_text' => array(
					'button_style'   => array(
						'id'      => 'button_style',
						'name'    => __( 'Default Button Style', 'commercestore' ),
						'desc'    => __( 'Choose the style you want to use for the buttons.', 'commercestore' ),
						'type'    => 'select',
						'options' => cs_get_button_styles(),
					),
					'checkout_color' => array(
						'id'      => 'checkout_color',
						'name'    => __( 'Default Button Color', 'commercestore' ),
						'desc'    => __( 'Choose the color you want to use for the buttons.', 'commercestore' ),
						'type'    => 'color_select',
						'options' => cs_get_button_colors(),
						'std'     => 'blue'
					),
					'checkout_label' => array(
						'id'   => 'checkout_label',
						'name' => __( 'Complete Purchase Text', 'commercestore' ),
						'desc' => __( 'The button label for completing a purchase.', 'commercestore' ),
						'type' => 'text',
						'std'  => __( 'Purchase', 'commercestore' ),
					),
					'free_checkout_label' => array(
						'id'   => 'free_checkout_label',
						'name' => __( 'Complete Free Purchase Text', 'commercestore' ),
						'desc' => __( 'The button label for completing a free purchase.', 'commercestore' ),
						'type' => 'text',
						'std'  => __( 'Free Download', 'commercestore' ),
					),
					'add_to_cart_text' => array(
						'id'   => 'add_to_cart_text',
						'name' => __( 'Add to Cart Text', 'commercestore' ),
						'desc' => __( 'Text shown on the Add to Cart Buttons.', 'commercestore' ),
						'type' => 'text',
						'std'  => __( 'Add to Cart', 'commercestore' ),
					),
					'checkout_button_text' => array(
						'id'   => 'checkout_button_text',
						'name' => __( 'Checkout Button Text', 'commercestore' ),
						'desc' => __( 'Text shown on the Add to Cart Button when the product is already in the cart.', 'commercestore' ),
						'type' => 'text',
						'std'  => _x( 'Checkout', 'text shown on the Add to Cart Button when the product is already in the cart', 'commercestore' ),
					),
					'buy_now_text' => array(
						'id'   => 'buy_now_text',
						'name' => __( 'Buy Now Text', 'commercestore' ),
						'desc' => __( 'Text shown on the Buy Now Buttons.', 'commercestore' ),
						'type' => 'text',
						'std'  => __( 'Buy Now', 'commercestore' ),
					),
				),
				'file_downloads' => array(
					'download_method' => array(
						'id'            => 'download_method',
						'name'          => __( 'Download Method', 'commercestore' ),
						'desc'          => sprintf( __( 'Select the file download method. Note, not all methods work on all servers.', 'commercestore' ), cs_get_label_singular() ),
						'type'          => 'select',
						'tooltip_title' => __( 'Download Method', 'commercestore' ),
						'tooltip_desc'  => __( 'Due to its consistency in multiple platforms and better file protection, \'forced\' is the default method. Because CommerceStore uses PHP to process the file with the \'forced\' method, larger files can cause problems with delivery, resulting in hitting the \'max execution time\' of the server. If users are getting 404 or 403 errors when trying to access their purchased files when using the \'forced\' method, changing to the \'redirect\' method can help resolve this.', 'commercestore' ),
						'options'       => array(
							'direct'   => __( 'Forced', 'commercestore' ),
							'redirect' => __( 'Redirect', 'commercestore' ),
						),
					),
					'symlink_file_downloads' => array(
						'id'   => 'symlink_file_downloads',
						'name' => __( 'Symbolically Link Files', 'commercestore' ),
						'desc' => __( 'Check this if you are delivering really large files or having problems with file downloads completing.', 'commercestore' ),
						'type' => 'checkbox',
					),
					'file_download_limit' => array(
						'id'            => 'file_download_limit',
						'name'          => __( 'File Download Limit', 'commercestore' ),
						'desc'          => sprintf( __( 'The maximum number of times files can be downloaded for purchases. Can be overwritten for each %s.', 'commercestore' ), cs_get_label_singular() ),
						'type'          => 'number',
						'size'          => 'small',
						'tooltip_title' => __( 'File Download Limits', 'commercestore' ),
						'tooltip_desc'  => sprintf( __( 'Set the global default for the number of times a customer can download items they purchase. Using a value of 0 is unlimited. This can be defined on a %s-specific level as well. Download limits can also be reset for an individual purchase.', 'commercestore' ), cs_get_label_singular( true ) ),
					),
					'download_link_expiration' => array(
						'id'            => 'download_link_expiration',
						'name'          => __( 'Download Link Expiration', 'commercestore' ),
						'desc'          => __( 'How long should download links be valid for? Default is 24 hours from the time they are generated. Enter a time in hours.', 'commercestore' ),
						'tooltip_title' => __( 'Download Link Expiration', 'commercestore' ),
						'tooltip_desc'  => __( 'When a customer receives a link to their downloads via email, in their receipt, or in their purchase history, the link will only be valid for the timeframe (in hours) defined in this setting. Sending a new purchase receipt or visiting the account page will re-generate a valid link for the customer.', 'commercestore' ),
						'type'          => 'number',
						'size'          => 'small',
						'std'           => '24',
						'min'           => '0',
					),
					'disable_redownload' => array(
						'id'   => 'disable_redownload',
						'name' => __( 'Disable Redownload', 'commercestore' ),
						'desc' => __( 'Check this if you do not want to allow users to redownload items from their purchase history.', 'commercestore' ),
						'type' => 'checkbox',
					),
				),
			) ),

			// Privacy Settings
			'privacy' => apply_filters( 'cs_settings_privacy', array(
				'main' => array(
					'' => array(
						'id'            => 'privacy_settings',
						'name'          => '<h3>' . __( 'Privacy Policy', 'commercestore' ) . '</h3>',
						'desc'          => '',
						'type'          => 'header',
						'tooltip_title' => __( 'Privacy Policy Settings', 'commercestore' ),
						'tooltip_desc'  => __( 'Depending on legal and regulatory requirements, it may be necessary for your site to show a checkbox for agreement to a privacy policy.','commercestore' ),
					),
					'show_agree_to_privacy_policy' => array(
						'id'    => 'show_agree_to_privacy_policy',
						'name'  => __( 'Agreement', 'commercestore' ),
						'check' => __( 'Check this box to show an "Agree to Privacy Policy" checkbox on checkout.', 'commercestore' ),
						'desc'  => __( 'Customers must agree to your privacy policy before purchasing.', 'commercestore' ),
						'type'  => 'checkbox_description',
					),
					'agree_privacy_label' => array(
						'id'          => 'privacy_agree_label',
						'name'        => __( 'Agreement Label', 'commercestore' ),
						'desc'        => __( 'Label for the "Agree to Privacy Policy" checkbox.', 'commercestore' ),
						'type'        => 'text',
						'placeholder' => __( 'I agree to the privacy policy', 'commercestore' ),
						'size'        => 'regular',
					),
					'show_privacy_policy_on_checkout' => array(
						'id'    => 'show_privacy_policy_on_checkout',
						'name'  => __( 'Privacy Policy on Checkout',                     'commercestore' ),
						'check' => __( 'Display the entire Privacy Policy at checkout.', 'commercestore' ) . ' <a href="' . esc_attr( admin_url( 'options-privacy.php' ) ) . '">' . __( 'Set your Privacy Policy here', 'commercestore' ) .'</a>.',
						'desc' =>
							__( 'Display your Privacy Policy on checkout.', 'commercestore' ) . ' <a href="' . esc_attr( admin_url( 'privacy.php' ) ) . '">' . __( 'Set your Privacy Policy here', 'commercestore' ) .'</a>.' .
							'<p>' . sprintf( __( 'Need help creating a Privacy Policy? We recommend %sTermageddon%s.', 'commercestore' ), '<a href="https://termageddon.com/i/commercestore-cs-termageddon-promotion/" target="_blank" rel="noopener noreferrer">', '</a>' ) . '</p>',
						'type'  => 'checkbox',
					),
				),
				'site_terms' => array(
					'' => array(
						'id'            => 'terms_settings',
						'name'          => '<h3>' . __( 'Terms & Agreements', 'commercestore' ) . '</h3>',
						'desc'          => '',
						'type'          => 'header',
						'tooltip_title' => __( 'Terms & Agreements Settings', 'commercestore' ),
						'tooltip_desc'  => __( 'Depending on legal and regulatory requirements, it may be necessary for your site to show checkbox for agreement to terms.','commercestore' ),
					),
					'show_agree_to_terms' => array(
						'id'    => 'show_agree_to_terms',
						'name'  => __( 'Agreement', 'commercestore' ),
						'check' => __( 'Check this box to show an "Agree to Terms" checkbox on checkout.', 'commercestore' ),
						'desc' =>
							__( 'Check this to show an agree to terms on checkout that users must agree to before purchasing.', 'commercestore' ) .
							'<p>' .
							sprintf(
								__( 'Need help creating a Terms of Agreement? We recommend using %sTermageddon%s.', 'commercestore' ),
								'<a href="https://termageddon.com/i/commercestore-cs-termageddon-promotion/" target="_blank" rel="noopener noreferrer">',
								'</a>'
							) .
							'</p>',
						'type'  => 'checkbox_description',
					),
					'agree_label' => array(
						'id'          => 'agree_label',
						'name'        => __( 'Agreement Label', 'commercestore' ),
						'desc'        => __( 'Label for the "Agree to Terms" checkbox.', 'commercestore' ),
						'placeholder' => __( 'I agree to the terms', 'commercestore' ),
						'type'        => 'text',
						'size'        => 'regular',
					),
					'agree_text' => array(
						'id'   => 'agree_text',
						'name' => __( 'Agreement Text', 'commercestore' ),
						'type' => 'rich_editor',
					),
				),
				'export_erase' => array(
					array(
						'id'            => 'payment_privacy_status_action_header',
						'name'          => '<h3>' . __( 'Order Statuses', 'commercestore' ) . '</h3>',
						'type'          => 'header',
						'desc'          => __( 'When a user requests to be anonymized or removed from a site, these are the actions that will be taken on payments associated with their customer, by status.','commercestore' ),
						'tooltip_title' => __( 'What settings should I use?', 'commercestore' ),
						'tooltip_desc'  => __( 'By default, CommerceStore sets suggested actions based on the Payment Status. These are purely recommendations, and you may need to change them to suit your store\'s needs. If you are unsure, you can safely leave these settings as is.','commercestore' ),
					),
					array(
						'id'   => 'payment_privacy_status_action_text',
						'name' => __( 'Rules', 'commercestore' ),
						'type' => 'descriptive_text',
						'desc' => __( 'When a user wants their order history anonymized or removed, the following rules will be used:','commercestore' ),
					)
				)
			) )
		);

		// Add Privacy settings for statuses
		foreach ( $payment_statuses as $status => $label ) {
			switch ( $status ) {
				case 'complete':
				case 'refunded':
				case 'revoked':
					$action = 'anonymize';
					break;

				case 'failed':
				case 'abandoned':
					$action = 'delete';
					break;

				case 'pending':
				case 'processing':
				default:
					$action = 'none';
					break;
			}

			$cs_settings['privacy']['export_erase'][] = array(
				'id'      => 'payment_privacy_status_action_' . $status,
				'name'    => $label,
				'desc'    => '',
				'type'    => 'select',
				'std'     => $action,
				'options' => $options,
			);
		}

		if ( ! cs_shop_supports_buy_now() ) {
			$cs_settings['misc']['button_text']['buy_now_text']['disabled']      = true;
			$cs_settings['misc']['button_text']['buy_now_text']['tooltip_title'] = __( 'Buy Now Disabled', 'commercestore' );
			$cs_settings['misc']['button_text']['buy_now_text']['tooltip_desc']  = __( 'Buy Now buttons are only available for stores that have a single supported gateway active and that do not use taxes.', 'commercestore' );
		}

		// Show a disabled "Default Rate" in "Tax Rates" if the value is not 0.
		if ( false !== cs_get_option( 'tax_rate' ) ) {
			$cs_settings['taxes']['rates'] = array_merge(
				array(
					'tax_rate' => array(
						'id'            => 'tax_rate',
						'type'          => 'tax_rate',
						'name'          => __( 'Default Rate', 'commercestore' ),
						'desc'          => (
							'<div class="notice inline notice-error"><p>' . __( 'This setting is no longer used in this version of CommerceStore. Please confirm your regional tax rates are properly configured properly below, then click "Save Changes" to dismiss this notice.', 'commercestore' ) . '</p></div>'
						),
					),
				),
				$cs_settings['taxes']['rates']
			);
		}

		// Allow registered settings to surface the deprecated "Styles" tab.
		if ( has_filter( 'cs_settings_styles' ) ) {
			$cs_settings['styles'] = cs_apply_filters_deprecated(
				'cs_settings_styles',
				array(
					array(
						'main'    => array(),
						'buttons' => array(),
					),
				),
				'3.0',
				'cs_settings_misc'
			);
		}
	}

	// Filter & return
	if (CS_FEATURE_MARKETING == false) {
		unset ( $cs_settings['marketing'] );
	}
	return apply_filters( 'cs_registered_settings', $cs_settings );
}

/**
 * Settings Sanitization
 *
 * Adds a settings error (for the updated message)
 * At some point this will validate input
 *
 * @since 1.0.8.2
 *
 * @param array  $input       The value inputted in the field
 *
 * @global array $cs_options Array of all the CommerceStore Options
 *
 * @return array $input Sanitized value
 */
function cs_settings_sanitize( $input = array() ) {
	global $cs_options;

	// Default values
	$referrer      = '';
	$setting_types = cs_get_registered_settings_types();
	$doing_section = ! empty( $_POST['_wp_http_referer'] );
	$input         = ! empty( $input )
		? $input
		: array();

	if ( true === $doing_section ) {

		// Pull out the tab and section
		parse_str( $_POST['_wp_http_referer'], $referrer );
		$tab     = ! empty( $referrer['tab']     ) ? sanitize_key( $referrer['tab']     ) : 'general';
		$section = ! empty( $referrer['section'] ) ? sanitize_key( $referrer['section'] ) : 'main';

		// Maybe override the tab section
		if ( ! empty( $_POST['cs_section_override'] ) ) {
			$section = sanitize_text_field( $_POST['cs_section_override'] );
		}

		// Get setting types for this section
		$setting_types = cs_get_registered_settings_types( $tab, $section );

		// Run a general sanitization for the tab for special fields (like taxes)
		$input = apply_filters( 'cs_settings_' . $tab . '_sanitize', $input );

		// Run a general sanitization for the section so custom tabs with sub-sections can save special data
		$input = apply_filters( 'cs_settings_' . $tab . '-' . $section . '_sanitize', $input );
	}

	// Remove non setting types and merge settings together
	$non_setting_types = cs_get_non_setting_types();
	$setting_types     = array_diff( $setting_types, $non_setting_types );
	$output            = array_merge( $cs_options, $input );

	// Loop through settings, and apply any filters
	foreach ( $setting_types as $key => $type ) {

		// Skip if type is empty
		if ( empty( $type ) ) {
			continue;
		}

		if ( array_key_exists( $key, $output ) ) {
			$output[ $key ] = apply_filters( 'cs_settings_sanitize_' . $type, $output[ $key ], $key );
			$output[ $key ] = apply_filters( 'cs_settings_sanitize', $output[ $key ], $key );
		}

		if ( true === $doing_section ) {
			switch ( $type ) {
				case 'checkbox':
				case 'checkbox_description':
				case 'gateways':
				case 'multicheck':
				case 'payment_icons':
					if ( array_key_exists( $key, $input ) && $output[ $key ] === '-1' ) {
						unset( $output[ $key ] );
					}
					break;
				case 'text':
					if ( array_key_exists( $key, $input ) && empty( $input[ $key ] ) ) {
						unset( $output[ $key ] );
					}
					break;
				case 'number':
					if ( array_key_exists( $key, $output ) && ! array_key_exists( $key, $input ) ) {
						unset( $output[ $key ] );
					}

					$setting_details = cs_get_registered_setting_details( $tab, $section, $key );
					$number_type     = ! empty( $setting_details['step'] ) && false !== strpos( $setting_details['step'], '.' ) ? 'floatval' : 'intval';
					$minimum         = isset( $setting_details['min'] ) ? $number_type( $setting_details['min'] ) : false;
					$maximum         = isset( $setting_details['max'] ) ? $number_type( $setting_details['max'] ) : false;
					$new_value       = $number_type( $input[ $key ] );

					if ( ( false !== $minimum && $minimum > $new_value ) || ( false !== $maximum && $maximum < $new_value ) ) {
						unset( $output[ $key ] );
					}
					break;
				default:
					if ( array_key_exists( $key, $input ) && empty( $input[ $key ] ) || ( array_key_exists( $key, $output ) && ! array_key_exists( $key, $input ) ) ) {
						unset( $output[ $key ] );
					}
					break;
			}
		} elseif ( empty( $input[ $key ] ) ) {
			unset( $output[ $key ] );
		}
	}

	// Return output.
	return (array) $output;
}

/**
 * Flattens the set of registered settings and their type so we can easily sanitize all the settings
 * in a much cleaner set of logic in cs_settings_sanitize
 *
 * @since  2.6.5
 * @since  2.8 - Added the ability to filter setting types by tab and section
 *
 * @param $filtered_tab     bool|string     A tab to filter setting types by.
 * @param $filtered_section bool|string A section to filter setting types by.
 *
 * @return array Key is the setting ID, value is the type of setting it is registered as
 */
function cs_get_registered_settings_types( $filtered_tab = false, $filtered_section = false ) {
	$settings      = cs_get_registered_settings();
	$setting_types = array();

	foreach ( $settings as $tab_id => $tab ) {

		if ( false !== $filtered_tab && $filtered_tab !== $tab_id ) {
			continue;
		}

		foreach ( $tab as $section_id => $section_or_setting ) {

			// See if we have a setting registered at the tab level for backwards compatibility
			if ( false !== $filtered_section && is_array( $section_or_setting ) && array_key_exists( 'type', $section_or_setting ) ) {
				$setting_types[ $section_or_setting['id'] ] = $section_or_setting['type'];
				continue;
			}

			if ( false !== $filtered_section && $filtered_section !== $section_id ) {
				continue;
			}

			foreach ( $section_or_setting as $section_settings ) {
				if ( ! empty( $section_settings['type'] ) ) {
					$setting_types[ $section_settings['id'] ] = $section_settings['type'];
				}
			}
		}
	}

	return $setting_types;
}

/**
 * Allow getting a specific setting's details.
 *
 * @since 3.0
 *
 * @param string $filtered_tab      The tab the setting's section is in.
 * @param string $filtered_section  The section the setting is located in.
 * @param string $setting_key       The key associated with the setting.
 *
 * @return array
 */
function cs_get_registered_setting_details( $filtered_tab = '', $filtered_section = '', $setting_key = '' ) {
	$settings        = cs_get_registered_settings();
	$setting_details = array();

	if ( isset( $settings[ $filtered_tab ][ $filtered_section][ $setting_key ] ) ) {
		$setting_details = $settings[ $filtered_tab ][ $filtered_section][ $setting_key ];
	}

	return $setting_details;
}

/**
 * Return array of settings field types that aren't settings.
 *
 * @since 3.0
 *
 * @return array
 */
function cs_get_non_setting_types() {
	return apply_filters( 'cs_non_setting_types', array(
		'header',
		'descriptive_text',
		'hook',
	) );
}

/**
 * Misc File Download Settings Sanitization
 *
 * @since 2.5
 *
 * @param array $input The value inputted in the field
 *
 * @return string $input Sanitized value
 */
function cs_settings_sanitize_misc_file_downloads( $input ) {

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return $input;
	}

	if ( cs_get_file_download_method() != $input['download_method'] || ! cs_htaccess_exists() ) {
		// Force the .htaccess files to be updated if the Download method was changed.
		cs_create_protection_files( true, $input['download_method'] );
	}

	return $input;
}

add_filter( 'cs_settings_misc-file_downloads_sanitize', 'cs_settings_sanitize_misc_file_downloads' );

/**
 * Misc Accounting Settings Sanitization
 *
 * @since 2.5
 *
 * @param array $input The value inputted in the field
 *
 * @return array $input Sanitized value
 */
function cs_settings_sanitize_misc_accounting( $input ) {

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return $input;
	}

	if ( ! empty( $input['enable_sequential'] ) && ! cs_get_option( 'enable_sequential' ) ) {

		// Shows an admin notice about upgrading previous order numbers
		update_option( 'cs_upgrade_sequential', time() );

	}

	return $input;
}

add_filter( 'cs_settings_gateways-accounting_sanitize', 'cs_settings_sanitize_misc_accounting' );

/**
 * Taxes Settings Sanitization
 *
 * Adds a settings error (for the updated message)
 * This also saves the tax rates table
 *
 * @since 1.6
 *
 * @param array $input The value inputted in the field
 *
 * @return array $input Sanitized value.
 */
function cs_settings_sanitize_taxes( $input ) {

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return $input;
	}

	if ( ! isset( $_POST['tax_rates'] ) ) {
		return $input;
	}

	$tax_rates = ! empty( $_POST['tax_rates'] )
		? $_POST['tax_rates']
		: array();

	foreach ( $tax_rates as $tax_rate ) {

		$scope = isset( $tax_rate['global'] )
			? 'country'
			: 'region';

		$region = isset( $tax_rate['state'] )
			? sanitize_text_field( $tax_rate['state'] )
			: '';

		$adjustment_data = array(
			'name'        => sanitize_text_field( $tax_rate['country'] ),
			'type'        => 'tax_rate',
			'scope'       => $scope,
			'amount_type' => 'percent',
			'amount'      => floatval( $tax_rate['rate'] ),
			'description' => $region,
		);

		if ( empty( $adjustment_data['name'] ) || $adjustment_data['amount'] <= 0 ) {
			continue;
		}

		$existing_adjustment = cs_get_adjustments( $adjustment_data );

		if ( ! empty( $existing_adjustment ) ) {
			$adjustment                = $existing_adjustment[0];
			$adjustment_data['status'] = sanitize_text_field( $tax_rate['status'] );

			cs_update_adjustment( $adjustment->id, $adjustment_data );
		} else {
			$adjustment_data['status'] = 'active';

			cs_add_adjustment( $adjustment_data );
		}

	}

	return $input;
}
add_filter( 'cs_settings_taxes_sanitize', 'cs_settings_sanitize_taxes' );

/**
 * Payment Gateways Settings Sanitization
 *
 * @since 2.7
 *
 * @param array $input The value inputted in the field
 *
 * @return string $input Sanitized value
 */
function cs_settings_sanitize_gateways( $input = array() ) {

	// Bail if user cannot manage shop settings
	if ( ! current_user_can( 'manage_shop_settings' ) || empty( $input['default_gateway'] ) ) {
		return $input;
	}

	// Unset the default gateway if there are no `gateways` enabled
	if ( empty( $input['gateways'] ) || '-1' == $input['gateways'] ) {
		unset( $input['default_gateway'] );

	// Current gateway is no longer enabled, so
	} elseif ( ! array_key_exists( $input['default_gateway'], $input['gateways'] ) ) {
		$enabled_gateways = $input['gateways'];

		reset( $enabled_gateways );

		$first_gateway = key( $enabled_gateways );

		if ( $first_gateway ) {
			$input['default_gateway'] = $first_gateway;
		}
	}

	return $input;
}
add_filter( 'cs_settings_gateways_sanitize', 'cs_settings_sanitize_gateways' );

/**
 * Sanitize text fields
 *
 * @since 1.8
 *
 * @param array $input The field value
 *
 * @return string $input Sanitized value
 */
function cs_sanitize_text_field( $input = '' ) {
	$allowed_tags = cs_get_allowed_tags();

	return trim( wp_kses( $input, $allowed_tags ) );
}

add_filter( 'cs_settings_sanitize_text', 'cs_sanitize_text_field' );

/**
 * Sanitize HTML Class Names
 *
 * @since 2.6.11
 *
 * @param  string|array $class HTML Class Name(s)
 *
 * @return string $class
 */
function cs_sanitize_html_class( $class = '' ) {

	if ( is_string( $class ) ) {
		$class = sanitize_html_class( $class );
	} else if ( is_array( $class ) ) {
		$class = array_values( array_map( 'sanitize_html_class', $class ) );
		$class = implode( ' ', array_unique( $class ) );
	}

	return $class;
}


/**
 * Sanitizes banned emails.
 *
 * @since 3.0
 */
function cs_sanitize_banned_emails( $value, $key ) {
	if ( 'banned_emails' !== $key ) {
		return $value;
	}

	if ( ! empty( $value ) ) {
		// Sanitize the input
		$emails = array_map( 'trim', explode( "\n", $value ) );
		$emails = array_unique( $emails );
		$emails = array_map( 'sanitize_text_field', $emails );

		foreach ( $emails as $id => $email ) {
			if ( ! is_email( $email ) && $email[0] != '@' && $email[0] != '.' ) {
				unset( $emails[ $id ] );
			}
		}
	} else {
		$emails = '';
	}

	return $emails;
}
add_filter( 'cs_settings_sanitize', 'cs_sanitize_banned_emails', 10, 2 );

/**
 * Retrieve settings tabs
 *
 * @since 1.8
 * @since 2.11.4 Any tabs with no registered settings are filtered out in `cs_options_page`.
 *
 * @return array $tabs
 */
function cs_get_settings_tabs() {
	return apply_filters( 'cs_settings_tabs', array(
		'general'    => __( 'General', 'commercestore' ),
		'gateways'   => __( 'Payments', 'commercestore' ),
		'emails'     => __( 'Emails', 'commercestore' ),
		'marketing'  => __( 'Marketing', 'commercestore' ),
		'styles'     => __( 'Styles', 'commercestore' ),
		'taxes'      => __( 'Taxes', 'commercestore' ),
		'privacy'    => __( 'Privacy', 'commercestore' ),
		'extensions' => __( 'Extensions', 'commercestore' ),
		'licenses'   => __( 'Licenses', 'commercestore' ),
		'misc'       => __( 'Misc', 'commercestore' ),
	) );
}

/**
 * Retrieve settings tabs
 *
 * @since 2.5
 * @return array $section
 */
function cs_get_settings_tab_sections( $tab = false ) {
	$tabs     = array();
	$sections = cs_get_registered_settings_sections();

	if ( $tab && ! empty( $sections[ $tab ] ) ) {
		$tabs = $sections[ $tab ];
	} else if ( $tab ) {
		$tabs = array();
	}

	return $tabs;
}

/**
 * Get the settings sections for each tab
 * Uses a static to avoid running the filters on every request to this function
 *
 * @since  2.5
 * @return array Array of tabs and sections
 */
function cs_get_registered_settings_sections() {
	static $sections = null;

	if ( null === $sections ) {
		$sections = array(
			'general'    => apply_filters( 'cs_settings_sections_general', array(
				'main'               => __( 'Store',    'commercestore' ),
				'currency'           => __( 'Currency',   'commercestore' ),
				'pages'              => __( 'Pages',      'commercestore' ),
				'moderation'         => __( 'Moderation', 'commercestore' ),
				'refunds'            => __( 'Refunds',    'commercestore' ),
				'api'                => __( 'API',        'commercestore' ),
				'tracking'           => __( 'Tracking',   'commercestore' )
			) ),
			'gateways'   => apply_filters( 'cs_settings_sections_gateways', array(
				'main'               => __( 'General',         'commercestore' ),
				'checkout'           => __( 'Checkout',           'commercestore' ),
				'accounting'         => __( 'Accounting',         'commercestore' ),
			) ),
			'emails'     => apply_filters( 'cs_settings_sections_emails', array(
				'main'               => __( 'General',            'commercestore' ),
				'purchase_receipts'  => __( 'Purchase Receipts',  'commercestore' ),
				'sale_notifications' => __( 'Sale Notifications', 'commercestore' )
			) ),
			'marketing'  => apply_filters( 'cs_settings_sections_marketing', array(
				'main' => __( 'General', 'commercestore' ),
			) ),
			'styles'     => apply_filters( 'cs_settings_sections_styles', array(
				'main'               => __( 'General', 'commercestore' ),
				'buttons'            => __( 'Buttons', 'commercestore' )
			) ),
			'taxes'      => apply_filters( 'cs_settings_sections_taxes', array(
				'main'               => __( 'General', 'commercestore' ),
				'rates'              => __( 'Rates',   'commercestore' ),
			) ),
			'privacy'    => apply_filters( 'cs_settings_section_privacy', array(
				'main'               => __( 'Privacy Policy',     'commercestore' ),
				'site_terms'         => __( 'Terms & Agreements', 'commercestore' ),
				'export_erase'       => __( 'Export & Erase',     'commercestore' )
			) ),
			'extensions' => apply_filters( 'cs_settings_sections_extensions', array(
				'main'               => __( 'Main', 'commercestore' )
			) ),
			'licenses'   => apply_filters( 'cs_settings_sections_licenses', array() ),
			'misc'       => apply_filters( 'cs_settings_sections_misc', array(
				'main'               => __( 'General',            'commercestore' ),
				'button_text'        => __( 'Purchase Buttons',   'commercestore' ),
				'file_downloads'     => __( 'File Downloads',     'commercestore' ),
			) )
		);
	}

	// Filter & return
	return apply_filters( 'cs_settings_sections', $sections );
}

/**
 * Retrieve a list of all published pages
 *
 * On large sites this can be expensive, so only load if on the settings page or $force is set to true
 *
 * @since 1.9.5
 *
 * @param bool $force Force the pages to be loaded even if not on settings
 *
 * @return array $pages_options An array of the pages
 */
function cs_get_pages( $force = false ) {

	$pages_options = array( '' => '' ); // Blank option

	if ( ( ! isset( $_GET['page'] ) || 'cs-settings' != $_GET['page'] ) && ! $force ) {
		return $pages_options;
	}

	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $page ) {
			$pages_options[ $page->ID ] = $page->post_title;
		}
	}

	return $pages_options;
}

/**
 * Header Callback
 *
 * Renders the header.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_header_callback( $args ) {
	echo apply_filters( 'cs_after_setting_output', '', $args );
}

/**
 * Checkbox Callback
 *
 * Renders checkboxes.
 *
 * @since 1.0
 * @since 3.0 Updated to use `CS_HTML_Elements`.
 *
 * @param array $args Arguments passed by the setting.
 */
function cs_checkbox_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$name = '';
	} else {
		$name = 'cs_settings[' . cs_sanitize_key( $args['id'] ) . ']';
	}

	$class = cs_sanitize_html_class( $args['field_class'] );

	$args['name']    = $name;
	$args['class']   = $class;
	$args['current'] = ! empty( $cs_option )
		? $cs_option
		: '';
	$args['label']   = wp_kses_post( $args['desc'] );

	$html    = '<input type="hidden" name="' . $name . '" value="-1" />';
	$html   .= '<div class="cs-check-wrapper">';
	$html   .= CS()->html->checkbox( $args );
	$html   .= '</div>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Checkbox with description Callback
 *
 * Renders checkboxes with a description.
 *
 * @since 3.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_checkbox_description_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$name = '';
	} else {
		$name = 'name="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']"';
	}

	$class   = cs_sanitize_html_class( $args['field_class'] );
	$checked = ! empty( $cs_option ) ? checked( 1, $cs_option, false ) : '';
	$html    = '<input type="hidden"' . $name . ' value="-1" />';
	$html   .= '<div class="cs-check-wrapper">';
	$html   .= '<input type="checkbox" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']"' . $name . ' value="1" ' . $checked . ' class="' . $class . '"/>';
	$html   .= '<label for="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['check'] ) . '</label>';
	$html   .= '</div>';
	$html   .= '<p class="description">' . wp_kses_post( $args['desc'] ) . '</p>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Multicheck Callback
 *
 * Renders multiple checkboxes.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_multicheck_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	$class = cs_sanitize_html_class( $args['field_class'] );

	$html = '';
	if ( ! empty( $args['options'] ) ) {
		$html .= '<input type="hidden" name="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" value="-1" />';

		foreach ( $args['options'] as $key => $option ):
			if ( isset( $cs_option[ $key ] ) ) {
				$enabled = $option;
			} else {
				$enabled = null;
			}
			$html .= '<div class="cs-check-wrapper">';
			$html .= '<input name="cs_settings[' . cs_sanitize_key( $args['id'] ) . '][' . cs_sanitize_key( $key ) . ']" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . '][' . cs_sanitize_key( $key ) . ']" class="' . $class . '" type="checkbox" value="' . esc_attr( $option ) . '" ' . checked( $option, $enabled, false ) . '/>&nbsp;';
			$html .= '<label for="cs_settings[' . cs_sanitize_key( $args['id'] ) . '][' . cs_sanitize_key( $key ) . ']">' . wp_kses_post( $option ) . '</label>';
			$html .= '</div>';
		endforeach;
		$html .= '<p class="description">' . $args['desc'] . '</p>';
	}

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Payment method icons callback
 *
 * @since 2.1
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_payment_icons_callback( $args = array() ) {

	// Start an output buffer
	ob_start();

	$cs_option = cs_get_option( $args['id'] );
	$class      = cs_sanitize_html_class( $args['field_class'] ); ?>

	<input type="hidden" name="cs_settings[<?php echo cs_sanitize_key( $args['id'] ); ?>]" value="-1" />
	<input type="hidden" name="cs_settings[payment_icons_order]" class="cs-order" value="<?php echo cs_get_option( 'payment_icons_order' ); ?>" />

	<?php

	// Only go if options exist
	if ( ! empty( $args['options'] ) ) :
		$class = cs_sanitize_html_class( $args['field_class'] );

		// Everything is wrapped in a sortable UL
		?><ul id="cs-payment-icons-list" class="cs-sortable-list">

		<?php foreach ( $args['options'] as $key => $option ) :
			$enabled = isset( $cs_option[ $key ] )
				? $option
				: null; ?>

			<li class="cs-check-wrapper" data-key="<?php echo cs_sanitize_key( $key ); ?>">
				<label>
					<input name="cs_settings[<?php echo cs_sanitize_key( $args['id'] ); ?>][<?php echo cs_sanitize_key( $key ); ?>]" id="cs_settings[<?php echo cs_sanitize_key( $args['id'] ); ?>][<?php echo cs_sanitize_key( $key ); ?>]" class="<?php echo $class; ?>" type="checkbox" value="<?php echo esc_attr( $option ); ?>" <?php echo checked( $option, $enabled, false ); ?> />

				<?php if ( cs_string_is_image_url( $key ) ) : ?>
					<span class="payment-icon-image"><img class="payment-icon" src="<?php echo esc_url( $key ); ?>" /></span>
				<?php else :

					$type = '';
					$card = strtolower( str_replace( ' ', '', $option ) );

					if ( has_filter( 'cs_accepted_payment_' . $card . '_image' ) ) {
						$image = apply_filters( 'cs_accepted_payment_' . $card . '_image', '' );

					} elseif ( has_filter( 'cs_accepted_payment_' . $key . '_image' ) ) {
						$image = apply_filters( 'cs_accepted_payment_' . $key . '_image', '' );
					} else {
						// Set the type to SVG.
						$type = 'svg';

						// Get SVG dimensions.
						$dimensions = cs_get_payment_icon_dimensions( $key );

						// Get SVG markup.
						$image = cs_get_payment_icon( array(
							'icon'    => $key,
							'width'   => $dimensions['width'],
							'height'  => $dimensions['height'],
							'title'   => $option,
							'classes' => array( 'payment-icon' )
						) );
					}

					// SVG or IMG
					if ( 'svg' === $type ) : ?>

						<span class="payment-icon-image"><?php echo $image; // Contains trusted HTML ?></span>

					<?php else : ?>

						<span class="payment-icon-image"><img class="payment-icon" src="<?php echo esc_url( $image ); ?>"/></span>

					<?php endif; ?>

				<?php endif; ?>
					<span class="payment-option-name"><?php echo $option; ?></span>
				</label>
			</li>

		<?php endforeach; ?>

		</ul>

		<p class="description" style="margin-top:16px;"><?php echo wp_kses_post( $args['desc'] ); ?></p>

	<?php endif;

	// Get the contents of the current output buffer
	$html = ob_get_clean();

	// Filter & return
	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Enforce the payment icon order (from the sortable admin area UI)
 *
 * @since 3.0
 *
 * @param array $icons
 * @return array
 */
function cs_order_accepted_payment_icons( $icons = array() ) {

	// Get the order option
	$order = cs_get_option( 'payment_icons_order', '' );

	// If order is set, enforce it
	if ( ! empty( $order ) ) {
		$order = array_flip( explode( ',', $order ) );
		$order = array_intersect_key( $order, $icons );
		$icons = array_merge( $order, $icons );
	}

	// Return ordered icons
	return $icons;
}
add_filter( 'cs_accepted_payment_icons', 'cs_order_accepted_payment_icons', 99 );

/**
 * Radio Callback
 *
 * Renders radio boxes.
 *
 * @since 1.3.3
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_radio_callback( $args ) {
	$cs_options = cs_get_option( $args['id'] );

	$html = '';

	$class = cs_sanitize_html_class( $args['field_class'] );

	foreach ( $args['options'] as $key => $option ) :
		$checked = false;

		if ( $cs_options && $cs_options == $key ) {
			$checked = true;
		} elseif ( isset( $args['std'] ) && $args['std'] == $key && ! $cs_options ) {
			$checked = true;
		}

		$html .= '<div class="cs-check-wrapper">';
		$html .= '<input name="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . '][' . cs_sanitize_key( $key ) . ']" class="' . $class . '" type="radio" value="' . cs_sanitize_key( $key ) . '" ' . checked( true, $checked, false ) . '/>&nbsp;';
		$html .= '<label for="cs_settings[' . cs_sanitize_key( $args['id'] ) . '][' . cs_sanitize_key( $key ) . ']">' . esc_html( $option ) . '</label>';
		$html .= '</div>';
	endforeach;

	$html .= '<p class="description">' . apply_filters( 'cs_after_setting_output', wp_kses_post( $args['desc'] ), $args ) . '</p>';

	echo $html;
}

/**
 * Gateways Callback
 *
 * Renders gateways fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_gateways_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	$html  = '<input type="hidden" name="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" value="-1" />';
	$html .= '<input type="hidden" name="cs_settings[gateways_order]" class="cs-order" value="' . cs_get_option( 'gateways_order' ) . '" />';

	if ( ! empty( $args['options'] ) ) {
		$class = cs_sanitize_html_class( $args['field_class'] );
		$html .= '<ul id="cs-payment-gateways" class="cs-sortable-list">';

		foreach ( $args['options'] as $key => $option ) {
			if ( isset( $cs_option[ $key ] ) ) {
				$enabled = '1';
			} else {
				$enabled = null;
			}

			$html .= '<li class="cs-check-wrapper" data-key="' . cs_sanitize_key( $key ) . '">';
			$html .= '<label>';
			$html .= '<input name="cs_settings[' . esc_attr( $args['id'] ) . '][' . cs_sanitize_key( $key ) . ']" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . '][' . cs_sanitize_key( $key ) . ']" class="' . $class . '" type="checkbox" value="1" data-gateway-key="' . cs_sanitize_key( $key ) . '" ' . checked( '1', $enabled, false ) . '/>&nbsp;';
			$html .= esc_html( $option['admin_label'] );
			if ( 'manual' === $key ) {
				$html .= '<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<strong>' . esc_html__( 'Store Gateway', 'commercestore' ) . '</strong>: ' . esc_html__( 'This is an internal payment gateway which can be used for manually added orders or test purchases. No money is actually processed.', 'commercestore' ) . '"></span>';
			}
			$html .= '</label>';
			$html .= '</li>';
		}

		$html .= '</ul>';

		$url_args = array(
			'utm_source'   => 'settings',
			'utm_medium'   => 'gateways',
			'utm_campaign' => 'admin',
		);

		$url   = add_query_arg( $url_args, 'https://commercestore.com/downloads/category/extensions/gateways/' );
		$html .= '<p class="description">' . esc_html__( 'These gateways will be offered at checkout.', 'commercestore' ) . '<br>' . sprintf( __( 'More <a href="%s">Payment Gateways</a> are available.', 'commercestore' ), esc_url( $url ) ) . '</p>';
	}

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Gateways Callback (drop down)
 *
 * Renders gateways select menu
 *
 * @since 1.5
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_gateway_select_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	$class = cs_sanitize_html_class( $args['field_class'] );
	if ( isset( $args['chosen'] ) ) {
		$class .= ' cs-select-chosen';
		if ( is_rtl() ) {
			$class .= ' chosen-rtl';
		}
	}

	$html     = '<select name="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']"" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" class="' . $class . '">';
	$html    .= '<option value="">' . __( '&mdash; No gateway &mdash;', 'commercestore' ) . '</option>';
	$gateways = cs_get_payment_gateways();

	foreach ( $gateways as $key => $option ) {
		$selected = isset( $cs_option )
			? selected( $key, $cs_option, false )
			: '';
		$disabled = disabled( cs_is_gateway_active( $key ), false, false );
		$html    .= '<option value="' . cs_sanitize_key( $key ) . '"' . $selected . ' ' . $disabled . '>' . esc_html( $option['admin_label'] ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Text Callback
 *
 * Renders text fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_text_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	if ( $cs_option ) {
		$value = $cs_option;
	} elseif ( ! empty( $args['allow_blank'] ) && empty( $cs_option ) ) {
		$value = '';
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value            = isset( $args['std'] ) ? $args['std'] : '';
		$name             = '';
	} else {
		$name = 'name="cs_settings[' . esc_attr( $args['id'] ) . ']"';
	}

	$class = cs_sanitize_html_class( $args['field_class'] );

	$placeholder = ! empty( $args['placeholder'] )
		? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"'
		: '';

	$disabled = ! empty( $args['disabled'] ) ? ' disabled="disabled"' : '';
	$readonly = $args['readonly'] === true   ? ' readonly="readonly"' : '';
	$size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html     = '<input type="text" class="' . $class . ' ' . sanitize_html_class( $size ) . '-text" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"' . $readonly . $disabled . $placeholder . ' />';
	$html    .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Email Callback
 *
 * Renders email fields.
 *
 * @since 2.8
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_email_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	if ( $cs_option ) {
		$value = $cs_option;
	} elseif ( ! empty( $args['allow_blank'] ) && empty( $cs_option ) ) {
		$value = '';
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value            = isset( $args['std'] ) ? $args['std'] : '';
		$name             = '';
	} else {
		$name = 'name="cs_settings[' . esc_attr( $args['id'] ) . ']"';
	}

	$class = cs_sanitize_html_class( $args['field_class'] );

	$placeholder = isset( $args['placeholder'] )
		? $args['placeholder']
		: '';

	$disabled = ! empty( $args['disabled'] ) ? ' disabled="disabled"' : '';
	$readonly = $args['readonly'] === true ? ' readonly="readonly"' : '';
	$size     = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html     = '<input type="email" class="' . $class . ' ' . sanitize_html_class( $size ) . '-text" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '" ' . $readonly . $disabled . ' placeholder="' . esc_attr( $placeholder ) . '" />';
	$html    .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Number Callback
 *
 * Renders number fields.
 *
 * @since 1.9
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_number_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	if ( is_numeric( $cs_option ) ) {
		$value = $cs_option;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	if ( isset( $args['faux'] ) && true === $args['faux'] ) {
		$args['readonly'] = true;
		$value            = isset( $args['std'] ) ? $args['std'] : '';
		$name             = '';
	} else {
		$name = 'name="cs_settings[' . esc_attr( $args['id'] ) . ']"';
	}

	$class = cs_sanitize_html_class( $args['field_class'] );

	$max  = isset( $args['max']  ) ? $args['max']  : 999999;
	$min  = isset( $args['min']  ) ? $args['min']  : 0;
	$step = isset( $args['step'] ) ? $args['step'] : 1;

	$disabled = ! empty( $args['disabled'] ) ? ' disabled="disabled"' : '';

	$size  = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html  = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $class . ' ' . sanitize_html_class( $size ) . '-text" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" ' . $name . ' value="' . esc_attr( stripslashes( $value ) ) . '"' . $disabled . ' />';
	$html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Textarea Callback
 *
 * Renders textarea fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_textarea_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	if ( $cs_option ) {
		if ( is_array( $cs_option ) ) {
			$value = implode( "\n", maybe_unserialize( $cs_option ) );
		} else {
			$value = $cs_option;
		}
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$class       = cs_sanitize_html_class( $args['field_class'] );
	$placeholder = ! empty( $args['placeholder'] )
		? ' placeholder="' . esc_attr( $args['placeholder'] ) . '"'
		: '';

	$html  = '<textarea class="' . $class . '" cols="50" rows="5" ' . $placeholder . ' id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" name="cs_settings[' . esc_attr( $args['id'] ) . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Password Callback
 *
 * Renders password fields.
 *
 * @since 1.3
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_password_callback( $args ) {
	$cs_options = cs_get_option( $args['id'] );

	if ( $cs_options ) {
		$value = $cs_options;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$class = cs_sanitize_html_class( $args['field_class'] );

	$size  = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html  = '<input type="password" class="' . $class . ' ' . sanitize_html_class( $size ) . '-text" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" name="cs_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '"/>';
	$html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Missing Callback
 *
 * If a function is missing for settings callbacks alert the user.
 *
 * @since 1.3.1
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_missing_callback( $args ) {
	printf(
		__( 'The callback function used for the %s setting is missing.', 'commercestore' ),
		'<strong>' . $args['id'] . '</strong>'
	);
}

/**
 * Select Callback
 *
 * Renders select fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_select_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	if ( $cs_option ) {
		$value = $cs_option;
	} else {

		// Properly set default fallback if the Select Field allows Multiple values
		if ( empty( $args['multiple'] ) ) {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		} else {
			$value = ! empty( $args['std'] ) ? $args['std'] : array();
		}

	}

	$placeholder = isset( $args['placeholder'] )
		? $args['placeholder']
		: '';

	$class = cs_sanitize_html_class( $args['field_class'] );

	if ( isset( $args['chosen'] ) ) {
		$class .= ' cs-select-chosen';
		if ( is_rtl() ) {
			$class .= ' chosen-rtl';
		}
	}

	// Nonce
	$nonce = isset( $args['data']['nonce'] )
		? ' data-nonce="' . sanitize_text_field( $args['data']['nonce'] ) . '"'
		: '';

	// If the Select Field allows Multiple values, save as an Array
	$name_attr = 'cs_settings[' . esc_attr( $args['id'] ) . ']';
	$name_attr = ( $args['multiple'] ) ? $name_attr . '[]' : $name_attr;

	$html = '<select ' . $nonce . ' id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" name="' . $name_attr . '" class="' . $class . '" data-placeholder="' . esc_html( $placeholder ) . '" ' . ( ( $args['multiple'] ) ? 'multiple="true"' : '' ) . '>';

	foreach ( $args['options'] as $option => $name ) {

		if ( ! $args['multiple'] ) {
			$selected = selected( $option, $value, false );
			$html    .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
		} else {
			// Do an in_array() check to output selected attribute for Multiple
			$html .= '<option value="' . esc_attr( $option ) . '" ' . ( ( in_array( $option, $value ) ) ? 'selected="true"' : '' ) . '>' . esc_html( $name ) . '</option>';
		}

	}

	$html .= '</select>';
	$html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Color select Callback
 *
 * Renders color select fields.
 *
 * @since 1.8
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_color_select_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	if ( $cs_option ) {
		$value = $cs_option;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$class = cs_sanitize_html_class( $args['field_class'] );
	if ( $args['chosen'] ) {
		$class .= 'cs-select-chosen';
		if ( is_rtl() ) {
			$class .= ' chosen-rtl';
		}
	}

	$html = '<select id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" class="' . $class . '" name="cs_settings[' . esc_attr( $args['id'] ) . ']"/>';

	foreach ( $args['options'] as $option => $color ) {
		$selected = selected( $option, $value, false );
		$html     .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $color['label'] ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Rich Editor Callback
 *
 * Renders rich editor fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 */
function cs_rich_editor_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	if ( $cs_option ) {
		$value = $cs_option;
	} else {
		if ( ! empty( $args['allow_blank'] ) && empty( $cs_option ) ) {
			$value = '';
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}
	}

	$rows = isset( $args['size'] ) ? $args['size'] : 20;

	$class = cs_sanitize_html_class( $args['field_class'] );

	ob_start();

	wp_editor( stripslashes( $value ), 'cs_settings_' . esc_attr( $args['id'] ), array(
		'textarea_name' => 'cs_settings[' . esc_attr( $args['id'] ) . ']',
		'textarea_rows' => absint( $rows ),
		'editor_class'  => $class,
	) );

	if ( ! empty( $args['desc'] ) ) {
		echo '<p class="cs-rich-editor-desc">' . wp_kses_post( $args['desc'] ) . '</p>';
	}

	$html = ob_get_clean();

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Upload Callback
 *
 * Renders upload fields.
 *
 * @since 1.0
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_upload_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	if ( $cs_option ) {
		$value = $cs_option;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$class = cs_sanitize_html_class( $args['field_class'] );

	$size  = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html  = '<input type="text" class="' . sanitize_html_class( $size ) . '-text" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" class="' . $class . '" name="cs_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<span>&nbsp;<input type="button" data-uploader-title="' . esc_html__( 'Attach File', 'commercestore' ) . '" data-uploader-button-text="' . esc_html__( 'Attach', 'commercestore' ) . '" class="cs_settings_upload_button button-secondary" value="' . __( 'Attach File', 'commercestore' ) . '"/></span>';
	$html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Color picker Callback
 *
 * Renders color picker fields.
 *
 * @since 1.6
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_color_callback( $args ) {
	$cs_option = cs_get_option( $args['id'] );

	if ( $cs_option ) {
		$value = $cs_option;
	} else {
		$value = isset( $args['std'] ) ? $args['std'] : '';
	}

	$default = isset( $args['std'] ) ? $args['std'] : '';

	$class = cs_sanitize_html_class( $args['field_class'] );

	$html  = '<input type="text" class="' . $class . ' cs-color-picker" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" name="cs_settings[' . esc_attr( $args['id'] ) . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
	$html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Shop States Callback
 *
 * Renders states drop down based on the currently selected country
 *
 * @since 1.6
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_shop_states_callback( $args ) {
	$cs_option  = cs_get_option( $args['id'] );
	$states      = cs_get_shop_states();
	$class       = cs_sanitize_html_class( $args['field_class'] );
	$placeholder = isset( $args['placeholder'] )
		? $args['placeholder']
		: '';

	if ( $args['chosen'] ) {
		$class .= ' cs-select-chosen';
		if ( is_rtl() ) {
			$class .= ' chosen-rtl';
		}
	}

	if ( empty( $states ) ) {
		$class .= ' cs-no-states';
	}

	$html = '<select id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" name="cs_settings[' . esc_attr( $args['id'] ) . ']" class="' . esc_attr( trim( $class ) ) . '" data-placeholder="' . esc_html( $placeholder ) . '">';

	foreach ( $states as $option => $name ) {
		$selected = isset( $cs_option ) ? selected( $option, $cs_option, false ) : '';
		$html     .= '<option value="' . esc_attr( $option ) . '" ' . $selected . '>' . esc_html( $name ) . '</option>';
	}

	$html .= '</select>';
	$html .= '<p class="description"> ' . wp_kses_post( $args['desc'] ) . '</p>';

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Outputs the "Default Rate" setting.
 *
 * @since 3.0
 *
 * @param array $args Arguments passed to the setting.
 */
function cs_tax_rate_callback( $args ) {
	echo '<input type="hidden" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" name="cs_settings[' . esc_attr( $args['id'] ) . ']" value="" />';
	echo wp_kses_post( $args['desc'] );
}

/**
 * Recapture Callback
 *
 * Renders Recapture Settings
 *
 * @since 2.10.2
 * @param array $args Arguments passed by the setting
 * @return void
 */
function cs_recapture_callback($args) {
	$client_connected = false;

	if ( class_exists( 'RecaptureCS' ) ) {
		$client_connected = RecaptureCS::is_ready();
	}

	ob_start();

	echo $args['desc'];

	// Output the appropriate button and label based on connection status
	if ( $client_connected ) :
		$connection_complete = get_option( 'recapture_api_key' );
		?>
		<div class="inline notice notice-<?php echo $connection_complete ? 'success' : 'warning'; ?>">
			<p>
				<?php _e( 'Recapture plugin activated.', 'commercestore' ); ?>
				<?php printf( __( '%sAccess your Recapture account%s.', 'commercestore' ), '<a href="https://recapture.io/account" target="_blank" rel="noopener noreferrer">', '</a>' ); ?>
			</p>

			<?php if ( $connection_complete ) : ?>
				<p>
					<a id="cs-recapture-disconnect" class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=recapture-confirm-disconnect' ) ); ?>"><?php esc_html_e( 'Disconnect Recapture', 'commercestore' ); ?></a>
				</p>
			<?php else : ?>
				<p>
					<?php printf( __( '%sComplete your connection to Recapture%s', 'commercestore' ), '<a href="' . admin_url( 'admin.php?page=recapture' ) . '">', '</a>' ); ?>
				</p>
			<?php endif; ?>
		</div>
	<?php
	else :
		?>
		<p>
			<?php _e( 'We recommend Recapture for recovering lost revenue by automatically sending effective, targeted emails to customers who abandon their shopping cart.', 'commercestore' ); ?> <?php printf( __( '%sLearn more%s (Free trial available)', 'commercestore' ), '<a href="https://recapture.io/abandoned-carts-commercestore" target="_blank" rel="noopener noreferrer">', '</a>' ); ?>
		</p>
		<?php if ( current_user_can( 'install_plugins' ) ) : ?>
		<p>
			<button type="button" id="cs-recapture-connect" class="button button-primary"><?php esc_html_e( 'Connect with Recapture', 'commercestore' ); ?>
			</button>
		</p>
	<?php endif; ?>

	<?php
	endif;

	echo ob_get_clean();
}

/**
 * Tax Rates Callback
 *
 * Renders tax rates table
 *
 * @since 1.6
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_tax_rates_callback( $args ) {
	$rates = cs_get_tax_rates( array(), OBJECT );

	wp_enqueue_script( 'cs-admin-tax-rates' );
	wp_enqueue_style( 'cs-admin-tax-rates' );

	wp_localize_script( 'cs-admin-tax-rates', 'csTaxRates', array(
		'rates' => $rates,
		'nonce' => wp_create_nonce( 'cs-country-field-nonce' ),
		'i18n'  => array(
			/* translators: Tax rate country code */
			'duplicateRate' => esc_html__( 'Duplicate tax rates are not allowed. Please deactivate the existing %s tax rate before adding or activating another.', 'commercestore' ),
			'emptyCountry'  => esc_html__( 'Please select a country.', 'commercestore' ),
			'emptyTax'      => esc_html__( 'Please enter a tax rate greater than 0.', 'commercestore' ),
		),
	) );

	$templates = array(
		'meta',
		'row',
		'row-empty',
		'add',
		'bulk-actions'
	);

	echo '<p>' . $args['desc'] . '</p>';

	echo '<div id="cs-admin-tax-rates"></div>';

	foreach ( $templates as $tmpl ) {
?>

<script type="text/html" id="tmpl-cs-admin-tax-rates-table-<?php echo esc_attr( $tmpl ); ?>">
	<?php require_once CS_PLUGIN_DIR . 'includes/admin/views/tmpl-tax-rates-table-' . $tmpl . '.php'; ?>
</script>

<?php
	}

}

/**
 * Descriptive text callback.
 *
 * Renders descriptive text onto the settings field.
 *
 * @since 2.1.3
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_descriptive_text_callback( $args ) {
	$html = wp_kses_post( $args['desc'] );

	echo apply_filters( 'cs_after_setting_output', $html, $args );
}

/**
 * Registers the license field callback for Software Licensing
 *
 * @since 1.5
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
if ( ! function_exists( 'cs_license_key_callback' ) ) {
	function cs_license_key_callback( $args ) {
		$cs_option = cs_get_option( $args['id'] );

		$messages = array();
		$license  = get_option( $args['options']['is_valid_license_option'] );

		if ( $cs_option ) {
			$value = $cs_option;
		} else {
			$value = isset( $args['std'] )
				? $args['std']
				: '';
		}

		if ( ! empty( $license ) && is_object( $license ) ) {
			$now        = current_time( 'timestamp' );
			$expiration = ! empty( $license->expires )
				? strtotime( $license->expires, $now )
				: false;

			// activate_license 'invalid' on anything other than valid, so if there was an error capture it
			if ( false === $license->success ) {

				switch ( $license->error ) {

					case 'expired' :
						$class      = 'expired';
						$messages[] = sprintf(
							__( 'Your license key expired on %s. Please <a href="%s" target="_blank">renew your license key</a>.', 'commercestore' ),
							cs_date_i18n( $expiration ),
							'https://commercestore.com/checkout/?cs_license_key=' . $value . '&utm_campaign=admin&utm_source=licenses&utm_medium=expired'
						);

						$license_status = 'license-' . $class . '-notice';

						break;

					case 'revoked' :
						$class      = 'error';
						$messages[] = sprintf(
							__( 'Your license key has been disabled. Please <a href="%s" target="_blank">contact support</a> for more information.', 'commercestore' ),
							'https://commercestore.com/support?utm_campaign=admin&utm_source=licenses&utm_medium=revoked'
						);

						$license_status = 'license-' . $class . '-notice';

						break;

					case 'missing' :
						$class      = 'error';
						$messages[] = sprintf(
							__( 'Invalid license. Please <a href="%s" target="_blank">visit your account page</a> and verify it.', 'commercestore' ),
							'https://commercestore.com/your-account?utm_campaign=admin&utm_source=licenses&utm_medium=missing'
						);

						$license_status = 'license-' . $class . '-notice';

						break;

					case 'invalid' :
					case 'site_inactive' :
						$class      = 'error';
						$messages[] = sprintf(
							__( 'Your %s is not active for this URL. Please <a href="%s" target="_blank">visit your account page</a> to manage your license key URLs.', 'commercestore' ),
							$args['name'],
							'https://commercestore.com/your-account?utm_campaign=admin&utm_source=licenses&utm_medium=invalid'
						);

						$license_status = 'license-' . $class . '-notice';

						break;

					case 'item_name_mismatch' :
						$class      = 'error';
						$messages[] = sprintf( __( 'This appears to be an invalid license key for %s.', 'commercestore' ), $args['name'] );

						$license_status = 'license-' . $class . '-notice';

						break;

					case 'no_activations_left':
						$class      = 'error';
						$messages[] = sprintf( __( 'Your license key has reached its activation limit. <a href="%s">View possible upgrades</a> now.', 'commercestore' ), 'https://commercestore.com/your-account/' );

						$license_status = 'license-' . $class . '-notice';

						break;

					case 'license_not_activable':
						$class      = 'error';
						$messages[] = __( 'The key you entered belongs to a bundle, please use the product specific license key.', 'commercestore' );

						$license_status = 'license-' . $class . '-notice';
						break;

					default :
						$class      = 'error';
						$error      = ! empty( $license->error ) ? $license->error : __( 'unknown_error', 'commercestore' );
						$messages[] = sprintf( __( 'There was an error with this license key: %s. Please <a href="%s">contact our support team</a>.', 'commercestore' ), $error, 'https://commercestore.com/support' );

						$license_status = 'license-' . $class . '-notice';
						break;
				}

			} else {

				switch ( $license->license ) {

					case 'valid' :
					default:

						$class = 'valid';

						if ( 'lifetime' === $license->expires ) {
							$messages[] = __( 'License key never expires.', 'commercestore' );

							$license_status = 'license-lifetime-notice';

						} elseif ( ( $expiration > $now ) && ( $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) ) {
							$messages[] = sprintf(
								__( 'Your license key expires soon! It expires on %s.', 'commercestore' ),
								date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) )
							);

							$license_status = 'license-expires-soon-notice';

						} else {
							$messages[] = sprintf(
								__( 'Your license key expires on %s.', 'commercestore' ),
								cs_date_i18n( $expiration )
							);

							$license_status = 'license-expiration-date-notice';
						}

						break;
				}
			}

		} else {
			$class = 'empty';

			$messages[] = sprintf(
				__( 'To receive updates, please enter your valid %s license key.', 'commercestore' ),
				$args['name']
			);

			$license_status = null;
		}

		$class .= ' ' . cs_sanitize_html_class( $args['field_class'] );

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="password" autocomplete="off" class="' . sanitize_html_class( $size ) . '-text" id="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" name="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']" value="' . esc_attr( $value ) . '"/>';

		if ( ( is_object( $license ) && ! empty( $license->license ) && 'valid' == $license->license ) || 'valid' == $license ) {
			$html .= '<input type="submit" class="button-secondary" name="' . $args['id'] . '_deactivate" value="' . __( 'Deactivate License', 'commercestore' ) . '"/>';
		}

		$html .= '<label for="cs_settings[' . cs_sanitize_key( $args['id'] ) . ']"> ' . wp_kses_post( $args['desc'] ) . '</label>';

		if ( ! empty( $messages ) ) {
			foreach ( $messages as $message ) {

				$html .= '<div class="cs-license-data cs-license-' . $class . ' ' . $license_status . '">';
				$html .= '<p>' . $message . '</p>';
				$html .= '</div>';

			}
		}

		wp_nonce_field( cs_sanitize_key( $args['id'] ) . '-nonce', cs_sanitize_key( $args['id'] ) . '-nonce' );

		echo $html;
	}
}

/**
 * Hook Callback
 *
 * Adds a do_action() hook in place of the field
 *
 * @since 1.0.8.2
 *
 * @param array $args Arguments passed by the setting
 *
 * @return void
 */
function cs_hook_callback( $args ) {
	do_action( 'cs_' . $args['id'], $args );
}

/**
 * Set manage_shop_settings as the cap required to save CommerceStore settings pages
 *
 * @since 1.9
 * @return string capability required
 */
function cs_set_settings_cap() {
	return 'manage_shop_settings';
}
add_filter( 'option_page_capability_cs_settings', 'cs_set_settings_cap' );

/**
 * Maybe attach a tooltip to a setting
 *
 * @since 1.9
 * @param string $html
 * @param type $args
 * @return string
 */
function cs_add_setting_tooltip( $html = '', $args = array() ) {

	// Tooltip has title & description
	if ( ! empty( $args['tooltip_title'] ) && ! empty( $args['tooltip_desc'] ) ) {
		$tooltip   = '<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<strong>' . esc_html( $args['tooltip_title'] ) . '</strong>: ' . esc_html( $args['tooltip_desc'] ) . '"></span>';
		$has_p_tag = strstr( $html, '</p>'     );
		$has_label = strstr( $html, '</label>' );

		// Insert tooltip at end of paragraph
		if ( false !== $has_p_tag ) {
			$html = str_replace( '</p>', $tooltip . '</p>', $html );

		// Insert tooltip at end of label
		} elseif ( false !== $has_label ) {
			$html = str_replace( '</label>', '</label>' . $tooltip, $html );

		// Append tooltip to end of HTML
		} else {
			$html .= $tooltip;
		}
	}

	return $html;
}
add_filter( 'cs_after_setting_output', 'cs_add_setting_tooltip', 10, 2 );
