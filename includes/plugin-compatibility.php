<?php
/**
 * Plugin Compatibility
 *
 * Functions for compatibility with other plugins.
 *
 * @package     CS
 * @subpackage  Functions/Compatibility
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Disables admin sorting of Post Types Order
 *
 * When sorting downloads by price, earnings, sales, date, or name,
 * we need to remove the posts_orderby that Post Types Order imposes
 *
 * @since 1.2.2
 * @return void
 */
function cs_remove_post_types_order() {
	remove_filter( 'posts_orderby', 'CPTOrderPosts' );
}
add_action( 'load-edit.php', 'cs_remove_post_types_order' );

/**
 * Disables opengraph tags on the checkout page
 *
 * There is a bizarre conflict that makes the checkout errors not get displayed
 * when the Jetpack opengraph tags are displayed
 *
 * @since 1.3.3.1
 * @return bool
 */
function cs_disable_jetpack_og_on_checkout() {
	if ( cs_is_checkout() ) {
		remove_action( 'wp_head', 'jetpack_og_tags' );
	}
}
add_action( 'template_redirect', 'cs_disable_jetpack_og_on_checkout' );

/**
 * Checks if a caching plugin is active
 *
 * @since 1.4.1
 * @return bool $caching True if caching plugin is enabled, false otherwise
 */
function cs_is_caching_plugin_active() {
	$caching = ( function_exists( 'wpsupercache_site_admin' ) || defined( 'W3TC' ) || function_exists( 'rocket_init' ) ) || defined( 'WPHB_VERSION' );
	return apply_filters( 'cs_is_caching_plugin_active', $caching );
}

/**
 * Adds a ?nocache option for the checkout page
 *
 * This ensures the checkout page remains uncached when plugins like WP Super Cache are activated
 *
 * @since 1.4.1
 * @param array $settings Misc Settings
 * @return array $settings Updated Misc Settings
 */
function cs_append_no_cache_param( $settings ) {
	if ( ! cs_is_caching_plugin_active() )
		return $settings;

	$settings[] = array(
		'id' => 'no_cache_checkout',
		'name' => __('No Caching on Checkout?','commercestore' ),
		'desc' => __('Check this box in order to append a ?nocache parameter to the checkout URL to prevent caching plugins from caching the page.','commercestore' ),
		'type' => 'checkbox'
	);

	return $settings;
}
add_filter( 'cs_settings_misc', 'cs_append_no_cache_param', -1 );

/**
 * Show the correct language on the [downloads] shortcode if qTranslate is active
 *
 * @since 1.7
 * @param string $content Download content
 * @return string $content Download content
 */
function cs_qtranslate_content( $content ) {
	if( defined( 'QT_LANGUAGE' ) )
		$content = qtrans_useCurrentLanguageIfNotFoundShowAvailable( $content );
	return $content;
}
add_filter( 'cs_downloads_content', 'cs_qtranslate_content' );
add_filter( 'cs_downloads_excerpt', 'cs_qtranslate_content' );

/**
 * Prevents qTranslate from redirecting to language-specific URL when downloading purchased files
 *
 * @since 2.5
 * @param string       $target Target URL
 * @return string|bool $target Target URL. False if redirect is disabled
 */
function cs_qtranslate_prevent_redirect( $target ) {

	if( strpos( $target, 'csfile' ) ) {
		$target = false;
		global $q_config;
		$q_config['url_mode'] = '';
	}

	return $target;
}
add_filter( 'qtranslate_language_detect_redirect', 'cs_qtranslate_prevent_redirect' );

/**
 * Disable the WooCommerce 'Un-force SSL when leaving checkout' option on CommerceStore checkout
 * to prevent redirect loops
 *
 * @since 2.1
 * @return void
 */
function cs_disable_woo_ssl_on_checkout() {
	if( cs_is_checkout() && cs_is_ssl_enforced() ) {
		remove_action( 'template_redirect', array( 'WC_HTTPS', 'unforce_https_template_redirect' ) );
	}
}
add_action( 'template_redirect', 'cs_disable_woo_ssl_on_checkout', 9 );

/**
 * Disables the mandrill_nl2br filter while sending CommerceStore emails
 *
 * @since 2.1
 * @return void
 */
function cs_disable_mandrill_nl2br() {
	add_filter( 'mandrill_nl2br', '__return_false' );
}
add_action( 'cs_email_send_before', 'cs_disable_mandrill_nl2br');

/**
 * Prevents the Purchase Confirmation screen from being detected as a 404 error in the 404 Redirected plugin
 *
 * @since 2.2.3
 * @return void
 */
function cs_disable_404_redirected_redirect() {

	if( ! defined( 'WBZ404_VERSION' ) ) {
		return;
	}

	if( cs_is_success_page() ) {
		remove_action( 'template_redirect', 'wbz404_process404', 10 );
	}
}
add_action( 'template_redirect', 'cs_disable_404_redirected_redirect', 9 );

/**
 * Adds 'cs' to the list of Say What aliases after moving to WordPress.org language packs
 *
 * @since  2.4.6
 * @param  array $aliases Say What domain aliases
 * @return array          Say What domain alises with 'cs' added
 */
function cs_say_what_domain_aliases( $aliases ) {
	$aliases['commercestore'][] = 'cs';

	return $aliases;
}
add_filter( 'say_what_domain_aliases', 'cs_say_what_domain_aliases', 10, 1 );

/**
 * Removes the Really Simple SSL mixed content filter during file downloads to avoid
 * errors with chunked file delivery
 *
 * @see https://github.com/rlankhorst/really-simple-ssl/issues/30
 * @see https://github.com/commercestore/commercestore/issues/5802
 *
 * @since 2.7.10
 * @return void
 */
function cs_rsssl_remove_mixed_content_filter() {
	if ( class_exists( 'REALLY_SIMPLE_SSL' ) && did_action( 'cs_process_verified_download' ) ) {
		remove_action( 'init', array( RSSSL()->rsssl_mixed_content_fixer, 'start_buffer' ) );
		remove_action( 'shutdown', array( RSSSL()->rsssl_mixed_content_fixer, 'end_buffer' ) );
	}
}
add_action( 'plugins_loaded', 'cs_rsssl_remove_mixed_content_filter', 999 );
