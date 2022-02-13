<?php
/**
 *  CommerceStore Template File for the Subscriptions section of [cs_receipt]
 *
 * @description: Place this template file within your theme directory under /my-theme/cs_templates/
 *
 * @copyright  : http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since      : 2.4
 */

global $cs_receipt_args;

$payment       = cs_get_payment( $cs_receipt_args['id'] );
$db            = new CS_Subscriptions_DB;
$args          = array(
	'parent_payment_id' => $payment->ID,
	'order'             => 'ASC'
);

$subscriptions = $db->get_subscriptions( $args );

//Sanity check: ensure this is a subscription download
if ( empty( $subscriptions ) ) {
	return;
}
?>

<h3><?php esc_html_e( 'Subscription Details', 'cs-recurring' ); ?></h3>

<?php do_action( 'cs_subscription_receipt_before_table', $payment ); ?>

<table id="cs_subscription_receipt">
	<thead>
	<tr>
		<?php do_action( 'cs_subscription_receipt_header_before' ); ?>
		<th><?php _e( 'Subscription', 'cs-recurring' ); ?></th>
		<th><?php _e( 'Renewal Date', 'cs-recurring' ); ?></th>
		<th><?php _e( 'Initial Amount', 'cs-recurring' ); ?></th>
		<th><?php _e( 'Times Billed', 'cs-recurring' ); ?></th>
		<th><?php _e( 'Status', 'cs-recurring' ); ?></th>
		<?php do_action( 'cs_subscription_receipt_header_after' ); ?>
	</tr>
	</thead>
	<tbody>
	<?php foreach ( $subscriptions as $subscription ) :
		//Set vars
		$title        = get_the_title( $subscription->product_id );
		$renewal_date = ! empty( $subscription->expiration ) ? date_i18n( get_option( 'date_format' ), strtotime( $subscription->expiration ) ) : __( 'N/A', 'cs-recurring' );
		$frequency    = CS_Recurring()->get_pretty_subscription_frequency( $subscription->period );
		?>
		<tr>
			<td>
				<span class="cs_subscription_name"><?php echo $title; ?></span><br/>
				<span class="cs_subscription_billing_cycle"><?php echo cs_currency_filter( cs_format_amount( $subscription->recurring_amount ), cs_get_payment_currency_code( $payment->ID ) ) . ' / ' . $frequency; ?></span>
			</td>
			<td>
				<?php if( 'trialling' == $subscription->status ) : ?>
					<?php _e( 'Trialling Until:', 'cs-recurring' ); ?>
				<?php endif; ?>
				<span class="cs_subscription_renewal_date"><?php echo $renewal_date; ?></span>
			</td>
			<td>
				<span class="cs_subscription_initial_amount"><?php echo cs_currency_filter( cs_format_amount( $subscription->initial_amount ), cs_get_payment_currency_code( $payment->ID ) ); ?></span>
			</td>
			<td>
				<span class="cs_subscription_times_billed"><?php echo $subscription->get_times_billed() . ' / ' . ( ( $subscription->bill_times == 0 ) ? __( 'Until cancelled', 'cs-recurring' ) : $subscription->bill_times ); ?></span>
			</td>
			<td>
				<span class="cs_subscription_status"><?php echo $subscription->get_status_label(); ?></span>
			</td>
		</tr>
	<?php endforeach; ?>
	</tbody>
</table>
<?php
do_action( 'cs_subscription_receipt_after_table', $payment );
