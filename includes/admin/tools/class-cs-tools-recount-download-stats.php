<?php
/**
 * Recount download earnings and stats
 *
 * This class handles batch processing of recounting earnings and stats
 *
 * @subpackage  Admin/Tools/CS_Tools_Recount_Stats
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
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
class CS_Tools_Recount_Download_Stats extends CS_Batch_Export {

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
	public $per_step = 30;

	/**
	 * @var string
	 */
	public $message = '';

	/**
	 * ID of the download we're recounting stats for
	 * @var int|false
	 */
	protected $download_id = false;

	/**
	 * Get the Export Data
	 *
	 * @since 2.5
	 * @global object $wpdb Used to query the database using the WordPress
	 *   Database API
	 * @return bool
	 */
	public function get_data() {

		$accepted_statuses = apply_filters( 'cs_recount_accepted_statuses', cs_get_gross_order_statuses() );

		// These arguments are no longer used, but keeping the filter here to apply the deprecation notice.
		$deprecated_args = cs_apply_filters_deprecated( 'cs_recount_download_stats_args', array(
			array(
				'post_parent'    => $this->download_id,
				'post_type'      => 'cs_log',
				'posts_per_page' => $this->per_step,
				'post_status'    => 'publish',
				'paged'          => $this->step,
				'log_type'       => 'sale',
				'fields'         => 'ids',
			)
		), '3.0' );

		global $wpdb;

		/*
		 * Build up our WHERE clauses.
		 */
		$conditions = array();

		if ( ! empty( $accepted_statuses ) && is_array( $accepted_statuses ) ) {
			$placeholder_string = implode( ', ', array_fill( 0, count( $accepted_statuses ), '%s' ) );
			$conditions[]       = $wpdb->prepare(
				"oi.status IN({$placeholder_string})",
				$accepted_statuses
			);
		}

		if ( ! empty( $this->download_id ) && is_numeric( $this->download_id ) ) {
			$conditions[] = $wpdb->prepare(
				"oi.product_id = %d",
				$this->download_id
			);
		}
		$conditions = ! empty( $conditions ) ? ' AND ' . implode( ' AND ', $conditions ) : '';

		$results = $wpdb->get_row(
			"SELECT SUM(oi.total / oi.rate) AS revenue, COUNT(oi.id) AS sales
				FROM {$wpdb->cs_order_items} oi
				INNER JOIN {$wpdb->cs_orders} o ON(o.id = oi.order_id)
				WHERE o.type = 'sale'
				{$conditions}"
		);

		$sales    = ! empty( $results->sales ) ? intval( $results->sales ) : 0;
		$earnings = ! empty( $results->revenue ) ? cs_sanitize_amount( $results->revenue ) : 0.00;

		update_post_meta( $this->download_id, '_cs_download_sales', $sales );
		update_post_meta( $this->download_id, '_cs_download_earnings', sanitize_text_field( $earnings ) );

		return false;

	}

	/**
	 * Return the calculated completion percentage
	 *
	 * @since 2.5
	 * @return int
	 */
	public function get_percentage_complete() {
		return 100;
	}

	/**
	 * Set the properties specific to the payments export
	 *
	 * @since 2.5
	 * @param array $request The Form Data passed into the batch processing
	 */
	public function set_properties( $request ) {
		$this->download_id = isset( $request['download_id'] ) ? sanitize_text_field( $request['download_id'] ) : false;
	}

	/**
	 * Process a step
	 *
	 * @since 2.5
	 * @return bool
	 */
	public function process_step() {
		if ( ! $this->can_export() ) {
			wp_die( __( 'You do not have permission to perform this action.', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}

		$more_to_do = $this->get_data();

		if( $more_to_do ) {
			$this->done = false;
			return true;
		} else {
			$this->delete_data( 'cs_recount_total_' . $this->download_id );
			$this->delete_data( 'cs_temp_recount_download_stats' );
			$this->done    = true;
			$this->message = sprintf( __( 'Earnings and sales stats successfully recounted for %s.', 'commercestore' ), get_the_title( $this->download_id ) );
			return false;
		}
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

	/**
	 * Delete an option
	 *
	 * @since  2.5
	 * @param  string $key The option_name to delete
	 * @return void
	 */
	protected function delete_data( $key ) {
		global $wpdb;
		$wpdb->delete( $wpdb->options, array( 'option_name' => $key ) );
	}

}
