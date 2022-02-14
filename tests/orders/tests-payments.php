<?php
namespace CS\Orders;

/**
 * Payment tests.
 *
 * @group cs_payments
 */
class Payment_Tests extends \CS_UnitTestCase {

	/**
	 * Payment test fixture.
	 *
	 * @var \CS_Payment
	 */
	protected static $payment;

	public function setUp() {
		self::$payment = cs_get_payment( \CS_Helper_Payment::create_simple_payment() );
	}

	public function tearDown() {
		parent::tearDown();

		\CS_Helper_Payment::delete_payment( self::$payment->ID );

		cs_destroy_order( self::$payment->ID );

		$component = cs_get_component_interface( 'order_transaction', 'table' );

		if ( $component instanceof \CS\Database\Table ) {
			$component->truncate();
		}

		self::$payment = null;
	}

	public function test_get_payments() {
		$payments = cs_get_payments();

		$this->assertTrue( is_array( (array) $payments[0] ) );
		$this->assertArrayHasKey( 'ID', (array) $payments[0] );
		$this->assertEquals( 'cs_payment', $payments[0]->post_type );
	}

	public function test_payments_query_cs_payments() {
		$payments = new \CS_Payments_Query( array( 'output' => 'cs_payments' ) );
		$payments = $payments->get_payments();

		$this->assertTrue( is_object( $payments[0] ) );
		$this->assertTrue( property_exists( $payments[0], 'ID' ) );
		$this->assertTrue( property_exists( $payments[0], 'cart_details' ) );
		$this->assertTrue( property_exists( $payments[0], 'user_info' ) );
	}

	public function test_payments_query_payments() {
		$payments = new \CS_Payments_Query( array( 'output' => 'payments' ) );
		$out      = $payments->get_payments();
		$this->assertTrue( is_object( $out[0] ) );
		$this->assertTrue( property_exists( $out[0], 'ID' ) );
		$this->assertTrue( property_exists( $out[0], 'cart_details' ) );
		$this->assertTrue( property_exists( $out[0], 'user_info' ) );
	}

	public function test_payments_query_default() {
		$payments = new \CS_Payments_Query();
		$payments = $payments->get_payments();

		$this->assertTrue( is_object( $payments[0] ) );
		$this->assertTrue( property_exists( $payments[0], 'ID' ) );
		$this->assertTrue( property_exists( $payments[0], 'cart_details' ) );
		$this->assertTrue( property_exists( $payments[0], 'user_info' ) );
	}

	public function test_payments_query_search_discount() {
		$discount = cs_get_discount( \CS_Helper_Discount::create_simple_percent_discount() );

		$payment_id = \CS_Helper_Payment::create_simple_payment( array( 'discount' => $discount->code ) );

		$payments_query = new \CS_Payments_Query( array( 's' => 'discount:' . $discount->code ) );
		$out            = $payments_query->get_payments();

		$this->assertEquals( 1, count( $out ) );
		$this->assertEquals( $payment_id, $out[0]->ID );

		\CS_Helper_Payment::delete_payment( $payment_id );

		$payments_query = new \CS_Payments_Query( array( 's' => 'discount:' . $discount->code ) );
		$out            = $payments_query->get_payments();

		$this->assertEquals( 0, count( $out ) );
	}

	public function test_payments_query_count_payments() {
		$payments = new \CS_Payments_Query( array( 'count' => true ) );
		$count    = $payments->get_payments();

		$this->assertTrue( is_numeric( $count ) );
		$this->assertEquals( 1, $count );
	}

	public function test_cs_get_payment_by() {
		$payment = cs_get_payment_by( 'id', self::$payment->ID );
		$this->assertObjectHasAttribute( 'ID', $payment );

		$payment = cs_get_payment_by( 'key', self::$payment->key );
		$this->assertObjectHasAttribute( 'ID', $payment );
	}

	public function test_fake_insert_payment() {
		$this->assertFalse( cs_insert_payment() );
	}

	public function test_payment_completed_flag_not_exists() {
		$completed_date = cs_get_payment_completed_date( self::$payment->ID );
		$this->assertEmpty( $completed_date );
	}

	public function test_update_payment_status() {
		cs_update_payment_status( self::$payment->ID, 'complete' );

		$out = cs_get_payments();

		$this->assertEquals( 'complete', $out[0]->status );
	}

	public function test_update_payment_status_with_invalid_id() {
		$updated = cs_update_payment_status( 12121212, 'complete' );

		$this->assertFalse( $updated );
	}

	public function test_check_for_existing_payment() {
		cs_update_payment_status( self::$payment->ID, 'complete' );

		$this->assertTrue( cs_check_for_existing_payment( self::$payment->ID ) );
	}

	public function test_get_payment_status() {
		$this->assertEquals( 'pending', cs_get_payment_status( self::$payment->ID ) );

		$this->assertEquals( 'pending', cs_get_payment_status( self::$payment ) );
		$this->assertFalse( cs_get_payment_status( 1212121212121 ) );
	}

	/**
	 * FIXME: We have removed translations for now, skipping test.
	 * @return void
	 *

	public function test_get_payment_status_translated() {
		add_filter( 'locale', function() { return 'fr_FR'; }, 10 );
		$lang_file = CS_PLUGIN_DIR . 'languages/commercestore-fr_FR.mo';
		load_textdomain( 'commercestore', $lang_file );
		$this->assertEquals( 'pending', cs_get_payment_status( self::$payment->ID ) );
		$payment = new \CS_Payment( self::$payment->ID );
		$this->assertEquals( 'En attente', cs_get_payment_status_label( $payment->post_status ) );
		$this->assertEquals( 'pending', cs_get_payment_status( $payment ) );
		$this->assertFalse( cs_get_payment_status( 1212121212121 ) );
		remove_filter( 'locale', function() { return 'fr_FR'; }, 10 );
		unload_textdomain( 'commercestore' );
	}
	*/

	public function test_get_payment_status_label() {
		$this->assertEquals( 'Pending', cs_get_payment_status( self::$payment->ID, true ) );

		$this->assertEquals( 'Pending', cs_get_payment_status( self::$payment, true ) );
	}

	public function test_get_payment_statuses() {
		$out = cs_get_payment_statuses();

		$expected = array(
			'pending'            => 'Pending',
			'complete'           => 'Completed',
			'refunded'           => 'Refunded',
			'partially_refunded' => 'Partially Refunded',
			'failed'             => 'Failed',
			'revoked'            => 'Revoked',
			'abandoned'          => 'Abandoned',
			'processing'         => 'Processing',

			// Subscriptions are now core
			'preapproval'		 => 'Preapproved',
			'preapproval_pending'=> 'Preappproval Pending',
			'cancelled'	         => 'Cancelled',
			'cs_subscription'	 => 'Renewal',
		);

		$this->assertEquals( $expected, $out );
	}

	public function test_get_payment_status_keys() {
		$out = cs_get_payment_status_keys();

		$expected = array(
			'pending'            => __( 'Pending',    'commercestore' ),
			'processing'         => __( 'Processing', 'commercestore' ),
			'complete'           => __( 'Completed',  'commercestore' ),
			'refunded'           => __( 'Refunded',   'commercestore' ),
			'partially_refunded' => __( 'Partially Refunded', 'commercestore' ),
			'revoked'            => __( 'Revoked',    'commercestore' ),
			'failed'             => __( 'Failed',     'commercestore' ),
			'abandoned'          => __( 'Abandoned',  'commercestore' ),

			// Subscriptions are now core
			'preapproval'		 => __( 'Preapproved','commercestore' ),
			'preapproval_pending'=> __( 'Preappproval Pending', 'commercestore' ),
			'cancelled'	         => __( 'Cancelled', 'commercestore' ),
			'cs_subscription'	 => __( 'Renewal', 'commercestore' ),
		);

		asort( $expected );

		$expected = array_keys( $expected );

		$this->assertInternalType( 'array', $out );
		$this->assertEquals( $expected, $out );
	}

	public function test_delete_purchase() {
		cs_delete_purchase( self::$payment->ID );

		$this->assertEmpty( cs_get_payments() );
	}

	public function test_get_payment_completed_date() {
		cs_update_payment_status( self::$payment->ID, 'complete' );
		$payment = new \CS_Payment( self::$payment->ID );

		$this->assertInternalType( 'string', $payment->completed_date );

		$expected_date_range = array(
			date( 'Y-m-d H:i', strtotime( "-2 seconds" ) ),
			date( 'Y-m-d H:i', strtotime( "-1 second" ) ),
			date( 'Y-m-d H:i' ),
			date( 'Y-m-d H:i', strtotime( "+1 second" ) ),
			date( 'Y-m-d H:i', strtotime( "+2 seconds" ) ),
		);

		$this->assertTrue( in_array( date( 'Y-m-d H:i', strtotime( $payment->completed_date ) ), $expected_date_range ) );
	}

	public function test_get_payment_completed_date_bc() {
		cs_update_payment_status( self::$payment->ID, 'complete' );
		$completed_date = cs_get_payment_completed_date( self::$payment->ID );

		$this->assertInternalType( 'string', $completed_date );

		$expected_date_range = array(
			date( 'Y-m-d H:i', strtotime( "-2 seconds" ) ),
			date( 'Y-m-d H:i', strtotime( "-1 second" ) ),
			date( 'Y-m-d H:i' ),
			date( 'Y-m-d H:i', strtotime( "+1 second" ) ),
			date( 'Y-m-d H:i', strtotime( "+2 seconds" ) ),
		);

		$this->assertTrue( in_array( date( 'Y-m-d H:i', strtotime( $completed_date ) ), $expected_date_range ) );
	}

	public function test_get_payment_number() {
		global $cs_options;
		$cs_options['enable_sequential'] = 1;
		$cs_options['sequential_prefix'] = 'CS-';

		$payment_id = \CS_Helper_Payment::create_simple_payment();

		$this->assertInternalType( 'int', cs_get_next_payment_number() );
		$this->assertInternalType( 'string', cs_format_payment_number( cs_get_next_payment_number() ) );
		$this->assertEquals( 'CS-2', cs_format_payment_number( cs_get_next_payment_number() ) );

		$payment             = new \CS_Payment( $payment_id );
		$last_payment_number = cs_remove_payment_prefix_postfix( $payment->number );
		$this->assertEquals( 1, $last_payment_number );
		$this->assertEquals( 'CS-1', $payment->number );
		$this->assertEquals( 2, cs_get_next_payment_number() );

		// Now disable sequential and ensure values come back as expected
		$cs_options['enable_sequential'] = 0;

		$payment = new \CS_Payment( $payment_id );
		$this->assertEquals( $payment_id, $payment->number );
	}

	public function test_get_payment_transaction_id_bc() {
		$this->assertEquals( self::$payment->transaction_id, cs_get_payment_transaction_id( self::$payment->ID ) );
	}

	public function test_get_payment_transaction_id_legacy() {
		$this->assertEquals( self::$payment->transaction_id, cs_paypal_get_payment_transaction_id( self::$payment->ID ) );
	}

	public function test_get_payment_meta() {
		// Test by getting the payment key with three different methods
		$this->assertEquals( self::$payment->key, self::$payment->get_meta( '_cs_payment_purchase_key' ) );
		$this->assertEquals( self::$payment->key, cs_get_payment_meta( self::$payment->ID, '_cs_payment_purchase_key', true ) );

		// Try and retrieve the transaction ID
		$this->assertEquals( self::$payment->transaction_id, self::$payment->get_meta( '_cs_payment_transaction_id' ) );

		$this->assertEquals( self::$payment->email, self::$payment->get_meta( '_cs_payment_user_email' ) );
	}

	public function test_get_payment_meta_bc() {
		// Test by getting the payment key with three different methods
		$this->assertEquals( self::$payment->key, cs_get_payment_meta( self::$payment->ID, '_cs_payment_purchase_key' ) );
		$this->assertEquals( self::$payment->key, cs_get_payment_meta( self::$payment->ID, '_cs_payment_purchase_key', true ) );
		$this->assertEquals( self::$payment->key, cs_get_payment_key( self::$payment->ID ) );

		// Try and retrieve the transaction ID
		$this->assertEquals( self::$payment->transaction_id, cs_get_payment_meta( self::$payment->ID, '_cs_payment_transaction_id' ) );

		$user_info = cs_get_payment_meta_user_info( self::$payment->ID );
		$this->assertEquals( $user_info['email'], cs_get_payment_meta( self::$payment->ID, '_cs_payment_user_email' ) );
	}

	public function test_update_payment_meta() {
		$payment = new \CS_Payment( self::$payment->ID );
		$this->assertEquals( $payment->key, $payment->get_meta( '_cs_payment_purchase_key' ) );

		$new_value = 'test12345';
		$this->assertNotEquals( $payment->key, $new_value );

		$payment->key = $new_value;
		$ret          = $payment->save();

		$this->assertTrue( $ret );
		$this->assertEquals( $new_value, $payment->key );

		$payment->email = 'test@test.com';
		$ret            = $payment->save();

		$this->assertTrue( $ret );

		$this->assertEquals( 'test@test.com', $payment->email );
	}

	public function test_update_payment_meta_bc() {
		$old_value = self::$payment->key;
		$this->assertEquals( $old_value, cs_get_payment_meta( self::$payment->ID, '_cs_payment_purchase_key' ) );

		$new_value = 'test12345';
		$this->assertNotEquals( $old_value, $new_value );

		$ret = cs_update_payment_meta( self::$payment->ID, '_cs_payment_purchase_key', $new_value );

		$this->assertTrue( $ret );

		$this->assertEquals( $new_value, cs_get_payment_meta( self::$payment->ID, '_cs_payment_purchase_key' ) );

		$ret = cs_update_payment_meta( self::$payment->ID, '_cs_payment_user_email', 'test@test.com' );

		$this->assertTrue( $ret );

		$user_info = cs_get_payment_meta_user_info( self::$payment->ID );
		$this->assertEquals( 'test@test.com', cs_get_payment_meta( self::$payment->ID, '_cs_payment_user_email' ) );
	}

	public function test_update_payment_data() {
		self::$payment->date = date( 'Y-m-d H:i:s' );
		self::$payment->save();
		$meta = self::$payment->get_meta();

		$this->assertSame( self::$payment->date, $meta['date'] );
	}

	public function test_get_payment_currency_code() {
		$this->assertEquals( 'USD', self::$payment->currency );
		$this->assertEquals( 'US Dollars (&#36;)', cs_get_payment_currency( self::$payment->ID ) );

		$total1 = cs_currency_filter( cs_format_amount( self::$payment->total ), self::$payment->currency );
		$total2 = cs_currency_filter( cs_format_amount( self::$payment->total ) );

		$this->assertEquals( '&#36;120.00', $total1 );
		$this->assertEquals( '&#36;120.00', $total2 );
	}

	public function test_get_payment_currency_code_bc() {
		$this->assertEquals( 'USD', cs_get_payment_currency_code( self::$payment->ID ) );
		$this->assertEquals( 'US Dollars (&#36;)', cs_get_payment_currency( self::$payment->ID ) );

		$total1 = cs_currency_filter( cs_format_amount( cs_get_payment_amount( self::$payment->ID ) ), cs_get_payment_currency_code( self::$payment->ID ) );
		$total2 = cs_currency_filter( cs_format_amount( cs_get_payment_amount( self::$payment->ID ) ) );

		$this->assertEquals( '&#36;120.00', $total1 );
		$this->assertEquals( '&#36;120.00', $total2 );
	}

	public function test_is_guest_payment() {
		$this->assertFalse( cs_is_guest_payment( self::$payment->ID ) );

		$guest_payment_id = \CS_Helper_Payment::create_simple_guest_payment();
		$this->assertTrue( cs_is_guest_payment( $guest_payment_id ) );
	}

	public function test_get_payment() {
		$payment = cs_get_payment( self::$payment->ID );
		$this->assertTrue( property_exists( $payment, 'ID' ) );
		$this->assertTrue( property_exists( $payment, 'cart_details' ) );
		$this->assertTrue( property_exists( $payment, 'user_info' ) );
		$this->assertEquals( $payment->ID, self::$payment->ID );
		$payment->transaction_id = 'a1b2c3d4e5';
		$payment->save();

		$payment_2 = cs_get_payment( 'a1b2c3d4e5', true );
		$this->assertTrue( property_exists( $payment_2, 'ID' ) );
		$this->assertTrue( property_exists( $payment_2, 'cart_details' ) );
		$this->assertTrue( property_exists( $payment_2, 'user_info' ) );
		$this->assertEquals( $payment_2->ID, self::$payment->ID );
	}

	public function test_payments_date_query() {
		/**
		 * @internal There's a caching issue when running the test suite so we have to clear everything at the beginning
		 *           of this test.
		 */
		$component = cs_get_component_interface( 'order', 'table' );

		if ( $component instanceof \CS\Database\Table ) {
			$component->truncate();
		}

		$payment_id_1 = \CS_Helper_Payment::create_simple_payment_with_date( date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ) );
		\CS_Helper_Payment::create_simple_payment_with_date( date( 'Y-m-d H:i:s', strtotime( '-4 days' ) ) );
		\CS_Helper_Payment::create_simple_payment_with_date( date( 'Y-m-d H:i:s', strtotime( '-5 days' ) ) );
		\CS_Helper_Payment::create_simple_payment_with_date( date( 'Y-m-d H:i:s', strtotime( '-1 month' ) ) );

		$payments_query = new \CS_Payments_Query( array(
			'start_date' => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			'end_date'   => date( 'Y-m-d H:i:s' ),
			'output'     => 'orders',
		) );

		$payments = $payments_query->get_payments();

		$this->assertEquals( 1, count( $payments ) );
		$this->assertEquals( $payment_id_1, $payments[0]->ID );

		self::$payment = cs_get_payment( \CS_Helper_Payment::create_simple_payment() );
	}

	public function test_recovering_payment_guest_to_guest() {
		/**
		 * @internal This call is necessary as we need to flush the meta cache.
		 */
		wp_cache_flush();

		$initial_purchase_data = array(
			'price'        => 299.0,
			'date'         => date( 'Y-m-d H:i:s' ),
			'user_email'   => 'bruce@waynefoundation.org',
			'purchase_key' => '186c2fb5402d756487bd4b6192d59bc2',
			'currency'     => 'USD',
			'downloads'    =>
				array(
					0 =>
						array(
							'id'       => '1906',
							'options'  =>
								array(
									'price_id' => '1',
								),
							'quantity' => 1,
						),
				),
			'user_info'    =>
				array(
					'id'         => 0,
					'email'      => 'bruce@waynefoundation.org',
					'first_name' => 'Bruce',
					'last_name'  => 'Wayne',
					'discount'   => 'none',
					'address'    =>
						array(),
				),
			'cart_details' =>
				array(
					0 =>
						array(
							'name'        => 'Test Product 1',
							'id'          => '1906',
							'item_number' =>
								array(
									'id'       => '1906',
									'options'  =>
										array(
											'price_id' => '1',
										),
									'quantity' => 1,
								),
							'item_price'  => 299.0,
							'quantity'    => 1,
							'discount'    => 0.0,
							'subtotal'    => 299.0,
							'tax'         => 0.0,
							'fees'        =>
								array(),
							'price'       => 299.0,
						),
				),
			'gateway'      => 'paypal',
			'status'       => 'pending',
		);

		$initial_payment_id = cs_insert_payment( $initial_purchase_data );
		CS()->session->set( 'cs_resume_payment', $initial_payment_id );

		$recovery_purchase_data = array(
			'price'        => 299.0,
			'date'         => '2017-08-15 18:10:37',
			'user_email'   => 'batman@thebatcave.co',
			'purchase_key' => '4f2b5cda76c2a997996f4cf8b68255ed',
			'currency'     => 'USD',
			'downloads'    =>
				array(
					0 =>
						array(
							'id'       => '1906',
							'options'  =>
								array(
									'price_id' => '1',
								),
							'quantity' => 1,
						),
				),
			'user_info'    =>
				array(
					'id'         => 0,
					'email'      => 'batman@thebatcave.co',
					'first_name' => 'Batman',
					'last_name'  => '',
					'discount'   => 'none',
					'address'    =>
						array(),
				),
			'cart_details' =>
				array(
					0 =>
						array(
							'name'        => 'Test Product 1',
							'id'          => '1906',
							'item_number' =>
								array(
									'id'       => '1906',
									'options'  =>
										array(
											'price_id' => '1',
										),
									'quantity' => 1,
								),
							'item_price'  => 299.0,
							'quantity'    => 1,
							'discount'    => 0.0,
							'subtotal'    => 299.0,
							'tax'         => 0.0,
							'fees'        =>
								array(),
							'price'       => 299.0,
						),
				),
			'gateway'      => 'paypal',
			'status'       => 'pending',
		);

		$recovery_payment_id = cs_insert_payment( $recovery_purchase_data );
		$this->assertSame( $initial_payment_id, $recovery_payment_id );

		$payment           = cs_get_payment( $recovery_payment_id );
		$payment_customer  = new \CS_Customer( $payment->customer_id );
		$recovery_customer = new \CS_Customer( 'batman@thebatcave.co' );

		$this->assertSame( $payment_customer->id, $recovery_customer->id );
	}
}
