<?php

/**
 * Structured Data Tests
 *
 * @covers CS_Structured_Data
 * @group cs_structured_data
 */
class Tests_Structured_Data extends CS_UnitTestCase {

	/**
	 * Download test fixture.
	 *
	 * @var WP_Post
	 */
	protected static $download = null;

	/**
	 * Set up fixtures once.
	 */
	public static function wpsetUpBeforeClass() : void  {
		self::$download = CS_Helper_Download::create_simple_download();
	}

	/**
	 * @covers CS_Structured_Data::get_data()
	 */
	public function test_get_data_with_no_data() {
		$this->assertEmpty( CS()->structured_data->get_data() );
	}

	/**
	 * @covers CS_Structured_Data::generate_structured_data()
	 * @covers CS_Structured_Data::get_data()
	 */
	public function test_generate_structured_data_for_download() {
		CS()->structured_data->generate_structured_data( 'download', self::$download->ID );

		$data = CS()->structured_data->get_data();

		$product = $data[0];

		// @type
		$this->assertEquals( 'Product', $product['@type'] );

		// name
		$this->assertEquals( self::$download->post_title, $product['name'] );

		// url
		$this->assertArrayHasKey( 'url', $product );

		// image
		$this->assertArrayNotHasKey( 'image', $product );

		// brand
		$this->assertArrayHasKey( 'brand', $product );
		$this->assertEquals( 'Thing', $product['brand']['@type'] );
		$this->assertEquals( get_bloginfo( 'name' ), $product['brand']['name'] );

		// offers
		$this->assertArrayHasKey( 'offers', $product );
	}

	/**
	 * @covers CS_Structured_Data::generate_download_data()
	 */
	public function test_generate_download_data() {
		CS()->structured_data->generate_download_data( self::$download->ID );

		$data = CS()->structured_data->get_data();

		$this->assertEquals( self::$download->post_title, $data[1]['name'] );
	}

	/**
	 * @covers CS_Structured_Data::output_structured_data()
	 * @covers CS_Structured_Data::sanitize_data()
	 * @covers CS_Structured_Data::encoded_data()
	 */
	public function test_output_structured_data() {
		$this->expectOutputRegex( '/<script type="application\/ld\+json">(.*?)<\/script>/' );
		CS()->structured_data->output_structured_data();
	}
}
