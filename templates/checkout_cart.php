<?php
/**
 *  This template is used to display the Checkout page when items are in the cart
 */

global $post; ?>
<table id="cs_checkout_cart" <?php if ( ! cs_is_ajax_disabled() ) { echo 'class="ajaxed"'; } ?>>
	<thead>
		<tr class="cs_cart_header_row">
			<?php do_action( 'cs_checkout_table_header_first' ); ?>
			<th class="cs_cart_item_name"><?php _e( 'Item Name', 'commercestore' ); ?></th>
			<th class="cs_cart_item_price"><?php _e( 'Item Price', 'commercestore' ); ?></th>
			<th class="cs_cart_actions"><?php _e( 'Actions', 'commercestore' ); ?></th>
			<?php do_action( 'cs_checkout_table_header_last' ); ?>
		</tr>
	</thead>
	<tbody>
		<?php $cart_items = cs_get_cart_contents(); ?>
		<?php do_action( 'cs_cart_items_before' ); ?>
		<?php if ( $cart_items ) : ?>
			<?php foreach ( $cart_items as $key => $item ) : ?>
				<tr class="cs_cart_item" id="cs_cart_item_<?php echo esc_attr( $key ) . '_' . esc_attr( $item['id'] ); ?>" data-download-id="<?php echo esc_attr( $item['id'] ); ?>">
					<?php do_action( 'cs_checkout_table_body_first', $item ); ?>
					<td class="cs_cart_item_name">
						<?php
							if ( current_theme_supports( 'post-thumbnails' ) && has_post_thumbnail( $item['id'] ) ) {
								echo '<div class="cs_cart_item_image">';
									echo get_the_post_thumbnail( $item['id'], apply_filters( 'cs_checkout_image_size', array( 25,25 ) ) );
								echo '</div>';
							}
							$item_title = cs_get_cart_item_name( $item );
							echo '<span class="cs_checkout_cart_item_title">' . esc_html( $item_title ) . '</span>';

							/**
							 * Runs after the item in cart's title is echoed
							 * @since 2.6
							 *
							 * @param array $item Cart Item
							 * @param int $key Cart key
							 */
							do_action( 'cs_checkout_cart_item_title_after', $item, $key );
						?>
					</td>
					<td class="cs_cart_item_price">
						<?php
						echo cs_cart_item_price( $item['id'], $item['options'] );
						do_action( 'cs_checkout_cart_item_price_after', $item );
						?>
					</td>
					<td class="cs_cart_actions">
						<?php if( cs_item_quantities_enabled() && ! cs_download_quantities_disabled( $item['id'] ) ) : ?>
							<input type="number" min="1" step="1" name="cs-cart-download-<?php echo $key; ?>-quantity" data-key="<?php echo $key; ?>" class="cs-input cs-item-quantity" value="<?php echo cs_get_cart_item_quantity( $item['id'], $item['options'] ); ?>"/>
							<input type="hidden" name="cs-cart-downloads[]" value="<?php echo $item['id']; ?>"/>
							<input type="hidden" name="cs-cart-download-<?php echo $key; ?>-options" value="<?php echo esc_attr( json_encode( $item['options'] ) ); ?>"/>
						<?php endif; ?>
						<?php do_action( 'cs_cart_actions', $item, $key ); ?>
						<a class="cs_cart_remove_item_btn" href="<?php echo esc_url( wp_nonce_url( cs_remove_item_url( $key ), 'cs-remove-from-cart-' . $key, 'cs_remove_from_cart_nonce' ) ); ?>"><?php _e( 'Remove', 'commercestore' ); ?></a>
					</td>
					<?php do_action( 'cs_checkout_table_body_last', $item ); ?>
				</tr>
			<?php endforeach; ?>
		<?php endif; ?>
		<?php do_action( 'cs_cart_items_middle' ); ?>
		<!-- Show any cart fees, both positive and negative fees -->
		<?php if( cs_cart_has_fees() ) : ?>
			<?php foreach( cs_get_cart_fees() as $fee_id => $fee ) : ?>
				<tr class="cs_cart_fee" id="cs_cart_fee_<?php echo $fee_id; ?>">

					<?php do_action( 'cs_cart_fee_rows_before', $fee_id, $fee ); ?>

					<td class="cs_cart_fee_label"><?php echo esc_html( $fee['label'] ); ?></td>
					<td class="cs_cart_fee_amount"><?php echo esc_html( cs_currency_filter( cs_format_amount( $fee['amount'] ) ) ); ?></td>
					<td>
						<?php if( ! empty( $fee['type'] ) && 'item' == $fee['type'] ) : ?>
							<a href="<?php echo esc_url( cs_remove_cart_fee_url( $fee_id ) ); ?>"><?php _e( 'Remove', 'commercestore' ); ?></a>
						<?php endif; ?>

					</td>

					<?php do_action( 'cs_cart_fee_rows_after', $fee_id, $fee ); ?>

				</tr>
			<?php endforeach; ?>
		<?php endif; ?>

		<?php do_action( 'cs_cart_items_after' ); ?>
	</tbody>
	<tfoot>

		<?php if( has_action( 'cs_cart_footer_buttons' ) ) : ?>
			<tr class="cs_cart_footer_row<?php if ( cs_is_cart_saving_disabled() ) { echo ' cs-no-js'; } ?>">
				<th colspan="<?php echo cs_checkout_cart_columns(); ?>">
					<?php do_action( 'cs_cart_footer_buttons' ); ?>
				</th>
			</tr>
		<?php endif; ?>

		<?php if( cs_use_taxes() && ! cs_prices_include_tax() ) : ?>
			<tr class="cs_cart_footer_row cs_cart_subtotal_row"<?php if ( ! cs_is_cart_taxed() ) echo ' style="display:none;"'; ?>>
				<?php do_action( 'cs_checkout_table_subtotal_first' ); ?>
				<th colspan="<?php echo cs_checkout_cart_columns(); ?>" class="cs_cart_subtotal">
					<?php _e( 'Subtotal', 'commercestore' ); ?>:&nbsp;<span class="cs_cart_subtotal_amount"><?php echo cs_cart_subtotal(); ?></span>
				</th>
				<?php do_action( 'cs_checkout_table_subtotal_last' ); ?>
			</tr>
		<?php endif; ?>

		<tr class="cs_cart_footer_row cs_cart_discount_row" <?php if( ! cs_cart_has_discounts() )  echo ' style="display:none;"'; ?>>
			<?php do_action( 'cs_checkout_table_discount_first' ); ?>
			<th colspan="<?php echo cs_checkout_cart_columns(); ?>" class="cs_cart_discount">
				<?php cs_cart_discounts_html(); ?>
			</th>
			<?php do_action( 'cs_checkout_table_discount_last' ); ?>
		</tr>

		<?php if( cs_use_taxes() ) : ?>
			<tr class="cs_cart_footer_row cs_cart_tax_row"<?php if( ! cs_is_cart_taxed() ) echo ' style="display:none;"'; ?>>
				<?php do_action( 'cs_checkout_table_tax_first' ); ?>
				<th colspan="<?php echo cs_checkout_cart_columns(); ?>" class="cs_cart_tax">
					<?php _e( 'Tax', 'commercestore' ); ?>:&nbsp;<span class="cs_cart_tax_amount" data-tax="<?php echo cs_get_cart_tax( false ); ?>"><?php echo esc_html( cs_cart_tax() ); ?></span>
				</th>
				<?php do_action( 'cs_checkout_table_tax_last' ); ?>
			</tr>

		<?php endif; ?>

		<tr class="cs_cart_footer_row">
			<?php do_action( 'cs_checkout_table_footer_first' ); ?>
			<th colspan="<?php echo cs_checkout_cart_columns(); ?>" class="cs_cart_total"><?php _e( 'Total', 'commercestore' ); ?>: <span class="cs_cart_amount" data-subtotal="<?php echo cs_get_cart_subtotal(); ?>" data-total="<?php echo cs_get_cart_total(); ?>"><?php cs_cart_total(); ?></span></th>
			<?php do_action( 'cs_checkout_table_footer_last' ); ?>
		</tr>
	</tfoot>
</table>
