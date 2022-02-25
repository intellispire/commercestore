<?php
namespace CS\Api_Request_Logs;

/**
 * Logs Meta DB Tests
 *
 * @group cs_logs_db
 * @group database
 * @group cs_logs
 *
 * @coversDefaultClass \CS\Database\Queries\Log_Api_Request
 */
class Api_Request_Log_Meta_Tests extends \CS_UnitTestCase {

	/**
	 * Discount object test fixture.
	 *
	 * @access protected
	 * @var    Log
	 */
	protected static $log;

	/**
	 * Set up fixtures.
	 */
	public static function wpsetUpBeforeClass() : void  {
		self::$log = parent::cs()->api_request_log->create_and_get();
	}

	public function tearDown() {
		parent::tearDown();

		cs_get_component_interface( 'log_api_request', 'meta' )->truncate();
	}

	/**
	 * @covers \CS\Database\Queries\Log_Api_Request::add_meta()
	 * @covers \CS\Api_Request_Logs\Api_Request_Log::add_meta()
	 */
	public function test_add_metadata_with_empty_key_value_should_return_false() {
		$this->assertFalse( cs_add_api_request_log_meta( self::$log->id, '', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Log_Api_Request::add_meta()
	 * @covers \CS\Api_Request_Logs\Api_Request_Log::add_meta()
	 */
	public function test_add_metadata_with_empty_value_should_be_true() {
		$this->assertSame( 1, cs_add_api_request_log_meta( self::$log->id, 'test_key', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Log_Api_Request::add_meta()
	 * @covers \CS\Api_Request_Logs\Api_Request_Log::add_meta()
	 */
	public function test_add_metadata_with_key_value_should_not_be_empty() {
		$this->assertSame( 1, cs_add_api_request_log_meta( self::$log->id, 'test_key', '1' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Log_Api_Request::update_meta()
	 * @covers \CS\Api_Request_Logs\Api_Request_Log::update_meta()
	 */
	public function test_update_metadata_with_empty_key_value_should_be_empty() {
		$this->assertSame( false, cs_update_api_request_log_meta( self::$log->id, '', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Log_Api_Request::update_meta()
	 * @covers \CS\Api_Request_Logs\Api_Request_Log::update_meta()
	 */
	public function test_update_metadata_with_empty_value_should_not_be_empty() {
		$this->assertSame( 1, cs_update_api_request_log_meta( self::$log->id, 'test_key_2', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Log_Api_Request::update_meta()
	 * @covers \CS\Api_Request_Logs\Api_Request_Log::update_meta()
	 */
	public function test_update_metadata_with_key_value_should_not_be_empty() {
		$this->assertSame( 1, cs_update_api_request_log_meta( self::$log->id, 'test_key_2', '1' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Log_Api_Request::get_meta()
	 * @covers \CS\Api_Request_Logs\Api_Request_Log::get_meta()
	 */
	public function test_get_metadata_with_no_args_should_be_empty() {
		$this->assertSame( array(), cs_get_api_request_log_meta( self::$log->id ) );
	}

	/**
	 * @covers \CS\Database\Queries\Log_Api_Request::get_meta()
	 * @covers \CS\Api_Request_Logs\Api_Request_Log::get_meta()
	 */
	public function test_get_metadata_with_invalid_key_should_be_empty() {
		$this->assertSame( '', cs_get_api_request_log_meta( self::$log->id, 'key_that_does_not_exist', true ) );
	}

	/**
	 * @covers \CS\Database\Queries\Log_Api_Request::get_meta()
	 * @covers \CS\Api_Request_Logs\Api_Request_Log::get_meta()
	 */
	public function test_get_metadata_after_update_should_return_that_value() {
		cs_update_api_request_log_meta( self::$log->id, 'test_key_2', '1' );
		$this->assertSame( '1', cs_get_api_request_log_meta( self::$log->id, 'test_key_2', true ) );
	}

	/**
	 * @covers \CS\Database\Queries\Log_Api_Request::delete_meta()
	 * @covers \CS\Api_Request_Logs\Api_Request_Log::delete_meta()
	 */
	public function test_delete_metadata_with_valid_key_should_return_true() {
		cs_update_api_request_log_meta( self::$log->id, 'test_key', '1' );
		$this->assertTrue( cs_delete_api_request_log_meta( self::$log->id, 'test_key' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Log_Api_Request::delete_meta()
	 * @covers \CS\Api_Request_Logs\Api_Request_Log::delete_meta()
	 */
	public function test_delete_metadata_with_invalid_key_should_return_false() {
		$this->assertFalse( cs_delete_api_request_log_meta( self::$log->id, 'key_that_does_not_exist' ) );
	}
}
