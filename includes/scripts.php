<?php
/**
 * Scripts
 *
 * @package     CS
 * @subpackage  Functions
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Front End *****************************************************************/

/**
 * Register all front-end scripts
 *
 * @since 3.0
 */
function cs_register_scripts() {
	$js_dir = CS_PLUGIN_URL . 'assets/js/';

	// Use minified libraries not debugging scripts
	$version   = cs_admin_get_script_version();
	$in_footer = cs_scripts_in_footer();
	$deps      = array( 'jquery' );

	wp_register_script( 'creditCardValidator', $js_dir . 'vendor/jquery.creditcardvalidator.min.js', $deps, $version, $in_footer );

	// Registered so gateways can enqueue it when they support the space formatting. wp_enqueue_script( 'jQuery.payment' );
	wp_register_script( 'jQuery.payment',      $js_dir . 'vendor/jquery.payment.min.js', $deps, $version, $in_footer );
	wp_register_script( 'cs-checkout-global', $js_dir . 'cs-checkout-global.js',       $deps, $version, $in_footer );
	wp_register_script( 'cs-ajax',            $js_dir . 'cs-ajax.js',                  $deps, $version, $in_footer );
}
add_action( 'init', 'cs_register_scripts' );

/**
 * Register styles
 *
 * Checks the styles option and hooks the required filter.
 *
 * @since 1.0
 */
function cs_register_styles() {

	// Bail if styles are disabled
	if ( cs_get_option( 'disable_styles', false ) ) {
		return;
	}

	// Use minified libraries not debugging scripts
	$suffix  = cs_doing_script_debug() ? '' : '.min';
	$version = cs_admin_get_script_version();

	$file          = 'cs' . $suffix . '.css';
	$templates_dir = cs_get_theme_template_dir_name();

	$child_theme_style_sheet    = trailingslashit( get_stylesheet_directory() ) . $templates_dir . $file;
	$child_theme_style_sheet_2  = trailingslashit( get_stylesheet_directory() ) . $templates_dir . 'cs.css';
	$parent_theme_style_sheet   = trailingslashit( get_template_directory()   ) . $templates_dir . $file;
	$parent_theme_style_sheet_2 = trailingslashit( get_template_directory()   ) . $templates_dir . 'cs.css';
	$cs_plugin_style_sheet     = trailingslashit( cs_get_templates_dir()    ) . $file;

	// Look in the child theme directory first, followed by the parent theme, followed by the CommerceStore core templates directory
	// Also look for the min version first, followed by non minified version, even if SCRIPT_DEBUG is not enabled.
	// This allows users to copy just cs.css to their theme
	if ( file_exists( $child_theme_style_sheet ) || ( ! empty( $suffix ) && ( $nonmin = file_exists( $child_theme_style_sheet_2 ) ) ) ) {
		if( ! empty( $nonmin ) ) {
			$url = trailingslashit( get_stylesheet_directory_uri() ) . $templates_dir . 'cs.css';
		} else {
			$url = trailingslashit( get_stylesheet_directory_uri() ) . $templates_dir . $file;
		}
	} elseif ( file_exists( $parent_theme_style_sheet ) || ( ! empty( $suffix ) && ( $nonmin = file_exists( $parent_theme_style_sheet_2 ) ) ) ) {
		if( ! empty( $nonmin ) ) {
			$url = trailingslashit( get_template_directory_uri() ) . $templates_dir . 'cs.css';
		} else {
			$url = trailingslashit( get_template_directory_uri() ) . $templates_dir . $file;
		}
	} elseif ( file_exists( $cs_plugin_style_sheet ) || file_exists( $cs_plugin_style_sheet ) ) {
		$url = trailingslashit( cs_get_templates_url() ) . $file;
	}

	wp_register_style( 'cs-styles', $url, array(), $version, 'all' );
}
add_action( 'init', 'cs_register_styles' );

/**
 * Load Scripts
 *
 * Enqueues the required scripts.
 *
 * @since 1.0
 * @since 3.0 calls cs_enqueue_scripts()
 */
function cs_load_scripts() {
	cs_enqueue_scripts();
	cs_localize_scripts();
}
add_action( 'wp_enqueue_scripts', 'cs_load_scripts' );

/**
 * Load Scripts
 *
 * Enqueues the required scripts.
 *
 * @since 3.0
 */
function cs_enqueue_scripts() {

	// Checkout scripts
	if ( cs_is_checkout() ) {

		// Enqueue credit-card validator
		if ( cs_is_cc_verify_enabled() ) {
			wp_enqueue_script( 'creditCardValidator' );
		}

		// Enqueue global checkout
		wp_enqueue_script( 'cs-checkout-global' );
	}

	// AJAX scripts, if enabled
	if ( ! cs_is_ajax_disabled() ) {
		wp_enqueue_script( 'cs-ajax' );
	}
}

/**
 * Enqueue styles
 *
 * Checks the styles option and hooks the required filter.
 *
 * @since 3.0
 */
function cs_enqueue_styles() {
	wp_enqueue_style( 'cs-styles' );
}
add_action( 'wp_enqueue_scripts', 'cs_enqueue_styles' );

/**
 * Localize scripts
 *
 * @since 3.0
 *
 * @global $post $post
 */
function cs_localize_scripts() {
	global $post;

	$version = cs_admin_get_script_version();

	if ( cs_is_checkout() ) {
		wp_localize_script( 'cs-checkout-global', 'cs_global_vars', apply_filters( 'cs_global_checkout_script_vars', array(
			'ajaxurl'               => cs_get_ajax_url(),
			'checkout_nonce'        => wp_create_nonce( 'cs_checkout_nonce' ),
			'checkout_error_anchor' => '#cs_purchase_submit',
			'currency_sign'         => cs_currency_filter(''),
			'currency_pos'          => cs_get_option( 'currency_position', 'before' ),
			'decimal_separator'     => cs_get_option( 'decimal_separator', '.' ),
			'thousands_separator'   => cs_get_option( 'thousands_separator', ',' ),
			'no_gateway'            => __( 'Please select a payment method', 'commercestore' ),
			'no_discount'           => __( 'Please enter a discount code', 'commercestore' ), // Blank discount code message
			'enter_discount'        => __( 'Enter discount', 'commercestore' ),
			'discount_applied'      => __( 'Discount Applied', 'commercestore' ), // Discount verified message
			'no_email'              => __( 'Please enter an email address before applying a discount code', 'commercestore' ),
			'no_username'           => __( 'Please enter a username before applying a discount code', 'commercestore' ),
			'purchase_loading'      => __( 'Please Wait...', 'commercestore' ),
			'complete_purchase'     => cs_get_checkout_button_purchase_label(),
			'taxes_enabled'         => cs_use_taxes() ? '1' : '0',
			'cs_version'           => $version
		) ) );
	}

	// Load AJAX scripts, if enabled
	if ( ! cs_is_ajax_disabled() ) {

		// Get position in cart of current download
		$position = isset( $post->ID )
			? cs_get_item_position_in_cart( $post->ID )
			: -1;

		if ( ( ! empty( $post->post_content ) && ( has_shortcode( $post->post_content, 'purchase_link' ) || has_shortcode( $post->post_content, 'downloads' ) ) ) || is_post_type_archive( CS_POST_TYPE ) ) {
			$has_purchase_links = true;
		} else {
			$has_purchase_links = false;
		}

		wp_localize_script( 'cs-ajax', 'cs_scripts', apply_filters( 'cs_ajax_script_vars', array(
			'ajaxurl'                 => cs_get_ajax_url(),
			'position_in_cart'        => $position,
			'has_purchase_links'      => $has_purchase_links,
			'already_in_cart_message' => __('You have already added this item to your cart','commercestore' ), // Item already in the cart message
			'empty_cart_message'      => __('Your cart is empty','commercestore' ), // Item already in the cart message
			'loading'                 => __('Loading','commercestore' ) , // General loading message
			'select_option'           => __('Please select an option','commercestore' ) , // Variable pricing error with multi-purchase option enabled
			'is_checkout'             => cs_is_checkout() ? '1' : '0',
			'default_gateway'         => cs_get_default_gateway(),
			'redirect_to_checkout'    => ( cs_straight_to_checkout() || cs_is_checkout() ) ? '1' : '0',
			'checkout_page'           => cs_get_checkout_uri(),
			'permalinks'              => get_option( 'permalink_structure' ) ? '1' : '0',
			'quantities_enabled'      => cs_item_quantities_enabled(),
			'taxes_enabled'           => cs_use_taxes() ? '1' : '0', // Adding here for widget, but leaving in checkout vars for backcompat
		) ) );
	}
}

/**
 * Load head styles
 *
 * Ensures download styling is still shown correctly if a theme is using the CSS template file
 *
 * @since 2.5
 * @global $post
 */
function cs_load_head_styles() {
	global $post;

	// Bail if styles are disabled
	if ( cs_get_option( 'disable_styles', false ) || ! is_object( $post ) ) {
		return;
	}

	// Use minified libraries not debugging scripts
	$suffix  = is_rtl() ? '-rtl' : '';
	$suffix .= cs_doing_script_debug() ? '' : '.min';

	$file          = 'cs' . $suffix . '.css';
	$templates_dir = cs_get_theme_template_dir_name();

	$child_theme_style_sheet    = trailingslashit( get_stylesheet_directory() ) . $templates_dir . $file;
	$child_theme_style_sheet_2  = trailingslashit( get_stylesheet_directory() ) . $templates_dir . 'cs.css';
	$parent_theme_style_sheet   = trailingslashit( get_template_directory()   ) . $templates_dir . $file;
	$parent_theme_style_sheet_2 = trailingslashit( get_template_directory()   ) . $templates_dir . 'cs.css';

	if ( has_shortcode( $post->post_content, 'downloads' ) &&
		file_exists( $child_theme_style_sheet    ) ||
		file_exists( $child_theme_style_sheet_2  ) ||
		file_exists( $parent_theme_style_sheet   ) ||
		file_exists( $parent_theme_style_sheet_2 )
	) {
		$has_css_template = apply_filters( 'cs_load_head_styles', true );
	} else {
		$has_css_template = false;
	}

	// Bail if no template
	if ( empty( $has_css_template ) ) {
		return;
	}

	?>
	<style id="cs-head-styles">.cs_download{float:left;}.cs_download_columns_1 .cs_download{width: 100%;}.cs_download_columns_2 .cs_download{width:50%;}.cs_download_columns_0 .cs_download,.cs_download_columns_3 .cs_download{width:33%;}.cs_download_columns_4 .cs_download{width:25%;}.cs_download_columns_5 .cs_download{width:20%;}.cs_download_columns_6 .cs_download{width:16.6%;}</style>
	<?php
}
add_action( 'wp_print_styles', 'cs_load_head_styles' );

/**
 * Determine if the frontend scripts should be loaded in the footer or header (default: footer)
 *
 * @since 2.8.6
 * @return mixed
 */
function cs_scripts_in_footer() {
	return apply_filters( 'cs_load_scripts_in_footer', true );
}

/** Admin Area ****************************************************************/

/**
 * Return the current script version
 *
 * @since 3.0
 *
 * @return string
 */
function cs_admin_get_script_version() {
	return cs_doing_script_debug()
		? current_time( 'timestamp' )
		: CS_VERSION;
}

/**
 * Register all admin area scripts
 *
 * @since 3.0
 */
function cs_register_admin_scripts() {
	$js_dir     = CS_PLUGIN_URL . 'assets/js/';
	$version    = cs_admin_get_script_version();
	$admin_deps = array( 'jquery', 'jquery-form', 'underscore', 'alpinejs' );

	// Register scripts
	wp_register_script( 'alpinejs',                        $js_dir . 'alpine.min.js',                        array(), '3.4.2', false );
	wp_register_script( 'jquery-chosen',                   $js_dir . 'vendor/chosen.jquery.min.js',          array( 'jquery' ), $version );
	wp_register_script( 'cs-jquery-flot',                 $js_dir . 'vendor/jquery.flot.min.js',            array( 'jquery' ), $version );
	wp_register_script( 'cs-moment-js',                   $js_dir . 'vendor/moment.min.js',                 array(), $version );
	wp_register_script( 'cs-chart-js',                    $js_dir . 'vendor/chartjs.min.js',                array( 'cs-moment-js' ), $version );
	wp_register_script( 'cs-admin-scripts',               $js_dir . 'cs-admin.js',                         $admin_deps, $version );
	wp_register_script( 'cs-admin-tax-rates',             $js_dir . 'cs-admin-tax-rates.js',               array( 'wp-backbone', 'jquery-chosen' ), $version, true );
	wp_register_script( 'cs-admin-email-tags',            $js_dir . 'cs-admin-email-tags.js',              array( 'thickbox', 'wp-util' ), $version );

	// Individual admin pages.
	$admin_pages = array(
		'customers'    => array(
			'cs-admin-tools-export'
		),
		'dashboard'    => array(),
		'discounts'    => array(),
		'downloads'    => array(),
		'tools-export' => array(),
		'tools-import' => array(),
		'notes'        => array(),
		'orders'       => array(
			'cs-admin-notes',
			'wp-util',
			'wp-backbone',
		),
		// Backwards compatibility.
		'payments'     => array(),
		'reports'      => array(
			'cs-chart-js',
		),
		'settings'     => array(),
		'tools'        => array(
			'cs-admin-tools-export'
		),
		'upgrades'     => array()
	);

	foreach ( $admin_pages as $page => $deps ) {
		wp_register_script(
			'cs-admin-' . $page,
			$js_dir . 'cs-admin-' . $page . '.js',
			array_merge( $admin_deps, $deps ),
			$version
		);
	}
}
add_action( 'admin_init', 'cs_register_admin_scripts' );

/**
 * Register all admin area styles
 *
 * @since 3.0
 */
function cs_register_admin_styles() {
	$css_dir     = CS_PLUGIN_URL . 'assets/css/';
	$css_suffix  = is_rtl() ? '-rtl.min.css' : '.min.css';
	$version     = cs_admin_get_script_version();
	$deps        = array( 'cs-admin' );

	// Register styles
	wp_register_style( 'jquery-chosen',         $css_dir . 'chosen'               . $css_suffix, array(), $version );
	wp_register_style( 'jquery-ui-css',         $css_dir . 'jquery-ui-fresh'      . $css_suffix, array(), $version );
	wp_register_style( 'cs-admin',             $css_dir . 'cs-admin'            . $css_suffix, array(), $version );
	wp_register_style( 'cs-admin-menu',        $css_dir . 'cs-admin-menu'       . $css_suffix, array(), $version );
	wp_register_style( 'cs-admin-chosen',      $css_dir . 'cs-admin-chosen'     . $css_suffix, $deps,   $version );
	wp_register_style( 'cs-admin-email-tags',  $css_dir . 'cs-admin-email-tags' . $css_suffix, $deps,   $version );
	wp_register_style( 'cs-admin-datepicker',  $css_dir . 'cs-admin-datepicker' . $css_suffix, $deps,   $version );
	wp_register_style( 'cs-admin-tax-rates',   $css_dir . 'cs-admin-tax-rates'  . $css_suffix, $deps,   $version );
}
add_action( 'admin_init', 'cs_register_admin_styles' );

/**
 * Print admin area scripts
 *
 * @since 3.0
 */
function cs_enqueue_admin_scripts( $hook = '' ) {

	// Bail if not an CommerceStore admin page
	if ( ! cs_should_load_admin_scripts( $hook ) ) {
		return;
	}

	// Enqueue media on CommerceStore admin pages
	wp_enqueue_media();

	// Scripts to enqueue
	$scripts = array(
		'cs-admin-scripts',
		'jquery-chosen',
		'jquery-form',
		'jquery-ui-datepicker',
		'jquery-ui-dialog',
		'jquery-ui-tooltip',
		'media-upload',
		'thickbox',
		'wp-ajax-response',
		'wp-color-picker',
	);

	// Loop through and enqueue the scripts
	foreach ( $scripts as $script ) {
		wp_enqueue_script( $script );
	}

	// Downloads page.
	if ( cs_is_admin_page( CS_EX_DOWNLOAD_ADMIN_PAGE ) ) {
		wp_enqueue_script( 'cs-admin-downloads' );
	}

	// Upgrades Page
	if ( in_array( $hook, array( 'cs-admin-upgrades', 'download_page_cs-tools' ) ) ) {
		wp_enqueue_script( 'cs-admin-tools-export' );
		wp_enqueue_script( 'cs-admin-upgrades' );
	}

}
add_action( 'admin_enqueue_scripts', 'cs_enqueue_admin_scripts' );

/**
 * Enqueue admin area styling.
 *
 * Always enqueue the menu styling. Only enqueue others on CommerceStore pages.
 *
 * @since 3.0
 */
function cs_enqueue_admin_styles( $hook = '' ) {

	// Always enqueue the admin menu CSS
	wp_enqueue_style( 'cs-admin-menu' );

	// Bail if not an CommerceStore admin page
	if ( ! cs_should_load_admin_scripts( $hook ) ) {
		return;
	}

	// Styles to enqueue (in priority order)
	$styles = array(
		'jquery-chosen',
		'thickbox',
		'wp-jquery-ui-dialog',
		'wp-color-picker',
		'cs-admin',
		'cs-admin-chosen',
		'cs-admin-datepicker'
	);

	// Loop through and enqueue the scripts
	foreach ( $styles as $style ) {
		wp_enqueue_style( $style );
	}
}
add_action( 'admin_enqueue_scripts', 'cs_enqueue_admin_styles' );

/**
 * Localize all admin scripts
 *
 * @since 3.0
 */
function cs_localize_admin_scripts() {
	$currency = cs_get_currency();

	// Customize the currency on a few individual pages.
	if ( function_exists( 'cs_is_admin_page' ) ) {
		if ( cs_is_admin_page( 'reports' ) ) {
			/*
			 * For reports, use the currency currently being filtered.
			 */
			$currency_filter = \CS\Reports\get_filter_value( 'currencies' );
			if ( ! empty( $currency_filter ) && array_key_exists( strtoupper( $currency_filter ), cs_get_currencies() ) ) {
				$currency = strtoupper( $currency_filter );
			}
		} elseif ( cs_is_admin_page( 'payments' ) && ! empty( $_GET['id'] ) ) {
			/*
			 * For orders & refunds, use the currency of the current order.
			 */
			$order = cs_get_order( absint( $_GET['id'] ) );
			if ( $order instanceof \CS\Orders\Order ) {
				$currency = $order->currency;
			}
		}
	}

	// Admin scripts
	wp_localize_script( 'cs-admin-scripts', 'cs_vars', array(
		'post_id'                 => get_the_ID(),
		'cs_version'             => cs_admin_get_script_version(),
		'currency'                => $currency,
		'currency_sign'           => cs_currency_filter( '', $currency ),
		'currency_pos'            => cs_get_option( 'currency_position', 'before' ),
		'currency_decimals'       => cs_currency_decimal_filter( 2, $currency ),
		'decimal_separator'       => cs_get_option( 'decimal_separator', '.' ),
		'thousands_separator'     => cs_get_option( 'thousands_separator', ',' ),
		'date_picker_format'      => cs_get_date_picker_format( 'js' ),
		'add_new_download'        => __( 'Add New Download', 'commercestore' ),
		'use_this_file'           => __( 'Use This File', 'commercestore' ),
		'quick_edit_warning'      => __( 'Sorry, not available for variable priced products.', 'commercestore' ),
		'delete_payment'          => __( 'Are you sure you want to delete this order?', 'commercestore' ),
		'delete_order_item'       => __( 'Are you sure you want to delete this item?', 'commercestore' ),
		'delete_order_adjustment' => __( 'Are you sure you want to delete this adjustment?', 'commercestore' ),
		'delete_note'             => __( 'Are you sure you want to delete this note?', 'commercestore' ),
		'delete_tax_rate'         => __( 'Are you sure you want to delete this tax rate?', 'commercestore' ),
		'revoke_api_key'          => __( 'Are you sure you want to revoke this API key?', 'commercestore' ),
		'regenerate_api_key'      => __( 'Are you sure you want to regenerate this API key?', 'commercestore' ),
		'resend_receipt'          => __( 'Are you sure you want to resend the purchase receipt?', 'commercestore' ),
		'disconnect_customer'     => __( 'Are you sure you want to disconnect the WordPress user from this customer record?', 'commercestore' ),
		'copy_download_link_text' => __( 'Copy these links to your clipboard and give them to your customer', 'commercestore' ),
		'delete_payment_download' => sprintf( __( 'Are you sure you want to delete this %s?', 'commercestore' ), cs_get_label_singular() ),
		'type_to_search'          => sprintf( __( 'Type to search %s',     'commercestore' ), cs_get_label_plural() ),
		'one_option'              => sprintf( __( 'Choose a %s',           'commercestore' ), cs_get_label_singular() ),
		'one_or_more_option'      => sprintf( __( 'Choose one or more %s', 'commercestore' ), cs_get_label_plural() ),
		'one_price_min'           => __( 'You must have at least one price', 'commercestore' ),
		'one_field_min'           => __( 'You must have at least one field', 'commercestore' ),
		'one_download_min'        => __( 'Payments must contain at least one item', 'commercestore' ),
		'no_results_text'         => __( 'No match for:', 'commercestore'),
		'numeric_item_price'      => __( 'Item price must be numeric', 'commercestore' ),
		'numeric_item_tax'        => __( 'Item tax must be numeric', 'commercestore' ),
		'numeric_quantity'        => __( 'Quantity must be numeric', 'commercestore' ),
		'remove_text'             => __( 'Remove', 'commercestore' ),
		'batch_export_no_class'   => __( 'You must choose a method.', 'commercestore' ),
		'batch_export_no_reqs'    => __( 'Required fields not completed.', 'commercestore' ),
		'reset_stats_warn'        => __( 'Are you sure you want to reset your store? This process is <strong><em>not reversible</em></strong>. Please be sure you have a recent backup.', 'commercestore' ),
		'unsupported_browser'     => __( 'We are sorry but your browser is not compatible with this kind of file upload. Please upgrade your browser.', 'commercestore' ),
		'show_advanced_settings'  => __( 'Show advanced settings', 'commercestore' ),
		'hide_advanced_settings'  => __( 'Hide advanced settings', 'commercestore' ),
		'no_downloads_error'      => __( 'There are no downloads attached to this payment', 'commercestore' ),
		'wait'                    => __( 'Please wait &hellip;', 'commercestore' ),

		// Features
		'quantities_enabled'          => cs_item_quantities_enabled(),
		'taxes_enabled'               => cs_use_taxes(),
		'taxes_included'              => cs_use_taxes() && cs_prices_include_tax(),
		'new_media_ui'                => apply_filters( 'cs_use_35_media_ui', 1 ),

		'restBase'  => rest_url( \CS\API\v3\Endpoint::$namespace ),
		'restNonce' => wp_create_nonce( 'wp_rest' ),
	) );

	wp_localize_script( 'cs-admin-upgrades', 'cs_admin_upgrade_vars', array(
			'migration_complete' => esc_html__( 'Migration complete', 'commercestore' )
	) );
}
add_action( 'admin_enqueue_scripts', 'cs_localize_admin_scripts' );

/**
 * Add `defer` to the AlpineJS script tag.
 */
add_filter( 'script_loader_tag', function( $url ) {
	if ( false !== strpos( $url, CS_PLUGIN_URL . 'assets/js/alpine.min.js' ) ) {
		$url = str_replace( ' src', ' defer src', $url );
	}

	return $url;
} );

/**
 * Admin Downloads Icon
 *
 * Echoes the CSS for the downloads post type icon.
 *
 * @since 1.0
 * @since 2.6.11 Removed globals and CSS for custom icon
*/
function cs_admin_downloads_icon() {

	$images_url      = CS_PLUGIN_URL . 'assets/images/';
	$menu_icon       = '\f316';
	$icon_cpt_url    = $images_url . 'cs-cpt.png';
	$icon_cpt_2x_url = $images_url . 'cs-cpt-2x.png';
	?>
	<style type="text/css" media="screen">
		#dashboard_right_now .download-count:before {
			content: '<?php echo $menu_icon; ?>';
		}
		#icon-edit.icon32-posts-download {
			background: url(<?php echo $icon_cpt_url; ?>) -7px -5px no-repeat;
		}
		@media
		only screen and (-webkit-min-device-pixel-ratio: 1.5),
		only screen and (   min--moz-device-pixel-ratio: 1.5),
		only screen and (     -o-min-device-pixel-ratio: 3/2),
		only screen and (        min-device-pixel-ratio: 1.5),
		only screen and (        		 min-resolution: 1.5dppx) {
			#icon-edit.icon32-posts-download {
				background: url(<?php echo $icon_cpt_2x_url; ?>) no-repeat -7px -5px !important;
				background-size: 55px 45px !important;
			}
		}
	</style>
	<?php
}
add_action( 'admin_head','cs_admin_downloads_icon' );

/**
 * Should we be loading admin scripts
 *
 * @since 3.0
 *
 * @param string $hook
 * @return bool
 */
function cs_should_load_admin_scripts( $hook = '' ) {

	// Back compat for hook suffix
	$hook_suffix = empty( $hook )
		? $GLOBALS['hook_suffix']
		: $hook;

	// Filter & return
	return (bool) apply_filters( 'cs_load_admin_scripts', cs_is_admin_page(), $hook_suffix );
}

/** Deprecated ****************************************************************/

/**
 * Enqueue admin area scripts.
 *
 * Only enqueue on CommerceStore pages.
 *
 * @since 1.0
 * @deprecated 3.0
 */
function cs_load_admin_scripts( $hook ) {

	// Bail if not an CommerceStore admin page
	if ( ! cs_should_load_admin_scripts( $hook ) ) {
		return;
	}

	// Register all scripts and styles
	cs_register_admin_scripts();
	cs_register_admin_styles();

	// Load scripts and styles for back-compat
	cs_enqueue_admin_scripts( $hook );
	cs_enqueue_admin_styles( $hook );
}
