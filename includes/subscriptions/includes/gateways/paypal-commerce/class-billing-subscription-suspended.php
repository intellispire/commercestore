<?php
/**
 * Webhook Event: BILLING.SUBSCRIPTION.SUSPENDED
 *
 * @package    cs-recurring
 * @subpackage Gateways\PayPal
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    GPL2+
 * @since      2.11
 */

namespace CS_Recurring\Gateways\PayPal;

class Billing_Subscription_Suspended extends Billing_Subscription {

	/**
	 * Processes a suspension webhook.
	 *
	 * @since 2.11
	 * @throws \Exception
	 */
	protected function process_event() {
		/*
		 * This is intentionally blank for now because I'm not sure how we should be handling it.
		 */
	}
}
