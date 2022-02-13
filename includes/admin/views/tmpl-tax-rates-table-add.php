<?php
/**
 * Admin tax table add "form".
 *
 * @since 3.0
 *
 * @package CS
 * @category Template
 * @author CommerceStore
 * @version 1.0.0
 */
?>

<tr class="cs-tax-rate-table-add">

	<th colspan="2">
		<label for="tax_rate_country" class="screen-reader-text"><?php esc_html_e( 'Country', 'commercestore' ); ?></label>
		<?php
		echo CS()->html->country_select( array(
			'id'              => 'tax_rate_country',
			'show_option_all' => false,
		) );
		?>
	</th>

	<th>
		<label for="tax_rate_region" class="screen-reader-text"><?php esc_html_e( 'Region', 'commercestore' ); ?></label>

		<label>
			<input type="checkbox" checked /><?php esc_html_e( 'Apply to whole country', 'commercestore' ); ?>
		</label>

		<div id="tax_rate_region_wrapper"></div>
	</th>

	<th>
		<label for="tax_rate_amount" class="screen-reader-text"><?php esc_html_e( 'Rate', 'commercestore' ); ?></label>
		<input type="number" step="0.0001" min="0.0" max="99" id="tax_rate_amount" />
	</th>

	<th class="cs-tax-rates-table-actions">
		<button id="tax_rate_submit" class="button button-secondary"><?php esc_html_e( 'Add Rate', 'commercestore' ); ?></button>
	</th>

</tr>
