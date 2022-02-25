<?php
/**
 * Discount Codes
 *
 * @package     CS
 * @subpackage  Admin/Discounts
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Renders the Discounts admin page.
 *
 * Here only for backwards compatibility
 *
 * @since 1.4
 * @since 3.0 Nomenclature updated for consistency.
*/
function cs_discounts_page() {
	// Enqueue scripts.
	wp_enqueue_script( 'cs-admin-discounts' );

	// Edit
	if ( ! empty( $_GET['cs-action'] ) && ( 'edit_discount' === $_GET['cs-action'] ) ) {
		wp_enqueue_script( 'cs-admin-notes' );
		require_once CS_PLUGIN_DIR . 'includes/admin/discounts/edit-discount.php';

	// Add
	} elseif ( ! empty( $_GET['cs-action'] ) && ( 'add_discount' === $_GET['cs-action'] ) ) {
		require_once CS_PLUGIN_DIR . 'includes/admin/discounts/add-discount.php';

	// List tables
	} else {
		cs_adjustments_page();
	}
}

/**
 * Output the discounts page content, in the adjustments page action.
 *
 * @since 3.0
 */
function cs_discounts_page_content() {
	require_once CS_PLUGIN_DIR . 'includes/admin/discounts/class-discount-codes-table.php';

	$discount_codes_table = new CS_Discount_Codes_Table();
	$discount_codes_table->prepare_items();

	do_action( 'cs_discounts_page_top' ); ?>

	<form id="cs-discounts-filter" method="get" action="<?php echo admin_url( 'edit.php?post_type=' . CS_POST_TYPE . '&page=cs-discounts' ); ?>">
		<?php $discount_codes_table->search_box( __( 'Search Discounts', 'commercestore' ), 'cs-discounts' ); ?>

		<input type="hidden" name="post_type" value="download" />
		<input type="hidden" name="page" value="cs-discounts" />

		<?php
		$discount_codes_table->views();
		$discount_codes_table->display();
		?>
	</form>

	<?php do_action( 'cs_discounts_page_bottom' );
}
add_action( 'cs_adjustments_page_discount', 'cs_discounts_page_content' );
