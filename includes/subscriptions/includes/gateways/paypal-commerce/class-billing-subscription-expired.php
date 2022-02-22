<?php
/**
 * Webhook Event: BILLING.SUBSCRIPTION.EXPIRED
 *
 * @package    cs-recurring
 * @subpackage Gateways\PayPal
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    GPL2+
 * @since      2.11
 */

namespace CS_Recurring\Gateways\PayPal;

class Billing_Subscription_Expired extends Billing_Subscription {

	/**
	 * Handles expiration webhooks.
	 *
	 * @since 2.11
	 * @throws \Exception
	 */
	protected function process_event() {
		$subscription = $this->get_subscription_from_event();

		if ( 'completed' !== $subscription->status ) {
			$subscription->expire();
		}
	}
}
