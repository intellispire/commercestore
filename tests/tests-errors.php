<?php


/**
 * @group cs_errors
 */
class Tests_Errors extends CS_UnitTestCase {

	public function set_up() {
		parent::set_up();

		cs_set_error( 'invalid_email', 'Please enter a valid email address.' );
		cs_set_error( 'invalid_user', 'The user information is invalid.' );
		cs_set_error( 'username_incorrect', 'The username you entered does not exist' );
		cs_set_error( 'password_incorrect', 'The password you entered is incorrect' );
	}

	public function tear_down() {
		parent::tear_down();
	}

	public function test_set_errors() {
		$errors = CS()->session->get( 'cs_errors' );

		$this->assertArrayHasKey( 'invalid_email', $errors );
		$this->assertArrayHasKey( 'invalid_user', $errors );
		$this->assertArrayHasKey( 'username_incorrect', $errors );
		$this->assertArrayHasKey( 'password_incorrect', $errors );
	}

	public function test_clear_errors() {
		$errors = cs_clear_errors();
		$this->assertFalse( CS()->session->get( 'cs_errors' ) );
	}

	public function test_unset_error() {
		$error = cs_unset_error( 'invalid_email' );
		$errors = CS()->session->get( 'cs_errors' );

		$expected = array(
			'invalid_user' => 'The user information is invalid.',
			'username_incorrect' => 'The username you entered does not exist',
			'password_incorrect' => 'The password you entered is incorrect'
		);

		$this->assertEquals( $expected, $errors );
	}
}
