<?php
namespace CS\Reports;

if ( ! class_exists( 'CS\\Reports\\Init' ) ) {
	require_once( CS_PLUGIN_DIR . 'includes/reports/class-init.php' );
}

new \CS\Reports\Init();

/**
 * Tests for the Endpoint object.
 *
 * @group cs_reports
 * @group cs_reports_endpoints
 * @group cs_reports_functions
 * @group cs_objects
 */
class Reports_Functions_Tests extends \CS_UnitTestCase {

	/**
	 * Date fixture.
	 *
	 * @var \CS\Utils\Date
	 */
	protected static $date;

	/**
	 * Set up fixtures once.
	 */
	public static function wpsetUpBeforeClass() : void  {
		self::$date = CS()->utils->date();
	}

	/**
	 * Runs after every test method.
	 */
	public function tear_down() {
		unset( $_REQUEST['filter_from'] );
		unset( $_REQUEST['filter_to'] );
		unset( $_REQUEST['range'] );

		/** @var \CS\Reports\Data\Report_Registry|\WP_Error $registry */
		$registry = CS()->utils->get_registry( 'reports' );
		$registry->exchangeArray( array() );

		// Clear filters.
		$filters = array_keys( get_filters() );

		foreach ( $filters as $filter ) {
			clear_filter( $filter );
		}

		parent::tear_down();
	}

	/**
	 * @covers \CS\Reports\get_current_report()
	 */
	public function test_get_current_report_should_use_the_value_of_the_tab_var_when_set() {
		$_REQUEST['view'] = 'overview';

		$this->assertSame( 'overview', get_current_report() );
	}

	/**
	 * @covers \CS\Reports\get_current_report()
	 */
	public function test_get_current_report_should_use_the_sanitized_value_of_the_tab_var_when_set() {
		$_REQUEST['view'] = 'sales/figures';

		$this->assertSame( 'salesfigures', get_current_report() );
	}

	/**
	 * @covers \CS\Reports\get_endpoint()
	 * @group cs_errors
	 */
	public function test_get_endpoint_with_invalid_endpoint_id_should_return_WP_Error() {
		$result = get_endpoint( 'fake', 'tile' );

		$this->assertWPError( $result );
	}

	/**
	 * @covers \CS\Reports\get_endpoint()
	 * @group cs_errors
	 */
	public function test_get_endpoint_with_invalid_endpoint_id_should_return_WP_Error_code_invalid_endpoint() {
		$result = get_endpoint( 'fake', 'tile' );

		$this->assertSame( 'invalid_endpoint', $result->get_error_code() );
	}

	/**
	 * @covers \CS\Reports\get_endpoint()
	 * @throws \CS_Exception
	 */
	public function test_get_endpoint_with_valid_endpoint_id_valid_type_should_return_an_Endpoint_object() {
		register_endpoint( 'foo', array(
			'label' => 'Foo',
			'views' => array(
				'tile' => array(
					'display_args'     => array( 'some_value' ),
					'display_callback' => '__return_false',
					'data_callback'    => '__return_false',
				),
			),
		) );

		$registry = CS()->utils->get_registry( 'reports:endpoints' );

		$result = get_endpoint( 'foo', 'tile' );

		$this->assertInstanceOf( 'CS\Reports\Data\Endpoint', $result );
	}

	/**
	 * @covers \CS\Reports\get_endpoint()
	 * @group cs_errors
	 * @throws \CS_Exception
	 */
	public function test_get_endpoint_with_valid_endpoint_id_invalid_type_should_return_WP_Error_including_invalid_view_error_code() {
		register_endpoint( 'foo', array(
			'label' => 'Foo',
			'views' => array(
				'tile' => array(
					'display_args'     => array( 'some_value' ),
					'display_callback' => '__return_false',
					'data_callback'    => '__return_false',
				),
			),
		) );

		$result = get_endpoint( 'foo', 'fake' );

		$this->assertSame( 'invalid_view', $result->get_error_code() );
	}

	/**
	 * @covers \CS\Reports\parse_endpoint_views()
	 */
	public function test_get_endpoint_views_should_return_the_defaults() {
		$views = get_endpoint_views();

		$this->assertEqualSets( array( 'tile', 'chart', 'table' ), array_keys( $views ) );
	}

	/**
	 * @covers \CS\Reports\parse_endpoint_views()
	 */
	public function test_parse_endpoint_views_with_invalid_view_should_leave_it_intact() {
		$expected = array(
			'fake' => array(
				'display_callback' => '__return_false'
			),
		);

		$this->assertEqualSetsWithIndex( $expected, parse_endpoint_views( $expected ) );
	}

	/**
	 * @covers \CS\Reports\parse_endpoint_views()
	 */
	public function test_parse_endpoint_views_with_valid_view_should_inject_defaults() {
		$expected = array(
			'tile' => array(
				'data_callback'    => '__return_zero',
				'display_callback' => __NAMESPACE__ . '\\default_display_tile',
				'display_args'     => array(
					'type'             => '' ,
					'context'          => 'primary',
					'comparison_label' => __( 'All time', 'commercestore' ),
				),
			),
		);

		$views = array(
			'tile' => array(
				'data_callback' => '__return_zero',
			),
		);

		$this->assertEqualSetsWithIndex( $expected, parse_endpoint_views( $views ) );
	}

	/**
	 * @covers \CS\Reports\parse_endpoint_views()
	 */
	public function test_parse_endpoint_views_should_strip_invalid_fields() {
		$views = array(
			'tile' => array(
				'fake_field' => 'foo',
			),
		);

		$result = parse_endpoint_views( $views );

		$this->assertArrayNotHasKey( 'fake_field', $result['tile'] );
	}

	/**
	 * @covers \CS\Reports\parse_endpoint_views()
	 */
	public function test_parse_endpoint_views_should_inject_default_display_args() {
		$expected = array(
			'type'             => 'number',
			'context'          => 'primary',
			'comparison_label' => __( 'All time', 'commercestore' ),
		);

		$views = array(
			'tile' => array(
				'display_args' => array(
					'type' => 'number',
				)
			)
		);

		$result = parse_endpoint_views( $views );

		$this->assertEqualSetsWithIndex( $expected, $result['tile']['display_args'] );
	}

	/**
	 * @covers \CS\Reports\validate_endpoint_view()
	 */
	public function test_validate_endpoint_view_with_valid_view_should_return_true() {
		$this->assertTrue( validate_endpoint_view( 'tile' ) );
	}

	/**
	 * @covers \CS\Reports\validate_endpoint_view()
	 */
	public function test_validate_endpoint_view_with_invalid_view_should_return_false() {
		$this->assertFalse( validate_endpoint_view( 'fake' ) );
	}

	/**
	 * @covers \CS\Reports\get_endpoint_handler()
	 */
	public function test_get_endpoint_handler_with_valid_view_should_return_the_handler() {
		$expected = 'CS\Reports\Data\Tile_Endpoint';

		$this->assertSame( $expected, get_endpoint_handler( 'tile' ) );
	}

	/**
	 * @covers \CS\Reports\get_endpoint_handler()
	 */
	public function test_get_endpoint_handler_with_invalid_view_should_return_empty() {
		$this->assertSame( '', get_endpoint_handler( 'fake' ) );
	}

	/**
	 * @covers \CS\Reports\get_endpoint_group_callback()
	 */
	public function test_get_endpoint_group_callback_with_tile_view_should_return_that_group_callback() {
		$expected = 'CS\Reports\default_display_tiles_group';

		$this->assertSame( $expected, get_endpoint_group_callback( 'tile' ) );
	}

	/**
	 * @covers \CS\Reports\get_endpoint_group_callback()
	 */
	public function test_get_endpoint_group_callback_with_table_view_should_return_that_group_callback() {
		$expected = 'CS\Reports\default_display_tables_group';

		$this->assertSame( $expected, get_endpoint_group_callback( 'table' ) );
	}

	/**
	 * @covers \CS\Reports\get_endpoint_group_callback()
	 */
	public function test_get_endpoint_group_callback_with_invalid_view_should_return_an_empty_string() {
		$this->assertSame( '', get_endpoint_group_callback( 'fake' ) );
	}

	/**
	 * @covers \CS\Reports\get_filters()
	 */
	public function test_get_filters_should_return_records_for_all_official_filters() {
		$expected = array( 'dates', 'products', 'product_categories', 'taxes', 'gateways', 'discounts', 'regions', 'countries', 'currencies' );

		$this->assertEqualSets( $expected, array_keys( get_filters() ) );
	}

	/**
	 * @covers \CS\Reports\validate_filter()
	 */
	public function test_validate_filter_with_valid_filter_should_return_true() {
		$this->assertTrue( validate_filter( 'dates' ) );
	}

	/**
	 * @covers \CS\Reports\validate_filter()
	 */
	public function test_validate_filter_with_invalid_filter_should_return_false() {
		$this->assertFalse( validate_filter( 'fake' ) );
	}

	/**
	 * @covers \CS\Reports\get_filter_value()
	 */
	public function test_get_filter_value_with_invalid_filter_should_return_an_empty_string() {
		$this->assertSame( '', get_filter_value( 'fake' ) );
	}

	/**
	 * @covers \CS\Reports\get_filter_value()
	 */
	public function test_get_filter_value_with_a_valid_filter_should_retrieve_that_filters_value() {
		$expected = array(
			'from' => date( 'Y-m-d H:i:s' ),
			'to'   => date( 'Y-m-d H:i:s' ),
		);

		set_filter_value( 'dates', $expected );

		$this->assertEqualSetsWithIndex( $expected, get_filter_value( 'dates' ) );
	}

	/**
	 * @covers \CS\Reports\get_dates_filter_options()
	 * @group cs_dates
	 */
	public function test_get_dates_filter_options_should_match_defaults() {
		$expected = array(
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

		$this->assertEqualSetsWithIndex( $expected, get_dates_filter_options() );
	}

	/**
	 * @covers \CS\Reports\get_dates_filter()
	 * @group cs_dates
	 */
	public function test_get_dates_filter_should_return_strings() {
		$expected = array(
			'start' => self::$date->copy()->subDay( 30 )->startOfDay()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfDay()->toDateTimeString(),
			'range' => 'last_30_days',
		);

		$result = get_dates_filter();

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $result );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\get_dates_filter()
	 * @group cs_dates
	 */
	public function test_get_dates_filter_objects_as_values_should_return_objects() {
		$expected = array(
			'start' => self::$date->copy()->subDay( 30 )->startOfDay(),
			'end'   => self::$date->copy()->endOfDay(),
		);

		$result = get_dates_filter( 'objects' );

		$this->assertInstanceOf( '\CS\Utils\Date', $result['start'] );
		$this->assertInstanceOf( '\CS\Utils\Date', $result['end'] );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_this_month_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->startOfMonth()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfMonth()->toDateTimeString(),
			'range' => 'this_month',
		);

		$result = parse_dates_for_range( 'this_month' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_last_month_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->subMonthNoOverflow( 1 )->startOfMonth()->toDateTimeString(),
			'end'   => self::$date->copy()->subMonthNoOverflow( 1 )->endOfMonth()->toDateTimeString(),
			'range' => 'last_month',
		);

		$result = parse_dates_for_range( 'last_month' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_overflow_last_month_range_should_return_those_dates() {
		$overflow_day  = '2020-03-30 00:00:00';
		$overflow_date = CS()->utils->date( $overflow_day );

		$expected = array(
			'start' => ( new \DateTime( '2020-02-01 00:00:00' ) )->format( 'Y-m-d H:i' ),
			'end'   => ( new \DateTime( '2020-02-29 23:59:59' ) )->format( 'Y-m-d H:i' ),
			'range' => 'last_month',
		);

		$result = parse_dates_for_range( 'last_month', $overflow_day );

		// Explicitly strip seconds in case the test is slow.
		$result = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_today_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->setTimezone( cs_get_timezone_id() )->startOfDay()->setTimezone( 'UTC' ),
			'end'   => self::$date->copy()->setTimezone( cs_get_timezone_id() )->endOfDay()->setTimezone( 'UTC' ),
			'range' => 'today',
		);

		$result = parse_dates_for_range( 'today' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_yesterday_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->setTimezone( cs_get_timezone_id() )->subDay( 1 )->startOfDay()->setTimezone( 'UTC' ),
			'end'   => self::$date->copy()->setTimezone( cs_get_timezone_id() )->subDay( 1 )->endOfDay()->setTimezone( 'UTC' ),
			'range' => 'yesterday',
		);

		$result = parse_dates_for_range( 'yesterday' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_this_week_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->startOfWeek()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfWeek()->toDateTimeString(),
			'range' => 'this_week',
		);

		$result = parse_dates_for_range( 'this_week' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_last_week_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->subWeek( 1 )->startOfWeek()->toDateTimeString(),
			'end'   => self::$date->copy()->subWeek( 1 )->endOfWeek()->toDateTimeString(),
			'range' => 'last_week',
		);

		$result = parse_dates_for_range( 'last_week' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_last_30_days_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->subDay( 30 )->startOfDay()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfDay()->toDateTimeString(),
			'range' => 'last_30_days',
		);

		$result = parse_dates_for_range( 'last_30_days' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_this_quarter_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->startOfQuarter()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfQuarter()->toDateTimeString(),
			'range' => 'this_quarter',
		);

		$result = parse_dates_for_range( 'this_quarter' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_last_quarter_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->subQuarter( 1 )->startOfQuarter()->toDateTimeString(),
			'end'   => self::$date->copy()->subQuarter( 1 )->endOfQuarter()->toDateTimeString(),
			'range' => 'last_quarter',
		);

		$result = parse_dates_for_range( 'last_quarter' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_this_year_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->startOfYear()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfYear()->toDateTimeString(),
			'range' => 'this_year',
		);

		$result = parse_dates_for_range( 'this_year' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_last_year_range_should_return_those_dates() {
		$expected = array(
			'start' => self::$date->copy()->subYear( 1 )->startOfYear()->toDateTimeString(),
			'end'   => self::$date->copy()->subYear( 1 )->endOfYear()->toDateTimeString(),
			'range' => 'last_year',
		);

		$result = parse_dates_for_range( 'last_year' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_other_range_should_return_dates_for_request_vars() {
		$dates = array(
			'from'  => self::$date->copy()->subCentury( 2 )->startOfDay()->toDateTimeString(),
			'to'    => self::$date->copy()->addCentury( 2 )->endOfDay()->toDateTimeString(),
			'range' => 'other',
		);

		set_filter_value( 'dates', $dates );

		$expected = array(
			'start' => $dates['from'],
			'end'   => $dates['to'],
			'range' => 'other',
		);

		$result = parse_dates_for_range( 'other' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\parse_dates_for_range()
	 * @group cs_dates
	 */
	public function test_parse_dates_for_range_with_invalid_range_no_report_id_no_range_var_should_use_last_30_days() {
		$expected = array(
			'start' => self::$date->copy()->subDay( 30 )->startOfDay()->toDateTimeString(),
			'end'   => self::$date->copy()->endOfDay()->toDateTimeString(),
			'range' => 'last_30_days',
		);

		$result = parse_dates_for_range( 'fake' );

		// Explicitly strip seconds in case the test is slow.
		$expected = $this->strip_seconds( $expected );
		$result   = $this->strip_seconds( $this->objects_to_date_strings( $result ) );

		$this->assertEqualSetsWithIndex( $expected, $result );
	}

	/**
	 * @covers \CS\Reports\get_dates_filter_range()
	 * @group cs_dates
	 */
	public function test_get_dates_filter_range_with_no_preset_range_should_defualt_to_last_30_days() {
		$this->assertSame( 'last_30_days', get_dates_filter_range() );
	}

	/**
	 * @covers \CS\Reports\get_dates_filter_range()
	 * @group cs_dates
	 */
	public function test_get_dates_filter_range_with_non_default_range_set_should_return_that_reports_range() {
		$filter_key = get_filter_key( 'dates' );

		set_filter_value( 'dates', array(
			'range' => 'last_quarter',
		) );

		$this->assertSame( 'last_quarter', get_dates_filter_range() );
	}

	/**
	 * @covers \CS\Reports\get_filter_key
	 */
	public function test_get_filter_key_should_begin_with_reports() {
		$this->assertMatchesRegularExpression( '/^reports/', get_filter_key( 'dates' ) );
	}

	/**
	 * @covers \CS\Reports\get_filter_key
	 */
	public function test_get_filter_key_should_contain_the_filter_name() {
		$filter = 'dates';

		$this->assertMatchesRegularExpression( "/filter-{$filter}/", get_filter_key( $filter ) );
	}

	/**
	 * @covers \CS\Reports\get_filter_key
	 */
	public function test_get_filter_key_should_contain_the_current_site_id() {
		$site = get_current_blog_id();

		$this->assertMatchesRegularExpression( "/site-{$site}/", get_filter_key( 'dates' ) );
	}

	/**
	 * @covers \CS\Reports\get_filter_key
	 */
	public function test_get_filter_key_should_contain_the_current_user_id() {
		$user = get_current_user_id();

		$this->assertMatchesRegularExpression( "/user-{$user}/", get_filter_key( 'dates' ) );
	}

	/**
	 * @covers \CS\Reports\get_filter_key
	 */
	public function test_get_filter_key_should_contain_reports_the_filter_the_site_and_the_user() {
		$filter = 'dates';
		$site   = get_current_blog_id();
		$user   = get_current_user_id();

		$expected = "reports:filter-{$filter}:site-{$site}:user-{$user}";

		$this->assertSame( $expected, get_filter_key( $filter ) );
	}

	/**
	 * @covers \CS\Reports\set_filter_value
	 */
	public function test_set_filter_key_with_invalid_filter_should_not_set_filter() {
		set_filter_value( 'foo', 'bar' );

		$this->assertSame( '', get_filter_value( 'foo' ) );
	}

	/**
	 * @covers \CS\Reports\set_filter_value
	 */
	public function test_set_filter_value_with_valid_filter_should_set_it() {
		$dates = array(
			'from' => date( 'Y-m-d H:i:s' ),
			'to'   => date( 'Y-m-d H:i:s' ),
		);

		set_filter_value( 'dates', $dates );

		$this->assertEqualSetsWithIndex( $dates, get_filter_value( 'dates' ) );
	}

	/**
	 * @covers \CS\Reports\clear_filter
	 */
	public function test_clear_filter_should_default_to_last_30_days() {
		$dates = array(
			'from' => date( 'Y-m-d H:i:s' ),
			'to'   => date( 'Y-m-d H:i:s' ),
		);

		// Set the dates filter so there's something to clear.
		set_filter_value( 'dates', $dates );

		$this->assertEqualSetsWithIndex( $dates, get_filter_value( 'dates' ) );

		// Clear it.
		clear_filter( 'dates' );

		// Default to last 30 days for filter value.
		$dates = parse_dates_for_range( 'last_30_days' );

		$expected = array(
			'from'  => $dates['start']->format( 'Y-m-d' ),
			'to'    => $dates['end']->format( 'Y-m-d' ),
			'range' => 'last_30_days',
		);

		$this->assertEqualSetsWithIndex( $expected, get_filter_value( 'dates' ) );
	}

	public function test_gross_order_status() {
		$expected = array(
			'complete',
			'refunded',
			'partially_refunded',
			'revoked',
		);

		$this->assertSame( $expected, cs_get_gross_order_statuses() );
	}

	public function test_net_order_status() {
		$expected = array(
			'complete',
			'partially_refunded',
			'revoked',
		);

		$this->assertSame( $expected, cs_get_net_order_statuses() );
	}

	/**
	 * Strips the seconds from start and end datetime strings to guard against slow tests.
	 *
	 * @param array $dates Start/end dates array.
	 * @return array Start/end dates minus their seconds.
	 */
	protected function strip_seconds( $dates ) {
		$dates['start'] = date( 'Y-m-d H:i', strtotime( $dates['start'] ) );
		$dates['end']   = date( 'Y-m-d H:i', strtotime( $dates['end'] ) );

		return $dates;
	}

	/**
	 * Converts start and end date objects to strings.
	 *
	 * @param array $dates Start/end date objects array.
	 * @return array Start/end date strings array.
	 */
	protected function objects_to_date_strings( $dates ) {
		$dates['start'] = $dates['start']->toDateTimeString();
		$dates['end']   = $dates['end']->toDateTimeString();

		return $dates;
	}
}
