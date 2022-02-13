<?php
/**
 * PayPal Commerce Functions
 *
 * @package    cs-recurring
 * @subpackage Gateways\PayPal
 * @copyright  Copyright (c) 2021, Sandhills Development, LLC
 * @license    GPL2+
 * @since      2.11.2
 */

namespace CS_Recurring\Gateways\PayPal;

use CS_Recurring_PayPal_Commerce;

/**
 * Builds the arguments to create a plan when upgrading a Software Licensing license.
 * In this scenario we need to sync the subscription's first renewal date with the expiration
 * date of the license. So if your license key expires in 6 months but you're upgrading to
 * a yearly plan, we want to charge the user $x today and have the first renewal be in
 * 6 months time.
 *
 * @internal Not intended for general use. May change without warning.
 *
 * @param \DateTime $renewal_date Desired date of the first renewal.
 * @param string    $product_id   PayPal product ID this plan is associated with.
 * @param array     $subscription Array of subscription data.
 *
 * @since    2.11.2
 * @return array Arguments that can be used in the API request to create a plan.
 * @throws \Exception
 */
function _create_plan_args_for_sl_upgrade( $renewal_date, $product_id, $subscription ) {
	$plan_args = CS_Recurring_PayPal_Commerce::build_plan_api_args(
		$product_id,
		$subscription
	);

	if ( empty( $plan_args['billing_cycles'] ) || ! is_array( $plan_args['billing_cycles'] ) ) {
		throw new \Exception( 'Missing billing cycle arguments.' );
	}

	// We need to update the 'frequency' for the initial payment to match the renewal date.
	$current_date = new \DateTime( 'now' );
	$date_diff    = $current_date->diff( $renewal_date );

	$new_billing_cycles = array();
	$current_sequence   = 1;

	/*
	 * PayPal's maximum allowed value for a "day" cycle is 365. If the difference between today and the desired renewal
	 * date is greater than 365 days, then we need _two_ TRIAL billing cycles: one for the number of years, and another
	 * for however many days are left over.
	 */
	$remainder_days = $date_diff->days % 365;
	$years          = ( $date_diff->days - $remainder_days ) / 365;

	// Initial price due today.
	if ( $remainder_days > 0 ) {
		$new_billing_cycles[] = array(
			'frequency'      => CS_Recurring_PayPal_Commerce::subscription_frequency_to_paypal_args( 'day', $remainder_days ),
			'tenure_type'    => 'TRIAL',
			'sequence'       => $current_sequence,
			'pricing_scheme' => array(
				'fixed_price' => array(
					'currency_code' => strtoupper( cs_get_currency() ),
					'value'         => (string) $subscription['initial_amount']
				)
			),
			'total_cycles'   => 1
		);

		$current_sequence ++;
	}

	// If we have any years, we need another billing cycle for that.
	if ( $years > 0 ) {
		$new_billing_cycles[] = array(
			'frequency'      => CS_Recurring_PayPal_Commerce::subscription_frequency_to_paypal_args( 'year', $years ),
			'tenure_type'    => 'TRIAL',
			'sequence'       => $current_sequence,
			'pricing_scheme' => array(
				'fixed_price' => array(
					'currency_code' => strtoupper( cs_get_currency() ),
					// Only charge an amount today if this is the first sequence. (If we didn't already set a trial above.)
					'value'         => $current_sequence === 1 ? (string) $subscription['initial_amount'] : '0',
				)
			),
			'total_cycles'   => 1
		);

		$current_sequence ++;
	}

	// Now add the recurring cycle.
	$new_billing_cycles[] = array(
		'frequency'      => CS_Recurring_PayPal_Commerce::subscription_frequency_to_paypal_args( $subscription['period'], $subscription['frequency'] ),
		'tenure_type'    => 'REGULAR',
		'sequence'       => $current_sequence,
		'pricing_scheme' => array(
			'fixed_price' => array(
				'currency_code' => strtoupper( cs_get_currency() ),
				'value'         => (string) $subscription['recurring_amount']
			)
		),
		'total_cycles'   => ! empty( $subscription['bill_times'] ) ? intval( $subscription['bill_times'] ) : 0
	);

	$plan_args['billing_cycles'] = $new_billing_cycles;

	return $plan_args;
}
