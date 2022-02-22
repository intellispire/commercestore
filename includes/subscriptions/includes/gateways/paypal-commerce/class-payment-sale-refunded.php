<?php
/**
 * Webhook Event: PAYMENT.SALE.REFUNDED
 *
 * This is the same logic as `Payment_Capture_Refunded`, but slightly different, because PayPal sends
 * a separate event with a _slightly_ different object when refunding a subscription payment versus
 * a one-time transaction. (Of course.)
 *
 * @see        \CS\Gateways\PayPal\Webhooks\Events\Payment_Capture_Refunded
 *
 * @package    cs-recurring
 * @subpackage Gateways\PayPal
 * @copyright  Copyright (c) 2021, Easy Digital Downloads
 * @license    GPL2+
 * @since      2.11.3
 */

namespace CS_Recurring\Gateways\PayPal;

use CS\Gateways\PayPal\API;
use CS\Gateways\PayPal\Exceptions\API_Exception;
use CS\Gateways\PayPal\Exceptions\Authentication_Exception;
use CS\Gateways\PayPal\Webhooks\Events\Webhook_Event;

class Payment_Sale_Refunded extends Webhook_Event {

	/**
	 * Processes the event.
	 *
	 * @throws API_Exception
	 * @throws Authentication_Exception
	 *
	 * @since 2.11.3
	 */
	protected function process_event() {
		$payment = $this->get_payment_from_refund();

		if ( 'refunded' === $payment->status ) {
			cs_debug_log( 'PayPal Commerce - Exiting webhook, as payment status is already refunded.' );

			return;
		}

		$payment_amount  = cs_get_payment_amount( $payment->ID );
		$refunded_amount = isset( $this->event->resource->amount->total ) ? $this->event->resource->amount->total : $payment_amount;
		$currency        = isset( $this->event->resource->amount->currency_code ) ? $this->event->resource->amount->currency_code : $payment->currency;

		/* Translators: %1$s - Amount refunded; %2$s - Original payment ID; %3$s - Refund transaction ID */
		$payment_note = sprintf(
			esc_html__( 'Amount: %1$s; Payment transaction ID: %2$s; Refund transaction ID: %3$s', 'commercestore' ),
			cs_currency_filter( cs_format_amount( $refunded_amount ), $currency ),
			esc_html( $payment->transaction_id ),
			esc_html( $this->event->resource->id )
		);

		// Partial refund.
		if ( (float) $refunded_amount < (float) $payment_amount ) {
			cs_insert_payment_note( $payment->ID, esc_html__( 'Partial refund processed in PayPal.', 'commercestore' ) . ' ' . $payment_note );
		} else {
			// Full refund.
			cs_insert_payment_note( $payment->ID, esc_html__( 'Full refund processed in PayPal.', 'commercestore' ) . ' ' . $payment_note );
			cs_update_payment_status( $payment->ID, 'refunded' );
		}
	}

	/**
	 * Retrieves an CS_Payment record from a refund event.
	 *
	 * @since 2.11.3
	 *
	 * @return \CS_Payment
	 * @throws API_Exception
	 * @throws Authentication_Exception
	 * @throws \Exception
	 */
	protected function get_payment_from_refund() {
		cs_debug_log( sprintf( 'PayPal Commerce Webhook - get_payment_from_refund() - Resource type: %s; Resource ID: %s', $this->request->get_param( 'resource_type' ), $this->event->resource->id ) );

		if ( empty( $this->event->resource->links ) || ! is_array( $this->event->resource->links ) ) {
			throw new \Exception( 'Missing resources.', 200 );
		}

		$order_link = current( array_filter( $this->event->resource->links, function ( $link ) {
			return ! empty( $link->rel ) && 'sale' === strtolower( $link->rel );
		} ) );

		if ( empty( $order_link->href ) ) {
			throw new \Exception( 'Missing order link.', 200 );
		}

		// Based on the payment link, determine which mode we should act in.
		if ( false === strpos( $order_link->href, 'sandbox.paypal.com' ) ) {
			$mode = API::MODE_LIVE;
		} else {
			$mode = API::MODE_SANDBOX;
		}

		// Look up the full order record in PayPal.
		$api      = new API( $mode );
		$response = $api->make_request( $order_link->href, array(), array(), $order_link->method );

		if ( 200 !== $api->last_response_code ) {
			throw new API_Exception( sprintf( 'Invalid response code when retrieving order record: %d', $api->last_response_code ) );
		}

		if ( empty( $response->id ) ) {
			throw new API_Exception( 'Missing order ID from API response.' );
		}

		return $this->get_payment_from_capture_object( $response );
	}
}
