<?php


/**
 * @group cs_shortcode
 */
class Tests_Shortcode extends CS_UnitTestCase {

	protected static $payment_key;

	protected static $user_id;

	/**
	 * Set up fixtures once.
	 */
	public static function wpsetUpBeforeClass() : void  {
		self::$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( self::$user_id );

		$post_id = self::factory()->post->create( array( 'post_title' => 'Test Download', 'post_type' => CS_POST_TYPE, 'post_status' => 'publish' ) );

		$_variable_pricing = array(
			array(
				'name' => 'Simple',
				'amount' => 20
			),
			array(
				'name' => 'Advanced',
				'amount' => 100
			)
		);

		$_download_files = array(
			array(
				'name' => 'File 1',
				'file' => 'http://localhost/file1.jpg',
				'condition' => 0
			),
			array(
				'name' => 'File 2',
				'file' => 'http://localhost/file2.jpg',
				'condition' => 'all'
			)
		);

		$meta = array(
			'cs_price' => '0.00',
			'_variable_pricing' => 1,
			'_cs_price_options_mode' => 'on',
			'cs_variable_prices' => array_values( $_variable_pricing ),
			'cs_download_files' => array_values( $_download_files ),
			'_cs_download_limit' => 20,
			'_cs_hide_purchase_link' => 1,
			'cs_product_notes' => 'Purchase Notes',
			'_cs_product_type' => 'default',
			'_cs_download_earnings' => 129.43,
			'_cs_download_sales' => 59,
			'_cs_download_limit_override_1' => 1
		);
		foreach( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		$post = get_post( $post_id );

		/** Generate some sales */
		$user = get_userdata(1);

		$user_info = array(
			'id' => $user->ID,
			'email' => $user->user_email,
			'first_name' => $user->first_name,
			'last_name' => $user->last_name,
			'discount' => 'none'
		);

		$download_details = array(
			array(
				'id' => $post->ID,
				'options' => array(
					'price_id' => 1
				)
			)
		);

		$price = '100.00';

		$total = 0;

		$prices = get_post_meta($download_details[0]['id'], 'cs_variable_prices', true);
		$item_price = $prices[1]['amount'];

		$total += $item_price;

		$cart_details = array(
			array(
				'name' => 'Test Download',
				'id' => $post->ID,
				'item_number' => array(
					'id' => $post->ID,
					'options' => array(
						'price_id' => 1
					)
				),
				'price' =>  100,
				'item_price' => 100,
				'tax' => 0,
				'quantity' => 1
			)
		);

		$purchase_data = array(
			'price' => number_format( (float) $total, 2 ),
			'date' => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			'purchase_key' => strtolower( md5( uniqid() ) ),
			'user_email' => $user_info['email'],
			'user_info' => $user_info,
			'currency' => 'USD',
			'downloads' => $download_details,
			'cart_details' => $cart_details,
			'status' => 'complete'
		);

		$_SERVER['REMOTE_ADDR'] = '10.0.0.0';
		$_SERVER['SERVER_NAME'] = 'cs-virtual.local';

		remove_action( 'cs_complete_purchase', 'cs_trigger_purchase_receipt', 999, 3 );

		$payment_id = cs_insert_payment( $purchase_data );

		add_action( 'cs_complete_purchase', 'cs_trigger_purchase_receipt', 999, 3 );

		cs_update_order( $payment_id, array(
			'user_id' => $user->ID
		) );

		self::$payment_key = $purchase_data['purchase_key'];

		// Remove the account pending filter to only show once in a thread
		remove_filter( 'cs_allow_template_part_account_pending', 'cs_load_verification_template_once', 10, 1 );
	}

	public function set_up() {
		parent::set_up();

		wp_set_current_user( self::$user_id );

		// Remove the account pending filter to only show once in a thread
		remove_filter( 'cs_allow_template_part_account_pending', 'cs_load_verification_template_once', 10, 1 );
	}

	public function test_shortcodes_are_registered() {
		global $shortcode_tags;

		$this->assertArrayHasKey( 'purchase_link', $shortcode_tags );
		$this->assertArrayHasKey( 'download_history', $shortcode_tags );
		$this->assertArrayHasKey( 'purchase_history', $shortcode_tags );
		$this->assertArrayHasKey( 'download_checkout', $shortcode_tags );
		$this->assertArrayHasKey( 'download_cart', $shortcode_tags );
		$this->assertArrayHasKey( 'cs_login', $shortcode_tags );
		$this->assertArrayHasKey( 'download_discounts', $shortcode_tags );
		$this->assertArrayHasKey( 'purchase_collection', $shortcode_tags );
		$this->assertArrayHasKey( 'downloads', $shortcode_tags );
		$this->assertArrayHasKey( 'cs_price', $shortcode_tags );
		$this->assertArrayHasKey( 'cs_receipt', $shortcode_tags );
		$this->assertArrayHasKey( 'cs_profile_editor', $shortcode_tags );
	}

	public function test_download_history() {
		$actual = cs_download_history();

		$this->assertInternalType( 'string', $actual );
		$this->assertStringContainsString( '<p class="cs-no-downloads">', $actual );

		cs_set_user_to_pending( self::$user_id );

		$this->assertStringContainsString( '<p class="cs-account-pending">', cs_download_history() );
	}

	public function test_purchase_history() {
		$actual = cs_purchase_history();

		$this->assertInternalType( 'string', $actual );
		$this->assertStringContainsString( '<p class="cs-no-purchases">', $actual );

		cs_set_user_to_pending( self::$user_id );

		$this->assertStringContainsString( '<p class="cs-account-pending">', cs_purchase_history() );
	}

	public function test_checkout_form_shortcode() {
		$actual = cs_checkout_form_shortcode( array() );

		$this->assertInternalType( 'string', $actual );
		$this->assertStringContainsString( '<div id="cs_checkout_wrap">', $actual );
	}

	public function test_cart_shortcode() {
		$actual = cs_cart_shortcode( array() );

		$this->assertInternalType( 'string', $actual );
		$this->assertStringContainsString( '<ul class="cs-cart">', $actual );
	}

	public function test_login_form() {
		$purchase_history_page = cs_get_option( 'purchase_history_page' );

		$actual = cs_login_form_shortcode( array() );

		$this->assertInternalType( 'string', $actual );
		$this->assertStringContainsString( '<p class="cs-logged-in">You are already logged in</p>', $actual );

		// Log out the user so we can see the login form
		wp_set_current_user( 0 );

		$args = array(
			'redirect' => get_option( 'site_url' ),
		);

		$login_form = cs_login_form_shortcode( $args );
		$this->assertInternalType( 'string', $login_form );
		$this->assertStringContainsString( '"' . get_option( 'site_url' ) . '"', $login_form );

		cs_update_option( 'login_redirect_page', $purchase_history_page );

		$login_form = cs_login_form_shortcode( array() );
		$this->assertInternalType( 'string', $login_form );
		$this->assertStringContainsString( '"' . get_permalink( $purchase_history_page ) . '"', $login_form );
	}

	public function test_discounts_shortcode() {
		CS_Helper_Discount::create_simple_percent_discount();

		$actual = cs_discounts_shortcode( array() );

		$this->assertInternalType( 'string', $actual );
		$this->assertEquals( '<ul id="cs_discounts_list"><li class="cs_discount"><span class="cs_discount_name">20OFF</span><span class="cs_discount_separator"> - </span><span class="cs_discount_amount">20.00%</span></li></ul>', $actual );
	}

	public function test_purchase_collection_shortcode() {
		$this->go_to( '/' );

		$actual = cs_purchase_collection_shortcode( array() );

		$this->assertInternalType( 'string', $actual );
		$this->assertEquals( '<a href="/?cs_action=purchase_collection&#038;taxonomy&#038;terms" class="button blue cs-submit">Purchase All Items</a>', $actual );
	}

	public function test_download_price_shortcode() {
		$post_id = self::factory()->post->create( array( 'post_type' => CS_POST_TYPE ) );

		$meta = array(
			'cs_price' => '54.43',
		);

		foreach ( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		$actual = cs_download_price_shortcode( array( 'id' => $post_id ) );

		$this->assertInternalType( 'string', $actual );
		$this->assertEquals( '<span class="cs_price" id="cs_price_'. $post_id .'">&#36;54.43</span>', $actual );
	}

	public function __test_receipt_shortcode() {
		/**
		 * @internal This test fails on Travis but passes when running locally.
		 */

//		$actual = cs_receipt_shortcode( array( 'payment_key' => self::$payment_key ) );
//
//		$this->assertInternalType( 'string', $actual );
//		$this->assertStringContainsString( '<table id="cs_purchase_receipt" class="cs-table">', $actual  );
	}

	public function test_profile_shortcode() {
		$actual = cs_profile_editor_shortcode( array() );

		$this->assertInternalType( 'string', $actual );
		$this->assertStringContainsString( '<form id="cs_profile_editor_form" class="cs_form" action="', $actual );

		cs_set_user_to_pending( self::$user_id );

		$this->assertStringContainsString( '<p class="cs-account-pending">', cs_profile_editor_shortcode( array() ) );
	}

	public function test_profile_pending_single_load() {
		add_filter( 'cs_allow_template_part_account_pending', 'cs_load_verification_template_once', 10, 1 );
		cs_set_user_to_pending( self::$user_id );

		$actual = cs_profile_editor_shortcode( array() );

		$this->assertStringContainsString( '<p class="cs-account-pending">', $actual );

		remove_filter( 'cs_allow_template_part_account_pending', 'cs_load_verification_template_once', 10, 1 );
	}

	public function test_downloads_shortcode_pagination() {
		$output = cs_downloads_query( array() );
		$this->assertStringNotContainsString( 'id="cs_'.CS_POST_TYPE.'_pagination"', $output );

		// Create a second post so we can see pagination
		self::factory()->post->create( array( 'post_title' => 'Test Download #2', 'post_type' => CS_POST_TYPE, 'post_status' => 'publish' ) );

		$output2 = cs_downloads_query( array( 'number' => 1 ) );
		$this->assertStringContainsString( 'id="cs_download_pagination"', $output2 );

		cs_set_user_to_pending( self::$user_id );

		$this->assertStringContainsString( '<p class="cs-account-pending">', cs_download_history( array() ) );
	}

	public function test_downloads_shortcode_nopaging() {
		// Create a posts so we can see pagination
		self::factory()->post->create( array( 'post_title' => 'Test Download #2', 'post_type' => CS_POST_TYPE, 'post_status' => 'publish' ) );
		self::factory()->post->create( array( 'post_title' => 'Test Download #3', 'post_type' => CS_POST_TYPE, 'post_status' => 'publish' ) );
		self::factory()->post->create( array( 'post_title' => 'Test Download #4', 'post_type' => CS_POST_TYPE, 'post_status' => 'publish' ) );

		$output2 = cs_downloads_query( array( 'number' => 1, 'pagination' => 'false' ) );
		$this->assertStringNotContainsString( 'id="cs_download_pagination"', $output2 );
	}
}
