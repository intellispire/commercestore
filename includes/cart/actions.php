<?php
/**
 * Cart Actions.
 *
 * @package     CS
 * @subpackage  Cart
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register Endpoints for for adding/removing items from the cart.
 *
 * @since 1.3.4
 */
function cs_add_rewrite_endpoints( $rewrite_rules ) {
	add_rewrite_endpoint( 'cs-add', EP_ALL );
	add_rewrite_endpoint( 'cs-remove', EP_ALL );
}
add_action( 'init', 'cs_add_rewrite_endpoints' );

/**
 * Process cart endpoints.
 *
 * @since 1.3.4
*/
function cs_process_cart_endpoints() {
	global $wp_query;

	// Adds an item to the cart with a /cs-add/# URL.
	if ( isset( $wp_query->query_vars['cs-add'] ) ) {
		$download_id = absint( $wp_query->query_vars['cs-add'] );
		$cart        = cs_add_to_cart( $download_id, array() );

		cs_redirect( cs_get_checkout_uri() );
	}

	// Removes an item from the cart with a /cs-remove/# URL.
	if ( isset( $wp_query->query_vars['cs-remove'] ) ) {
		$cart_key = absint( $wp_query->query_vars['cs-remove'] );
		$cart     = cs_remove_from_cart( $cart_key );

		cs_redirect( cs_get_checkout_uri() );
	}
}
add_action( 'template_redirect', 'cs_process_cart_endpoints', 100 );

/**
 * Process the 'Add to Cart' request.
 *
 * @since 1.0
 *
 * @param array $data
 */
function cs_process_add_to_cart( $data ) {
	$download_id = ! empty( $data['download_id'] ) ? absint( $data['download_id'] ) : false;
	$options     = isset( $data['cs_options'] ) ? $data['cs_options'] : array();

	if ( ! empty( $data['cs_download_quantity'] ) ) {
		$options['quantity'] = absint( $data['cs_download_quantity'] );
	}

	if ( isset( $options['price_id'] ) && is_array( $options['price_id'] ) ) {
		foreach ( $options['price_id'] as  $key => $price_id ) {
			$options['quantity'][ $key ] = isset( $data[ 'cs_download_quantity_' . $price_id ] ) ? absint( $data[ 'cs_download_quantity_' . $price_id ] ) : 1;
		}
	}

	if ( ! empty( $download_id ) ) {
		cs_add_to_cart( $download_id, $options );
	}

	if ( cs_straight_to_checkout() && ! cs_is_checkout() ) {
		$query_args = remove_query_arg( array( 'cs_action', 'download_id', 'cs_options' ) );
		$query_part = strpos( $query_args, "?" );
		$url_parameters = '';

		if ( false !== $query_part ) {
			$url_parameters = substr( $query_args, $query_part );
		}

		cs_redirect( cs_get_checkout_uri() . $url_parameters, 303 );
	} else {
		cs_redirect( remove_query_arg( array( 'cs_action', 'download_id', 'cs_options' ) ) );
	}
}
add_action( 'cs_add_to_cart', 'cs_process_add_to_cart' );

/**
 * Process the 'Remove from Cart' request.
 *
 * @since 1.0
 *
 * @param $data
 */
function cs_process_remove_from_cart( $data ) {
	$cart_key = absint( $_GET['cart_item'] );

	if ( ! isset( $_GET['cs_remove_from_cart_nonce'] ) ) {
		cs_debug_log( __( 'Missing nonce when removing an item from the cart. Please read the following for more information: https://commercestore.com/development/2018/07/05/important-update-to-ajax-requests-in-commercestore-2-9-4', 'commercestore' ), true );
	}

	$nonce = ! empty( $_GET['cs_remove_from_cart_nonce'] )
		? sanitize_text_field( $_GET['cs_remove_from_cart_nonce'] )
		: '';

	$nonce_verified = wp_verify_nonce( $nonce, 'cs-remove-from-cart-' . $cart_key );
	if ( false !== $nonce_verified ) {
		cs_remove_from_cart( $cart_key );
	}

	wp_redirect( remove_query_arg( array( 'cs_action', 'cart_item', 'nocache' ) ) );
	cs_die();
}
add_action( 'cs_remove', 'cs_process_remove_from_cart' );

/**
 * Process the Remove fee from Cart request.
 *
 * @since 2.0
 *
 * @param $data
 */
function cs_process_remove_fee_from_cart( $data ) {
	$fee = sanitize_text_field( $data['fee'] );
	CS()->fees->remove_fee( $fee );
	cs_redirect( remove_query_arg( array( 'cs_action', 'fee', 'nocache' ) ) );
}
add_action( 'cs_remove_fee', 'cs_process_remove_fee_from_cart' );

/**
 * Process the Collection Purchase request.
 *
 * @since 1.0
 *
 * @param $data
 */
function cs_process_collection_purchase( $data ) {
	$taxonomy = urldecode( $data['taxonomy'] );
	$terms    = urldecode( $data['terms'] );

	cs_add_collection_to_cart( $taxonomy, $terms );
	cs_redirect( add_query_arg( 'added', '1', remove_query_arg( array( 'cs_action', 'taxonomy', 'terms' ) ) ) );
}
add_action( 'cs_purchase_collection', 'cs_process_collection_purchase' );

/**
 * Process cart updates, primarily for quantities.
 *
 * @since 1.7
 */
function cs_process_cart_update( $data ) {
	if ( ! empty( $data['cs-cart-downloads'] ) && is_array( $data['cs-cart-downloads'] ) ) {
		foreach ( $data['cs-cart-downloads'] as $key => $cart_download_id ) {
			$options  = json_decode( stripslashes( $data[ 'cs-cart-download-' . $key . '-options' ] ), true );
			$quantity = absint( $data[ 'cs-cart-download-' . $key . '-quantity' ] );
			cs_set_cart_item_quantity( $cart_download_id, $quantity, $options );
		}
	}
}
add_action( 'cs_update_cart', 'cs_process_cart_update' );

/**
 * Process cart save.
 *
 * @since 1.8
 */
function cs_process_cart_save( $data ) {
	$cart = cs_save_cart();

	if ( ! $cart ) {
		cs_redirect( cs_get_checkout_uri() );
	}
}
add_action( 'cs_save_cart', 'cs_process_cart_save' );

/**
 * Process cart save
 *
 * @since 1.8
 * @return void
 */
function cs_process_cart_restore( $data ) {
	$cart = cs_restore_cart();

	if ( ! is_wp_error( $cart ) ) {
		cs_redirect( cs_get_checkout_uri() );
	}
}
add_action( 'cs_restore_cart', 'cs_process_cart_restore' );
