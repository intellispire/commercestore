<?php
/**
 * Logs UI
 *
 * @package     CS
 * @subpackage  Admin/Tools
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
*/

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Setup the logs view
 *
 * @since 3.0
 *
 * @param type $type
 * @return boolean
 */
function cs_logs_view_setup( $type = '' ) {

	// Bail if cannot view
	if ( ! current_user_can( 'view_shop_reports' ) ) {
		return false;
	}

	// Includes
	require_once ABSPATH        . 'wp-admin/includes/class-wp-list-table.php';
	require_once CS_PLUGIN_DIR . 'includes/admin/reporting/class-base-logs-list-table.php';
	require_once CS_PLUGIN_DIR . 'includes/admin/reporting/class-' . sanitize_key( $type ) . '-logs-list-table.php';

	// Done!
	return true;
}

/**
 * Output the log page
 *
 * @since 3.0
 *
 * @param CS_Base_Log_List_Table $logs_table List table class to work with
 * @param string                  $tag        Type of log to view
 */
function cs_logs_view_page( $logs_table, $tag = '' ) {
	$tag = sanitize_key( $tag );
	$logs_table->prepare_items(); ?>

	<div class="wrap">
		<?php
		/**
		 * Fires at the top of the logs view.
		 *
		 * @since 3.0
		 */
		do_action( "cs_logs_{$tag}_top" ); ?>

		<form id="cs-logs-filter" method="get" action="<?php echo admin_url( 'edit.php?post_type=download&page=cs-tools&tab=' . $tag ); ?>">
			<input type="hidden" name="post_type" value="download" />
			<input type="hidden" name="page" value="cs-tools" />
			<input type="hidden" name="tab" value="<?php echo esc_attr( $tag ); ?>" />
			<?php
			wp_nonce_field( -1, 'cs_filter', false );
			$logs_table->views();
			$logs_table->advanced_filters();
			?>
		</form>
		<?php
		$logs_table->display();
		?>

		<?php
		/**
		 * Fires at the bottom of the logs view.
		 *
		 * @since 3.0
		 */
		do_action( "cs_logs_{$tag}_bottom" ); ?>

	</div>
<?php
}

/** Views *********************************************************************/

/**
 * Sales Log View
 *
 * @deprecated 3.0
 *
 * @since 1.4
 * @uses CS_Sales_Log_Table::prepare_items()
 * @uses CS_Sales_Log_Table::display()
 * @return void
 */
function cs_logs_view_sales() {

	// Setup or bail
	if ( ! cs_logs_view_setup( 'sales' ) ) {
		return;
	}

	$logs_table = new CS_Sales_Log_Table();

	cs_logs_view_page( $logs_table, 'sales' );
}
add_action( 'cs_logs_view_sales', 'cs_logs_view_sales' );

/**
 * File Download Logs
 *
 * @since 1.4
 * @uses CS_File_Downloads_Log_Table::prepare_items()
 * @uses CS_File_Downloads_Log_Table::search_box()
 * @uses CS_File_Downloads_Log_Table::display()
 * @return void
 */
function cs_logs_view_file_downloads() {

	// Setup or bail
	if ( ! cs_logs_view_setup( 'file-downloads' ) ) {
		return;
	}

	$logs_table = new CS_File_Downloads_Log_Table();

	cs_logs_view_page( $logs_table, 'file_downloads' );
}
add_action( 'cs_logs_view_file_downloads', 'cs_logs_view_file_downloads' );

/**
 * Gateway Error Logs
 *
 * @since 1.4
 * @uses CS_File_Downloads_Log_Table::prepare_items()
 * @uses CS_File_Downloads_Log_Table::display()
 * @return void
 */
function cs_logs_view_gateway_errors() {

	// Setup or bail
	if ( ! cs_logs_view_setup( 'gateway-error' ) ) {
		return;
	}

	$logs_table = new CS_Gateway_Error_Log_Table();

	cs_logs_view_page( $logs_table, 'gateway_errors' );
}
add_action( 'cs_logs_view_gateway_errors', 'cs_logs_view_gateway_errors' );

/**
 * API Request Logs
 *
 * @since 1.5
 * @uses CS_API_Request_Log_Table::prepare_items()
 * @uses CS_API_Request_Log_Table::search_box()
 * @uses CS_API_Request_Log_Table::display()
 * @return void
 */

function cs_logs_view_api_requests() {

	// Setup or bail
	if ( ! cs_logs_view_setup( 'api-requests' ) ) {
		return;
	}

	$logs_table = new CS_API_Request_Log_Table();

	cs_logs_view_page( $logs_table, 'api_requests' );
}
add_action( 'cs_logs_view_api_requests', 'cs_logs_view_api_requests' );


/**
 * Default Log Views
 *
 * @since 1.4
 * @return array $views Log Views
 */
function cs_log_default_views() {
	/**
	 * Filters the default logs views.
	 *
	 * @since 1.4
	 * @since 3.0 Removed sales log.
	 *
	 * @param array $views Logs views. Each key/value pair represents the view slug
	 *                     and label, respectively.
	 */
	return apply_filters( 'cs_log_views', array(
		'file_downloads'  => __( 'File Downloads', 'commercestore' ),
		'gateway_errors'  => __( 'Payment Errors', 'commercestore' ),
		'api_requests'    => __( 'API Requests',   'commercestore' )
	) );
}

/**
 * Renders the Reports page views drop down
 *
 * @since 1.3
 * @since 3.0 Deprecated, and modified to look like the 3.0 approach
 *
 * @return void
*/
function cs_log_views() {
	static $once = false;

	// Only once
	if ( true === $once ) {
		return;
	}

	// Only once
	$once = true; ?>

	<!-- CommerceStore 3.0 Hack -->
	</div></div>
	<form method="get" class="cs-old-log-filters" action="<?php echo admin_url( 'edit.php?post_type=download&page=cs-payment-history' ); ?>">
		<?php cs_admin_filter_bar( 'old_logs' ); ?>
	</form>
	<div class="tablenav top"><div>
	<!-- CommerceStore 3.0 Hack -->

<?php
}

/**
 * Output old logs filter bar items
 *
 * @since 3.0
 */
function cs_old_logs_filter_bar_items() {
	$views        = cs_log_default_views();
	$current_view = isset( $_GET['view'] ) && array_key_exists( $_GET['view'], cs_log_default_views() )
		? sanitize_text_field( $_GET['view'] )
		: 'file_downloads'; ?>

	<span id="cs-type-filter">
		<select id="cs-logs-view" name="view">
			<?php foreach ( $views as $view_id => $label ) : ?>
				<option value="<?php echo esc_attr( $view_id ); ?>" <?php selected( $view_id, $current_view ); ?>><?php echo esc_html( $label ); ?></option>
			<?php endforeach; ?>
		</select>
	</span>

	<?php
	/**
	 * Fires immediately after the logs view actions are rendered in the Logs screen.
	 *
	 * @since 1.3
	 */
	do_action( 'cs_log_view_actions' ); ?>

	<button type="submit "class="button button-secondary"><?php _e( 'Filter', 'commercestore' ); ?></button>

	<input type="hidden" name="post_type" value="download" />
	<input type="hidden" name="page" value="cs-tools" />
	<input type="hidden" name="tab" value="logs" /><?php
}
add_action( 'cs_admin_filter_bar_old_logs', 'cs_old_logs_filter_bar_items' );
