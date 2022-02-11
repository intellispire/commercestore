<?php
/**
 * Deprecated Hooks
 *
 * All hooks that have been deprecated.
 *
 * @package     CS
 * @subpackage  Deprecated
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

/**
 * Legacy pre-refund hook which fired after a payment status changed, but before store stats were updated.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @link       https://github.com/commercestore/commercestore/issues/8574
 *
 * @param int $order_id The original order id.
 */
add_action( 'cs_refund_order', function( $order_id ) {
	if ( has_action( 'cs_pre_refund_payment' ) ) {
		do_action( 'cs_pre_refund_payment', cs_get_payment( $order_id ) );
	}
} );

/**
 * Legacy post-refund hook which fired after a payment status changed and store stats were updated.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @link       https://github.com/commercestore/commercestore/issues/8574
 *
 * @param int $order_id The original order id.
 */
add_action( 'cs_refund_order', function( $order_id ) {
	if ( has_action( 'cs_post_refund_payment' ) ) {
		do_action( 'cs_post_refund_payment', cs_get_payment( $order_id ) );
	}
} );

/**
 * Fires after the order receipt files, if needed.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @param int   $filekey          Index of array of files returned by cs_get_download_files() that this download link is for.
 * @param array $file             The array of file information.
 * @param int   $item->product_id The product ID.
 * @param int   $order->id        The order ID.
 */
add_action( 'cs_order_receipt_files', function( $filekey, $file, $product_id, $order_id ) {
	if ( ! has_action( 'cs_receipt_files' ) ) {
		return;
	}
	$meta = cs_get_payment_meta( $order_id );
	do_action( 'cs_receipt_files', $filekey, $file, $product_id, $order_id, $meta );
}, 10, 4 );


/**
 * Fires after the order receipt bundled items, if needed.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @param int   $filekey          Index of array of files returned by cs_get_download_files() that this download link is for.
 * @param array $file             The array of file information.
 * @param int   $item->product_id The product ID.
 * @param array $bundle_item      The array of information about the bundled item.
 * @param int   $order->id        The order ID.
 */
add_action( 'cs_order_receipt_bundle_files', function( $filekey, $file, $product_id, $bundle_item, $order_id ) {
	if ( ! has_action( 'cs_receipt_bundle_files' ) ) {
		return;
	}
	$meta = cs_get_payment_meta( $order_id );
	do_action( 'cs_receipt_bundle_files', $filekey, $file, $product_id, $bundle_item, $order_id, $meta );
}, 10, 5 );

/**
 * Fires at the end of the product cell.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @param \CS\Orders\Order_Item $item The current order item.
 * @param \CS\Orders\Order $order     The current order object.
 */
add_action( 'cs_order_receipt_after_files', function( $item, $order ) {
	if ( ! has_action( 'cs_purchase_receipt_after_files' ) ) {
		return;
	}
	$meta = cs_get_payment_meta( $order->id );
	do_action( 'cs_purchase_receipt_after_files', $item->product_id, $order->id, $meta, $item->price_id );
}, 10, 2 );

/**
 * Fires before the order receipt table, if needed.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @param \CS\Orders\Order $order            The current order object.
 * @param array             $cs_receipt_args The shortcode parameters for the receipt.
 */
add_action( 'cs_order_receipt_before_table', function( $order, $cs_receipt_args ) {
	if ( ! has_action( 'cs_payment_receipt_before_table' ) ) {
		return;
	}
	$payment = cs_get_payment( $order->id );
	do_action( 'cs_payment_receipt_before_table', $payment, $cs_receipt_args );
}, 10, 2 );

/**
 * Fires at the beginning of the order receipt `thead`, if needed.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @param \CS\Orders\Order $order            The current order object.
 * @param array             $cs_receipt_args The shortcode parameters for the receipt.
 */
add_action( 'cs_order_receipt_before', function( $order, $cs_receipt_args ) {
	if ( ! has_action( 'cs_payment_receipt_before' ) ) {
		return;
	}
	$payment = cs_get_payment( $order->id );
	do_action( 'cs_payment_receipt_before', $payment, $cs_receipt_args );
}, 10, 2 );

/**
 * Fires at the end of the order receipt `tbody`, if needed.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @param \CS\Orders\Order $order            The current order object.
 * @param array             $cs_receipt_args The shortcode parameters for the receipt.
 */
add_action( 'cs_order_receipt_after', function( $order, $cs_receipt_args ) {
	if ( ! has_action( 'cs_payment_receipt_after' ) ) {
		return;
	}
	$payment = cs_get_payment( $order->id );
	do_action( 'cs_payment_receipt_after', $payment, $cs_receipt_args );
}, 10, 2 );

/**
 * Fires after the order receipt table, if needed.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @param \CS\Orders\Order $order            The current order object.
 * @param array             $cs_receipt_args The shortcode parameters for the receipt.
 */
add_action( 'cs_order_receipt_after_table', function( $order, $cs_receipt_args ) {
	if ( ! has_action( 'cs_payment_receipt_after_table' ) ) {
		return;
	}
	$payment = cs_get_payment( $order->id );
	do_action( 'cs_payment_receipt_after_table', $payment, $cs_receipt_args );
}, 10, 2 );

/**
 * Fires the cs_before_purchase_history hook in the purchase history, if needed.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @param \CS\Orders\Order[] $orders The array of the current user's orders.
 */
add_action( 'cs_before_order_history', function( $orders ) {
	if ( ! has_action( 'cs_before_purchase_history' ) ) {
		return;
	}

	$payments = array();

	if ( ! empty( $orders ) ) {
		$order_ids = wp_list_pluck( $orders, 'id' );
		$payments  = cs_get_payments(
			array(
				'id__in'  => $order_ids,
				'orderby' => 'date',
			)
		);
	}

	do_action( 'cs_before_purchase_history', $payments );
} );

/**
 * Fires at the beginning of the purchase history row, if needed.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @param \CS\Orders\Order $order The current order object.
 */
add_action( 'cs_order_history_row_start', function( \CS\Orders\Order $order ) {
	if ( ! has_action( 'cs_purchase_history_row_start' ) ) {
		return;
	}

	$payment = cs_get_payment( $order->id );
	if ( ! $payment ) {
		return;
	}

	do_action( 'cs_purchase_history_row_start', $payment->ID, $payment->payment_meta );
} );

/**
 * Fires at the end of the purchase history row, if needed.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @param \CS\Orders\Order $order The current order object.
 */
add_action( 'cs_order_history_row_end', function( \CS\Orders\Order $order ) {
	if ( ! has_action( 'cs_purchase_history_row_end' ) ) {
		return;
	}

	$payment = cs_get_payment( $order->id );
	if ( ! $payment ) {
		return;
	}

	do_action( 'cs_purchase_history_row_end', $payment->ID, $payment->payment_meta );
} );

/**
 * Fires the cs_after_purchase_history hook in the purchase history, if needed.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in CommerceStore 3.1
 * @param \CS\Orders\Order[] $orders The array of the current user's orders.
 */
add_action( 'cs_after_order_history', function( $orders ) {
	if ( ! has_action( 'cs_after_purchase_history' ) ) {
		return;
	}

	$payments = array();

	if ( ! empty( $orders ) ) {
		$order_ids = wp_list_pluck( $orders, 'id' );
		$payments  = cs_get_payments(
			array(
				'id__in'  => $order_ids,
				'orderby' => 'date',
			)
		);
	}

	do_action( 'cs_after_purchase_history', $payments );
} );

/**
 * Fires after the individual download file in the downloads history, if needed.
 *
 * @deprecated 3.0
 * @todo       Formally deprecate in 3.1
 * @param int                    $filekey Download file ID.
 * @param array                  $file    Array of file information.
 * @param \CS\Orders\Order_Item $item    The order item object.
 * @param \CS\Orders\Order      $order   The order object.
 */
add_action( 'cs_download_history_download_file', function( $filekey, $file, $item, $order ) {
	if ( ! has_action( 'cs_download_history_files' ) ) {
		return;
	}
	$purchase_data = cs_get_payment_meta( $order->id );
	do_action( 'cs_download_history_files', $filekey, $file, $item->product_id, $order->id, $purchase_data );
}, 10, 4 );
