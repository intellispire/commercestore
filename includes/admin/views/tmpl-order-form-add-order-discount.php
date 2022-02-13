<?php
/**
 * Order Overview: Add Discount form
 *
 * @package     CS
 * @subpackage  Admin/Views
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

$discounts = cs_get_discounts( array(
	'number' => 100,
	'status' => 'active',
) );
?>

<div class="cs-order-overview-modal">
	<form class="cs-order-overview-add-discount">
		<p>
			<label for="discount">
				<?php esc_html_e( 'Discount', 'commercestore' ); ?>
			</label>

			<select
				id="discount"
				class="cs-select"
				required
			>
				<option value=""><?php esc_html_e( 'Choose a discount', 'commercestore' ); ?></option>
				<?php
				if ( false !== $discounts ) :
					foreach ( $discounts as $discount ) :
				?>
					<option
						data-code="<?php echo esc_attr( $discount->code ); ?>"
						value="<?php echo esc_attr( $discount->id ); ?>"
						<# if ( <?php echo esc_js( $discount->id ); ?> === data.typeId ) { #>
							selected
						<# } #>
					>
						<?php echo esc_html( $discount->code ); ?> &ndash; <?php echo esc_html( $discount->name ); ?>
					</option>
				<?php
					endforeach;
				endif;
				?>
			</select>

			<# if ( true === data._isDuplicate ) { #>
			<span class="cs-order-overview-error">
				<?php esc_html_e( 'This Discount already applied to the Order.', 'commercestore' ); ?>
			</span>
			<# } #>
		</p>

		<p class="submit">
			<# if ( true === data.state.isFetching ) { #>
				<span class="spinner is-active cs-ml-auto"></span>
			<# } #>

			<input
				type="submit"
				class="button button-primary cs-ml-auto"
				value="<?php esc_html_e( 'Add Discount', 'commercestore' ); ?>"
				<# if ( 0 === data.typeId || true === data._isDuplicate || true === data.state.isFetching ) { #>
					disabled
				<# } #>
			/>
		</p>
	</form>
</div>
