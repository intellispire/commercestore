<?php
/**
 * File Downloads Log List Table.
 *
 * @package     CS
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4.4
 * @since       3.0 Updated to use the custom tables.
 */

use CS\Logs\File_Download_Log;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CS_File_Downloads_Log_Table Class
 *
 * @since 1.4
 * @since 3.0 Updated to use the custom tables and new query classes.
 */
class CS_File_Downloads_Log_Table extends CS_Base_Log_List_Table {

	/**
	 * Log type
	 *
	 * @var string
	 */
	protected $log_type = 'file_downloads';

	/**
	 * Are we searching for files?
	 *
	 * @var bool
	 * @since 1.4
	 */
	public $file_search = false;

	/**
	 * Store each unique product's files so they only need to be queried once
	 *
	 * @var array
	 * @since 1.9
	 */
	private $queried_files = array();

	/**
	 * Get things started
	 *
	 * @since 1.4
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @since 1.4
	 *
	 * @param array  $item Contains all the data of the log item.
	 * @param string $column_name The name of the column.
	 *
	 * @return string Column Name
	 */
	public function column_default( $item, $column_name ) {
		$base_url = remove_query_arg( 'paged' );
		switch ( $column_name ) {
			case 'download' :
				$download     = new CS_Download( $item[ $column_name ] );
				$column_value = ! empty( $item['price_id'] )
					? cs_get_download_name( $download->ID, $item['price_id'] )
					: cs_get_download_name( $download->ID );

				return '<a href="' . esc_url( add_query_arg( 'download', $download->ID, $base_url ) ) . '" >' . $column_value . '</a>';
			case 'customer' :
				return ! empty( $item[ 'customer' ]->id )
					? '<a href="' . esc_url( add_query_arg( 'customer', $item[ 'customer' ]->id, $base_url ) ) . '">' . $item['customer']->name . '</a>'
					: '&mdash;';

			case 'payment_id' :
				$number = cs_get_payment_number( $item['payment_id'] );
				return ! empty( $number )
					? '<a href="' . esc_url( admin_url( 'edit.php?post_type=download&page=cs-payment-history&view=view-order-details&id=' . esc_attr( $item['payment_id'] ) ) ) . '">' . esc_html( $number ) . '</a>'
					: '&mdash;';
			case 'ip' :
				return '<a href="' . esc_url( 'https://ipinfo.io/' . esc_attr( $item['ip'] ) )  . '" target="_blank" rel="noopener noreferrer">' . esc_html( $item['ip'] )  . '</a>';
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * Set the table columns.
	 *
	 * @since 1.4
     *
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		return array(
			'ID'         => __( 'Log ID',       'commercestore' ),
			'download'   => cs_get_label_singular(),
			'customer'   => __( 'Customer',     'commercestore' ),
			'payment_id' => __( 'Order Number', 'commercestore' ),
			'file'       => __( 'File',         'commercestore' ),
			'ip'         => __( 'IP Address',   'commercestore' ),
			'user_agent' => __( 'User Agent',   'commercestore' ),
			'date'       => __( 'Date',         'commercestore' )
		);
	}

	/**
	 * Gets the log entries for the current view
	 *
	 * @since 1.4
	 *
	 * @param $log_query array Arguments for getting logs.
     *
	 * @return array $logs_data Array of all the logs.
	 */
	function get_logs( $log_query = array() ) {
		$logs_data = array();

		$logs = cs_get_file_download_logs( $log_query );

		if ( $logs ) {
			foreach ( $logs as $log ) {
				/** @var $log File_Download_Log */

				$customer_id = ! empty( $log->customer_id ) ? (int) $log->customer_id : cs_get_payment_customer_id( $log->order_id );

				$files = $this->get_log_files( $log, $customer_id );

				$file_id = $log->file_id;

				/**
				 * Filters the ID of the file that was actually downloaded from this log.
				 *
				 * @param int               $file_id
				 * @param File_Download_Log $log
				 */
				$file_id = apply_filters( 'cs_log_file_download_file_id', $file_id, $log );

				$file_name = isset( $files[ $file_id ]['name'] )
					? $files[ $file_id ]['name']
					: null;

				if ( empty( $this->file_search ) || ( ! empty( $this->file_search ) && strpos( strtolower( $file_name ), strtolower( $this->get_search() ) ) !== false ) ) {
					$logs_data[] = array(
						'ID'         => $log->id,
						'download'   => $log->product_id,
						'customer'   => new CS_Customer( $customer_id ),
						'payment_id' => $log->order_id,
						'price_id'   => $log->price_id,
						'file'       => $file_name,
						'ip'         => $log->ip,
						'user_agent' => $log->user_agent,
						'date'       => $log->date_created,
					);
				}
			}
		}

		return $logs_data;
	}

	/**
	 * Retrieves the array of files associated with the log.
	 *
	 * This method contains a lot of logic to ensure backwards compatibility with how
	 * the `cs_log_file_download_download_files` filter was formatted in CommerceStore 2.x.
	 *
	 * @since 3.0
	 *
	 * @param File_Download_Log $log
	 * @param int               $customer_id
	 *
	 * @return array
	 */
	protected function get_log_files( File_Download_Log $log, $customer_id ) {
		$customer = ! empty( $customer_id ) ? cs_get_customer( $customer_id ) : false;

		/*
		 * Get the files associated with this download and store them in a property to prevent
		 * multiple queries for the same download.
		 * This is needed for backwards compatibility in the `cs_log_file_download_download_files` filter.
		 */
		if ( ! array_key_exists( $log->product_id, $this->queried_files ) ) {
			$files = get_post_meta( $log->product_id, 'cs_download_files', true );
			$this->queried_files[ $log->product_id ] = $files;
		} else {
			$files = $this->queried_files[ $log->product_id ];
		}

		/*
		 * User info is needed for backwards compatibility in the `cs_log_file_download_download_files` filter.
		 */
		$user = ! empty( $customer->user_id ) ? get_userdata( $customer->user_id ) : false;

		$user_info = ! empty( $user )
			? array(
				'id'    => $user->ID,
				'email' => $user->user_email,
				'name'  => $user->display_name,
			)
			: array();

		/*
		 * Build up an array of meta.
		 */
		$meta = cs_get_file_download_log_meta( $log->id );

		// These meta keys no longer exist, but we need to create them for use in the filter.
		$backwards_compat_meta = array(
			'_cs_log_user_info'   => $user_info,
			'_cs_log_user_id'     => ! empty( $customer->user_id ) ? $customer->user_id : false,
			'_cs_log_customer_id' => $customer_id,
			'_cs_log_file_id'     => $log->file_id,
			'_cs_log_ip'          => $log->ip,
			'_cs_log_payment_id'  => $log->order_id,
			'_cs_log_price_id'    => $log->price_id,
		);

		foreach( $backwards_compat_meta as $meta_key => $meta_value ) {
			$meta[ $meta_key ] = array( $meta_value );
		}

		/**
		 * Filters the array of all files linked to the product.
		 *
		 * @param array             $files Files linked to the product.
		 * @param File_Download_Log $log   Log record from the database.
		 * @param array             $meta  What used to be the meta array in CommerceStore 2.9 and lower.
		 */
		return apply_filters( 'cs_log_file_download_download_files', $files, $log, $meta );
	}

	/**
	 * Setup the final data for the table.
	 *
	 * @since 1.5
	 */
	public function get_total( $log_query = array() ) {
		return cs_count_file_download_logs( $log_query );
	}
}
