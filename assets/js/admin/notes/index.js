/**
 * Notes
 */
const CS_Notes = {
	init: function() {
		this.enter_key();
		this.add_note();
		this.remove_note();
	},

	enter_key: function() {
		$( document.body ).on( 'keydown', '#cs-note', function( e ) {
			if ( e.keyCode === 13 && ( e.metaKey || e.ctrlKey ) ) {
				e.preventDefault();
				$( '#cs-add-note' ).click();
			}
		} );
	},

	/**
	 * Ajax handler for adding new notes
	 *
	 * @since 3.0
	 */
	add_note: function() {
		$( '#cs-add-note' ).on( 'click', function( e ) {
			e.preventDefault();

			const cs_button = $( this ),
				cs_note = $( '#cs-note' ),
				cs_notes = $( '.cs-notes' ),
				cs_no_notes = $( '.cs-no-notes' ),
				cs_spinner = $( '.cs-add-note .spinner' ),
				cs_note_nonce = $( '#cs_note_nonce' );

			const postData = {
				action: 'cs_add_note',
				nonce: cs_note_nonce.val(),
				object_id: cs_button.data( 'object-id' ),
				object_type: cs_button.data( 'object-type' ),
				note: cs_note.val(),
			};

			if ( postData.note ) {
				cs_button.prop( 'disabled', true );
				cs_spinner.css( 'visibility', 'visible' );

				$.ajax( {
					type: 'POST',
					data: postData,
					url: ajaxurl,
					success: function( response ) {
						let res = wpAjax.parseAjaxResponse( response );
						res = res.responses[ 0 ];

						cs_notes.append( res.data );
						cs_no_notes.hide();
						cs_button.prop( 'disabled', false );
						cs_spinner.css( 'visibility', 'hidden' );
						cs_note.val( '' );
					},
				} ).fail( function( data ) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
					cs_button.prop( 'disabled', false );
					cs_spinner.css( 'visibility', 'hidden' );
				} );
			} else {
				const border_color = cs_note.css( 'border-color' );

				cs_note.css( 'border-color', 'red' );

				setTimeout( function() {
					cs_note.css( 'border-color', border_color );
				}, userInteractionInterval );
			}
		} );
	},

	/**
	 * Ajax handler for deleting existing notes
	 *
	 * @since 3.0
	 */
	remove_note: function() {
		$( document.body ).on( 'click', '.cs-delete-note', function( e ) {
			e.preventDefault();

			const cs_link = $( this ),
				cs_notes = $( '.cs-note' ),
				cs_note = cs_link.parents( '.cs-note' ),
				cs_no_notes = $( '.cs-no-notes' ),
				cs_note_nonce = $( '#cs_note_nonce' );

			if ( confirm( cs_vars.delete_note ) ) {
				const postData = {
					action: 'cs_delete_note',
					nonce: cs_note_nonce.val(),
					note_id: cs_link.data( 'note-id' ),
				};

				cs_note.addClass( 'deleting' );

				$.ajax( {
					type: 'POST',
					data: postData,
					url: ajaxurl,
					success: function( response ) {
						if ( '1' === response ) {
							cs_note.remove();
						}

						if ( cs_notes.length === 1 ) {
							cs_no_notes.show();
						}

						return false;
					},
				} ).fail( function( data ) {
					if ( window.console && window.console.log ) {
						console.log( data );
					}
					cs_note.removeClass( 'deleting' );
				} );
				return true;
			}
		} );
	},
};

jQuery( document ).ready( function( $ ) {
	CS_Notes.init();
} );
