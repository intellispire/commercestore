<?php
namespace CS\Discounts;

/**
 * Tests for Discounts API.
 *
 * @covers \CS_Discount
 * @group cs_discounts
 *
 * @coversDefaultClass \CS_Discount
 */
class Tests_Discounts extends \CS_UnitTestCase {

	/**
	 * Download test fixture.
	 *
	 * @var \WP_Post
	 * @static
	 */
	protected static $download;

	/**
	 * Discount ID.
	 *
	 * @var int
	 * @static
	 */
	protected static $discount_id;

	/**
	 * Discount object test fixture.
	 *
	 * @var \CS_Discount
	 * @static
	 */
	protected static $discount;

	/**
	 * Flat discount test fixture.
	 *
	 * @var int
	 * @static
	 */
	protected static $flatdiscount_id;

	/**
	 * Negative discount test fixture.
	 *
	 * @var int
	 * @static
	 */
	protected static $negativediscount_id;

	/**
	 * Set up fixtures once.
	 */
	public static function wpsetUpBeforeClass() : void  {
		self::$download = \CS_Helper_Download::create_simple_download();

		self::$discount_id         = \CS_Helper_Discount::create_simple_percent_discount();
		self::$negativediscount_id = \CS_Helper_Discount::create_simple_negative_percent_discount();
		self::$flatdiscount_id     = \CS_Helper_Discount::create_simple_flat_discount();

		self::$discount = cs_get_discount( self::$discount_id );
	}

	public function set_up() {
		parent::set_up();
	}

	/**
	 * Run after each test to empty the cart and reset the test store.
	 *
	 * @access public
	 */
	public function tear_down() {
		cs_empty_cart();

		parent::tear_down();
	}

	/**
	 * @covers ::setup_discount()
	 */
	public function test_discount_instantiated() {
		$this->assertGreaterThan( 0, self::$discount->id );
	}

	/**
	 * @covers ::setup_discount()
	 */
	public function test_id_is_0_when_no_id_is_passed() {
		$d = new \CS_Discount();

		$this->assertTrue( 0 === $d->id );
	}

	/**
	 * @covers ::setup_discount()
	 */
	public function test_discount_id_matches_id() {
		$this->assertEquals( self::$discount->id, self::$discount_id );
	}

	/**
	 * @covers ::setup_discount()
	 */
	public function test_discount_id_matches_capital_ID() {
		$this->assertEquals( self::$discount->ID, self::$discount_id );
	}

	/**
	 * @covers ::setup_discount()
	 */
	public function test_get_discount_name() {
		$this->assertEquals( '20 Percent Off', self::$discount->name );
	}

	/**
	 * @covers ::setup_discount()
	 */
	public function test_get_discount_name_by_property() {
		$this->assertEquals( '20OFF', self::$discount->code );
	}

	/**
	 * @covers ::get_code()
	 */
	public function test_get_discount_name_by_method() {
		$this->assertEquals( '20OFF', self::$discount->get_code() );
	}

	/**
	 * @covers ::get_status()
	 */
	public function test_get_discount_status_by_property() {
		$this->assertEquals( 'active', self::$discount->status );
	}

	/**
	 * @covers ::get_status()
	 */
	public function test_get_discount_status_by_method() {
		$this->assertEquals( 'active', self::$discount->get_status() );
	}

	/**
	 * @covers ::get_expiration()
	 */
	public function test_get_discount_expiration_by_property_backcompat() {
		$this->assertEquals( date( 'Y-m-d', time() ) . ' 23:59:59', self::$discount->expiration );
	}

	/**
	 * @covers ::get_expiration()
	 */
	public function test_get_discount_expiration_by_method_backcompat() {
		$this->assertEquals( date( 'Y-m-d', time() ) . ' 23:59:59', self::$discount->get_expiration() );
	}

	/**
	 * @covers ::end_date
	 */
	public function test_get_discount_end_date_by_property() {
		$this->assertEquals( date( 'Y-m-d', time() ) . ' 23:59:59', self::$discount->end_date );
	}

	/**
	 * @covers ::get_uses()
	 */
	public function test_get_discount_uses_by_property() {
		$this->assertEquals( 54, self::$discount->uses );
	}

	/**
	 * @covers ::get_uses()
	 */
	public function test_get_discount_uses_by_method() {
		$this->assertEquals( 54, self::$discount->get_uses() );
	}

	/**
	 * @covers ::get_max_uses()
	 */
	public function test_get_discount_max_uses_by_property() {
		$this->assertEquals( 10, self::$discount->max_uses );
	}

	/**
	 * @covers ::get_max_uses()
	 */
	public function test_get_discount_max_uses_by_method() {
		$this->assertEquals( 10, self::$discount->get_max_uses() );
	}

	/**
	 * @covers ::get_min_price()
	 */
	public function test_get_discount_min_price_by_property() {
		$this->assertEquals( 128, self::$discount->min_charge_amount );
	}

	/**
	 * @covers ::get_min_price()
	 */
	public function test_get_discount_min_price_by_method() {
		$this->assertEquals( 128, self::$discount->get_min_price() );
	}

	/**
	 * @covers ::get_is_single_use()
	 */
	public function test_get_discount_is_single_use_should_return_false() {
		$this->assertFalse( self::$discount->get_is_single_use() );
	}

	/**
	 * @covers ::get_once_per_customer()
	 */
	public function test_get_discount_is_once_per_customer_should_return_false() {
		$this->assertFalse( self::$discount->get_once_per_customer() );
	}

	/**
	 * @covers ::exists()
	 */
	public function test_discount_exists_should_return_true() {
		$this->assertTrue( self::$discount->exists() );
	}

	/**
	 * @covers ::get_type()
	 */
	public function test_get_discount_type_by_property() {
		$this->assertEquals( 'percent', self::$discount->type );
	}

	/**
	 * @covers ::get_type()
	 */
	public function test_get_discount_type_by_method() {
		$this->assertEquals( 'percent', self::$discount->get_type() );
	}

	/**
	 * @covers ::get_type()
	 */
	public function test_get_discount_type_of_flat_discount() {
		$d = new \CS_Discount( self::$flatdiscount_id );
		$this->assertEquals( 'flat', $d->type );
	}

	/**
	 * @covers ::get_amount()
	 */
	public function test_get_discount_amount_by_property() {
		$this->assertEquals( '20', self::$discount->amount );
	}

	/**
	 * @covers ::get_amount()
	 */
	public function test_get_discount_amount_by_method() {
		$this->assertEquals( '20', self::$discount->get_amount() );
	}

	/**
	 * @covers ::get_product_reqs()
	 */
	public function test_get_discount_product_requirements_by_method() {
		$this->assertSame( array(), self::$discount->get_product_reqs() );
	}

	/**
	 * @covers ::get_product_reqs()
	 */
	public function test_get_discount_product_requirements_by_property() {
		$this->assertSame( array(), self::$discount->product_reqs );
	}

	/**
	 * @covers ::get_excluded_products()
	 */
	public function test_get_discount_excluded_products_by_method() {
		$this->assertSame( array(), self::$discount->get_excluded_products() );
	}

	/**
	 * @covers ::get_excluded_products()
	 */
	public function test_get_discount_excluded_products_by_property() {
		$this->assertSame( array(), self::$discount->excluded_products );
	}

	/**
	 * @covers ::save()
	 * @covers ::add()
	 */
	public function test_discount_save() {
		$discount = new \CS_Discount();
		$discount->code = '30FLAT';
		$discount->name = '$30 Off';
		$discount->type = 'flat';
		$discount->amount = '30';

		$discount->save();

		$this->assertGreaterThan( 0, (int) $discount->id );
	}

	/**
	 * @covers ::add()
	 * @covers ::sanitize_columns()
	 * @covers ::convert_legacy_args()
	 */
	public function test_discount_add() {
		$args = array(
			'code'   => '30FLAT',
			'name'   => '$30 Off',
			'type'   => 'flat',
			'amount' => 30,
		);

		$discount = new \CS_Discount();
		$discount->add( $args );

		$this->assertGreaterThan( 0, $discount->id );
	}

	/**
	 * @covers ::update()
	 * @covers ::sanitize_columns()
	 * @covers ::convert_legacy_args()
	 */
	public function test_discount_update_type() {
		$args = array(
			'type'   => 'flat',
			'amount' => 50,
		);

		self::$discount->update( $args );

		$this->assertEquals( 'flat', self::$discount->type );
	}

	/**
	 * @covers ::update()
	 * @covers ::sanitize_columns()
	 * @covers ::convert_legacy_args()
	 */
	public function test_discount_update_amount() {
		$args = array(
			'amount' => 50,
		);

		self::$discount->update( $args );

		$this->assertEquals( 50.0, self::$discount->amount );
	}

	/**
	 * @covers ::update_status()
	 * @covers ::get_status()
	 */
	public function test_discount_update_status_with_no_args() {
		self::$discount->update_status();

		$this->assertEquals( 'active', self::$discount->status );
	}

	/**
	 * @covers ::update_status()
	 * @covers ::get_status()
	 */
	public function test_discount_update_status_to_inactive() {
		self::$discount->update_status( 'inactive' );

		$this->assertEquals( 'inactive', self::$discount->status );
	}

	/**
	 * @covers ::is_product_requirements_met()
	 */
	public function test_discount_is_product_requirements_met() {
		$args = array(
			'product_reqs' => array( self::$download->ID ),
		);

		cs_update_discount( self::$discount_id, $args );

		cs_add_to_cart( self::$download->ID );

		$this->assertTrue( self::$discount->is_product_requirements_met() );
	}

	/**
	 * @covers cs_validate_discount
	 */
	public function test_cs_validate_discount_product_requirements_any_all_in_array() {
		$products = array( self::$download->ID, 100 );
		$args     = array(
			'product_reqs'      => $products,
			'product_condition' => 'any',
			'max_uses'          => 10000,
		);

		cs_update_discount( self::$discount_id, $args );
		$this->assertTrue( cs_validate_discount( self::$discount_id, $products ) );
	}

	/**
	 * @covers cs_validate_discount
	 */
	public function test_cs_validate_discount_product_requirements_any_none_in_array() {
		$products = array( self::$download->ID, 100 );
		$args     = array(
			'product_reqs'      => $products,
			'product_condition' => 'any',
			'max_uses'          => 10000,
		);

		cs_update_discount( self::$discount_id, $args );
		$this->assertFalse( cs_validate_discount( self::$discount_id, array( 123 ) ) );
	}

	/**
	 * @covers cs_validate_discount
	 */
	public function test_cs_validate_discount_product_requirements_any_one_in_array() {
		$products = array( self::$download->ID, 100 );
		$args     = array(
			'product_reqs'      => $products,
			'product_condition' => 'any',
			'max_uses'          => 10000,
		);

		cs_update_discount( self::$discount_id, $args );
		$this->assertTrue( cs_validate_discount( self::$discount_id, array( self::$download->ID ) ) );
	}

	/**
	 * @covers cs_validate_discount
	 */
	public function test_cs_validate_discount_product_requirements_all_all_in_array() {
		$products = array( self::$download->ID, 100 );
		$args     = array(
			'product_reqs'      => $products,
			'product_condition' => 'all',
			'max_uses'          => 10000,
		);

		cs_update_discount( self::$discount_id, $args );
		$this->assertTrue( cs_validate_discount( self::$discount_id, $products ) );
	}

	/**
	 * @covers cs_validate_discount
	 */
	public function test_cs_validate_discount_product_requirements_all_none_in_array() {
		$products = array( self::$download->ID, 100 );
		$args     = array(
			'product_reqs'      => $products,
			'product_condition' => 'all',
			'max_uses'          => 10000,
		);

		cs_update_discount( self::$discount_id, $args );
		$this->assertFalse( cs_validate_discount( 123 ) );
	}

	/**
	 * @covers cs_validate_discount
	 */
	public function test_cs_validate_discount_product_requirements_all_one_in_array() {
		$products = array( self::$download->ID, 100 );
		$args     = array(
			'product_reqs'      => $products,
			'product_condition' => 'all',
			'max_uses'          => 10000,
		);

		cs_update_discount( self::$discount_id, $args );
		$this->assertFalse( cs_validate_discount( self::$discount_id, array( self::$download->ID ) ) );
	}

	/**
	 * @covers cs_validate_discount
	 */
	public function test_cs_validate_discount_excluded_products_all_in_array() {
		$products = array( self::$download->ID, 100 );
		$args     = array(
			'product_reqs'      => array(),
			'max_uses'          => 10000,
			'excluded_products' => $products,
		);

		cs_update_discount( self::$discount_id, $args );
		$this->assertFalse( cs_validate_discount( self::$discount_id, $products ) );
	}

	/**
	 * @covers cs_validate_discount
	 */
	public function test_cs_validate_discount_excluded_products_none_in_array() {
		$products = array( self::$download->ID, 100 );
		$args     = array(
			'product_reqs'      => array(),
			'max_uses'          => 10000,
			'excluded_products' => $products,
		);

		cs_update_discount( self::$discount_id, $args );
		$this->assertTrue( cs_validate_discount( self::$discount_id, array( 546 ) ) );
	}

	/**
	 * @covers cs_validate_discount
	 */
	public function test_cs_validate_discount_excluded_products_one_in_array() {
		$products = array( self::$download->ID, 100 );
		$args     = array(
			'product_reqs'      => array(),
			'max_uses'          => 10000,
			'excluded_products' => $products,
		);

		cs_update_discount( self::$discount_id, $args );
		$this->assertFalse( cs_validate_discount( self::$discount_id, array( self::$download->ID ) ) );
	}

	/**
	 * @covers ::edit_url()
	 */
	public function test_discount_edit_url() {
		$this->assertStringContainsString( 'edit.php?post_type=download&#038;page=cs-discounts', self::$discount->edit_url() );
	}

	/**
	 * @covers ::update_meta()
	 */
	public function test_discount_update_meta() {
		cs_update_adjustment_meta( self::$discount->id, 'test_meta_key', 'test_meta_value' );

		$this->assertEquals( 'test_meta_value', cs_get_adjustment_meta( self::$discount->id, 'test_meta_key', true ) );
	}

	/**
	 * @covers ::delete_meta()
	 */
	public function test_discount_delete_meta_with_no_meta_key_should_be_false() {
		$this->assertFalse( cs_delete_adjustment_meta( self::$download->ID, '' ) );
	}

	/*
	 * Legacy tests
	 *
	 * All tests below are from before CommerceStore 3.0 when discounts were stored as wp_posts.
	 * CommerceStore 3.0 stores them in a custom table.
	 * The below tests are left here to help ensure the backwards compatibility layers work properly
	 */
	public function test_discount_created() {
		$this->assertInternalType( 'int', self::$discount_id );
	}

	public function test_addition_of_negative_discount() {
		$this->assertInternalType( 'int', self::$negativediscount_id );
	}

	public function test_addition_of_flat_discount() {
		$this->assertInternalType( 'int', self::$flatdiscount_id );
	}

	/**
	 * @covers \cs_store_discount()
	 */
	public function test_updating_discount_code() {
		$post = array(
			'name'              => '20 Percent Off',
			'type'              => 'percent',
			'amount'            => '20',
			'code'              => '20OFF',
			'product_condition' => 'all',
			'start'             => date( 'm/d/Y', time() ) . ' 00:00:00',
			'expiration'        => date( 'm/d/Y', time() ) . ' 23:59:59',
			'max'               => 10,
			'uses'              => 54,
			'min_price'         => 128,
			'status'            => 'active'
		);

		$updated_id = cs_store_discount( $post, self::$discount_id );
		$this->assertEquals( $updated_id, self::$discount_id );
	}

	/**
	 * @covers \cs_update_discount_status()
	 */
	public function test_discount_status_update_inactive() {
		$this->assertTrue( cs_update_discount_status( self::$discount_id, 'inactive' ) );
		$discount = cs_get_discount( self::$discount_id );
		$this->assertEquals( 'inactive', $discount->status );

		$this->assertTrue( cs_update_discount_status( self::$discount_id, 'active' ) );
		$discount = cs_get_discount( self::$discount_id );
		$this->assertEquals( 'active', $discount->status );
	}

	/**
	 * @covers \cs_update_discount_status()
	 */
	public function test_discount_status_update() {
		$this->assertTrue( cs_update_discount_status( self::$discount_id, 'active' ) );
	}

	/**
	 * @covers \cs_update_discount_status()
	 */
	public function test_discount_status_update_fail() {
		$this->assertFalse( cs_update_discount_status( -1 ) );
	}

	/**
	 * @covers \cs_has_active_discounts()
	 */
	public function test_discounts_exists() {
		cs_update_discount_status( self::$discount_id, 'active' );

		$this->assertTrue( cs_has_active_discounts() );
	}

	/**
	 * @covers \cs_update_discount_status()
	 * @covers \cs_is_discount_active()
	 * @covers \cs_store_discount()
	 */
	public function test_is_discount_active() {
		$this->setExpectedIncorrectUsage( 'get_post_meta()' );

		cs_update_discount_status( self::$discount_id, 'active' );

		$this->assertTrue( cs_is_discount_active( self::$discount_id, true  ) );
		$this->assertTrue( cs_is_discount_active( self::$discount_id, false ) );

		$post = array(
			'name'              => '20 Percent Off',
			'type'              => 'percent',
			'amount'            => '20',
			'code'              => '20OFFEXPIRED',
			'product_condition' => 'all',
			'start'             => date( 'm/d/Y', time() - DAY_IN_SECONDS*5 ) . ' 00:00:00',
			'expiration'        => date( 'm/d/Y', time() - DAY_IN_SECONDS*5 ) . ' 23:59:59',
			'max'               => 10,
			'uses'              => 54,
			'min_price'         => 128,
			'status'            => 'active'
		);

		$expired_discount_id = cs_store_discount( $post );

		$this->assertFalse( cs_is_discount_active( $expired_discount_id, true ) );

		$this->assertEquals( 'expired', get_post_meta( $expired_discount_id, '_cs_discount_status', true ) );
	}

	/**
	 * @covers \cs_discount_exists()
	 */
	public function test_discount_exists_helper() {
		$this->assertTrue( cs_discount_exists( self::$discount_id ) );
	}

	/**
	 * @covers \cs_update_discount_status()
	 * @covers \cs_get_discount()
	 */
	public function test_get_discount() {
		cs_update_discount_status( self::$discount_id, 'active' );

		$discount = cs_get_discount( self::$discount_id );

		$this->assertEquals( self::$discount_id, $discount->id );
		$this->assertEquals( '20 Percent Off', $discount->post_title );
		$this->assertEquals( 'active', $discount->post_status );
	}

	/**
	 * @covers \cs_get_discount_code()
	 */
	public function test_get_discount_code() {
		$this->assertSame( '20OFF', cs_get_discount_code( self::$discount_id ) );
	}

	/**
	 * @covers \cs_get_discount_start_date()
	 */
	public function test_discount_start_date() {
		$this->assertSame( date( 'Y-m-d', time() ) . ' 00:00:00', cs_get_discount_start_date( self::$discount_id ) );
	}

	/**
	 * @covers \cs_get_discount_expiration()
	 */
	public function test_discount_expiration_date() {
		$this->assertSame( date( 'Y-m-d', time() ) . ' 23:59:59', cs_get_discount_expiration( self::$discount_id ) );
	}

	/**
	 * @covers \cs_get_discount_max_uses()
	 */
	public function test_discount_max_uses() {
		$this->assertSame( 10, cs_get_discount_max_uses( self::$discount_id ) );
	}

	/**
	 * @covers \cs_get_discount_uses()
	 */
	public function test_discount_uses() {
		$this->assertSame( 54, cs_get_discount_uses( self::$discount_id ) );
	}

	/**
	 * @covers \cs_get_discount_min_price()
	 */
	public function test_discount_min_price() {
		$this->assertSame( '128.00', cs_get_discount_min_price( self::$discount_id ) );
	}

	/**
	 * @covers \cs_get_discount_amount()
	 */
	public function test_discount_amount() {
		$this->assertSame( 20.0, cs_get_discount_amount( self::$discount_id ) );
	}

	/**
	 * @covers \cs_get_discount_amount()
	 */
	public function test_discount_amount_negative() {
		$this->assertSame( -100.0, cs_get_discount_amount( self::$negativediscount_id ) );
	}

	/**
	 * @covers \cs_get_discount_type()
	 */
	public function test_discount_type() {
		$this->assertSame( 'percent', cs_get_discount_type( self::$discount_id ) );
	}

	/**
	 * @covers \cs_is_discount_not_global()
	 */
	public function test_discount_is_not_global() {
		$this->assertFalse( cs_is_discount_not_global( self::$discount_id ) );
	}

	/**
	 * @covers \cs_discount_is_single_use()
	 */
	public function test_discount_is_single_use() {
		$this->assertFalse( cs_discount_is_single_use( self::$discount_id ) );
	}

	/**
	 * @covers \cs_is_discount_started()
	 */
	public function test_discount_is_started() {
		$this->assertTrue( cs_is_discount_started( self::$discount_id ) );
	}

	/**
	 * @covers \cs_is_discount_expired()
	 */
	public function test_discount_is_expired() {
		$this->assertFalse( cs_is_discount_expired( self::$discount_id ) );
	}

	public function test_discount_is_expired_timezone_change() {
		update_option( 'gmt_offset', 25 );
		$this->assertFalse( cs_is_discount_expired( self::$discount_id ) );
		update_option( 'gmt_offset', 0 );
	}

	/**
	 * @covers \cs_is_discount_maxed_out()
	 */
	public function test_discount_is_maxed_out() {
		$this->assertTrue( cs_is_discount_maxed_out( self::$discount_id ) );
	}

	/**
	 * @covers \cs_discount_is_min_met()
	 */
	public function test_discount_is_min_met() {
		$this->assertFalse( cs_discount_is_min_met( self::$discount_id ) );
	}

	/**
	 * @covers \cs_is_discount_used()
	 * @covers ::is_used()
	 */
	public function test_discount_is_used() {
		$this->assertFalse( cs_is_discount_used( '20OFF' ) );
	}

	/**
	 * @covers ::setup_discount()
	 * @covers ::get_is_single_use()
	 * @covers ::is_used()
	 */
	public function test_is_used_case_insensitive() {
		$payment_id         = \CS_Helper_Payment::create_simple_payment();
		$payment            = cs_get_payment( $payment_id );
		$payment->discounts = '20off';
		$payment->status    = 'publish';
		$payment->save();

		$discount                = new \CS_Discount( '20OFF', true );
		$discount->is_single_use = true;
		$this->assertTrue( $discount->is_used( 'admin@example.org', false ) );
		$discount->is_single_use = false;

		\CS_Helper_Payment::delete_payment( $payment_id );
	}

	/**
	 * @covers \cs_is_discount_valid()
	 * @covers ::is_valid()
	 */
	public function test_discount_is_valid_when_purchasing() {
		$this->assertFalse( cs_is_discount_valid( '20OFF' ) );
	}

	/**
	 * @covers \cs_get_discount_id_by_code()
	 *@covers \cs_get_discount_id_by()
	 */
	public function test_discount_id_by_code() {
		$id       = cs_get_discount_id_by_code( '20OFF' );
		$discount = cs_get_discount_by( 'code', '20OFF' );

		$this->assertSame( $discount->id, $id );
	}


	/**
	 * @covers \cs_get_discounted_amount()
	 * @covers ::get_discounted_amount()
	 */
	public function test_get_discounted_amount() {
		$this->assertEquals( '432', cs_get_discounted_amount( '20OFF',  '540' ) );
		$this->assertEquals( '150', cs_get_discounted_amount( 'DOUBLE', '75'  ) );
		$this->assertEquals( '10',  cs_get_discounted_amount( '10FLAT', '20'  ) );

		// Test that an invalid Code returns the base price
		$this->assertEquals( '10', cs_get_discounted_amount( 'FAKEDISCOUNT', '10' ) );
	}

	/**
	 * @covers \cs_get_discount_id_by_code()
	 * @covers \cs_get_discount_uses()
	 * @covers \cs_increase_discount_usage()
	 * @covers ::increase_usage()
	 */
	public function test_increase_discount_usage() {
		$id   = cs_get_discount_id_by_code( '20OFF' );
		$uses = cs_get_discount_uses( $id );

		$increased = cs_increase_discount_usage( '20OFF' );
		$this->assertequals( $increased, (int) $uses + 1 );

		// Test missing codes
		$this->assertFalse( cs_increase_discount_usage( 'INVALIDDISCOUNTCODE' ) );
	}

	/**
	 * @covers _cs_discount_update_meta_backcompat()
	 * @covers \cs_get_discount_code()
	 * @covers \cs_increase_discount_usage()
	 */
	public function test_discount_inactive_at_max() {
		$this->setExpectedIncorrectUsage( 'get_post_meta()' );
		$this->setExpectedIncorrectUsage( 'add_post_meta()/update_post_meta()' );

		update_post_meta( self::$discount_id, '_cs_discount_status', 'active' );

		$code = cs_get_discount_code( self::$discount_id );

		update_post_meta( self::$discount_id, '_cs_discount_max', 10 );
		update_post_meta( self::$discount_id, '_cs_discount_uses', 9 );

		cs_increase_discount_usage( $code );

		$this->assertEquals( 'inactive', get_post_meta( self::$discount_id, '_cs_discount_status', true ) );
	}

	/**
	 * @covers _cs_discount_update_meta_backcompat()
	 * @covers \cs_get_discount_code()
	 * @covers \cs_increase_discount_usage()
	 * @covers ::decrease_usage()
	 */
	public function test_discount_active_after_decreasing_at_max() {
		$this->setExpectedIncorrectUsage( 'get_post_meta()' );
		$this->setExpectedIncorrectUsage( 'add_post_meta()/update_post_meta()' );

		update_post_meta( self::$discount_id, '_cs_discount_max', 10 );
		update_post_meta( self::$discount_id, '_cs_discount_uses', 10 );
		update_post_meta( self::$discount_id, '_cs_discount_status', 'inactive' );

		$code = cs_get_discount_code( self::$discount_id );

		cs_decrease_discount_usage( $code );

		$this->assertEquals( 'active', get_post_meta( self::$discount_id, '_cs_discount_status', true ) );
	}

	/**
	 * @covers \cs_get_discount_id_by_code()
	 * @covers \cs_get_discount_uses()
	 * @covers \cs_decrease_discount_usage()
	 */
	public function test_decrease_discount_usage() {
		$id   = cs_get_discount_id_by_code( '20OFF' );
		$uses = cs_get_discount_uses( $id );

		$decreased = cs_decrease_discount_usage( '20OFF' );
		$this->assertSame( $decreased, (int) $uses - 1 );

		// Test missing codes
		$this->assertFalse( cs_decrease_discount_usage( 'INVALIDDISCOUNTCODE' ) );
	}

	/**
	 * @covers _cs_discount_post_meta_bc_filter()
	 * @covers \cs_format_discount_rate()
	 */
	public function test_formatted_discount_amount() {
		$this->setExpectedIncorrectUsage( 'get_post_meta()' );

		$rate = get_post_meta( self::$discount_id, '_cs_discount_amount', true );
		$this->assertSame( '20.00%', cs_format_discount_rate( 'percent', $rate ) );
	}

	/**
	 * @covers \cs_get_discount_by()
	 */
	public function test_cs_get_discount_by() {
		$discount = cs_get_discount_by( 'id', self::$discount_id );

		$this->assertEquals( $discount->id,    self::$discount_id );
		$this->assertEquals( '20 Percent Off', cs_get_discount_by( 'code', '20OFF'          )->post_title );
		$this->assertEquals( $discount->id,    cs_get_discount_by( 'code', '20OFF'          )->id         );
		$this->assertEquals( $discount->id,    cs_get_discount_by( 'name', '20 Percent Off' )->id         );
	}

	/**
	 * @covers \cs_get_discount_amount()
	 * @covers \cs_format_discount_rate()
	 */
	public function test_formatted_discount_amount_negative() {
		$amount = cs_get_discount_amount( self::$negativediscount_id );
		$this->assertSame( '-100.00%', cs_format_discount_rate( 'percent', $amount ) );
	}

	/**
	 * @covers \cs_get_discount_amount()
	 * @covers \cs_format_discount_rate()
	 */
	public function test_formatted_discount_amount_flat() {
		$amount = cs_get_discount_amount( self::$flatdiscount_id );

		$this->assertSame( '&#36;10.00', cs_format_discount_rate( 'flat', $amount ) );
	}

	/**
	 * @covers \cs_get_discount_excluded_products()
	 * @covers ::get_excluded_products()
	 */
	public function test_discount_excluded_products() {
		$this->assertInternalType( 'array', cs_get_discount_excluded_products( self::$discount_id ) );
	}

	/**
	 * @covers \cs_get_discount_product_reqs()
	 * @covers ::get_product_reqs()
	 */
	public function test_discount_product_reqs() {
		$this->assertInternalType( 'array', cs_get_discount_product_reqs( self::$discount_id ) );
	}

	/**
	 * @covers \cs_delete_discount()
	 * @covers \cs_get_discount()
	 */
	public function test_deletion_of_discount_should_be_false_because_use_count_greater_than_1() {
		cs_delete_discount( self::$discount_id );

		$this->assertInstanceOf( 'CS_Discount', cs_get_discount( self::$discount_id ) );

		cs_delete_discount( self::$negativediscount_id );

		$this->assertInstanceOf( 'CS_Discount', cs_get_discount( self::$negativediscount_id ) );
	}

	/**
	 * @covers \cs_set_cart_discount()
	 * @covers \cs_get_discount_code()
	 */
	public function test_set_discount() {
		CS()->session->set( 'cart_discounts', null );

		cs_add_to_cart( self::$download->ID );

		$this->assertEquals( '20.00', cs_get_cart_total() );

		cs_set_cart_discount( cs_get_discount_code( self::$discount_id ) );
		$this->assertEquals( '16.00', cs_get_cart_total() );
	}

	/**
	 * @covers \cs_set_cart_discount()
	 */
	public function test_set_multiple_discounts() {
		$this->setExpectedIncorrectUsage( 'add_post_meta()/update_post_meta()' );

		CS()->session->set( 'cart_discounts', null );

		cs_update_option( 'allow_multiple_discounts', true );

		cs_add_to_cart( self::$download->ID );

		$this->assertEquals( '20.00', cs_get_cart_total() );

		// Test a single discount code
		$discounts = cs_set_cart_discount( self::$discount->code );

		$this->assertInternalType( 'array', $discounts );
		$this->assertTrue( 1 === count( $discounts ) );
		$this->assertEquals( '16.00', cs_get_cart_total() );

		// Test a single discount code again but with lower case
		$discounts = cs_set_cart_discount( strtolower( self::$discount->code ) );

		$this->assertInternalType( 'array', $discounts );
		$this->assertTrue( 1 === count( $discounts ) );
		$this->assertEquals( '16.00', cs_get_cart_total() );

		// Test a new code
		$code_id = \CS_Helper_Discount::create_simple_percent_discount();
		update_post_meta( $code_id, '_cs_discount_code', 'SECONDcode' );

		$discounts = cs_set_cart_discount( 'SECONDCODE' );

		$this->assertInternalType( 'array', $discounts );
		$this->assertTrue( 2 === count( $discounts ) );
		$this->assertEquals( '12.00', cs_get_cart_total() );
	}

	/**
	 * @covers \cs_store_discount()
	 * @covers \cs_get_cart_discountable_subtotal()
	 */
	public function test_discountable_subtotal() {
		$download_1 = \CS_Helper_Download::create_simple_download();
		$download_2 = \CS_Helper_Download::create_simple_download();
		cs_add_to_cart( $download_1->ID );
		cs_add_to_cart( $download_2->ID );

		$discount = \CS_Helper_Discount::create_simple_flat_discount();
		$post = array(
			'name'              => 'Excludes',
			'amount'            => '1',
			'code'              => 'EXCLUDES',
			'product_condition' => 'all',
			'start'             => date( 'm/d/Y H:i:s', time() ),
			'expiration'        => date( 'm/d/Y H:i:s', time() + HOUR_IN_SECONDS ),
			'min_price'         => 23,
			'status'            => 'active',
			'excluded-products' => array( $download_2->ID ),
		);
		cs_store_discount( $post, $discount );

		$this->assertEquals( '20', cs_get_cart_discountable_subtotal( $discount ) );

		$download_3 = \CS_Helper_Download::create_simple_download();
		cs_add_to_cart( $download_3->ID );

		$this->assertEquals( '40', cs_get_cart_discountable_subtotal( $discount ) );

		\CS_Helper_Download::delete_download( $download_1->ID );
		\CS_Helper_Download::delete_download( $download_2->ID );
		\CS_Helper_Download::delete_download( $download_3->ID );
		\CS_Helper_Discount::delete_discount( $discount );
	}

	/**
	 * @covers \cs_discount_is_min_met()
	 * @covers \cs_is_discount_valid()
	 */
	public function test_discount_min_excluded_products() {
		cs_empty_cart();
		$download_1 = \CS_Helper_Download::create_simple_download();
		$download_2 = \CS_Helper_Download::create_simple_download();
		$discount   = \CS_Helper_Discount::create_simple_flat_discount();

		$post = array(
			'name'              => 'Excludes',
			'amount'            => '1',
			'code'              => 'EXCLUDES',
			'product_condition' => 'all',
			'start'             => date( 'm/d/Y H:i:s', time() ),
			'expiration'        => date( 'm/d/Y H:i:s', time() + HOUR_IN_SECONDS ),
			'min_price'         => 23,
			'status'            => 'active',
			'excluded-products' => array( $download_2->ID ),
		);

		cs_store_discount( $post, $discount );

		cs_add_to_cart( $download_1->ID );
		cs_add_to_cart( $download_2->ID );
		$this->assertFalse( cs_discount_is_min_met( $discount ) );

		$download_3 = \CS_Helper_Download::create_simple_download();
		cs_add_to_cart( $download_3->ID );
		$this->assertTrue( cs_discount_is_min_met( $discount ) );

		cs_empty_cart();
		cs_add_to_cart( $download_2->ID );
		$discount_obj = cs_get_discount( $discount );
		$this->assertFalse( cs_is_discount_valid( $discount_obj->code ) );

		\CS_Helper_Download::delete_download( $download_1->ID );
		\CS_Helper_Download::delete_download( $download_2->ID );
		\CS_Helper_Download::delete_download( $download_3->ID );
	}

	/**
	 * @covers \cs_get_discounts()
	 */
	public function test_cs_get_discounts() {
		$found_discounts = cs_get_discounts( array(
			'posts_per_page' => 3,
		) );

		$this->assertTrue( 3 === count( $found_discounts ) );
	}

	/**
	 * @covers _cs_discounts_bc_wp_count_posts()
	 */
	public function test_cs_discounts_bc_wp_count_posts() {
		$counts = wp_count_posts( 'cs_discount' );

		$this->assertEquals( 3, (int) $counts->active );
		$this->assertEquals( 0, (int) $counts->inactive );
	}
}
