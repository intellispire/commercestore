<?php
namespace CS\Orders;

use Carbon\Carbon;

/**
 * Trash Tests.
 *
 * @group cs_orders
 * @group cs_trash
 * @group database
 *
 * @coversDefaultClass \CS\Orders\Order
 */
class Trash_Tests extends \CS_UnitTestCase {

	/**
	 * Orders fixture.
	 *
	 * @var array
	 * @static
	 */
	protected static $orders = array();

	protected static $order_queries = null;

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$order_queries = new \CS\Database\Queries\Order();
		self::$orders = parent::cs()->order->create_many( 5 );

		foreach ( self::$orders as $order ) {
			cs_add_order_adjustment( array(
				'object_type' => 'order',
				'object_id'   => $order,
				'type'        => 'discount',
				'description' => '5OFF',
				'subtotal'    => 0,
				'total'       => 5,
			) );
		}
	}

	/**
	 * @covers ::cs_is_order_trashable
	 */
	public function test_is_order_trashable_should_return_true() {
		$this->assertTrue( cs_is_order_trashable( self::$orders[0] ) );
	}

	/**
	 * @covers ::cs_is_order_trashable
	 */
	public function test_is_order_trashable_should_return_false() {
		self::$order_queries->update_item( self::$orders[1], array( 'status' => 'trash' ) );
		$this->assertFalse( cs_is_order_trashable( self::$orders[1] ) );

		// Reset this order ID's status
		self::$order_queries->update_item( self::$orders[1], array( 'status' => 'complete' ) );
	}

	/**
	 * @covers ::cs_trash_order
	 */
	public function test_trash_order_should_return_true() {
		$order = cs_get_order( self::$orders[0] );
		$previous_status = $order->status;

		$this->assertTrue( cs_trash_order( self::$orders[0] ) );

		$order = cs_get_order( self::$orders[0] );
		$this->assertSame( 'trash', $order->status );

		// Update the status of any order to 'trashed'.
		$order_items = cs_get_order_items( array(
			'order_id'      => self::$orders[0],
			'no_found_rows' => true,
		) );

		foreach ( $order_items as $order_item ) {
			$this->assertSame( 'trash', $order_item->status );
		}

		$this->assertSame( $previous_status, cs_get_order_meta( self::$orders[0], '_pre_trash_status', true ) );
	}

	public function test_is_order_restorable_should_return_false() {
		$this->assertFalse( cs_is_order_restorable( self::$orders[0] ) );
	}

	public function test_is_order_restorable_should_return_true() {
		cs_trash_order( self::$orders[0] );
		$this->assertTrue( cs_is_order_restorable( self::$orders[0] ) );
	}

	public function test_restore_order() {
		$order = cs_get_order( self::$orders[0] );
		$previous_status = $order->status;

		cs_trash_order( self::$orders[0] );

		$this->assertTrue( cs_is_order_restorable( self::$orders[0] ) );

		$this->assertTrue( cs_restore_order( self::$orders[0] ) );
		$order = cs_get_order( self::$orders[0] );
		$this->assertSame( $previous_status, $order->status );
		$this->assertEmpty( cs_get_order_meta( self::$orders[0], '_pre_trash_status', true ) );
	}

	public function test_trash_order_with_refunds() {
		cs_refund_order( self::$orders[0] );
		$refunds = cs_get_orders( array( 'parent' => self::$orders[0] ) );

		$this->assertTrue( cs_is_order_trashable( self::$orders[0] ) );
		$this->assertTrue( cs_is_order_trashable( self::$orders[0] ) );

		cs_trash_order( self::$orders[0] );
		$order   = cs_get_order( self::$orders[0] );
		$refunds = cs_get_orders( array( 'parent' => self::$orders[0] ) );
		$this->assertSame( 'trash', $order->status );

		$this->assertFalse( cs_is_order_trashable( $refunds[0]->id ) );
		$this->assertSame( 'trash', $refunds[0]->status );
	}

}
