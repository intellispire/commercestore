<?php
/**
 * View Order Details
 *
 * @package     CS
 * @subpackage  Admin/Payments
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.6
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * View Order Details Page
 *
 * @since 1.6
 * @since 3.0 Updated to use the new CS\Orders\Order object.
 */

if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
	wp_die( __( 'Order ID not supplied. Please try again', 'commercestore' ), __( 'Error', 'commercestore' ) );
}

$order_id = absint( $_GET['id'] );
$order    = cs_get_order( $order_id );

// Check that the order exists in the database.
if ( empty( $order ) ) {
	wp_die( __( 'The specified ID does not belong to an order. Please try again', 'commercestore' ), __( 'Error', 'commercestore' ) );
}

if ( 'refund' === $order->type ) {
	$refund_link = cs_get_admin_url(
		array(
			'page' => 'cs-payment-history',
			'view' => 'view-refund-details',
			'id'   => urlencode( $order->id ),
		)
	);
	wp_die( sprintf( __( 'The specified ID is for a refund, not an order. Please <a href="%s">access the refund directly</a>.', 'commercestore' ), esc_url( $refund_link ) ), __( 'Error', 'commercestore' ) );
}

wp_enqueue_script( 'cs-admin-orders' );
// Enqueued for backwards compatibility. Empty file.
wp_enqueue_script( 'cs-admin-payments' );
?>

<form id="cs-edit-order-form" method="post">

	<?php cs_order_details_publish( $order ); ?>

	<div class="wrap cs-wrap cs-clearfix">
		<h1><?php printf( __( 'Order: %s', 'commercestore' ), $order->number ); ?></h1>

		<hr class="wp-header-end">

		<div class="notice notice-error inline" id="cs-add-order-customer-error" style="display: none;">
			<p><strong><?php esc_html_e( 'Error', 'commercestore' ); ?>:</strong> <?php esc_html_e( 'Please select an existing customer or create a new customer.', 'commercestore' ); ?></p>
		</div>

		<?php do_action( 'cs_view_order_details_before', $order->id ); ?>

		<?php do_action( 'cs_view_order_details_form_top', $order->id ); ?>

		<div id="poststuff">
			<div id="cs-dashboard-widgets-wrap">
				<div id="post-body" class="metabox-holder columns-2">
					<div id="postbox-container-2" class="postbox-container">
						<div id="normal-sortables">
							<?php

							// Before body
							do_action( 'cs_view_order_details_main_before', $order->id );

							// Overview
							cs_order_details_overview( $order );

							// Details sections
							cs_order_details_sections( $order );

							// Legacy hook from pre version 3 of CommerceStore.
							do_action( 'cs_view_order_details_billing_after', $order->id );

							// After body
							do_action( 'cs_view_order_details_main_after', $order->id );

							?>
						</div>
					</div>

					<div id="postbox-container-1" class="postbox-container">
						<div id="side-sortables">
							<?php

							// Before sidebar
							do_action( 'cs_view_order_details_sidebar_before', $order->id );

							// Attributes
							cs_order_details_attributes( $order );

							// Extras
							cs_order_details_extras( $order );

							// After sidebar
							do_action( 'cs_view_order_details_sidebar_after', $order->id );

							?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php

		do_action( 'cs_view_order_details_form_bottom', $order->id );

		wp_nonce_field( 'cs_update_payment_details_nonce' ); ?>
		<input type="hidden" name="cs_payment_id" value="<?php echo esc_attr( $order->id ); ?>"/>
		<input type="hidden" name="cs_action" value="update_payment_details"/>

		<?php do_action( 'cs_view_order_details_after', $order->id ); ?>

	</div><!-- /.wrap -->

</form>

<div id="cs-refund-order-dialog" title="<?php _e( 'Submit Refund', 'commercestore' ); ?>"></div>

<div
	id="cs-admin-order-copy-download-link-dialog"
	title="<?php echo esc_html( sprintf( __( 'Copy %s Links', 'commercestore' ), cs_get_label_singular() ) ); ?>"
	style="display: none;"
>
	<div id="cs-admin-order-copy-download-link-dialog-content"></div>
</div>
