<?php
/**
 * PayPal Commerce "Buy Now" Buttons
 *
 * @package    commercestore
 * @subpackage Gateways\PayPal
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    GPL2+
 */

namespace CS\Gateways\PayPal;

/**
 * Determines whether or not Buy Now is enabled for PayPal.
 *
 * @since 2.11
 * @return bool
 */
function is_buy_now_enabled() {
	return cs_shop_supports_buy_now() && array_key_exists( 'paypal_commerce', cs_get_enabled_payment_gateways() );
}

/**
 * Sets the gateway to `paypal_commerce` when building straight to gateway data.
 * This is technically already set via `cs_build_straight_to_gateway_data()` but we want
 * to make 100% sure we override PayPal Standard when PayPal Commerce is enabled.
 *
 * @param array $purchase_data
 *
 * @since 2.11
 * @return array
 */
function straight_to_gateway_data( $purchase_data ) {
	if ( is_buy_now_enabled() ) {
		$_REQUEST['cs-gateway']  = 'paypal_commerce';
		$purchase_data['gateway'] = 'paypal_commerce';
	}

	return $purchase_data;
}

add_filter( 'cs_straight_to_gateway_purchase_data', __NAMESPACE__ . '\straight_to_gateway_data' );

/**
 * Adds the `cs-paypal-checkout-buy-now` class to qualified shortcodes.
 *
 * @param array $args
 *
 * @since 2.11
 * @return array
 */
function cs_maybe_add_purchase_link_class( $args ) {
	if ( ! is_buy_now_enabled() ) {
		return $args;
	}

	// Don't add class if "Free Downloads" is active and available for this download.
	if ( function_exists( 'cs_free_downloads_use_modal' ) ) {
		if ( cs_free_downloads_use_modal( $args['download_id'] ) && ! cs_has_variable_prices( $args['download_id'] ) ) {
			return $args;
		}
	}

	// Don't add class if Recurring is enabled for this download.
	if ( function_exists( 'cs_recurring' ) ) {
		// Overall download is recurring.
		if ( cs_recurring()->is_recurring( $args['download_id'] ) ) {
			return $args;
		}

		// Price ID is recurring.
		if ( ! empty( $args['price_id'] ) && cs_recurring()->is_price_recurring( $args['download_id'], $args['price_id'] ) ) {
			return $args;
		}
	}

	if ( ! empty( $args['direct'] ) ) {
		$args['class'] .= ' cs-paypal-checkout-buy-now';
	}

	return $args;
}

add_filter( 'cs_purchase_link_args', __NAMESPACE__ . '\cs_maybe_add_purchase_link_class' );

/**
 * Registers PayPal Commerce JavaScript if using "direct" buy now links.
 *
 * @param int   $download_id ID of the download.
 * @param array $args        Purchase link arguments.
 *
 * @since 2.11
 */
function maybe_enable_buy_now_js( $download_id, $args ) {
	if ( ! empty( $args['direct'] ) && is_buy_now_enabled() ) {
		register_js( true );
		$timestamp = time();
		?>
		<input type="hidden" name="cs_process_paypal_nonce" value="<?php echo esc_attr( wp_create_nonce( 'cs_process_paypal' ) ); ?>">
		<input type="hidden" name="cs-process-paypal-token" data-timestamp="<?php echo esc_attr( $timestamp ); ?>" data-token="<?php echo esc_attr( \CS\Utils\Tokenizer::tokenize( $timestamp ) ); ?>" />
		<?php
	}
}

add_action( 'cs_purchase_link_end', __NAMESPACE__ . '\maybe_enable_buy_now_js', 10, 2 );
