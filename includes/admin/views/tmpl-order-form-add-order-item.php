<?php
/**
 * Order Overview: Add Item form
 *
 * @package     CS
 * @subpackage  Admin/Views
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

$currency_position  = cs_get_option( 'currency_position', 'before' );

//
// Retrieve a list of recent Downloads to populate list.
//
// @todo this is similar to cs_ajax_download_search() but
//       that cannot be used because it requires $_GET requests.
//
$downloads        = array();
$recent_downloads = get_posts( array(
	'fields'         => 'ids',
	'orderby'        => 'date',
	'order'          => 'ASC',
	'post_type'      => 'download',
	'posts_per_page' => 25,
	'post_status'    => array(
		'publish',
		'draft',
		'private',
		'future',
	),
) );

if ( ! empty( $recent_downloads ) ) {

	foreach ( $recent_downloads as $download_id ) {
		$prices = cs_get_variable_prices( $download_id );

		// Non-variable items.
		if ( empty( $prices ) ) {
			$downloads[] = array(
				'id'   => $download_id,
				'name' => cs_get_download_name( $download_id ),
			);
		// Variable items.
		} else {
			foreach ( $prices as $key => $value ) {
				$name = cs_get_download_name( $download_id, $key );

				if ( ! empty( $name ) ) {
					$downloads[] = array(
						'id'   => $download_id . '_' . $key,
						'name' => esc_html( $name ),
					);
				}
			}
		}
	}
}
?>

<div class="cs-order-overview-modal">
	<form class="cs-order-overview-add-item">
		<# if ( false !== data.state.error ) { #>
			<div class="notice notice-error">
				<p>{{ data.state.error }}</p>
			</div>
		<# } #>

		<p>
			<label for="download">
				<?php echo esc_html( cs_get_label_singular() ); ?>
			</label>

			<select
				name="cs-order-add-download-select"
				id="download"
				class="cs-select cs-order-add-download-select variations variations-only cs-select-chosen"
				data-placeholder="<?php echo esc_html_e( 'Search for a download', 'commercestore' ); ?>"
				data-search-placeholder="<?php echo esc_html_e( 'Search for a download', 'commercestore' ); ?>"
				data-search-type="download">
					<option value=""></option>
					<# if ( 0 !== data.productId ) { #>
						<option value="{{ data.productId }}<# if ( 0 !== data.priceId ) { #>_{{ data.priceId }}<# } #>" selected>{{ data.productName }}</option>
					<# } #>
					<?php foreach ( $downloads as $download ) : ?>
						<option value="<?php echo esc_attr( $download['id'] ); ?>"><?php echo esc_html( $download['name'] ); ?></option>
					<?php endforeach; ?>
			</select>

			<# if ( true === data.state.isDuplicate ) { #>
			<span class="cs-order-overview-error">
			<?php
			/* translators: %s "Download" singular label. */
			echo esc_html(
				sprintf(
					__( 'This %s already exists in the Order. Please remove it before adding it again.', 'commercestore' ),
					cs_get_label_singular()
				)
			);
			?>
			</span>
			<# } #>
		</p>

		<# if ( false !== data.state.hasQuantity ) { #>
			<p>
				<label for="">
					<?php esc_html_e( 'Quantity', 'commercestore' ); ?>
				</label>
				<input
					type="number"
					id="quantity"
					class="cs-add-order-quantity"
					value="{{ data.quantity }}"
					step="1"
					min="1"
					<# if ( 0 === data.productId || true === data.state.isDuplicate ) { #>
						disabled
					<# } #>
				/>
			</p>
		<# } #>

		<p>
			<label
				class="cs-toggle"
				for="auto-calculate"
			>
				<input
					type="checkbox"
					id="auto-calculate"
					<# if ( true !== data.state.isAdjustingManually ) { #>
						checked
					<# } #>
					<# if ( 0 === data.productId || true === data.state.isDuplicate ) { #>
						disabled
					<# } #>
				/>
				<span class="label">
					<?php esc_html_e( 'Automatically calculate amounts', 'commercestore' ); ?>
					<# if ( 'none' !== data.state.hasTax && '' !== data.state.hasTax.country ) { #>
					<br />
					<small>
						<?php
						printf(
							esc_html__( 'Tax Rate: %s', 'commercestore' ),
							'{{ data.state.hasTax.country}}<# if ( \'\' !== data.state.hasTax.region ) { #>: {{ data.state.hasTax.region }}<# } #> &ndash; {{ data.state.hasTax.rate }}%'
						); // WPCS: XSS okay.
						?>
					</small>
					<# } #>
				</span>
			</label>
		</p>

		<# if ( 'none' !== data.state.hasTax && '' === data.state.hasTax.country && false === data.state.isAdjustingManually ) { #>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'No tax rate has been set.', 'commercestore' ); ?></strong><br />
					<?php esc_html_e( 'Tax rates are defined by the customer\'s billing address.', 'commercestore' ); ?>
				</p>
				<p>
					<button class="button button-secondary" id="set-address">
						<?php esc_html_e( 'Set an address', 'commercestore' ); ?>
					</button>
				</p>
			</div>
		<# } #>

		<# if ( true === data.state.isAdjustingManually ) { #>

			<p>
				<label for="amount"><?php esc_html_e( 'Unit Price', 'commercestore' ); ?></label>
				<span class="cs-amount">
					<?php if ( 'before' === $currency_position ) : ?>
						<?php echo cs_currency_filter( '' ); ?>
					<?php endif; ?>

					<input
						type="text"
						id="amount"
						value="{{ data.amountManual }}"
					/>

					<?php if ( 'after' === $currency_position ) : ?>
						<?php echo cs_currency_filter( '' ); ?>
					<?php endif; ?>
				</span>
			</p>

			<# if ( 'none' !== data.state.hasTax ) { #>
				<p>
					<label for="tax">
						<?php esc_html_e( 'Tax', 'commercestore' ); ?>
						<# if ( '' !== data.state.hasTax.country ) { #>
							<?php
							printf(
								esc_html_x( '(%s)', 'add order item tax rate', 'commercestore' ),
								'{{ data.state.hasTax.country}}<# if ( \'\' !== data.state.hasTax.region ) { #>: {{ data.state.hasTax.region }}<# } #> &ndash; {{ data.state.hasTax.rate }}%'
							); // WPCS: XSS okay.
							?>
						<# } #>
					</label>
					<span class="cs-amount">
						<?php if ( 'before' === $currency_position ) : ?>
							<?php echo cs_currency_filter( '' ); ?>
						<?php endif; ?>

						<input
							type="text"
							id="tax"
							value="{{ data.taxManual }}"
						/>

						<?php if ( 'after' === $currency_position ) : ?>
							<?php echo cs_currency_filter( '' ); ?>
						<?php endif; ?>
					</span>
				</p>
			<# } #>

			<p>
				<label for="subtotal"><?php esc_html_e( 'Amount', 'commercestore' ); ?></label>
				<span class="cs-amount">
					<?php if ( 'before' === $currency_position ) : ?>
						<?php echo cs_currency_filter( '' ); ?>
					<?php endif; ?>

					<input
						type="text"
						id="subtotal"
						value="{{ data.subtotalManual }}"
					/>

					<?php if ( 'after' === $currency_position ) : ?>
						<?php echo cs_currency_filter( '' ); ?>
					<?php endif; ?>
				</span>
			</p>

		<# } #>

		<p class="submit">
			<# if ( true === data.state.isFetching ) { #>
				<span class="spinner is-active cs-ml-auto"></span>
			<# } #>

			<input
				type="submit"
				class="button button-primary cs-ml-auto"
				value="<?php echo esc_html( sprintf( __( 'Add %s', 'commercestore' ), cs_get_label_singular() ) ); ?>"
				<# if ( 0 === data.productId || true === data.state.isDuplicate || true === data.state.isFetching ) { #>
					disabled
				<# } #>
			/>
		</p>
	</form>
</div>
