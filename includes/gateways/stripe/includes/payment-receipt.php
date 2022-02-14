<?php
/**
 * Payment receipt.
 *
 * @package CS_Stripe
 * @since   2.7.0
 */

/**
 * Output a Payment authorization form in the Payment Receipt.
 *
 * @param WP_Post $payment Payment.
 */
function csx_payment_receipt_authorize_payment_form( $payment ) {
	// CS 3.0 compat.
	if ( is_a( $payment, 'WP_Post' ) ) {
		$payment = cs_get_payment( $payment->ID );
	}

	$customer_id = $payment->get_meta( '_csx_stripe_customer_id' );
	$payment_intent_id = $payment->get_meta( '_csx_stripe_payment_intent_id' );

	if ( empty( $customer_id ) || empty( $payment_intent_id ) ) {
		return false;
	}

	if ( 'preapproval_pending' !== $payment->status ) {
		return false;
	}

	$payment_intent = csx_api_request( 'PaymentIntent', 'retrieve', $payment_intent_id );

	cs_stripe_js( true );
?>

<form
	id="csx-update-payment-method"
	data-payment-intent="<?php echo esc_attr( $payment_intent->id ); ?>"
	<?php if ( isset( $payment_intent->last_payment_error ) && isset( $payment_intent->last_payment_error->payment_method ) ) : ?>
	data-payment-method="<?php echo esc_attr( $payment_intent->last_payment_error->payment_method->id ); ?>"
	<?php endif; ?>
>
	<h3>Authorize Payment</h3>
	<p><?php esc_html_e( 'To finalize your preapproved purchase, please confirm your payment method.', 'csx' ); ?></p>

	<div id="cs_checkout_form_wrap">
		<?php
		/** This filter is documented in commercestore/includes/checkout/template.php */
		do_action( 'cs_stripe_cc_form' );
		?>

		<p>
			<input
				id="csx-update-payment-method-submit"
				type="submit"
				data-loading="<?php echo esc_attr( 'Please Wait…', 'csx' ); ?>"
				data-submit="<?php echo esc_attr( 'Authorize Payment', 'csx' ); ?>"
				value="<?php echo esc_attr( 'Authorize Payment', 'csx' ); ?>"
				class="button cs-button"
			/>
		</p>

		<div id="csx-update-payment-method-errors"></div>

	</div>
</form>

<?php
}
add_action( 'cs_payment_receipt_after_table', 'csx_payment_receipt_authorize_payment_form' );
