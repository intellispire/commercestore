<?php
/**
 * Admin tax table bulk actions.
 *
 * @since 3.0
 *
 * @package CS
 * @category Template
 * @author CommerceStore
 * @version 1.0.0
 */
?>

<div class="tablenav top">

	<div class="cs-admin-tax-rates__tablenav--left">
		<select id="cs-admin-tax-rates-table-bulk-actions">
			<option><?php esc_html_e( 'Bulk Actions', 'commercestore' ); ?></option>
			<option value="active"><?php esc_html_e( 'Activate', 'commercestore' ); ?></option>
			<option value="inactive"><?php esc_html_e( 'Deactivate', 'commercestore' ); ?></option>
		</select>

		<button class="button cs-admin-tax-rates-table-filter"><?php esc_html_e( 'Apply', 'commercestore' ); ?></button>
	</div>

	<div class="cs-admin-tax-rates__tablenav--right">
		<label class="cs-toggle cs-admin-tax-rates-table-hide">
			<span class="label"><?php esc_html_e( 'Show deactivated rates', 'commercestore' ); ?></span>
			<input type="checkbox" id="hide-deactivated" />
		</label>
	</div>

</div>
