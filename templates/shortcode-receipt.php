<?php
/**
 * Shortcode: Receipt - [cs_receipt]
 *
 * @package CS
 * @category Template
 *
 * @since 3.0 Check status of order item when showing download link, instead of order itself.
 *            Show "Refunded" next to any refunded order items.
 */

global $cs_receipt_args;
$order = cs_get_order( $cs_receipt_args['id'] );

// Display a notice if the order was not found in the database.
if ( ! $order ) : ?>

	<div class="cs_errors cs-alert cs-alert-error">
		<?php esc_html_e( 'The specified receipt ID appears to be invalid.', 'commercestore' ); ?>
	</div>

	<?php
	return;
endif;

/**
 * Allows additional output before displaying the receipt table.
 *
 * @since 3.0
 *
 * @param \CS\Orders\Order $order          Current order.
 * @param array             $cs_receipt_args [cs_receipt] shortcode arguments.
 */
do_action( 'cs_order_receipt_before_table', $order, $cs_receipt_args );
?>
<table id="cs_purchase_receipt" class="cs-table">
	<thead>
		<?php do_action( 'cs_order_receipt_before', $order, $cs_receipt_args ); ?>

		<?php if ( filter_var( $cs_receipt_args['payment_id'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
		<tr>
			<th><strong><?php echo esc_html_x( 'Order', 'heading', 'commercestore' ); ?>:</strong></th>
			<th><?php echo esc_html( $order->get_number() ); ?></th>
		</tr>
		<?php endif; ?>
	</thead>

	<tbody>
		<tr>
			<td class="cs_receipt_payment_status"><strong><?php esc_html_e( 'Order Status', 'commercestore' ); ?>:</strong></td>
			<td class="cs_receipt_payment_status <?php echo esc_attr( strtolower( $order->status ) ); ?>"><?php echo esc_html( cs_get_status_label( $order->status ) ); ?></td>
		</tr>

		<?php if ( filter_var( $cs_receipt_args['payment_key'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
			<tr>
				<td><strong><?php esc_html_e( 'Payment Key', 'commercestore' ); ?>:</strong></td>
				<td><?php echo esc_html( $order->payment_key ); ?></td>
			</tr>
		<?php endif; ?>

		<?php if ( filter_var( $cs_receipt_args['payment_method'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
			<tr>
				<td><strong><?php esc_html_e( 'Payment Method', 'commercestore' ); ?>:</strong></td>
				<td><?php echo esc_html( cs_get_gateway_checkout_label( $order->gateway ) ); ?></td>
			</tr>
		<?php endif; ?>
		<?php if ( filter_var( $cs_receipt_args['date'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
		<tr>
			<td><strong><?php esc_html_e( 'Date', 'commercestore' ); ?>:</strong></td>
			<td><?php echo esc_html( cs_date_i18n( CS()->utils->date( $order->date_created, null, true )->toDateTimeString() ) ); ?></td>
		</tr>
		<?php endif; ?>

		<?php if ( filter_var( $cs_receipt_args['price'], FILTER_VALIDATE_BOOLEAN ) && $order->subtotal > 0 ) : ?>
			<tr>
				<td><strong><?php esc_html_e( 'Subtotal', 'commercestore' ); ?>:</strong></td>
				<td>
					<?php echo esc_html( cs_payment_subtotal( $order->id ) ); ?>
				</td>
			</tr>
		<?php endif; ?>

		<?php
		if ( filter_var( $cs_receipt_args['discount'], FILTER_VALIDATE_BOOLEAN ) ) :
			$order_discounts = $order->get_discounts();
			if ( $order_discounts ) :
				$label = _n( 'Discount', 'Discounts', count( $order_discounts ), 'commercestore' );
				?>
				<tr>
					<td colspan="2"><strong><?php echo esc_html( $label ); ?>:</strong></td>
				</tr>
				<?php
				foreach ( $order_discounts as $order_discount ) {
					$label = $order_discount->description;
					if ( 'percent' === cs_get_discount_type( $order_discount->type_id ) ) {
						$rate   = cs_format_discount_rate( 'percent', cs_get_discount_amount( $order_discount->type_id ) );
						$label .= "&nbsp;({$rate})";
					}
					?>
					<tr>
						<td><?php echo esc_html( $label ); ?></td>
						<td><?php echo esc_html( cs_display_amount( cs_negate_amount( $order_discount->total ), $order->currency ) ); ?></td>
					</tr>
					<?php
				}
				?>
				</tr>
			<?php endif; ?>
		<?php endif; ?>

		<?php
		$fees = $order->get_fees();
		if ( ! empty( $fees ) ) :
			?>
			<tr>
				<td colspan="2"><strong><?php echo esc_html( _n( 'Fee', 'Fees', count( $fees ), 'commercestore' ) ); ?>:</strong></td>
			</tr>
			<?php
			foreach ( $fees as $fee ) :
				$label = __( 'Fee', 'commercestore' );
				if ( ! empty( $fee->description ) ) {
					$label = $fee->description;
				}
				?>
				<tr>
					<td><span class="cs_fee_label"><?php echo esc_html( $label ); ?></span></td>
					<td><span class="cs_fee_amount"><?php echo esc_html( cs_display_amount( $fee->subtotal, $order->currency ) ); ?></span></td>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php if ( $order->tax > 0 ) : ?>
			<tr>
				<td><strong><?php esc_html_e( 'Tax', 'commercestore' ); ?>:</strong></td>
				<td><?php echo esc_html( cs_payment_tax( $order->id ) ); ?></td>
			</tr>
		<?php endif; ?>
		<?php
		$credits = $order->get_credits();
		if ( $credits ) {
			?>
			<tr>
				<td colspan="2"><strong><?php echo esc_html( _n( 'Credit', 'Credits', count( $credits ), 'commercestore' ) ); ?>:</strong></td>
			</tr>
			<?php
			foreach ( $credits as $credit ) {
				$label = __( 'Credit', 'commercestore' );
				if ( ! empty( $credit->description ) ) {
					$label = $credit->description;
				}
				?>
				<tr>
					<td><?php echo esc_html( $label ); ?></td>
					<td><?php echo esc_html( cs_display_amount( cs_negate_amount( $credit->total ), $order->currency ) ); ?></td>
				</tr>
				<?php
			}
		}
		?>

		<?php if ( filter_var( $cs_receipt_args['price'], FILTER_VALIDATE_BOOLEAN ) ) : ?>
			<tr>
				<td><strong><?php esc_html_e( 'Total', 'commercestore' ); ?>:</strong></td>
				<td><?php echo esc_html( cs_payment_amount( $order->id ) ); ?></td>
			</tr>
		<?php endif; ?>

		<?php
		/**
		 * Fires at the end of the order receipt `tbody`.
		 *
		 * @since 3.0
		 * @param \CS\Orders\Order $order          Current order.
		 * @param array             $cs_receipt_args [cs_receipt] shortcode arguments.
		 */
		do_action( 'cs_order_receipt_after', $order, $cs_receipt_args );
		?>
	</tbody>
</table>

<?php
/**
 * Fires after the order receipt table.
 *
 * @since 3.0
 * @param \CS\Orders\Order $order          Current order.
 * @param array             $cs_receipt_args [cs_receipt] shortcode arguments.
 */
do_action( 'cs_order_receipt_after_table', $order, $cs_receipt_args );

if ( ! filter_var( $cs_receipt_args['products'], FILTER_VALIDATE_BOOLEAN ) ) {
	return;
}
$order_items = $order->get_items();
if ( empty( $order_items ) ) {
	return;
}
?>

<h3><?php echo esc_html( apply_filters( 'cs_payment_receipt_products_title', __( 'Products', 'commercestore' ) ) ); ?></h3>

<table id="cs_purchase_receipt_products" class="cs-table">
	<thead>
		<th><?php esc_html_e( 'Name', 'commercestore' ); ?></th>
		<?php if ( cs_use_skus() ) { ?>
			<th><?php esc_html_e( 'SKU', 'commercestore' ); ?></th>
		<?php } ?>
		<?php if ( cs_item_quantities_enabled() ) : ?>
			<th><?php esc_html_e( 'Quantity', 'commercestore' ); ?></th>
		<?php endif; ?>
		<th><?php esc_html_e( 'Price', 'commercestore' ); ?></th>
	</thead>

	<tbody>
		<?php foreach ( $order_items as $key => $item ) : ?>
			<?php
			// Skip this item if we can't view it.
			if ( ! apply_filters( 'cs_user_can_view_receipt_item', true, $item ) ) {
				continue;
			}
			?>

			<tr>
				<td>
					<?php $download_files = cs_get_download_files( $item->product_id, $item->price_id ); ?>

					<div class="cs_purchase_receipt_product_name">
						<?php
						echo esc_html( $item->product_name );

						if ( ! empty( $item->status ) && 'complete' !== $item->status ) {
							echo ' &ndash; ' . esc_html( cs_get_status_label( $item->status ) );
						}
						?>
					</div>
					<?php
					$notes = cs_get_product_notes( $item->product_id );
					if ( ! empty( $notes ) ) : ?>
						<div class="cs_purchase_receipt_product_notes"><?php echo wp_kses_post( wpautop( $notes ) ); ?></div>
					<?php endif; ?>

					<?php if ( 'refunded' !== $item->status && cs_receipt_show_download_files( $item->product_id, $cs_receipt_args, $item ) ) : ?>
					<ul class="cs_purchase_receipt_files">
						<?php
						if ( ! empty( $download_files ) && is_array( $download_files ) ) :
							foreach ( $download_files as $filekey => $file ) :
								?>
								<li class="cs_download_file">
									<a href="<?php echo esc_url( cs_get_download_file_url( $order->payment_key, $order->email, $filekey, $item->product_id, $item->price_id ) ); ?>" class="cs_download_file_link"><?php echo esc_html( cs_get_file_name( $file ) ); ?></a>
								</li>
								<?php
								/**
								 * Fires at the end of the order receipt files list.
								 *
								 * @since 3.0
								 * @param int   $filekey          Index of array of files returned by cs_get_download_files() that this download link is for.
								 * @param array $file             The array of file information.
								 * @param int   $item->product_id The product ID.
								 * @param int   $order->id        The order ID.
								 */
								do_action( 'cs_order_receipt_files', $filekey, $file, $item->product_id, $order->id );
							endforeach;
						elseif ( cs_is_bundled_product( $item->product_id ) ) :
							$bundled_products = cs_get_bundled_products( $item->product_id, $item->price_id );

							foreach ( $bundled_products as $bundle_item ) :
								?>

								<li class="cs_bundled_product">
									<span class="cs_bundled_product_name"><?php echo esc_html( cs_get_bundle_item_title( $bundle_item ) ); ?></span>
									<ul class="cs_bundled_product_files">
										<?php
										$download_files = cs_get_download_files( cs_get_bundle_item_id( $bundle_item ), cs_get_bundle_item_price_id( $bundle_item ) );

										if ( $download_files && is_array( $download_files ) ) :
											foreach ( $download_files as $filekey => $file ) :
												?>
												<li class="cs_download_file">
													<a href="<?php echo esc_url( cs_get_download_file_url( $order->payment_key, $order->email, $filekey, $bundle_item, $item->price_id ) ); ?>" class="cs_download_file_link"><?php echo esc_html( cs_get_file_name( $file ) ); ?></a>
												</li>
												<?php
												/**
												 * Fires at the end of the order receipt bundled files list.
												 *
												 * @since 3.0
												 * @param int   $filekey          Index of array of files returned by cs_get_download_files() that this download link is for.
												 * @param array $file             The array of file information.
												 * @param int   $item->product_id The product ID.
												 * @param array $bundle_item      The array of information about the bundled item.
												 * @param int   $order->id        The order ID.
												 */
												do_action( 'cs_order_receipt_bundle_files', $filekey, $file, $item->product_id, $bundle_item, $order->id );
											endforeach;
										else :
											echo '<li>' . esc_html__( 'No downloadable files found for this bundled item.', 'commercestore' ) . '</li>';
										endif;
										?>
									</ul>
								</li>
								<?php
							endforeach;

						else :
							echo '<li>' . esc_html( apply_filters( 'cs_receipt_no_files_found_text', __( 'No downloadable files found.', 'commercestore' ), $item->product_id ) ) . '</li>';
						endif;
						?>
					</ul>
					<?php endif; ?>

					<?php
					/**
					 * Allow extensions to extend the product cell.
					 * @since 3.0
					 * @param \CS\Orders\Order_Item $item The current order item.
					 * @param \CS\Orders\Order $order     The current order object.
					 */
					do_action( 'cs_order_receipt_after_files', $item, $order );
					?>
				</td>
				<?php if ( cs_use_skus() ) : ?>
					<td><?php echo esc_html( cs_get_download_sku( $item->product_id ) ); ?></td>
				<?php endif; ?>
				<?php if ( cs_item_quantities_enabled() ) { ?>
					<td><?php echo esc_html( $item->quantity ); ?></td>
				<?php } ?>
				<td>
					<?php echo esc_html( cs_display_amount( $item->total, $order->currency ) ); ?>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>

</table>
