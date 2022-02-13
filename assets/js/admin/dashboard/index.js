jQuery( document ).ready( function( $ ) {
	if ( $( '#cs_dashboard_sales' ).length ) {
		$.ajax( {
			type: 'GET',
			data: {
				action: 'cs_load_dashboard_widget',
			},
			url: ajaxurl,
			success: function( response ) {
				$( '#cs_dashboard_sales .cs-loading' ).html( response );
			},
		} );
	}
} );
