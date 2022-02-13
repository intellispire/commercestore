<?php
/**
 * Customers Export Class
 *
 * This class handles customer export
 *
 * @package     CS
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.4
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CS_Customers_Export Class
 *
 * @since 1.4.4
 */
class CS_Customers_Export extends CS_Export {
	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var string
	 * @since 1.4.4
	 */
	public $export_type = 'customers';

	/**
	 * Set the export headers
	 *
	 * @since 1.4.4
	 * @return void
	 */
	public function headers() {
		cs_set_time_limit();

		$extra = '';

		if ( ! empty( $_POST['cs_export_download'] ) ) {
			$extra = sanitize_title( get_the_title( absint( $_POST['cs_export_download'] ) ) ) . '-';
		}

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . apply_filters( 'cs_customers_export_filename', 'cs-export-' . $extra . $this->export_type . '-' . date( 'm-d-Y' ) ) . '.csv"' );
		header( 'Expires: 0' );
	}

	/**
	 * Set the CSV columns
	 *
	 * @since 1.4.4
	 * @return array $cols All the columns
	 */
	public function csv_cols() {
		if ( ! empty( $_POST['cs_export_download'] ) ) {
			$cols = array(
				'first_name' => __( 'First Name',   'commercestore' ),
				'last_name'  => __( 'Last Name',   'commercestore' ),
				'email'      => __( 'Email', 'commercestore' ),
				'date'       => __( 'Date Purchased', 'commercestore' )
			);
		} else {

			$cols = array();

			if( 'emails' != $_POST['cs_export_option'] ) {
				$cols['name'] = __( 'Name',   'commercestore' );
			}

			$cols['email'] = __( 'Email',   'commercestore' );

			if( 'full' == $_POST['cs_export_option'] ) {
				$cols['purchases'] = __( 'Total Purchases',   'commercestore' );
				$cols['amount']    = __( 'Total Purchased', 'commercestore' ) . ' (' . html_entity_decode( cs_currency_filter( '' ) ) . ')';
			}

		}

		return $cols;
	}

	/**
	 * Get the Export Data
	 *
	 * @since 1.4.4
	 * @global object $wpdb Used to query the database using the WordPress
	 *   Database API
	 * @global object $cs_logs CommerceStore Logs Object
	 * @return array $data The data for the CSV file
	 */
	public function get_data() {

		$data = array();

		if ( ! empty( $_POST['cs_export_download'] ) ) {

			$cs_logs = CS()->debug_log;

			$args = array(
				'post_parent' => absint( $_POST['cs_export_download'] ),
				'log_type'    => 'sale',
				'nopaging'    => true
			);

			if( isset( $_POST['cs_price_option'] ) ) {
				$args['meta_query'] = array(
					array(
						'key'   => '_cs_log_price_id',
						'value' => (int) $_POST['cs_price_option']
					)
				);
			}

			$logs = $cs_logs->get_connected_logs( $args );

			if ( $logs ) {
				foreach ( $logs as $log ) {
					$payment_id = get_post_meta( $log->ID, '_cs_log_payment_id', true );
					$user_info  = cs_get_payment_meta_user_info( $payment_id );
					$data[] = array(
						'first_name' => $user_info['first_name'],
						'last_name'  => $user_info['last_name'],
						'email'      => $user_info['email'],
						'date'       => $log->post_date
					);
				}
			}

		} else {

			// Export all customers
			$customers = cs_get_customers( array(
				'limit' => 9999999,
			) );

			$i = 0;

			foreach ( $customers as $customer ) {

				if( 'emails' != $_POST['cs_export_option'] ) {
					$data[$i]['name'] = $customer->name;
				}

				$data[$i]['email'] = $customer->email;

				if( 'full' == $_POST['cs_export_option'] ) {

					$data[$i]['purchases'] = $customer->purchase_count;
					$data[$i]['amount']    = cs_format_amount( $customer->purchase_value );

				}
				$i++;
			}
		}

		$data = apply_filters( 'cs_export_get_data', $data );
		$data = apply_filters( 'cs_export_get_data_' . $this->export_type, $data );

		return $data;
	}
}
