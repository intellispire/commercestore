<?php
/**
 * Deprecated PayPal Functions
 *
 * @package    commercestore
 * @subpackage Gateways\PayPal
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    GPL2+
 * @since      3.0
 */

namespace CS\Gateways\PayPal;

/**
 * Adds a "Refund in PayPal" checkbox when switching the payment's status to "Refunded".
 *
 * @deprecated 3.0 In favor of a `cs_after_submit_refund_table` hook.
 *
 * @param int $payment_id
 *
 * @since 2.11
 * @return void
 */
function add_refund_javascript( $payment_id ) {
	_cs_deprecated_function( __FUNCTION__, '3.0', null, debug_backtrace() );

	$payment = cs_get_payment( $payment_id );

	if ( ! $payment || 'paypal_commerce' !== $payment->gateway ) {
		return;
	}

	$mode = ( 'live' === $payment->mode ) ? API::MODE_LIVE : API::MODE_SANDBOX;

	try {
		$api = new API( $mode );
	} catch ( Exceptions\Authentication_Exception $e ) {
		// If we don't have credentials.
		return;
	}

	$label = __( 'Refund Transaction in PayPal', 'commercestore' );
	?>
	<script type="text/javascript">
		jQuery( document ).ready( function ( $ ) {
			$( 'select[name=cs-payment-status]' ).change( function () {
				if ( 'refunded' === $( this ).val() ) {
					$( this ).parent().parent().append( '<input type="checkbox" id="cs-paypal-commerce-refund" name="cs-paypal-commerce-refund" value="1" style="margin-top:0">' );
					$( this ).parent().parent().append( '<label for="cs-paypal-commerce-refund"><?php echo esc_html( $label ); ?></label>' );
				} else {
					$( '#cs-paypal-commerce-refund' ).remove();
					$( 'label[for="cs-paypal-commerce-refund"]' ).remove();
				}
			} );
		} );
	</script>
	<?php
}

/**
 * Refunds the transaction in PayPal, if the option was selected.
 *
 * @deprecated 3.0 In favor of `cs_refund_order` hook.
 *
 * @param \CS_Payment $payment The payment being refunded.
 *
 * @since 2.11
 * @return void
 */
function maybe_refund_transaction( \CS_Payment $payment ) {
	_cs_deprecated_function( __FUNCTION__, '3.0', null, debug_backtrace() );

	if ( ! current_user_can( 'edit_shop_payments', $payment->ID ) ) {
		return;
	}

	if ( 'paypal_commerce' !== $payment->gateway || empty( $_POST['cs-paypal-commerce-refund'] ) ) {
		return;
	}

	// Payment status should be coming from "publish" or "revoked".
	// @todo In 3.0 use `cs_get_refundable_order_statuses()`
	if ( ! in_array( $payment->old_status, array( 'publish', 'complete', 'revoked', 'cs_subscription' ) ) ) {
		return;
	}

	// If the payment has already been refunded, bail.
	if ( $payment->get_meta( '_cs_paypal_refunded', true ) ) {
		return;
	}

	// Process the refund.
	try {
		refund_transaction( $payment );
	} catch ( \Exception $e ) {
		cs_insert_payment_note( $payment->ID, sprintf(
		/* Translators: %s - The error message */
			__( 'Failed to refund transaction in PayPal. Error Message: %s', 'commercestore' ),
			$e->getMessage()
		) );
	}
}
