<?php
namespace CS\Reports;

if ( ! class_exists( 'CS\\Reports\\Init' ) ) {
	require_once( CS_PLUGIN_DIR . 'includes/reports/class-init.php' );
}

new \CS\Reports\Init();

/**
 * Tests for the Report registry API.
 *
 * @group cs_registry
 * @group cs_reports
 *
 * @coversDefaultClass \CS\Reports\Registry
 */
class Registry_Tests extends \CS_UnitTestCase {

	/**
	 * Report registry fixture.
	 *
	 * @access protected
	 * @var    \CS\Reports\Data\Report_Registry
	 */
	protected $registry;

	/**
	 * Set up fixtures once.
	 */
	public function setUp() {
		parent::setUp();

		$this->registry = new \CS\Reports\Registry();
	}

	/**
	 * Runs after each test to reset the items array.
	 *
	 * @access public
	 */
	public function tear_down() {
		$this->registry->exchangeArray( array() );

		parent::tear_down();
	}

	/**
	 * @covers ::validate_attributes()
	 * @throws \CS_Exception
	 */
	public function test_validate_attributes_should_throw_exception_if_attribute_is_empty_and_not_filtered() {
		$this->setExpectedException(
			'\CS\Reports\Exceptions\Invalid_Parameter',
			"The 'foo' parameter for the 'some_item_id' item is missing or invalid in 'CS\Reports\Registry::validate_attributes'."
		);

		$this->registry->validate_attributes( array( 'foo' => '' ), 'some_item_id' );
	}

	/**
	 * @covers ::validate_attributes()
	 * @throws \CS_Exception
	 */
	public function test_validate_attributes_should_not_throw_exception_if_attribute_is_empty_and_filtered() {
		$attributes = array(
			'foo' => 'bar',
			'baz' => ''
		);

		$filter = array( 'foo' );

		$this->setExpectedException(
			'\CS\Reports\Exceptions\Invalid_Parameter',
			"The 'baz' parameter for the 'some_item_id' item is missing or invalid in 'CS\Reports\Registry::validate_attributes'."
		);

		/*
		 * Tough to actually test for no exception, so we'll have to settle
		 * for testing that the first (filtered) attribute _doesn't_ throw one.
		 */
		$this->registry->validate_attributes( $attributes, 'some_item_id', $filter );
	}
}
