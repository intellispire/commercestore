<?php
/**
 * Order User Interface Functions
 *
 * @package     CS
 * @subpackage  Orders
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add "Order" to the "+ New" admin menu bar.
 *
 * @since 3.0
 *
 * @param WP_Admin_Bar $wp_admin_bar Admin bar object.
 */
function cs_add_new_order_to_wp_admin_bar( $wp_admin_bar ) {
	// Bail if no admin bar
	if ( empty( $wp_admin_bar ) ) {
		return;
	}

	// Bail if incorrect user.
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	// Add the menu item
	$wp_admin_bar->add_menu( array(
		'id'     => 'new-order',
		'title'  => __( 'Order', 'commercestore' ),
		'parent' => 'new-content',
		'href'   => cs_get_admin_url( array(
			'page' => 'cs-payment-history',
			'view' => 'add-order',
		) ),
	) );
}
add_action( 'admin_bar_menu', 'cs_add_new_order_to_wp_admin_bar', 99 );
