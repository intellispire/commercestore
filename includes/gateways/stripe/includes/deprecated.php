<?php
/**
 * Manage deprecations.
 *
 * @package CS_Stripe
 * @since   2.7.0
 */

/**
 * Process stripe checkout submission
 *
 * @access      public
 * @since       1.0
 * @return      void
 */
function csx_process_stripe_payment( $purchase_data ) {
	_cs_deprecated_function( 'csx_process_stripe_payment', '2.7.0', 'csx_process_purchase_form', debug_backtrace() );

	return csx_process_purchase_form( $purchase_data );
}
