<?php

/**
 * Class CS_Recurring_Reports
 *
 * @since 2.4
 *
 */
class CS_Recurring_Reports {


	/**
	 * Get it started
	 */
	public function __construct() {

		if ( function_exists( 'cs_add_order' ) ) {
			// CommerceStore 3.0+ Graph Reports
			add_action( 'cs_reports_init', array( $this, 'register_reports' ) );
		} else {
			// CommerceStore 2.9 and below Graph Reports
			add_filter( 'cs_report_views', array( $this, 'add_subscriptions_reports_view' ) );
			add_action( 'cs_reports_view_subscriptions', array( $this, 'display_subscriptions_report' ) );
		}

		//Payments' subscription status column
		add_filter( 'cs_payments_table_column', array( $this, 'status_column' ), 800, 3 );

	}

	/**
	 * Registers reports with CS
	 *
	 * @param CS\Reports\Data\Report_Registry $reports
	 *
	 * @since 2.10.1
	 * @return void
	 */
	public function register_reports( $reports ) {
		try {
			$options = CS\Reports\get_dates_filter_options();
			$dates   = CS\Reports\get_filter_value( 'dates' );
			$label   = $options[ $dates['range'] ];

			$reports->add_report( 'recurring_subscription_renewals', array(
				'label'     => __( 'Subscription Renewals', 'cs-recurring' ),
				'icon'      => 'chart-area', // @todo is there a better one?
				'priority'  => 60,
				'endpoints' => array(
					'tiles' => array(
						'recurring_subscription_renewals_number',
						'recurring_subscription_renewals_refunded_number',
						'recurring_subscription_renewals_gross_earnings',
						'recurring_subscription_renewals_refunded_amount',
						'recurring_subscription_renewals_net_earnings'
					),
					'charts' => array(
						'recurring_subscription_renewals_chart'
					)
				)
			) );

			$reports->register_endpoint( 'recurring_subscription_renewals_number', array(
				'label' => __( 'Number of Renewals', 'cs-recurring' ),
				'views' => array(
					'tile' => array(
						'data_callback' => 'cs_recurring_renewals_number_callback',
						'display_args'  => array(
							'comparison_label' => $label
						)
					)
				)
			) );

			$reports->register_endpoint( 'recurring_subscription_renewals_refunded_number', array(
				'label' => __( 'Number of Refunded Renewals', 'cs-recurring' ),
				'views' => array(
					'tile' => array(
						'data_callback' => 'cs_recurring_renewals_refunded_number_callback',
						'display_args'  => array(
							'comparison_label' => $label
						)
					)
				)
			) );

			$reports->register_endpoint( 'recurring_subscription_renewals_gross_earnings', array(
				'label' => __( 'Gross Renewal Earnings', 'cs-recurring' ),
				'views' => array(
					'tile' => array(
						'data_callback' => function() {
							return cs_currency_filter( cs_format_amount( cs_recurring_get_gross_renewal_earnings_for_report_period() ) );
						},
						'display_args'  => array(
							'comparison_label' => $label
						)
					)
				)
			) );

			$reports->register_endpoint( 'recurring_subscription_renewals_refunded_amount', array(
				'label' => __( 'Refunded Renewals', 'cs-recurring' ),
				'views' => array(
					'tile' => array(
						'data_callback' => function() {
							return cs_currency_filter( cs_format_amount( abs( cs_recurring_get_refunded_amount_for_report_period() ) ) );
						},
						'display_args'  => array(
							'comparison_label' => $label
						)
					)
				)
			) );

			$reports->register_endpoint( 'recurring_subscription_renewals_net_earnings', array(
				'label' => __( 'Net Renewal Earnings', 'cs-recurring' ),
				'views' => array(
					'tile' => array(
						'data_callback' => function() {
							$net = cs_recurring_get_gross_renewal_earnings_for_report_period() - cs_recurring_get_refunded_amount_for_report_period();

							return cs_currency_filter( cs_format_amount( $net ) );
						},
						'display_args'  => array(
							'comparison_label' => $label
						)
					)
				)
			) );

			$reports->register_endpoint( 'recurring_subscription_renewals_chart', array(
				'label' => __( 'Subscription Renewals', 'cs-recurring' ),
				'views' => array(
					'chart' => array(
						'data_callback' => 'CS_Recurring_Reports_Chart::get_chart_data',
						'type'          => 'line',
						'options'       => array(
							'datasets' => array(
								'renewals' => array(
									'label'                => __( 'Renewals', 'cs-recurring' ),
									'borderColor'          => 'rgb(237,194,64)',
									'backgroundColor'      => 'rgba(237,194,64,0.2)',
									'fill'                 => true,
									'borderDash'           => array( 2, 6 ),
									'borderCapStyle'       => 'round',
									'borderJoinStyle'      => 'round',
									'pointRadius'          => 4,
									'pointHoverRadius'     => 6,
									'pointBackgroundColor' => 'rgb(255,255,255)',
								),
								'refunds' => array(
									'label'                => __( 'Refunds', 'cs-recurring' ),
									'borderColor'          => 'rgb(175,216,248)',
									'backgroundColor'      => 'rgba(175,216,248,0.05)',
									'fill'                 => true,
									'borderDash'           => array( 2, 6 ),
									'borderCapStyle'       => 'round',
									'borderJoinStyle'      => 'round',
									'pointRadius'          => 4,
									'pointHoverRadius'     => 6,
									'pointBackgroundColor' => 'rgb(255,255,255)',
								),
								'earnings' => array(
									'label'                => __( 'Earnings', 'cs-recurring' ),
									'borderColor'          => 'rgb(203,75,75)',
									'backgroundColor'      => 'rgba(203,75,75,0.05)',
									'fill'                 => true,
									'borderWidth'          => 2,
									'type'                 => 'currency',
									'pointRadius'          => 4,
									'pointHoverRadius'     => 6,
									'pointBackgroundColor' => 'rgb(255,255,255)',
								),
								'refunded_earnings' => array(
									'label'                => __( 'Refunded Earnings', 'cs-recurring' ),
									'borderColor'          => 'rgb(77,167,77)',
									'backgroundColor'      => 'rgba(77,167,77,0.05)',
									'fill'                 => true,
									'borderWidth'          => 2,
									'type'                 => 'currency',
									'pointRadius'          => 4,
									'pointHoverRadius'     => 6,
									'pointBackgroundColor' => 'rgb(255,255,255)',
								),
							)
						)
					)
				)
			) );

		} catch( \Exception $e ) {

		}
	}

	/**
	 * Adds "Subscriptions Revenue" to the report views
	 *
	 * @param $views
	 *
	 * @return mixed
	 */
	public function add_subscriptions_reports_view( $views ) {
		$views['subscriptions'] = __( 'Subscription Renewals', 'cs-recurring' );

		return $views;
	}


	/**
	 * Get Subscription by Date
	 *
	 * @description: Helper function for reports
	 *
	 * @since      2.4
	 *
	 * @param null $day
	 * @param null $month
	 * @param null $year
	 * @param null $hour
	 *
	 * @return array
	 */
	public function get_subscriptions_by_date( $day = null, $month = null, $year = null, $hour = null, $include_taxes = false ) {

		$args = apply_filters( 'cs_get_subscriptions_by_date', array(
			'nopaging'    => true,
			'post_status' => array( 'cs_subscription', 'refunded' ),
			'year'        => $year,
			'monthnum'    => $month,
			'meta_key'    => 'subscription_id',
			'output'      => 'payments',
		), $day, $month, $year );

		if ( ! empty( $day ) ) {
			$args['day'] = $day;
		}

		if ( ! empty( $hour ) ) {
			$args['hour'] = $hour;
		}

		$subscriptions = cs_get_payments( $args );

		$return             = array();
		$return['earnings']       = 0;
		$return['refunded']       = 0;
		$return['count']          = 0;
		$return['refunded_count'] = 0;
		if ( $subscriptions ) {
			foreach ( $subscriptions as $renewal ) {

				$amount = cs_get_payment_amount( $renewal->ID );

				switch ( $renewal->status ) {

					case 'cs_subscription' :

						$tax    = 0;

						if( ! $include_taxes ) {
							$tax = cs_get_payment_tax( $renewal->ID );
						}

						$return['count']    += 1;
						$return['earnings'] += ( $amount - $tax );

						break;

					case 'refunded' :

						$return['refunded_count'] += 1;
						$return['refunded']       += $amount;

						break;


				}


			}
		}

		return $return;
	}


	/**
	 * Show subscriptions report
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function display_subscriptions_report() {

		if ( ! current_user_can( 'view_shop_reports' ) ) {
			wp_die( __( 'You do not have permission to view this data', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 401 ) );
		}

		// Retrieve the queried dates
		$dates = cs_get_report_dates();

		// Determine graph options
		switch ( $dates['range'] ) :
			case 'today' :
			case 'yesterday' :
			case 'last_30_days' :
				$day_by_day = true;
				break;
			case 'last_year' :
			case 'this_year' :
			case 'last_quarter' :
			case 'this_quarter' :
				$day_by_day = false;
				break;
			case 'other' :
				if ( $dates['m_end'] - $dates['m_start'] >= 2 || $dates['year_end'] > $dates['year'] && ( $dates['m_start'] != '12' && $dates['m_end'] != '1' ) ) {
					$day_by_day = false;
				} else {
					$day_by_day = true;
				}
				break;
			default:
				$day_by_day = true;
				break;
		endswitch;

		$earnings_totals      = 0.00; // Total earnings for time period shown
		$refunded_amount      = 0.00; // Total earnings refunded
		$subscriptions_totals = 0;    // Total sales for time period shown
		$refunded_count       = 0;    // Total renewals refunded

		//@TODO: Should taxes ever be included?

		$include_taxes      = empty( $_GET['exclude_taxes'] ) ? true : false;
		$earnings_data      = array();
		$refunds_data       = array();
		$subscription_count = array();
		$refunded_counter   = array();

		if ( $dates['range'] == 'today' || $dates['range'] == 'yesterday' ) {
			// Hour by hour
			$hour  = 1;
			$month = $dates['m_start'];
			while ( $hour <= 23 ) :

				$subscriptions = $this->get_subscriptions_by_date( $dates['day'], $month, $dates['year'], $hour, $include_taxes );

				$earnings_totals += $subscriptions['earnings'];
				$refunded_amount += $subscriptions['refunded'];
				$subscriptions_totals += $subscriptions['count'];
				$refunded_count += $subscriptions['refunded_count'];

				$date                 = mktime( $hour, 0, 0, $month, $dates['day'], $dates['year'] ) * 1000;
				$subscription_count[] = array( $date, $subscriptions['count'] );
				$refunded_counter[]   = array( $date, $subscriptions['refunded_count'] );
				$earnings_data[]      = array( $date, $subscriptions['earnings'] );
				$refunds_data[]       = array( $date, $subscriptions['refunded'] );

				$hour ++;
			endwhile;

		} elseif ( $dates['range'] == 'this_week' || $dates['range'] == 'last_week' ) {

			// Day by day
			$day     = $dates['day'];
			$day_end = $dates['day_end'];
			$month   = $dates['m_start'];
			while ( $day <= $day_end ) :

				$subscriptions = $this->get_subscriptions_by_date( $day, $month, $dates['year'], null, $include_taxes );

				$earnings_totals += $subscriptions['earnings'];
				$refunded_amount += $subscriptions['refunded'];
				$subscriptions_totals += $subscriptions['count'];
				$refunded_count += $subscriptions['refunded_count'];

				$date                 = mktime( 0, 0, 0, $month, $day, $dates['year'] ) * 1000;
				$subscription_count[] = array( $date, $subscriptions['count'] );
				$refunded_counter[]   = array( $date, $subscriptions['refunded_count'] );
				$earnings_data[]      = array( $date, $subscriptions['earnings'] );
				$refunds_data[]       = array( $date, $subscriptions['refunded'] );
				$day ++;
			endwhile;

		} else {

			$y = $dates['year'];

			while ( $y <= $dates['year_end'] ) :

				$last_year = false;

				if ( $dates['year'] == $dates['year_end'] ) {
					$month_start = $dates['m_start'];
					$month_end   = $dates['m_end'];
					$last_year   = true;
				} elseif ( $y == $dates['year'] ) {
					$month_start = $dates['m_start'];
					$month_end   = 12;
				} elseif ( $y == $dates['year_end'] ) {
					$month_start = 1;
					$month_end   = $dates['m_end'];
				} else {
					$month_start = 1;
					$month_end   = 12;
				}

				$i = $month_start;
				while ( $i <= $month_end ) :
					$d = $dates['day'];

					if ( $day_by_day ) :

						if ( $i == $month_end ) {

							$num_of_days = $dates['day_end'];
							if ( $month_start < $month_end ) {
								$d = 1;
							}

						} else {

							$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );

						}

						while ( $d <= $num_of_days ) :

							$subscriptions = $this->get_subscriptions_by_date( $d, $i, $y, null, $include_taxes );

							$earnings_totals += $subscriptions['earnings'];
							$refunded_amount += $subscriptions['refunded'];
							$subscriptions_totals += $subscriptions['count'];
							$refunded_count += $subscriptions['refunded_count'];

							$date                 = mktime( 0, 0, 0, $i, $d, $y ) * 1000;
							$subscription_count[] = array( $date, $subscriptions['count'] );
							$refunded_counter[]   = array( $date, $subscriptions['refunded_count'] );
							$earnings_data[]      = array( $date, $subscriptions['earnings'] );
							$refunds_data[]       = array( $date, $subscriptions['refunded'] );
							$d ++;

						endwhile;

					else :

						$subscriptions = $this->get_subscriptions_by_date( null, $i, $y, null, $include_taxes );

						$earnings_totals += $subscriptions['earnings'];
						$refunded_amount += $subscriptions['refunded'];
						$subscriptions_totals += $subscriptions['count'];
						$refunded_count += $subscriptions['refunded_count'];

						if ( $i == $month_end && $last_year ) {

							$num_of_days = cal_days_in_month( CAL_GREGORIAN, $i, $y );

						} else {

							$num_of_days = 1;

						}

						$date                 = mktime( 0, 0, 0, $i, $num_of_days, $y ) * 1000;
						$subscription_count[] = array( $date, $subscriptions['count'] );
						$refunded_counter[]   = array( $date, $subscriptions['refunded_count'] );
						$earnings_data[]      = array( $date, $subscriptions['earnings'] );
						$refunds_data[]       = array( $date, $subscriptions['refunded'] );

					endif;

					$i ++;

				endwhile;

				$y ++;
			endwhile;

		}

		$data = array(
			__( 'Renewals', 'cs-recurring' )          => $subscription_count,
			__( 'Refunds', 'cs-recurring' )           => $refunded_counter,
			__( 'Earnings', 'cs-recurring' )          => $earnings_data,
			__( 'Refunded Earnings', 'cs-recurring' ) => $refunds_data,
		);

		$renewals_earnings_max = max( wp_list_pluck( $earnings_data, 1 ) );
		$refunds_earnings_max  = max( wp_list_pluck( $refunds_data, 1 ) );
		$earnings_max          = max( $renewals_earnings_max, $refunds_earnings_max );

		$renewals_max = max( wp_list_pluck( $subscription_count, 1 ) );
		$refunds_max  = max( wp_list_pluck( $refunded_counter, 1 ) );
		$sales_max    = max( $renewals_max, $refunds_max ) + 1;

		ob_start();
		?>
		<div class="tablenav top">
			<div class="alignleft actions"><?php cs_report_views(); ?></div>
		</div>
		<?php do_action( 'cs_subscription_reports_graph_before' ); ?>
		<div class="metabox-holder">
			<div class="postbox">
				<h3><span><?php _e( 'Subscription Renewals', 'cs-recurring' ); ?></span></h3>

				<div class="inside">
					<?php
					cs_reports_graph_controls();
					$graph = new CS_Graph( $data );
					$graph->set( 'x_mode', 'time' );
					$graph->set( 'multiple_y_axes', true );
					$additional_options = array(
						0 => array(
							'max' => $sales_max,
						),
						1 => array(
							'max' => $sales_max,
							'show' => false,
						),
						2 => array(
							'max' => $earnings_max,
						),
						3 => array(
							'max' => $earnings_max,
							'show' => false,
						),
					);
					$graph->set( 'additional_options', 'yaxes: ' . json_encode( $additional_options ) );
					$graph->display();
					?>

					<p class="cs_graph_totals">
						<strong>
							<?php
							_e( 'Gross earnings for period shown: ', 'commercestore' );
							echo cs_currency_filter( cs_format_amount( $earnings_totals  + $refunded_amount) );
							?>
						</strong>
					</p>

					<p class="cs_graph_totals">
						<strong>
							<?php
							_e( 'Refunded earnings for period shown: ', 'commercestore' );
							echo cs_currency_filter( cs_format_amount( $refunded_amount ) );
							?>
						</strong>
					</p>

					<p class="cs_graph_totals">
						<strong>
							<?php
							_e( 'NET earnings for period shown: ', 'commercestore' );
							echo cs_currency_filter( cs_format_amount( $earnings_totals ) );
							?>
						</strong>
					</p>

					<p class="cs_graph_totals">
						<strong><?php _e( 'Total renewals for period shown: ', 'cs-recurring' ); echo cs_format_amount( $subscriptions_totals, false ); ?></strong>
					</p>

					<p class="cs_graph_totals">
						<strong>
							<?php
							_e( 'Total renewals refunded for period shown: ', 'commercestore' );
							echo cs_format_amount( $refunded_count, false );
							?>
						</strong>
					</p>

					<?php do_action( 'cs_subscription_reports_graph_additional_stats' ); ?>

					<p class="cs_graph_notes">
						<?php if ( false === $include_taxes ) : ?>
							<em><sup>&dagger;</sup> <?php _e( 'Excludes sales tax.', 'commercestore' ); ?></em>
						<?php endif; ?>
					</p>


				</div>
			</div>
		</div>
		<?php do_action( 'cs_subscription_reports_graph_after' ); ?>

		<?php
		// get output buffer contents and end our own buffer
		$output = ob_get_contents();
		ob_end_clean();

		echo $output;

	}


	/**
	 * Show subscription payment statuses in Payment History
	 *
	 * @since  2.2
	 * @return void
	 */
	public function status_column( $value, $payment_id, $column_name ) {

		// This is handled automatically in CommerceStore 3.0.
		if ( function_exists( 'cs_get_orders' ) || 'status' !== $column_name ) {
			return $value;
		}

		if ( 'cs_subscription' === cs_get_payment_status( $payment_id ) ) {
			return __( 'Renewal', 'cs-recurring' );
		}

		return $value;
	}

}
new CS_Recurring_Reports();
