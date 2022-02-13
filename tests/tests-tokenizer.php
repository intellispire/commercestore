<?php
/**
 * tests-tokenizer.php
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 * @since     2.11
 */

namespace CS\Tests;

use CS\Utils\Tokenizer;

/**
 * Class TestsTokenizer
 *
 * @coversDefaultClass \CS\Utils\Tokenizer
 *
 * @package CS\Tests
 */
class TestsTokenizer extends \CS_UnitTestCase {

	/**
	 * When a valid token is passed through using the current timestamp, it should be valid.
	 *
	 * @covers \CS\Utils\Tokenizer::is_token_valid
	 */
	public function test_token_valid_for_timestamp() {
		$timestamp = time();
		$token     = Tokenizer::tokenize( $timestamp );

		$this->assertTrue( Tokenizer::is_token_valid( $token, $timestamp ) );
	}

	/**
	 * When a valid token is passed through using a timestamp from 1 day ago, it should be valid.
	 *
	 * @covers \CS\Utils\Tokenizer::is_token_valid
	 */
	public function test_token_valid_for_yesterday_timestamp() {
		$timestamp = strtotime( '-1 day' );

		$token = Tokenizer::tokenize( $timestamp );

		$this->assertTrue( Tokenizer::is_token_valid( $token, $timestamp ) );
	}

	/**
	 * Token is generated from current timestamp, but then validated using a timestamp from 1 day ago.
	 * Because the data being tokenized is different, this should fail.
	 *
	 * @covers \CS\Utils\Tokenizer::is_token_valid
	 */
	public function test_token_invalid_when_wrong_token_provided() {
		$token = Tokenizer::tokenize( time() );

		$this->assertFalse( Tokenizer::is_token_valid( $token, strtotime( '-1 day' ) ) );
	}

	/**
	 * If the signing key is regenerated, previously generated tokens should be invalidated.
	 *
	 * @covers \CS\Utils\Tokenizer::is_token_valid
	 */
	public function test_regenerating_key_invalidates_token() {
		$timestamp = time();
		$token     = Tokenizer::tokenize( $timestamp );

		// This should be valid.
		$this->assertTrue( Tokenizer::is_token_valid( $token, $timestamp ) );

		// But if we delete the signing key, a new one will be generated, which should then invalidate the above token.
		delete_option( 'cs_tokenizer_signing_key' );
		$this->assertFalse( Tokenizer::is_token_valid( $token, $timestamp ) );
	}

}
