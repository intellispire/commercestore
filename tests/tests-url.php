<?php


/**
 * @group cs_url
 */
class Tests_URL extends CS_UnitTestCase {
	public function setUp() {
		parent::setUp();
	}

	public function tear_down() {
		parent::tear_down();
	}

	public function test_ajax_url() {
		$_SERVER['SERVER_PORT'] = 80;
		$_SERVER['HTTPS'] = 'off';

		$this->assertEquals( cs_get_ajax_url(), get_site_url( null, '/wp-admin/admin-ajax.php', 'http' ) );
	}

	public function test_current_page_url() {
		$_SERVER['SERVER_PORT'] = 80;
		$_SERVER["SERVER_NAME"] = 'example.org';
		$this->assertEquals( 'http://example.org/', cs_get_current_page_url() );
	}
}
