<?php
/**
 * Reports API - Functions
 *
 * @package     CS
 * @subpackage  Reports
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */
namespace CS\Reports;

//
// Endpoint and report helpers.
//

/**
 * Registers a new endpoint to the master registry.
 *
 * @since 3.0
 *
 * @see \CS\Reports\Data\Endpoint_Registry::register_endpoint()
 *
 * @param string $endpoint_id Reports data endpoint ID.
 * @param array  $attributes  {
 *     Endpoint attributes. All arguments are required unless otherwise noted.
 *
 *     @type string $label    Endpoint label.
 *     @type int    $priority Optional. Priority by which to retrieve the endpoint. Default 10.
 *     @type array  $views {
 *         Array of view handlers by type.
 *
 *         @type array $view_type {
 *             View type slug, with array beneath it.
 *
 *             @type callable $data_callback    Callback used to retrieve data for the view.
 *             @type callable $display_callback Callback used to render the view.
 *             @type array    $display_args     Optional. Array of arguments to pass to the
 *                                              display_callback (if any). Default empty array.
 *         }
 *     }
 * }
 * @return bool True if the endpoint was successfully registered, otherwise false.
 */
function register_endpoint( $endpoint_id, $attributes ) {

	/** @var Data\Endpoint_Registry|\WP_Error $registry */
	$registry = CS()->utils->get_registry( 'reports:endpoints' );

	if ( empty( $registry ) || is_wp_error( $registry ) ) {
		return false;
	}

	try {
		$added = $registry->register_endpoint( $endpoint_id, $attributes );

	} catch ( \CS_Exception $exception ) {
		cs_debug_log_exception( $exception );

		$added = false;
	}

	return $added;
}

/**
 * Retrieves and builds an endpoint object.
 *
 * @since 3.0
 *
 * @see \CS\Reports\Data\Endpoint_Registry::build_endpoint()
 *
 * @param string $endpoint_id Endpoint ID.
 * @param string $view_type   View type to use when building the object.
 * @return Data\Endpoint|\WP_Error Endpoint object on success, otherwise a WP_Error object.
 */
function get_endpoint( $endpoint_id, $view_type ) {

	/** @var Data\Endpoint_Registry|\WP_Error $registry */
	$registry = CS()->utils->get_registry( 'reports:endpoints' );

	if ( empty( $registry ) || is_wp_error( $registry ) ) {
		return $registry;
	}

	return $registry->build_endpoint( $endpoint_id, $view_type );
}

/**
 * Registers a new report.
 *
 * @since 3.0
 *
 * @see \CS\Reports\Data\Report_Registry::add_report()
 *
 * @param string $report_id   Report ID.
 * @param array  $attributes {
 *     Reports attributes. All arguments are required unless otherwise noted.
 *
 *     @type string $label     Report label.
 *     @type int    $priority  Optional. Priority by which to register the report. Default 10.
 *     @type array  $filters   Filters available to the report.
 *     @type array  $endpoints Endpoints to associate with the report.
 * }
 * @return bool True if the report was successfully registered, otherwise false.
 */
function add_report( $report_id, $attributes ) {

	/** @var Data\Report_Registry|\WP_Error $registry */
	$registry = CS()->utils->get_registry( 'reports' );

	if ( empty( $registry ) || is_wp_error( $registry ) ) {
		return false;
	}

	try {
		$added = $registry->add_report( $report_id, $attributes );

	} catch ( \CS_Exception $exception ) {
		cs_debug_log_exception( $exception );

		$added = false;
	}

	return $added;
}

/**
 * Retrieves and builds a report object.
 *
 * @since 3.0
 *
 * @see \CS\Reports\Data\Report_Registry::build_report()
 *
 * @param string $report_id       Report ID.
 * @param bool   $build_endpoints Optional. Whether to build the endpoints (includes registering
 *                                any endpoint dependencies, such as registering meta boxes).
 *                                Default true.
 * @return Data\Report|\WP_Error Report object on success, otherwise a WP_Error object.
 */
function get_report( $report_id = false, $build_endpoints = true ) {

	/** @var Data\Report_Registry|\WP_Error $registry */
	$registry = CS()->utils->get_registry( 'reports' );

	if ( empty( $registry ) || is_wp_error( $registry ) ) {
		return $registry;
	}

	return $registry->build_report( $report_id, $build_endpoints );
}

/** Sections ******************************************************************/

/**
 * Retrieves the list of slug/label report pairs.
 *
 * @since 3.0
 *
 * @return array List of reports, otherwise an empty array.
 */
function get_reports() {

	/** @var Data\Report_Registry|\WP_Error $registry */
	$registry = CS()->utils->get_registry( 'reports' );

	if ( empty( $registry ) || is_wp_error( $registry ) ) {
		return array();
	} else {
		$reports = $registry->get_reports( 'priority', 'core' );
	}

	// Re-sort by priority.
	uasort( $reports, array( $registry, 'priority_sort' ) );

	/**
	 * Filters the list of report slug/label pairs.
	 *
	 * @since 3.0
	 *
	 * @param array $reports List of slug/label pairs as representative of reports.
	 */
	return apply_filters( 'cs_get_reports', $reports );
}

/**
 * Retrieves the slug for the active report.
 *
 * @since 3.0
 *
 * @return string The active report, or the 'overview' report if no view defined
 */
function get_current_report() {
	return isset( $_REQUEST['view'] )
		? sanitize_key( $_REQUEST['view'] )
		: 'overview'; // Hardcoded default
}

/** Endpoints *****************************************************************/

/**
 * Retrieves the list of supported endpoint view types and their attributes.
 *
 * @since 3.0
 *
 * @return array List of supported endpoint types.
 */
function get_endpoint_views() {
	if ( ! did_action( 'cs_reports_init' ) ) {
		_doing_it_wrong( __FUNCTION__, 'Endpoint views cannot be retrieved prior to the firing of the cs_reports_init hook.', 'CS 3.0' );

		return array();
	}

	/** @var Data\Endpoint_View_Registry|\WP_Error $registry */
	$registry = CS()->utils->get_registry( 'reports:endpoints:views' );

	if ( empty( $registry ) || is_wp_error( $registry ) ) {
		return array();
	} else {
		$views = $registry->get_endpoint_views();
	}

	return $views;
}

/**
 * Retrieves the name of the handler class for a given endpoint view.
 *
 * @since 3.0
 *
 * @param string $view Endpoint view.
 * @return string Handler class name if set and the view exists, otherwise an empty string.
 */
function get_endpoint_handler( $view ) {
	$views = get_endpoint_views();

	return isset( $views[ $view ]['handler'] )
		? $views[ $view ]['handler']
		: '';
}

/**
 * Retrieves the group display callback for a given endpoint view.
 *
 * @since 3.0
 *
 * @param string $view Endpoint view.
 * @return string Group callback if set, otherwise an empty string.
 */
function get_endpoint_group_callback( $view ) {
	$views = get_endpoint_views();

	return isset( $views[ $view ]['group_callback'] )
		? $views[ $view ]['group_callback']
		: '';
}

/**
 * Determines whether an endpoint view is valid.
 *
 * @since 3.0
 *
 * @param string $view Endpoint view slug.
 * @return bool True if the view is valid, otherwise false.
 */
function validate_endpoint_view( $view ) {
	return array_key_exists( $view, get_endpoint_views() );
}

/**
 * Parses views for an incoming endpoint.
 *
 * @since 3.0
 *
 * @see get_endpoint_views()
 *
 * @param array  $views View slugs and attributes as dictated by get_endpoint_views().
 *
 * @return array (Maybe) adjusted views slugs and attributes array.
 */
function parse_endpoint_views( $views ) {
	$valid_views = get_endpoint_views();

	foreach ( $views as $view => $attributes ) {
		if ( ! empty( $valid_views[ $view ]['fields'] ) ) {
			$fields = $valid_views[ $view ]['fields'];

			// Merge the incoming args with the field defaults.
			$view_args = wp_parse_args( $attributes, $fields );

			// Overwrite the view attributes, keeping only the valid fields.
			$views[ $view ] = array_intersect_key( $view_args, $fields );

			if ( $views[ $view ]['display_callback'] === $fields['display_callback'] ) {
				$views[ $view ]['display_args'] = wp_parse_args( $views[ $view ]['display_args'], $fields['display_args'] );
			}
		}
	}

	return $views;
}

/** Filters *******************************************************************/

/**
 * Retrieves the list of registered reports filters and their attributes.
 *
 * @since 3.0
 *
 * @return array List of supported endpoint filters.
 */
function get_filters() {
	$filters = array(
		'dates'              => array(
			'label'            => __( 'Date', 'commercestore' ),
			'display_callback' => __NAMESPACE__ . '\\display_dates_filter'
		),
		'products'           => array(
			'label'            => __( 'Products', 'commercestore' ),
			'display_callback' => __NAMESPACE__ . '\\display_products_filter'
		),
		'product_categories' => array(
			'label'            => __( 'Product Categories', 'commercestore' ),
			'display_callback' => __NAMESPACE__ . '\\display_product_categories_filter'
		),
		'taxes'              => array(
			'label'            => __( 'Exclude Taxes', 'commercestore' ),
			'display_callback' => __NAMESPACE__ . '\\display_taxes_filter'
		),
		'gateways'           => array(
			'label'            => __( 'Gateways', 'commercestore' ),
			'display_callback' => __NAMESPACE__ . '\\display_gateways_filter'
		),
		'discounts'          => array(
			'label'            => __( 'Discounts', 'commercestore' ),
			'display_callback' => __NAMESPACE__ . '\\display_discounts_filter'
		),
		'regions'            => array(
			'label'            => __( 'Regions', 'commercestore' ),
			'display_callback' => __NAMESPACE__ . '\\display_region_filter'
		),
		'countries'          => array(
			'label'            => __( 'Countries', 'commercestore' ),
			'display_callback' => __NAMESPACE__ . '\\display_country_filter'
		),
		'currencies'          => array(
			'label'            => __( 'Currencies', 'commercestore' ),
			'display_callback' => __NAMESPACE__ . '\\display_currency_filter'
		)
	);

	/**
	 * Filters the list of available report filters.
	 *
	 * @since 3.0
	 *
	 * @param array[] $filters
	 */
	return apply_filters( 'cs_report_filters', $filters );
}

/**
 * Determines whether the given filter is valid.
 *
 * @since 3.0
 *
 * @param string $filter Filter key.
 * @return bool True if the filter is valid, otherwise false.
 */
function validate_filter( $filter ) {
	return array_key_exists( $filter, get_filters() );
}

/**
 * Retrieves the value of an endpoint filter for the current session and report.
 *
 * @since 3.0
 *
 * @param string $filter Filter key to retrieve the value for.
 * @return mixed|string Value of the filter if it exists, otherwise an empty string.
 */
function get_filter_value( $filter ) {
	$value = '';

	// Bail if filter does not validate
	if ( ! validate_filter( $filter ) ) {
		return $value;
	}

	// Look for filter in transients
	$filter_key   = get_filter_key( $filter );
	$filter_value = get_transient( $filter_key );

	// Maybe use transient value
	if ( false !== $filter_value ) {
		$value = $filter_value;

		// Maybe use dates defaults
	} elseif ( 'dates' === $filter ) {

		// Default to last 30 days for filter value.
		$default = 'last_30_days';
		$dates   = parse_dates_for_range( $default );
		$value   = array(
			'from'  => $dates['start']->format( 'Y-m-d' ),
			'to'    => $dates['end']->format( 'Y-m-d' ),
			'range' => $default,
		);
	}

	return $value;
}

/**
 * Sets the value of a given report filter.
 *
 * The filter will only be set if the filter is valid.
 *
 * @since 3.0
 *
 * @param string $filter Filter name.
 * @param mixed  $value  Filter value.
 */
function set_filter_value( $filter, $value ) {
	if ( validate_filter( $filter ) ) {
		$filter_key = get_filter_key( $filter );

		set_transient( $filter_key, $value );
	}
}

/**
 * Builds the transient key used for a given reports filter.
 *
 * @since 3.0
 *
 * @param string $filter Filter key to retrieve the value for.
 * @return string Transient key for the filter.
 */
function get_filter_key( $filter ) {
	$site = get_current_blog_id();
	$user = get_current_user_id();

	return "reports:filter-{$filter}:site-{$site}:user-{$user}";
}

/**
 * Clears the value of a filter.
 *
 * @since 3.0
 *
 * @param string $filter Filter key to clear.
 * @return bool true if successful, false otherwise.
 */
function clear_filter( $filter ) {
	return delete_transient( get_filter_key( $filter ) );
}

/**
 * Retrieves key/label pairs of date filter options for use in a drop-down.
 *
 * @since 3.0
 *
 * @return array Key/label pairs of date filter options.
 */
function get_dates_filter_options() {
	static $options = null;

	if ( is_null( $options ) ) {
		$options = array(
			'other'        => __( 'Custom', 'commercestore' ),
			'today'        => __( 'Today', 'commercestore' ),
			'yesterday'    => __( 'Yesterday', 'commercestore' ),
			'this_week'    => __( 'This Week', 'commercestore' ),
			'last_week'    => __( 'Last Week', 'commercestore' ),
			'last_30_days' => __( 'Last 30 Days', 'commercestore' ),
			'this_month'   => __( 'This Month', 'commercestore' ),
			'last_month'   => __( 'Last Month', 'commercestore' ),
			'this_quarter' => __( 'This Quarter', 'commercestore' ),
			'last_quarter' => __( 'Last Quarter', 'commercestore' ),
			'this_year'    => __( 'This Year', 'commercestore' ),
			'last_year'    => __( 'Last Year', 'commercestore' ),
		);
	}

	/**
	 * Filters the list of key/label pairs of date filter options.
	 *
	 * @since 1.3
	 *
	 * @param array $date_options Date filter options.
	 */
	return apply_filters( 'cs_report_date_options', $options );
}

/**
 * Retrieves the start and end date filters for use with the Reports API.
 *
 * @since 3.0
 *
 * @param string $values   Optional. What format to retrieve dates in the resulting array in.
 *                         Accepts 'strings' or 'objects'. Default 'strings'.
 * @param string $timezone Optional. Timezone to force for filter dates. Primarily used for
 *                         legacy testing purposes. Default empty.
 * @return array|\CS\Utils\Date[] {
 *     Query date range for the current graph filter request.
 *
 *     @type string|\CS\Utils\Date $start Start day and time (based on the beginning of the given day).
 *                                         If `$values` is 'objects', a Carbon object, otherwise a date
 *                                         time string.
 *     @type string|\CS\Utils\Date $end   End day and time (based on the end of the given day). If `$values`
 *                                         is 'objects', a Carbon object, otherwise a date time string.
 * }
 */
function get_dates_filter( $values = 'strings', $timezone = null ) {
	$dates = parse_dates_for_range();

	if ( 'strings' === $values ) {
		if ( ! empty( $dates['start'] ) ) {
			$dates['start'] = $dates['start']->toDateTimeString();
		}
		if ( ! empty( $dates['end'] ) ) {
			$dates['end'] = $dates['end']->toDateTimeString();
		}
	}

	/**
	 * Filters the start and end date filters for use with the Graphs API.
	 *
	 * @since 3.0
	 *
	 * @param array|\CS\Utils\Date[] $dates {
	 *     Query date range for the current graph filter request.
	 *
	 *     @type string|\CS\Utils\Date $start Start day and time (based on the beginning of the given day).
	 *                                         If `$values` is 'objects', a Date object, otherwise a date
	 *                                         time string.
	 *     @type string|\CS\Utils\Date $end   End day and time (based on the end of the given day). If `$values`
	 *                                         is 'objects', a Date object, otherwise a date time string.
	 * }
	 */
	return apply_filters( 'cs_get_dates_filter', $dates );
}

/**
 * Parses start and end dates for the given range.
 *
 * @since 3.0
 *
 * @param string          $range Optional. Range value to generate start and end dates for against `$date`.
 *                               Default is the current range as derived from the session.
 * @param string          $date  Date string converted to `\CS\Utils\Date` to anchor calculations to.
 * @return \CS\Utils\Date[] Array of start and end date objects.
 */
function parse_dates_for_range( $range = null, $date = 'now' ) {

	// Set the time ranges in the user's timezone, so they ultimately see them in their own timezone.
	$date = CS()->utils->date( $date, cs_get_timezone_id(), false );

	if ( null === $range || ! array_key_exists( $range, get_dates_filter_options() ) ) {
		$range = get_dates_filter_range();
	}

	switch ( $range ) {

		case 'this_month':
			$dates = array(
				'start' => $date->copy()->startOfMonth(),
				'end'   => $date->copy()->endOfMonth(),
			);
			break;

		case 'last_month':
			$dates = array(
				'start' => $date->copy()->subMonthNoOverflow( 1 )->startOfMonth(),
				'end'   => $date->copy()->subMonthNoOverflow( 1 )->endOfMonth(),
			);
			break;

		case 'today':
			$dates = array(
				'start' => $date->copy()->startOfDay(),
				'end'   => $date->copy()->endOfDay(),
			);
			break;

		case 'yesterday':
			$dates = array(
				'start' => $date->copy()->subDay( 1 )->startOfDay(),
				'end'   => $date->copy()->subDay( 1 )->endOfDay(),
			);
			break;

		case 'this_week':
			$dates = array(
				'start' => $date->copy()->startOfWeek(),
				'end'   => $date->copy()->endOfWeek(),
			);
			break;

		case 'last_week':
			$dates = array(
				'start' => $date->copy()->subWeek( 1 )->startOfWeek(),
				'end'   => $date->copy()->subWeek( 1 )->endOfWeek(),
			);
			break;

		case 'last_30_days':
			$dates = array(
				'start' => $date->copy()->subDay( 30 )->startOfDay(),
				'end'   => $date->copy()->endOfDay(),
			);
			break;

		case 'this_quarter':
			$dates = array(
				'start' => $date->copy()->startOfQuarter(),
				'end'   => $date->copy()->endOfQuarter(),
			);
			break;

		case 'last_quarter':
			$dates = array(
				'start' => $date->copy()->subQuarter( 1 )->startOfQuarter(),
				'end'   => $date->copy()->subQuarter( 1 )->endOfQuarter(),
			);
			break;

		case 'this_year':
			$dates = array(
				'start' => $date->copy()->startOfYear(),
				'end'   => $date->copy()->endOfYear(),
			);
			break;

		case 'last_year':
			$dates = array(
				'start' => $date->copy()->subYear( 1 )->startOfYear(),
				'end'   => $date->copy()->subYear( 1 )->endOfYear(),
			);
			break;

		case 'other':
		default:
			$dates_from_report = get_filter_value( 'dates' );

			if ( ! empty( $dates_from_report ) ) {
				$start = $dates_from_report['from'];
				$end   = $dates_from_report['to'];
			} else {
				$start = $end = 'now';
			}

			$dates = array(
				'start' => CS()->utils->date( $start, cs_get_timezone_id(), false )->startOfDay(),
				'end'   => CS()->utils->date( $end, cs_get_timezone_id(), false )->endOfDay(),
			);
			break;
	}

	// Convert the values to the UTC equivalent so that we can query the database using UTC.
	$dates['start'] = cs_get_utc_equivalent_date( $dates['start'] );
	$dates['end']   = cs_get_utc_equivalent_date( $dates['end'] );

	$dates['range'] = $range;

	return $dates;
}

/**
 * Retrieves the date filter range.
 *
 * @since 3.0
 *
 * @return string Date filter range.
 */
function get_dates_filter_range() {

	$dates = get_filter_value( 'dates' );

	if ( isset( $dates['range'] ) ) {
		$range = sanitize_key( $dates['range'] );

	} else {

		/**
		 * Filters the report dates default range.
		 *
		 * @since 1.3
		 *
		 * @param string $range Date range as derived from the session. Default 'last_30_days'
		 * @param array  $dates Dates filter data array.
		 */
		$range = apply_filters( 'cs_get_report_dates_default_range', 'last_30_days', $dates );
	}

	/**
	 * Filters the dates filter range.
	 *
	 * @since 3.0
	 *
	 * @param string $range Dates filter range.
	 * @param array  $dates Dates filter data array.
	 */
	return apply_filters( 'cs_get_dates_filter_range', $range, $dates );
}

/**
 * Determines whether results should be displayed hour by hour, or not.
 *
 * @since 3.0
 *
 * @return bool True if results should use hour by hour, otherwise false.
 */
function get_dates_filter_hour_by_hour() {
	// Retrieve the queried dates
	$dates = get_dates_filter( 'objects' );

	// Determine graph options
	switch ( $dates['range'] ) {
		case 'today':
		case 'yesterday':
			$hour_by_hour = true;
			break;
		default:
			$hour_by_hour = false;
			break;
	}

	return $hour_by_hour;
}

/**
 * Determines whether results should be displayed day by day or not.
 *
 * @since 3.0
 *
 * @return bool True if results should use day by day, otherwise false.
 */
function get_dates_filter_day_by_day() {
	// Retrieve the queried dates
	$dates = get_dates_filter( 'objects' );

	// Determine graph options
	switch ( $dates['range'] ) {
		case 'today':
		case 'yesterday':
		case 'last_quarter':
		case 'this_quarter':
		case 'this_year':
		case 'last_year':
			$day_by_day = false;
			break;
		case 'other':
			$difference = ( $dates['end']->getTimestamp() - $dates['start']->getTimestamp() );

			if ( $difference >= ( YEAR_IN_SECONDS / 4 ) ) {
				$day_by_day = false;
			} else {
				$day_by_day = true;
			}
			break;
		default:
			$day_by_day = true;
			break;
	}

	return $day_by_day;
}

/**
 * Retrieves the tax exclusion filter.
 *
 * @since 3.0
 *
 * @return bool True if taxes should be excluded from calculations.
 */
function get_taxes_excluded_filter() {
	$taxes = get_filter_value( 'taxes' );

	if ( ! isset( $taxes['exclude_taxes'] ) ) {
		return false;
	}

	return (bool) $taxes['exclude_taxes'];
}

/** Display *******************************************************************/

/**
 * Handles display of a report.
 *
 * @since 3.0
 *
 * @param Data\Report $report Report object.
 */
function default_display_report( $report ) {

	// Bail if erroneous report
	if ( empty( $report ) || is_wp_error( $report ) ) {
		return;
	}

	// Try to output: tiles, tables, and charts
	$report->display_endpoint_group( 'tiles'  );
	$report->display_endpoint_group( 'tables' );
	$report->display_endpoint_group( 'charts' );
}

/**
 * Displays the default content for a tile endpoint.
 *
 * @since 3.0
 *
 * @param Data\Report $report Report object the tile endpoint is being rendered in.
 *                            Not always set.
 * @param array       $tile   {
 *     Tile display arguments.
 *
 *     @type Data\Tile_Endpoint $endpoint     Endpoint object.
 *     @type mixed|array        $data         Date for display. By default, will be an array,
 *                                            but can be of other types.
 *     @type array              $display_args Array of any display arguments.
 * }
 * @return void Meta box display callbacks only echo output.
 */
function default_display_tile( $endpoint, $data, $args ) {
	echo '<span class="tile-label">' . esc_html( $endpoint->get_label() ) .'</span>';

	if ( empty( $data ) ) {
		echo '<span class="tile-no-data tile-value">&mdash;</span>';
	} else {
		switch ( $args['type'] ) {
			case 'number':
				echo '<span class="tile-number tile-value">' . cs_format_amount( $data ) . '</span>';
				break;

			case 'split-number':
				printf( '<span class="tile-amount tile-value">%1$d / %2$d</span>',
					cs_format_amount( $data['first_value'] ),
					cs_format_amount( $data['second_value'] )
				);
				break;

			case 'split-amount':
				printf( '<span class="tile-amount tile-value">%1$d / %2$d</span>',
					cs_currency_filter( cs_format_amount( $data['first_value'] ) ),
					cs_currency_filter( cs_format_amount( $data['second_value'] ) )
				);
				break;

			case 'relative':
				$direction = ( ! empty( $data['direction'] ) && in_array( $data['direction'], array( 'up', 'down' ), true ) )
					? '-' . sanitize_key( $data['direction'] )
					: '';
				echo '<span class="tile-change' . esc_attr( $direction ) . ' tile-value">' . cs_format_amount( $data['value'] ) . '</span>';
				break;

			case 'amount':
				echo '<span class="tile-amount tile-value">' . cs_currency_filter( cs_format_amount( $data ) ) . '</span>';
				break;

			case 'url':
				echo '<span class="tile-url tile-value">' . esc_url( $data ) . '</span>';
				break;

			default:
				$tags = wp_kses_allowed_html( 'post' );
				echo '<span class="tile-value tile-default">' . wp_kses( $data, $tags ) . '</span>';
				break;
		}
	}

	if ( ! empty( $args['comparison_label'] ) ) {
		echo '<span class="tile-compare">' . esc_attr( $args['comparison_label'] ) . '</span>';
	}
}

/**
 * Handles default display of all tile endpoints registered against a report.
 *
 * @since 3.0
 *
 * @param Data\Report $report Report object.
 */
function default_display_tiles_group( $report ) {
	if ( ! $report->has_endpoints( 'tiles' ) ) {
		return;
	}

	$tiles = $report->get_endpoints( 'tiles' );
?>

	<div id="cs-reports-tiles-wrap" class="cs-report-wrap">
		<?php
		foreach ( $tiles as $endpoint_id => $tile ) :
			$tile->display();
		endforeach;
		?>
	</div>

	<?php
}

/**
 * Handles default display of all table endpoints registered against a report.
 *
 * @since 3.0
 *
 * @param Data\Report $report Report object.
 */
function default_display_tables_group( $report ) {
	if ( ! $report->has_endpoints( 'tables' ) ) {
		return;
	}

	$tables = $report->get_endpoints( 'tables' ); ?>

	<div id="cs-reports-tables-wrap" class="cs-report-wrap"><?php

		foreach ( $tables as $endpoint_id => $table ) :

			?><div class="cs-reports-table" id="cs-reports-table-<?php echo esc_attr( $endpoint_id ); ?>">
				<h3><?php echo esc_html( $table->get_label() ); ?></h3><?php

				$table->display();

			?></div><?php

		endforeach;

	?><div class="clear"></div></div><?php
}

/**
 * Handles default display of all chart endpoints registered against a report.
 *
 * @since 3.0
 *
 * @param Data\Report $report Report object.
 */
function default_display_charts_group( $report ) {
	if ( ! $report->has_endpoints( 'charts' ) ) {
		return;
	}

	?>
	<div id="cs-reports-charts-wrap" class="cs-report-wrap">
	<?php

	$charts = $report->get_endpoints( 'charts' );

	foreach ( $charts as $endpoint_id => $chart ) {
		?>
		<div class="cs-reports-chart cs-reports-chart-<?php echo esc_attr( $chart->get_type() ); ?>" id="cs-reports-table-<?php echo esc_attr( $endpoint_id ); ?>">
			<h3><?php echo esc_html( $chart->get_label() ); ?></h3>

			<?php $chart->display(); ?>
		</div>
		<?php
	}
	?>
	</div>
	<?php
}

/**
 * Handles display of the 'Date' filter for reports.
 *
 * @since 3.0
 */
function display_dates_filter() {
	$options = get_dates_filter_options();
	$dates   = get_filter_value( 'dates' );
	$range   = isset( $dates['range'] )
		? $dates['range']
		: get_dates_filter_range();

	$class = ( 'other' !== $range )
		? ' screen-reader-text'
		: '';

	$range = CS()->html->select( array(
		'name'             => 'range',
		'class'            => 'cs-graphs-date-options',
		'options'          => $options,
		'variations'       => false,
		'show_option_all'  => false,
		'show_option_none' => false,
		'selected'         => $range
	) );

	// From.
	$from = CS()->html->date_field( array(
		'id'          => 'filter_from',
		'name'        => 'filter_from',
		'value'       => ( empty( $dates['from'] ) || ( 'other' !== $dates['range'] ) ) ? '' : $dates['from'],
		'placeholder' => _x( 'From', 'date filter', 'commercestore' ),
	) );

	// To.
	$to = CS()->html->date_field( array(
		'id'          => 'filter_to',
		'name'        => 'filter_to',
		'value'       => ( empty( $dates['to'] ) || ( 'other' !== $dates['range'] ) ) ? '' : $dates['to'],
		'placeholder' => _x( 'To', 'date filter', 'commercestore' ),
	) );

	// Output fields
	?><span class="cs-date-range-picker graph-option-section"><?php
		echo $range;
	?></span>

	<span class="cs-date-range-options graph-option-section cs-from-to-wrapper<?php echo esc_attr( $class ); ?>">
		<?php echo $from . $to; ?>
	</span><?php
}

/**
 * Handles display of the 'Products' filter for reports.
 *
 * @since 3.0
 */
function display_products_filter() {
	$products = get_filter_value( 'products' );

	$select   = CS()->html->product_dropdown( array(
		'chosen'           => true,
		'variations'       => true,
		'selected'         => empty( $products ) ? 0 : $products,
		'show_option_none' => false,
		'show_option_all'  => sprintf( __( 'All %s', 'commercestore' ), cs_get_label_plural() ),
	) ); ?>

	<span class="cs-graph-filter-options graph-option-section"><?php
		echo $select;
	?></span><?php
}

/**
 * Handles display of the 'Products Dropdown' filter for reports.
 *
 * @since 3.0
 */
function display_product_categories_filter() {
	?>
	<span class="cs-graph-filter-options graph-option-selection">
		<?php echo CS()->html->category_dropdown( 'product_categories', get_filter_value( 'product_categories' ) ); ?>
	</span>
	<?php
}

/**
 * Handles display of the 'Exclude Taxes' filter for reports.
 *
 * @since 3.0
 */
function display_taxes_filter() {
	if ( false === cs_use_taxes() ) {
		return;
	}

	$taxes         = get_filter_value( 'taxes' );
	$exclude_taxes = isset( $taxes['exclude_taxes'] ) && true == $taxes['exclude_taxes'];
?>
	<span class="cs-graph-filter-options graph-option-section">
		<label for="exclude_taxes">
			<input type="checkbox" id="exclude_taxes" <?php checked( true, $exclude_taxes, true ); ?> value="1" name="exclude_taxes"/>
			<?php esc_html_e( 'Exclude Taxes', 'commercestore' ); ?>
		</label>
	</span>
<?php
}

/**
 * Handles display of the 'Discounts' filter for reports.
 *
 * @since 3.0
 */
function display_discounts_filter() {
	$discount = get_filter_value( 'discounts' );

	$d = cs_get_discounts( array(
		'fields' => array( 'code', 'name' ),
		'number' => 100,
	) );

	$discounts = array();

	foreach ( $d as $discount_data ) {
		$discounts[ $discount_data->code ] = esc_html( $discount_data->name );
	}

	// Get the select
	$select = CS()->html->discount_dropdown( array(
		'name'     => 'discounts',
		'chosen'   => true,
		'selected' => empty( $discount ) ? 0 : $discount,
	) ); ?>

    <span class="cs-graph-filter-options graph-option-section"><?php
		echo $select;
	?></span><?php
}

/**
 * Handles display of the 'Gateways' filter for reports.
 *
 * @since 3.0
 */
function display_gateways_filter() {
	$gateway = get_filter_value( 'gateways' );

	$known_gateways = cs_get_payment_gateways();

	$gateways = array();

	foreach ( $known_gateways as $id => $data ) {
		$gateways[ $id ] = esc_html( $data['admin_label'] );
	}

	// Get the select
	$select = CS()->html->select( array(
		'name'             => 'gateways',
		'options'          => $gateways,
		'selected'         => empty( $gateway ) ? 0 : $gateway,
		'show_option_none' => false,
	) ); ?>

    <span class="cs-graph-filter-options graph-option-section"><?php
		echo $select;
	?></span><?php
}

/**
 * Handles display of the 'Country' filter for reports.
 *
 * @since 3.0
 */
function display_region_filter() {
	$region  = get_filter_value( 'regions' );
	$country = get_filter_value( 'countries' );

	if ( empty( $region ) ) {
		$region = '';
	}
	if ( empty( $country ) ) {
		$country = '';
	}

	$regions = cs_get_shop_states( $country );

	// Remove empty values.
	$regions = array_filter( $regions );

	// Get the select
	$select = CS()->html->region_select(
		array(
			'name'    => 'regions',
			'id'      => 'cs_reports_filter_regions',
			'options' => $regions,
		),
		$country,
		$region
	);
	?>

	<span class="cs-graph-filter-options graph-option-section"><?php
	echo $select;
	?></span><?php
}

/**
 * Handles display of the 'Country' filter for reports.
 *
 * @since 3.0
 */
function display_country_filter() {
	$country = get_filter_value( 'countries' );
	if ( empty( $country ) ) {
		$country = '';
	}

	$countries = cs_get_country_list();

	// Remove empty values.
	$countries = array_filter( $countries );

	// Get the select
	$select = CS()->html->country_select(
		array(
			'name'    => 'countries',
			'id'      => 'cs_reports_filter_countries',
			'options' => $countries,
		),
		$country
	);
	?>

	<span class="cs-graph-filter-options graph-option-section"><?php
	echo $select;
	?></span><?php
}

/**
 * Handles the display of the 'Currency' filter for reports.
 *
 * @since 3.0
 */
function display_currency_filter() {
	$currency = get_filter_value( 'currencies' );
	if ( empty( $currency ) ) {
		$currency = 'all';
	}

	$order_currencies = get_transient( 'cs_distinct_order_currencies' );
	if ( false === $order_currencies ) {
		global $wpdb;

		$order_currencies = $wpdb->get_col(
			"SELECT distinct currency FROM {$wpdb->cs_orders}"
		);

		if ( is_array( $order_currencies ) ) {
			$order_currencies = array_filter( $order_currencies );
		}

		set_transient( 'cs_distinct_order_currencies', $order_currencies, 3 * HOUR_IN_SECONDS );
	}

	if ( ! is_array( $order_currencies ) || count( $order_currencies ) <= 1 ) {
		return;
	}

	$all_currencies = array_intersect_key( cs_get_currencies(), array_flip( $order_currencies ) );
	if ( array_key_exists( cs_get_currency(), $all_currencies ) ) {
		$all_currencies = array_merge( array(
			'convert' => sprintf( __( '%s - Converted', 'commercestore' ), $all_currencies[ cs_get_currency() ] )
		), $all_currencies );
	}
	?>
	<span class="cs-graph-filter-options graph-option-section">
		<?php
		echo CS()->html->select( array(
			'name'             => 'currencies',
			'id'               => 'cs_reports_filter_currencies',
			'options'          => $all_currencies,
			'selected'         => $currency,
			'show_option_all'  => false,
			'show_option_none' => false
		) );
		?>
	</span>
	<?php
}

/**
 * Displays the filters UI for a report.
 *
 * @since 3.0
 *
 * @param Data\Report $report Report object.
 */
function display_filters( $report ) {

	// Output the filter bar
	?><form method="get"><?php
		cs_admin_filter_bar( 'reports', $report );
	?></form><?php

}

/**
 * Output filter items
 *
 * @since 3.0
 *
 * @param object $report
 */
function filter_items( $report = false ) {

	// Get the report ID
	$report_id = $report->get_id();

	// Bail if no report
	if ( empty( $report_id ) ) {
		return;
	}

	// Get form actions
	$action = admin_url( add_query_arg( array(
		'post_type' => CS_POST_TYPE,
		'page'      => 'cs-reports',
		'view'      => get_current_report(),
	), 'edit.php' ) );

	// Bail if no filters
	$filters  = $report->get_filters();
	if ( empty( $filters ) ) {
		return;
	}

	// Bail if no manifest
	$manifest = get_filters();
	if ( empty( $manifest ) ) {
		return;
	}

	// Setup callables
	$callables = array();

	// Loop through filters and find the callables
	foreach ( $filters as $filter ) {

		// Skip if empty
		if ( empty( $manifest[ $filter ]['display_callback'] ) ) {
			continue;
		}

		// Skip if not callable
		$callback = $manifest[ $filter ]['display_callback'];
		if ( ! is_callable( $callback ) ) {
			continue;
		}

		// Add callable to callables
		$callables[] = $callback;
	}

	// Bail if no callables
	if ( empty( $callables ) ) {
		return;
	}

	// Start an output buffer
	ob_start();

	// Call the callables in the buffer
	foreach ( $callables as $to_call ) {
		call_user_func( $to_call, $report );
	} ?>

	<span class="cs-graph-filter-submit graph-option-section">
		<input type="submit" class="button button-secondary" value="<?php esc_html_e( 'Filter', 'commercestore' ); ?>"/>
		<input type="hidden" name="cs_action" value="filter_reports" />
		<input type="hidden" name="cs_redirect" value="<?php echo esc_url( $action ); ?>">
		<input type="hidden" name="report_id" value="<?php echo esc_attr( $report_id ); ?>">
	</span>

	<?php

	// Output the current buffer
	echo ob_get_clean();
}
add_action( 'cs_admin_filter_bar_reports', 'CS\Reports\filter_items' );

/**
 * Renders the mobile link at the bottom of the payment history page
 *
 * @since 1.8.4
 * @since 3.0 Updated filter to display link next to the reports filters.
*/
function mobile_link() {
	?>
	<span class="cs-mobile-link">
		<a href="https://commercestore.com/downloads/ios-app/?utm_source=payments&utm_medium=mobile-link&utm_campaign=admin" target="_blank">
			<?php esc_html_e( 'Try the Sales/Earnings iOS App!', 'commercestore' ); ?>
		</a>
	</span>
	<?php
}
add_action( 'cs_after_admin_filter_bar_reports', 'CS\Reports\mobile_link', 100 );

/** Compat ********************************************************************/

/**
 * Private: Injects the value of $_REQUEST['range'] into the Reports\get_dates_filter_range() if set.
 *
 * To be used only for backward-compatibility with anything relying on the `$_REQUEST['range']` value.
 *
 * @since 3.0
 * @access private
 *
 * @param string $range Currently resolved dates range.
 * @return string (Maybe) modified range based on the value of `$_REQUEST['range']`.
 */
function compat_filter_date_range( $range ) {
	return isset( $_REQUEST['range'] )
		? sanitize_key( $_REQUEST['range'] )
		: $range;
}
