<?php
/**
 * Webhook Event: PAYMENT.CAPTURE.DENIED
 *
 * @package    commercestore
 * @subpackage Gateways\PayPal\Webhooks\Events
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    GPL2+
 * @since      2.11
 */

namespace CS\Gateways\PayPal\Webhooks\Events;

class Payment_Capture_Denied extends Webhook_Event {

	/**
	 * Processes the webhook event
	 *
	 * @since 2.11
	 *
	 * @throws \Exception
	 */
	protected function process_event() {
		$order = $this->get_order_from_capture();

		cs_update_order_status( $order->id, 'failed' );

		cs_add_note( array(
			'object_type' => 'order',
			'object_id'   => $order->id,
			'content'     => __( 'PayPal transaction denied.', 'commercestore' ),
		) );
	}
}
