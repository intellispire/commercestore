<?php
/**
 * Error Tracking
 *
 * @package     CS
 * @subpackage  Functions/Errors
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Print Errors
 *
 * Prints all stored errors. For use during checkout.
 * If errors exist, they are returned.
 *
 * @since 1.0
 * @uses cs_get_errors()
 * @uses cs_clear_errors()
 * @return void
 */
function cs_print_errors() {
	$errors = cs_get_errors();
	if ( $errors ) {

		echo cs_build_errors_html( $errors );

		cs_clear_errors();

	}
}
add_action( 'cs_purchase_form_before_submit', 'cs_print_errors' );
add_action( 'cs_ajax_checkout_errors', 'cs_print_errors' );
add_action( 'cs_print_errors', 'cs_print_errors' );

/**
 * Formats error messages and returns an HTML string.
 *
 * @param array $errors
 *
 * @since 2.11
 * @return string
 */
function cs_build_errors_html( $errors ) {
	$error_html = '';

	$classes = apply_filters( 'cs_error_class', array(
		'cs_errors', 'cs-alert', 'cs-alert-error'
	) );

	if ( ! empty( $errors ) && is_array( $errors ) ) {
		$error_html .= '<div class="' . implode( ' ', $classes ) . '">';
		// Loop error codes and display errors
		foreach ( $errors as $error_id => $error ) {
			$error_html .= '<p class="cs_error" id="cs_error_' . $error_id . '"><strong>' . __( 'Error', 'commercestore' ) . '</strong>: ' . $error . '</p>';

		}
		$error_html .= '</div>';
	}

	return $error_html;
}

/**
 * Get Errors
 *
 * Retrieves all error messages stored during the checkout process.
 * If errors exist, they are returned.
 *
 * @since 1.0
 * @uses CS_Session::get()
 * @return mixed array if errors are present, false if none found
 */
function cs_get_errors() {
	$errors = CS()->session->get( 'cs_errors' );
	$errors = apply_filters( 'cs_errors', $errors );
	return $errors;
}

/**
 * Set Error
 *
 * Stores an error in a session var.
 *
 * @since 1.0
 * @uses CS_Session::get()
 * @param int $error_id ID of the error being set
 * @param string $error_message Message to store with the error
 * @return void
 */
function cs_set_error( $error_id, $error_message ) {
	$errors = cs_get_errors();
	if ( ! $errors ) {
		$errors = array();
	}
	$errors[ $error_id ] = $error_message;
	CS()->session->set( 'cs_errors', $errors );
}

/**
 * Clears all stored errors.
 *
 * @since 1.0
 * @uses CS_Session::set()
 * @return void
 */
function cs_clear_errors() {
	CS()->session->set( 'cs_errors', null );
}

/**
 * Removes (unsets) a stored error
 *
 * @since 1.3.4
 * @uses CS_Session::set()
 * @param int $error_id ID of the error being set
 * @return string
 */
function cs_unset_error( $error_id ) {
	$errors = cs_get_errors();

	if ( $errors && isset( $errors[ $error_id ] ) ) {
		unset( $errors[ $error_id ] );
		CS()->session->set( 'cs_errors', $errors );
	}
}

/**
 * Register die handler for cs_die()
 *
 * @author Sunny Ratilal
 * @since 1.6
 *
 * @return void
 */
function _cs_die_handler() {
	die();
}

/**
 * Wrapper function for wp_die().
 *
 * This function adds filters for wp_die() which kills execution of the script
 * using wp_die(). This allows us to then to work with functions using cs_die()
 * in the unit tests.
 *
 * @author Sunny Ratilal
 * @since 1.6
 * @return void
 */
function cs_die( $message = '', $title = '', $status = 400 ) {
	if ( ! defined( 'CS_UNIT_TESTS' ) ) {
		add_filter( 'wp_die_ajax_handler', '_cs_die_handler', 10, 3 );
		add_filter( 'wp_die_handler'     , '_cs_die_handler', 10, 3 );
		add_filter( 'wp_die_json_handler', '_cs_die_handler', 10, 3 );
	}

	wp_die( $message, $title, array( 'response' => $status ) );
}
