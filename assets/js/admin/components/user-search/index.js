jQuery( document ).ready( function( $ ) {
	// AJAX user search
	$( '.cs-ajax-user-search' )

		// Search
		.keyup( function() {
			let user_search = $( this ).val(),
				exclude = '';

			if ( $( this ).data( 'exclude' ) ) {
				exclude = $( this ).data( 'exclude' );
			}

			$( '.cs_user_search_wrap' ).addClass( 'loading' );

			const data = {
				action: 'cs_search_users',
				user_name: user_search,
				exclude: exclude,
			};

			$.ajax( {
				type: 'POST',
				data: data,
				dataType: 'json',
				url: ajaxurl,

				success: function( search_response ) {
					$( '.cs_user_search_wrap' ).removeClass( 'loading' );
					$( '.cs_user_search_results' ).removeClass( 'hidden' );
					$( '.cs_user_search_results span' ).html( '' );
					if ( search_response.results ) {
						$( search_response.results ).appendTo( '.cs_user_search_results span' );
					}
				},
			} );
		} )

		// Hide
		.blur( function() {
			if ( cs_user_search_mouse_down ) {
				cs_user_search_mouse_down = false;
			} else {
				$( this ).removeClass( 'loading' );
				$( '.cs_user_search_results' ).addClass( 'hidden' );
			}
		} )

		// Show
		.focus( function() {
			$( this ).keyup();
		} );

	$( document.body ).on( 'click.eddSelectUser', '.cs_user_search_results span a', function( e ) {
		e.preventDefault();
		const login = $( this ).data( 'login' );
		$( '.cs-ajax-user-search' ).val( login );
		$( '.cs_user_search_results' ).addClass( 'hidden' );
		$( '.cs_user_search_results span' ).html( '' );
	} );

	$( document.body ).on( 'click.eddCancelUserSearch', '.cs_user_search_results a.cs-ajax-user-cancel', function( e ) {
		e.preventDefault();
		$( '.cs-ajax-user-search' ).val( '' );
		$( '.cs_user_search_results' ).addClass( 'hidden' );
		$( '.cs_user_search_results span' ).html( '' );
	} );

	// Cancel user-search.blur when picking a user
	var cs_user_search_mouse_down = false;
	$( '.cs_user_search_results' ).mousedown( function() {
		cs_user_search_mouse_down = true;
	} );
} );
