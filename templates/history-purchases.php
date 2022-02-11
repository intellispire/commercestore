<?php
/**
 * Shortcode: Purchase History - [purchase_history]
 *
 * @package CS
 * @category Template
 *
 * @since 3.0 Allow details link to appear for `partially_refunded` orders.
 */

if ( ! empty( $_GET['cs-verify-success'] ) ) : ?>
	<p class="cs-account-verified cs_success">
		<?php esc_html_e( 'Your account has been successfully verified!', 'commercestore' ); ?>
	</p>
	<?php
endif;
/**
 * This template is used to display the purchase history of the current user.
 */
if ( ! is_user_logged_in() ) {
	return;
}
$page   = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
$orders = cs_get_orders(
	array(
		'user_id' => get_current_user_id(),
		'number'  => 20,
		'offset'  => 20 * ( intval( $page ) - 1 ),
		'type'    => 'sale',
	)
);
if ( $orders ) :
	do_action( 'cs_before_order_history', $orders );
	?>
	<table id="cs_user_history" class="cs-table">
		<thead>
			<tr class="cs_purchase_row">
				<?php do_action( 'cs_purchase_history_header_before' ); ?>
				<th class="cs_purchase_id"><?php esc_html_e( 'ID', 'commercestore' ); ?></th>
				<th class="cs_purchase_date"><?php esc_html_e( 'Date', 'commercestore' ); ?></th>
				<th class="cs_purchase_amount"><?php esc_html_e( 'Amount', 'commercestore' ); ?></th>
				<th class="cs_purchase_details"><?php esc_html_e( 'Details', 'commercestore' ); ?></th>
				<?php do_action( 'cs_purchase_history_header_after' ); ?>
			</tr>
		</thead>
		<?php foreach ( $orders as $order ) : ?>
			<tr class="cs_purchase_row">
				<?php do_action( 'cs_order_history_row_start', $order ); ?>
				<td class="cs_purchase_id">#<?php echo esc_html( $order->get_number() ); ?></td>
				<td class="cs_purchase_date"><?php echo esc_html( cs_date_i18n( CS()->utils->date( $order->date_created, null, true )->toDateTimeString() ) ); ?></td>
				<td class="cs_purchase_amount">
					<span class="cs_purchase_amount"><?php echo esc_html( cs_display_amount( $order->total, $order->currency ) ); ?></span>
				</td>
				<td class="cs_purchase_details">
					<?php
					$link_text = ! in_array( $order->status, array( 'complete', 'partially_refunded' ), true ) ? __( 'View Details', 'commercestore' ) : __( 'View Details and Downloads', 'commercestore' );
					?>
					<a href="<?php echo esc_url( add_query_arg( 'payment_key', $order->payment_key, cs_get_success_page_uri() ) ); ?>"><?php echo esc_html( $link_text ); ?></a>
					<?php if ( ! in_array( $order->status, array( 'complete' ), true ) ) : ?>
						| <span class="cs_purchase_status <?php echo esc_attr( $order->status ); ?>"><?php echo esc_html( cs_get_status_label( $order->status ) ); ?></span>
					<?php endif; ?>
					<?php
					$recovery_url = $order->get_recovery_url();
					if ( $recovery_url ) :
						?>
						&mdash; <a href="<?php echo esc_url( $recovery_url ); ?>"><?php esc_html_e( 'Complete Purchase', 'commercestore' ); ?></a>
						<?php
					endif;
					?>
				</td>
				<?php do_action( 'cs_order_history_row_end', $order ); ?>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
	$count = cs_count_orders(
		array(
			'user_id' => get_current_user_id(),
			'type'    => 'sale',
		)
	);
	echo cs_pagination(
		array(
			'type'  => 'purchase_history',
			'total' => ceil( $count / 20 ), // 20 items per page
		)
	);
	do_action( 'cs_after_order_history', $orders );
	?>
<?php else : ?>
	<p class="cs-no-purchases"><?php esc_html_e( 'You have not made any purchases.', 'commercestore' ); ?></p>
	<?php
endif;
