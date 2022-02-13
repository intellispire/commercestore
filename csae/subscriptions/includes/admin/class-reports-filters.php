<?php

/**
 * Class EDD_Recurring_Reports
 *
 * @since x.x.x
 *
 */
class EDD_Recurring_Reports_Filters {
	/** Singleton *************************************************************/

	/**
	 * @var EDD_Recurring_Reports_Filters
	 * @since x.x.x
	 */
	private static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof EDD_Recurring_Reports_Filters ) ) {
			self::$instance = new EDD_Recurring_Reports_Filters;
			self::$instance->setup_filters();
		}

		return self::$instance;
	}

	private function setup_filters() {
		add_filter( 'edd_export_earnings_completed_statuses', array( $this, 'add_completed_status' ), 10, 1 );
		add_filter( 'edd_export_get_data_earnings_report', array( $this, 'add_subscription_earnings_data' ), 10, 3 );
	}

	public function add_completed_status( $statuses ) {
		$statuses[] = 'edd_subscription';

		return $statuses;
	}

	public function add_subscription_earnings_data( $data, $start_date, $end_date ) {
		global $wpdb;

		if ( function_exists( 'edd_get_order' ) ) {
			$sql = $wpdb->prepare(
				"SELECT SUM(edd_o.total) AS total, COUNT(DISTINCT edd_o.id) AS count
				FROM {$wpdb->edd_orders} edd_o
				WHERE edd_o.type = 'sale'
				AND edd_o.status = 'edd_subscription'
				AND edd_o.date_created >= %s AND edd_o.date_created <= %s",
				$start_date,
				$end_date
			);
		} else {
			$sql = $wpdb->prepare(
				"SELECT SUM(meta_value) AS total, COUNT(DISTINCT posts.ID) AS count
				FROM {$wpdb->posts} AS posts
				INNER JOIN {$wpdb->postmeta} ON posts.ID = {$wpdb->postmeta}.post_ID
				WHERE posts.post_type IN ('edd_payment')
				AND post_status = %s
				AND {$wpdb->postmeta}.meta_key = '_edd_payment_total'
				AND posts.post_date >= %s
				AND posts.post_date < %s
				ORDER by posts.post_date ASC",
				'edd_subscription',
				$start_date,
				$end_date
			);
		}

		$totals = $wpdb->get_results( $sql, ARRAY_A );

		$data['edd_subscription'] = array(
			'count'  => absint( $totals[0]['count'] ),
			'amount' => floatval( $totals[0]['total'] ),
		);

		return $data;
	}

}
EDD_Recurring_Reports_Filters::instance();
