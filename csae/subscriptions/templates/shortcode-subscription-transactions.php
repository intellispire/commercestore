<?php
/**
 *  CommerceStore Template File for [cs_subscriptions] shortcode with the 'view_transactions' action.
 *
 * @description: Place this template file within your theme directory under /my-theme/cs_templates/
 *
 * @copyright  : http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      : 2.11.5
 */

if ( ! is_user_logged_in() ) {
	return;
}

$subscription = false;
if ( isset( $_GET['subscription_id'] ) && is_numeric( $_GET['subscription_id'] ) ) {
	$subscription_id = absint( $_GET['subscription_id'] );
	$subscription    = new CS_Subscription( $subscription_id );
}
if ( ! $subscription instanceof CS_Subscription || empty( $subscription->id ) ) {
	return;
}
$subscriber = new CS_Recurring_Subscriber( get_current_user_id(), true );
if ( empty( $subscriber->id ) || $subscription->customer_id !== $subscriber->id ) {
	return;
}
$payments   = $subscription->get_child_payments();
$payments[] = cs_get_payment( $subscription->parent_payment_id );
if ( ! $payments ) {
	return;
}
$action_url = remove_query_arg( array( 'subscription_id', 'view_transactions' ), cs_get_current_page_url() );
?>
<a href="<?php echo esc_url( $action_url ); ?>">&larr;&nbsp;<?php esc_html_e( 'Back', 'cs-recurring' ); ?></a>
<h3><?php esc_html_e( 'Transactions for Subscription #', 'cs-recurring' ); ?><?php echo esc_html( $subscription->id ); ?></h3>
<table class="cs-recurring-subscription-transactions">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Order', 'cs-recurring' ); ?></th>
			<th><?php esc_html_e( 'Order Amount', 'cs-recurring' ); ?></th>
			<th><?php esc_html_e( 'Order Date', 'cs-recurring' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php
		foreach ( $payments as $payment ) {
			?>
			<tr>
				<td>
					<a
						href="<?php echo esc_url( add_query_arg( 'payment_key', cs_get_payment_key( $payment->ID ), cs_get_success_page_uri() ) ); ?>"
					>
						<?php echo esc_html( cs_get_payment_number( $payment->ID ) ); ?>
					</a>
					<?php
					if ( $payment->ID == $subscription->parent_payment_id ) {
						echo ' ' . esc_html__( '(original order)', 'cs-recurring' );
					}
					?>
				</td>
				<td><?php echo esc_html( cs_currency_filter( cs_format_amount( cs_get_payment_amount( $payment->ID ) ), cs_get_payment_currency_code( $payment->ID ) ) ); ?></td>
				<td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $payment->date ) ) ); ?></td>
			</tr>
			<?php
		}
		?>
	</tbody>
</table>
<?php
