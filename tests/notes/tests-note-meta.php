<?php
namespace CS\Notes;

/**
 * Note Meta DB Tests
 *
 * @covers CS\Database\Queries\Notes
 * @group cs_notes_db
 * @group database
 * @group cs_notes
 */
class Tests_Note_Meta extends \CS_UnitTestCase {

	/**
	 * Note fixture.
	 *
	 * @access protected
	 * @var    Note
	 */
	protected static $note = null;

	/**
	 * Set up fixtures once.
	 */
	public static function wpsetUpBeforeClass() : void  {
		self::$note = parent::cs()->note->create_and_get();
	}

	public function tearDown() {
		parent::tearDown();

		cs_get_component_interface( 'note', 'meta' )->truncate();
	}

	/**
	 * @covers \CS\Database\Queries\Notes::add_meta()
	 * @covers Note::add_meta()
	 */
	public function test_add_metadata_with_empty_key_value_should_return_false() {
		$this->assertFalse( cs_add_note_meta( self::$note->id, '', '' ) );
	}

	public function test_add_metadata_with_empty_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_add_note_meta( self::$note->id, 'test_key', '' ) );
	}

	public function test_add_metadata_with_key_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_add_note_meta( self::$note->id, 'test_key', '1' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Notes::update_meta()
	 * @covers Note::update_meta()
	 */
	public function test_update_metadata_with_empty_key_value_should_return_false() {
		$this->assertEmpty( cs_update_note_meta( self::$note->id, '', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Notes::update_meta()
	 * @covers Note::update_meta()
	 */
	public function test_update_metadata_with_empty_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_update_note_meta( self::$note->id, 'test_key_2', '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Notes::update_meta()
	 * @covers Note::update_meta()
	 */
	public function test_update_metadata_with_key_value_should_not_be_empty() {
		$this->assertNotEmpty( cs_update_note_meta( self::$note->id, 'test_key_2', '1' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Notes::get_meta()
	 * @covers Note::get_meta()
	 */
	public function test_get_metadata_with_no_args_should_be_empty() {
		$this->assertEmpty( cs_get_note_meta( self::$note->id, '' ) );
	}

	/**
	 * @covers \CS\Database\Queries\Notes::get_meta()
	 * @covers Note::get_meta()
	 */
	public function test_get_metadata_with_invalid_key_should_be_empty() {
		$this->assertEmpty( cs_get_note_meta( self::$note->id, 'key_that_does_not_exist', true ) );
		cs_update_note_meta( self::$note->id, 'test_key_2', '1' );
		$this->assertEquals( '1', cs_get_note_meta( self::$note->id, 'test_key_2', true ) );
		$this->assertInternalType( 'array', cs_get_note_meta( self::$note->id, 'test_key_2', false ) );
	}

	/**
	 * @covers \CS\Database\Queries\Notes::get_meta()
	 * @covers Note::get_meta()
	 */
	public function test_get_metadata_after_update_should_return_1_and_be_of_type_array() {
		cs_update_note_meta( self::$note->id, 'test_key_2', '1' );

		$this->assertEquals( '1', cs_get_note_meta( self::$note->id, 'test_key_2', true ) );
		$this->assertInternalType( 'array', cs_get_note_meta( self::$note->id, 'test_key_2', false ) );
	}

	/**
	 * @covers \CS\Database\Queries\Notes::delete_meta()
	 * @covers Note::delete_meta()
	 */
	public function test_delete_metadata_after_update() {
		cs_update_note_meta( self::$note->id, 'test_key', '1' );

		$this->assertTrue( cs_delete_note_meta( self::$note->id, 'test_key' ) );
		$this->assertFalse( cs_delete_note_meta( self::$note->id, 'key_that_does_not_exist' ) );
	}
}
