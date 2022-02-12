<?php
/**
 * Admin Plugins
 *
 * @package     CS
 * @subpackage  Admin/Plugins
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.8
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Plugins row action links
 *
 * @author Michael Cannon <mc@aihr.us>
 * @since 1.8
 * @param array $links already defined action links
 * @param string $file plugin file path and name being processed
 * @return array $links
 */
function cs_plugin_action_links( $links = array(), $file = '' ) {

	// Only CommerceStore plugin row
	if ( CS_PLUGIN_BASE === $file ) {
		$settings_url = cs_get_admin_url( array(
			'page' => 'cs-settings'
		) );

		$links['settings'] = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Settings', 'commercestore' ) . '</a>';
	}

	// Return array of links
	return $links;
}
add_filter( 'plugin_action_links', 'cs_plugin_action_links', 10, 2 );


/**
 * Plugin row meta links
 *
 * @author Michael Cannon <mc@aihr.us>
 * @since 1.8
 * @param array $links already defined meta links
 * @param string $file plugin file path and name being processed
 * @return array $input
 */
function cs_plugin_row_meta( $links = array(), $file = '' ) {

	// Only CommerceStore plugin row
	if ( CS_PLUGIN_BASE === $file ) {
		$extensions_url = add_query_arg( array(
			'utm_source'   => 'plugins-page',
			'utm_medium'   => 'plugin-row',
			'utm_campaign' => 'admin',
		), 'https://commercestore.com/downloads/' );

		$links['extensions'] = '<a href="' . esc_url( $extensions_url ) . '">' . esc_html__( 'Extensions', 'commercestore' ) . '</a>';
	}

	// Return array of links
	return $links;
}
add_filter( 'plugin_row_meta', 'cs_plugin_row_meta', 10, 2 );
