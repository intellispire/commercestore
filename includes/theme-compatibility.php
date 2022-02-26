<?php
/**
 * Theme Compatibility
 *
 * Functions for compatibility with specific themes.
 *
 * @package     CS
 * @subpackage  Functions/Compatibility
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.3
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Remove the "download" post class from single Download pages
 *
 * The Responsive theme applies special styling the .download class resulting in really terrible display.
 *
 * @since 1.4.3
 * @param array $classes Post classes
 * @param string $class
 * @param int $post_id Post ID
 * @return array
 */
function cs_responsive_download_post_class( $classes = array(), $class = '', $post_id = 0 ) {
	if (
		! is_singular( CS_POST_TYPE ) &&
		! is_post_type_archive( CS_POST_TYPE ) &&
		! is_tax( CS_CAT_TYPE ) &&
		! is_tax( CS_TAG_TYPE )
	)
		return $classes;

	if ( ( $key = array_search( CS_EX_DOWNLOAD_CSS_CLASS, $classes ) ) )
		unset( $classes[ $key ] );

	return $classes;
}
add_filter( 'post_class', 'cs_responsive_download_post_class', 999, 3 );
