<?php
/**
 * Login / Register Functions
 *
 * @package     CS
 * @subpackage  Functions/Login
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * While loading the template, see if an error was set for a filed login attempt and set the proper
 * HTTP status code if there was a failed login attempt.
 *
 * @since 2.9.24
 *
 * @return void
 */
function cs_login_error_check() {
	$errors = cs_get_errors();
	if ( ! empty( $errors ) ) {
		if ( array_key_exists( 'cs_invalid_login', $errors ) ) {
			status_header( 401 );
		}
	}
}
add_action( 'template_redirect', 'cs_login_error_check', 10 );

/**
 * Login Form
 *
 * @since 1.0
 * @global $post
 * @param string $redirect Redirect page URL
 * @return string Login form
*/
function cs_login_form( $redirect = '' ) {
	global $cs_login_redirect;

	if ( empty( $redirect ) ) {
		$redirect = cs_get_current_page_url();
	}

	$cs_login_redirect = $redirect;

	ob_start();

	cs_get_template_part( 'shortcode', 'login' );

	return apply_filters( 'cs_login_form', ob_get_clean() );
}

/**
 * Registration Form
 *
 * @since 2.0
 * @global $post
 * @param string $redirect Redirect page URL
 * @return string Register form
*/
function cs_register_form( $redirect = '' ) {
	global $cs_register_redirect;

	if ( empty( $redirect ) ) {
		$redirect = cs_get_current_page_url();
	}

	$cs_register_redirect = $redirect;

	ob_start();

	cs_get_template_part( 'shortcode', 'register' );

	return apply_filters( 'cs_register_form', ob_get_clean() );
}

/**
 * Process Login Form
 *
 * @since 1.0
 * @since 2.9.24 No longer does validation which would prevent bruteforce detection plugins to be able to integrate.
 *
 * @param array $data Data sent from the login form
 * @return void
*/
function cs_process_login_form( $data ) {

	if ( ! empty( $data['cs_login_nonce'] ) && wp_verify_nonce( $data['cs_login_nonce'], 'cs-login-nonce' ) ) {
		$login      = isset( $data['cs_user_login'] ) ? $data['cs_user_login'] : '';
		$pass       = isset( $data['cs_user_pass'] ) ? $data['cs_user_pass'] : '';
		$rememberme = isset( $data['rememberme'] );

		$user = cs_log_user_in( 0, $login, $pass, $rememberme );

		// Wipe these variables so they aren't anywhere in the submitted format any longer.
		$login = null;
		$pass  = null;
		$data['cs_user_login'] = null;
		$data['cs_user_pass']  = null;

		// Check for errors and redirect if none present.
		$errors = cs_get_errors();
		if ( ! $errors ) {
			$default_redirect_url = esc_url_raw( $data['cs_redirect'] );
			if ( has_filter( 'cs_login_redirect' ) ) {
				$user_id = $user instanceof WP_User ? $user->ID : false;
				/**
				 * Filters the URL to which users are redirected to after logging in.
				 *
				 * @since 1.0
				 * @param string $default_redirect_url The URL to which to redirect after logging in.
				 * @param int|false                    User ID. false if no ID is available.
				 */
				wp_redirect( apply_filters( 'cs_login_redirect', $default_redirect_url, $user_id ) );
			} else {
				wp_safe_redirect( $default_redirect_url );
			}
			cs_die();
		}
	}
}
add_action( 'cs_user_login', 'cs_process_login_form' );


/**
 * Log User In
 *
 * @since 1.0
 * @since 2.9.24 Uses the wp_signon function instead of all the additional checks which can bypass hooks in core.
 *
 * @param int $user_id User ID
 * @param string $user_login Username
 * @param string $user_pass Password
 * @param boolean $remember Remember me
 * @return void
*/
function cs_log_user_in( $user_id, $user_login, $user_pass, $remember = false ) {

	$credentials = array(
		'user_login'    => $user_login,
		'user_password' => $user_pass,
		'remember'      => $remember,
	);

	$user = wp_signon( $credentials );

	if ( ! $user instanceof WP_User ) {
		cs_set_error(
			'cs_invalid_login',
			sprintf(
				/* translators: %1$s Opening anchor tag, do not translate. %2$s Closing anchor tag, do not translate. */
				__( 'Invalid username or password. %1$sReset Password%2$s', 'commercestore' ),
				'<a href="' . esc_url( cs_get_lostpassword_url() ) . '">',
				'</a>'
			)
		);
	} else {
		// Since wp_signon doesn't set the current user, we need to do this.
		wp_set_current_user( $user->ID );

		do_action( 'cs_log_user_in', $user_id, $user_login, $user_pass );
	}

	return $user;

}

add_filter( 'wp_login_errors', 'cs_login_register_error_message', 10, 2 );
/**
 * Changes the WordPress login confirmation message when using CS's reset password link.
 *
 * @since 2.10
 * @param object \WP_Error $errors
 * @param string $redirect
 * @return void
 */
function cs_login_register_error_message( $errors, $redirect ) {
	$action_is_confirm   = ! empty( $_GET['checkemail'] ) && 'confirm' === sanitize_text_field( $_GET['checkemail'] );
	$cs_action_is_reset = ! empty( $_GET['cs_reset_password'] ) && 'confirm' === sanitize_text_field( $_GET['checkemail'] );
	$redirect_url        = ! empty( $_GET['cs_redirect'] ) ? urldecode( $_GET['cs_redirect'] ) : false;
	if ( $action_is_confirm && $cs_action_is_reset && $redirect_url ) {
		$errors->remove( 'confirm' );
		$errors->add(
			'confirm',
			apply_filters(
				'cs_login_register_error_message',
				sprintf(
					/* translators: %s: Link to the referring page. */
					__( 'Follow the instructions in the confirmation email you just received, then <a href="%s">return to what you were doing</a>.', 'commercestore' ),
					esc_url( $redirect_url )
				),
				$redirect_url
			),
			'message'
		);
	}

	return $errors;
}

/**
 * Gets the lost password URL, customized for CS. Using this allows the password
 * reset form to redirect to the login screen with the CommerceStore custom confirmation message.
 *
 * @since 2.10
 * @return string
 */
function cs_get_lostpassword_url() {
	$url      = wp_validate_redirect( cs_get_current_page_url(), cs_get_checkout_uri() );
	$redirect = add_query_arg(
		array(
			'checkemail'         => 'confirm',
			'cs_reset_password' => 'confirm',
			'cs_redirect'       => urlencode( $url ),
		),
		wp_login_url()
	);

	return wp_lostpassword_url( $redirect );
}

/**
 * Process Register Form
 *
 * @since 2.0
 * @param array $data Data sent from the register form
 * @return void
*/
function cs_process_register_form( $data ) {

	if ( is_user_logged_in() ) {
		return;
	}

	if ( empty( $_POST['cs_register_submit'] ) ) {
		return;
	}

	do_action( 'cs_pre_process_register_form' );

	if ( empty( $data['cs_user_login'] ) ) {
		cs_set_error( 'empty_username', __( 'Invalid username', 'commercestore' ) );
	}

	if ( username_exists( $data['cs_user_login'] ) ) {
		cs_set_error( 'username_unavailable', __( 'Username already taken', 'commercestore' ) );
	}

	if ( ! validate_username( $data['cs_user_login'] ) ) {
		cs_set_error( 'username_invalid', __( 'Invalid username', 'commercestore' ) );
	}

	if ( email_exists( $data['cs_user_email'] ) ) {
		cs_set_error( 'email_unavailable', __( 'Email address already taken', 'commercestore' ) );
	}

	if ( empty( $data['cs_user_email'] ) || ! is_email( $data['cs_user_email'] ) ) {
		cs_set_error( 'email_invalid', __( 'Invalid email', 'commercestore' ) );
	}

	if ( ! empty( $data['cs_payment_email'] ) && $data['cs_payment_email'] != $data['cs_user_email'] && ! is_email( $data['cs_payment_email'] ) ) {
		cs_set_error( 'payment_email_invalid', __( 'Invalid payment email', 'commercestore' ) );
	}

	if ( isset( $data['cs_honeypot'] ) && ! empty( $data['cs_honeypot'] ) ) {
		cs_set_error( 'invalid_form_data', __( 'Registration form validation failed.', 'commercestore' ) );
	}

	if ( empty( $_POST['cs_user_pass'] ) ) {
		cs_set_error( 'empty_password', __( 'Please enter a password', 'commercestore' ) );
	}

	if ( ( ! empty( $_POST['cs_user_pass'] ) && empty( $_POST['cs_user_pass2'] ) ) || ( $_POST['cs_user_pass'] !== $_POST['cs_user_pass2'] ) ) {
		cs_set_error( 'password_mismatch', __( 'Passwords do not match', 'commercestore' ) );
	}

	do_action( 'cs_process_register_form' );

	// Check for errors and redirect if none present.
	$errors = cs_get_errors();

	if ( empty( $errors ) ) {

		$redirect = apply_filters( 'cs_register_redirect', $data['cs_redirect'] );

		cs_register_and_login_new_user(
			array(
				'user_login'      => $data['cs_user_login'],
				'user_pass'       => $data['cs_user_pass'],
				'user_email'      => $data['cs_user_email'],
				'user_registered' => date( 'Y-m-d H:i:s' ),
				'role'            => get_option( 'default_role' ),
			)
		);

		cs_redirect( $redirect );
	}
}
add_action( 'cs_user_register', 'cs_process_register_form' );
