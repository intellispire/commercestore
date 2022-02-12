<?php
/**
 * Dashboard Widgets
 *
 * @package     CS
 * @subpackage  Admin/Dashboard
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Registers the dashboard widgets
 *
 * @author Sunny Ratilal
 * @since 1.2.2
 * @return void
 */
function cs_register_dashboard_widgets() {
	if ( current_user_can( apply_filters( 'cs_dashboard_stats_cap', 'view_shop_reports' ) ) ) {
		wp_add_dashboard_widget( 'cs_dashboard_sales', __('CommerceStore Sales Summary','commercestore' ), 'cs_dashboard_sales_widget' );
	}
}
add_action('wp_dashboard_setup', 'cs_register_dashboard_widgets', 10 );

/**
 * Sales Summary Dashboard Widget
 *
 * Builds and renders the Sales Summary dashboard widget. This widget displays
 * the current month's sales and earnings, total sales and earnings best selling
 * downloads as well as recent purchases made on your CommerceStore Store.
 *
 * @author Sunny Ratilal
 * @since 1.2.2
 * @return void
 */
function cs_dashboard_sales_widget() {
	if ( ! cs_has_upgrade_completed( 'migrate_orders' ) ) {
		global $wpdb;
		$orders = $wpdb->get_var( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'cs_payment' LIMIT 1" );
		if ( ! empty( $orders ) ) {
			$url = add_query_arg(
				array(
					'page'        => 'cs-upgrades',
					'cs-upgrade' => 'v30_migration',
				),
				admin_url( 'index.php' )
			);
			printf(
				'<p>%1$s <a href="%2$s">%3$s</a></p>',
				esc_html__( 'CommerceStore needs to upgrade the database. This summary will be available when that has completed.', 'commercestore' ),
				esc_url( $url ),
				esc_html__( 'Begin the upgrade.', 'commercestore' )
			);
			return;
		}
	}
	wp_enqueue_script( 'cs-admin-dashboard' );

	/**
	 * Action hook to add content to the dashboard widget.
	 * This content will not be replaced by the AJAX function:
	 * only the "cs-loading" content will.
	 *
	 * @since 2.11.4
	 */
	do_action( 'cs_dashboard_sales_widget' );
	?>
	<p class="cs-loading"><img src="<?php echo esc_url( CS_PLUGIN_URL . 'assets/images/loading.gif' ); ?>"></p>
	<?php
}

/**
 * Gets the sales earnings/count data for the dashboard widget.
 *
 * @since 3.0.0
 * @return array
 */
function cs_get_dashboard_sales_widget_data() {
	$data   = array();
	$ranges = array( 'this_month', 'last_month', 'today', 'total' );
	foreach ( $ranges as $range ) {
		$args = array(
			'range'  => $range,
			'output' => 'formatted',
		);
		if ( 'total' === $range ) {
			unset( $args['range'] );
		}
		// Remove filters so that deprecation notices are not unnecessarily logged outside of reports.
		remove_all_filters( 'cs_report_views' );
		$stats          = new CS\Stats( $args );
		$data[ $range ] = array(
			'earnings' => $stats->get_order_earnings(),
			'count'    => $stats->get_order_count(),
		);
	}

	return $data;
}

/**
 * Loads the dashboard sales widget via ajax
 *
 * @since 2.1
 * @return void
 */
function cs_load_dashboard_sales_widget( ) {

	if ( ! current_user_can( apply_filters( 'cs_dashboard_stats_cap', 'view_shop_reports' ) ) ) {
		die();
	}

	$stats = new CS_Payment_Stats();
	$data  = cs_get_dashboard_sales_widget_data(); ?>
	<div class="cs_dashboard_widget">
		<div class="table table_left table_current_month">
			<table>
				<thead>
					<tr>
						<td colspan="2"><?php esc_html_e( 'Current Month', 'commercestore' ); ?></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="first t monthly_earnings"><?php esc_html_e( 'Earnings', 'commercestore' ); ?></td>
						<td class="b b-earnings"><?php echo esc_html( $data['this_month']['earnings'] ); ?></td>
					</tr>
						<td class="first t monthly_sales"><?php echo esc_html( _n( 'Sale', 'Sales', $data['this_month']['count'], 'commercestore' ) ); ?></td>
						<td class="b b-sales"><?php echo esc_html( $data['this_month']['count'] ); ?></td>
					</tr>
				</tbody>
			</table>
			<table>
				<thead>
					<tr>
						<td colspan="2"><?php esc_html_e( 'Last Month', 'commercestore' ); ?></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="first t earnings"><?php esc_html_e( 'Earnings', 'commercestore' ); ?></td>
						<td class="b b-last-month-earnings"><?php echo esc_html( $data['last_month']['earnings'] ); ?></td>
					</tr>
					<tr>
						<td class="first t sales"><?php echo esc_html( _n( 'Sale', 'Sales', $data['last_month']['count'], 'commercestore' ) ); ?></td>
						<td class="b b-last-month-sales"><?php echo esc_html( $data['last_month']['count'] ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="table table_right table_today">
			<table>
				<thead>
					<tr>
						<td colspan="2">
							<?php esc_html_e( 'Today', 'commercestore' ); ?>
						</td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="t sales"><?php esc_html_e( 'Earnings', 'commercestore' ); ?></td>
						<td class="last b b-earnings">
							<?php echo esc_html( $data['today']['earnings'] ); ?>
						</td>
					</tr>
					<tr class="t sales">
						<td class="t sales"><?php echo esc_html( _n( 'Sale', 'Sales', $data['today']['count'], 'commercestore' ) ); ?></td>
						<td class="last b b-sales"><?php echo esc_html( $data['today']['count'] ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div class="table table_right table_totals">
			<table>
				<thead>
					<tr>
						<td colspan="2"><?php esc_html_e( 'Totals', 'commercestore' ); ?></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td class="t earnings"><?php esc_html_e( 'Total Earnings', 'commercestore' ); ?></td>
						<td class="last b b-earnings"><?php echo esc_html( $data['total']['earnings'] ); ?></td>
					</tr>
					<tr>
						<td class="t sales"><?php echo esc_html( _n( 'Sale', 'Sales', $data['total']['count'], 'commercestore' ) ); ?></td>
						<td class="last b b-sales"><?php echo esc_html( $data['total']['count'] ); ?></td>
					</tr>
				</tbody>
			</table>
		</div>
		<div style="clear: both"></div>
		<?php do_action( 'cs_sales_summary_widget_after_stats', $stats ); ?>
		<?php
		$payments = cs_get_payments( array( 'number' => 5, 'status' => 'complete' ) );

		if ( $payments ) { ?>
		<div class="table recent_orders">
			<h3><?php esc_html_e( 'Recent Orders', 'commercestore' ); ?></h3>
			<ul>
			<?php
			foreach ( $payments as $payment ) {
				$link = cs_get_admin_url(
					array(
						'page' => 'cs-payment-history',
						'view' => 'view-order-details',
						'id'   => urlencode( $payment->ID ),
					),
					admin_url( 'edit.php' )
				);
				?>
				<li class="cs_order_label">
					<a href="<?php echo esc_url( $link ); ?>">
						<?php
						$customer      = cs_get_customer( $payment->customer_id );
						$customer_name = ! empty( $customer->name ) ? $customer->name : __( 'No Name', 'commercestore' );
						$item_count    = cs_count_order_items( array( 'order_id' => $payment->ID ) );
						echo wp_kses_post(
							sprintf(
								/* translators: 1. customer name; 2. number of items purchased; 3. order total */
								_n(
									'%1$s purchased %2$s item for <strong>%3$s</strong>',
									'%1$s purchased %2$s items for <strong>%3$s</strong>',
									$item_count,
									'commercestore'
								),
								$customer_name,
								$item_count,
								cs_currency_filter( cs_format_amount( cs_get_order_total( $payment->ID ) ) )
							)
						);
						?>
					</a>
					<br /><?php echo esc_html( cs_date_i18n( $payment->date ) ); ?>
				</li>
				<?php } // End foreach ?>
		</ul>
			<?php
			$all_orders_link = cs_get_admin_url(
				array(
					'page' => 'cs-payment-history',
				)
			);
			?>
		<a href="<?php echo esc_url( $all_orders_link ); ?>" class="button-secondary"><?php esc_html_e( 'View All Orders', 'commercestore' ); ?></a>
		</div>
		<?php } // End if ?>
		<?php do_action( 'cs_sales_summary_widget_after_purchases', $payments ); ?>
	</div>
	<?php
	die();
}
add_action( 'wp_ajax_cs_load_dashboard_widget', 'cs_load_dashboard_sales_widget' );

/**
 * Add download count to At a glance widget
 *
 * @author Daniel J Griffiths
 * @since 2.1
 * @return void
 */
function cs_dashboard_at_a_glance_widget( $items ) {
	$num_posts = wp_count_posts( 'download' );

	if ( $num_posts && $num_posts->publish ) {
		$text = _n( '%s ' . cs_get_label_singular(), '%s ' . cs_get_label_plural(), $num_posts->publish, 'commercestore' );

		$text = sprintf( $text, number_format_i18n( $num_posts->publish ) );

		if ( current_user_can( 'edit_products' ) ) {
			$text = sprintf( '<a class="download-count" href="edit.php?post_type=download">%1$s</a>', $text );
		} else {
			$text = sprintf( '<span class="download-count">%1$s</span>', $text );
		}

		$items[] = $text;
	}

	return $items;
}
add_filter( 'dashboard_glance_items', 'cs_dashboard_at_a_glance_widget', 1 );
