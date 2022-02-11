<?php
/**
 * Manage actions and callbacks related to templates.
 *
 * @package     CS
 * @subpackage  Templates
 * @copyright   Copyright (c) 2017, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.8
 */

/**
 * Output a message and login form on the profile editor when the
 * current visitor is not logged in.
 *
 * @since 2.8
 */
function cs_profile_editor_logged_out() {
	echo '<p class="cs-logged-out">' . esc_html__( 'You need to log in to edit your profile.', 'commercestore' ) . '</p>';
	echo cs_login_form(); // WPCS: XSS ok.
}
add_action( 'cs_profile_editor_logged_out', 'cs_profile_editor_logged_out' );

/**
 * Output a message on the login form when a user is already logged in.
 *
 * This remains mainly for backwards compatibility.
 *
 * @since 2.8
 */
function cs_login_form_logged_in() {
	echo '<p class="cs-logged-in">' . esc_html__( 'You are already logged in', 'commercestore' ) . '</p>';
}
add_action( 'cs_login_form_logged_in', 'cs_login_form_logged_in' );