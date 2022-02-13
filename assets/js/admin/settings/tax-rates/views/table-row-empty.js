/* global wp */

/**
 * Empty tax rates table.
 */
const TableRowEmpty = wp.Backbone.View.extend( {
	// Insert as a <tr>
	tagName: 'tr',

	// Set class.
	className: 'cs-tax-rate-row cs-tax-rate-row--is-empty',

	// See https://codex.wordpress.org/Javascript_Reference/wp.template
	template: wp.template( 'cs-admin-tax-rates-table-row-empty' ),
} );

export default TableRowEmpty;
