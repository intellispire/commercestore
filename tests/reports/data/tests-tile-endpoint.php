<?php
namespace CS\Reports\Data;

if ( ! class_exists( 'CS\\Reports\\Init' ) ) {
	require_once( CS_PLUGIN_DIR . 'includes/reports/class-init.php' );
}

new \CS\Reports\Init();

/**
 * Tests for the Tile_Endpoint object.
 *
 * @group cs_reports
 * @group cs_reports_endpoints
 * @group cs_objects
 *
 * @coversDefaultClass \CS\Reports\Data\Tile_Endpoint
 */
class Tile_Endpoint_Tests extends \CS_UnitTestCase {

	/**
	 * @covers ::check_view()
	 */
	public function test_check_view_with_valid_view_should_set_that_view() {
		$endpoint = new Tile_Endpoint( array() );

		$this->assertSame( 'tile', $endpoint->get_view() );
	}

}
