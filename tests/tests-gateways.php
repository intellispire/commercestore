<?php


/**
 * @group cs_gateways
 */
class Test_Gateways extends CS_UnitTestCase {

	public function setUp() {
		parent::setUp();
	}

	public function tear_down() {
		parent::tear_down();
	}

	public function test_payment_gateways() {
		$out = cs_get_payment_gateways();
		$this->assertArrayHasKey( 'paypal', $out );
		$this->assertArrayHasKey( 'manual', $out );

		$this->assertEquals( 'PayPal Standard', $out['paypal']['admin_label'] );
		$this->assertEquals( 'PayPal', $out['paypal']['checkout_label'] );

		$this->assertEquals( 'Store Gateway', $out['manual']['admin_label'] );
		$this->assertEquals( 'Store Gateway', $out['manual']['checkout_label'] );
	}

	public function test_enabled_gateways() {
		$this->assertEmpty( cs_get_enabled_payment_gateways() );

		global $cs_options;
		$cs_options['gateways']['paypal'] = '1';
		$cs_options['gateways']['manual'] = '1';

		// Verify PayPal comes back as default/first when none is set
		$this->assertTrue( empty( $cs_options['default_gateway'] ) );

		$enabled_gateway_list = cs_get_enabled_payment_gateways( true );
		$first_gateway_id     = current( array_keys( $enabled_gateway_list ) );
		$this->assertEquals( 'paypal', $first_gateway_id );

		// Test when default is set to paypal
		$cs_options['default_gateway'] = 'paypal';
		$enabled_gateway_list = cs_get_enabled_payment_gateways( true );
		$first_gateway_id     = current( array_keys( $enabled_gateway_list ) );
		$this->assertEquals( 'paypal', $first_gateway_id );

		// Test default is set to manual and we ask for it sorted
		$cs_options['default_gateway'] = 'manual';
		$enabled_gateway_list = cs_get_enabled_payment_gateways( true );
		$first_gateway_id     = current( array_keys( $enabled_gateway_list ) );
		$this->assertEquals( 'manual', $first_gateway_id );

		// Test the call does not return it sorted when manual is default
		$enabled_gateway_list = cs_get_enabled_payment_gateways();
		$first_gateway_id     = current( array_keys( $enabled_gateway_list ) );
		$this->assertEquals( 'paypal', $first_gateway_id );

		// Reset these so the rest of the tests don't fail
		unset( $cs_options['default_gateway'], $cs_options['gateways']['paypal'], $cs_options['gateways']['manual'] );
	}

	public function test_is_gateway_active() {
		$this->assertFalse( cs_is_gateway_active( 'paypal' ) );
	}

	/**
	 * @todo - rewrite test. As of 3.0, there is a new sort order that this test should be exercising.
	 * @see cs_order_gateways
	 *
	 * @return void
	 */

	public function test_default_gateway() {

		global $cs_options;

		$this->assertFalse( cs_get_default_gateway() );

		$cs_options['gateways'] = array();
		$cs_options['gateways']['paypal'] = '1';
		$cs_options['gateways']['manual'] = '1';

		$this->assertEquals( 'paypal', cs_get_default_gateway() );

		$cs_options['default_gateway'] = 'paypal';
		$cs_options['gateways'] = array();
		$cs_options['gateways']['manual'] = '1';
		$cs_options['gateways']['stripe'] = '1';

		$this->assertEquals( 'stripe', cs_get_default_gateway() );

		$cs_options['default_gateway'] = 'manual';
		$cs_options['gateways'] = array();
		$cs_options['gateways']['manual'] = '1';

		$this->assertEquals( 'manual', cs_get_default_gateway() );
	}

	public function test_get_gateway_admin_label() {
		global $cs_options;

		$cs_options['gateways'] = array();
		$cs_options['gateways']['paypal'] = '1';
		$cs_options['gateways']['manual'] = '1';

		$this->assertEquals( 'PayPal Standard', cs_get_gateway_admin_label( 'paypal' ) );
		$this->assertEquals( 'Store Gateway', cs_get_gateway_admin_label( 'manual' ) );
	}

	public function test_get_gateway_checkout_label() {
		global $cs_options;

		$cs_options['gateways'] = array();
		$cs_options['gateways']['paypal'] = '1';
		$cs_options['gateways']['manual'] = '1';

		$this->assertEquals( 'PayPal', cs_get_gateway_checkout_label( 'paypal' ) );
		$this->assertEquals( 'Store Gateway', cs_get_gateway_checkout_label( 'manual' ) );
	}

	public function test_buy_now_supported_single_gateway() {
		global $cs_options;

		$cs_options['default_gateway'] = 'paypal';
		$cs_options['gateways'] = array();
		$cs_options['gateways']['paypal'] = '1';

		$this->assertTrue( cs_shop_supports_buy_now() );
	}

	public function test_buy_now_supported_multiple_gateways() {
		global $cs_options;

		$cs_options['default_gateway'] = 'paypal';
		$cs_options['gateways'] = array();
		$cs_options['gateways']['paypal'] = '1';
		$cs_options['gateways']['manual'] = '1';

		$this->assertFalse( cs_shop_supports_buy_now() );
	}

	public function test_show_gateways() {
		cs_empty_cart();
		$this->assertFalse( cs_show_gateways() );
	}

	public function test_chosen_gateway() {
		$this->assertEquals( 'manual', cs_get_chosen_gateway() );
	}

	public function test_no_gateway_error() {

		global $cs_options;

		$download = CS_Helper_Download::create_simple_download();
		cs_add_to_cart( $download->ID );

		$cs_options['gateways'] = array();

		cs_no_gateway_error();

		$errors = cs_get_errors();

		$this->assertArrayHasKey( 'no_gateways', $errors );
		$this->assertEquals( 'You must enable a payment gateway to use CommerceStore', $errors['no_gateways'] );
	}

}
