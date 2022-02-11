/**
 * Sortables
 *
 * This makes certain settings sortable, and attempts to stash the results
 * in the nearest .cs-order input value.
 */
jQuery( document ).ready( function( $ ) {
	const cs_sortables = $( 'ul.cs-sortable-list' );

	if ( cs_sortables.length > 0 ) {
		cs_sortables.sortable( {
			axis: 'y',
			items: 'li',
			cursor: 'move',
			tolerance: 'pointer',
			containment: 'parent',
			distance: 2,
			opacity: 0.7,
			scroll: true,

			/**
			 * When sorting stops, assign the value to the previous input.
			 * This input should be a hidden text field
			 */
			stop: function() {
				const keys = $.map( $( this ).children( 'li' ), function( el ) {
					 return $( el ).data( 'key' );
				} );

				$( this ).prev( 'input.cs-order' ).val( keys );
			},
		} );
	}
} );
