<?php
/**
 * Manage the Stripe API PHP bindings usage.
 *
 * @package CS_Stripe
 * @since   2.7.0
 */

use \Stripe\Stripe;

/**
 * Implements a wrapper for the Stripe API PHP bindings.
 *
 * @since 2.7.0
 */
class CS_Stripe_API {

	/**
	 * Configures the Stripe API before each request.
	 *
	 * @since 2.7.0
	 */
	public function __construct() {
		$this->set_api_key();
		$this->set_app_info();
		$this->set_api_version();
	}

	/**
	 * Makes an API request.
	 *
	 * Requires a Stripe object and method, with optional additional arguments.
	 *
	 * @since 2.7.0
	 *
	 * @link https://github.com/stripe/stripe-php
	 *
	 * @throws \CS_Stripe_Utils_Exceptions_Stripe_Object_Not_Found When attempting to call an object or method that is not available.
	 * @throws \Stripe\Error                                        When any Stripe-related error occurs.
	 *
	 * @param string $object Stripe object, such as Customer, Subscription, PaymentMethod, etc.
	 * @param string $method Method to call on the object, such as update, retrieve, etc.
	 * @param mixed ...$args Additional arguments to pass to the request.
	 * @return \Stripe\StripeObject 
	 */
	public function request( $object, $method ) {
		$classname = 'Stripe\\' . $object;

		// Retrieve additional arguments.
		$args = func_get_args();
		unset( $args[0] ); // Removes $object.
		unset( $args[1] ); // Removes $method.

		// Reset keys.
		$args = array_values( $args );

		if ( ! is_callable( array( $classname, $method ) ) ) {
			throw new CS_Stripe_Utils_Exceptions_Stripe_Object_Not_Found( sprintf( esc_html__( 'Unable to call %1$s::%2$s', 'commercestore' ), $classname, $method ) );
		}

		// @todo Filter arguments and per-request options?
		//
		// Need to account for:
		//
		// ::retrieve( array() );
		// ::retrieve( array(), array() );
		// ::retrieve( '123' );
		// ::retrieve( '123', array() );
		// ::update( '123', array() );
		// ::update( '123', array(), array() );

		return call_user_func_array( array( $classname, $method ), $args );
	}

	/**
	 * Sets API key for all proceeding requests.
	 *
	 * @since 2.7.0
	 */
	private function set_api_key() {
		$secret_key = cs_get_option( ( cs_is_test_mode() ? 'test' : 'live' ) . '_secret_key' );

		Stripe::setApiKey( trim( $secret_key ) );
	}

	/**
	 * Sets application info for all proceeding requests.
	 *
	 * @link https://stripe.com/docs/building-plugins#setappinfo
	 *
	 * @since 2.7.0
	 */
	private function set_app_info() {
		Stripe::setAppInfo(
			'WordPress Easy Digital Downloads - Stripe',
			CS_STRIPE_VERSION,
			esc_url( site_url() ),
			CS_STRIPE_PARTNER_ID
		);
	}

	/**
	 * Sets API version for all proceeding requests.
	 *
	 * @link https://stripe.com/docs/building-plugins#set-api-version
	 *
	 * @since 2.7.0
	 */
	private function set_api_version() {
		Stripe::setApiVersion( CS_STRIPE_API_VERSION );
	}
}
