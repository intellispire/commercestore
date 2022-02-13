<?php
/**
 * Admin tax table "meta" (thead or tfoot).
 *
 * @since 3.0
 *
 * @package CS
 * @category Template
 * @author CommerceStore
 * @version 1.0.0
 */
?>

<tr>
	<td class="cs-tax-rates-table-checkbox check-column"><input type="checkbox" /></td>
	<th class="cs-tax-rates-table-country"><?php esc_html_e( 'Country', 'commercestore' ); ?></th>
	<th><?php esc_html_e( 'Region', 'commercestore' ); ?></th>
	<th class="cs-tax-rates-table-rate"><?php esc_html_e( 'Rate', 'commercestore' ); ?></th>
	<th class="cs-tax-rates-table-actions"><?php esc_html_e( 'Actions', 'commercestore' ); ?></th>
</tr>
