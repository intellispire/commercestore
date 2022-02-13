<?php
/**
 * Order Overview: Total
 *
 * @package     CS
 * @subpackage  Admin/Views
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */
?>

<tr class="is-expanded cs-order-overview-summary__total-total">
	<td></td>
	<td colspan="{{ data.config.colspan }}" class="column-primary">
		<?php esc_html_e( 'Total', 'commercestore' ); ?>

		<# if ( data.state.hasManualAdjustment ) { #>
			<br />
			<small><?php esc_html_e( '&dagger; Some amounts have been manually adjusted.', 'commercestore' ); ?></small>
		<# } #>
	</td>
	<td class="column-right" data-colname="<?php esc_html_e( 'Amount', 'commercestore' ); ?>">
		<span class="total <# if ( data.total < 0 ) { #>is-negative<# } #>">{{ data.totalCurrency }}</span>
	</td>
</tr>

<input type="hidden" value="{{ data.discount }}" name="discount" />
<input type="hidden" value="{{ data.total }}" name="total" />
