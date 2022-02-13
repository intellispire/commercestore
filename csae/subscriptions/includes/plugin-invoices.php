<?php

/**
 * Integrates CommerceStore Recurring with the CommerceStore Invoices extension
 *
 * @since v2.5.3
 */
class CS_Recurring_Invoices {


	/**
	 * Get things started
	 *
	 * @since  2.5.3
	 * @return void
	 */
	public function __construct() {

		if ( ! class_exists( 'CSInvoices' ) ) {
			return;
		}

		add_filter( 'cs_invoices_acceptable_payment_statuses', array( $this, 'add_acceptable_payment_statuses' ), 10, 1 );
	}

	/**
	 * Add the payment statuses created and used by Recurring to the list of acceptable statuses when CommerceStore Invoices is deciding if it should show the "Generate Invoice" option.
	 *
	 * @since  2.5.3
	 * @param  array $acceptable_statuses  The array containing all of the acceptable payment statuses.
	 * @return void
	 */
	public function add_acceptable_payment_statuses( $acceptable_statuses ) {

		$acceptable_statuses[] = 'cs_subscription';
		
		return $acceptable_statuses;
	}

}