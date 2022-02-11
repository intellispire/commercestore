<?php
/**
 * Admin / Heartbeat
 *
 * @package     CS
 * @subpackage  Admin
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.8
*/

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CS_Heartbeart Class
 *
 * Hooks into the WP heartbeat API to update various parts of the dashboard as new sales are made
 *
 * Dashboard components that are effect:
 *	- Dashboard Summary Widget
 *
 * @since 1.8
 */
class CS_Heartbeat {

	/**
	 * Get things started
	 *
	 * @since 1.8
	 * @return void
	 */
	public static function init() {

		add_filter( 'heartbeat_received', array( 'CS_Heartbeat', 'heartbeat_received' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( 'CS_Heartbeat', 'enqueue_scripts' ) );
	}

	/**
	 * Tie into the heartbeat and append our stats
	 *
	 * @since 1.8
	 * @return array
	 */
	public static function heartbeat_received( $response, $data ) {

		if ( ! current_user_can( 'view_shop_reports' ) ) {
			return $response; // Only modify heartbeat if current user can view show reports
		}

		// Make sure we only run our query if the cs_heartbeat key is present
		if ( ( isset( $data['cs_heartbeat'] ) ) && ( 'dashboard_summary' === $data['cs_heartbeat'] ) ) {

			$stats                          = cs_get_dashboard_sales_widget_data();
			$response['cs-total-payments'] = $stats['total']['count'];
			$response['cs-total-earnings'] = html_entity_decode( $stats['total']['earnings'] );
			$response['cs-payments-month'] = $stats['this_month']['count'];
			$response['cs-earnings-month'] = html_entity_decode( $stats['this_month']['earnings'] );
			$response['cs-payments-today'] = $stats['today']['count'];
			$response['cs-earnings-today'] = html_entity_decode( $stats['today']['earnings'] );
		}

		return $response;

	}

	/**
	 * Load the heartbeat scripts
	 *
	 * @since 1.8
	 * @return array
	 */
	public static function enqueue_scripts() {

		if( ! current_user_can( 'view_shop_reports' ) ) {
			return; // Only load heartbeat if current user can view show reports
		}

		// Make sure the JS part of the Heartbeat API is loaded.
		wp_enqueue_script( 'heartbeat' );
		add_action( 'admin_print_footer_scripts', array( 'CS_Heartbeat', 'footer_js' ), 20 );
	}

	/**
	 * Inject our JS into the admin footer
	 *
	 * @since 1.8
	 * @return array
	 */
	public static function footer_js() {
		global $pagenow;

		// Only proceed if on the dashboard
		if( 'index.php' != $pagenow ) {
			return;
		}

		if( ! current_user_can( 'view_shop_reports' ) ) {
			return; // Only load heartbeat if current user can view show reports
		}

		?>
		<script>
			(function($){
				// Hook into the heartbeat-send
				$(document).on('heartbeat-send', function(e, data) {
					data['cs_heartbeat'] = 'dashboard_summary';
				});

				// Listen for the custom event "heartbeat-tick" on $(document).
				$(document).on( 'heartbeat-tick', function(e, data) {

					// Only proceed if our CommerceStore data is present
					if ( ! data['cs-total-payments'] )
						return;

					<?php if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) : ?>
					console.log('tick');
					<?php endif; ?>

					// Update sale count and bold it to provide a highlight
					cs_dashboard_heartbeat_update( '.cs_dashboard_widget .table_totals .b.b-earnings', data['cs-total-earnings'] );
					cs_dashboard_heartbeat_update( '.cs_dashboard_widget .table_totals .b.b-sales', data['cs-total-payments'] );
					cs_dashboard_heartbeat_update( '.cs_dashboard_widget .table_today .b.b-earnings', data['cs-earnings-today'] );
					cs_dashboard_heartbeat_update( '.cs_dashboard_widget .table_today .b.b-sales', data['cs-payments-today'] );
					cs_dashboard_heartbeat_update( '.cs_dashboard_widget .table_current_month .b-earnings', data['cs-earnings-month'] );
					cs_dashboard_heartbeat_update( '.cs_dashboard_widget .table_current_month .b-sales', data['cs-payments-month'] );

					// Return font-weight to normal after 2 seconds
					setTimeout(function(){
						$('.cs_dashboard_widget .b.b-sales,.cs_dashboard_widget .b.b-earnings').css( 'font-weight', 'normal' );
						$('.cs_dashboard_widget .table_current_month .b.b-earnings,.cs_dashboard_widget .table_current_month .b.b-sales').css( 'font-weight', 'normal' );
					}, 2000);

				});

				function cs_dashboard_heartbeat_update( selector, new_value ) {
					var current_value = $(selector).text();
					$(selector).text( new_value );
					if ( current_value !== new_value ) {
						$(selector).css( 'font-weight', 'bold' );
					}
				}
			}(jQuery));
		</script>
		<?php
	}
}
add_action( 'plugins_loaded', array( 'CS_Heartbeat', 'init' ) );
