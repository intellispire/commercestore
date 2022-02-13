<?php
namespace CS\Reports\Data\Charts\v2;

if ( ! class_exists( 'CS\\Reports\\Init' ) ) {
	require_once( CS_PLUGIN_DIR . 'includes/reports/class-init.php' );
}

new \CS\Reports\Init();

/**
 * Tests for the Pie_Dataset class
 *
 * @group cs_reports
 * @group cs_reports_charts
 *
 * @coversDefaultClass \CS\Reports\Data\Charts\v2\Pie_Dataset
 */
class Pie_Dataset_Tests extends \CS_UnitTestCase {

	/**
	 * @covers ::$fields
	 */
	public function test_default_fields() {
		$expected = array(
			'hoverBackgroundColor', 'hoverBorderColor',
			'hoverBorderWidth'
		);

		$pie_dataset = $this->getMockBuilder( 'CS\\Reports\\Data\\Charts\\v2\\Pie_Dataset' )
			->setMethods( null )
			->disableOriginalConstructor()
			->getMock();

		$this->assertEqualSets( $expected, $pie_dataset->get_fields() );
	}

}
