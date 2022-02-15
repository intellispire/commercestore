<?php
/**
 * Payment emails.
 *
 * @package CS_Stripe
 * @since   2.7.0
 */

/**
 * Notify a customer that a Payment needs further action.
 *
 * @since 2.7.0
 *
 * @param int $payment_id CS Payment ID.
 */
function csx_preapproved_payment_needs_action_notification( $payment_id ) {
	$payment      = cs_get_payment( $payment_id );
	$payment_data = $payment->get_meta( '_cs_payment_meta', true );

	$from_name    = cs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name    = apply_filters( 'cs_purchase_from_name', $from_name, $payment_id, $payment_data );
	$from_email   = cs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email   = apply_filters( 'cs_purchase_from_address', $from_email, $payment_id, $payment_data );

	if ( empty( $to_email ) ) {
		$to_email = $payment->email;
	}

	$subject = esc_html__( 'Your Preapproved Payment Requires Action', 'commercestore' );
	$heading = cs_do_email_tags( esc_html__( 'Payment Requires Action', 'commercestore' ), $payment_id );

	$message  = esc_html__( 'Dear {name},', 'commercestore' ) . "\n\n";
	$message .= esc_html__( 'Your preapproved payment requires further action before your purchase can be completed. Please click the link below to take finalize your purchase', 'commercestore' ) . "\n\n";
	$message .= esc_url( add_query_arg( 'payment_key', $payment->key, cs_get_success_page_uri() ) );
	$message  = cs_do_email_tags( $message, $payment_id );

	/** This filter is documented in commercestore/includes/emails/template.php */
	$message = apply_filters( 'cs_email_template_wpautop', true ) ? wpautop( $message ) : $message;

	$emails = CS()->emails;

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', $heading );

	$headers = $emails->get_headers();
	$emails->__set( 'headers', $headers );

	$emails->send( $to_email, $subject, $message );
}
add_action( 'csx_preapproved_payment_needs_action', 'csx_preapproved_payment_needs_action_notification' );
