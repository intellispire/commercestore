<?php
/**
 * Order Overview: Actions
 *
 * @package     CS
 * @subpackage  Admin/Views
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

$is_refundable    = cs_is_order_refundable( $order->id );
$is_override      = cs_is_order_refundable_by_override( $order->id );
$is_window_passed = cs_is_order_refund_window_passed( $order->id );

if ( true === cs_is_add_order_page() ) :
?>
	<button
		id="add-adjustment"
		class="button button-secondary"
	>
		<?php echo esc_html_x( 'Add Adjustment', 'Apply an adjustment to an order', 'commercestore' ); ?>
	</button>

	<?php if ( true === cs_has_active_discounts() ) : ?>
	<button
		id="add-discount"
		class="button button-secondary"
	>
		<?php echo esc_html_x( 'Add Discount', 'Apply a discount to an order', 'commercestore' ); ?>
	</button>
	<?php endif; ?>

	<button
		id="add-item"
		class="button button-secondary"
		autofocus
	>
		<?php echo esc_html( sprintf( __( 'Add %s', 'commercestore' ), cs_get_label_singular() ) ); ?>
	</button>
<?php elseif ( 'refunded' !== $order->status && cs_get_order_total( $order->id ) > 0 ) : ?>
	<div class="cs-order-overview-actions__locked">
		<?php esc_html_e( 'Order items cannot be modified.', 'commercestore' ); ?>
		<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Issue a refund to adjust the net total for this order.', 'commercestore' ); ?>"></span>
	</div>

	<div class="cs-order-overview-actions__refund">
		<?php if ( 'amazon' === $order->gateway ) : ?>
			<span class="dashicons dashicons-lock" title="<?php esc_attr_e( 'Amazon orders must be refunded at the gateway.', 'commercestore' ); ?>"></span>
		<?php elseif ( true === $is_refundable && true === $is_override && true === $is_window_passed ) : ?>
			<span class="cs-help-tip dashicons dashicons-unlock" title="<?php esc_attr_e( 'The refund window for this Order has passed; however, you have the ability to override this.', 'commercestore' ); ?>"></span>
		<?php elseif ( false === $is_refundable && true === $is_window_passed ) : ?>
			<span class="cs-help-tip dashicons dashicons-lock" title="<?php esc_attr_e( 'The refund window for this Order has passed.', 'commercestore' ); ?>"></span>
		<?php endif; ?>

		<button
			id="refund"
			class="button button-secondary cs-refund-order"
			<?php if ( false === $is_refundable && false === $is_override ) : ?>
				disabled
			<?php endif; ?>
		>
			<?php esc_html_e( 'Initialize Refund', 'commercestore' ); ?>
		</button>
	</div>
	<?php if ( 'amazon' === $order->gateway ) : ?>
		<div class="cs-order-overview-actions__notice notice notice-warning">
			<p><?php esc_attr_e( 'Orders placed through the Amazon gateway must be refunded through Amazon. The order status can then be updated manually.', 'commercestore' ); ?></p>
		</div>
	<?php endif; ?>
<?php endif; ?>
