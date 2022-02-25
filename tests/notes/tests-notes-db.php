<?php
namespace CS\Notes;

/**
 * Notes DB Tests.
 *
 * @group cs_notes_db
 * @group database
 * @group cs_notes
 *
 * @coversDefaultClass \CS\Database\Queries\Notes
 */
class Tests_Notes_DB extends \CS_UnitTestCase {

	/**
	 * Notes fixture.
	 *
	 * @access protected
	 * @var array
	 */
	protected static $notes = array();

	/**
	 * Set up fixtures once.
	 */
	public static function wpsetUpBeforeClass() : void  {
		self::$notes = parent::cs()->note->create_many( 5 );
	}

	public function test_installed() {
		$this->assertTrue( cs_get_component_interface( 'note', 'table' )->exists() );
	}

	/**
	 * @covers ::insert()
	 */
	public function test_insert_should_return_false_if_no_object_id_supplied() {
		$this->assertFalse( cs_add_note( array(
			'object_type' => 'payment',
			'content'     => 'Payment status changed',
		) ) );
	}

	/**
	 * @covers ::insert()
	 */
	public function test_insert_should_return_false_if_no_object_type_supplied() {
		$this->assertFalse( cs_add_note( array(
			'object_id' => 6278,
			'content'   => 'Payment status changed',
		) ) );
	}

	/**
	 * @covers ::insert()
	 */
	public function test_insert_with_valid_data() {
		$this->assertGreaterThan( 0, cs_add_note( array(
			'object_type' => 'payment',
			'object_id'   => 6278,
			'content'     => 'Payment status changed',
		) ) );
	}

	/**
	 * @covers ::insert()
	 */
	public function test_insert_with_invalid_data_should_return_false() {
		$this->assertFalse( cs_add_note( array( ) ) );
	}

	/**
	 * @covers ::update()
	 */
	public function test_update_should_return_false_if_no_row_id_supplied() {
		$this->assertFalse( cs_update_note( 0 ) );
	}

	/**
	 * @covers ::update()
	 */
	public function test_note_object_after_update_should_return_true() {
		$this->assertSame( 1, cs_update_note( self::$notes[0], array(
			'content' => 'Note with updated body',
		) ) );

		$note = cs_get_note( self::$notes[0] );

		$this->assertSame( 'Note with updated body', $note->content );
	}

	/**
	 * @covers ::delete()
	 */
	public function test_delete_should_return_false_if_no_row_id_supplied() {
		$this->assertFalse( cs_delete_note( 0 ) );
	}

	/**
	 * @covers ::delete()
	 */
	public function test_delete_should_return_false() {
		$this->assertSame( 1, cs_delete_note( self::$notes[0] ) );

		$note = cs_get_note( self::$notes[0] );

		$this->assertFalse( $note );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_with_number_should_return_true() {
		$notes = cs_get_notes( array(
			'number' => 10,
		) );

		$this->assertCount( 5, $notes );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_with_offset_should_return_true() {
		$notes = cs_get_notes( array(
			'number' => 10,
			'offset' => 4,
		) );

		$this->assertCount( 1, $notes );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_with_search_should_return_true() {
		$notes = cs_get_notes( array(
			'search' => 'Payment status changed for object with ID',
		) );

		$this->assertCount( 5, $notes );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_with_orderby_object_id_and_order_asc_should_return_true() {
		$notes = cs_get_notes( array(
			'orderby' => 'object_id',
			'order'   => 'asc'
		) );

		$this->assertTrue( $notes[0]->object_id < $notes[1]->object_id );
	}
	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_with_orderby_object_id_and_order_desc_should_return_true() {
		$notes = cs_get_notes( array(
			'orderby' => 'object_id',
			'order'   => 'desc'
		) );

		$this->assertTrue( $notes[0]->object_id > $notes[1]->object_id );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_with_orderby_content_and_order_asc_should_return_true() {
		$notes = cs_get_notes( array(
			'orderby' => 'content',
			'order'   => 'asc'
		) );

		$this->assertTrue( $notes[0]->content < $notes[1]->content );
	}
	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_with_orderby_content_and_order_desc_should_return_true() {
		$notes = cs_get_notes( array(
			'orderby' => 'content',
			'order'   => 'desc'
		) );
		$this->assertTrue( $notes[0]->content > $notes[1]->content );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_with_orderby_user_id_and_order_asc_should_return_true() {
		$notes = cs_get_notes( array(
			'orderby' => 'user_id',
			'order'   => 'asc'
		) );

		$this->assertTrue( $notes[0]->user_id < $notes[1]->user_id );
	}
	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_with_orderby_user_id_and_order_desc_should_return_true() {
		$notes = cs_get_notes( array(
			'orderby' => 'user_id',
			'order'   => 'desc'
		) );

		$this->assertTrue( $notes[0]->user_id > $notes[1]->user_id );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_with_order_asc_should_return_true() {
		$notes = cs_get_notes( array(
			'order' => 'asc',
		) );

		$this->assertTrue( $notes[0]->id < $notes[1]->id );
	}
	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_with_order_desc_should_return_true() {
		$notes = cs_get_notes( array(
			'order' => 'desc',
		) );
		$this->assertTrue( $notes[0]->id > $notes[1]->id );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_by_object_id_should_be_1() {
		$notes = cs_get_notes( array(
			'object_id' => \WP_UnitTest_Generator_Sequence::$incr
		) );

		$this->assertCount( 1, $notes );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_by_invalid_object_id_should_be_0() {
		$notes = cs_get_notes( array(
			'object_id' => 99999,
		) );

		$this->assertCount( 0, $notes );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_by_object_type_should_be_5() {
		$notes = cs_get_notes( array(
			'object_type' => 'payment',
		) );

		$this->assertCount( 5, $notes );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_by_content_should_be_1() {
		$notes = cs_get_notes( array(
			'content' => 'Payment status changed for object with ID: ' . \WP_UnitTest_Generator_Sequence::$incr,
		) );

		$this->assertCount( 1, $notes );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_get_notes_with_invalid_content_should_be_0() {
		$notes = cs_get_notes( array(
			'content' => 'Payment status changed for object with ID: 99999',
		) );

		$this->assertCount( 0, $notes );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_count_should_be_5() {
		$this->assertSame( 5, cs_count_notes() );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_count_with_valid_object_type_should_be_0() {
		$this->assertSame( 5, cs_count_notes( array(
			'object_type' => 'payment'
		) ) );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_count_with_invalid_object_type_should_be_0() {
		$this->assertSame( 0, cs_count_notes( array(
			'object_type' => 'foo'
		) ) );
	}

	/**
	 * @covers ::get_notes()
	 */
	public function test_count_with_user_id_should_be_1() {
		$this->assertSame( 1, cs_count_notes( array(
			'user_id' => \WP_UnitTest_Generator_Sequence::$incr
		) ) );
	}
}
