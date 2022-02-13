<?php
/**
 * Handle subscription cancellation options on CS Payments when applying a refund.
 *
 * This class is for working with payments in CS.
 *
 * @package     CS Recurring
 * @subpackage  Refunds
 * @copyright   Copyright (c) 2019, Sandhills Development
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9.3
 */

// 3.0
if ( function_exists( 'cs_get_order' ) ) {
	add_action( 'cs_after_submit_refund_table', 'cs_recurring_show_cancel_checkbox' );
	add_action( 'cs_refund_order', 'cs_recurring_cancel_subscription_on_order_refund' );
} else {
	// 2.x
	add_action( 'cs_view_order_details_before', 'cs_recurring_cancel_subscription_during_refund_option', 100 );
	add_action( 'cs_post_refund_payment', 'cs_recurring_cancel_subscription_during_refund' );
}

/**
 * Load the javascript which shows the "cancel subscription" checkbox while refunding a payment.
 * This is being done here instead of through wp_enqueue_scripts because it matches the way the
 * button for "Refund Charge in Stripe" is output. See the function called "cs_stripe_admin_js".
 *
 * @access      public
 * @since       2.9.3
 * @param       int $payment_id The id of the payment being viewed, and potentially refunded.
 * @return      void
 */
function cs_recurring_cancel_subscription_during_refund_option( $payment_id = 0 ) {

	if ( ! current_user_can( 'edit_shop_payments', $payment_id ) ) {
		return;
	}

	$payment = cs_get_payment( $payment_id );

	$is_sub = cs_get_payment_meta( $payment_id, '_cs_subscription_payment' );
	$subs   = false;

	// If this payment is the parent payment of a subscription.
	if ( $is_sub ) {

		$subs_db = new CS_Subscriptions_DB();
		$subs    = $subs_db->get_subscriptions(
			array(
				'parent_payment_id' => $payment_id,
				'order'             => 'ASC',
			)
		);

		// If this payment has a parent payment, and is possibly a renewal payment.
	} elseif ( $payment->parent_payment ) {

		// Check if there's a sub ID attached to this payment.
		$subs = $payment->get_meta( 'subscription_id', true );

		// If no subscription was found attached to this payment, try searching subscriptions using the parent payment ID.
		if ( ! $subs ) {
			$subs_db = new CS_Subscriptions_DB();
			$subs    = $subs_db->get_subscriptions(
				array(
					'parent_payment_id' => $payment->parent_payment,
					'order'             => 'ASC',
				)
			);
		}
	}

	// If there's no subscriptions here, don't output any JS.
	if ( ! $subs ) {
		return;
	}

	wp_enqueue_script( 'jquery' );

	?>
	<script type="text/javascript">
		jQuery(document).ready(function($) {
			$('select[name=cs-payment-status]').change(function() {

				if( 'refunded' == $(this).val() ) {

					var cancel_sub_container = $(this).parent().parent().append( '<div class="cs-recurring-cancel-sub"></div>' );

					cancel_sub_container.append( '<input type="checkbox" id="cs_recurring_cancel_subscription" name="cs_recurring_cancel_subscription" value="1" style="margin-top: 0;" />' );
					cancel_sub_container.append( '<label for="cs_recurring_cancel_subscription"><?php echo esc_js( __( 'Cancel Subscription', 'cs-recurring' ) ); ?></label></div>' );

				} else {

					$('#cs_recurring_cancel_subscription').remove();
					$('label[for="cs_recurring_cancel_subscription"]').remove();

				}

			});
		});
	</script>
	<?php

}

/**
 * Shows the checkbox to cancel a subscription in the refund modal.
 *
 * @since 2.10.4
 * @param \CS\Orders\Order $order The order object (CS 3.0).
 * @return void
 */
function cs_recurring_show_cancel_checkbox( $order ) {
	$subscriptions = cs_recurring_get_order_subscriptions( $order );

	// If there are no subscriptions linked to the order, do nothing.
	if ( ! $subscriptions ) {
		return;
	}

	?>
	<div class="cs-form-group cs-recurring-cancel-sub">
	<?php
	foreach ( $subscriptions as $sub ) {
		$label = sprintf(
			/* translators: 1. The subscription ID 2. The download name. */
			__( 'Cancel Subscription ID #%1$d (%2$s)', 'cs-recurring' ),
			$sub->id,
			cs_get_download_name( $sub->product_id )
		);
		?>
		<div class="cs-form-group__control">
			<input type="checkbox" id="cs_recurring_cancel_subscription_<?php echo esc_attr( $sub->id ); ?>" name="cs_recurring_cancel_subscription[<?php echo esc_attr( $sub->id ); ?>]" class="cs-form-group__input" value="<?php echo esc_attr( $sub->id ); ?>" />
			<label for="cs_recurring_cancel_subscription_<?php echo esc_attr( $sub->id ); ?>"><?php echo esc_html( $label ); ?></label>
		</div>
		<?php
	}
	?>
	</div>
	<?php
}

/**
 * Cancels a subscription during the refund process in CS 3.0.
 *
 * @since 2.10.4
 * @param int $order_id The original order ID.
 * @return void
 */
function cs_recurring_cancel_subscription_on_order_refund( $order_id ) {
	if ( ! current_user_can( 'edit_shop_payments' ) ) {
		return;
	}

	if ( empty( $_POST['data'] ) ) {
		return;
	}

	// Get our data out of the serialized string.
	parse_str( $_POST['data'], $form_data );
	if ( empty( $form_data['cs_recurring_cancel_subscription'] ) || ! is_array( $form_data['cs_recurring_cancel_subscription'] ) ) {
		return;
	}
	$order = cs_get_order( $order_id );
	if ( empty( $order ) ) {
		return;
	}
	$subs = cs_recurring_get_order_subscriptions( $order );
	if ( ! $subs ) {
		return;
	}

	foreach ( $subs as $sub ) {
		if ( ! $sub instanceof \CS_Subscription ) {
			continue;
		}
		if ( ! in_array( $sub->id, $form_data['cs_recurring_cancel_subscription'] ) ) {
			continue;
		}

		// Run the cancel method in the CS_Subscription class. This also cancels the sub at the gateway.
		$sub->cancel();

		$note = cs_add_note(
			array(
				'object_type' => 'order',
				'object_id'   => $order_id,
				/* translators: the subscription ID. */
				'content'     => sprintf( __( 'Subscription %d cancelled because of refund.', 'cs-recurring' ), $sub->id ),
				'user_id'     => get_current_user_id(),
			)
		);
	}
}

/**
 * Cancel subscription when refunding a payment, if that was selected by the admin.
 *
 * @access      public
 * @since       2.9.3
 * @param       CS_Payment $payment The CS_Payment object being viewed, and potentially refunded.
 * @return      void
 */
function cs_recurring_cancel_subscription_during_refund( $payment ) {

	if ( ! current_user_can( 'edit_shop_payments' ) ) {
		return;
	}

	if ( empty( $_POST['cs_recurring_cancel_subscription'] ) ) {
		return;
	}

	$should_cancel_subscription = apply_filters( 'cs_recurring_should_cancel_subscription', true, $payment->ID );

	if ( false === $should_cancel_subscription ) {
		return;
	}

	$is_sub = cs_get_payment_meta( $payment->ID, '_cs_subscription_payment' );

	// If this payment is the parent payment of a subscription.
	if ( $is_sub ) {

		$subs_db = new CS_Subscriptions_DB();
		$subs    = $subs_db->get_subscriptions(
			array(
				'parent_payment_id' => $payment->ID,
				'order'             => 'ASC',
			)
		);

		// If there's no subscriptions here, don't do anything here.
		if ( ! $subs ) {
			return;
		}

		// Loop through each subscription in this parent payment, cancelling each one.
		foreach ( $subs as $cs_sub ) {

			// Run the cancel method in the CS_Subscription class. This also cancels the sub at the gateway.
			$cs_sub->cancel();

			$payment->add_note( sprintf( __( 'Subscription %d cancelled because of refund.', 'cs-recurring' ), $cs_sub->id ) );

		}

		// If this payment has a parent payment, and is possibly a renewal payment.
	} elseif ( $payment->parent_payment ) {

		// Check if there's a sub ID attached to this payment.
		$sub_id = $payment->get_meta( 'subscription_id', true );

		// If no subscription was found attached to this payment, try searching subscriptions using the parent payment ID.
		if ( ! $sub_id ) {
			$subs_db = new CS_Subscriptions_DB();
			$subs    = $subs_db->get_subscriptions(
				array(
					'parent_payment_id' => $payment->parent_payment,
					'order'             => 'ASC',
				)
			);
			$sub     = reset( $subs );
			$sub_id  = $sub->id;
		}

		// If there's really just no subscription here, don't do anything here.
		if ( ! $sub_id ) {
			return;
		}

		// Get the CS Subscription object that we want to cancel.
		$cs_sub = new CS_Subscription( $sub_id );

		// Run the cancel method in the CS_Subscription class. This also cancels the sub at the gateway.
		$cs_sub->cancel();

		$payment->add_note( sprintf( __( 'Subscription %d cancelled because of refund.', 'cs-recurring' ), $sub_id ) );

	}

}
