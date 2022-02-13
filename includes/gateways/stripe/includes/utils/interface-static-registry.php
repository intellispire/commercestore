<?php
/**
 * CS_Stripe_Utils_Static_Registry interface.
 *
 * @package CS_Stripe
 * @since   2.6.19
 */

/**
 * Defines the contract for a static (singleton) registry object.
 *
 * @since 2.6.19
 */
interface CS_Stripe_Utils_Static_Registry {

	/**
	 * Retrieves the one true registry instance.
	 *
	 * @since 2.6.19
	 *
	 * @return CS_Stripe_Utils_Static_Registry Registry instance.
	 */
	public static function instance();

}
