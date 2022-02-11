<?php
/**
 * Note Functions.
 *
 * @package     CS
 * @subpackage  Notes
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add a note.
 *
 * @since 3.0
 *
 * @param array $data {
 *     Array of note data. Default empty.
 *
 *     The `date_created` and `date_modified` parameters do not need to be passed.
 *     They will be automatically populated if empty.
 *
 *     @type int    $object_id     Object ID that the note refers to. This would
 *                                 be an ID that corresponds to the object type
 *                                 specified. E.g. an object ID of 25 with object
 *                                 type of `order` refers to order 25 in the
 *                                 `cs_orders` table. Default empty.
 *     @type string $object_type   Object type that the note refers to.
 *                                 E.g. `discount` or `order`. Default empty.
 *     @type int    $user_id       ID of the current WordPress user logged in.
 *                                 Default 0.
 *     @type string $content       Note content. Default empty.
 *     @type string $date_created  Optional. Automatically calculated on add/edit.
 *                                 The date & time the note was inserted.
 *                                 Format: YYYY-MM-DD HH:MM:SS. Default empty.
 *     @type string $date_modified Optional. Automatically calculated on add/edit.
 *                                 The date & time the note was last modified.
 *                                 Format: YYYY-MM-DD HH:MM:SS. Default empty.
 * }
 * @return int|false ID of newly created note, false on error.
 */
function cs_add_note( $data = array() ) {

	// An object ID and object type must be supplied for every note that is
	// inserted into the database.
	if ( empty( $data['object_id'] ) || empty( $data['object_type'] ) ) {
		return false;
	}

	// Instantiate a query object
	$notes = new CS\Database\Queries\Note();

	return $notes->add_item( $data );
}

/**
 * Delete a note.
 *
 * @since 3.0
 *
 * @param int $note_id Note ID.
 * @return int|false `1` if the note was deleted successfully, false on error.
 */
function cs_delete_note( $note_id = 0 ) {
	$notes = new CS\Database\Queries\Note();

	return $notes->delete_item( $note_id );
}

/**
 * Update a note.
 *
 * @since 3.0
 *
 * @param int   $note_id Note ID.
 * @param array $data {
 *     Array of note data. Default empty.
 *
 *     @type int    $object_id     Object ID that the note refers to. This would
 *                                 be an ID that corresponds to the object type
 *                                 specified. E.g. an object ID of 25 with object
 *                                 type of `order` refers to order 25 in the
 *                                 `cs_orders` table. Default empty.
 *     @type string $object_type   Object type that the note refers to.
 *                                 E.g. `discount` or `order`. Default empty.
 *     @type int    $user_id       ID of the current WordPress user logged in.
 *                                 Default 0.
 *     @type string $content       Note content. Default empty.
 *     @type string $date_created  Optional. Automatically calculated on add/edit.
 *                                 The date & time the note was inserted.
 *                                 Format: YYYY-MM-DD HH:MM:SS. Default empty.
 *     @type string $date_modified Optional. Automatically calculated on add/edit.
 *                                 The date & time the note was last modified.
 *                                 Format: YYYY-MM-DD HH:MM:SS. Default empty.
 * }
 *
 * @return int|false Number of rows updated if successful, false otherwise.
 */
function cs_update_note( $note_id = 0, $data = array() ) {
	$notes = new CS\Database\Queries\Note();

	return $notes->update_item( $note_id, $data );
}

/**
 * Get a note by ID.
 *
 * @since 3.0
 *
 * @param int $note_id Note ID.
 * @return CS\Notes\Note Note object if successful, false otherwise.
 */
function cs_get_note( $note_id = 0 ) {
	$notes = new CS\Database\Queries\Note();

	// Return note
	return $notes->get_item( $note_id );
}

/**
 * Get a note by a specific field value.
 *
 * @since 3.0
 *
 * @param string $field Database table field.
 * @param string $value Value of the row.
 *
 * @return CS\Notes\Note Note object if successful, false otherwise.
 */
function cs_get_note_by( $field = '', $value = '' ) {
	$notes = new CS\Database\Queries\Note();

	// Return note
	return $notes->get_item_by( $field, $value );
}

/**
 * Query for notes.
 *
 * @see \CS\Database\Queries\Note::__construct()
 *
 * @since 3.0
 *
 * @param array $args Arguments. See `CS\Database\Queries\Note` for
 *                    accepted arguments.
 * @return \CS\Notes\Note[] Array of `Note` objects.
 */
function cs_get_notes( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'number' => 30
	) );

	// Instantiate a query object
	$notes = new CS\Database\Queries\Note();

	// Return notes
	return $notes->query( $r );
}

/**
 * Count notes.
 *
 * @see \CS\Database\Queries\Note::__construct()
 *
 * @since 3.0
 *
 * @param array $args Arguments. See `CS\Database\Queries\Note` for
 *                    accepted arguments.
 * @return int Number of notes returned based on query arguments passed.
 */
function cs_count_notes( $args = array() ) {

	// Parse args
	$r = wp_parse_args( $args, array(
		'count' => true
	) );

	// Query for count(s)
	$notes = new CS\Database\Queries\Note( $r );

	// Return count(s)
	return absint( $notes->found_items );
}
