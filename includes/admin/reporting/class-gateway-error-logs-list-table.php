<?php
/**
 * Gateway Error Log View Class
 *
 * @package     CS
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CS_Gateway_Error_Log_Table Class
 *
 * @since 1.4
 * @since 3.0 Updated to use the custom tables and new query classes.
 */
class CS_Gateway_Error_Log_Table extends CS_Base_Log_List_Table {

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
	 * Gets the name of the primary column.
	 *
	 * @since 2.5
	 * @access protected
	 *
	 * @return string Name of the primary column.
	 */
	protected function get_primary_column_name() {
		return 'ID';
	}

	/**
	 * This function renders most of the columns in the list table.
	 *
	 * @since 1.4
	 *
	 * @param array $item Contains all the data of the log item
	 * @param string $column_name The name of the column
	 *
	 * @return string Column Name
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'error' :
				return $item['error'];
			case 'payment_id' :
				return ! empty( $item['payment_id'] ) ? $item['payment_id'] : '&ndash;';
			default:
				return $item[ $column_name ];
		}
	}

	/**
	 * Output Error Message Column
	 *
	 * @since 1.4.4
	 * @param array $item Contains all the data of the log
	 * @return void
	 */
	public function column_message( $item ) {
	?>
		<a href="#TB_inline?width=640&amp;inlineId=log-message-<?php echo $item['ID']; ?>" class="thickbox"><?php _e( 'View Log Message', 'commercestore' ); ?></a>
		<div id="log-message-<?php echo $item['ID']; ?>" style="display:none;">
			<?php

			$log_message = $item['content'];
			$serialized  = strpos( $log_message, '{"' );

			// Check to see if the log message contains serialized information
			if ( $serialized !== false ) {
				$length = strlen( $log_message ) - $serialized;
				$intro  = substr( $log_message, 0, - $length );
				$data   = substr( $log_message, $serialized, strlen( $log_message ) - 1 );

				echo wpautop( $intro );
				echo '<strong>' . wpautop( __( 'Log data:', 'commercestore' ) ) . '</strong>';
				echo '<div style="word-wrap: break-word;">' . wpautop( $data ) . '</div>';
			} else {
				// No serialized data found
				echo wpautop( $log_message );
			}
			?>
		</div>
	<?php
	}

	/**
	 * Retrieve the table columns
	 *
	 * @since 1.4
	 * @return array $columns Array of all the list table columns
	 */
	public function get_columns() {
		return array(
			'ID'         => __( 'Log ID',        'commercestore' ),
			'payment_id' => __( 'Order Number',  'commercestore' ),
			'error'      => __( 'Error',         'commercestore' ),
			'message'    => __( 'Error Message', 'commercestore' ),
			'gateway'    => __( 'Gateway',       'commercestore' ),
			'date'       => __( 'Date',          'commercestore' )
		);
	}

	/**
	 * Gets the log entries for the current view
	 *
	 * @since 1.4
	 * @param  array  $log_query Query arguments
	 * @global object $cs_logs CommerceStore Logs Object
	 * @return array $logs_data Array of all the Log entries
	 */
	public function get_logs( $log_query = array() ) {
		$logs_data         = array();
		$log_query['type'] = 'gateway_error';

		$logs = cs_get_logs( $log_query );

		if ( $logs ) {
			foreach ( $logs as $log ) {
				/** @var $log CS\Logs\Log */

				$logs_data[] = array(
					'ID'         => $log->id,
					'payment_id' => $log->object_id,
					'error'      => $log->title ? $log->title : __( 'Payment Error', 'commercestore' ),
					'gateway'    => cs_get_payment_gateway( $log->object_id ),
					'date'       => $log->date_created,
					'content'    => $log->content,
				);
			}
		}

		return $logs_data;
	}

	/**
	 * Setup the final data for the table.
	 *
	 * @since 1.5
	 */
	public function get_total( $log_query = array() ) {
		$log_query['type'] = 'gateway_error';

		return cs_count_logs( $log_query );
	}
}
