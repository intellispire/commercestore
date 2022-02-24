<?php
/**
 * Admin Pages
 *
 * @package     CS
 * @subpackage  Admin/Pages
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get the admin pages.
 *
 * This largely exists for back-compat in cs_is_admin_page(). Maybe eventually
 * we'll move away from globals for all of these, but who knows what add-ons are
 * doing, so we're keeping these around until we can formally deprecate them.
 *
 * @since 3.0
 *
 * @global $cs_discounts_page $cs_discounts_page
 * @global $cs_payments_page $cs_payments_page
 * @global $cs_settings_page $cs_settings_page
 * @global $cs_reports_page $cs_reports_page
 * @global type $cs_system_info_page
 * @global $cs_add_ons_page $cs_add_ons_page
 * @global $cs_settings_export $cs_settings_export
 * @global $cs_upgrades_screen $cs_upgrades_screen
 * @global $cs_customers_page $cs_customers_page
 * @global $cs_reports_page $cs_reports_page
 *
 * @return array
 */
function cs_get_admin_pages() {
	global  $cs_discounts_page,
			$cs_payments_page,
			$cs_settings_page,
			$cs_reports_page,
			$cs_system_info_page,
			$cs_add_ons_page,
			$cs_settings_export,
			$cs_upgrades_screen,
			$cs_customers_page,
			$cs_reports_page;

	// Filter & return
	return (array) apply_filters( 'cs_admin_pages', array(
		$cs_discounts_page,
		$cs_payments_page,
		$cs_settings_page,
		$cs_reports_page,
		$cs_system_info_page,
		$cs_add_ons_page,
		$cs_settings_export,
		$cs_upgrades_screen,
		$cs_customers_page,
		$cs_reports_page
	) );
}

/**
 * Creates the admin submenu pages under the Downloads menu and assigns their
 * links to global variables
 *
 * @since 1.0
 *
 * @global $cs_discounts_page
 * @global $cs_payments_page
 * @global $cs_customers_page
 * @global $cs_settings_page
 * @global $cs_reports_page
 * @global $cs_add_ons_page
 * @global $cs_settings_export
 * @global $cs_upgrades_screen
 */
function cs_add_options_link() {
	global $submenu, $cs_discounts_page, $cs_payments_page, $cs_settings_page, $cs_reports_page, $cs_upgrades_screen, $cs_tools_page, $cs_customers_page;

	// Filter the "View Customers" role
	$customer_view_role  = apply_filters( 'cs_view_customers_role', 'view_shop_reports' );

	// Setup pages
	$cs_payments_page   = add_submenu_page( 'edit.php?post_type=download', __( 'Orders',       'commercestore' ), __( 'Orders',    'commercestore' ), 'edit_shop_payments',    'cs-payment-history', 'cs_payment_history_page' );
	$cs_customers_page  = add_submenu_page( 'edit.php?post_type=download', __( 'Customers',    'commercestore' ), __( 'Customers', 'commercestore' ), $customer_view_role,     'cs-customers',       'cs_customers_page'       );
	$cs_discounts_page  = add_submenu_page( 'edit.php?post_type=download', __( 'Discounts',    'commercestore' ), __( 'Discounts', 'commercestore' ), 'manage_shop_discounts', 'cs-discounts',       'cs_discounts_page'       );
	$cs_reports_page    = add_submenu_page( 'edit.php?post_type=download', __( 'Reports',      'commercestore' ), __( 'Reports',   'commercestore' ), 'view_shop_reports',     'cs-reports',         'cs_reports_page'         );
	$cs_settings_page   = add_submenu_page( 'edit.php?post_type=download', __( 'CS Settings', 'commercestore' ), __( 'Settings',  'commercestore' ), 'manage_shop_settings',  'cs-settings',        'cs_options_page'         );
	$cs_tools_page      = add_submenu_page( 'edit.php?post_type=download', __( 'CS Tools',    'commercestore' ), __( 'Tools',     'commercestore' ), 'manage_shop_settings',  'cs-tools',           'cs_tools_page'           );

	// Setup hidden upgrades page
	$cs_upgrades_screen = add_submenu_page( null, __( 'CS Upgrades', 'commercestore' ), __( 'CS Upgrades', 'commercestore' ), 'manage_shop_settings', 'cs-upgrades', 'cs_upgrades_screen' );

	// Add our reports link in the main Dashboard menu.
	$submenu['index.php'][] = array(
		__( 'Store Reports', 'commercestore' ),
		'view_shop_reports',
		'edit.php?post_type=download&page=cs-reports',
	);
}
add_action( 'admin_menu', 'cs_add_options_link', 10 );

/**
 * Create the Extensions submenu page under the "Downloads" menu
 *
 * @since 3.0
 *
 * @global $cs_add_ons_page
 */
function cs_add_extentions_link() {
	global $cs_add_ons_page;

	$cs_add_ons_page = add_submenu_page( 'edit.php?post_type=download', __( 'CS Extensions', 'commercestore' ), __( 'Extensions', 'commercestore' ), 'manage_shop_settings', 'cs-addons', 'cs_add_ons_page' );
}
add_action( 'admin_menu', 'cs_add_extentions_link', 99999 );

/**
 * Whether the current admin area page is one that allows the insertion of a
 * button to make inserting Downloads easier.
 *
 * @since 3.0
 * @global $pagenow $pagenow
 * @global $typenow $typenow
 * @return boolean
 */
function cs_is_insertable_admin_page() {
	global $pagenow, $typenow;

	// Allowed pages
	$pages = array(
		'post.php',
		'page.php',
		'post-new.php',
		'post-edit.php'
	);

	// Allowed post types
	$types = get_post_types_by_support( 'cs_insert_download' );

	// Return if page and type are allowed
	return in_array( $pagenow, $pages, true ) && in_array( $typenow, $types, true );
}

/**
 * Determines whether the current admin page is a specific CommerceStore admin page.
 *
 * Only works after the `wp_loaded` hook, & most effective
 * starting on `admin_menu` hook. Failure to pass in $view will match all views of $passed_page.
 * Failure to pass in $passed_page will return true if on any CommerceStore page
 *
 * @since 1.9.6
 * @since 2.11.3 Added `$include_non_exclusive` parameter.
 *
 * @param string $passed_page           Optional. Main page's slug.
 * @param string $passed_view           Optional. Page view ( ex: `edit` or `delete` )
 * @param bool   $include_non_exclusive Optional. If we should consider pages not exclusive to CS.
 *                                      Includes the main dashboard page and custom post types that
 *                                      support the "Insert Download" button via the TinyMCE editor.
 *
 * @return bool True if CommerceStore admin page we're looking for or an CommerceStore page or if $page is empty, any CommerceStore page
 */
function cs_is_admin_page( $passed_page = '', $passed_view = '', $include_non_exclusive = true ) {
	global $pagenow, $typenow;

	$found      = false;
	$post_type  = isset( $_GET['post_type'] )  ? strtolower( $_GET['post_type'] )  : false;
	$action     = isset( $_GET['action'] )     ? strtolower( $_GET['action'] )     : false;
	$taxonomy   = isset( $_GET['taxonomy'] )   ? strtolower( $_GET['taxonomy'] )   : false;
	$page       = isset( $_GET['page'] )       ? strtolower( $_GET['page'] )       : false;
	$view       = isset( $_GET['view'] )       ? strtolower( $_GET['view'] )       : false;
	$cs_action = isset( $_GET['cs-action'] ) ? strtolower( $_GET['cs-action'] ) : false;
	$tab        = isset( $_GET['tab'] )        ? strtolower( $_GET['tab'] )        : false;

	switch ( $passed_page ) {
		case 'download':
			switch ( $passed_view ) {
				case 'list-table':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' ) {
						$found = true;
					}
					break;
				case 'edit':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'post.php' ) {
						$found = true;
					}
					break;
				case 'new':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'post-new.php' ) {
						$found = true;
					}
					break;
				default:
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) || CS_POST_TYPE === $post_type || ( 'post-new.php' === $pagenow && CS_POST_TYPE === $post_type ) ) {
						$found = true;
					}
					break;
			}
			break;
		case 'categories':
			switch ( $passed_view ) {
				case 'list-table':
				case 'new':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit-tags.php' && 'edit' !== $action && CS_CAT_TYPE === $taxonomy ) {
						$found = true;
					}
					break;
				case 'edit':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit-tags.php' && 'edit' === $action && CS_CAT_TYPE === $taxonomy ) {
						$found = true;
					}
					break;
				default:
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit-tags.php' && CS_CAT_TYPE === $taxonomy ) {
						$found = true;
					}
					break;
			}
			break;
		case 'tags':
			switch ( $passed_view ) {
				case 'list-table':
				case 'new':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit-tags.php' && 'edit' !== $action && 'download_tax' === $taxonomy ) {
						$found = true;
					}
					break;
				case 'edit':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit-tags.php' && 'edit' === $action && 'download_tax' === $taxonomy ) {
						$found = true;
					}
					break;
				default:
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit-tags.php' && 'download_tax' === $taxonomy ) {
						$found = true;
					}
					break;
			}
			break;
		case 'payments':
			switch ( $passed_view ) {
				case 'list-table':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-payment-history' === $page && false === $view  ) {
						$found = true;
					}
					break;
				case 'edit':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-payment-history' === $page && 'view-order-details' === $view ) {
						$found = true;
					}
					break;
				default:
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-payment-history' === $page ) {
						$found = true;
					}
					break;
			}
			break;
		case 'discounts':
			switch ( $passed_view ) {
				case 'list-table':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-discounts' === $page && false === $cs_action ) {
						$found = true;
					}
					break;
				case 'edit':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-discounts' === $page && 'edit_discount' === $cs_action ) {
						$found = true;
					}
					break;
				case 'new':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-discounts' === $page && 'add_discount' === $cs_action ) {
						$found = true;
					}
					break;
				default:
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-discounts' === $page ) {
						$found = true;
					}
					break;
			}
			break;
		case 'reports':
			switch ( $passed_view ) {
				// If you want to do something like enqueue a script on a particular report's duration, look at $_GET[ 'range' ]
				case 'earnings':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-reports' === $page && ( 'earnings' === $view || '-1' === $view || false === $view ) ) {
						$found = true;
					}
					break;
				case 'downloads':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-reports' === $page && 'downloads' === $view ) {
						$found = true;
					}
					break;
				case 'customers':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-reports' === $page && 'customers' === $view ) {
						$found = true;
					}
					break;
				case 'gateways':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-reports' === $page && 'gateways' === $view ) {
						$found = true;
					}
					break;
				case 'taxes':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-reports' === $page && 'taxes' === $view ) {
						$found = true;
					}
					break;
				case 'export':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-reports' === $page && 'export' === $view ) {
						$found = true;
					}
					break;
				default:
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-reports' === $page ) {
						$found = true;
					}
					break;
			}
			break;
		case 'settings':
			switch ( $passed_view ) {
				case 'general':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-settings' === $page && ( 'general' === $tab || false === $tab ) ) {
						$found = true;
					}
					break;
				case 'gateways':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-settings' === $page && 'gateways' === $tab ) {
						$found = true;
					}
					break;
				case 'emails':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-settings' === $page && 'emails' === $tab ) {
						$found = true;
					}
					break;
				case 'styles':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-settings' === $page && 'styles' === $tab ) {
						$found = true;
					}
					break;
				case 'taxes':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-settings' === $page && 'taxes' === $tab ) {
						$found = true;
					}
					break;
				case 'extensions':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-settings' === $page && 'extensions' === $tab ) {
						$found = true;
					}
					break;
				case 'licenses':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-settings' === $page && 'licenses' === $tab ) {
						$found = true;
					}
					break;
				case 'misc':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-settings' === $page && 'misc' === $tab ) {
						$found = true;
					}
					break;
				case 'marketing':
					if ( ( CS_POST_TYPE == $typenow || CS_POST_TYPE === $post_type ) && $pagenow == 'edit.php' && 'cs-settings' === $page && 'marketing' === $tab ) {
						$found = true;
					}
					break;
				default:
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-settings' === $page ) {
						$found = true;
					}
					break;
			}
			break;
		case 'tools':
			switch ( $passed_view ) {
				case 'general':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-tools' === $page && ( 'general' === $tab || false === $tab ) ) {
						$found = true;
					}
					break;
				case 'api_keys':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-tools' === $page && 'api_keys' === $tab ) {
						$found = true;
					}
					break;
				case 'system_info':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-tools' === $page && 'system_info' === $tab ) {
						$found = true;
					}
					break;
				case 'logs':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-tools' === $page && 'logs' === $tab ) {
						$found = true;
					}
					break;
				case 'import_export':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-tools' === $page && 'import_export' === $tab ) {
						$found = true;
					}
					break;
				default:
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-tools' === $page ) {
						$found = true;
					}
					break;
			}
			break;
		case 'addons':
			if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-addons' === $page ) {
				$found = true;
			}
			break;
		case 'customers':
			switch ( $passed_view ) {
				case 'list-table':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-customers' === $page && false === $view ) {
						$found = true;
					}
					break;
				case 'overview':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-customers' === $page && 'overview' === $view ) {
						$found = true;
					}
					break;
				case 'notes':
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-customers' === $page && 'notes' === $view ) {
						$found = true;
					}
					break;
				default:
					if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-customers' === $page ) {
						$found = true;
					}
					break;
			}
			break;
		case 'reports':
			if ( ( CS_POST_TYPE === $typenow || CS_POST_TYPE === $post_type ) && $pagenow === 'edit.php' && 'cs-reports' === $page ) {
				$found = true;
			}
			break;
		case 'index.php' :
			if ( 'index.php' === $pagenow ) {
				$found = true;
			}
			break;

		default:
			$admin_pages = cs_get_admin_pages();

			// Downloads sub-page or Dashboard page
			if ( ( CS_POST_TYPE === $typenow ) || ( $include_non_exclusive && 'index.php' === $pagenow ) ) {
				$found = true;

			// Registered global pages
			} elseif ( in_array( $pagenow, $admin_pages, true ) ) {
				$found = true;

			// Supported post types
			} elseif ( $include_non_exclusive && cs_is_insertable_admin_page() ) {
				$found = true;

			// The CommerceStore settings screen (fallback if mislinked)
			} elseif ( 'cs-settings' === $page ) {
				$found = true;
			}
			break;
	}

	return (bool) apply_filters( 'cs_is_admin_page', $found, $page, $view, $passed_page, $passed_view );
}

/**
 * Forces the Cache-Control header on our admin pages to send the no-store header
 * which prevents the back-forward cache (bfcache) from storing a copy of this page in local
 * cache. This helps make sure that page elements modified via AJAX and DOM manipulations aren't
 * incorrectly shown as if they never changed.
 *
 * @since 3.0
 * @param array $headers An array of nocache headers.
 *
 * @return array
 */
function _cs_bfcache_buster( $headers ) {
	if ( ! is_admin() & ! cs_is_admin_page() ) {
		return $headers;
	}

	$headers['Cache-Control'] = 'no-cache, must-revalidate, max-age=0, no-store';

	return $headers;
}
add_filter( 'nocache_headers', '_cs_bfcache_buster', 10, 1 );
