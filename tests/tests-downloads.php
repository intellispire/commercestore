<?php


/**
 * @group cs_downloads
 * @group cs_functions
 */
class Tests_Downloads extends CS_UnitTestCase {
	protected $_post = null;

	protected $_variable_pricing = null;

	protected $_download_files = null;

	public function set_up() {
		parent::set_up();

		$this->_post = CS_Helper_Download::create_variable_download();
	}

	public function tear_down() {

		parent::tear_down();

		CS_Helper_Download::delete_download( $this->_post->ID );

	}

	public function test_get_download() {
		$out = cs_get_download( $this->_post->ID );

		$this->assertObjectHasAttribute( 'ID', $out );
		$this->assertObjectHasAttribute( 'post_title', $out );
		$this->assertObjectHasAttribute( 'post_type', $out );

		$this->assertEquals( $out->post_type, $this->_post->post_type );
	}

	public function test_get_download_null() {
		$out = cs_get_download( 999999999 );

		$this->assertSame( null, $out );
	}

	public function test_get_download_by_name() {
		$out = cs_get_download( $this->_post->post_name );

		$this->assertObjectHasAttribute( 'ID', $out );
		$this->assertObjectHasAttribute( 'post_title', $out );
		$this->assertObjectHasAttribute( 'post_type', $out );

		$this->assertEquals( 'Variable Test Download Product', $out->get_name() );
	}

	public function test_get_download_by_name_null() {
		$out = cs_get_download( 'TESTING BY NAME NULL' );

		$this->assertSame( null, $out );
	}

	public function test_cs_get_download_by() {

		$download = cs_get_download_by( 'id', $this->_post->ID );
		$this->assertSame( $this->_post->ID, $download->ID );

		$download = cs_get_download_by( 'sku', 'sku_0012' );
		$this->assertSame( $this->_post->ID, $download->ID );

		$download = cs_get_download_by( 'slug', 'variable-test-download-product' );
		$this->assertSame( $this->_post->ID, $download->ID );

		$downoad = cs_get_download_by( 'name', 'Variable Test Download Product' );
		$this->assertSame( $this->_post->ID, $download->ID );

	}

	public function test_cs_download() {

		// Verify passing nothing gives us an empty download
		$download = new CS_Download;
		$this->assertEquals( 0, $download->ID );

		// Create a Download
		$args = array(
			'post_title'  => 'Test Create Download'
		);
		$download2 = new CS_Download;
		$this->assertEquals( 0, $download2->ID );

		$download2->create( $args );

		$this->assertNotEmpty( $download2->ID );
		$this->assertEquals( 'download', $download2->post_type );
		$this->assertEquals( 'draft', $download2->post_status );
		$this->assertEquals( 0, $download2->sales );
		$this->assertEquals( 0.00, $download2->earnings );
		$this->assertFalse( $download2->has_variable_prices() );
		$this->assertEmpty( $download2->prices );

		// Retrieve a previously created download
		$prices = array(
			array(
				'name' => 'Simple',
				'amount' => 20
			),
			array(
				'name' => 'Advanced',
				'amount' => 100
			)
		);
		$files = array(
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
		$download3 = new CS_Download( $this->_post->ID );
		$download3->increase_earnings( '0.50' );
		$this->assertNotEmpty( $download3->ID );
		$this->assertEquals( $this->_post->ID, $download3->ID );
		$this->assertEquals( 'download', $download3->post_type );
		$this->assertEquals( 'publish', $download3->post_status );
		$this->assertEquals( 0.00, $download3->price );
		$this->assertEquals( 0.00, $download3->get_price() );
		$this->assertTrue( $download3->has_variable_prices() );
		$this->assertNotEmpty( $download3->prices );
		$this->assertEquals( $prices, $download3->prices );
		$this->assertEquals( $prices, $download3->get_prices() );
		$this->assertEquals( 6, $download3->sales );
		$this->assertEquals( 120.50, $download3->earnings );
		$this->assertNotEmpty( $download3->files );
		$this->assertEquals( $files, $download3->files );
		$this->assertEquals( $files, $download3->get_files() );
		$this->assertEquals( 20, $download3->file_download_limit );
		$this->assertEquals( 20, $download3->get_file_download_limit() );
		$this->assertEquals( 0, $download3->get_file_price_condition( 0 ) );
		$this->assertEquals( 'all', $download3->get_file_price_condition( 1 ) );
		$this->assertEquals( 'default', $download3->get_type() );
		$this->assertFalse( $download3->is_bundled_download() );
		$this->assertInternalType( 'array', $download3->get_bundled_downloads() );
		$this->assertInternalType( 'string', $download3->get_notes() );
		$this->assertInternalType( 'string', $download3->notes );
		$this->assertEquals( 'Purchase Notes', $download3->get_notes() );
		$this->assertEquals( 'add_to_cart', $download3->get_button_behavior() );
		$this->assertFalse( $download3->is_free() );
		$this->assertFalse( $download3->is_free( 0 ) );
		$this->assertFalse( $download3->is_free( 1 ) );

		update_post_meta( $download3->ID, '_variable_pricing', false );
		$download4 = new CS_Download( $download3->ID );
		$this->assertEmpty( $download4->prices );

		// Test the magic __get function
		$this->assertEquals( 20, $download3->file_download_limit );
		$this->assertTrue( is_wp_error( $download3->__get( 'asdf') ) );

	}

	public function test_can_purchase() {
		$download = new CS_Download( $this->_post->ID );
		$this->assertTrue( $download->can_purchase() );

		add_filter( 'cs_can_purchase_download', '__return_false' );
		$this->assertFalse( $download->can_purchase() );
		remove_filter( 'cs_can_purchase_download', '__return_false' );

		$download->post_status = 'draft';
		wp_set_current_user( 0 );
		$this->assertFalse( $download->can_purchase() );

		add_filter( 'cs_can_purchase_download', '__return_true' );
		$this->assertTrue( $download->can_purchase() );
		remove_filter( 'cs_can_purchase_download', '__return_true' );
	}

	public function test_download_price() {
		// This is correct and should equal 0.00 because this download uses variable pricing
		$this->assertEquals( 0.00, cs_get_download_price( $this->_post->ID ) );
	}

	public function test_variable_pricing() {
		$out = cs_get_variable_prices( $this->_post->ID );
		$this->assertNotEmpty( $out );
		foreach ( $out as $var ) {
			$this->assertArrayHasKey( 'name', $var );
			$this->assertArrayHasKey( 'amount', $var );

			if ( $var['name'] == 'Simple' ) {
				$this->assertEquals( 20, $var['amount'] );
			}

			if ( $var['name'] == 'Advanced' ) {
				$this->assertEquals( 100, $var['amount'] );
			}
		}
	}

	public function test_variable_pricing_cs_price() {
		$out = cs_get_variable_prices( $this->_post->ID );
		$price_text = cs_price( $this->_post->ID, false, 0);
		$this->assertStringContainsString( '&#36;20.00', $price_text, 'Variable Price cs_price incorrect' );
	}

	public function test_has_variable_prices() {
		$this->assertTrue( cs_has_variable_prices( $this->_post->ID ) );
	}

	public function test_default_variable_price() {
		$this->assertEquals( 0, cs_get_default_variable_price( $this->_post->ID ) );

		update_post_meta( $this->_post->ID, '_cs_default_price_id', 1 );
		$this->assertEquals( 1, cs_get_default_variable_price( $this->_post->ID ) );
	}

	public function test_get_price_option_name() {
		$this->assertEquals( 'Simple', cs_get_price_option_name( $this->_post->ID, 0 ) );
		$this->assertEquals( 'Advanced', cs_get_price_option_name( $this->_post->ID, 1 ) );
	}

	public function test_get_lowest_price_option() {
		$this->assertEquals( 20, cs_get_lowest_price_option( $this->_post->ID ) );
	}

	public function test_get_highest_price_option() {
		$this->assertEquals( 100, cs_get_highest_price_option( $this->_post->ID ) );
	}

	public function test_price_range() {
		$range = cs_price_range( $this->_post->ID );
		$expected = '<span class="cs_price cs_price_range_low" id="cs_price_low_' . $this->_post->ID . '">&#36;20.00</span><span class="cs_price_range_sep">&nbsp;&ndash;&nbsp;</span><span class="cs_price cs_price_range_high" id="cs_price_high_' . $this->_post->ID . '">&#36;100.00</span>';
		$this->assertInternalType( 'string', $range );
		$this->assertEquals( $expected, $range );
	}

	public function test_single_price_option_mode() {
		$this->assertTrue( cs_single_price_option_mode( $this->_post->ID ) );
	}

	public function test_download_type() {
		$this->assertEquals( 'default', cs_get_download_type( $this->_post->ID ) );
	}

	public function test_download_earnings() {
		$download = new CS_Download( $this->_post->ID );
		$download->increase_earnings( '0.50' );
		$this->assertEquals( 120.50, cs_get_download_earnings_stats( $this->_post->ID ) );
	}

	public function test_download_sales() {
		$this->assertEquals( 6, cs_get_download_sales_stats( $this->_post->ID ) );
	}

	public function test_increase_purchase_count() {

		// Test our helper function
		$this->assertEquals( 7, cs_increase_purchase_count( $this->_post->ID ) );

		// Now test our helper with a quantity
		$this->assertEquals( 9, cs_increase_purchase_count( $this->_post->ID, 2 ) );

		$download = new CS_Download( $this->_post->ID );
		// Now test our CS_Download class method
		$this->assertEquals( 10, $download->increase_sales() );

		// Now test our CS_Downlaod class method with a quantity
		$this->assertEquals( 12, $download->increase_sales( 2 ) );

	}

	public function test_decrease_purchase_count() {

		// Test our helper function
		$this->assertEquals( 5, cs_decrease_purchase_count( $this->_post->ID ) );

		// Test our helper function with a quantity
		$this->assertEquals( 3, cs_decrease_purchase_count( $this->_post->ID, 2 ) );

		$download = new CS_Download( $this->_post->ID );
		// Now test our CS_Download class method
		$this->assertEquals( 2, $download->decrease_sales() );

		// Now test our CS_Download class method with a quantity
		$this->assertEquals( 0, $download->decrease_sales( 2 ) );

	}

	public function test_earnings_increase() {

		// Test our helper function
		$this->assertEquals( 140, cs_increase_earnings( $this->_post->ID, 20 ) );

		// Now test our CS_Download class method
		$download = new CS_Download( $this->_post->ID );
		$this->assertEquals( 160, $download->increase_earnings( 20 ) );

	}

	public function test_decrease_earnings() {

		// Test our helper function
		$this->assertEquals( 100, cs_decrease_earnings( $this->_post->ID, 20 ) );

		// Now test our CS_Download class method
		$download = new CS_Download( $this->_post->ID );
		$this->assertEquals( 95, $download->decrease_earnings( 5 ) );
	}

	public function test_get_download_files() {
		$out = cs_get_download_files( $this->_post->ID );

		foreach ( $out as $file ) {
			$this->assertArrayHasKey( 'name', $file );
			$this->assertArrayHasKey( 'file', $file );
			$this->assertArrayHasKey( 'condition', $file );

			if ( $file['name'] == 'File 1' ) {
				$this->assertEquals( 'http://localhost/file1.jpg', $file['file'] );
				$this->assertEquals( 0, $file['condition'] );
			}

			if ( $file['name'] == 'File 2' ) {
				$this->assertEquals( 'http://localhost/file2.jpg', $file['file'] );
				$this->assertEquals( 'all', $file['condition'] );
			}
		}
	}

	public function test_get_file_download_limit() {
		$this->assertEquals( 20, cs_get_file_download_limit( $this->_post->ID ) );
	}

	public function test_get_file_download_limit_override() {
		$this->assertEquals( 1, cs_get_file_download_limit_override( $this->_post->ID, 1 ) );
	}

	public function test_is_file_at_download_limit() {
		$this->assertFalse( cs_is_file_at_download_limit( $this->_post->ID, 1, 1 ) );
	}

	public function test_get_file_price_condition() {
		$this->assertEquals( 0, cs_get_file_price_condition( $this->_post->ID, 0 ) );
		$this->assertEquals( 'all', cs_get_file_price_condition( $this->_post->ID, 1 ) );
	}

	public function test_get_product_notes() {
		$this->assertEquals( 'Purchase Notes', cs_get_product_notes( $this->_post->ID ) );
	}

	public function test_get_download_type() {
		$this->assertEquals( 'default', cs_get_download_type( $this->_post->ID ) );
	}

	public function test_get_download_is_bundle() {
		$this->assertFalse( cs_is_bundled_product( $this->_post->ID ) );
	}

	public function test_item_quantities_not_disabled() {
		$this->assertFalse( cs_download_quantities_disabled( $this->_post->ID ) );
	}

	public function test_item_quantities_disabled() {

		update_post_meta( $this->_post->ID, '_cs_quantities_disabled', 1 );

		$this->assertTrue( cs_download_quantities_disabled( $this->_post->ID ) );
	}

}
