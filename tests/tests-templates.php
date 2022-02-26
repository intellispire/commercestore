<?php


/**
 * @group cs_mime
 * @group cs_functions
 */
class Tests_Templates extends CS_UnitTestCase {

	protected $_post;

	public function set_up() {
		parent::set_up();

		$post_id = $this->factory->post->create( array( 'post_title' => 'A Test Download', 'post_type' => CS_POST_TYPE, 'post_status' => 'publish' ) );

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
			'_cs_download_limit_override_1' => 1,
			'cs_sku' => 'sku1234567'
		);
		foreach( $meta as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		$this->_post = get_post( $post_id );

	}

	public function tear_down() {
		parent::tear_down();
	}

	public function test_get_purchase_link() {
		$link = cs_get_purchase_link( array( 'download_id' => $this->_post->ID ) );
		$this->assertInternalType( 'string', $link );
		$this->assertStringContainsString( '<form id="cs_purchase_', $link );
		$this->assertStringContainsString( 'class="cs_download_purchase_form', $link );
		$this->assertStringContainsString( 'method="post">', $link );
		$this->assertStringContainsString( '<input type="hidden" name="download_id" value="' . $this->_post->ID . '">', $link );

		// The product we created has variable pricing, so ensure the price options render
		$this->assertStringContainsString( '<div class="cs_price_options', $link );
		$this->assertStringContainsString( '<span class="cs_price_option_name">', $link );

		add_filter( 'cs_item_quantities_enabled', '__return_true' );
		$link = cs_get_purchase_link( array( 'download_id' => $this->_post->ID ) );
		$this->assertInternalType( 'string', $link );
		remove_filter( 'cs_item_quantities_enabled', '__return_true' );

		// Test a single price point as well
		$single_id = $this->factory->post->create( array( 'post_title' => 'A Test Single Price Download', 'post_type' => CS_POST_TYPE, 'post_status' => 'publish' ) );
		$meta = array(
			'cs_price' => '10.00',
			'_cs_download_limit' => 20,
			'_cs_product_type' => 'default',
			'_cs_download_earnings' => 0,
			'_cs_download_sales' => 0
		);

		foreach( $meta as $key => $value ) {
			update_post_meta( $single_id, $key, $value );
		}

		$single_link_default = cs_get_purchase_link( array( 'download_id' => $single_id ) );
		$this->assertStringContainsString( 'data-price="10.00"', $single_link_default );
		$this->assertStringContainsString( '<span class="cs-add-to-cart-label">&#36;10.00&nbsp;&ndash;&nbsp;Purchase</span>', $single_link_default );

		// Verify the purchase link works with price = 0
		$single_link_no_price = cs_get_purchase_link( array( 'download_id' => $single_id, 'price' => 0 ) );
		// Price should NOT show on button
		$this->assertStringContainsString( '<span class="cs-add-to-cart-label">Purchase</span>', $single_link_no_price );
		// data-price should still contain the price
		$this->assertStringContainsString( 'data-price="10.00"', $single_link_no_price );
	}

	// For issue #4755
	public function test_get_purchase_link_invalid_sku() {
		$link = cs_get_purchase_link( array( 'sku' => 'SKU' ) );
		$this->assertTrue( empty( $link ) );
	}

	public function test_button_colors() {
		$colors = cs_get_button_colors();
		$this->assertInternalType( 'array', $colors );
		$this->assertarrayHasKey( 'white', $colors );
		$this->assertarrayHasKey( 'gray', $colors );
		$this->assertarrayHasKey( 'blue', $colors );
		$this->assertarrayHasKey( 'red', $colors );
		$this->assertarrayHasKey( 'green', $colors );
		$this->assertarrayHasKey( 'yellow', $colors );
		$this->assertarrayHasKey( 'orange', $colors );
		$this->assertarrayHasKey( 'dark-gray', $colors );
		$this->assertarrayHasKey( 'inherit', $colors );
		$this->assertInternalType( 'array', $colors['white'] );
		$this->assertEquals( 'White', $colors['white']['label'] );
	}

	public function test_button_styles() {
		$styles = cs_get_button_styles();
		$this->assertInternalType( 'array', $styles );
		$this->assertarrayHasKey( 'button', $styles );
		$this->assertarrayHasKey( 'plain', $styles );
		$this->assertEquals( 'Button', $styles['button'] );
		$this->assertEquals( 'Plain Text', $styles['plain'] );
	}

	public function test_locate_template() {
		// Test that a file path is found
		$this->assertInternalType( 'string', cs_locate_template( 'history-purchases.php' ) );
	}

	public function test_get_theme_template_paths() {
		$paths = cs_get_theme_template_paths();
		$this->assertInternalType( 'array', $paths );
		$this->assertarrayHasKey( 1, $paths );
		$this->assertarrayHasKey( 10, $paths );
		$this->assertarrayHasKey( 100, $paths );
		$this->assertInternalType( 'string', $paths[1] );
		$this->assertInternalType( 'string', $paths[10] );
		$this->assertInternalType( 'string', $paths[100] );
	}

	public function test_get_templates_dir_name() {
		$this->assertEquals( 'cs_templates/', cs_get_theme_template_dir_name() );
	}
}
