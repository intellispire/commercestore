<?php
/**
 * Refund Functions
 *
 * This file contains functions related to refunds.
 *
 * @package     CS
 * @subpackage  Functions
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Return array of refundability types.
 *
 * @since 3.0
 *
 * @return array
 */
function cs_get_refundability_types() {
	return array(
		'refundable'    => __( 'Refundable',     'commercestore' ),
		'nonrefundable' => __( 'Non-Refundable', 'commercestore' )
	);
}

/**
 * Calculate refund date.
 *
 * @since 3.0
 *
 * @param string $date        Date order was completed (Accepts UTC).
 * @param int    $download_id Download ID.
 *
 * @return string|false Date refundable (in UTC), false otherwise.
 */
function cs_get_refund_date( $date = '', $download_id = 0 ) {

	// Bail if no date was passed.
	if ( empty( $date ) ) {
		return false;
	}

	$refund_window = absint( cs_get_option( 'refund_window', 30 ) );

	// Refund window is infinite.
	if ( 0 === $refund_window ) {
		return false;
	}

	if ( ! empty( $download_id ) ) {
		$refund_window = cs_get_download_refund_window( $download_id );

		$date_refundable = \Carbon\Carbon::parse( $date, 'UTC' )->addDays( $refund_window );
	} else {
		$date_refundable = \Carbon\Carbon::parse( $date, 'UTC' )->addDays( $refund_window );
	}

	return $date_refundable->toDateTimeString();
}