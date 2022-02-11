<?php
/**
 * Adjustment Functions.
 *
 * @package     CS
 * @subpackage  Adjustments
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add an adjustment.
 *
 * @since 3.0
 *
 * @param array $data {
 *     Array of adjustment data. Default empty.
 *
 *     The `date_created` and `date_modified` parameters do not need to be passed.
 *     They will be automatically populated if empty.
 *
 *     @type int    $parent            Parent adjustment ID. Default empty.
 *     @type string $name              Name of the adjustment. Default empty.
 *     @type string $code              Code that needs to be applied at the
 *                                     checkout for the adjustment to be applied.
 *     @type string $status            Adjustment status. Default `draft`.
 *     @type string $type              Adjustment type (e.g. `discount`). Default empty.
 *     @type string $scope             Adjustment scope. Value is dependent on
 *                                     the adjustment type. E.g. a tax rate will
 *                                     a scope of `country` or `region`. Default empty.
 *     @type string $amount_type       Type of adjustment. Adjustments can be a
 *                                     percentage or a flat amount. Default empty.
 *     @type float  $amount            Adjustment amount. If the amount type is a,
 *                                     percentage the amount reflects a percentage,
 *                                     otherwise a flat amount.
 *     @type string $description       Extended description of an adjustment.
 *                                     Default empty.
 *     @type int    $max_uses          Maximum number of times an adjustment can
 *                                     be used. Default 0 (unlimited).
 *     @type int    $use_count         Usage count of the adjustment. Default 0.
 *     @type bool   $once_per_customer True if customer can only apply adjustment
 *                                     once, false otherwise. Default false.
 *     @type float  $min_charge_amount Minimum amount that needs to be in the cart
 *                                     for adjustment to be valid. Default 0.
 *     @type string $product_condition Product condition that needs to hold for
 *                                     adjustment to be valid. Default empty.
 *     @type string|null $start_date   The date & time the adjustment is valid from.
 *                                     Format: YYYY-MM-DD HH:MM:SS. Default null.
 *     @type string|null $end_date     The date & time the adjustment is valid to.
 *                                     Format: YYYY-MM-DD HH:MM:SS. Default null.
 *     @type string $date_created      Optional. Automatically calculated on add/edit.
 *                                     The date & time the adjustment was inserted.
 *                                     Format: YYYY-MM-DD HH:MM:SS. Default empty.
 *     @type string $date_modified     Optional. Automatically calculated on add/edit.
 *                                     The date & time the adjustment was last modified.
 *                                     Format: YYYY-MM-DD HH:MM:SS. Default empty.
 * }
 * @return int ID of the inserted adjustment.
 */
function cs_add_adjustment( $data = array() ) {
	$adjustments = new CS\Database\Queries\Adjustment();

	return $adjustments->add_item( $data );
}

/**
 * Delete an adjustment.
 *
 * @since 3.0
 *
 * @param int $adjustment_id Adjustment ID.
 * @return int|false `1` if the adjustment was deleted successfully, false on error.
 */
function cs_delete_adjustment( $adjustment_id = 0 ) {
	$adjustments = new CS\Database\Queries\Adjustment();

	return $adjustments->delete_item( $adjustment_id );
}

/**
 * Update an adjustment.
 *
 * @since 3.0
 *
 * @param int   $adjustment_id Adjustment ID.
 * @param array $data {
 *     Array of adjustment data. Default empty.
 *
 *     @type int    $parent            Parent adjustment ID. Default empty.
 *     @type string $name              Name of the adjustment. Default empty.
 *     @type string $code              Code that needs to be applied at the
 *                                     checkout for the adjustment to be applied.
 *     @type string $status            Adjustment status. Default `draft`.
 *     @type string $type              Adjustment type (e.g. `discount`). Default empty.
 *     @type string $scope             Adjustment scope. Value is dependent on
 *                                     the adjustment type. E.g. a tax rate will
 *                                     a scope of `country` or `region`. Default empty.
 *     @type string $amount_type       Type of adjustment. Adjustments can be a
 *                                     percentage or a flat amount. Default empty.
 *     @type float  $amount            Adjustment amount. If the amount type is a,
 *                                     percentage the amount reflects a percentage,
 *                                     otherwise a flat amount.
 *     @type string $description       Extended description of an adjustment.
 *                                     Default empty.
 *     @type int    $max_uses          Maximum number of times an adjustment can
 *                                     be used. Default 0 (unlimited).
 *     @type int    $use_count         Usage count of the adjustment. Default 0.
 *     @type bool   $once_per_customer True if customer can only apply adjustment
 *                                     once, false otherwise. Default false.
 *     @type float  $min_charge_amount Minimum amount that needs to be in the cart
 *                                     for adjustment to be valid. Default 0.
 *     @type string $product_condition Product condition that needs to hold for
 *                                     adjustment to be valid. Default empty.
 *     @type string|null $start_date   The date & time the adjustment is valid from.
 *                                     Format: YYYY-MM-DD HH:MM:SS. Default empty.
 *     @type string|null $end_date     The date & time the adjustment is valid to.
 *                                     Format: YYYY-MM-DD HH:MM:SS. Default empty.
 *     @type string $date_created      Optional. Automatically calculated on add/edit.
 *                                     The date & time the adjustment was inserted.
 *                                     Format: YYYY-MM-DD HH:MM:SS. Default empty.
 *     @type string $date_modified     Optional. Automatically calculated on add/edit.
 *                                     The date & time the adjustment was last modified.
 *                                     Format: YYYY-MM-DD HH:MM:SS. Default empty.
 * }
 *
 * @return int|false Number of rows updated if successful, false otherwise.
 */
function cs_update_adjustment( $adjustment_id = 0, $data = array() ) {
	$adjustments = new CS\Database\Queries\Adjustment();

	return $adjustments->update_item( $adjustment_id, $data );
}

/**
 * Get an adjustment by ID.
 *
 * @since 3.0
 *
 * @param int $adjustment_id Adjustment ID.
 * @return CS\Adjustments\Adjustment|false Adjustment object if successful,
 *                                          false otherwise.
 */
function cs_get_adjustment( $adjustment_id = 0 ) {
	$adjustments = new CS\Database\Queries\Adjustment();

	// Return adjustment
	return $adjustments->get_item( $adjustment_id );
}

/**
 * Get an adjustment by a specific field value.
 *
 * @since 3.0
 *
 * @param string $field Database table field.
 * @param string $value Value of the row.
 *
 * @return CS\Adjustments\Adjustment|false Adjustment object if successful,
 *                                          false otherwise.
 */
function cs_get_adjustment_by( $field = '', $value = '' ) {
	$adjustments = new CS\Database\Queries\Adjustment();

	// Return adjustment
	return $adjustments->get_item_by( $field, $value );
}

/**
 * Query for adjustments.
 *
 * @see \CS\Database\Queries\Adjustment::__construct()
 *
 * @since 3.0
 *
 * @param array $args Arguments. See `CS\Database\Queries\Adjustment` for
 *                    accepted arguments.
 * @return \CS\Adjustments\Adjustment[] Array of `Adjustment` objects.
 */
function cs_get_adjustments( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'number' => 30
	) );

	// Instantiate a query object
	$adjustments = new CS\Database\Queries\Adjustment();

	// Return adjustments
	return $adjustments->query( $r );
}

/**
 * Count adjustments.
 *
 * @see \CS\Database\Queries\Adjustment::__construct()
 *
 * @since 3.0
 *
 * @param array $args Arguments. See `CS\Database\Queries\Adjustment` for
 *                    accepted arguments.
 * @return int Number of adjustments returned based on query arguments passed.
 */
function cs_count_adjustments( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'count' => true
	) );

	// Query for count(s)
	$adjustments = new CS\Database\Queries\Adjustment( $r );

	// Return count(s)
	return absint( $adjustments->found_items );
}
