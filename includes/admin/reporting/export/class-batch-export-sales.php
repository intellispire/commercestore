<?php
/**
 * Batch Sales Logs Export Class
 *
 * This class handles Sales logs export
 *
 * @package     CS
 * @subpackage  Admin/Reporting/Export
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.7
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CS_Batch_Sales_Export Class
 *
 * @since 2.7
 */
class CS_Batch_Sales_Export extends CS_Batch_Export {
	/**
	 * Our export type. Used for export-type specific filters/actions
	 *
	 * @var string
	 * @since 2.7
	 */
	public $export_type = 'sales';

	/**
	 * Set the CSV columns
	 *
	 * @since 2.7
	 * @return array $cols All the columns
	 */
	public function csv_cols() {
		$cols = array(
			'ID'          => __( 'Log ID', 'commercestore' ),
			'user_id'     => __( 'User', 'commercestore' ),
			'customer_id' => __( 'Customer ID', 'commercestore' ),
			'email'       => __( 'Email', 'commercestore' ),
			'first_name'  => __( 'First Name', 'commercestore' ),
			'last_name'   => __( 'Last Name', 'commercestore' ),
			'download'    => cs_get_label_singular(),
			'quantity'    => __( 'Quantity', 'commercestore' ),
			'amount'      => __( 'Item Amount', 'commercestore' ),
			'payment_id'  => __( 'Payment ID', 'commercestore' ),
			'price_id'    => __( 'Price ID', 'commercestore' ),
			'date'        => __( 'Date', 'commercestore' ),
		);

		return $cols;
	}

	/**
	 * Get the Export Data
	 *
	 * @since 2.7
	 * @since 3.0 Updated to use new query methods.
	 *
	 * @return array|bool The data for the CSV file, false if no data to return.
	 */
	public function get_data() {
		$data = array();

		$args = array(
			'number' => 30,
			'offset' => ( $this->step * 30 ) - 30,
			'order'  => 'ASC',
		);

		if ( ! empty( $this->start ) || ! empty( $this->end ) ) {
			$args['date_query'] = $this->get_date_query();
		}

		if ( 0 !== $this->download_id ) {
			$args['product_id'] = $this->download_id;
		}

		$items = cs_get_order_items( $args );

		foreach ( $items as $item ) {
			/** @var CS\Orders\Order_Item $item */
			$order     = cs_get_order( $item->order_id );
			$download  = cs_get_download( $item->product_id );
			$user_info = $order->get_user_info();

			$download_title = $item->product_name;

			// Maybe append variable price name.
			if ( $download->has_variable_prices() ) {
				$price_option = cs_get_price_option_name( $item->product_id, $item->price_id, $order->id );

				$download_title .= ! empty( $price_option )
					? ' - ' . $price_option
					: '';
			}

			$data[] = array(
				'ID'          => $item->product_id,
				'user_id'     => $order->user_id,
				'customer_id' => $order->customer_id,
				'email'       => $order->email,
				'first_name'  => isset( $user_info['first_name'] ) ? $user_info['first_name'] : '',
				'last_name'   => isset( $user_info['last_name'] ) ? $user_info['last_name'] : '',
				'download'    => $download_title,
				'quantity'    => $item->quantity,
				'amount'      => $order->total,
				'payment_id'  => $order->id,
				'price_id'    => $item->price_id,
				'date'        => $order->date_created,
			);
		}

		$data = apply_filters( 'cs_export_get_data', $data );
		$data = apply_filters( 'cs_export_get_data_' . $this->export_type, $data );

		return ! empty( $data )
			? $data
			: false;
	}
	/**
	 * Return the calculated completion percentage.
	 *
	 * @since 2.7
	 * @since 3.0 Updated to use new query methods.
	 *
	 * @return int
	 */
	public function get_percentage_complete() {
		$args = array(
			'fields' => 'ids',
		);

		if ( ! empty( $this->start ) || ! empty( $this->end ) ) {
			$args['date_query'] = $this->get_date_query();
		}

		if ( 0 !== $this->download_id ) {
			$args['product_id'] = $this->download_id;
		}

		$total = cs_count_order_items( $args );
		$percentage = 100;

		if ( $total > 0 ) {
			$percentage = ( ( 30 * $this->step ) / $total ) * 100;
		}

		if ( $percentage > 100 ) {
			$percentage = 100;
		}

		return $percentage;
	}

	public function set_properties( $request ) {
		$this->start       = isset( $request['orders-export-start'] ) ? sanitize_text_field( $request['orders-export-start'] ) : '';
		$this->end         = isset( $request['orders-export-end'] ) ? sanitize_text_field( $request['orders-export-end'] ) . ' 23:59:59' : '';
		$this->download_id = isset( $request['download_id'] ) ? absint( $request['download_id'] ) : 0;
	}
}
