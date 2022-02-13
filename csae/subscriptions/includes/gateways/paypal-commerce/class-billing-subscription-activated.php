<?php
/**
 * Webhook Event: BILLING.SUBSCRIPTION.ACTIVATED
 *
 * @package    cs-recurring
 * @subpackage Gateways\PayPal
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    GPL2+
 * @since      2.11
 */

namespace CS_Recurring\Gateways\PayPal;

class Billing_Subscription_Activated extends Billing_Subscription {

	/**
	 * Handles the subscription activation event.
	 *
	 * @since 2.11
	 * @throws \Exception
	 */
	protected function process_event() {
		$subscription = $this->get_subscription_from_event();

		if ( ! $subscription->is_active() ) {
			$subscription->update( array(
				'status' => 'active'
			) );
		}
	}
}
