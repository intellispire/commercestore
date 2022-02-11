<?php
namespace CS;

use CS\Reports as Reports;

/**
 * Tests for CS_Utilities.
 *
 * @group cs_utils
 *
 * @coversDefaultClass \CS\Utilities
 */
class Utilities_Tests extends \CS_UnitTestCase {

	/**
	 * \CS\Utilities fixture.
	 *
	 * @var \CS\Utilities
	 */
	protected static $utils;

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		update_option( 'gmt_offset', -5 );

		CS()->utils->get_gmt_offset( true );

		self::$utils = new Utilities();
	}

	/**
	 * @dataProvider _test_includes_dp
	 * @covers ::includes()
	 *
	 * @group cs_includes
	 */
	public function test_includes( $path_to_file ) {
		$this->assertFileExists( $path_to_file );
	}

	/**
	 * Data provider for test_includes().
	 */
	public function _test_includes_dp() {
		$utils_dir = CS_PLUGIN_DIR . 'includes/utils/';

		return array(

			// Interfaces.
			array( $utils_dir . 'interface-static-registry.php' ),
			array( $utils_dir . 'interface-error-logger.php' ),

			// Exceptions.
			array( $utils_dir . 'class-cs-exception.php' ),
			array( $utils_dir . 'exceptions/class-attribute-not-found.php' ),
			array( $utils_dir . 'exceptions/class-invalid-argument.php' ),
			array( $utils_dir . 'exceptions/class-invalid-parameter.php' ),

			// Date management.
			array( $utils_dir . 'class-date.php' ),

			// Registry.
			array( $utils_dir . 'class-registry.php' ),
		);
	}

	/**
	 * @covers ::get_registry()
	 * @group cs_registry
	 * @group cs_errors
	 */
	public function test_get_registry_with_invalid_registry_should_return_a_WP_Error() {
		$result = self::$utils->get_registry( 'fake' );

		$this->assertWPError( $result );
	}

	/**
	 * @covers ::get_registry()
	 * @group cs_registry
	 * @group cs_errors
	 */
	public function test_get_registry_with_invalid_registry_should_return_a_WP_Error_including_code_invalid_registry() {
		$result = self::$utils->get_registry( 'fake' );

		$this->assertContains( 'invalid_registry', $result->get_error_codes() );
	}

	/**
	 * @covers ::get_registry()
	 * @group cs_registry
	 */
	public function test_get_registry_with_reports_should_retrieve_reports_registry_instance() {
		new Reports\Init();

		$result = self::$utils->get_registry( 'reports' );

		$this->assertInstanceOf( '\CS\Reports\Data\Report_Registry', $result );
	}

	/**
	 * @covers ::get_registry()
	 * @group cs_registry
	 */
	public function test_get_registry_with_reports_endpoints_should_retrieve_endpoints_registry_instance() {
		new Reports\Init();

		$result = self::$utils->get_registry( 'reports:endpoints' );

		$this->assertInstanceOf( '\CS\Reports\Data\Endpoint_Registry', $result );
	}

	/**
	 * @covers ::date()
	 * @group cs_dates
	 */
	public function test_date_default_date_string_and_timeszone_should_return_a_Carbon_instance() {
		$this->assertInstanceOf( '\Carbon\Carbon', self::$utils->date() );
	}

	/**
	 * @covers ::get_gmt_offset()
	 * @group cs_dates
	 */
	public function test_get_gmt_offset_should_return_gmt_offset() {
		$expected = get_option( 'gmt_offset', 0 ) * HOUR_IN_SECONDS;

		$this->assertSame( $expected, self::$utils->get_gmt_offset() );
	}

	/**
	 * @covers ::get_gmt_offset()
	 * @group cs_dates
	 */
	public function test_get_gmt_offset_refresh_true_should_refresh_the_stored_offset() {
		$current_gmt = get_option( 'gmt_offset', 0 );

		update_option( 'gmt_offset', -6 );

		$expected = get_option( 'gmt_offset', -6 ) * HOUR_IN_SECONDS;

		$this->assertSame( $expected, self::$utils->get_gmt_offset( true ) );

		// Clean up.
		update_option( 'gmt_offset', $current_gmt );
	}

	/**
	 * @covers ::get_date_format()
	 * @group cs_dates
	 */
	public function test_get_date_format_should_retrieve_the_WordPress_date_format() {
		$expected = get_option( 'date_format', '' );

		$this->assertSame( $expected, self::$utils->get_date_format() );
	}

	/**
	 * @covers ::get_time_format()
	 * @group cs_dates
	 */
	public function test_get_time_format_should_retrieve_the_WordPress_time_format() {
		$expected = get_option( 'time_format', '' );

		$this->assertSame( $expected, self::$utils->get_time_format() );
	}
}
