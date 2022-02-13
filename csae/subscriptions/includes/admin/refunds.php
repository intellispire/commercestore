<?php
/**
 * Handle subscription cancellation options on EDD Payments when applying a refund.
 *
 * This class is for working with payments in EDD.
 *
 * @package     EDD Recurring
 * @subpackage  Refunds
 * @copyright   Copyright (c) 2019, Sandhills Development
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9.3
 */

// 3.0
if ( function_exists( 'edd_get_order' ) ) {
	add_action( 'edd_after_submit_refund_table', 'edd_recurring_show_cancel_checkbox' );
	add_action( 'edd_refund_order', 'edd_recurring_cancel_subscription_on_order_refund' );
} else {
	// 2.x
	add_action( 'edd_view_order_details_before', 'edd_recurring_cancel_subscription_during_refund_option', 100 );
	add_action( 'edd_post_refund_payment', 'edd_recurring_cancel_subscription_during_refund' );
}

/**
 * Load the javascript which shows the "cancel subscription" checkbox while refunding a payment.
 * This is being done here instead of through wp_enqueue_scripts because it matches the way the
 * button for "Refund Charge in Stripe" is output. See the function called "edd_stripe_admin_js".
 *
 * @access      public
 * @since       2.9.3
 * @param       int $payment_id The id of the payment being viewed, and potentially refunded.
 * @return      void
 */
function edd_recurring_cancel_subscription_during_refund_option( $payment_id = 0 ) {

	if ( ! current_user_can( 'edit_shop_payments', $payment_id ) ) {
		return;
	}

	$payment = edd_get_payment( $payment_id );

	$is_sub = edd_get_payment_meta( $payment_id, '_edd_subscription_payment' );
	$subs   = false;

	// If this payment is the parent payment of a subscription.
	if ( $is_sub ) {

		$subs_db = new EDD_Subscriptions_DB();
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
			$subs_db = new EDD_Subscriptions_DB();
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
			$('select[name=edd-payment-status]').change(function() {

				if( 'refunded' == $(this).val() ) {

					var cancel_sub_container = $(this).parent().parent().append( '<div class="edd-recurring-cancel-sub"></div>' );

					cancel_sub_container.append( '<input type="checkbox" id="edd_recurring_cancel_subscription" name="edd_recurring_cancel_subscription" value="1" style="margin-top: 0;" />' );
					cancel_sub_container.append( '<label for="edd_recurring_cancel_subscription"><?php echo esc_js( __( 'Cancel Subscription', 'edd-recurring' ) ); ?></label></div>' );

				} else {

					$('#edd_recurring_cancel_subscription').remove();
					$('label[for="edd_recurring_cancel_subscription"]').remove();

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
 * @param \EDD\Orders\Order $order The order object (EDD 3.0).
 * @return void
 */
function edd_recurring_show_cancel_checkbox( $order ) {
	$subscriptions = edd_recurring_get_order_subscriptions( $order );

	// If there are no subscriptions linked to the order, do nothing.
	if ( ! $subscriptions ) {
		return;
	}

	?>
	<div class="edd-form-group edd-recurring-cancel-sub">
	<?php
	foreach ( $subscriptions as $sub ) {
		$label = sprintf(
			/* translators: 1. The subscription ID 2. The download name. */
			__( 'Cancel Subscription ID #%1$d (%2$s)', 'edd-recurring' ),
			$sub->id,
			edd_get_download_name( $sub->product_id )
		);
		?>
		<div class="edd-form-group__control">
			<input type="checkbox" id="edd_recurring_cancel_subscription_<?php echo esc_attr( $sub->id ); ?>" name="edd_recurring_cancel_subscription[<?php echo esc_attr( $sub->id ); ?>]" class="edd-form-group__input" value="<?php echo esc_attr( $sub->id ); ?>" />
			<label for="edd_recurring_cancel_subscription_<?php echo esc_attr( $sub->id ); ?>"><?php echo esc_html( $label ); ?></label>
		</div>
		<?php
	}
	?>
	</div>
	<?php
}

/**
 * Cancels a subscription during the refund process in EDD 3.0.
 *
 * @since 2.10.4
 * @param int $order_id The original order ID.
 * @return void
 */
function edd_recurring_cancel_subscription_on_order_refund( $order_id ) {
	if ( ! current_user_can( 'edit_shop_payments' ) ) {
		return;
	}

	if ( empty( $_POST['data'] ) ) {
		return;
	}

	// Get our data out of the serialized string.
	parse_str( $_POST['data'], $form_data );
	if ( empty( $form_data['edd_recurring_cancel_subscription'] ) || ! is_array( $form_data['edd_recurring_cancel_subscription'] ) ) {
		return;
	}
	$order = edd_get_order( $order_id );
	if ( empty( $order ) ) {
		return;
	}
	$subs = edd_recurring_get_order_subscriptions( $order );
	if ( ! $subs ) {
		return;
	}

	foreach ( $subs as $sub ) {
		if ( ! $sub instanceof \EDD_Subscription ) {
			continue;
		}
		if ( ! in_array( $sub->id, $form_data['edd_recurring_cancel_subscription'] ) ) {
			continue;
		}

		// Run the cancel method in the EDD_Subscription class. This also cancels the sub at the gateway.
		$sub->cancel();

		$note = edd_add_note(
			array(
				'object_type' => 'order',
				'object_id'   => $order_id,
				/* translators: the subscription ID. */
				'content'     => sprintf( __( 'Subscription %d cancelled because of refund.', 'edd-recurring' ), $sub->id ),
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
 * @param       EDD_Payment $payment The EDD_Payment object being viewed, and potentially refunded.
 * @return      void
 */
function edd_recurring_cancel_subscription_during_refund( $payment ) {

	if ( ! current_user_can( 'edit_shop_payments' ) ) {
		return;
	}

	if ( empty( $_POST['edd_recurring_cancel_subscription'] ) ) {
		return;
	}

	$should_cancel_subscription = apply_filters( 'edd_recurring_should_cancel_subscription', true, $payment->ID );

	if ( false === $should_cancel_subscription ) {
		return;
	}

	$is_sub = edd_get_payment_meta( $payment->ID, '_edd_subscription_payment' );

	// If this payment is the parent payment of a subscription.
	if ( $is_sub ) {

		$subs_db = new EDD_Subscriptions_DB();
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
		foreach ( $subs as $edd_sub ) {

			// Run the cancel method in the EDD_Subscription class. This also cancels the sub at the gateway.
			$edd_sub->cancel();

			$payment->add_note( sprintf( __( 'Subscription %d cancelled because of refund.', 'edd-recurring' ), $edd_sub->id ) );

		}

		// If this payment has a parent payment, and is possibly a renewal payment.
	} elseif ( $payment->parent_payment ) {

		// Check if there's a sub ID attached to this payment.
		$sub_id = $payment->get_meta( 'subscription_id', true );

		// If no subscription was found attached to this payment, try searching subscriptions using the parent payment ID.
		if ( ! $sub_id ) {
			$subs_db = new EDD_Subscriptions_DB();
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

		// Get the EDD Subscription object that we want to cancel.
		$edd_sub = new EDD_Subscription( $sub_id );

		// Run the cancel method in the EDD_Subscription class. This also cancels the sub at the gateway.
		$edd_sub->cancel();

		$payment->add_note( sprintf( __( 'Subscription %d cancelled because of refund.', 'edd-recurring' ), $sub_id ) );

	}

}
