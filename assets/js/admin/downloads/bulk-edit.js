jQuery( document ).ready( function( $ ) {
	$( 'body' ).on( 'click', '#the-list .editinline', function() {
		let post_id = $( this ).closest( 'tr' ).attr( 'id' );

		post_id = post_id.replace( 'post-', '' );

		const $cs_inline_data = $( '#post-' + post_id );

		const regprice = $cs_inline_data.find( '.column-price .downloadprice-' + post_id ).val();

		// If variable priced product disable editing, otherwise allow price changes
		if ( regprice !== $( '#post-' + post_id + '.column-price .downloadprice-' + post_id ).val() ) {
			$( '.regprice', '#cs-download-data' ).val( regprice ).attr( 'disabled', false );
		} else {
			$( '.regprice', '#cs-download-data' ).val( cs_vars.quick_edit_warning ).attr( 'disabled', 'disabled' );
		}
	} );

	// Bulk edit save
	$( document.body ).on( 'click', '#bulk_edit', function() {
		// define the bulk edit row
		const $bulk_row = $( '#bulk-edit' );

		// get the selected post ids that are being edited
		const $post_ids = new Array();
		$bulk_row.find( '#bulk-titles' ).children().each( function() {
			$post_ids.push( $( this ).attr( 'id' ).replace( /^(ttle)/i, '' ) );
		} );

		// get the stock and price values to save for all the product ID's
		const $price = $( '#cs-download-data input[name="_cs_regprice"]' ).val();

		const data = {
			action: 'cs_save_bulk_edit',
			cs_bulk_nonce: $post_ids,
			post_ids: $post_ids,
			price: $price,
		};

		// save the data
		$.post( ajaxurl, data );
	} );
} );
