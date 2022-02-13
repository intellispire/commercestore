<?php
namespace CS\Discounts;

/**
 * Test for the discount meta table.
 *
 * @covers \CS\Database\Queries\Adjustment
 * @group cs_discounts_db
 * @group database
 * @group cs_discounts
 */
class Tests_Meta extends \CS_UnitTestCase {

	/**
	 * Discount object test fixture.
	 *
	 * @access protected
	 * @var    int
	 */
	protected static $discount_id;

	/**
	 * Set up fixtures.
	 *
	 * @access public
	 */
	public static function wpSetUpBeforeClass() {
		self::$discount_id = self::cs()->discount->create_object( array(
			'name'              => '20 Percent Off',
			'code'              => '20OFF',
			'status'            => 'active',
			'type'              => 'percent',
			'amount'            => '20',
			'use_count'         => 54,
			'max_uses'          => 10,
			'min_charge_amount' => 128,
			'product_condition' => 'all',
			'start_date'        => '2010-12-12 00:00:00',
			'end_date'          => '2050-12-31 23:59:59'
		) );
	}

	/**
	 * @covers \CS\Database\Queries\Adjustment::add_meta()
	 * @covers CS_Discount::add_meta()
	 */
	public function test_add_metadata_with_empty_key_value_should_be_null() {
		$this->assertFalse( cs_add_adjustment_meta( self::$discount_id, '', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Adjustment::add_meta()
	 * @covers CS_Discount::add_meta()
	 */
	public function test_add_metadata_with_empty_value_should_be_empty() {
		$this->assertNotEmpty( cs_add_adjustment_meta( self::$discount_id, 'test_key', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Adjustment::add_meta()
	 * @covers CS_Discount::add_meta()
	 */
	public function test_add_metadata_with_key_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_add_adjustment_meta( self::$discount_id, 'test_key', '1' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Adjustment::update_meta()
	 * @covers CS_Discount::update_meta()
	 */
	public function test_update_metadata_with_empty_key_value_should_be_empty() {
		$this->assertEmpty( cs_update_adjustment_meta( self::$discount_id, '', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Adjustment::update_meta()
	 * @covers CS_Discount::update_meta()
	 */
	public function test_update_metadata_with_empty_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_update_adjustment_meta( self::$discount_id, 'test_key_2', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Adjustment::update_meta()
	 * @covers CS_Discount::update_meta()
	 */
	public function test_update_metadata_with_key_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_update_adjustment_meta( self::$discount_id, 'test_key_2', '1' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Adjustment::get_meta()
	 * @covers CS_Discount::get_meta()
	 */
	public function test_get_metadata_with_no_args_should_return_array() {
		$this->assertSame( 1, count( cs_get_adjustment_meta( self::$discount_id ) ) );
	}

	/**
	 * @covers \CS\Database\Queries\Adjustment::get_meta()
	 * @covers CS_Discount::get_meta()
	 */
	public function test_get_metadata_with_invalid_key_should_be_empty() {
		$this->assertEmpty( cs_get_adjustment_meta( self::$discount_id, 'key_that_does_not_exist', true ) );
	}

	/**
	 * @covers \CS\Database\Queries\Adjustment::get_meta()
	 * @covers CS_Discount::get_meta()
	 */
	public function test_get_metadata_after_update_should_return_that_value() {
		cs_update_adjustment_meta( self::$discount_id, 'test_key_2', '1' );
		$this->assertEquals( '1', cs_get_adjustment_meta( self::$discount_id, 'test_key_2', true ) );
	}

	/**
	 * @covers \CS\Database\Queries\Adjustment::delete_meta()
	 * @covers CS_Discount::delete_meta()
	 */
	public function test_delete_metadata_with_valid_key_should_return_true() {
		cs_update_adjustment_meta( self::$discount_id, 'test_key', '1' );
		$this->assertTrue( cs_delete_adjustment_meta( self::$discount_id, 'test_key' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Adjustment::delete_meta()
	 * @covers CS_Discount::delete_meta()
	 */
	public function test_delete_metadata_with_invalid_key_should_return_false() {
		$this->assertFalse( cs_delete_adjustment_meta( self::$discount_id,  'key_that_does_not_exist' ) );
	}

	/**
	 * @covers \cs_get_discount_product_condition()
	 */
	public function test_discount_product_condition() {
		$this->assertSame( 'all', cs_get_discount_product_condition( self::$discount_id ) );
	}
}
