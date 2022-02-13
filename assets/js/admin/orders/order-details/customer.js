/* global $ */

/**
 * Internal dependencies
 */
import { jQueryReady } from 'utils/jquery.js';

jQueryReady( () => {

	// Change Customer.
	$( '.cs-payment-change-customer-input' ).on( 'change', function() {
		const $this = $( this ),
			data = {
				action: 'cs_customer_details',
				customer_id: $this.val(),
				nonce: $( '#cs_customer_details_nonce' ).val(),
			};

		if ( '' === data.customer_id ) {
			return;
		}

		$( '.customer-details' ).css( 'display', 'none' );
		$( '#customer-avatar' ).html( '<span class="spinner is-active"></span>' );

		$.post( ajaxurl, data, function( response ) {
			const { success, data } = response;

			if ( success ) {
				$( '.customer-details' ).css( 'display', 'flex' );
				$( '.customer-details-wrap' ).css( 'display', 'flex' );

				$( '#customer-avatar' ).html( data.avatar );
				$( '.customer-name' ).html( data.name );
				$( '.customer-since span' ).html( data.date_created_i18n );
				$( '.customer-record a' ).prop( 'href', data._links.self );
			} else {
				$( '.customer-details-wrap' ).css( 'display', 'none' );
			}
		}, 'json' );
	} );

	$( '.cs-payment-change-customer-input' ).trigger( 'change' );

	// New Customer.
	$( '#cs-customer-details' ).on( 'click', '.cs-payment-new-customer, .cs-payment-new-customer-cancel', function( e ) {
		e.preventDefault();

		var new_customer = $( this ).hasClass( 'cs-payment-new-customer' ),
			cancel = $( this ).hasClass( 'cs-payment-new-customer-cancel' );

		if ( new_customer ) {
			$( '.order-customer-info' ).hide();
			$( '.new-customer' ).show();
		} else if ( cancel ) {
			$( '.order-customer-info' ).show();
			$( '.new-customer' ).hide();
		}

		var new_customer = $( '#cs-new-customer' );

		if ( $( '.new-customer' ).is( ':visible' ) ) {
			new_customer.val( 1 );
		} else {
			new_customer.val( 0 );
		}
	} );

} );
