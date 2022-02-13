<?php
namespace CS\Reports\Data\Charts\v2;

if ( ! class_exists( 'CS\\Reports\\Init' ) ) {
	require_once( CS_PLUGIN_DIR . 'includes/reports/class-init.php' );
}

new \CS\Reports\Init();

/**
 * Tests for the Line_Dataset class
 *
 * @group cs_reports
 * @group cs_reports_charts
 *
 * @coversDefaultClass \CS\Reports\Data\Charts\v2\Line_Dataset
 */
class Line_Dataset_Tests extends \CS_UnitTestCase {

	/**
	 * @covers ::$fields
	 */
	public function test_default_fields() {
		$expected = array(
			'borderDash', 'borderDashOffset', 'borderCapStyle', 'borderJoinStyle',
			'cubicInterpolationMode', 'fill', 'lineTension', 'pointBackgroundColor',
			'pointBorderColor', 'pointBorderWidth', 'pointRadius', 'pointStyle',
			'pointHitRadius', 'pointHoverBackgroundColor', 'pointHoverBorderColor',
			'pointHoverBorderWidth', 'pointHoverRadius', 'showLine', 'spanGaps',
			'steppedLine',
		);

		$line_dataset = $this->getMockBuilder( 'CS\\Reports\\Data\\Charts\\v2\\Line_Dataset' )
			->setMethods( null )
			->disableOriginalConstructor()
			->getMock();

		$this->assertEqualSets( $expected, $line_dataset->get_fields() );
	}

}
