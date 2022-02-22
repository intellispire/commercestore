<?php

/**
 * Gets the (first) subscription related to an order
 *
 * @since 2.10.4
 * @param \CS\Orders\Order $order The order object (CS 3.0)
 * @return array|bool              Returns an array of subscriptions, or false.
 */
function cs_recurring_get_order_subscriptions( $order ) {
	$is_sub = cs_get_order_meta( $order->id, '_cs_subscription_payment', true );
	$subs   = false;
	$args   = array(
		'status' => array( 'active', 'trialling' ),
	);

	// If this payment is the parent payment of a subscription.
	if ( $is_sub ) {

		$subs_db                   = new CS_Subscriptions_DB();
		$args['parent_payment_id'] = $order->id;

		return $subs_db->get_subscriptions( $args );
	}

	// If this payment has a parent payment, and is possibly a renewal payment.
	if ( $order->parent ) {

		// Check if there's a sub ID attached to this payment.
		$sub_id = cs_get_order_meta( $order->id, 'subscription_id', true );
		if ( $sub_id ) {
			$subscription = new CS_Subscription( $sub_id );
			if ( $subscription ) {
				return array( $subscription );
			}
		}

		// If no subscription was found attached to this payment, try searching subscriptions using the parent payment ID.
		$subs_db                   = new CS_Subscriptions_DB();
		$args['parent_payment_id'] = $order->parent;

		return $subs_db->get_subscriptions( $args );
	}

	return $subs;
}
