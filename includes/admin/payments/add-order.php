<?php
/**
 * Add Order Page.
 *
 * @package     CS
 * @subpackage  Admin/Orders
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

use CS\Database\Rows\Order as Order;

/**
 * Output the Add Order page
 *
 * @since 3.0
 */
function cs_add_order_page_content() {

	wp_enqueue_script( 'cs-admin-orders' );
	// Enqueued for backwards compatibility. Empty file.
	wp_enqueue_script( 'cs-admin-payments' );

	// Create empty order object to pass to callback functions.
	$order = new Order( array(
		'id'              => 0,
		'parent'          => 0,
		'order_number'    => 0,
		'status'          => 'complete',
		'date_created'    => date( 'Y-m-d H:i:s' ),
		'date_modified'   => date( 'Y-m-d H:i:s' ),
		'date_refundable' => null,
		'user_id'         => 0,
		'customer_id'     => 0,
		'email'           => '',
		'ip'              => cs_get_ip(),
		'gateway'         => '',
		'mode'            => '',
		'currency'        => cs_get_currency(),
		'payment_key'     => '',
		'subtotal'        => 0,
		'discount'        => 0,
		'tax'             => 0,
		'total'           => 0,
	) );

	?>

	<form id="cs-add-order-form" method="post">

		<?php cs_order_details_publish( $order ); ?>

		<div class="wrap cs-wrap cs-clearfix">
			<h1><?php esc_html_e( 'New Order', 'commercestore' ); ?></h1>

			<hr class="wp-header-end">

			<div class="notice notice-error inline" id="cs-add-order-customer-error" style="display: none;">
				<p><strong><?php esc_html_e( 'Error', 'commercestore' ); ?>:</strong> <?php esc_html_e( 'Please select an existing customer or create a new customer.', 'commercestore' ); ?></p>
			</div>

			<div class="notice notice-error inline" id="cs-add-order-no-items-error" style="display: none">
				<p><strong><?php esc_html_e( 'Error', 'commercestore' ); ?>:</strong> <?php esc_html_e( 'Please add an item to this order.', 'commercestore' ); ?></p>
			</div>

			<?php do_action( 'cs_add_order_before' ); ?>

			<?php do_action( 'cs_add_order_form_top' ); ?>

			<div id="poststuff">
				<div id="cs-dashboard-widgets-wrap">
					<div id="post-body" class="metabox-holder columns-2">
						<div id="postbox-container-2" class="postbox-container">
							<div id="normal-sortables">
								<?php

								// Before body.
								do_action( 'cs_add_order_details_main_before' );

								// Items.
								cs_order_details_overview( $order );

								// Details sections.
								cs_order_details_sections( $order );

								// After body.
								do_action( 'cs_add_order_details_main_after' );

								?>
							</div>
						</div>

						<div id="postbox-container-1" class="postbox-container">
							<div id="side-sortables">
								<?php

								// Before sidebar.
								do_action( 'cs_add_order_details_sidebar_before' );

								// Attributes.
								cs_order_details_attributes( $order );

								// Extras.
								cs_order_details_extras( $order );

								// After sidebar.
								do_action( 'cs_add_order_details_sidebar_after' );

								?>
							</div>
						</div>
					</div>
				</div>
			</div>

			<?php
			do_action( 'cs_add_order_form_bottom' );

			wp_nonce_field( 'cs_add_order_nonce', 'cs_add_order_nonce' );
			?>
			<input type="hidden" name="cs_action" value="add_order" />

			<?php do_action( 'cs_add_order_after' ); ?>

		</div><!-- /.wrap -->

	</form>

	<div
		id="cs-admin-order-add-item-dialog"
		title="<?php esc_attr_e( 'Add Download', 'commercestore' ); ?>"
		style="display: none;"
	>
		<div id="cs-admin-order-add-item-dialog-content"></div>
	</div>

	<div
		id="cs-admin-order-add-discount-dialog"
		title="<?php esc_attr_e( 'Add Discount', 'commercestore' ); ?>"
		style="display: none;"
	>
		<div id="cs-admin-order-add-discount-dialog-content"></div>
	</div>

	<div
		id="cs-admin-order-add-adjustment-dialog"
		title="<?php esc_attr_e( 'Add Adjustment', 'commercestore' ); ?>"
		style="display: none;"
	>
		<div id="cs-admin-order-add-adjustment-dialog-content"></div>
	</div>

<?php
}
