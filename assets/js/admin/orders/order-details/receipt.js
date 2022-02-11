/* global $, ajaxurl */

/**
 * Internal dependencies
 */
import { jQueryReady } from 'utils/jquery.js';

jQueryReady( () => {
	const emails_wrap = $( '.cs-order-resend-receipt-addresses' );

	$( document.body ).on( 'click', '#cs-select-receipt-email', function( e ) {
		e.preventDefault();
		emails_wrap.slideDown();
	} );

	$( document.body ).on( 'change', '.cs-order-resend-receipt-email', function() {
		const selected = $('input:radio.cs-order-resend-receipt-email:checked').val();

		$( '#cs-select-receipt-email').data( 'email', selected );
	} );

	$( document.body).on( 'click', '#cs-select-receipt-email', function () {
		if ( confirm( cs_vars.resend_receipt ) ) {
			const href = $( this ).prop( 'href' ) + '&email=' + $( this ).data( 'email' );
			window.location = href;
		}
	} );

	$( document.body ).on( 'click', '#cs-resend-receipt', function() {
		return confirm( cs_vars.resend_receipt );
	} );
} );
