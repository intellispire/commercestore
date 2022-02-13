<?php
/**
 * Report Data Callbacks
 *
 * Queries performed to get data used in reports.
 *
 * @package   cs-recurring
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 */

/**
 * Fetches the number of subscription renewals that were processed during this report period.
 *
 * @since 2.10.1
 * @return int
 */
function cs_recurring_renewals_number_callback() {
	if ( ! function_exists( '\\CS\\Reports\\get_dates_filter' ) ) {
		return 0;
	}

	global $wpdb;

	$dates = CS\Reports\get_dates_filter( 'objects' );

	$number = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(cs_o.id) FROM {$wpdb->cs_orders} cs_o
			INNER JOIN {$wpdb->cs_ordermeta} cs_ometa ON( cs_o.id = cs_ometa.cs_order_id AND cs_ometa.meta_key = 'subscription_id' )
			WHERE cs_o.type = 'sale'
			AND cs_o.status IN( 'cs_subscription', 'refunded', 'partially_refunded' )
			AND cs_o.date_created >= %s AND cs_o.date_created <= %s",
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
function cs_recurring_renewals_refunded_number_callback() {
	if ( ! function_exists( '\\CS\\Reports\\get_dates_filter' ) ) {
		return 0;
	}

	global $wpdb;

	$dates = CS\Reports\get_dates_filter( 'objects' );

	$number = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(cs_o.id) FROM {$wpdb->cs_orders} cs_o
			INNER JOIN {$wpdb->cs_ordermeta} cs_ometa ON( cs_o.parent = cs_ometa.cs_order_id AND cs_ometa.meta_key = 'subscription_id' )
			WHERE cs_o.type = 'refund'
			AND cs_o.date_created >= %s AND cs_o.date_created <= %s",
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
function cs_recurring_get_gross_renewal_earnings_for_report_period() {
	if ( ! function_exists( '\\CS\\Reports\\get_dates_filter' ) ) {
		return 0;
	}

	global $wpdb;

	$dates  = CS\Reports\get_dates_filter( 'objects' );
	$column = CS\Reports\get_taxes_excluded_filter() ? 'total - tax' : 'total';

	$earnings = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM({$column}) FROM {$wpdb->cs_orders} cs_o
			INNER JOIN {$wpdb->cs_ordermeta} cs_ometa ON( cs_o.id = cs_ometa.cs_order_id AND cs_ometa.meta_key = 'subscription_id' )
			WHERE cs_o.type = 'sale'
			AND cs_o.status IN( 'cs_subscription', 'refunded', 'partially_refunded' )
			AND cs_o.date_created >= %s AND cs_o.date_created <= %s",
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
function cs_recurring_get_refunded_amount_for_report_period() {
	if ( ! function_exists( '\\CS\\Reports\\get_dates_filter' ) ) {
		return 0.00;
	}

	global $wpdb;

	$dates  = CS\Reports\get_dates_filter( 'objects' );
	$column = CS\Reports\get_taxes_excluded_filter() ? 'total - tax' : 'total';

	$earnings = $wpdb->get_var( $wpdb->prepare(
		"SELECT SUM({$column}) FROM {$wpdb->cs_orders} cs_o
			INNER JOIN {$wpdb->cs_ordermeta} cs_ometa ON( cs_o.parent = cs_ometa.cs_order_id AND cs_ometa.meta_key = 'subscription_id' )
			WHERE cs_o.type = 'refund'
			AND cs_o.date_created >= %s AND cs_o.date_created <= %s",
		$dates['start']->copy()->format( 'mysql' ),
		$dates['end']->copy()->format( 'mysql' )
	) );

	if ( is_null( $earnings ) ) {
		$earnings = 0.00;
	}

	return floatval( abs( $earnings ) );
}
