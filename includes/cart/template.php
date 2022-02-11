<?php
/**
 * Cart Template
 *
 * @package     CS
 * @subpackage  Cart
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Builds the Cart by providing hooks and calling all the hooks for the Cart
 *
 * @since 1.0
 * @return void
 */
function cs_checkout_cart() {

	// Check if the Update cart button should be shown
	if( cs_item_quantities_enabled() ) {
		add_action( 'cs_cart_footer_buttons', 'cs_update_cart_button' );
	}

	// Check if the Save Cart button should be shown
	if( ! cs_is_cart_saving_disabled() ) {
		add_action( 'cs_cart_footer_buttons', 'cs_save_cart_button' );
	}

	do_action( 'cs_before_checkout_cart' );
	echo '<form id="cs_checkout_cart_form" method="post">';
		echo '<div id="cs_checkout_cart_wrap">';
			do_action( 'cs_checkout_cart_top' );
			cs_get_template_part( 'checkout_cart' );
			do_action( 'cs_checkout_cart_bottom' );
		echo '</div>';
	echo '</form>';
	do_action( 'cs_after_checkout_cart' );
}

/**
 * Renders the Shopping Cart
 *
 * @since 1.0
 *
 * @param bool $echo
 * @return string Fully formatted cart
 */
function cs_shopping_cart( $echo = false ) {
	ob_start();

	do_action( 'cs_before_cart' );

	cs_get_template_part( 'widget', 'cart' );

	do_action( 'cs_after_cart' );

	if ( $echo ) {
		echo ob_get_clean();
	} else {
		return ob_get_clean();
	}
}

/**
 * Get Cart Item Template
 *
 * @since 1.0
 * @param int $cart_key Cart key
 * @param array $item Cart item
 * @param bool $ajax AJAX?
 * @return string Cart item
*/
function cs_get_cart_item_template( $cart_key, $item, $ajax = false ) {
	global $post;

	$id = is_array( $item ) ? $item['id'] : $item;

	$remove_url = cs_remove_item_url( $cart_key );
	$title      = get_the_title( $id );
	$options    = !empty( $item['options'] ) ? $item['options'] : array();
	$quantity   = cs_get_cart_item_quantity( $id, $options );
	$price      = cs_get_cart_item_price( $id, $options );

	if ( ! empty( $options ) ) {
		$title .= ( cs_has_variable_prices( $item['id'] ) ) ? ' <span class="cs-cart-item-separator">-</span> ' . cs_get_price_name( $id, $item['options'] ) : cs_get_price_name( $id, $item['options'] );
	}

	ob_start();

	cs_get_template_part( 'widget', 'cart-item' );

	$item = ob_get_clean();

	$item = str_replace( '{item_title}', $title, $item );
	$item = str_replace( '{item_amount}', cs_currency_filter( cs_format_amount( $price ) ), $item );
	$item = str_replace( '{cart_item_id}', absint( $cart_key ), $item );
	$item = str_replace( '{item_id}', absint( $id ), $item );
	$item = str_replace( '{item_quantity}', absint( $quantity ), $item );
	$item = str_replace( '{remove_url}', $remove_url, $item );
  	$subtotal = '';
  	if ( $ajax ){
   	 $subtotal = cs_currency_filter( cs_format_amount( cs_get_cart_subtotal() ) ) ;
  	}
 	$item = str_replace( '{subtotal}', $subtotal, $item );

	return apply_filters( 'cs_cart_item', $item, $id );
}

/**
 * Returns the Empty Cart Message
 *
 * @since 1.0
 * @return string Cart is empty message
 */
function cs_empty_cart_message() {
	return apply_filters( 'cs_empty_cart_message', '<span class="cs_empty_cart">' . __( 'Your cart is empty.', 'commercestore' ) . '</span>' );
}

/**
 * Echoes the Empty Cart Message
 *
 * @since 1.0
 * @return void
 */
function cs_empty_checkout_cart() {
	echo cs_empty_cart_message();
}
add_action( 'cs_cart_empty', 'cs_empty_checkout_cart' );

/*
 * Calculate the number of columns in the cart table dynamically.
 *
 * @since 1.8
 * @return int The number of columns
 */
function cs_checkout_cart_columns() {
	global $wp_filter, $wp_version;

	$columns_count = 3;

	if ( ! empty( $wp_filter['cs_checkout_table_header_first'] ) ) {
		$header_first_count = 0;
		$callbacks          = $wp_filter['cs_checkout_table_header_first']->callbacks;

		foreach ( $callbacks as $callback ) {
			$header_first_count += count( $callback );
		}
		$columns_count += $header_first_count;
	}

	if ( ! empty( $wp_filter['cs_checkout_table_header_last'] ) ) {
		$header_last_count = 0;
		$callbacks         = $wp_filter['cs_checkout_table_header_last']->callbacks;

		foreach ( $callbacks as $callback ) {
			$header_last_count += count( $callback );
		}
		$columns_count += $header_last_count;
	}

	return apply_filters( 'cs_checkout_cart_columns', $columns_count );
}

/**
 * Display the "Save Cart" button on the checkout
 *
 * @since 1.8
 * @return void
 */
function cs_save_cart_button() {
	if ( cs_is_cart_saving_disabled() )
		return;

	$color = cs_get_option( 'checkout_color', 'blue' );
	$color = ( $color == 'inherit' ) ? '' : $color;

	if ( cs_is_cart_saved() ) : ?>
		<a class="cs-cart-saving-button cs-submit button<?php echo ' ' . $color; ?>" id="cs-restore-cart-button" href="<?php echo esc_url( add_query_arg( array( 'cs_action' => 'restore_cart', 'cs_cart_token' => cs_get_cart_token() ) ) ); ?>"><?php _e( 'Restore Previous Cart', 'commercestore' ); ?></a>
	<?php endif; ?>
	<a class="cs-cart-saving-button cs-submit button<?php echo ' ' . $color; ?>" id="cs-save-cart-button" href="<?php echo esc_url( add_query_arg( 'cs_action', 'save_cart' ) ); ?>"><?php _e( 'Save Cart', 'commercestore' ); ?></a>
	<?php
}

/**
 * Displays the restore cart link on the empty cart page, if a cart is saved
 *
 * @since 1.8
 * @return void
 */
function cs_empty_cart_restore_cart_link() {

	if( cs_is_cart_saving_disabled() )
		return;

	if( cs_is_cart_saved() ) {
		echo ' <a class="cs-cart-saving-link" id="cs-restore-cart-link" href="' . esc_url( add_query_arg( array( 'cs_action' => 'restore_cart', 'cs_cart_token' => cs_get_cart_token() ) ) ) . '">' . __( 'Restore Previous Cart.', 'commercestore' ) . '</a>';
	}
}
add_action( 'cs_cart_empty', 'cs_empty_cart_restore_cart_link' );

/**
 * Display the "Save Cart" button on the checkout
 *
 * @since 1.8
 * @return void
 */
function cs_update_cart_button() {
	if ( ! cs_item_quantities_enabled() )
		return;

	$color = cs_get_option( 'checkout_color', 'blue' );
	$color = ( $color == 'inherit' ) ? '' : $color;
?>
	<input type="submit" name="cs_update_cart_submit" class="cs-submit cs-no-js button<?php echo ' ' . $color; ?>" value="<?php _e( 'Update Cart', 'commercestore' ); ?>"/>
	<input type="hidden" name="cs_action" value="update_cart"/>
<?php

}

/**
 * Display the messages that are related to cart saving
 *
 * @since 1.8
 * @return void
 */
function cs_display_cart_messages() {
	$messages = CS()->session->get( 'cs_cart_messages' );

	if ( $messages ) {
		foreach ( $messages as $message_id => $message ) {

			// Try and detect what type of message this is
			if ( strpos( strtolower( $message ), 'error' ) ) {
				$type = 'error';
			} elseif ( strpos( strtolower( $message ), 'success' ) ) {
				$type = 'success';
			} else {
				$type = 'info';
			}

			$classes = apply_filters( 'cs_' . $type . '_class', array(
				'cs_errors', 'cs-alert', 'cs-alert-' . $type
			) );

			echo '<div class="' . implode( ' ', $classes ) . '">';
				// Loop message codes and display messages
					echo '<p class="cs_error" id="cs_msg_' . $message_id . '">' . $message . '</p>';
			echo '</div>';

		}

		// Remove all of the cart saving messages
		CS()->session->set( 'cs_cart_messages', null );
	}
}
add_action( 'cs_before_checkout_cart', 'cs_display_cart_messages' );

/**
 * Show Added To Cart Messages
 *
 * @since 1.0
 * @param int $download_id Download (Post) ID
 * @return void
 */
function cs_show_added_to_cart_messages( $download_id ) {
	if ( isset( $_POST['cs_action'] ) && $_POST['cs_action'] == 'add_to_cart' ) {
		if ( $download_id != absint( $_POST['download_id'] ) )
			$download_id = absint( $_POST['download_id'] );

		$alert = '<div class="cs_added_to_cart_alert">'
		. sprintf( __('You have successfully added %s to your shopping cart.','commercestore' ), get_the_title( $download_id ) )
		. ' <a href="' . cs_get_checkout_uri() . '" class="cs_alert_checkout_link">' . __('Checkout.','commercestore' ) . '</a>'
		. '</div>';

		echo apply_filters( 'cs_show_added_to_cart_messages', $alert );
	}
}
add_action('cs_after_download_content', 'cs_show_added_to_cart_messages');
