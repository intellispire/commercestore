<?php
/**
 * Discount Functions
 *
 * @package     CS
 * @subpackage  Functions
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add a discount.
 *
 * @since 1.0
 * @since 3.0 This function has been repurposed. Previously it was an internal admin callback for adding
 *        a discount via the UI. It's now used as a public function for inserting a new discount
 *        into the database.
 *
 * @param array $data Discount data.
 * @return int Discount ID.
 */
function cs_add_discount( $data = array() ) {

	// Juggle requirements and products.
	$product_requirements = isset( $data['product_reqs'] )      ? $data['product_reqs']      : null;
	$excluded_products    = isset( $data['excluded_products'] ) ? $data['excluded_products'] : null;
	$product_condition    = isset( $data['product_condition'] ) ? $data['product_condition'] : null;
	$pre_convert_args     = $data;
	unset( $data['product_reqs'], $data['excluded_products'], $data['product_condition'] );

	if ( isset( $data['expiration'] ) ) {
		$data['end_date'] = $data['expiration'];
		unset( $data['expiration'] );
	}

	if ( isset( $data['start'] ) ) {
		$data['start_date'] = $data['start'];
		unset( $data['start'] );
	}

	// Setup the discounts query.
	$discounts = new CS\Compat\Discount_Query();

	// Attempt to add the discount.
	$discount_id = $discounts->add_item( $data );

	// Maybe add requirements & exclusions.
	if ( ! empty( $discount_id ) ) {

		// Product requirements.
		if ( ! empty( $product_requirements ) ) {
			if ( is_string( $product_requirements ) ) {
				$product_requirements = maybe_unserialize( $product_requirements );
			}

			if ( is_array( $product_requirements ) ) {
				foreach ( $product_requirements as $product_requirement ) {
					cs_add_adjustment_meta( $discount_id, 'product_requirement', $product_requirement );
				}
			}
		}

		// Excluded products.
		if ( ! empty( $excluded_products ) ) {
			if ( is_string( $excluded_products ) ) {
				$excluded_products = maybe_unserialize( $excluded_products );
			}

			if ( is_array( $excluded_products ) ) {
				foreach ( $excluded_products as $excluded_product ) {
					cs_add_adjustment_meta( $discount_id, 'excluded_product', $excluded_product );
				}
			}
		}

		if ( ! empty( $product_condition ) ) {
			cs_add_adjustment_meta( $discount_id, 'product_condition', $product_condition );
		}

		// If the end date has passed, mark the discount as expired.
		cs_is_discount_expired( $discount_id );
	}

	/**
	 * Fires after the discount code is inserted. This hook exists for
	 * backwards compatibility purposes. It uses the $pre_convert_args variable
	 * to ensure the arguments maintain backwards compatible array keys.
	 *
	 * @since 2.7
	 *
	 * @param array $pre_convert_args Discount args.
	 * @param int   $return Discount  ID.
	 */
	do_action( 'cs_post_insert_discount', $pre_convert_args, $discount_id );

	// Return the new discount ID.
	return $discount_id;
}

/**
 * Delete a discount.
 *
 * @since 3.0
 *
 * @param int $discount_id Discount ID.
 * @return int
 */
function cs_delete_discount( $discount_id = 0 ) {
	$discount = cs_get_discount( $discount_id );

	// Do not allow for a discount to be deleted if it has been used.
	if ( $discount && 0 < $discount->use_count ) {
		return false;
	}

	$discounts = new CS\Compat\Discount_Query();

	// Pre-3.0 pre action.
	do_action( 'cs_pre_delete_discount', $discount_id );

	$retval = $discounts->delete_item( $discount_id );

	// Pre-3.0 post action.
	do_action( 'cs_post_delete_discount', $discount_id );

	return $retval;
}

/**
 * Get Discount.
 *
 * @since 1.0
 * @since 2.7 Updated to use CS_Discount object
 * @since 3.0 Updated to call use new query class.
 *
 * @param int $discount_id Discount ID.
 * @return \CS_Discount|bool CS_Discount object or false if not found.
 */
function cs_get_discount( $discount_id = 0 ) {
	$discounts = new CS\Compat\Discount_Query();

	// Return discount
	return $discounts->get_item( $discount_id );
}

/**
 * Get discount by code.
 *
 * @since 1.0
 * @since 2.7 Updated to use CS_Discount object
 * @since 3.0 Updated to call use new query class.
 *
 * @param string $code Discount code.
 * @return CS_Discount|bool CS_Discount object or false if not found.
 */
function cs_get_discount_by_code( $code = '' ) {
	return cs_get_discount_by( 'code', $code );
}

/**
 * Retrieve discount by a given field
 *
 * @since 2.0
 * @since 2.7 Updated to use CS_Discount object
 * @since 3.0 Updated to call use new query class.
 *
 * @param string $field The field to retrieve the discount with.
 * @param mixed  $value The value for $field.
 * @return mixed CS_Discount|bool CS_Discount object or false if not found.
 */
function cs_get_discount_by( $field = '', $value = '' ) {
	$discounts = new CS\Compat\Discount_Query();

	// Return discount
	return $discounts->get_item_by( $field, $value );
}

/**
 * Retrieve discount by a given field
 *
 * @since 2.0
 * @since 2.7 Updated to use CS_Discount object
 * @since 3.0 Updated to call cs_get_discount()
 *
 * @param int $discount_id Discount ID.
 * @param string $field The field to retrieve the discount with.
 * @return mixed object|bool CS_Discount object or false if not found.
 */
function cs_get_discount_field( $discount_id, $field = '' ) {
	$discount = cs_get_discount( $discount_id );

	// Check that field exists
	return isset( $discount->{$field} )
		? $discount->{$field}
		: null;
}

/**
 * Update a discount
 *
 * @since 3.0
 * @param int $discount_id Discount ID.
 * @param array $data
 * @return int
 */
function cs_update_discount( $discount_id = 0, $data = array() ) {

	// Pre-3.0 pre action
	do_action( 'cs_pre_update_discount', $data, $discount_id );

	// Product requirements.
	if ( isset( $data['product_reqs'] ) && ! empty( $data['product_reqs'] ) ) {
		if ( is_string( $data['product_reqs'] ) ) {
			$data['product_reqs'] = maybe_unserialize( $data['product_reqs'] );
		}

		if ( is_array( $data['product_reqs'] ) ) {
			cs_delete_adjustment_meta( $discount_id, 'product_requirement' );

			foreach ( $data['product_reqs'] as $product_requirement ) {
				cs_add_adjustment_meta( $discount_id, 'product_requirement', $product_requirement );
			}
		}

		unset( $data['product_reqs'] );
	} elseif ( isset( $data['product_reqs'] ) ) {
		cs_delete_adjustment_meta( $discount_id, 'product_requirement' );

		// We don't have product conditions when there are no product requirements.
		cs_delete_adjustment_meta( $discount_id, 'product_condition' );
		unset( $data['product_condition'] );
	}

	// Excluded products are handled differently.
	if ( isset( $data['excluded_products'] ) && ! empty( $data['excluded_products'] ) ) {
		if ( is_string( $data['excluded_products'] ) ) {
			$data['excluded_products'] = maybe_unserialize( $data['excluded_products'] );
		}

		if ( is_array( $data['excluded_products'] ) ) {
			cs_delete_adjustment_meta( $discount_id, 'excluded_product' );

			foreach ( $data['excluded_products'] as $excluded_product ) {
				cs_add_adjustment_meta( $discount_id, 'excluded_product', $excluded_product );
			}
		}

		unset( $data['excluded_products'] );
	} elseif( isset( $data['excluded_products'] ) ) {
		cs_delete_adjustment_meta( $discount_id, 'excluded_product' );
	}

	if ( isset( $data['product_condition'] ) ) {
		$product_condition = sanitize_text_field( $data['product_condition'] );
		cs_update_adjustment_meta( $discount_id, 'product_condition', $product_condition );
	}

	$discounts = new CS\Compat\Discount_Query();

	$retval = $discounts->update_item( $discount_id, $data );

	// Pre-3.0 post action
	do_action( 'cs_post_update_discount', $data, $discount_id );

	return $retval;
}

/**
 * Get Discounts
 *
 * Retrieves an array of all available discount codes.
 *
 * @since 1.0
 * @param array $args Query arguments
 * @return mixed array if discounts exist, false otherwise
 */
function cs_get_discounts( $args = array() ) {

	// Parse arguments.
	$r = wp_parse_args( $args, array(
		'number' => 30
	) );

	// Back compat for old query arg.
	if ( isset( $r['posts_per_page'] ) ) {
		$r['number'] = $r['posts_per_page'];
	}

	// Instantiate a query object.
	$discounts = new CS\Compat\Discount_Query();

	// Return discounts
	return $discounts->query( $r );
}

/**
 * Return total number of discounts
 *
 * @since 3.0
 *
 * @param array $args Arguments.
 * @return int
 */
function cs_get_discount_count( $args = array() ) {

	// Parse args.
	$r = wp_parse_args( $args, 	array(
		'count' => true
	) );

	// Query for count(s).
	$discounts = new CS\Compat\Discount_Query( $r );

	// Return count(s).
	return absint( $discounts->found_items );
}

/**
 * Query for and return array of discount counts, keyed by status.
 *
 * @since 3.0
 *
 * @return array
 */
function cs_get_discount_counts( $args = array() ) {

	// Parse arguments
	$r = wp_parse_args( $args, array(
		'count'   => true,
		'groupby' => 'status'
	) );

	// Query for count.
	$counts = new CS\Compat\Discount_Query( $r );

	// Format & return
	return cs_format_counts( $counts, $r['groupby'] );
}

/**
 * Query for discount notes.
 *
 * @since 3.0
 *
 * @param int $discount_id Discount ID.
 * @return array Retrieved notes.
 */
function cs_get_discount_notes( $discount_id = 0 ) {
	return cs_get_notes( array(
		'object_id'   => $discount_id,
		'object_type' => 'discount',
		'order'       => 'asc'
	) );
}

/**
 * Checks if there is any active discounts, returns a boolean.
 *
 * @since 1.0
 * @since 3.0 Updated to be more efficient and make direct calls to the CS_Discount object.
 *
 * @return bool
 */
function cs_has_active_discounts() {

	// Query for active discounts.
	$discounts = cs_get_discounts( array(
		'number' => 10,
		'status' => 'active'
	) );

	// Bail if none.
	if ( empty( $discounts ) ) {
		return false;
	}

	// Check each discount for active status, applying filters, etc...
	foreach ( $discounts as $discount ) {
		/** @var $discount CS_Discount */
		if ( $discount->is_active( false, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Stores a discount code. If the code already exists, it updates it, otherwise
 * it creates a new one.
 *
 * @internal This method exists for backwards compatibility. `cs_add_discount()` should be used.
 *
 * @since 1.0
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to use new query class.
 *
 * @param array $details     Discount args.
 * @param int   $discount_id Discount ID.
 * @return mixed bool|int The discount ID of the discount code, or false on failure.
 */
function cs_store_discount( $details, $discount_id = null ) {

	// Set default return value to false.
	$return = false;

	// Back-compat for start date.
	if ( isset( $details['start'] ) && strstr( $details['start'], '/' ) ) {
		$details['start_date'] = date( 'Y-m-d', strtotime( $details['start'] ) ) . ' 00:00:00';
		unset( $details['start'] );
	}

	// Back-compat for end date.
	if ( isset( $details['expiration'] ) && strstr( $details['expiration'], '/' ) ) {
		$details['end_date'] = date( 'Y-m-d', strtotime( $details['expiration'] ) ) . ' 23:59:59';
		unset( $details['expiration'] );
	}

	/**
	 * Filters the args before being inserted into the database. This hook
	 * exists for backwards compatibility purposes.
	 *
	 * @since 2.7
	 *
	 * @param array $details Discount args.
	 */
	$details = apply_filters( 'cs_insert_discount', $details );

	/**
	 * Fires before the discount has been added to the database. This hook
	 * exists for backwards compatibility purposes. It fires before the
	 * call to `CS_Discount::convert_legacy_args` to ensure the arguments
	 * maintain backwards compatible array keys.
	 *
	 * @since 2.7
	 *
	 * @param array $details Discount args.
	 */
	do_action( 'cs_pre_insert_discount', $details );

	// Convert legacy arguments to new ones accepted by `cs_add_discount()`.
	$details = CS_Discount::convert_legacy_args( $details );

	if ( null === $discount_id ) {
		$return = (int) cs_add_discount( $details );
	} else {
		cs_update_discount( $discount_id, $details );
		$return = $discount_id;
	}

	return $return;
}

/**
 * Deletes a discount code.
 *
 * @internal This method exists for backwards compatibility. `cs_delete_discount()` should be used.
 *
 * @since 1.0
 * @deprecated 3.0
 *
 * @param int $discount_id Discount ID.
 */
function cs_remove_discount( $discount_id = 0 ) {
	cs_delete_discount( $discount_id );
}

/**
 * Updates a discount status from one status to another.
 *
 * @since 1.0
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount()
 *
 * @param int    $discount_id Discount ID (default: 0)
 * @param string $new_status  New status (default: active)
 *
 * @return bool Whether the status has been updated or not.
 */
function cs_update_discount_status( $discount_id = 0, $new_status = 'active' ) {

	// Bail if an invalid ID is passed.
	if ( $discount_id <= 0 ) {
		return false;
	}

	// Set defaults.
	$updated    = false;
	$new_status = sanitize_key( $new_status );
	$discount   = cs_get_discount( $discount_id );

	// No change.
	if ( $new_status === $discount->status ) {
		return true;
	}

	// Try to update status.
	if ( ! empty( $discount->id ) ) {
		$updated = (bool) cs_update_discount( $discount->id, array(
			'status' => $new_status
		) );
	}

	// Return.
	return $updated;
}

/**
 * Checks to see if a discount code already exists.
 *
 * @since 1.0
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount().
 *
 * @param int $discount_id Discount ID.
 *
 * @return bool Whether or not the discount exists.
 */
function cs_discount_exists( $discount_id ) {
	$discount = cs_get_discount( $discount_id );

	return $discount instanceof CS_Discount && $discount->exists();
}

/**
 * Checks whether a discount code is active.
 *
 * @since 1.0
 * @since 2.6.11 Added $update parameter.
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount().
 *
 * @param int  $discount_id Discount ID.
 * @param bool $update      Update the discount to expired if an one is found but has an active status/
 * @param bool $set_error   Whether an error message should be set in session.
 * @return bool Whether or not the discount is active.
 */
function cs_is_discount_active( $discount_id = 0, $update = true, $set_error = true ) {
	$discount = cs_get_discount( $discount_id );

	if ( ! $discount instanceof CS_Discount ) {
		return false;
	}

	return $discount->is_active( $update, $set_error );
}

/**
 * Retrieve the discount code.
 *
 * @since 1.4
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_field()
 *
 * @param int $discount_id Discount ID.
 * @return string $code Discount Code.
 */
function cs_get_discount_code( $discount_id = 0 ) {
	return cs_get_discount_field( $discount_id, 'code' );
}

/**
 * Retrieve the discount code start date.
 *
 * @since 1.4
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_field()
 *
 * @param int $discount_id Discount ID.
 * @return string $start Discount start date.
 */
function cs_get_discount_start_date( $discount_id = 0 ) {
	return cs_get_discount_field( $discount_id, 'start_date' );
}

/**
 * Retrieve the discount code expiration date.
 *
 * @since 1.4
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_field()
 *
 * @param int $discount_id Discount ID.
 * @return string $expiration Discount expiration.
 */
function cs_get_discount_expiration( $discount_id = 0 ) {
	return cs_get_discount_field( $discount_id, 'end_date' );
}

/**
 * Retrieve the maximum uses that a certain discount code.
 *
 * @since 1.4
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_field()
 *
 * @param int $discount_id Discount ID.
 * @return int $max_uses Maximum number of uses for the discount code.
 */
function cs_get_discount_max_uses( $discount_id = 0 ) {
	return cs_get_discount_field( $discount_id, 'max_uses' );
}

/**
 * Retrieve number of times a discount has been used.
 *
 * @since 1.4
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_field().
 *
 * @param int $discount_id Discount ID.
 * @return int $uses Number of times a discount has been used.
 */
function cs_get_discount_uses( $discount_id = 0 ) {
	return (int) cs_get_discount_field( $discount_id, 'use_count' );
}

/**
 * Retrieve the minimum purchase amount for a discount.
 *
 * @since 1.4
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_field().
 *
 * @param int $discount_id Discount ID.
 * @return float $min_price Minimum purchase amount.
 */
function cs_get_discount_min_price( $discount_id = 0 ) {
	return cs_format_amount( cs_get_discount_field( $discount_id, 'min_charge_amount' ) );
}

/**
 * Retrieve the discount amount.
 *
 * @since 1.4
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_field().
 *
 * @param int $discount_id Discount ID.
 * @return float $amount Discount amount.
 */
function cs_get_discount_amount( $discount_id = 0 ) {
	return cs_get_discount_field( $discount_id, 'amount' );
}

/**
 * Retrieve the discount type
 *
 * @since 1.4
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_field().
 *
 * @param int $discount_id Discount ID.
 * @return string $type Discount type
 */
function cs_get_discount_type( $discount_id = 0 ) {
	return cs_get_discount_field( $discount_id, 'type' );
}

/**
 * Retrieve the products the discount cannot be applied to.
 *
 * @since 1.9
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount()
 *
 * @param int $discount_id Discount ID.
 * @return array $excluded_products IDs of the required products.
 */
function cs_get_discount_excluded_products( $discount_id = 0 ) {
	$discount = cs_get_discount( $discount_id );

	return $discount instanceof CS_Discount ? $discount->excluded_products : array();
}

/**
 * Retrieve the discount product requirements.
 *
 * @since 1.5
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount()
 *
 * @param int $discount_id Discount ID.
 * @return array $product_reqs IDs of the required products.
 */
function cs_get_discount_product_reqs( $discount_id = 0 ) {
	$discount = cs_get_discount( $discount_id );

	return $discount instanceof CS_Discount ? $discount->product_reqs : array();
}

/**
 * Retrieve the product condition.
 *
 * @since 1.5
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_field()
 *
 * @param int $discount_id Discount ID.
 *
 * @return string Product condition.
 */
function cs_get_discount_product_condition( $discount_id = 0 ) {
	$discount = cs_get_discount( $discount_id );

	return $discount instanceof CS_Discount ? $discount->product_condition : '';
}

/**
 * Retrieves the discount status label.
 *
 * @since 2.9
 *
 * @param int $discount_id Discount ID.
 * @return string Product condition.
 */
function cs_get_discount_status_label( $discount_id = null ) {
	$discount = cs_get_discount( $discount_id );

	return $discount instanceof CS_Discount ? $discount->get_status_label() : '';
}

/**
 * Check if a discount is not global.
 *
 * By default discounts are applied to all products in the cart. Non global discounts are
 * applied only to the products selected as requirements.
 *
 * @since 1.5
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Please use cs_get_discount_scope() instead.
 *
 * @param int $discount_id Discount ID.
 *
 * @return boolean Whether or not discount code is not global.
 */
function cs_is_discount_not_global( $discount_id = 0 ) {
	return ( 'not_global' === cs_get_discount_field( $discount_id, 'scope' ) );
}

/**
 * Retrieve the discount scope.
 *
 * By default this will return "global" as discounts are applied to all products in the cart. Non global discounts are
 * applied only to the products selected as requirements.
 *
 * @since 3.0
 *
 * @param int $discount_id Discount ID.
 *
 * @return string global or not_global.
 */
function cs_get_discount_scope( $discount_id = 0 ) {
	return cs_get_discount_field( $discount_id, 'scope' );
}

/**
 * Checks whether a discount code is expired.
 *
 * @since 1.0
 * @since 2.6.11 Added $update parameter.
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount()
 *
 * @param int  $discount_id Discount ID.
 * @param bool $update  Update the discount to expired if an one is found but has an active status.
 * @return bool Whether on not the discount has expired.
 */
function cs_is_discount_expired( $discount_id = 0, $update = true ) {
	$discount = cs_get_discount( $discount_id );
	return ! empty( $discount->id )
		? $discount->is_expired( $update )
		: false;
}

/**
 * Checks whether a discount code is available to use yet (start date).
 *
 * @since 1.0
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount()
 *
 * @param int  $discount_id   Discount ID.
 * @param bool $set_error Whether an error message be set in session.
 * @return bool Is discount started?
 */
function cs_is_discount_started( $discount_id = 0, $set_error = true ) {
	$discount = cs_get_discount( $discount_id );
	return ! empty( $discount->id )
		? $discount->is_started( $set_error )
		: false;
}

/**
 * Is Discount Maxed Out.
 *
 * @since 1.0
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount()
 *
 * @param int  $discount_id   Discount ID.
 * @param bool $set_error Whether an error message be set in session.
 * @return bool Is discount maxed out?
 */
function cs_is_discount_maxed_out( $discount_id = 0, $set_error = true ) {
	$discount = cs_get_discount( $discount_id );
	return ! empty( $discount->id )
		? $discount->is_maxed_out( $set_error )
		: false;
}

/**
 * Checks to see if the minimum purchase amount has been met.
 *
 * @since 1.1.7
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount()
 *
 * @param int  $discount_id   Discount ID.
 * @param bool $set_error Whether an error message be set in session.
 * @return bool Whether the minimum amount has been met or not.
 */
function cs_discount_is_min_met( $discount_id = 0, $set_error = true ) {
	$discount = cs_get_discount( $discount_id );
	return ! empty( $discount->id )
		? $discount->is_min_price_met( $set_error )
		: false;
}

/**
 * Is the discount limited to a single use per customer?
 *
 * @since 1.5
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_field()
 *
 * @param int $discount_id Discount ID.
 *
 * @return bool Whether the discount is single use or not.
 */
function cs_discount_is_single_use( $discount_id = 0 ) {
	return (bool) cs_get_discount_field( $discount_id, 'once_per_customer' );
}

/**
 * Checks to see if the required products are in the cart
 *
 * @since 1.5
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount()
 *
 * @param int  $discount_id   Discount ID.
 * @param bool $set_error Whether an error message be set in session.
 * @return bool Are required products in the cart for the discount to hold.
 */
function cs_discount_product_reqs_met( $discount_id = 0, $set_error = true ) {
	$discount = cs_get_discount( $discount_id );

	return $discount instanceof CS_Discount && $discount->is_product_requirements_met( $set_error );
}

/**
 * Checks to see if a user has already used a discount.
 *
 * @since 1.1.5
 * @since 1.5 Added $discount_id parameter.
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount()
 *
 * @param string $code      Discount Code.
 * @param string $user      User info.
 * @param int    $discount_id   Discount ID.
 * @param bool   $set_error Whether an error message be set in session
 *
 * @return bool $return Whether the the discount code is used.
 */
function cs_is_discount_used( $code = null, $user = '', $discount_id = 0, $set_error = true ) {
	$discount = ( null == $code )
		? cs_get_discount( $discount_id )
		: cs_get_discount_by_code( $code );

	return $discount instanceof CS_Discount && $discount->is_used( $user, $set_error );
}

/**
 * Check whether a discount code is valid (when purchasing).
 *
 * @since 1.0
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_by_code()
 *
 * @param string $code      Discount Code.
 * @param string $user      User info.
 * @param bool   $set_error Whether an error message be set in session.
 * @return bool Whether the discount code is valid.
 */
function cs_is_discount_valid( $code = '', $user = '', $set_error = true ) {
	$discount = cs_get_discount_by_code( $code );

	if ( ! empty( $discount->id ) ) {
		return $discount->is_valid( $user, $set_error );
	} elseif ( $set_error ) {
		cs_set_error( 'cs-discount-error', _x( 'This discount is invalid.', 'error for when a discount is invalid based on its configuration', 'commercestore' ) );
		return false;
	} else {
		return false;
	}
}

/**
 * Retrieves a discount ID from the code.
 *
 * @since 1.0
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_by_code()
 *
 * @param string $code Discount code.
 * @return int|bool Discount ID, or false if discount does not exist.
 */
function cs_get_discount_id_by_code( $code = '' ) {
	$discount = cs_get_discount_by_code( $code );

	return ( $discount instanceof CS_Discount ) ? $discount->id : false;
}

/**
 * Get Discounted Amount.
 *
 * @since 1.0
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_by_code()
 *
 * @param string           $code       Code to calculate a discount for.
 * @param mixed string|int $base_price Price before discount.
 * @return string Amount after discount.
 */
function cs_get_discounted_amount( $code = '', $base_price = 0 ) {
	$discount = cs_get_discount_by_code( $code );

	return ! empty( $discount->id )
		? $discount->get_discounted_amount( $base_price )
		: $base_price;
}

/**
 * Increases the use count of a discount code.
 *
 * @since 1.0
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_by_code()
 *
 * @param string $code Discount code to be incremented.
 * @return int New usage.
 */
function cs_increase_discount_usage( $code = '' ) {
	$discount = cs_get_discount_by_code( $code );

	// Increase if discount exists
	return ! empty( $discount->id )
		? (int) $discount->increase_usage()
		: false;
}

/**
 * Decreases the use count of a discount code.
 *
 * @since 2.5.7
 * @since 2.7 Updated to use CS_Discount object.
 * @since 3.0 Updated to call cs_get_discount_by_code()
 *
 * @param string $code Discount code to be decremented.
 * @return int New usage.
 */
function cs_decrease_discount_usage( $code = '' ) {
	$discount = cs_get_discount_by_code( $code );

	// Decrease if discount exists
	return ! empty( $discount->id )
		? (int) $discount->decrease_usage()
		: false;
}

/**
 * Format Discount Rate
 *
 * @since 1.0
 * @param string $type Discount code type
 * @param string|int $amount Discount code amount
 * @return string $amount Formatted amount
 */
function cs_format_discount_rate( $type = '', $amount = '' ) {
	return ( 'flat' === $type )
		? cs_currency_filter( cs_format_amount( $amount ) )
		: cs_format_amount( $amount ) . '%';
}

/**
 * Retrieves a discount amount for an item.
 *
 * Calculates an amount based on the context of other items.
 *
 * @since 3.0
 *
 * @global float $cs_flat_discount_total Track flat rate discount total for penny adjustments.
 * @link https://github.com/commercestore/commercestore/issues/2757
 *
 * @param array                    $item {
 *   Order Item data, matching Cart line item format.
 *
 *   @type string $id       Download ID.
 *   @type array  $options {
 *     Download options.
 *
 *     @type string $price_id Download Price ID.
 *   }
 *   @type int    $quantity Purchase quantity.
 * }
 * @param array                    $items     All items (including item being calculated).
 * @param \CS_Discount[]|string[] $discounts Discount to determine adjustment from.
 *                                            A discount code can be passed as a string.
 * @return float Discount amount. 0 if Discount is invalid or no Discount is applied.
 */
function cs_get_item_discount_amount( $item, $items, $discounts ) {
	global $cs_flat_discount_total;

	// Validate item.
	if ( empty( $item ) || empty( $item['id'] ) ) {
		return 0;
	}

	if ( ! isset( $item['quantity'] ) ) {
		return 0;
	}

	if ( ! isset( $item['options'] ) ) {
		$item['options'] = array();

		/*
		 * Support for variable pricing when the `item_number` key is set (cart details).
		 */
		if ( isset( $item['item_number']['options'] ) ) {
			$item['options'] = $item['item_number']['options'];
		}
	}

	// Validate and normalize Discounts.
	$discounts = array_map(
		function( $discount ) {
			// Convert a Discount code to a Discount object.
			if ( is_string( $discount ) ) {
				$discount = cs_get_discount_by_code( $discount );
			}

			if ( ! $discount instanceof \CS_Discount ) {
				return false;
			}

			return $discount;
		},
		$discounts
	);

	$discounts = array_filter( $discounts );

	// Determine the price of the item.
	if ( cs_has_variable_prices( $item['id'] ) ) {
		// Mimics the original behavior of `\CS_Cart::get_item_amount()` that
		// does not fallback to the first Price ID if none is provided.
		if ( ! isset( $item['options']['price_id'] ) ) {
			return 0;
		}

		$item_unit_price = cs_get_price_option_amount( $item['id'], $item['options']['price_id'] );
	} else {
		$item_unit_price = cs_get_download_price( $item['id'] );
	}

	$item_amount     = ( $item_unit_price * $item['quantity'] );
	$discount_amount = 0;

	foreach ( $discounts as $discount ) {
		$reqs              = $discount->get_product_reqs();
		$excluded_products = $discount->get_excluded_products();

		// Make sure requirements are set and that this discount shouldn't apply to the whole cart.
		if ( ! empty( $reqs ) && 'global' !== $discount->get_scope() ) {
			// This is a product(s) specific discount.
			foreach ( $reqs as $download_id ) {
				if ( $download_id == $item['id'] && ! in_array( $item['id'], $excluded_products ) ) {
					$discount_amount += ( $item_amount - $discount->get_discounted_amount( $item_amount ) );
				}
			}
		} else {
			// This is a global cart discount.
			if ( ! in_array( $item['id'], $excluded_products ) ) {
				if ( 'flat' === $discount->get_type() ) {
					// In order to correctly record individual item amounts, global flat rate discounts
					// are distributed across all items.
					//
					// The discount amount is divided by the number of items in the cart and then a
					// portion is evenly applied to each item.
					$items_amount = 0;

					foreach ( $items as $i ) {
						if ( ! in_array( $i['id'], $excluded_products ) ) {
							if ( cs_has_variable_prices( $i['id'] ) ) {
								$i_amount = cs_get_price_option_amount( $i['id'], $i['options']['price_id'] );
							} else {
								$i_amount = cs_get_download_price( $i['id'] );
							}

							$items_amount += ( $i_amount * $i['quantity'] );
						}
					}

					$subtotal_percent = ! empty( $items_amount ) ? ( $item_amount / $items_amount ) : 0;
					$discount_amount += ( $discount->get_amount() * $subtotal_percent );

					$cs_flat_discount_total += round( $discount_amount, cs_currency_decimal_filter() );

					if ( $item['id'] === end( $items )['id'] && $cs_flat_discount_total < $discount->get_amount() ) {
						$adjustment       = ( $discount->get_amount() - $cs_flat_discount_total );
						$discount_amount += $adjustment;
					}

					if ( $discount_amount > $item_amount ) {
						$discount_amount = $item_amount;
					}
				} else {
					$discount_amount += ( $item_amount - $discount->get_discounted_amount( $item_amount ) );
				}
			}
		}
	}

	return $discount_amount;
}

/** Cart **********************************************************************/

/**
 * Set the active discount for the shopping cart
 *
 * @since 1.4.1
 * @param string $code Discount code
 * @return string[] All currently active discounts
 */
function cs_set_cart_discount( $code = '' ) {

	// Get all active cart discounts
	if ( cs_multiple_discounts_allowed() ) {
		$discounts = cs_get_cart_discounts();

	// Only one discount allowed per purchase, so override any existing
	} else {
		$discounts = false;
	}

	if ( $discounts ) {
		$key = array_search( strtolower( $code ), array_map( 'strtolower', $discounts ) );

		// Can't set the same discount more than once
		if ( false !== $key ) {
			unset( $discounts[ $key ] );
		}
		$discounts[] = $code;
	} else {
		$discounts = array();
		$discounts[] = $code;
	}

	CS()->session->set( 'cart_discounts', implode( '|', $discounts ) );

	do_action( 'cs_cart_discount_set', $code, $discounts );
	do_action( 'cs_cart_discounts_updated', $discounts );

	return $discounts;
}

/**
 * Remove an active discount from the shopping cart
 *
 * @since 1.4.1
 * @param string $code Discount code
 * @return array $discounts All remaining active discounts
 */
function cs_unset_cart_discount( $code = '' ) {
	$discounts = cs_get_cart_discounts();

	if ( $discounts ) {
		$discounts = array_map( 'strtoupper', $discounts );
		$key       = array_search( strtoupper( $code ), $discounts );

		if ( false !== $key ) {
			unset( $discounts[ $key ] );
		}

		$discounts = implode( '|', array_values( $discounts ) );
		// update the active discounts
		CS()->session->set( 'cart_discounts', $discounts );
	}

	do_action( 'cs_cart_discount_removed', $code, $discounts );
	do_action( 'cs_cart_discounts_updated', $discounts );

	return $discounts;
}

/**
 * Remove all active discounts
 *
 * @since 1.4.1
 * @return void
 */
function cs_unset_all_cart_discounts() {
	CS()->cart->remove_all_discounts();
}

/**
 * Retrieve the currently applied discount
 *
 * @since 1.4.1
 * @return array $discounts The active discount codes
 */
function cs_get_cart_discounts() {
	return CS()->cart->get_discounts();
}

/**
 * Check if the cart has any active discounts applied to it
 *
 * @since 1.4.1
 * @return bool
 */
function cs_cart_has_discounts() {
	return CS()->cart->has_discounts();
}

/**
 * Retrieves the total discounted amount on the cart
 *
 * @since 1.4.1
 *
 * @param bool $discounts Discount codes
 *
 * @return float|mixed|void Total discounted amount
 */
function cs_get_cart_discounted_amount( $discounts = false ) {
	return CS()->cart->get_discounted_amount( $discounts );
}

/**
 * Get the discounted amount on a price
 *
 * @since 1.9
 * @param array $item Cart item array
 * @param bool|string $discount False to use the cart discounts or a string to check with a discount code
 * @return float The discounted amount
 */
function cs_get_cart_item_discount_amount( $item = array(), $discount = false ) {
	return CS()->cart->get_item_discount_amount( $item, $discount );
}

/**
 * Outputs the HTML for all discounts applied to the cart
 *
 * @since 1.4.1
 *
 * @return void
 */
function cs_cart_discounts_html() {
	echo cs_get_cart_discounts_html();
}

/**
 * Retrieves the HTML for all discounts applied to the cart
 *
 * @since 1.4.1
 *
 * @param mixed $discounts Array of cart discounts.
 * @return mixed|void
 */
function cs_get_cart_discounts_html( $discounts = false ) {
	if ( ! $discounts ) {
		$discounts = CS()->cart->get_discounts();
	}

	if ( empty( $discounts ) ) {
		return;
	}

	$html = _n( 'Discount', 'Discounts', count( $discounts ), 'commercestore' ) . ':&nbsp;';

	foreach ( $discounts as $discount ) {
		$discount_id     = cs_get_discount_id_by_code( $discount );
		$discount_amount = 0;
		$items           = CS()->cart->get_contents();

		if ( is_array( $items ) && ! empty( $items ) ) {
			foreach ( $items as $key => $item ) {
				$discount_amount += cs_get_item_discount_amount( $item, $items, array( $discount ) );
			}
		}

		$type = cs_get_discount_type( $discount_id );
		$rate = cs_format_discount_rate( $type, cs_get_discount_amount( $discount_id ) );

		$remove_url  = add_query_arg(
			array(
				'cs_action'    => 'remove_cart_discount',
				'discount_id'   => $discount_id,
				'discount_code' => $discount
			),
			cs_get_checkout_uri()
		);

		$discount_html   = '';
		$discount_html  .= "<span class=\"cs_discount\">\n";
		$discount_amount = cs_currency_filter( cs_format_amount( $discount_amount ) );
		$discount_html  .= "<span class=\"cs_discount_total\">{$discount}&nbsp;&ndash;&nbsp;{$discount_amount}</span>\n";
		if ( 'percent' === $type ) {
			$discount_html .= "<span class=\"cs_discount_rate\">($rate)</span>\n";
		}
		$discount_html .= sprintf(
			'<a href="%s" data-code="%s" class="cs_discount_remove"><span class="screen-reader-text">%s</span></a>',
			esc_url( $remove_url ),
			esc_attr( $discount ),
			esc_attr__( 'Remove discount', 'commercestore' )
		);
		$discount_html .= "</span>\n";

		$html .= apply_filters( 'cs_get_cart_discount_html', $discount_html, $discount, $rate, $remove_url );
	}

	return apply_filters( 'cs_get_cart_discounts_html', $html, $discounts, $rate, $remove_url );
}

/**
 * Show the fully formatted cart discount
 *
 * Note the $formatted parameter was removed from the display_cart_discount() function
 * within CS_Cart in 2.7 as it was a redundant parameter.
 *
 * @since 1.4.1
 * @param bool $formatted
 * @param bool $echo Echo?
 * @return string $amount Fully formatted cart discount
 */
function cs_display_cart_discount( $formatted = false, $echo = false ) {
	if ( ! $echo ) {
		return CS()->cart->display_cart_discount( $echo );
	} else {
		CS()->cart->display_cart_discount( $echo );
	}
}

/**
 * Processes a remove discount from cart request
 *
 * @since 1.4.1
 * @return void
 */
function cs_remove_cart_discount() {

	// Get ID
	$discount_id = isset( $_GET['discount_id'] )
		? absint( $_GET['discount_id'] )
		: 0;

	// Get code
	$discount_code = isset( $_GET['discount_code'] )
		? urldecode( $_GET['discount_code'] )
		: '';

	// Bail if either ID or code are empty
	if ( empty( $discount_id ) || empty( $discount_code ) ) {
		return;
	}

	// Pre-3.0 pre action
	do_action( 'cs_pre_remove_cart_discount', $discount_id );

	cs_unset_cart_discount( $discount_code );

	// Pre-3.0 post action
	do_action( 'cs_post_remove_cart_discount', $discount_id );

	// Redirect
	cs_redirect( cs_get_checkout_uri() );
}
add_action( 'cs_remove_cart_discount', 'cs_remove_cart_discount' );

/**
 * Checks whether discounts are still valid when removing items from the cart
 *
 * If a discount requires a certain product, and that product is no longer in
 * the cart, the discount is removed.
 *
 * @since 1.5.2
 *
 * @param int $cart_key
 */
function cs_maybe_remove_cart_discount( $cart_key = 0 ) {

	$discounts = cs_get_cart_discounts();

	if ( empty( $discounts ) ) {
		return;
	}

	foreach ( $discounts as $discount ) {
		if ( ! cs_is_discount_valid( $discount ) ) {
			cs_unset_cart_discount( $discount );
		}
	}
}
add_action( 'cs_post_remove_from_cart', 'cs_maybe_remove_cart_discount' );

/**
 * Checks whether multiple discounts can be applied to the same purchase
 *
 * @since 1.7
 * @return bool
 */
function cs_multiple_discounts_allowed() {
	$ret = cs_get_option( 'allow_multiple_discounts', false );
	return (bool) apply_filters( 'cs_multiple_discounts_allowed', $ret );
}

/**
 * Listens for a discount and automatically applies it if present and valid
 *
 * @since 2.0
 * @return void
 */
function cs_listen_for_cart_discount() {

	// Bail if in admin
	if ( is_admin() ) {
		return;
	}

	// Array stops the bulk delete of discount codes from storing as a preset_discount
	if ( empty( $_REQUEST['discount'] ) || is_array( $_REQUEST['discount'] ) ) {
		return;
	}

	$code = preg_replace('/[^a-zA-Z0-9-_]+/', '', $_REQUEST['discount'] );

	CS()->session->set( 'preset_discount', $code );
}
add_action( 'init', 'cs_listen_for_cart_discount', 0 );

/**
 * Applies the preset discount, if any. This is separated from cs_listen_for_cart_discount() in order to allow items to be
 * added to the cart and for it to persist across page loads if necessary
 *
 * @return void
 */
function cs_apply_preset_discount() {

	// Bail if in admin
	if ( is_admin() ) {
		return;
	}

	$code = sanitize_text_field( CS()->session->get( 'preset_discount' ) );

	if ( empty( $code ) ) {
		return;
	}

	if ( ! cs_is_discount_valid( $code, '', false ) ) {
		return;
	}

	$code = apply_filters( 'cs_apply_preset_discount', $code );

	cs_set_cart_discount( $code );

	CS()->session->set( 'preset_discount', null );
}
add_action( 'init', 'cs_apply_preset_discount', 999 );

/**
 * Validate discount code.
 *
 * @since 3.0
 *
 * @param int   $discount_id  Discount ID.
 * @param array $download_ids Array of download IDs.
 *
 * @return boolean True if discount holds, false otherwise.
 */
function cs_validate_discount( $discount_id = 0, $download_ids = array() ) {

	// Bail if discount ID not passed.
	if ( empty( $discount_id ) ) {
		return false;
	}

	// Set discount to be invalid initially.
	$is_valid = false;

	$discount = cs_get_discount( $discount_id );

	// Bail if discount not found.
	if ( ! $discount ) {
		return false;
	}

	// Check if discount is active, started, and not maxed out.
	if ( ! $discount->is_active( true, false ) || ! $discount->is_started( false ) || $discount->is_maxed_out( false ) ) {
		return false;
	}

	$product_requirements = $discount->get_product_reqs();
	$excluded_products    = $discount->get_excluded_products();

	// Return true if there are no requirements/excluded products set.
	if ( empty( $product_requirements ) && empty( $excluded_products ) ) {
		return true;
	}

	$product_requirements = array_map( 'absint', $product_requirements );
	asort( $product_requirements );
	$product_requirements = array_filter( array_values( $product_requirements ) );

	$excluded_products = array_map( 'absint', $excluded_products );
	asort( $excluded_products );
	$excluded_products = array_filter( array_values( $excluded_products ) );

	if ( ! empty( $product_requirements ) ) {
		foreach ( $product_requirements as $download_id ) {
			if ( empty( $download_id ) ) {
				continue;
			}

			$download_id  = absint( $download_id );
			$has_download = in_array( $download_id, $download_ids, true );

			switch ( $discount->get_product_condition() ) {
				case 'all':
					$is_valid = false !== $has_download;
					break;
				default:
					$is_valid = $has_download;
			}
		}
	} else {
		$is_valid = true;
	}

	if ( ! empty( $excluded_products ) ) {
		foreach ( $excluded_products as $download_id ) {
			if ( empty( $download_id ) ) {
				continue;
			}

			$download_id  = absint( $download_id );
			$has_download = in_array( $download_id, $download_ids, true );

			$is_valid = false === $has_download;
		}
	}

	/**
	 * Filters the validity of a discount.
	 *
	 * @since 3.0
	 *
	 * @param bool          $is_valid     True if valid, false otherwise.
	 * @param \CS_Discount $discount     Discount object.
	 * @param array         $download_ids Download IDs to check against.
	 */
	return apply_filters( 'cs_validate_discount', $is_valid, $discount, $download_ids );
}
