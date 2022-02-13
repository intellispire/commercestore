<?php
/**
 * Webhook Event: BILLING.SUBSCRIPTION.PAYMENT.FAILED
 *
 * @package    cs-recurring
 * @subpackage Gateways\PayPal
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    GPL2+
 * @since      2.11
 */

namespace CS_Recurring\Gateways\PayPal;

class Billing_Subscription_Payment_Failed extends Billing_Subscription {

	/**
	 * Handles subscription renewal payment failures.
	 *
	 * @since 2.11
	 * @throws \Exception
	 */
	protected function process_event() {
		$subscription = $this->get_subscription_from_event();
		$subscription->failing();

		if ( isset( $this->event->resource->billing_info->last_failed_payment ) ) {
			$subscription->add_note( sprintf(
			/* Translators: %s - information about the last failed payment */
				__( 'Failed payment details from PayPal: %s', 'cs-recurring' ),
				json_encode( $this->event->resource->billing_info->last_failed_payment )
			) );
		}

		/**
		 * Triggers after a recurring payment has failed.
		 *
		 * @param \CS_Subscription $subscription
		 */
		do_action( 'cs_recurring_payment_failed', $subscription );
	}
}
