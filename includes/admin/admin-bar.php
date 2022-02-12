<?php
/**
 * Admin Bar
 *
 * @package     CS
 * @subpackage  Admin/Bar
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Maybe add the store status to the WordPress admin bar
 *
 * @since 3.0
 */
function cs_maybe_add_store_mode_admin_bar_menu( $wp_admin_bar ) {

	// Bail if no admin bar
	if ( empty( $wp_admin_bar ) ) {
		return;
	}

	// Bail if user cannot manage shop settings
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	// String
	$text = ! cs_is_test_mode()
		? __( 'Live',      'commercestore' )
		: __( 'Test Mode', 'commercestore' );

	// Mode
	$mode = ! cs_is_test_mode()
		? 'live'
		: 'test';

	// Add the menu
    $wp_admin_bar->add_menu( array(
        'id'     => 'cs-store-menu',
        'title'  => sprintf( __( 'Store Status: %s', 'commercestore' ), '<span class="cs-mode cs-mode-' . esc_attr( $mode ) . '">' . $text . '</span>' ),
        'parent' => false,
        'href'   => cs_get_admin_url( array(
			'page' => 'cs-settings',
			'tab'  => 'gateways'
		) )
	) );

	// Is development environment?
	$is_dev = cs_is_dev_environment();
	if ( ! empty( $is_dev ) ) {
		$wp_admin_bar->add_menu( array(
			'id'     => 'cs-is-dev',
			'title'  => sprintf( __( 'Development Domain %s', 'commercestore' ), '<span class="cs-mode">' . $is_dev . '</span>' ),
			'parent' => 'cs-store-menu',
			'href'   => cs_get_admin_url( array(
				'page' => 'cs-settings',
				'tab'  => 'gateways'
			) )
		) );
	}
}
add_action( 'admin_bar_menu', 'cs_maybe_add_store_mode_admin_bar_menu', 9999 );

/**
 * Styling for text-mode button
 *
 * @since 3.0
 */
function cs_store_mode_admin_bar_print_link_styles() {

	// Bail if user cannot manage shop settings
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	} ?>

	<style type="text/css" id="cs-store-menu-styling">
		#wp-admin-bar-cs-store-menu .cs-mode {
			color: #fff;
			background-color: #0073aa;
			padding: 3px 7px;
			font-weight: 600;
			border-radius: 3px;
		}
		#wp-admin-bar-cs-store-menu .cs-mode-live {
			background-color: #32CD32;
		}
		#wp-admin-bar-cs-store-menu .cs-mode-test {
			background-color: #FF8C00;
		}
	</style>

<?php
}
add_action( 'wp_print_styles',    'cs_store_mode_admin_bar_print_link_styles' );
add_action( 'admin_print_styles', 'cs_store_mode_admin_bar_print_link_styles' );
