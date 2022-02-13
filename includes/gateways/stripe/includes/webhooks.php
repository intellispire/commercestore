<?php
/**
 * Webhooks.
 *
 * @package CS_Stripe
 * @since   2.7.0
 */

/**
 * Listen for Stripe Webhooks.
 *
 * @since 1.5
 */
function csx_stripe_event_listener() {
	if ( ! isset( $_GET['cs-listener'] ) || 'stripe' !== $_GET['cs-listener'] ) {
		return;
	}

	try {
		// Retrieve the request's body and parse it as JSON.
		$body = @file_get_contents( 'php://input' );
		$event = json_decode( $body );

		if ( isset( $event->id ) ) {
			$event = csx_api_request( 'Event', 'retrieve', $event->id );
		} else {
			throw new \Exception( esc_html__( 'Unable to find Event', 'csx' ) );
		}

		// Handle events.
		//
		switch ( $event->type ) {

			// Charge succeeded. Update CS Payment address.
			case 'charge.succeeded' :
				$charge     = $event->data->object;
				$payment_id = cs_get_purchase_id_by_transaction_id( $charge->id );
				$payment    = new CS_Payment( $payment_id );

				if ( $payment && $payment->ID > 0 ) {
					$payment->address = array(
						'line1'   => $charge->billing_details->address->line1,
						'line2'   => $charge->billing_details->address->line2,
						'state'   => $charge->billing_details->address->state,
						'city'    => $charge->billing_details->address->city,
						'zip'     => $charge->billing_details->address->postal_code,
						'country' => $charge->billing_details->address->country,
					);

					$payment->save();
				}

				break;

			// Charge refunded. Ensure CS Payment status is correct.
			case 'charge.refunded':
				$charge     = $event->data->object;
				$payment_id = cs_get_purchase_id_by_transaction_id( $charge->id );
				$payment    = new CS_Payment( $payment_id );

				// This is an uncaptured PaymentIntent, not a true refund.
				if ( ! $charge->captured ) {
					return;
				}

				if ( $payment && $payment->ID > 0 ) {

					// If this was completely refunded, set the status to refunded.
					if ( $charge->refunded ) {
						$payment->status = 'refunded';
						$payment->save();
						// Translators: The charge ID from Stripe that is being refunded.
						$payment->add_note( sprintf( __( 'Charge %s has been fully refunded in Stripe.', 'csx' ), $charge->id ) );

						// If this was partially refunded, don't change the status.
					} else {
						// Translators: The charge ID from Stripe that is being partially refunded.
						$payment->add_note( sprintf( __( 'Charge %s partially refunded in Stripe.', 'csx' ), $charge->id ) );
					}
				}

				break;

			// Review started.
			case 'review.opened' :
				$is_live = ! cs_is_test_mode();
				$review  = $event->data->object;

				// Make sure the modes match.
				if ( $is_live !== $review->livemode ) {
					return;
				}

				$charge = $review->charge;

				// Get the charge from the PaymentIntent.
				if ( ! $charge ) {
					$payment_intent = $review->payment_intent;

					if ( ! $payment_intent ) {
						return;
					}

					$payment_intent = csx_api_request( 'PaymentIntent', 'retrieve', $payment_intent );
					$charge         = $payment_intent->charges->data[0]->id;
				}

				$payment_id = cs_get_purchase_id_by_transaction_id( $charge );
				$payment    = new CS_Payment( $payment_id );

				if ( $payment && $payment->ID > 0 ) {
					$payment->add_note( sprintf( __( 'Stripe Radar review opened with a reason of %s.', 'csx' ), $review->reason ) );
					$payment->save();

					do_action( 'cs_stripe_review_opened', $review, $payment_id );
				}

				break;

			// Review closed.
			case 'review.closed' :
				$is_live = ! cs_is_test_mode();
				$review  = $event->data->object;

				// Make sure the modes match
				if ( $is_live !== $review->livemode ) {
					return;
				}

				$charge = $review->charge;

				// Get the charge from the PaymentIntent.
				if ( ! $charge ) {
					$payment_intent = $review->payment_intent;

					if ( ! $payment_intent ) {
						return;
					}

					$payment_intent = csx_api_request( 'PaymentIntent', 'retrieve', $payment_intent );
					$charge         = $payment_intent->charges->data[0]->id;
				}

				$payment_id = cs_get_purchase_id_by_transaction_id( $charge );
				$payment    = new CS_Payment( $payment_id );

				if ( $payment && $payment->ID > 0 ) {
					$payment->add_note( sprintf( __( 'Stripe Radar review closed with a reason of %s.', 'csx' ), $review->reason ) );
					$payment->save();

					do_action( 'cs_stripe_review_closed', $review, $payment_id );
				}

				break;
		}

		do_action( 'csx_stripe_event_' . $event->type, $event );

		// Nothing failed, mark complete.
		status_header( 200 );
		die( esc_html( 'CS Stripe: ' . $event->type ) );

		// Fail, allow a retry.
	} catch ( \Exception $e ) {
		status_header( 500 );
		die( '-2' );
	}
}
add_action( 'init', 'csx_stripe_event_listener' );
