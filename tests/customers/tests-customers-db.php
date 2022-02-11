<?php
namespace CS\Customers;

/**
 * Customers DB Tests.
 *
 * @group cs_customers_db
 * @group database
 * @group cs_customers
 */

class Tests_Customers_DB extends \CS_UnitTestCase {

	/**
	 * Customers fixture.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $customers = array();

	/**
	 * Set up fixtures once.
	 */
	public static function wpSetUpBeforeClass() {
		self::$customers = parent::cs()->customer->create_many( 5 );
	}

	public function test_installed() {
		$this->assertTrue( cs_get_component_interface( 'customer', 'table' )->exists() );
	}

	public function test_insert_with_no_email_supplied_should_return_false() {
		$this->assertFalse( cs_add_customer( array(
			'name' => 'John Smith',
		) ) );
	}

	public function test_insert_with_invalid_data_should_return_false() {
		$this->assertFalse( cs_add_customer() );
	}

	public function test_update_should_return_false_if_no_row_id_supplied() {
		$this->assertFalse( cs_update_customer( 0 ) );
	}

	public function test_customer_object_after_update_should_return_false() {
		$this->assertSame( 1, cs_update_customer( self::$customers[0], array(
			'name' => 'John Smith'
		) ) );

		/** @var $customer \CS_Customer */
		$customer = cs_get_customer( self::$customers[0] );

		$this->assertSame( 'John Smith', $customer->name );
	}

	public function test_delete_should_return_false_if_no_row_id_supplied() {
		$this->assertFalse( cs_delete_customer( 0 ) );
	}

	public function test_delete_should_return_false() {
		$this->assertSame( 1, cs_delete_customer( self::$customers[0] ) );

		$customer = cs_get_customer( self::$customers[0] );

		$this->assertFalse( $customer );
	}

	public function test_get_customers_should_return_5() {
		$customers = cs_get_customers();

		$this->assertCount( 5, $customers );
	}

	public function test_get_customers_with_number_should_return_5() {
		$customers = cs_get_customers( array(
			'number' => 10
		) );

		$this->assertCount( 5, $customers );
	}

	public function test_get_customers_with_offset_should_return_true() {
		$customers = cs_get_customers( array(
			'number' => 10,
			'offset' => 4,
		) );

		$this->assertCount( 1, $customers );
	}

	public function test_get_customers_with_orderby_name_and_order_asc_should_return_true() {
		$customers = cs_get_customers( array(
			'orderby' => 'name',
			'order'   => 'asc'
		) );

		$this->assertTrue( $customers[0]->name < $customers[1]->name );
	}

	public function test_get_customers_with_orderby_name_and_order_desc_should_return_true() {
		$customers = cs_get_customers( array(
			'orderby' => 'name',
			'order'   => 'desc'
		) );

		$this->assertTrue( $customers[0]->name > $customers[1]->name );
	}

	public function test_get_customers_with_orderby_email_and_order_asc_should_return_true() {
		$customers = cs_get_customers( array(
			'orderby' => 'email',
			'order'   => 'asc'
		) );

		$this->assertTrue( $customers[0]->email < $customers[1]->email );
	}

	public function test_get_customers_with_orderby_email_and_order_desc_should_return_true() {
		$customers = cs_get_customers( array(
			'orderby' => 'email',
			'order'   => 'desc'
		) );

		$this->assertTrue( $customers[0]->email > $customers[1]->email );
	}

	public function test_get_customers_with_id_should_be_1() {
		$customers = cs_get_customers( array(
			'id' => self::$customers[4]
		) );

		$this->assertCount( 1, $customers );
	}

	public function test_get_customers_with_email_should_be_1() {
		$customers = cs_get_customers( array(
			'email' => 'user' . \WP_UnitTest_Generator_Sequence::$incr . '@cs.test'
		) );

		$this->assertCount( 1, $customers );
	}

	public function test_get_customers_by_invalid_id_should_be_0() {
		$customers = cs_get_customers( array(
			'id' => 99999,
		) );

		$this->assertCount( 0, $customers );
	}

	public function test_count_should_be_5() {
		$this->assertSame( 5, cs_count_customers() );
	}

	public function test_count_with_id_should_be_1() {
		$this->assertSame( 1, cs_count_customers( array(
			'id' => self::$customers[2]
		) ) );
	}

	public function test_count_with_email_should_be_1() {
		$this->assertSame( 1, cs_count_customers( array(
			'email' => 'user' . \WP_UnitTest_Generator_Sequence::$incr . '@cs.test'
		) ) );
	}

	public function test_get_customer_by() {
		$customer = cs_get_customer_by( 'email', 'user' . \WP_UnitTest_Generator_Sequence::$incr . '@cs.test' );

		$this->assertSame( 'user' . \WP_UnitTest_Generator_Sequence::$incr . '@cs.test', $customer->email );
	}

	public function test_update_customer_email_on_user_update() {
		$user_id = wp_insert_user( array(
			'user_login' => 'user' . ( \WP_UnitTest_Generator_Sequence::$incr - 1 ),
			'user_email' => 'user' . ( \WP_UnitTest_Generator_Sequence::$incr - 1 ) . '@cs.test',
			'user_pass'  => wp_generate_password()
		) );

		cs_update_customer( self::$customers[3], array(
			'user_id' => $user_id
		) );

		wp_update_user( array(
			'ID' => $user_id,
			'user_email' => 'user' . \WP_UnitTest_Generator_Sequence::$incr . '-updated@cs.test',
		) );

		$updated_customer = new \CS_Customer( 'user' . \WP_UnitTest_Generator_Sequence::$incr . '-updated@cs.test' );

		$this->assertEquals( self::$customers[3], $updated_customer->id );
	}

	public function test_legacy_attach_payment_should_return_true() {
		$payment_id = \CS_Helper_Payment::create_simple_payment();

		// Legacy method that should be handled by Customer back-compat class.
		CS()->customers->attach_payment( self::$customers[0], $payment_id );

		$customer    = cs_get_customer( self::$customers[0] );
		$payment_ids = array_map( 'absint', explode( ',', $customer->payment_ids ) );

		$this->assertTrue( in_array( $payment_id, $payment_ids ) );
	}

	public function test_legacy_remove_payment_should_return_false() {
		$payment_id = \CS_Helper_Payment::create_simple_payment();

		// Legacy method that should be handled by Customer back-compat class.
		CS()->customers->attach_payment( self::$customers[0], $payment_id );
		CS()->customers->remove_payment( self::$customers[0], $payment_id );

		$customer    = cs_get_customer( self::$customers[0] );
		$payment_ids = array_map( 'absint', explode( ',', $customer->payment_ids ) );

		$this->assertFalse( in_array( $payment_id, $payment_ids ) );
	}

	/**
	 * @expectCSDeprecated CS_Customer::increase_purchase_count
	 * @expectCSDeprecated CS_Customer::increase_value
	 */
	public function test_legacy_increment_stats_purchase_value_should_return_10() {
		CS()->customers->increment_stats( self::$customers[0], 10 );

		/** @var $customer \CS_Customer */
		$customer = cs_get_customer( self::$customers[0] );

		$this->assertSame( 10.0, $customer->purchase_value );
	}

	/**
	 * @expectCSDeprecated CS_Customer::increase_purchase_count
	 * @expectCSDeprecated CS_Customer::increase_value
	 */
	public function test_legacy_increment_stats_purchase_count_should_return_1() {
		CS()->customers->increment_stats( self::$customers[0], 10 );

		/** @var $customer \CS_Customer */
		$customer = cs_get_customer( self::$customers[0] );

		$this->assertSame( 1, $customer->purchase_count );
	}

	/**
	 * @expectCSDeprecated CS_Customer::increase_purchase_count
	 * @expectCSDeprecated CS_Customer::increase_value
	 * @expectCSDeprecated CS_Customer::decrease_purchase_count
	 * @expectCSDeprecated CS_Customer::decrease_value
	 */
	public function test_legacy_decrement_stats_purchase_value_should_return_90() {
		CS()->customers->increment_stats( self::$customers[0], 100 );
		CS()->customers->decrement_stats( self::$customers[0], 10 );

		/** @var $customer \CS_Customer */
		$customer = cs_get_customer( self::$customers[0] );

		$this->assertSame( 90.0, $customer->purchase_value );
	}

	/**
	 * @expectCSDeprecated CS_Customer::increase_purchase_count
	 * @expectCSDeprecated CS_Customer::increase_value
	 * @expectCSDeprecated CS_Customer::decrease_purchase_count
	 * @expectCSDeprecated CS_Customer::decrease_value
	 */
	public function test_legacy_decrement_stats_purchase_count_should_return_0() {
		CS()->customers->increment_stats( self::$customers[0], 10 );
		CS()->customers->decrement_stats( self::$customers[0], 10 );

		/** @var $customer \CS_Customer */
		$customer = cs_get_customer( self::$customers[0] );

		$this->assertSame( 0, $customer->purchase_count );
	}
}
