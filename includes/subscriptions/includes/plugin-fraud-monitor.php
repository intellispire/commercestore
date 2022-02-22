<?php

/**
 * Integrates CommerceStore Recurring with the Fraud Monitor extension
 *
 * @since v2.7.1
 */
class CS_Recurring_Fraud_Monitor {


	/**
	 * Get things started
	 *
	 * @since  2.7.1
	 * @return void
	 */
	public function __construct() {

		if ( class_exists( 'CS_Fraud_Monitor' ) ) {
			// Cancel subscriptions when Fraud Monitor
			add_action( 'cs_fm_payment_confirmed_as_fraud', array( $this, 'cancel_on_fraud' ), 10, 1 );
		}

	}

	/**
	 * When a payment is confirmed as fraud, cancel any subscriptions associated with the payment.
	 *
	 * @since 2.7.1
	 * @param $payment_id
	 * @return void
	 */
	public function cancel_on_fraud( $payment_id ) {
		$args = array(
			'parent_payment_id' => $payment_id,
			'status'            => array( 'active', 'trialling' ),
		);

		$subs_db       = new CS_Subscriptions_DB;
		$subscriptions = $subs_db->get_subscriptions( $args );
		if ( $subscriptions ) {
			foreach ( $subscriptions as $sub ) {

				if( ! $sub->can_cancel() && 'manual' !== $sub->gateway ) {
					return;
				}

				$gateway = cs_recurring()->get_gateway( $sub->gateway );

				if( empty( $gateway ) ) {
					continue;
				}

				$recurring = cs_recurring();

				remove_action( 'cs_subscription_cancelled', array( $recurring::$emails, 'send_subscription_cancelled' ), 10 );

				// If we were able to cancel the subscription, log a note stating it was because of Fraud Monitor.
				if( $gateway->cancel( $sub, true ) ) {

					$note = __( 'Subscription cancelled via Fraud Monitor', 'commercestore' );
					$sub->add_note( $note );

					$sub->cancel();
				}
			}
		}
	}

}
