<?php
namespace CS\Orders;

/**
 * Order meta tests.
 *
 * @group cs_orders
 */
class Order_Meta_Tests extends \CS_UnitTestCase {

	/**
	 * Order fixture.
	 *
	 * @access protected
	 * @var    Order
	 */
	protected static $order = null;

	/**
	 * Set up fixtures once.
	 */
	public static function wpsetUpBeforeClass() : void  {
		self::$order = parent::cs()->order->create_and_get();
	}

	public function tear_down() {
		parent::tear_down();

		cs_get_component_interface( 'order', 'meta' )->truncate();
	}

	/**
	 * @covers ::cs_add_order_meta
	 */
	public function test_add_metadata_with_empty_key_value_should_return_false() {
		$this->assertFalse( cs_add_order_meta( self::$order->id, '', '' ) );
	}

	/**
	 * @covers ::cs_add_order_meta
	 */
	public function test_add_metadata_with_empty_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_add_order_meta( self::$order->id, 'test_key', '' ) );
	}

	/**
	 * @covers ::cs_add_order_meta
	 */
	public function test_add_metadata_with_key_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_add_order_meta( self::$order->id, 'test_key', '1' ) );
	}

	/**
	 * @covers ::cs_update_order_meta
	 */
	public function test_update_metadata_with_empty_key_value_should_return_false() {
		$this->assertEmpty( cs_update_order_meta( self::$order->id, '', '' ) );
	}

	/**
	 * @covers ::cs_update_order_meta
	 */
	public function test_update_metadata_with_empty_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_update_order_meta( self::$order->id, 'test_key_2', '' ) );
	}

	/**
	 * @covers ::cs_update_order_meta
	 */
	public function test_update_metadata_with_key_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_update_order_meta( self::$order->id, 'test_key_2', '1' ) );
	}

	/**
	 * @covers ::cs_get_order_meta
	 */
	public function test_get_metadata_with_no_args_should_be_empty() {
		$this->assertEmpty( cs_get_order_meta( self::$order->id, '' ) );
	}

	/**
	 * @covers ::cs_get_order_meta
	 */
	public function test_get_metadata_with_invalid_key_should_be_empty() {
		$this->assertEmpty( cs_get_order_meta( self::$order->id, 'key_that_does_not_exist', true ) );
		cs_update_order_meta( self::$order->id, 'test_key_2', '1' );
		$this->assertEquals( '1', cs_get_order_meta( self::$order->id, 'test_key_2', true ) );
		$this->assertInternalType( 'array', cs_get_order_meta( self::$order->id, 'test_key_2', false ) );
	}

	/**
	 * @covers ::cs_get_order_meta
	 */
	public function test_get_metadata_after_update_should_return_1_and_be_of_type_array() {
		cs_update_order_meta( self::$order->id, 'test_key_2', '1' );

		$this->assertEquals( '1', cs_get_order_meta( self::$order->id, 'test_key_2', true ) );
		$this->assertInternalType( 'array', cs_get_order_meta( self::$order->id, 'test_key_2', false ) );
	}

	/**
	 * @covers ::cs_delete_order_meta
	 */
	public function test_delete_metadata_after_update() {
		cs_update_order_meta( self::$order->id, 'test_key', '1' );

		$this->assertTrue( cs_delete_order_meta( self::$order->id, 'test_key' ) );
		$this->assertFalse( cs_delete_order_meta( self::$order->id, 'key_that_does_not_exist' ) );
	}
}
