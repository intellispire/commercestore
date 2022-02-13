<?php
/**
 * Report Data Callbacks
 *
 * Queries performed to get data used in reports.
 *
 * @package   edd-recurring
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 */

/**
 * Fetches the number of subscription renewals that were processed during this report period.
 *
 * @since 2.10.1
 * @return int
 */
function edd_recurring_renewals_number_callback() {
	if ( ! function_exists( '\\EDD\\Reports\\get_dates_filter' ) ) {
		return 0;
	}

	global $wpdb;

	$dates = EDD\Reports\get_dates_filter( 'objects' );

	$number = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(edd_o.id) FROM {$wpdb->edd_orders} edd_o
			INNER JOIN {$wpdb->edd_ordermeta} edd_ometa ON( edd_o.id = edd_ometa.edd_order_id AND edd_ometa.meta_key = 'subscription_id' )
			WHERE edd_o.type = 'sale'
			AND edd_o.status IN( 'edd_subscription', 'refunded', 'partially_refunded' )
			AND edd_o.date_created >= %s AND edd_o.date_created <= %s",
		$dates['start']->copy()->format( 'mysql' ),
		$dates['end']->copy()->format( 'mysql' )
	) );

	return absint( $number );
}

/**
 * Fetches the number of subscription renewals that were refunded during this report period.
 *
 * @since 2.10.1
 * @return int
 */
function edd_recurring_renewals_refunded_number_callback() {
	if ( ! function_exists( '\\EDD\\Reports\\get_dates_filter' ) ) {
		return 0;
	}

	global $wpdb;

	$dates = EDD\Reports\get_dates_filter( 'objects' );

	$number = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(edd_o.id) FROM {$wpdb->edd_orders} edd_o
			INNER JOIN {$wpdb->edd_ordermeta} edd_ometa ON( edd_o.parent = edd_ometa.edd_order_id AND edd_ometa.meta_key = 'subscription_id' )
			WHERE edd_o.type = 'refund'
			AND edd_o.date_created >= %s AND edd_o.date_created <= %s",
		$dates['start']->copy()->format( 'mysql' ),
		$dates['end']->copy()->format( 'mysql' )
	) );

	return absint( $number );
}

/**
 * Queries the database to get the gross renewal earnings.
 *
 * @since 2.10.1
 * @return float
 */
function edd_recurring_get_gross_renewal_earnings_for_report_period() {
	if ( ! function_exists( '\\EDD\\Reports\\get_dates_filter' ) ) {
		return 0;
	}

	global $wpdb;

	$dates  = EDD\Reports\get_dates_filter( 'objects' );
	$column = EDD\Reports\get_taxes_excluded_filter() ? 'total - tax' : 'total';

	$earnings = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM({$column}) FROM {$wpdb->edd_orders} edd_o
			INNER JOIN {$wpdb->edd_ordermeta} edd_ometa ON( edd_o.id = edd_ometa.edd_order_id AND edd_ometa.meta_key = 'subscription_id' )
			WHERE edd_o.type = 'sale'
			AND edd_o.status IN( 'edd_subscription', 'refunded', 'partially_refunded' )
			AND edd_o.date_created >= %s AND edd_o.date_created <= %s",
		$dates['start']->copy()->format( 'mysql' ),
		$dates['end']->copy()->format( 'mysql' )
	) );

	if ( is_null( $earnings ) ) {
		$earnings = 0;
	}

	return floatval( $earnings );
}

/**
 * Queries the database to get the renewal earnings refunded.
 *
 * @since 2.10.1
 * @return float
 */
function edd_recurring_get_refunded_amount_for_report_period() {
	if ( ! function_exists( '\\EDD\\Reports\\get_dates_filter' ) ) {
		return 0.00;
	}

	global $wpdb;

	$dates  = EDD\Reports\get_dates_filter( 'objects' );
	$column = EDD\Reports\get_taxes_excluded_filter() ? 'total - tax' : 'total';

	$earnings = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM({$column}) FROM {$wpdb->edd_orders} edd_o
			INNER JOIN {$wpdb->edd_ordermeta} edd_ometa ON( edd_o.parent = edd_ometa.edd_order_id AND edd_ometa.meta_key = 'subscription_id' )
			WHERE edd_o.type = 'refund'
			AND edd_o.date_created >= %s AND edd_o.date_created <= %s",
		$dates['start']->copy()->format( 'mysql' ),
		$dates['end']->copy()->format( 'mysql' )
	) );

	if ( is_null( $earnings ) ) {
		$earnings = 0.00;
	}

	return floatval( abs( $earnings ) );
}
