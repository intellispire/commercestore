<?php
/**
 * Shortcode: Download History - [download_history]
 *
 * @package CS
 * @category Template
 *
 * @since 3.0 Uses new `cs_get_orders()` function and associated helpers.
 *            Checks status on individual order items when determining download link visibility.
 */

if ( ! empty( $_GET['cs-verify-success'] ) ) : ?>
	<p class="cs-account-verified cs_success">
		<?php esc_html_e( 'Your account has been successfully verified!', 'commercestore' ); ?>
	</p>
	<?php
endif;
/**
 * This template is used to display the download history of the current user.
 */
$customer = cs_get_customer_by( 'user_id', get_current_user_id() );
$page     = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;

if ( ! empty( $customer ) ) {
	$orders = cs_get_orders(
		array(
			'customer_id' => $customer->id,
			'number'      => 20,
			'offset'      => 20 * ( intval( $page ) - 1 ),
			'type'        => 'sale',
		)
	);
} else {
	$orders = array();
}

if ( $orders ) :
	do_action( 'cs_before_download_history' ); ?>
	<table id="cs_user_history" class="cs-table">
		<thead>
			<tr class="cs_download_history_row">
				<?php do_action( 'cs_download_history_header_start' ); ?>
				<th class="cs_download_download_name"><?php esc_html_e( 'Download Name', 'commercestore' ); ?></th>
				<?php if ( ! cs_no_redownload() ) : ?>
					<th class="cs_download_download_files"><?php esc_html_e( 'Files', 'commercestore' ); ?></th>
				<?php endif; //End if no redownload?>
				<?php do_action( 'cs_download_history_header_end' ); ?>
			</tr>
		</thead>
		<?php foreach ( $orders as $order ) :
			foreach ( $order->get_items() as $key => $item ) :

				// Skip over Bundles. Products included with a bundle will be displayed individually
				if ( cs_is_bundled_product( $item->product_id ) ) {
					continue;
				}
				?>

				<tr class="cs_download_history_row">
					<?php
					$price_id       = $item->price_id;
					// Get price ID of product with variable prices included in Bundle
						if ( ! empty( $download['in_bundle'] ) && cs_has_variable_prices( $download['id'] ) ) {
							$price_id = cs_get_bundle_item_price_id( $download['id'] );
						}
						$download_files = cs_get_download_files( $item->product_id, $price_id );
					$name           = $item->product_name;

					do_action( 'cs_download_history_row_start', $order->id, $item->product_id );
					?>
					<td class="cs_download_download_name"><?php echo esc_html( $name ); ?></td>

					<?php if ( ! cs_no_redownload() ) : ?>
						<td class="cs_download_download_files">
							<?php

							if ( 'complete' == $item->status ) :

								if ( $download_files ) :

									foreach ( $download_files as $filekey => $file ) :

										$download_url = cs_get_download_file_url( $order->payment_key, $order->email, $filekey, $item->product_id, $price_id );
										?>

										<div class="cs_download_file">
											<a href="<?php echo esc_url( $download_url ); ?>" class="cs_download_file_link">
												<?php echo cs_get_file_name( $file ); ?>
											</a>
										</div>

										<?php
										do_action( 'cs_download_history_download_file', $filekey, $file, $item, $order );
									endforeach;

								else :
									esc_html_e( 'No downloadable files found.', 'commercestore' );
								endif; // End if payment complete

							else : ?>
								<span class="cs_download_payment_status">
									<?php
									printf(
										/* translators: the order item's status. */
										esc_html__( 'Status: %s', 'commercestore' ),
										esc_html( cs_get_status_label( $item->status ) )
									);
									?>
								</span>
								<?php
							endif; // End if $download_files
							?>
						</td>
					<?php endif; // End if ! cs_no_redownload()

					do_action( 'cs_download_history_row_end', $order->id, $item->product_id );
					?>
				</tr>
				<?php
			endforeach; // End foreach get_items()
		endforeach;
		?>
	</table>
	<?php
	if ( ! empty( $customer->id ) ) {
		$count = cs_count_orders(
			array(
				'customer_id' => $customer->id,
				'type'        => 'sale',
			)
		);
		echo cs_pagination(
			array(
				'type'  => 'download_history',
				'total' => ceil( $count / 20 ), // 20 items per page
			)
		);
	}
	?>
	<?php do_action( 'cs_after_download_history' ); ?>
<?php else : ?>
	<p class="cs-no-downloads"><?php esc_html_e( 'You have not purchased any downloads', 'commercestore' ); ?></p>
<?php endif; ?>
