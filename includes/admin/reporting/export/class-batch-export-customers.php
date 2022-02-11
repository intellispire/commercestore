<?php
/**
 * Batch Customers Export Class
 *
 * This class handles customer export
 *
 * @package     CS
 * @subpackage  Admin/Reporting/Export
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.4
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CS_Batch_Customers_Export Class
 *
 * @since 2.4
 * @since 3.0 Allowed customers to be exported by taxonomy.
 */
class CS_Batch_Customers_Export extends CS_Batch_Export {

	/**
	 * Our export type. Used for export-type specific filters/actions.
	 *
	 * @var string
	 * @since 2.4
	 */
	public $export_type = 'customers';

	/**
	 * Taxonomy.
	 *
	 * @since 3.0
	 * @var int
	 */
	public $taxonomy = null;

	/**
	 * Set the CSV columns.
	 *
	 * @since 2.4
	 *
	 * @return array $cols All the columns
	 */
	public function csv_cols() {

		return array(
			'id'           => __( 'ID', 'commercestore' ),
			'user_id'      => __( 'User ID', 'commercestore' ),
			'name'         => __( 'Name', 'commercestore' ),
			'email'        => __( 'Email', 'commercestore' ),
			'purchases'    => __( 'Number of Purchases', 'commercestore' ),
			'amount'       => __( 'Customer Value', 'commercestore' ),
			'payment_ids'  => __( 'Payment IDs', 'commercestore' ),
			'date_created' => __( 'Date Created', 'commercestore' ),
		);
	}

	/**
	 * Get the export data.
	 *
	 * @since 2.4
	 * @since 3.0 Updated to use new query methods.
	 *
	 * @return array $data The data for the CSV file.
	 */
	public function get_data() {
		global $wpdb;

		$data = array();

		// Taxonomy.
		if ( ! empty( $this->taxonomy ) ) {
			$taxonomy = $wpdb->prepare( 't.term_id = %d', $this->taxonomy );

			$limit = $wpdb->prepare( '%d, %d', 30 * ( $this->step - 1 ), 30 );

			$sql = "SELECT DISTINCT o.customer_id
					FROM {$wpdb->terms} t
					INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
					INNER JOIN {$wpdb->term_relationships} tr ON tr.term_taxonomy_id = tt.term_taxonomy_id
					INNER JOIN {$wpdb->cs_order_items} oi ON tr.object_id = oi.product_id
					INNER JOIN {$wpdb->cs_orders} o ON oi.order_id = o.id
					WHERE {$taxonomy}
					LIMIT {$limit}";

			$results = $wpdb->get_col( $sql ); // WPCS: unprepared SQL ok.

			if ( $results ) {
				foreach ( $results as $customer_id ) {
					$customer = new CS_Customer( $customer_id );

					$data[] = array(
						'id'        => $customer->id,
						'name'      => $customer->name,
						'email'     => $customer->email,
						'purchases' => $customer->purchase_count,
						'amount'    => cs_format_amount( $customer->purchase_value ),
					);
				}
			}

		// Download.
		} elseif ( ! empty( $this->download ) ) {
			// Export customers of a specific product

			$args = array(
				'product_id' => absint( $this->download ),
				'number'     => 30,
				'offset'     => 30 * ( $this->step - 1 ),
			);

			if ( null !== $this->price_id ) {
				$args['price_id'] = (int) $this->price_id;
			}

			$order_items = cs_get_order_items( $args );

			if ( $order_items ) {
				foreach ( $order_items as $item ) {
					$order = cs_get_order( $item->order_id );

					$customer = new CS_Customer( $order->customer_id );

					$data[] = array(
						'id'           => $customer->id,
						'user_id'      => $customer->user_id,
						'name'         => $customer->name,
						'email'        => $customer->email,
						'purchases'    => $customer->purchase_count,
						'amount'       => cs_format_amount( $customer->purchase_value ),
						'payment_ids'  => $customer->payment_ids,
						'date_created' => $customer->date_created,
					);
				}
			}

		// All customers.
		} else {
			$customers = cs_get_customers( array(
				'number' => 30,
				'offset' => 30 * ( $this->step - 1 ),
			) );

			$i = 0;

			foreach ( $customers as $customer ) {
				$data[ $i ]= array(
					'id'           => $customer->id,
					'user_id'      => $customer->user_id,
					'name'         => $customer->name,
					'email'        => $customer->email,
					'purchases'    => $customer->purchase_count,
					'amount'       => cs_format_amount( $customer->purchase_value ),
					'payment_ids'  => $customer->payment_ids,
					'date_created' => $customer->date_created,
				);

				$i++;
			}
		}

		$data = apply_filters( 'cs_export_get_data', $data );
		$data = apply_filters( 'cs_export_get_data_' . $this->export_type, $data );

		return $data;
	}

	/**
	 * Return the calculated completion percentage.
	 *
	 * @since 2.4
	 *
	 * @return float Percentage complete.
	 */
	public function get_percentage_complete() {
		$percentage = 0;

		// We can't count the number when getting them for a specific download.
		if ( empty( $this->download ) ) {
			$total = cs_count_customers();

			if ( $total > 0 ) {
				$percentage = ( ( 30 * $this->step ) / $total ) * 100;
			}
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	}

	/**
	 * Set the properties specific to the Customers export
	 *
	 * @since 2.4.2
	 *
	 * @param array $request Form data passed into the batch processing.
	 */
	public function set_properties( $request ) {
		$this->taxonomy = isset( $request['taxonomy'] )
			? absint( $request['taxonomy'] )
			: null;

		$this->download = isset( $request['download'] )
			? absint( $request['download'] )
			: null;

		$this->price_id = ! empty( $request['cs_price_option'] ) && 0 !== $request['cs_price_option']
			? absint( $request['cs_price_option'] )
			: null;
	}
}
