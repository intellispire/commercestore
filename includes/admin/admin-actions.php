<?php
/**
 * Admin Actions
 *
 * @package     CS
 * @subpackage  Admin/Actions
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Processes all CommerceStore actions sent via POST and GET by looking for the 'cs-action'
 * request and running do_action() to call the function
 *
 * @since 1.0
 * @return void
 */
function cs_process_actions() {
	if ( isset( $_POST['cs-action'] ) ) {
		do_action( 'cs_' . $_POST['cs-action'], $_POST );
	}

	if ( isset( $_GET['cs-action'] ) ) {
		do_action( 'cs_' . $_GET['cs-action'], $_GET );
	}
}
add_action( 'admin_init', 'cs_process_actions' );

/**
 * When the Download list table loads, call the function to view our tabs.
 *
 * @since 2.8.9
 * @since 2.11.3 Unhooked this to revert to standard admin H1 tags.
 * @since 3.0    Added back as download categories/tags have been removed from the admin menu.
 * @param $views
 *
 * @return mixed
 */
function cs_products_tabs( $views ) {
	cs_display_product_tabs();

	return $views;
}
add_filter( 'views_edit-download', 'cs_products_tabs', 10, 1 );

/**
 * When the Download list table loads, call the function to view our tabs.
 *
 * @since 3.0
 *
 * @return void
 */
function cs_taxonomies_tabs() {

	// Bail if not viewing a taxonomy
	if ( empty( $_GET['taxonomy'] ) ) {
		return;
	}

	// Get taxonomies
	$taxonomy   = sanitize_key( $_GET['taxonomy'] );
	$taxonomies = get_object_taxonomies( CS_POST_TYPE );

	// Bail if current taxonomy is not a download taxonomy
	if ( ! in_array( $taxonomy, $taxonomies, true ) ) {
		return;
	}

	// Output the tabs
	cs_display_product_tabs();
}
add_action( 'admin_notices', 'cs_taxonomies_tabs', 10, 1 );

/**
 * Remove the top level taxonomy submenus.
 *
 * Since 3.0, these links were moved to horizontal tabs.
 *
 * @since 3.0
 */
function cs_admin_adjust_submenus() {

	// Get taxonomies
	$taxonomies = get_object_taxonomies( CS_POST_TYPE );

	// Bail if no taxonomies
	if ( empty( $taxonomies ) ) {
		return;
	}

	// Loop through each taxonomy and remove the menu
	foreach ( $taxonomies as $taxonomy ) {
		remove_submenu_page( 'edit.php?post_type=' . CS_POST_TYPE, 'edit-tags.php?taxonomy=' . $taxonomy . '&amp;post_type=' . CS_POST_TYPE );
	}

	// Remove the "Add New" link for downloads
	remove_submenu_page( 'edit.php?post_type=' . CS_POST_TYPE, 'post-new.php?post_type=' . CS_POST_TYPE);
}
add_action( 'admin_menu', 'cs_admin_adjust_submenus', 999 );

/**
 * This tells WordPress to highlight the Downloads > Downloads submenu,
 * regardless of which actual Downloads Taxonomy screen we are on.
 *
 * The conditional prevents the override when the user is viewing settings or
 * any third-party plugins.
 *
 * @since 3.0.0
 *
 * @global string $submenu_file
 */
function cs_taxonomies_modify_menu_highlight() {
	global $submenu_file;

	// Bail if not viewing a taxonomy
	if ( empty( $_GET['taxonomy'] ) ) {
		return;
	}

	// Get taxonomies
	$taxonomy   = sanitize_key( $_GET['taxonomy'] );
	$taxonomies = get_object_taxonomies( CS_POST_TYPE );

	// Bail if current taxonomy is not a download taxonomy
	if ( ! in_array( $taxonomy, $taxonomies, true ) ) {
		return;
	}

	// Force the submenu file
	$submenu_file = 'edit.php?post_type=' . CS_POST_TYPE;
}
add_filter( 'admin_head', 'cs_taxonomies_modify_menu_highlight', 9999 );

/**
 * This tells WordPress to highlight the Downloads > Downloads submenu when
 * adding a new product.
 *
 * @since 3.0.0
 *
 * @global string $submenu_file
 */
function cs_add_new_modify_menu_highlight() {
	global $submenu_file, $pagenow;

	// Bail if not viewing the right page or post type
	if ( empty( $_GET['post_type'] ) || ( 'post-new.php' !== $pagenow ) ) {
		return;
	}

	// Get post_type
	$post_type = sanitize_key( $_GET['post_type'] );

	// Bail if current post type is not ours
	if ( CS_POST_TYPE !== $post_type ) {
		return;
	}

	// Force the submenu file
	$submenu_file = 'edit.php?post_type=' . CS_POST_TYPE;
}
add_filter( 'admin_head', 'cs_add_new_modify_menu_highlight', 9999 );

/**
 * Displays the product tabs for Products, Categories, and Tags
 *
 * @since 2.8.9
 */
function cs_display_product_tabs() {

	// Initial tabs
	$tabs = array(
		'products' => array(
			'name' => cs_get_label_plural(),
			'url'  => cs_get_admin_url(),
		),
	);

	// Get taxonomies
	$taxonomies = get_object_taxonomies( CS_POST_TYPE, 'objects' );
	foreach ( $taxonomies as $tax => $details ) {
		$tabs[ $tax ] = array(
			'name' => $details->labels->menu_name,
			'url'  => add_query_arg( array(
				'taxonomy'  => $tax,
				'post_type' => CS_POST_TYPE
			), admin_url( 'edit-tags.php' ) )
		);
	}

	// Filter the tabs
	$tabs = apply_filters( 'cs_add_ons_tabs', $tabs );

	// Taxonomies
	if ( isset( $_GET['taxonomy'] ) && in_array( $_GET['taxonomy'], array_keys( $taxonomies ), true ) ) {
		$active_tab = $_GET['taxonomy'];

	// Default to Products
	} else {
		$active_tab = 'products';
	}

	// Start a buffer
	ob_start();
	?>

	<nav class="nav-tab-wrapper wp-clearfix" aria-label="<?php esc_attr_e( 'Secondary menu', 'commercestore' ); ?>">
		<?php

		foreach ( $tabs as $tab_id => $tab ) {
			$class = 'nav-tab';
			if ( $active_tab === $tab_id ) {
				$class .= ' nav-tab-active';
			}
			printf(
				'<a href="%s" class="%s">%s</a>',
				esc_url( $tab['url'] ),
				esc_attr( $class ),
				esc_html( $tab['name'] )
			);
		} ?>

	</nav>
	<br />

	<?php

	// Output the current buffer
	echo ob_get_clean();
}

/**
 * Return array of query arguments that should be removed from URLs.
 *
 * @since 3.0
 *
 * @return array
 */
function cs_admin_removable_query_args() {
	return apply_filters( 'cs_admin_removable_query_args', array(
		'cs-action',
		'cs-notice',
		'cs-message',
		'cs-redirect'
	) );
}

/**
 * Output payment icons into the admin footer.
 *
 * Specifically on the "General" tab of the "Payment Gateways" admin page.
 *
 * @since 3.0
 */
function cs_admin_print_payment_icons() {

	// Bail if not the gateways page
	if ( ! cs_is_admin_page( 'settings', 'gateways' ) ) {
		return;
	}

	// Output the SVG icons
	cs_print_payment_icons( array(
		'mastercard',
		'visa',
		'americanexpress',
		'discover',
		'paypal',
		'amazon'
	) );
}
add_action( 'admin_footer', 'cs_admin_print_payment_icons', 9999 );
