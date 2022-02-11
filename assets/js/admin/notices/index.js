/**
 * Deletes the debug log file and disables logging.
 */
; ( function ( document, $ ) {
	'use strict';

	$( '#cs-disable-debug-log' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( this ).attr( 'disabled', true );
		var notice = $( '#cs-debug-log-notice' );
		$.ajax( {
			type: "GET",
			data: {
				action: 'cs_disable_debugging',
				nonce: $( '#cs_debug_log_delete' ).val(),
			},
			url: ajaxurl,
			success: function ( response ) {
				notice.empty().append( response.data );
				setTimeout( function () {
					notice.slideUp();
				}, 3000 );
			}
		} ).fail( function ( response ) {
			notice.empty().append( response.responseJSON.data );
		} );
	} );
} )( document, jQuery );
