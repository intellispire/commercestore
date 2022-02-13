<?php
/**
 * BILLING.SUBSCRIPTION Event Abstract
 *
 * This can be used for all events where the resource type is `subscription`.
 *
 * @package   cs-recurring
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 * @since     2.11
 */

namespace CS_Recurring\Gateways\PayPal;

use CS\Gateways\PayPal\Webhooks\Events\Webhook_Event;

abstract class Billing_Subscription extends Webhook_Event {

	/**
	 * Retrieves an CommerceStore subscription object from a subscription event.
	 *
	 * @since 2.11
	 *
	 * @return \CS_Subscription
	 * @throws \Exception
	 */
	protected function get_subscription_from_event() {
		if ( empty( $this->event->resource_type ) || 'subscription' !== $this->event->resource_type ) {
			throw new \Exception( 'Invalid resource type.' );
		}

		if ( empty( $this->event->resource->id ) ) {
			throw new \Exception( 'Missing resource ID from payload.' );
		}

		$subscription = new \CS_Subscription( $this->event->resource->id, true );
		if ( empty( $subscription->id ) ) {
			throw new \Exception( sprintf(
				'Failed to locate CommerceStore subscription from PayPal profile ID: %s',
				$this->event->resource->id
			), 200 );
		}

		return $subscription;
	}

}
