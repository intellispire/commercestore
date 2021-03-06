<?php
/**
 * Checkout tests.
 *
 * @group cs_checkout
 */
class Tests_Checkout extends CS_UnitTestCase {

	/**
	 * Set up fixtures once.
	 */
	public static function wpsetUpBeforeClass() : void  {
		global $wp_rewrite;
		$GLOBALS['wp_rewrite']->init();
		flush_rewrite_rules( false );
		cs_add_rewrite_endpoints( $wp_rewrite );

		$post_id = static::factory()->post->create( array(
			'post_title'  => 'Test Download',
			'post_type'   => 'download',
			'post_status' => 'publish',
		) );

		$meta = array(
			'cs_price'               => '10.50',
			'_cs_price_options_mode' => 'on',
			'_cs_product_type'       => 'default',
		);
		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		// Add our test product to the cart
		$options = array(
			'name'     => 'Simple',
			'amount'   => '10.50',
			'quantity' => 1,
		);

		cs_add_to_cart( $post_id, $options );
	}

	/**
	 * Test the can checkout function
	 */
	public function test_can_checkout() {
		$this->assertTrue( cs_can_checkout() );
	}

	/**
	 * Test the default 3 columns used for checkout carts.
	 */
	public function test_checkout_cart_columns_default() {
		$this->assertSame( 3, cs_checkout_cart_columns() );
	}

	/**
	 * Test the default 3 columns + 1 column used for checkout carts.
	 */
	public function test_checkout_cart_columns_add_one() {
		add_action( 'cs_checkout_table_header_first', '__return_true' );

		$this->assertSame( 4, cs_checkout_cart_columns() );

		remove_action( 'cs_checkout_table_header_first', '__return_true' );
	}

	/**
	 * Test the default 3 columns + 2 columns used for checkout carts.
	 */
	public function test_checkout_cart_columns_add_two() {
		add_action( 'cs_checkout_table_header_first', '__return_true' );
		add_action( 'cs_checkout_table_header_first', '__return_false' );

		$this->assertSame( 5, cs_checkout_cart_columns() );

		remove_action( 'cs_checkout_table_header_first', '__return_true' );
		remove_action( 'cs_checkout_table_header_first', '__return_false' );
	}

	/**
	 * Test the filter at the bottom of
	 */
	public function test_checkout_cart_columns_filter() {
		add_filter( 'cs_checkout_cart_columns', array( $this, 'helper_test_checkout_cart_columns_filter' ) );

		$this->assertSame( 2, cs_checkout_cart_columns() );

		remove_filter( 'cs_checkout_cart_columns', array( $this, 'helper_test_checkout_cart_columns_filter' ) );
	}

	/**
	 * Helper function for the above test, to test the filter in cs_checkout_cart_columns()
	 *
	 * @param $columns
	 *
	 * @return int
	 */
	public function helper_test_checkout_cart_columns_filter( $columns ) {
		return 2;
	}

	/**
	 * Test to make sure the checkout form returns the expected HTML
	 */
	public function test_checkout_form() {
		$this->assertInternalType( 'string', cs_checkout_form() );

		$this->assertStringContainsString( '<div id="cs_checkout_wrap">', cs_checkout_form() );

		$this->assertStringContainsString( '<div id="cs_checkout_form_wrap" class="cs_clearfix">', cs_checkout_form() );
	}

	/**
	 * Test to make sure the Next button is returned properly
	 */
	public function test_checkout_button_next() {
		$this->assertInternalType( 'string', cs_checkout_button_next() );
		$this->assertStringContainsString( '<input type="hidden" name="cs_action" value="gateway_select" />', cs_checkout_button_next() );
	}

	/**
	 * Test to make sure the purchase button is returned properly
	 */
	public function test_checkout_button_purchase() {
		// We need activate gateways in order for this to pass.
		add_filter( 'cs_enabled_payment_gateways', array( $this, 'modify_gateaways' ) );

		$this->assertInternalType( 'string', cs_checkout_button_purchase() );
		$this->assertStringContainsString( '<input type="submit" class="cs-submit blue button" id="cs-purchase-button" name="cs-purchase" value="Purchase"/>', cs_checkout_button_purchase() );

		remove_filter( 'cs_enabled_payment_gateways', array( $this, 'modify_gateaways' ) );
	}

	/**
	 * Test for retrieving banned emails
	 */
	public function test_cs_get_banned_emails() {
		$this->assertInternalType( 'array', cs_get_banned_emails() );
		$this->assertEmpty( cs_get_banned_emails() );
	}

	/**
	 * Test that a specific email is banned
	 */
	public function test_cs_is_email_banned() {
		$emails   = array();
		$emails[] = 'john@test.com'; // Banned email
		$emails[] = 'test2.com'; // Banned domain
		$emails[] = '.zip'; // Banned TLD

		cs_update_option( 'banned_emails', $emails );

		$this->assertTrue( cs_is_email_banned( 'john@test.com' ) );
		$this->assertTrue( cs_is_email_banned( 'john@test2.com' ) );
		$this->assertFalse( cs_is_email_banned( 'john2@test.com' ) );
		$this->assertTrue( cs_is_email_banned( 'john2@test.zip' ) );
		$this->assertFalse( cs_is_email_banned( 'john.zip@test.com' ) );
	}

	public function test_cs_is_lowercase_email_banned_with_uppcase_tld_banned() {
		$emails   = array();
		$emails[] = '.ZIP'; // Banned TLD

		cs_update_option( 'banned_emails', $emails );

		$this->assertTrue( cs_is_email_banned( 'john2@test.zip' ) );
		$this->assertFalse( cs_is_email_banned( 'john.zip@test.com' ) );
	}

	public function test_cs_is_uppercase_email_banned_with_lowercase_tld_banned() {
		$emails   = array();
		$emails[] = '.zip'; // Banned TLD

		cs_update_option( 'banned_emails', $emails );

		$this->assertTrue( cs_is_email_banned( 'JOHN2@test.ZIP' ) );
		$this->assertFalse( cs_is_email_banned( 'john.ZIP@test.com' ) );
	}

	/**
	 * Test SSL enforced checkout
	 */
	public function test_cs_is_ssl_enforced() {
		$this->assertFalse( cs_is_ssl_enforced() );

		cs_update_option( 'enforce_ssl', true );

		$this->assertTrue( cs_is_ssl_enforced() );
	}

	/**
	 * Test SSL asset filter
	 */
	public function test_cs_enforced_ssl_asset_filter() {
		// Test page URLs. These should not get modified

		$content = 'http://local.dev/';
		$this->assertSame( 'http://local.dev/', cs_enforced_ssl_asset_filter( $content ) );

		$content  = array( 'http://local.dev/' );
		$expected = array( 'http://local.dev/' );

		$this->assertSame( $expected, cs_enforced_ssl_asset_filter( $content ) );

		// Test asset URLs.

		$content = 'http://local.dev/assets/file.jpg';
		$this->assertSame( 'https://local.dev/assets/file.jpg', cs_enforced_ssl_asset_filter( $content ) );

		$content  = array( 'http://local.dev/assets/js/js_file.js' );
		$expected = array( 'https://local.dev/assets/js/js_file.js' );

		$this->assertSame( $expected, cs_enforced_ssl_asset_filter( $content ) );
	}

	public function test_credit_card_format_methods() {

		// Test Cards, Thanks http://www.freeformatter.com/credit-card-number-generator-validator.html
		$test_cards = array(
			'amex'                      => array(
				'373727872168601',
				'349197153955145',
				'347051495193935',
			),
			'diners_club_carte_blanche' => array(
				'30142801263033',
				'30358703415790',
				'30495144869936',
			),
			'diners_club_international' => array(
				'36326253251158',
				'36880678146963',
				'36446904405472',
			),
			'jcb'                       => array(
				'3530111333300000',
				'3566002020360505',
			),
			'laser'                     => array(
				'6304894437928605',
				'6771753193657440',
				'6771575180660297',
			),
			'visa_electron'             => array(
				'4175000419164927',
				'4917758689682679',
				'4913525617006584',
			),
			'visa'                      => array(
				'4485319939801387',
				'4556288114854566',
				'4929098273851984',
			),
			'mastercard'                => array(
				'5529267381716121',
				'5577967452254156',
				'5255867454472922',
			),
			'maestro'                   => array(
				'5038721445859297',
				'5018250387370752',
				'5020265126898844',
			),
			'discover'                  => array(
				'6011911144758069',
				'6011783671967201',
				'6011427578160466',
			),
		);

		foreach ( $test_cards as $type => $cards ) {
			foreach ( $cards as $card ) {
				$card_type = cs_detect_cc_type( $card );
				$this->assertEquals( $type, $card_type );

				$is_valid = cs_validate_card_number_format( $card );
				$this->assertTrue( $is_valid, $type . ' failed' );
			}
		}
	}

	public function test_cs_is_checkout_setting() {
		$checkout_page = cs_get_option( 'purchase_page' );

		$this->go_to( get_permalink( $checkout_page ) );

		$this->assertTrue( cs_is_checkout() );
	}

	public function test_cs_is_checkout_shortcode() {
		$post_id = $this->factory->post->create( array(
			'post_title'   => 'Test Page',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => '[download_checkout]',
		) );

		$this->go_to( get_permalink( $post_id ) );

		do_action( 'template_redirect' ); // Necessary to trigger correct actions

		$this->assertTrue( cs_is_checkout() );
	}

	public function test_cs_is_checkout_fail() {
		$post_id = $this->factory->post->create( array(
			'post_title'   => 'Test Page 2',
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_content' => 'Test Page',
		) );

		$this->go_to( get_permalink( $post_id ) );

		do_action( 'template_redirect' ); // Necessary to trigger correct actions

		$this->assertFalse( cs_is_checkout() );
	}

	public function modify_gateaways( $gateways ) {
		return array( 'test-gateway' );
	}
}
