/* global ajaxurl */

jQuery( document ).ready( function( $ ) {
	/**
	 * Display notices
	 */
	const topOfPageNotice = $( '.cs-admin-notice-top-of-page' );
	if ( topOfPageNotice ) {
		const topOfPageNoticeEl = topOfPageNotice.detach();

		$( '#wpbody-content' ).prepend( topOfPageNoticeEl );
		topOfPageNotice.delay( 1000 ).slideDown();
	}

	/**
	 * Dismiss notices
	 */
	$( '.cs-promo-notice' ).each( function() {
		const notice = $( this );

		notice.on( 'click', '.cs-promo-notice-dismiss', function( e ) {
			// Only prevent default behavior for buttons, not links.
			if ( ! $( this ).attr( 'href' ) ) {
				e.preventDefault();
			}

			$.ajax( {
				type: 'POST',
				data: {
					action: 'cs_dismiss_promo_notice',
					notice_id: notice.data( 'id' ),
					nonce: notice.data( 'nonce' ),
					lifespan: notice.data( 'lifespan' )
				},
				url: ajaxurl,
				success: function( response ) {
					notice.slideUp();
				}
			} );
		} );
	} );
} );
