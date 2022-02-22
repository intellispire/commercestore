<?php
/**
 * Webhook Event: BILLING.SUBSCRIPTION.CANCELLED
 *
 * @package    cs-recurring
 * @subpackage Gateways\PayPal
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    GPL2+
 * @since      2.11
 */

namespace CS_Recurring\Gateways\PayPal;

class Billing_Subscription_Cancelled extends Billing_Subscription {

	/**
	 * Processes a cancellation webhook.
	 *
	 * @since 2.11
	 * @throws \Exception
	 */
	protected function process_event() {
		$subscription = $this->get_subscription_from_event();
		$subscription->cancel();
	}
}
