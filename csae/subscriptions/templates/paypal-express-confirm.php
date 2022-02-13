<?php global $cs_checkout_details; ?>
<div class="cs-confirm-details" id="billing_info">
	<h3><?php _e( 'Please confirm your subscription', 'cs-recurring' ); ?></h3>
	<p><strong><?php echo $cs_checkout_details['FIRSTNAME'] ?> <?php echo $cs_checkout_details['LASTNAME'] ?></strong><br />
	<?php _e( 'PayPal Status:', 'cs-recurring' ); ?> <?php echo $cs_checkout_details['PAYERSTATUS'] ?><br />
	<?php _e( 'Email:', 'cs-recurring' ); ?> <?php echo $cs_checkout_details['EMAIL'] ?></p>
</div>
<table id="order_summary" class="cs-table">
	<thead>
		<tr>
			<th><?php _e( 'Subscription', 'cs-recurring' ); ?></th>
			<th><?php _e( 'Initial Amount', 'cs-recurring' ); ?></th>
			<th><?php _e( 'Recurring Amount', 'cs-recurring' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach( $cs_checkout_details['subscriptions'] as $subscription ) : $frequency = CS_Recurring()->get_pretty_subscription_frequency( $subscription->period ); ?>
		<tr>
			<td>
				<span class="cs_subscription_name"><?php echo get_the_title( $subscription->product_id ); ?></span>
			</td>
			<td>
				<span class="cs_subscription_initial_amount"><?php echo cs_currency_filter( $subscription->initial_amount ); ?></td></span>
			<td>
				<span class="cs_subscription_billing_cycle"><?php echo cs_currency_filter( cs_format_amount( $subscription->recurring_amount ) ) . ' / ' . $frequency; ?></span>
				<?php if( $subscription->bill_times > 1 ) : ?>
					<?php $subscription->bill_times = empty( $subscription->trial_period ) ? $subscription->bill_times - 1 : $subscription->bill_times; ?>
					<br/>
					<span class="cs_subscription_bill_times"><?php printf( _n( '%d Time', '%d Times', $subscription->bill_times, 'cs-recurring' ), $subscription->bill_times ); ?></span>
				<?php endif; ?>
			</td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<form action="<?php echo esc_url( add_query_arg( 'cs-confirm', 'paypal_express' ) ); ?>" method="post">
	<input type="hidden" name="confirmation" value="yes" />
	<input type="hidden" name="payment_id" value="<?php echo esc_attr( $_GET['payment_id'] ); ?>" />
	<input type="hidden" name="token" value="<?php echo esc_attr( $_GET['token'] ); ?>" />
	<input type="hidden" name="payer_id" value="<?php echo esc_attr( $cs_checkout_details['PAYERID'] ); ?>" />
	<input type="hidden" name="cs_ppe_confirm_nonce" value="<?php echo wp_create_nonce( 'cs-ppe-confirm-nonce' ); ?>"/>
	<input type="submit" value="<?php _e( 'Confirm', 'cs-recurring' ); ?>" />
</form>