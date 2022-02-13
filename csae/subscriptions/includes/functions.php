<?php

/**
 * Gets the (first) subscription related to an order
 *
 * @since 2.10.4
 * @param \EDD\Orders\Order $order The order object (EDD 3.0)
 * @return array|bool              Returns an array of subscriptions, or false.
 */
function edd_recurring_get_order_subscriptions( $order ) {
	$is_sub = edd_get_order_meta( $order->id, '_edd_subscription_payment', true );
	$subs   = false;
	$args   = array(
		'status' => array( 'active', 'trialling' ),
	);

	// If this payment is the parent payment of a subscription.
	if ( $is_sub ) {

		$subs_db                   = new EDD_Subscriptions_DB();
		$args['parent_payment_id'] = $order->id;

		return $subs_db->get_subscriptions( $args );
	}

	// If this payment has a parent payment, and is possibly a renewal payment.
	if ( $order->parent ) {

		// Check if there's a sub ID attached to this payment.
		$sub_id = edd_get_order_meta( $order->id, 'subscription_id', true );
		if ( $sub_id ) {
			$subscription = new EDD_Subscription( $sub_id );
			if ( $subscription ) {
				return array( $subscription );
			}
		}

		// If no subscription was found attached to this payment, try searching subscriptions using the parent payment ID.
		$subs_db                   = new EDD_Subscriptions_DB();
		$args['parent_payment_id'] = $order->parent;

		return $subs_db->get_subscriptions( $args );
	}

	return $subs;
}
