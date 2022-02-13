jQuery( document ).ready( function ( $ ) {
	$( '.cs_countries_filter' ).on( 'change', function () {
		const select = $( this ),
			data = {
				action: 'cs_get_shop_states',
				country: select.val(),
				nonce: select.data( 'nonce' ),
				field_name: 'cs_regions_filter',
			};

		$.post( ajaxurl, data, function ( response ) {
			$( 'select.cs_regions_filter' ).find( 'option:gt(0)' ).remove();

			if ( 'nostates' !== response ) {
				$( response ).find( 'option:gt(0)' ).appendTo( 'select.cs_regions_filter' );
			}

			$( 'select.cs_regions_filter' ).trigger( 'chosen:updated' );
		} );

		return false;
	} );
} );
