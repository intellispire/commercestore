/* global jQuery */

jQuery( document ).ready( function( $ ) {
	$( '.cs-advanced-filters-button' ).on( 'click', function( e ) {
		e.preventDefault();

		$( this ).closest( '#cs-advanced-filters' ).toggleClass( 'open' );
	} );
} );
