<?php
/**
 * Order Functions.
 *
 * @package     CS
 * @subpackage  Orders
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add an order.
 *
 * @since 3.0
 *
 * @param array $data {
 *     Array of order data. Default empty.
 *
 *     The `date_created` and `date_modified` parameters do not need to be passed.
 *     They will be automatically populated if empty.
 *
 *     @type int    $parent               ID of the parent order. Default 0.
 *     @type string $order_number         Order number, if enabled. Default empty.
 *     @type string $status               Order status. Default `pending`.
 *     @type string $type                 Order type. Default `sale`.
 *     @type int    $user_id              WordPress user ID linked to the customer of
 *                                        the order. Default 0.
 *     @type int    $customer_id          ID of the customer of the order. Default 0.
 *     @type string $email                Email address used for the order. Default empty.
 *     @type string $ip                   IP address of the client at checkout. Default empty.
 *     @type string $gateway              Gateway used to process the order. Default empty.
 *     @type string $mode                 Store mode when order was placed. Default empty.
 *     @type string $currency             Currency used for the order. Default empty.
 *     @type string $payment_key          Payment key generated for the order. Default empty.
 *     @type int|null $tax_rate_id        ID of the tax rate Adjustment associated with the order. Default null.
 *     @type float  $subtotal             Order subtotal. Default 0.
 *     @type float  $discount             Discount applied to the order. Default 0.
 *     @type float  $tax                  Tax applied to the order. Default 0.
 *     @type float  $total                Order total. Default 0.
 *     @type string $date_created         Optional. Automatically calculated on add/edit.
 *                                        The date & time the order was inserted.
 *                                        Format: YYYY-MM-DD HH:MM:SS. Default empty.
 *     @type string $date_modified        Optional. Automatically calculated on add/edit.
 *                                        The date & time the order was last modified.
 *                                        Format: YYYY-MM-DD HH:MM:SS. Default empty.
 *     @type string|null $date_completed  The date & time the order's status was
 *                                        changed to `complete`. Format: YYYY-MM-DD HH:MM:SS.
 *                                        Default null.
 *     @type string|null $date_refundable The date & time an order can be refunded until.
 *                                        Format: YYYY-MM-DD HH:MM:SS.
 * }
 * @return int|false ID of newly created order, false on error.
 */
function cs_add_order( $data = array() ) {
	$orders = new CS\Database\Queries\Order();

	return $orders->add_item( $data );
}

/**
 * Move an order to the trashed status
 *
 * @since 3.0
 *
 * @param $order_id
 *
 * @return bool      true if the order was trashed successfully, false if not
 */
function cs_trash_order( $order_id ) {

	if ( false === cs_is_order_trashable( $order_id ) ) {
		return false;
	}

	$order = cs_get_order( $order_id );

	$orders         = new CS\Database\Queries\Order();
	$current_status = $order->status;

	$trashed = $orders->update_item( $order_id, array(
		'status' => 'trash',
	) ); new CS\Database\Queries\Order();

	if ( ! empty( $trashed ) ) {

		// If successfully trashed, store the pre-trashed status in meta, so we can possibly restore it.
		cs_add_order_meta( $order_id, '_pre_trash_status', $current_status );

		// Update the status of any order to 'trashed'.
		$order_items = cs_get_order_items( array(
			'order_id'      => $order_id,
			'no_found_rows' => true,
		) );

		$items = new CS\Database\Queries\Order_Item();
		foreach ( $order_items as $item ) {
			$current_item_status = $item->status;

			$item_trashed = $items->update_item( $item->id, array(
				'status' => 'trash',
			) );

			if ( ! empty( $item_trashed ) ) {
				cs_add_order_item_meta( $item->id, '_pre_trash_status', $current_item_status );
			}
		}

		// Now look for any orders with the refund type.
		$refund_orders = cs_get_orders( array(
			'type'   => 'refund',
			'parent' => $order_id,
		) );

		if ( ! empty( $refund_orders ) ) {
			foreach( $refund_orders as $refund ) {

				$current_refund_status = $refund->status;
				$refund_trashed = cs_trash_order( $refund->id );

				if ( ! empty( $refund_trashed ) ) {
					cs_add_order_meta( $refund->id, '_pre_trash_status', $current_refund_status );
				}

			}
		}

		// Update the customer records when an order is trashed.
		if ( ! empty( $order->customer_id ) ) {
			$customer = new CS_Customer( $order->customer_id );
			$customer->recalculate_stats();
		}
	}

	return filter_var( $trashed, FILTER_VALIDATE_BOOLEAN );
}

/**
 * Restore an order from the trashed status to it's previous status.
 *
 * @since 3.0
 *
 * @param $order_id
 *
 * @return bool      true if the order was trashed successfully, false if not
 */
function cs_restore_order( $order_id ) {

	if ( false === cs_is_order_restorable( $order_id ) ) {
		return false;
	}

	$order = cs_get_order( $order_id );

	if ( 'trash' !== $order->status ) {
		return false;
	}

	$orders = new CS\Database\Queries\Order();

	$pre_trash_status = cs_get_order_meta( $order_id, '_pre_trash_status', true );
	if ( empty( $pre_trash_status ) ) {
		return false;
	}

	$restored = $orders->update_item( $order_id, array(
		'status' => $pre_trash_status,
	) );

	if ( ! empty( $restored ) ) {

		// If successfully trashed, store the pre-trashed status in meta, so we can possibly restore it.
		cs_delete_order_meta( $order_id, '_pre_trash_status' );

		// Update the status of any order to 'trashed'.
		$order_items = cs_get_order_items( array(
			'order_id'      => $order_id,
			'no_found_rows' => true,
		) );

		$items = new CS\Database\Queries\Order_Item();
		foreach ( $order_items as $item ) {
			$pre_trash_status = cs_get_order_item_meta( $item->id, '_pre_trash_status', true );

			if ( ! empty( $pre_trash_status ) ) {
				$restored_item = $items->update_item( $item->id, array(
					'status' => $pre_trash_status,
				) );

				if ( ! empty( $restored_item ) ) {
					cs_delete_order_item_meta( $item->id, '_pre_trash_status' );
				}
			}

		}

		// Now look for any orders with the refund type.
		$refund_orders = cs_get_orders( array(
			'type'   => 'refund',
			'parent' => $order_id,
		) );

		if ( ! empty( $refund_orders ) ) {
			foreach( $refund_orders as $refund ) {
				cs_restore_order( $refund->id );
			}
		}

	}

	return filter_var( $restored, FILTER_VALIDATE_BOOLEAN );
}

/**
 * Delete an order.
 *
 * @since 3.0
 *
 * @param int $order_id Order ID.
 * @return int|false `1` if the order was deleted successfully, false on error.
 */
function cs_delete_order( $order_id = 0 ) {
	$orders = new CS\Database\Queries\Order();

	return $orders->delete_item( $order_id );
}

/**
 * Destroy an order.
 *
 * Completely deletes an order, and the items and adjustments with it.
 *
 * @todo switch to _destroy_ for items & adjustments
 *
 * @since 3.0
 *
 * @param int $order_id Order ID.
 * @return int|false `1` if the order was deleted successfully, false on error.
 */
function cs_destroy_order( $order_id = 0 ) {

	// Delete the order
	$destroyed = cs_delete_order( $order_id );

	if ( $destroyed ) {
		// Get items.
		$items = cs_get_order_items( array(
			'order_id'      => $order_id,
			'no_found_rows' => true,
		) );

		// Destroy items (and their adjustments).
		if ( ! empty( $items ) ) {
			foreach ( $items as $item ) {
				cs_delete_order_item( $item->id );
			}
		}

		// Get adjustments.
		$adjustments = cs_get_order_adjustments( array(
			'object_id'     => $order_id,
			'object_type'   => 'order',
			'no_found_rows' => true,
		) );

		// Destroy adjustments.
		if ( ! empty( $adjustments ) ) {
			foreach ( $adjustments as $adjustment ) {
				cs_delete_order_adjustment( $adjustment->id );
			}
		}

		// Get address.
		$address = cs_get_order_address_by( 'order_id', $order_id );

		// Destroy address.
		if ( $address ) {
			cs_delete_order_address( $address->id );
		}

		// Now look for any orders with the refund type.
		$refund_orders = cs_get_orders( array(
			'type'   => 'refund',
			'parent' => $order_id,
		) );

		if ( ! empty( $refund_orders ) ) {
			foreach( $refund_orders as $refund ) {
				cs_destroy_order( $refund->id );
			}
		}
	}



	return $destroyed;
}

/**
 * Update an order.
 *
 * @since 3.0
 *
 * @param int   $order_id Order ID.
 * @param array $data {
 *     Array of order data. Default empty.
 *
 *     @type int    $parent               ID of the parent order. Default 0.
 *     @type string $order_number         Order number, if enabled. Default empty.
 *     @type string $status               Order status. Default `pending`.
 *     @type string $type                 Order type. Default `sale`.
 *     @type int    $user_id              WordPress user ID linked to the customer of
 *                                        the order. Default 0.
 *     @type int    $customer_id          ID of the customer of the order. Default 0.
 *     @type string $email                Email address used for the order. Default empty.
 *     @type string $ip                   IP address of the client at checkout. Default empty.
 *     @type string $gateway              Gateway used to process the order. Default empty.
 *     @type string $mode                 Store mode when order was placed. Default empty.
 *     @type string $currency             Currency used for the order. Default empty.
 *     @type string $payment_key          Payment key generated for the order. Default empty.
 *     @type int|float $tax_rate_id       ID of the tax rate Adjustment associated with the order. Default empty.
 *     @type float  $subtotal             Order subtotal. Default 0.
 *     @type float  $discount             Discount applied to the order. Default 0.
 *     @type float  $tax                  Tax applied to the order. Default 0.
 *     @type float  $total                Order total. Default 0.
 *     @type string $date_created         Optional. Automatically calculated on add/edit.
 *                                        The date & time the order was inserted.
 *                                        Format: YYYY-MM-DD HH:MM:SS. Default empty.
 *     @type string $date_modified        Optional. Automatically calculated on add/edit.
 *                                        The date & time the order was last modified.
 *                                        Format: YYYY-MM-DD HH:MM:SS. Default empty.
 *     @type string|null $date_completed  The date & time the order's status was
 *                                        changed to `complete`. Format: YYYY-MM-DD HH:MM:SS.
 *                                        Default empty.
 *     @type string|null $date_refundable The date & time an order can be refunded until.
 *                                        Format: YYYY-MM-DD HH:MM:SS.
 * }
 *
 * @return bool Whether or not the order was updated.
 */
function cs_update_order( $order_id = 0, $data = array() ) {
	$orders = new CS\Database\Queries\Order();
	$update = $orders->update_item( $order_id, $data );

	if ( ! empty( $data['customer_id'] ) ) {
		$customer = new CS_Customer( $data['customer_id'] );
		$customer->recalculate_stats();
	}

	return $update;
}

/**
 * Get an order by ID.
 *
 * @since 3.0
 *
 * @param int $order_id Order ID.
 * @return CS\Orders\Order|false Order object if successful, false otherwise.
 */
function cs_get_order( $order_id = 0 ) {
	$orders = new CS\Database\Queries\Order();

	// Return order
	return $orders->get_item( $order_id );
}

/**
 * Get an order by a specific field value.
 *
 * @since 3.0
 *
 * @param string $field Database table field.
 * @param string $value Value of the row.
 *
 * @return CS\Orders\Order|false Order object if successful, false otherwise.
 */
function cs_get_order_by( $field = '', $value = '' ) {
	$orders = new CS\Database\Queries\Order();

	// Return order
	return $orders->get_item_by( $field, $value );
}

/**
 * Query for orders.
 *
 * @see \CS\Database\Queries\Order::__construct()
 *
 * @since 3.0
 *
 * @param array $args Arguments. See `CS\Database\Queries\Order` for
 *                    accepted arguments.
 * @return CS\Orders\Order[] Array of `Order` objects.
 */
function cs_get_orders( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'number' => 30,
	) );

	// Instantiate a query object
	$orders = new CS\Database\Queries\Order();

	// Return orders
	return $orders->query( $r );
}

/**
 * Count orders.
 *
 * @see \CS\Database\Queries\Order::__construct()
 *
 * @since 3.0
 *
 * @param array $args Arguments. See `CS\Database\Queries\Order` for
 *                    accepted arguments.
 * @return int Number of orders returned based on query arguments passed.
 */
function cs_count_orders( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'count' => true,
	) );

	// Query for count(s)
	$orders = new CS\Database\Queries\Order( $r );

	// Return count(s)
	return absint( $orders->found_items );
}

/**
 * Query for and return array of order counts, keyed by status.
 *
 * @see \CS\Database\Queries\Order::__construct()
 *
 * @since 3.0
 *
 * @param array $args Arguments. See `CS\Database\Queries\Order` for
 *                    accepted arguments.
 * @return array Order counts keyed by status.
 */
function cs_get_order_counts( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'count'   => true,
		'groupby' => 'status',
		'type'    => 'sale'
	) );

	// Query for count
	$counts = new CS\Database\Queries\Order( $r );

	// Format & return
	return cs_format_counts( $counts, $r['groupby'] );
}

/** Helpers *******************************************************************/

/**
 * Determine if an order ID is able to be trashed.
 *
 * @param $order_id
 *
 * @return bool
 */
function cs_is_order_trashable( $order_id ) {
	$order        = cs_get_order( $order_id );
	$is_trashable = false;

	if ( empty( $order ) ) {
		return $is_trashable;
	}

	$non_trashable_statuses = apply_filters( 'cs_non_trashable_statuses', array( 'trash' ) );
	if ( ! in_array( $order->status, $non_trashable_statuses ) ) {
		$is_trashable = true;
	}

	return (bool) apply_filters( 'cs_is_order_trashable', $is_trashable, $order );
}

/**
 * Determine if an order ID is able to be restored from the trash.
 *
 * @param $order_id
 *
 * @return bool
 */
function cs_is_order_restorable( $order_id ) {
	$order         = cs_get_order( $order_id );
	$is_restorable = false;

	if ( empty( $order ) ) {
		return $is_restorable;
	}

	if ( 'trash' === $order->status ) {
		$is_restorable = true;
	}

	return (bool) apply_filters( 'cs_is_order_restorable', $is_restorable, $order );
}

/**
 * Check if an order can be recovered.
 *
 * @since 3.0
 *
 * @param int $order_id Order ID.
 * @return bool True if the order can be recovered, false otherwise.
 */
function cs_is_order_recoverable( $order_id = 0 ) {
	$order = cs_get_order( $order_id );

	if ( ! $order ) {
		return false;
	}

	$recoverable_statuses = cs_recoverable_order_statuses();

	$transaction_id = $order->get_transaction_id();

	if ( in_array( $order->status, $recoverable_statuses, true ) && empty( $transaction_id ) ) {
		return true;
	}

	return false;
}

/**
 * Update the status of an entire order.
 *
 * @since 3.0
 *
 * @param int    $order_id   Order ID.
 * @param string $new_status New order status.
 *
 * @return bool True if the status was updated successfully, false otherwise.
 */
function cs_update_order_status( $order_id = 0, $new_status = '' ) {

	// Bail if order and status are empty
	if ( empty( $order_id ) || empty( $new_status ) ) {
		return false;
	}

	// Get the order
	$order = cs_get_order( $order_id );

	// Bail if order not found
	if ( empty( $order ) ) {
		return false;
	}

	/**
	 * For backwards compatibility purposes, we need an instance of CS_Payment so that the correct actions
	 * are invoked.
	 */
	$payment = cs_get_payment( $order_id );

	// Override to `publish`
	if ( in_array( $new_status, array( 'completed', 'publish' ), true ) ) {
		$new_status = 'complete';
	}

	// Get the old (current) status
	$old_status = $order->status;

	// We do not allow status changes if the status is the same to that stored in the database.
	// This prevents the `cs_update_payment_status` action from being triggered unnecessarily.
	if ( $old_status === $new_status ) {
		return false;
	}

	// Backwards compatibility
	$do_change = apply_filters( 'cs_should_update_payment_status', true,       $order_id, $new_status, $old_status );
	$do_change = apply_filters( 'cs_should_update_order_status',   $do_change, $order_id, $new_status, $old_status );

	$updated = false;

	if ( ! empty( $do_change ) ) {
		/**
		 * We need to update the status on the CS_Payment instance so that the
		 * correct actions are invoked if the status is changing to something
		 * that requires interception by the payment gateway (e.g. refunds).
		 */
		$payment->status = $new_status;
		$updated         = $payment->save();
	}

	return $updated;
}

/**
 * Generate the correct parameters required to insert a new order into the database
 * based on the order details passed by the gateway.
 *
 * @since 3.0
 *
 * @param array $order_data Order data.
 * @return int|bool Order ID if successful, false otherwise.
 */
function cs_build_order( $order_data = array() ) {

	// Bail if no order data passed.
	if ( empty( $order_data ) ) {
		return false;
	}

	/* Order recovery ********************************************************/

	$resume_order   = false;
	$existing_order = CS()->session->get( 'cs_resume_payment' );

	if ( ! empty( $existing_order ) ) {
		$order = cs_get_order( $existing_order );

		if ( $order ) {
			$recoverable_statuses = cs_recoverable_order_statuses();

			$transaction_id = $order->get_transaction_id();

			if ( in_array( $order->status, $recoverable_statuses, true ) && empty( $transaction_id ) ) {
				$payment      = cs_get_payment( $existing_order );
				$resume_order = true;
			}
		}
	}

	if ( $resume_order ) {
		$payment->date = date( 'Y-m-d G:i:s', current_time( 'timestamp' ) );

		$payment->add_note( __( 'Payment recovery processed', 'commercestore' ) );

		// Since things could have been added/removed since we first crated this...rebuild the cart details.
		foreach ( $payment->fees as $fee_index => $fee ) {
			$payment->remove_fee_by( 'index', $fee_index, true );
		}

		foreach ( $payment->downloads as $cart_index => $download ) {
			$item_args = array(
				'quantity'   => isset( $download['quantity'] ) ? $download['quantity'] : 1,
				'cart_index' => $cart_index,
			);
			$payment->remove_download( $download['id'], $item_args );
		}

		if ( strtolower( $payment->email ) !== strtolower( $order_data['user_info']['email'] ) ) {

			// Remove the payment from the previous customer.
			$previous_customer = new CS_Customer( $payment->customer_id );
			$previous_customer->remove_payment( $payment->ID, false );

			// Redefine the email first and last names.
			$payment->email      = $order_data['user_info']['email'];
			$payment->first_name = $order_data['user_info']['first_name'];
			$payment->last_name  = $order_data['user_info']['last_name'];
		}

		// Remove any remainders of possible fees from items.
		$payment->save();
	}

	/** Setup order information ***********************************************/

	$gateway = ! empty( $order_data['gateway'] ) ? $order_data['gateway'] : '';
	$gateway = empty( $gateway ) && isset( $_POST['cs-gateway'] ) // WPCS: CSRF ok.
		? sanitize_key( $_POST['cs-gateway'] )
		: $gateway;

	if ( ! $resume_order ) {

		// Allow for post_date to be passed in.
		if ( isset( $order_data['post_date'] ) ) {
			$order_data['date_created'] = $order_data['post_date'];
			unset( $order_data['post_date'] );
		}
	}

	// Build order information based on data passed from the gateway.
	$order_args = array(
		'parent'       => ! empty( $order_data['parent'] ) ? absint( $order_data['parent'] ) : '',
		'order_number' => '',
		'status'       => ! empty( $order_data['status'] ) ? $order_data['status'] : 'pending',
		'user_id'      => $order_data['user_info']['id'],
		'email'        => $order_data['user_info']['email'],
		'ip'           => cs_get_ip(),
		'gateway'      => $gateway,
		'mode'         => cs_is_test_mode() ? 'test' : 'live',
		'currency'     => ! empty( $order_data['currency'] ) ? $order_data['currency'] : cs_get_currency(),
		'payment_key'  => $order_data['purchase_key'],
		'date_created' => ! empty( $order_data['date_created'] ) ? $order_data['date_created'] : '',
	);

	/** Setup customer ********************************************************/

	$customer = new stdClass();

	if ( did_action( 'cs_pre_process_purchase' ) && is_user_logged_in() ) {
		$customer = new CS_Customer( get_current_user_id(), true );

		// Customer is logged in but used a different email to purchase so we need to assign that email address to their customer record.
		if ( ! empty( $customer->id ) && ( $order_args['email'] !== $customer->email ) ) {
			$customer->add_email( $order_args['email'] );
		}
	}

	if ( empty( $customer->id ) ) {
		$customer = new CS_Customer( $order_args['email'] );

		if ( empty( $order_data['user_info']['first_name'] ) && empty( $order_data['user_info']['last_name'] ) ) {
			$name = $order_args['email'];
		} else {
			$name = trim( $order_data['user_info']['first_name'] . ' ' . $order_data['user_info']['last_name'] );
		}

		$customer->create( array(
			'name'    => $name,
			'email'   => $order_args['email'],
			'user_id' => $order_args['user_id'],
		) );
	}

	// If the customer name was initially empty, update the record to store the name used at checkout.
	if ( empty( $customer->name ) ) {
		$customer->update( array(
			'name' => $order_data['user_info']['first_name'] . ' ' . $order_data['user_info']['last_name'],
		) );
	}

	$order_args['customer_id'] = $customer->id;

	$country = ! empty( $order_data['user_info']['address']['country'] )
		? $order_data['user_info']['address']['country']
		: false;

	$region = ! empty( $order_data['user_info']['address']['state'] )
		? $order_data['user_info']['address']['state']
		: false;

	// If taxes are enabled, get the tax rate for the order location.
	$tax_rate = false;
	if ( cs_use_taxes() ) {
		$tax_rate = cs_get_tax_rate_by_location(
			array(
				'country' => $country,
				'region'  => $region,
			)
		);

		if ( ! empty( $tax_rate->id ) ) {
			$order_args['tax_rate_id'] = $tax_rate->id;
		}

		// If no tax rate is found, then we'll save a percentage rate in order meta later.
	}

	/** Insert order **********************************************************/

	// Add order into the cs_orders table.
	$order_id = true === $resume_order
		? $payment->ID
		: cs_add_order( $order_args );

	// Attach order to the customer record.
	$customer->attach_payment( $order_id, false );

	// Declare variables to store amounts for the order.
	$subtotal       = 0.00;
	$total_tax      = 0.00;
	$total_discount = 0.00;
	$total_fees     = 0.00;
	$order_total    = 0.00;

	/** Insert order address *************************************************/

	$order_data['user_info']['address'] = isset( $order_data['user_info']['address'] )
		? $order_data['user_info']['address']
		: array();

	$order_data['user_info']['address'] = wp_parse_args( $order_data['user_info']['address'], array(
		'line1'   => '',
		'line2'   => '',
		'city'    => '',
		'zip'     => '',
		'country' => '',
		'state'   => '',
	) );

	$order_address_data = array(
		'order_id'    => $order_id,
		'name'        => $order_data['user_info']['first_name'] . ' ' . $order_data['user_info']['last_name'],
		'address'     => $order_data['user_info']['address']['line1'],
		'address2'    => $order_data['user_info']['address']['line2'],
		'city'        => $order_data['user_info']['address']['city'],
		'region'      => $order_data['user_info']['address']['state'],
		'country'     => $order_data['user_info']['address']['country'],
		'postal_code' => $order_data['user_info']['address']['zip'],
	);

	// Remove empty data.
	$order_address_data = array_filter( $order_address_data );

	// Add to cs_order_addresses table.
	cs_add_order_address( $order_address_data );

	// Maybe add the address to the cs_customer_addresses.
	$customer_address_data = $order_address_data;

	// We don't need to pass this data to cs_maybe_add_customer_address().
	unset( $customer_address_data['order_id'] );
	unset( $customer_address_data['first_name'] );
	unset( $customer_address_data['last_name'] );

	cs_maybe_add_customer_address( $customer->id, $customer_address_data );

	/** Insert order items ****************************************************/

	$decimal_filter = cs_currency_decimal_filter();

	if ( is_array( $order_data['cart_details'] ) && ! empty( $order_data['cart_details'] ) ) {

		foreach ( $order_data['cart_details'] as $key => $item ) {

			// First, we need to check that what is being added is a valid download.
			$download = cs_get_download( $item['id'] );

			// Skip if download is missing or not actually a download.
			if ( empty( $download ) || ( 'download' !== $download->post_type ) ) {
				continue;
			}

			// Get price ID.
			$price_id = isset( $item['item_number']['options']['price_id'] ) && is_numeric( $item['item_number']['options']['price_id'] )
				? absint( $item['item_number']['options']['price_id'] )
				: null;

			// Build a base array of information for each order item.
			$item['discount'] = isset( $item['discount'] )
				? $item['discount']
				: 0.00;

			$item['subtotal'] = isset( $item['subtotal'] )
				? $item['subtotal']
				: (float) $item['quantity'] * $item['item_price'];

			$item_name   = $item['name'];
			$option_name = cs_get_price_option_name( $item['id'], $price_id );
			if ( ! empty( $option_name ) ) {
				$item_name .= ' — ' . $option_name;
			}

			$order_item_args = array(
				'order_id'     => $order_id,
				'product_id'   => $item['id'],
				'product_name' => $item_name,
				'price_id'     => $price_id,
				'cart_index'   => $key,
				'type'         => 'download',
				'status'       => ! empty( $order_data['status'] ) ? $order_data['status'] : 'pending',
				'quantity'     => $item['quantity'],
				'amount'       => $item['item_price'],
				'subtotal'     => $item['subtotal'],
				'discount'     => $item['discount'],
				'tax'          => $item['tax'],
				'total'        => $item['price'],
				'item_price'   => $item['item_price'], // Added for backwards compatibility
				'date_created' => ! empty( $order_data['date_created'] ) ? $order_data['date_created'] : '',
			);

			/**
			 * Allow the order item arguments to be filtered.
			 *
			 * This is here for backwards compatibility purposes.
			 *
			 * @since 3.0
			 *
			 * @param array $order_item_args Order item arguments.
			 * @param int   $download->ID    Download ID.
			 */
			$order_item_args = apply_filters( 'cs_payment_add_download_args', $order_item_args, $download->ID );
			$order_item_args = wp_parse_args( $order_item_args, array(
				'quantity'   => 1,
				'price_id'   => null,
				'amount'     => false,
				'item_price' => false,
				'discount'   => 0.00,
				'tax'        => 0.00,
			) );

			// The item_price key could have been changed by a filter.
			// This exists for backwards compatibility purposes.
			$order_item_args['amount'] = $order_item_args['item_price'];
			unset( $order_item_args['item_price'] );

			// Try to use what's passed in via the args.
			if ( false !== $order_item_args['amount'] ) {
				$item_price = $order_item_args['amount'];

				// Deal with variable pricing.
			} elseif ( $download->has_variable_prices() ) {
				$prices = $download->get_prices();

				if ( $order_item_args['price_id'] && array_key_exists( $order_item_args['price_id'], (array) $prices ) ) {
					$item_price = $prices[ $order_item_args['price_id'] ]['amount'];
				} else {
					$item_price                  = cs_get_lowest_price_option( $download->ID );
					$order_item_args['price_id'] = cs_get_lowest_price_id( $download->ID );
				}

				// Fallback to getting it directly.
			} else {
				$item_price = cs_get_download_price( $download->ID );
			}

			// Sanitize price & quantity.
			$item_price = cs_sanitize_amount( $item_price );
			$quantity   = cs_item_quantities_enabled()
				? absint( $order_item_args['quantity'] )
				: 1;

			// Subtotal needs to be updated with the sanitized amount.
			$order_item_args['subtotal'] = round( $item_price * $quantity, $decimal_filter );

			if ( cs_prices_include_tax() ) {
				$order_item_args['subtotal'] -= round( $order_item_args['tax'], $decimal_filter );
			}

			$total = $order_item_args['subtotal'] - $order_item_args['discount'] + $order_item_args['tax'];

			// Do not allow totals to go negative
			// TODO: probably remove for handling returns
			if ( $total < 0 ) {
				$total = 0;
			}

			// Sanitize all the amounts.
			$order_item_args['amount']   = round( $item_price,                  $decimal_filter );
			$order_item_args['subtotal'] = round( $order_item_args['subtotal'], $decimal_filter );
			$order_item_args['tax']      = round( $order_item_args['tax'],      $decimal_filter );
			$order_item_args['total']    = round( $total,                       $decimal_filter );

			$order_item_id = cs_add_order_item( $order_item_args );

			if ( ! empty( $item['item_number']['options'] ) ) {
				// Collect any item_number options and store them.

				// Remove our price_id and quantity, as they are columns on the order item now.
				unset( $item['item_number']['options']['price_id'] );
				unset( $item['item_number']['options']['quantity'] );

				foreach ( $item['item_number']['options'] as $option_key => $value ) {
					$option_key = '_option_' . sanitize_key( $option_key );

					cs_add_order_item_meta( $order_item_id, $option_key, $value );
				}
			}

			// Store order item fees as adjustments.
			if ( isset( $item['fees'] ) && ! empty( $item['fees'] ) ) {
				foreach ( $item['fees'] as $fee_id => $fee ) {

					$adjustment_subtotal = floatval( $fee['amount'] );
					$tax_rate_amount     = empty( $tax_rate->amount ) ? false : $tax_rate->amount;
					$tax                 = CS()->fees->get_calculated_tax( $fee, $tax_rate_amount );
					$adjustment_total    = floatval( $fee['amount'] ) + $tax;
					$adjustment_data     = array(
						'object_id'   => $order_item_id,
						'object_type' => 'order_item',
						'type_key'    => $fee_id,
						'type'        => 'fee',
						'description' => $fee['label'],
						'subtotal'    => $adjustment_subtotal,
						'tax'         => $tax,
						'total'       => $adjustment_total,
					);

					// Add the adjustment.
					$adjustment_id = cs_add_order_adjustment( $adjustment_data );

					$total_fees += $adjustment_data['subtotal'];
					$total_tax  += $adjustment_data['tax'];
				}
			}

			$subtotal       += (float) $order_item_args['subtotal'];
			$total_tax      += (float) $order_item_args['tax'];
			$total_discount += (float) $order_item_args['discount'];
		}
	}

	/** Insert order adjustments **********************************************/

	// Insert fees.
	$fees = cs_get_cart_fees();

	// Process fees.
	if ( ! empty( $fees ) ) {
		foreach ( $fees as $fee_id => $fee ) {

			/*
			 * Skip if fee has a `download_id` assigned. If it does, it will have been added above when
			 * inserting order items.
			 */
			if ( ! empty( $fee['download_id'] ) ) {
				continue;
			}

			add_filter( 'cs_prices_include_tax', '__return_false' );

			$fee_subtotal    = floatval( $fee['amount'] );
			$tax_rate_amount = empty( $tax_rate->amount ) ? false : $tax_rate->amount;
			$tax             = CS()->fees->get_calculated_tax( $fee, $tax_rate_amount );
			$fee_total       = floatval( $fee['amount'] ) + $tax;

			remove_filter( 'cs_prices_include_tax', '__return_false' );

			$args = array(
				'object_id'   => $order_id,
				'object_type' => 'order',
				'type_key'    => $fee_id,
				'type'        => 'fee',
				'description' => $fee['label'],
				'subtotal'    => $fee_subtotal,
				'tax'         => $tax,
				'total'       => $fee_total,
			);

			// Add the adjustment.
			$adjustment_id = cs_add_order_adjustment( $args );

			$total_fees += (float) $fee['amount'];
			$total_tax  += $tax;
		}
	}

	// Insert discounts.
	$discounts = ! empty( $order_data['user_info']['discount'] )
		? $order_data['user_info']['discount']
		: array();


	if ( ! is_array( $discounts ) ) {
		/** @var string $discounts */
		$discounts = array_map( 'trim', explode( ',', $discounts ) );
	}

	if ( ! empty( $discounts ) && ( 'none' !== $discounts[0] ) ) {
		/** @var array $discounts */
		foreach ( $discounts as $discount ) {
			$discount = cs_get_discount_by( 'code', $discount );

			if ( false === $discount ) {
				continue;
			}

			$discount_amount = 0;
			$items           = $order_data['cart_details'];

			if ( is_array( $items ) && ! empty( $items ) ) {
				foreach ( $items as $key => $item ) {
					$discount_amount += cs_get_item_discount_amount( $item, $items, array( $discount ) );
				}
			}

			cs_add_order_adjustment(
				array(
					'object_id'   => $order_id,
					'object_type' => 'order',
					'type_id'     => $discount->id,
					'type'        => 'discount',
					'description' => $discount->code,
					'subtotal'    => $discount_amount,
					'total'       => $discount_amount,
				)
			);
		}
	}

	// Calculate order total (this needs more flexibility)
	$order_total =
		  $subtotal       // Total of all items
		- $total_discount // Total of all discounts
		+ $total_tax      // Total of all taxes
		+ $total_fees;    // Total of all fees

	// If we have tax, but no tax rate, manually save the percentage.
	if ( empty( $order_args['tax_rate_id'] ) && $total_tax > 0 ) {
		$cart_tax_rate_percentage = cs_get_cart_tax_rate( $country, $region );
		if ( ! empty( $cart_tax_rate_percentage ) ) {
			if ( $cart_tax_rate_percentage > 0 && $cart_tax_rate_percentage < 1 ) {
				$cart_tax_rate_percentage = $cart_tax_rate_percentage * 100;
			}

			cs_update_order_meta( $order_id, 'tax_rate', $cart_tax_rate_percentage );
		}
	}

	// Setup order number.
	if ( cs_get_option( 'enable_sequential' ) ) {
		$number = cs_get_next_payment_number();

		$order_args['order_number'] = cs_format_payment_number( $number );

		update_option( 'cs_last_payment_number', $number );
	}

	// Update the order with all of the newly computed values.
	cs_update_order( $order_id, array(
		'order_number' => $order_args['order_number'],
		'subtotal'     => $subtotal,
		'tax'          => $total_tax,
		'discount'     => $total_discount,
		'total'        => $order_total,
	) );

	if ( cs_get_option( 'show_agree_to_terms', false ) && ! empty( $_POST['cs_agree_to_terms'] ) ) { // WPCS: CSRF ok.
		$order_data['agree_to_terms_time'] = current_time( 'timestamp' );
	}

	if ( cs_get_option( 'show_agree_to_privacy_policy', false ) && ! empty( $_POST['cs_agree_to_privacy_policy'] ) ) { // WPCS: CSRF ok.
		$order_data['agree_to_privacy_time'] = current_time( 'timestamp' );
	}

	/**
	 * Fires after an order has been inserted.
	 *
	 * @internal This hook exists for backwards compatibility.
	 *
	 * @since 1.0
	 *
	 * @param int   $order_id   ID of the new order.
	 * @param array $order_data Array of original order data.
	 */
	do_action( 'cs_insert_payment', $order_id, $order_data );

	/**
	 * Executes after an order has been fully built from the sum of its parts.
	 *
	 * @since 3.0
	 *
	 * @param int   $order_id   ID of the new order.
	 * @param array $order_data Array of original order data.
	 */
	do_action( 'cs_built_order', $order_id, $order_data );

	// Return order ID, or false
	return ! empty( $order_id )
		? $order_id
		: false;
}

/**
 * Clone an existing order.
 *
 * @since 3.0
 *
 * @param int     $order_id            Order ID.
 * @param boolean $clone_relationships True to clone order items and adjustments,
 *                                     false otherwise.
 * @param array   $args                Arguments that are used in place of cloned
 *                                     order attributes.
 *
 * @return int|false New order ID on success, false on failure.
 */
function cs_clone_order( $order_id = 0, $clone_relationships = false, $args = array() ) {

	// Bail if no order ID passed.
	if ( empty( $order_id ) ) {
		return false;
	}

	// Fetch the order.
	$order = cs_get_order( $order_id );

	// Bail if the order was not found.
	if ( ! $order ) {
		return false;
	}

	// Parse arguments.
	$r = wp_parse_args( $args, $order->to_array() );

	// Remove order ID and order number.
	unset( $r['id'] );
	unset( $r['order_number'] );

	// Remove dates.
	unset( $r['date_created'] );
	unset( $r['date_modified'] );
	unset( $r['date_completed'] );
	unset( $r['date_refundable'] );

	// Remove payment key.
	unset( $r['payment_key'] );

	// Remove object vars.
	unset( $r['address'] );
	unset( $r['adjustments'] );
	unset( $r['items'] );

	$new_order_id = cs_add_order( $r );

	if ( $clone_relationships ) {
		$items = cs_get_order_items( array(
			'order_id' => $order_id,
		) );

		if ( $items ) {
			foreach ( $items as $item ) {
				$r = $item->to_array();

				// Remove original item data.
				unset( $r['id'] );
				unset( $r['date_created'] );
				unset( $r['date_modified'] );

				// Point order item to the new order ID.
				$r['order_id'] = $new_order_id;

				$order_item_id = cs_add_order_item( $r );

				$metadata = cs_get_order_item_meta( $item->id );

				if ( $metadata ) {
					foreach ( $metadata as $meta_key => $meta_value ) {
						cs_add_order_item_meta( $order_item_id, $meta_key, $meta_value );
					}
				}

				$adjustments = cs_get_order_adjustments( array(
					'object_id'   => $item->id,
					'object_type' => 'order_item',
				) );

				if ( $adjustments ) {
					foreach ( $adjustments as $adjustment ) {
						$r = $adjustment->to_array();

						// Remove original adjustment data.
						unset( $r['id'] );
						unset( $r['date_created'] );
						unset( $r['date_modified'] );

						// Point order item to the new order ID.
						$r['object_id']   = $order_item_id;
						$r['object_type'] = 'order_item';

						$adjustment_id = cs_add_order_adjustment( $r );

						$metadata = cs_get_order_adjustment_meta( $adjustment->id );

						if ( $metadata ) {
							foreach ( $metadata as $meta_key => $meta_value ) {
								cs_add_order_adjustment_meta( $adjustment_id, $meta_key, $meta_value );
							}
						}
					}
				}
			}
		}

		$adjustments = cs_get_order_adjustments( array(
			'object_id'   => $order_id,
			'object_type' => 'order',
		) );

		if ( $adjustments ) {
			foreach ( $adjustments as $adjustment ) {
				$r = $adjustment->to_array();

				// Remove original adjustment data.
				unset( $r['id'] );
				unset( $r['date_created'] );
				unset( $r['date_modified'] );

				// Point order item to the new order ID.
				$r['object_id']   = $new_order_id;
				$r['object_type'] = 'order';

				$adjustment_id = cs_add_order_adjustment( $r );

				$metadata = cs_get_order_adjustment_meta( $adjustment->id );

				if ( $metadata ) {
					foreach ( $metadata as $meta_key => $meta_value ) {
						cs_add_order_adjustment_meta( $adjustment_id, $meta_key, $meta_value );
					}
				}
			}
		}

		if ( $order->address ) {
			$r = $order->address->to_array();

			// Remove original address data.
			unset( $r['order_id'] );
			unset( $r['date_created'] );
			unset( $r['date_modified'] );

			$r['order_id'] = $new_order_id;

			cs_add_order_address( $r );
		}
	}

	return $new_order_id;
}

/**
 * Get the order status array keys that can be used to run reporting related to gross reporting.
 *
 * @since 3.0
 *
 * @return array An array of order status array keys that can be related to gross reporting.
 */
function cs_get_gross_order_statuses() {
	$statuses = array(
		'complete',
		'refunded',
		'partially_refunded',
		'revoked',
	);

	/**
	 * Statuses that affect gross order statistics.
	 *
	 * This filter allows extensions and developers to alter the statuses that can affect the reporting of gross
	 * sales statistics.
	 *
	 * @since 3.0
	 *
	 * @param array $statuses {
	 *     An array of order status array keys.
	 *
	 */
	return apply_filters( 'cs_gross_order_statuses', $statuses );
}

/**
 * Get the order status array keys that can be used to run reporting related to net reporting.
 *
 * @since 3.0
 *
 * @return array An array of order status array keys that can be related to net reporting.
 */
function cs_get_net_order_statuses() {
	$statuses = array(
		'complete',
		'partially_refunded',
		'revoked',
	);

	/**
	 * Statuses that affect net order statistics.
	 *
	 * This filter allows extensions and developers to alter the statuses that can affect the reporting of net
	 * sales statistics.
	 *
	 * @since 3.0
	 *
	 * @param array $statuses {
	 *     An array of order status array keys.
	 *
	 */
	return apply_filters( 'cs_net_order_statuses', $statuses );
}

/**
 * Get the order status array keys which are considered recoverable.
 *
 * @since 3.0
 * @return array An array of order status keys which are considered recoverable.
 */
function cs_recoverable_order_statuses() {
	$statuses = array( 'pending', 'abandoned', 'failed' );

	/**
	 * Order statuses which are considered recoverable.
	 *
	 * @param $statuses {
	 *        An array of order status array keys.
	 * }
	 */
	return apply_filters( 'cs_recoverable_payment_statuses', $statuses );
}

/**
 * Generate unique payment key for orders.
 *
 * @since 3.0
 * @param string $key Additional string used to help randomize key.
 * @return string
 */
function cs_generate_order_payment_key( $key ) {
	$auth_key    = defined( 'AUTH_KEY' ) ? AUTH_KEY : '';
	$payment_key = strtolower( md5( $key . gmdate( 'Y-m-d H:i:s' ) . $auth_key . uniqid( 'cs', true ) ) );

	/**
	 * Filters the payment key
	 *
	 * @since 3.0
	 * @param string $payment_key The value to be filtered
	 * @param string $key Additional string used to help randomize key.
	 * @return string
	 */
	return apply_filters( 'cs_generate_order_payment_key', $payment_key, $key );
}
