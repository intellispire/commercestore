<?php
namespace CS\Logs;

/**
 * Logs Meta DB Tests
 *
 * @group cs_logs_db
 * @group database
 * @group cs_logs
 *
 * @coversDefaultClass \CS\Database\Queries\Logs
 */
class Log_Meta_Tests extends \CS_UnitTestCase {

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
		self::$log = parent::cs()->log->create_and_get();
	}

	public function tear_down() {
		parent::tear_down();

		cs_get_component_interface( 'log', 'meta' )->truncate();
	}

	/**
	 * @covers \CS\Database\Queries\Logs::add_meta()
	 * @covers \CS\Logs\Log::add_meta()
	 */
	public function test_add_metadata_with_empty_key_value_should_return_false() {
		$this->assertFalse( cs_add_log_meta( self::$log->id, '', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Logs::add_meta()
	 * @covers \CS\Logs\Log::add_meta()
	 */
	public function test_add_metadata_with_empty_value_should_be_empty() {
		$this->assertNotEmpty( cs_add_log_meta( self::$log->id, 'test_key', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Logs::add_meta()
	 * @covers \CS\Logs\Log::add_meta()
	 */
	public function test_add_metadata_with_key_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_add_log_meta( self::$log->id, 'test_key', '1' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Logs::update_meta()
	 * @covers \CS\Logs\Log::update_meta()
	 */
	public function test_update_metadata_with_empty_key_value_should_be_empty() {
		$this->assertEmpty( cs_update_note_meta( self::$log->id, '', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Logs::update_meta()
	 * @covers \CS\Logs\Log::update_meta()
	 */
	public function test_update_metadata_with_empty_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_update_log_meta( self::$log->id, 'test_key_2', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Logs::update_meta()
	 * @covers \CS\Logs\Log::update_meta()
	 */
	public function test_update_metadata_with_key_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_update_log_meta( self::$log->id, 'test_key_2', '1' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Logs::get_meta()
	 * @covers \CS\Logs\Log::get_meta()
	 */
	public function test_get_metadata_with_no_args_should_be_empty() {
		$this->assertEmpty( cs_get_note_meta( self::$log->id ) );
	}

	/**
	 * @covers \CS\Database\Queries\Logs::get_meta()
	 * @covers \CS\Logs\Log::get_meta()
	 */
	public function test_get_metadata_with_invalid_key_should_be_empty() {
		$this->assertEmpty( cs_get_log_meta( self::$log->id, 'key_that_does_not_exist', true ) );
	}

	/**
	 * @covers \CS\Database\Queries\Logs::get_meta()
	 * @covers \CS\Logs\Log::get_meta()
	 */
	public function test_get_metadata_after_update_should_return_that_value() {
		cs_update_log_meta( self::$log->id, 'test_key_2', '1' );
		$this->assertEquals( '1', cs_get_log_meta( self::$log->id, 'test_key_2', true ) );
	}

	/**
	 * @covers \CS\Database\Queries\Logs::delete_meta()
	 * @covers \CS\Logs\Log::delete_meta()
	 */
	public function test_delete_metadata_with_valid_key_should_return_true() {
		cs_update_log_meta( self::$log->id, 'test_key', '1' );
		$this->assertTrue( cs_delete_log_meta( self::$log->id, 'test_key' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Logs::delete_meta()
	 * @covers \CS\Logs\Log::delete_meta()
	 */
	public function test_delete_metadata_with_invalid_key_should_return_false() {
		$this->assertFalse( cs_delete_log_meta( self::$log->id, 'key_that_does_not_exist' ) );
	}
}
