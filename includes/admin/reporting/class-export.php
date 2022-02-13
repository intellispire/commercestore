<?php
/**
 * Base export class.
 *
 * This is the base class for all export methods. Each data export type (customers, payments, etc) extend this class.
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
 * CS_Export Class
 *
 * @since 1.4.4
 */
class CS_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions
	 * @var string
	 * @since 1.4.4
	 */
	public $export_type = 'default';

	/**
	 * Can we export?
	 *
	 * @since 1.4.4
	 *
	 * @return bool True if exporting is allowed, false otherwise.
	 */
	public function can_export() {
		return (bool) apply_filters( 'cs_export_capability', current_user_can( 'export_shop_reports' ) );
	}

	/**
	 * Set the export headers.
	 *
	 * @since 1.4.4
	 * @since 3.0 Add BOM to the CSV export.
	 */
	public function headers() {
		cs_set_time_limit();

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="cs-export-' . $this->export_type . '-' . date( 'm-d-Y' ) . '.csv"' );
		header( 'Expires: 0' );

		/**
		 * We need to append a BOM to the export so that Microsoft Excel knows
		 * that the file is in Unicode.
		 *
		 * @see https://github.com/commercestore/commercestore/issues/4859
		 */
		echo "\xEF\xBB\xBF";
	}

	/**
	 * Set the CSV columns.
	 *
	 * @since 1.4.4
	 *
	 * @return array $cols CSV columns.
	 */
	public function csv_cols() {
		$cols = array(
			'id'   => __( 'ID',   'commercestore' ),
			'date' => __( 'Date', 'commercestore' ),
		);
		return $cols;
	}

	/**
	 * Retrieve the CSV columns.
	 *
	 * @since 1.4.4
	 *
	 * @return array $cols Array of the columns.
	 */
	public function get_csv_cols() {
		$cols = $this->csv_cols();
		return apply_filters( 'cs_export_csv_cols_' . $this->export_type, $cols );
	}

	/**
	 * Output the CSV columns.
	 *
	 * @since 1.4.4
	 */
	public function csv_cols_out() {
		$cols = $this->get_csv_cols();

		$i = 1;

		// Output each column.
		foreach ( $cols as $col_id => $column ) {
			echo '"' . addslashes( $column ) . '"';

			echo count( $cols ) === $i
				? ''
				: ',';

			$i++;
		}
		echo "\r\n";
	}

	/**
	 * Get the data being exported.
	 *
	 * @since 1.4.4
	 *
	 * @return array $data Data for export.
	 */
	public function get_data() {

		// Just a sample data array
		$data = array(
			0 => array(
				'id'   => '',
				'data' => date( 'F j, Y' ),
			),
			1 => array(
				'id'   => '',
				'data' => date( 'F j, Y' ),
			),
		);

		$data = apply_filters( 'cs_export_get_data', $data );
		$data = apply_filters( 'cs_export_get_data_' . $this->export_type, $data );

		return $data;
	}

	/**
	 * Output the CSV rows.
	 *
	 * @since 1.4.4
	 */
	public function csv_rows_out() {
		$data = $this->get_data();

		$cols = $this->get_csv_cols();

		// Output each row.
		foreach ( $data as $row ) {
			$i = 1;

			foreach ( $row as $col_id => $column ) {

				// Make sure the column is valid.
				if ( array_key_exists( $col_id, $cols ) ) {
					echo '"' . addslashes( $column ) . '"';

					echo count( $cols ) === $i
						? ''
						: ',';

					$i++;
				}
			}
			echo "\r\n";
		}
	}

	/**
	 * Perform the export.
	 *
	 * @since 1.4.4
	 *
	 *
	 * @uses CS_Export::can_export()
	 * @uses CS_Export::headers()
	 * @uses CS_Export::csv_cols_out()
	 * @uses CS_Export::csv_rows_out()
	 */
	public function export() {

		// Bail if user if unauthorized.
		if ( ! $this->can_export() ) {
			wp_die( __( 'You do not have permission to export data.', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}

		// Set headers
		$this->headers();

		// Output CSV columns (headers)
		$this->csv_cols_out();

		// Output CSV rows
		$this->csv_rows_out();

		cs_die();
	}
}
