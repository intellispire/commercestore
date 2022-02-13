<?php


/**
 * @group cs_session
 */
class Tests_Session extends CS_UnitTestCase {

	public function setUp() {
		parent::setUp();
		new \CS_Session;
	}

	public function tearDown() {
		parent::tearDown();
	}

	public function test_set() {
		$this->assertEquals( 'bar', CS()->session->set( 'foo', 'bar' ) );
	}

	public function test_get() {
		$this->assertEquals( 'bar', CS()->session->get( 'foo' ) );
	}

	public function test_use_cart_cookie() {
		$this->assertTrue( CS()->session->use_cart_cookie() );
		define( 'CS_USE_CART_COOKIE', false );
		$this->assertFalse( CS()->session->use_cart_cookie());
	}

	public function test_should_start_session() {

		$blacklist = CS()->session->get_blacklist();

		foreach( $blacklist as $uri ) {

			$this->go_to( '/' . $uri );
			$this->assertFalse( CS()->session->should_start_session() );

		}

	}
}
