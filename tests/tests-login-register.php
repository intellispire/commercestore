<?php


/**
 * @group cs_login_register
 */
class Tests_Login_Register extends CS_UnitTestCase {

	public function set_up() {
		parent::set_up();
		wp_set_current_user(0);
	}

	/**
	 * Test that the login form returns the expected string.
	 */
	public function test_login_form() {
		$this->assertStringContainsString( '<legend>Log into Your Account</legend>', cs_login_form() );
	}

	/**
	 * Test that the registration form return the expected output.
	 */
	public function test_register_form() {
		$this->assertStringContainsString( '<legend>Register New Account</legend>', cs_register_form() );
	}

	/**
	 * Test that there is displayed a error when the username is incorrect.
	 *
	 * @since 2.2.3
	 */
	public function test_process_login_form_incorrect_username() {

		cs_process_login_form( array(
			'cs_login_nonce' => wp_create_nonce( 'cs-login-nonce' ),
			'cs_user_login'  => 'wrong_username',
		) );

		$errors = cs_get_errors();
		$this->assertArrayHasKey( 'cs_invalid_login', $errors );
		$this->assertStringContainsString( 'Invalid username or password', $errors['cs_invalid_login'] );

		// Clear errors for other test
		cs_clear_errors();

	}

	/**
	 * Test that there is displayed a error when the wrong password is entered.
	 *
	 * @since 2.2.3
	 */
	public function test_process_login_form_correct_username_invalid_pass() {
		cs_process_login_form( array(
			'cs_login_nonce' => wp_create_nonce( 'cs-login-nonce' ),
			'cs_user_login'  => 'admin@example.org',
			'cs_user_pass'   => 'falsepass',
		) );

		$errors = cs_get_errors();
		$this->assertArrayHasKey( 'cs_invalid_login', $errors );
		$this->assertStringContainsString( 'Invalid username or password', $errors['cs_invalid_login'] );

		// Clear errors for other test
		cs_clear_errors();
	}

	/**
	 * Test correct login.
	 *
	 * @since 2.2.3
	 */
	public function test_process_login_form_correct_login() {
		try {
			cs_process_login_form( array(
				'cs_login_nonce' => wp_create_nonce( 'cs-login-nonce' ),
				'cs_user_login'  => 'admin@example.org',
				'cs_user_pass'   => 'password',
				'cs_redirect'    => '',
			) );
		} catch ( WPDieException $e ) {

		}

		$this->assertEmpty( cs_get_errors() );
	}

	/**
	 * Test that the cs_log_user_in() function successfully logs the user in.
	 *
	 * @since 2.2.3
	 */
	public function test_log_user_in_return() {
		$this->assertTrue( cs_log_user_in( 0, '', '' ) instanceof WP_Error );
	}

	/**
	 * Test that the cs_log_user_in() function successfully logs the user in.
	 *
	 * @since 2.2.3
	 */
	public function test_log_user_in() {
		wp_logout();
		cs_log_user_in( 1, 'admin', 'password' );
		$this->assertTrue( is_user_logged_in() );
	}

	/**
	 * Test that the function returns when the user is already logged in.
	 *
	 * @since 2.2.3
	 */
	public function test_process_register_form_logged_in() {

		global $current_user;
		$origin_user  = $current_user;
		$current_user = wp_set_current_user( 1 );

		$_POST['cs_register_submit'] = '';
		$this->assertNull( cs_process_register_form( array() ) );

		// Reset to origin
		$current_user = $origin_user;

	}

	/**
	 * Test that the function returns when the submit is empty.
	 *
	 * @since 2.2.3
	 */
	public function test_process_register_form_return_submit() {

		$_POST['cs_register_submit'] = '';
		$this->assertNull( cs_process_register_form( array(
			'cs_register_submit' => '',
		) ) );

	}

	/**
	 * Test that 'empty' errors are displayed when certain fields are empty.
	 *
	 * @since 2.2.3
	 */
	public function test_process_register_form_empty_fields() {

		$_POST['cs_register_submit'] = 1;
		$_POST['cs_user_pass']       = '';
		$_POST['cs_user_pass2']      = '';

		cs_process_register_form( array(
			'cs_register_submit' => 1,
			'cs_user_login'      => '',
			'cs_user_email'      => '',
		) );

		$errors = cs_get_errors();
		$this->assertArrayHasKey( 'empty_username', $errors );
		$this->assertArrayHasKey( 'email_invalid', $errors );
		$this->assertArrayHasKey( 'empty_password', $errors );

		// Clear errors for other test
		cs_clear_errors();

	}

	/**
	 * Test that a error is displayed when the username already exists.
	 * Also tests the password mismatch.
	 *
	 * @since 2.2.3
	 */
	public function test_process_register_form_username_exists() {

		$_POST['cs_register_submit'] = 1;
		$_POST['cs_user_pass']       = 'password';
		$_POST['cs_user_pass2']      = 'other-password';

		cs_process_register_form( array(
			'cs_register_submit' => 1,
			'cs_user_login'      => 'admin',
			'cs_user_email'      => null,
		) );
		$this->assertArrayHasKey( 'username_unavailable', cs_get_errors() );
		$this->assertArrayHasKey( 'password_mismatch', cs_get_errors() );

		// Clear errors for other test
		cs_clear_errors();
	}

	/**
	 * Test that a error is displayed when the username is invalid.
	 *
	 * @since 2.2.3
	 */
	public function test_process_register_form_username_invalid() {

		$_POST['cs_register_submit'] 	= 1;
		$_POST['cs_user_pass'] 		= 'password';
		$_POST['cs_user_pass2'] 		= 'other-password';
		cs_process_register_form( array(
			'cs_register_submit' 	=> 1,
			'cs_user_login' 		=> 'admin#!@*&',
			'cs_user_email' 		=> null,
		) );
		$this->assertArrayHasKey( 'username_invalid', cs_get_errors() );

		// Clear errors for other test
		cs_clear_errors();
	}

	/**
	 * Test that a error is displayed when the email is already taken.
	 * Test that a error is displayed when the payment email is incorrect.
	 *
	 * @since 2.2.3
	 */
	public function test_process_register_form_payment_email_incorrect() {

		$_POST['cs_register_submit'] 	= 1;
		$_POST['cs_user_pass'] 		= '';
		$_POST['cs_user_pass2'] 		= '';
		cs_process_register_form( array(
			'cs_register_submit' 	=> 1,
			'cs_user_login' 		=> 'random_username',
			'cs_user_email' 		=> 'admin@example.org',
			'cs_payment_email' 	=> 'someotheradminexample.org',
		) );
		$this->assertArrayHasKey( 'email_unavailable', cs_get_errors() );
		$this->assertArrayHasKey( 'payment_email_invalid', cs_get_errors() );

		// Clear errors for other test
		cs_clear_errors();
	}
}
