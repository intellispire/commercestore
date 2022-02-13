<?php
/**
 * Add the price ID to the subscription rows.
 *
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.9
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'EDD_Batch_Export' ) ) {

	/**
	 * EDD_Recurring_Add_Subscription_Price_IDs Class
	 *
	 * @since 2.9
	 */
	class EDD_Recurring_Add_Subscription_Price_IDs extends EDD_Batch_Export {

		/**
		 * Our export type. Used for export-type specific filters/actions
		 * @var string
		 * @since 2.9
		 */
		public $export_type = '';

		/**
		 * Allows for a non-download batch processing to be run.
		 * @since  2.9
		 * @var boolean
		 */
		public $is_void = true;

		/**
		 * Sets the number of items to pull on each step
		 * @since  2.9
		 * @var integer
		 */
		public $per_step = 50;

		/**
		 * Get the Export Data
		 *
		 * @access public
		 * @since 2.9
		 * @return array $data The data for the CSV file
		 */
		public function get_data() {

			$step_items = $this->get_subscription_ids();

			if ( ! is_array( $step_items ) ) {
				return false;
			}

			if ( empty( $step_items ) ) {
				return false;
			}

			foreach ( $step_items as $subscription_id ) {
				$subscription   = new EDD_Subscription( $subscription_id );
				$parent_payment = edd_get_payment( $subscription->parent_payment_id );

				if ( false === $parent_payment || empty( $parent_payment->downloads ) ) {
					continue;
				}

				foreach ( $parent_payment->downloads as $download ) {

					if ( (int) $download['id'] !== (int) $subscription->product_id ) {
						continue;
					}

					if ( edd_has_variable_prices( $subscription->product_id ) ) {
						$price_id = isset( $download['options']['price_id'] ) ? $download['options']['price_id'] : edd_get_default_variable_price( $subscription->product_id );
					} else {
						$price_id = 0;
					}

					$subscription->update( array( 'price_id' => $price_id ) );
				}
			}

			return true;
		}

		/**
		 * Return the calculated completion percentage
		 *
		 * @since 2.9
		 * @return int
		 */
		public function get_percentage_complete() {

			$total = (int) get_option( 'edd_recurring_price_id_upgrade_total_count', 0 );

			$percentage = 100;

			if( $total > 0 ) {
				$percentage = ( ( $this->step * $this->per_step ) / $total ) * 100;
			}

			if( $percentage > 100 ) {
				$percentage = 100;
			}

			return $percentage;
		}

		/**
		 * Set the properties specific to this export
		 *
		 * @since 2.9
		 * @param array $request The Form Data passed into the batch processing
		 */
		public function set_properties( $request ) {}

		/**
		 * Process a step
		 *
		 * @since 2.9
		 * @return bool
		 */
		public function process_step() {

			if ( ! $this->can_export() ) {
				wp_die(
					__( 'You do not have permission to run this upgrade.', 'edd-recurring' ),
					__( 'Error', 'easy-digital-downloads' ),
					array( 'response' => 403 ) );
			}

			$had_data = $this->get_data();

			if( $had_data ) {
				$this->done = false;
				return true;
			} else {
				$this->done = true;
				delete_option( 'edd_recurring_price_id_upgrade_total_count' );
				$this->message = __( 'Subscription records have been successfully updated.', 'edd-recurring' );
				edd_set_upgrade_complete( 'recurring_add_price_id_column' );
				return false;
			}
		}

		public function headers() {
			ignore_user_abort( true );

			if ( ! edd_is_func_disabled( 'set_time_limit' ) ) {
				set_time_limit( 0 );
			}
		}

		/**
		 * Perform the export
		 *
		 * @access public
		 * @since 2.9
		 * @return void
		 */
		public function export() {

			// Set headers
			$this->headers();

			edd_die();
		}

		/**
		 * Fetch total number of subscription IDs needing migration
		 *
		 * @since 2.9.5
		 *
		 * @global object $wpdb
		 */
		public function pre_fetch() {
			global $wpdb;

			$sub_count = $wpdb->get_var( "SELECT count(id) FROM {$wpdb->prefix}edd_subscriptions WHERE price_id IS NULL OR price_id = ''" );
			update_option( 'edd_recurring_price_id_upgrade_total_count', $sub_count );
		}

		/**
		 * Get the subscription IDs (50 based on this->per_step) for the current step
		 *
		 * @since 2.9.5
		 *
		 * @global object $wpdb
		 * @return array
		 */
		private function get_subscription_ids() {
			global $wpdb;

			$offset  = ( $this->step * $this->per_step ) - $this->per_step;
			$sub_ids = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}edd_subscriptions WHERE price_id IS NULL OR price_id = '' LIMIT %d, %d", $offset, $this->per_step ) );

			// Always return an array.
			return ! is_wp_error( $sub_ids )
				? (array) $sub_ids
				: array();
		}
	}

}