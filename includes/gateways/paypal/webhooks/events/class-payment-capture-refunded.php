<?php
/**
 * Webhook Events:
 *
 * - PAYMENT.CAPTURE.REFUNDED - Merchant refunds a sale.
 * - PAYMENT.CAPTURE.REVERSED - PayPal reverses a sale.
 *
 * @package    commercestore
 * @subpackage Gateways\PayPal\Webhooks\Events
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    GPL2+
 * @since      2.11
 */

namespace CS\Gateways\PayPal\Webhooks\Events;

use CS\Gateways\PayPal\Exceptions\API_Exception;
use CS\Gateways\PayPal\Exceptions\Authentication_Exception;

class Payment_Capture_Refunded extends Webhook_Event {

	/**
	 * Processes the event.
	 *
	 * @throws API_Exception
	 * @throws Authentication_Exception
	 * @throws \Exception
	 *
	 * @since 2.11
	 */
	protected function process_event() {
		// Bail if this refund transaction already exists.
		if ( $this->refund_transaction_exists() ) {
			cs_debug_log( 'PayPal Commerce - Exiting webhook, as refund transaction already exists.' );

			return;
		}

		$order = $this->get_order_from_refund();

		if ( 'refunded' === $order->status ) {
			cs_debug_log( 'PayPal Commerce - Exiting webhook, as payment status is already refunded.' );

			return;
		}

		$order_amount    = cs_get_payment_amount( $order->id );
		$refunded_amount = isset( $this->event->resource->amount->value ) ? $this->event->resource->amount->value : $order_amount;
		$currency        = isset( $this->event->resource->amount->currency_code ) ? $this->event->resource->amount->currency_code : $order->currency;

		/* Translators: %1$s - Amount refunded; %2$s - Original payment ID; %3$s - Refund transaction ID */
		$payment_note = sprintf(
			esc_html__( 'Amount: %1$s; Payment transaction ID: %2$s; Refund transaction ID: %3$s', 'commercestore' ),
			cs_currency_filter( cs_format_amount( $refunded_amount ), $currency ),
			esc_html( $order->get_transaction_id() ),
			esc_html( $this->event->resource->id )
		);

		// Partial refund.
		if ( (float) $refunded_amount < (float) $order_amount ) {
			cs_add_note( array(
				'object_type' => 'order',
				'object_id'   => $order->id,
				'content'     => __( 'Partial refund processed in PayPal.', 'commercestore' ) . ' ' . $payment_note,
			) );
			cs_update_order_status( $order->id, 'partially_refunded' );
		} else {
			// Full refund.
			cs_add_note( array(
				'object_type' => 'order',
				'object_id'   => $order->id,
				'content'     => __( 'Full refund processed in PayPal.', 'commercestore' ) . ' ' . $payment_note,
			) );
			cs_update_order_status( $order->id, 'refunded' );
		}
	}

	/**
	 * Determines whether a transaction record exists for this refund.
	 *
	 * @since 3.0
	 *
	 * @return bool
	 * @throws \Exception
	 */
	private function refund_transaction_exists() {
		if ( ! isset( $this->event->resource->id ) ) {
			throw new \Exception( 'No resource ID found.', 200 );
		}

		$transaction = cs_get_order_transaction_by( 'transaction_id', $this->event->resource->id );

		return ! empty( $transaction );
	}
}
