<?php
/**
 * Recount all cutomer stats
 *
 * This class handles batch processing of recounting a single customer's stats
 *
 * @subpackage  Admin/Tools/CS_Tools_Recount_Customer_Stats
 * @copyright   Copyright (c) 2015, Chris Klosowski
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.5
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CS_Tools_Recount_Stats Class
 *
 * @since 2.5
 */
class CS_Tools_Recount_Single_Customer_Stats extends CS_Batch_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 * @var string
	 * @since 2.5
	 */
	public $export_type = '';

	/**
	 * Allows for a non-download batch processing to be run.
	 * @since  2.5
	 * @var boolean
	 */
	public $is_void = true;

	/**
	 * Sets the number of items to pull on each step
	 * @since  2.5
	 * @var integer
	 */
	public $per_step = 10;

	/**
	 * Get the Export Data
	 *
	 * @since 2.5
	 * @global object $wpdb Used to query the database using the WordPress
	 *   Database API
	 * @return bool True if data was found, false if not.
	 */
	public function get_data() {

		$customer = new CS_Customer( $this->customer_id );
		if ( $customer ) {
			$customer->recalculate_stats();
			$this->result_data = array(
				'purchase_count' => (int) $customer->purchase_count,
				'purchase_value' => esc_html( cs_currency_filter( cs_format_amount( $customer->purchase_value ) ) ),
			);
			return true;
		}

		return false;

	}

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 2.5
	 * @return int
	 */
	public function get_percentage_complete() {

		$total = cs_count_orders(
			array(
				'customer_id' => $this->customer_id,
			)
		);

		$percentage = 100;

		if ( $total > 0 ) {
			$percentage = ( ( $this->per_step * $this->step ) / $total ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	}

	/**
	 * Set the properties specific to the payments export
	 *
	 * @since 2.5
	 * @param array $request The Form Data passed into the batch processing
	 */
	public function set_properties( $request ) {
		$this->customer_id = isset( $request['customer_id'] ) ? sanitize_text_field( $request['customer_id'] ) : false;
	}

	/**
	 * Process a step
	 *
	 * @since 2.5
	 * @return bool
	 */
	public function process_step() {

		if ( ! $this->can_export() ) {
			wp_die( esc_html__( 'You do not have permission to modify this data.', 'commercestore' ), esc_html__( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}

		$had_data = $this->get_data();

		if ( ! $had_data ) {
			$this->done = false;
			return true;
		}
		$this->done    = true;
		$this->message = esc_html__( 'Customer stats successfully recounted.', 'commercestore' );

		return false;
	}

	public function headers() {
		cs_set_time_limit();
	}

	/**
	 * Perform the export
	 *
	 * @since 2.5
	 * @return void
	 */
	public function export() {

		// Set headers
		$this->headers();

		cs_die();
	}
}
