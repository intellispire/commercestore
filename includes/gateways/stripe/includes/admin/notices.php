<?php
/**
 * Bootstraps and outputs notices.
 *
 * @package CS_Stripe
 * @since   2.6.19
 */

/**
 * Registers scripts to manage dismissing notices.
 *
 * @since 2.6.19
 */
function csx_admin_notices_scripts() {
	wp_register_script(
		'csx-admin-notices',
		CSSTRIPE_PLUGIN_URL . 'assets/js/build/notices.min.js',
		array(
			'wp-util',
		)
	);
}
add_action( 'admin_enqueue_scripts', 'csx_admin_notices_scripts' );

/**
 * Registers admin notices.
 *
 * @since 2.6.19
 *
 * @return true|WP_Error True if all notices are registered, otherwise WP_Error.
 */
function csx_admin_notices_register() {
	$registry = csx_get_registry( 'admin-notices' );

	if ( ! $registry ) {
		return new WP_Error( 'csx-invalid-registry', esc_html__( 'Unable to locate registry', 'cs-stripe' ) );
	}

	try {
		// Stripe Connect.
		$registry->add(
			'stripe-connect',
			array(
				'message'     => '<p>' . wp_kses(
					sprintf(
						/* translators: %1$s Opening anchor tag, do not translate. %2$s Closing anchor tag, do not translate. */
						__( 'The Stripe extension for Easy Digital Downloads supports Stripe Connect for easier setup and improved security. %1$sClick here%2$s to learn more about connecting your Stripe account.', 'csx' ),
						'<a href="' . esc_url( admin_url( 'edit.php?post_type=download&page=cs-settings&tab=gateways&section=cs-stripe' ) ) . '">',
						'</a>'
					),
					array(
						'a' => array(
							'href' => true,
						),
					)
				) . '</p>',
				'type'        => 'info',
				'dismissible' => true,
			)
		);

		// Upcoming PHP requirement change.
		$registry->add(
			'php-56-requirement',
			array(
				'message'     => function() {
					ob_start();
					require_once CSS_PLUGIN_DIR . '/includes/admin/notices/php-56-requirement.php';
					return ob_get_clean();
				},
				'type'        => 'error',
				'dismissible' => false,
			)
		);

		// Recurring 2.9.0 requirement.
		$registry->add(
			'recurring-290-requirement',
			array(
				'message'     => '<p>' . wp_kses(
					sprintf(
						/* translators: %1$s Opening strong tag, do not translate. %2$s Closing strong tag, do not translate. */
						__( '%1$sCredit card payments with Stripe are currently disabled.%2$s', 'csx' ),
						'<strong>',
						'</strong>'
					) 
					. '<br />' .
					sprintf(
						/* translators: %1$s Opening code tag, do not translate. %2$s Closing code tag, do not translate. */
					 	__( 'To continue accepting credit card payments with Stripe please update the Recurring Payments extension to version %1$s2.9%2$s.', 'csx' ),
						'<code>',
						'</code>'
					),
					array(
						'br'     => true,
						'strong' => true,
						'code'   => true,
					)
				) . '</p>',
				'type'        => 'error',
				'dismissible' => false,
			)
		);
	} catch( Exception $e ) {
		return new WP_Error( 'csx-invalid-notices-registration', esc_html__( $e->getMessage() ) );
	};

	return true;
}
add_action( 'admin_init', 'csx_admin_notices_register' );

/**
 * Conditionally prints registered notices.
 *
 * @since 2.6.19
 */
function csx_admin_notices_print() {
	// Current user needs capability to dismiss notices.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$registry = csx_get_registry( 'admin-notices' );

	if ( ! $registry ) {
		return;
	}

	$notices = new CS_Stripe_Admin_Notices( $registry );

	wp_enqueue_script( 'csx-admin-notices' );

	try {
		// Stripe Connect.
		$enabled_gateways          = cs_get_enabled_payment_gateways();
		$stripe_connect_account_id = cs_get_option( 'stripe_connect_account_id' );

		if ( array_key_exists( 'stripe', $enabled_gateways ) && empty( $stripe_connect_account_id ) ) {
			$notices->output( 'stripe-connect' );
		}

		// Upcoming PHP requirement change.
		if ( version_compare( phpversion(), '5.6', '<' ) ) {
			$notices->output( 'php-56-requirement' );
		}

		// Recurring 2.9.0 requirement.
		if ( defined( 'CS_RECURRING_VERSION' ) && ! version_compare( CS_RECURRING_VERSION, '2.8.8', '>' ) ) {
			$notices->output( 'recurring-290-requirement' );
		}
	} catch( Exception $e ) {}
}
add_action( 'admin_notices', 'csx_admin_notices_print' );

/**
 * Handles AJAX dismissal of notices.
 *
 * WordPress automatically removes the notices, so the response here is arbitrary.
 * If the notice cannot be dismissed it will simply reappear when the page is refreshed.
 *
 * @since 2.6.19
 */
function csx_admin_notices_dismiss_ajax() {
	$notice_id = isset( $_REQUEST[ 'id' ] ) ? esc_attr( $_REQUEST['id'] ) : false;
	$nonce     = isset( $_REQUEST[ 'nonce' ] ) ? esc_attr( $_REQUEST['nonce'] ) : false;

	if ( ! ( $notice_id && $nonce ) ) {
		return wp_send_json_error();
	}

	if ( ! wp_verify_nonce( $nonce, "csx-dismiss-{$notice_id}-nonce" ) ) {
		return wp_send_json_error();
	}

	$registry = csx_get_registry( 'admin-notices' );

	if ( ! $registry ) {
		return wp_send_json_error();
	}

	$notices   = new CS_Stripe_Admin_Notices( $registry );
	$dismissed = $notices->dismiss( $notice_id );

	if ( true === $dismissed ) {
		return wp_send_json_success();
	} else {
		return wp_send_json_error();
	}
}
add_action( 'wp_ajax_csx_admin_notices_dismiss_ajax', 'csx_admin_notices_dismiss_ajax' );
