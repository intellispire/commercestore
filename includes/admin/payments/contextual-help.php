<?php
/**
 * Contextual Help
 *
 * @package     CS
 * @subpackage  Admin/Payments
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Payments contextual help.
 *
 * @access      private
 * @since       1.4
 * @return      void
 */
function cs_payments_contextual_help() {
	$screen = get_current_screen();

	// Only show on main "Orders" screen.
	if ( 'download_page_cs-payment-history' !== $screen->id ) {
		return;
	}

	// Do not show on Add or View Order/Refund.
	if ( isset( $_GET['view'] ) ) {
		return;
	}

	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'commercestore' ) . '</strong></p>' .
		'<p>' . sprintf( __( 'Visit the <a href="%s">documentation</a> on the CommerceStore website.', 'commercestore' ), esc_url( 'http://docs.commercestore.com/' ) ) . '</p>' .
		'<p>' . sprintf(
			__( 'Need more from your CommerceStore store? <a href="%s">Upgrade Now</a>!', 'commercestore' ),
			esc_url( 'https://commercestore.com/pricing/?utm_source=plugin-settings-page&utm_medium=contextual-help-sidebar&utm_term=pricing&utm_campaign=ContextualHelp' )
		) . '</p>'
	);

	$screen->add_help_tab( array(
		'id'	    => 'cs-payments-overview',
		'title'	    => __( 'Overview', 'commercestore' ),
		'content'	=>
			'<p>' . __( 'This screen provides access to all of the orders, refunds, and invoices in your store.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Orders can be searched by email address, user name, or filtered by status, mode, date range, gateway, and more!', 'commercestore' ) . '</p>' .
			'<p>' . __( 'To maintain accurate reporting and accounting, we strongly advise against deleting any completed order data.', 'commercestore' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'cs-orders',
		'title'	    => __( '&mdash; Orders', 'commercestore' ),
		'content'	=>
			'<p>' . __( 'Orders are placed by customers when they buy things from your store.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Every order contains a snapshot of your store at the time the order was placed, and is made up of many different pieces of information.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Things like products, discounts, taxes, fees, and customer email address, are all examples of information that is saved with each order.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Orders can be refunded entirely, or individual items can be refunded by editing an existing order.', 'commercestore' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'cs-refunds',
		'title'	    => __( '&mdash; Refunds', 'commercestore' ),
		'content'	=>
			'<p>' . __( 'Refunds are created when a customer would like money back from a completed order.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Every refund refers back to the original order, and only contains the items and adjustments that were refunded.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Refunds could be entire orders, or single products.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Once an item is refunded, it cannot be undone; it can only be repurchased.', 'commercestore' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'cs-invoice',
		'title'	    => __( '&mdash; Invoices', 'commercestore' ),
		'content'	=>
			'<p>' . __( 'Invoices are created by store admins as a way to request that a customer pay you for something.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Every invoice contains a snapshot of your store at the time the order was placed, and is made up of many different pieces of information.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Things like products, discounts, taxes, fees, and customer email address, are all examples of information that is saved with each invoice.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Invoices can be refunded entirely, or individual items can be refunded by editing an existing invoice.', 'commercestore' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'cs-payments-search',
		'title'	    => __( 'Search', 'commercestore' ),
		'content'	=>
			'<p>' . __( 'The order history can be searched in several different ways.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'You can enter:', 'commercestore' ) . '</p>' .
			'<ul>
				<li>' . __( 'The purchase ID', 'commercestore' ) . '</li>
				<li>' . __( 'The 32-character purchase key', 'commercestore' ) . '</li>
				<li>' . __( 'The customer\'s email address', 'commercestore' ) . '</li>
				<li>' . __( 'The customer\'s name or ID prefixed by <code>user:</code>', 'commercestore' ) . '</li>
				<li>' . sprintf( __( 'The %s ID prefixed by <code>#</code>', 'commercestore' ), cs_get_label_singular() ) . '</li>
				<li>' . __( 'The Discount Code prefixed by <code>discount:</code>', 'commercestore' ) . '</li>
				<li>' . __( 'A transaction ID prefixed by <code>txn:</code>', 'commercestore' ) . '</li>
			</ul>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'cs-payments-details',
		'title'	    => __( 'Details', 'commercestore' ),
		'content'	=>
			'<p>' . __( 'Each order can be further inspected by clicking the corresponding <em>View Order Details</em> link. This will provide more information including:', 'commercestore' ) . '</p>' .

			'<ul>
				<li><strong>Purchased File</strong> - ' . __( 'The file associated with the purchase.', 'commercestore' ) . '</li>
				<li><strong>Purchase Date</strong> - ' . __( 'The exact date and time the order was completed.', 'commercestore' ) . '</li>
				<li><strong>Discount Used</strong> - ' . __( 'If a coupon or discount was used during the checkout process.', 'commercestore' ) . '</li>
				<li><strong>Name</strong> - ' . __( "The buyer's name.", 'commercestore' ) . '</li>
				<li><strong>Email</strong> - ' . __( "The buyer's email address.", 'commercestore' ) . '</li>
				<li><strong>Payment Notes</strong> - ' . __( 'Any customer-specific notes related to the order.', 'commercestore' ) . '</li>
				<li><strong>Payment Method</strong> - ' . __( 'The name of the order gateway used to complete the order.', 'commercestore' ) . '</li>
				<li><strong>Purchase Key</strong> - ' . __( 'A unique key used to identify the order.', 'commercestore' ) . '</li>
			</ul>'
	) );

	do_action( 'cs_payments_contextual_help', $screen );
}
add_action( 'load-download_page_cs-payment-history', 'cs_payments_contextual_help' );
