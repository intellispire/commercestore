<?php
/**
 * Admin Footer
 *
 * @package     CS
 * @subpackage  Admin/Footer
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 * Add rating links to the admin dashboard
 *
 * @since	    1.8.5
 * @global		string $typenow
 * @param       string $footer_text The existing footer text
 * @return      string
 */
function cs_admin_rate_us( $footer_text ) {
	global $typenow;

	if ( $typenow == 'download' ) {
		$rate_text = sprintf( __( 'Thank you for using <a href="%1$s" target="_blank">CommerceStore</a>! Please <a href="%2$s" target="_blank">rate us on WordPress.org</a>', 'commercestore' ),
			'https://commercestore.com',
			'https://wordpress.org/support/plugin/commercestore/reviews/?rate=5#new-post'
		);

		return str_replace( '</span>', '', $footer_text ) . ' | ' . $rate_text . '</span>';
	} else {
		return $footer_text;
	}
}
add_filter( 'admin_footer_text', 'cs_admin_rate_us' );
