/* global cs_global_vars */

/**
 * Generate markup for a credit card icon based on a passed type.
 *
 * @param {string} type Credit card type.
 * @return HTML markup.
 */
export const getCreditCardIcon = ( type ) => {
	let width;
	let name = type;

	switch ( type ) {
		case 'amex':
			name = 'americanexpress';
			width = 32;
			break;
		default:
			width = 50;
			break;
	}

	return `
    <svg
      width=${ width }
      height=${ 32 }
      class="payment-icon icon-${ name }"
      role="img"
    >
      <use
        href="#icon-${ name }"
        xlink:href="#icon-${ name }">
      </use>
    </svg>`;
};

let ajax_tax_count = 0;

/**
 * Recalulate taxes.
 *
 * @param {string} state State to calculate taxes for.
 * @return {Promise}
 */
export function recalculateTaxes( state ) {
	if ( '1' != cs_global_vars.taxes_enabled ) {
		return;
	} // Taxes not enabled

	const $cs_cc_address = jQuery( '#cs_cc_address' );

	const billing_country = $cs_cc_address.find( '#billing_country' ).val(),
		card_address = $cs_cc_address.find( '#card_address' ).val(),
		card_address_2 = $cs_cc_address.find( '#card_address_2' ).val(),
		card_city = $cs_cc_address.find( '#card_city' ).val(),
		card_state = $cs_cc_address.find( '#card_state' ).val(),
		card_zip = $cs_cc_address.find( '#card_zip' ).val();

	if ( ! state ) {
		state = card_state;
	}

	const postData = {
		action: 'cs_recalculate_taxes',
		card_address: card_address,
		card_address_2: card_address_2,
		card_city: card_city,
		card_zip: card_zip,
		state: state,
		billing_country: billing_country,
		nonce: jQuery( '#cs-checkout-address-fields-nonce' ).val(),
	};

	jQuery( '#cs_purchase_submit [type=submit]' ).after( '<span class="cs-loading-ajax cs-recalculate-taxes-loading cs-loading"></span>' );

	const current_ajax_count = ++ajax_tax_count;

	return jQuery.ajax( {
		type: 'POST',
		data: postData,
		dataType: 'json',
		url: cs_global_vars.ajaxurl,
		xhrFields: {
			withCredentials: true,
		},
		success: function( tax_response ) {
			// Only update tax info if this response is the most recent ajax call.
			// Avoids bug with form autocomplete firing multiple ajax calls at the same time and not
			// being able to predict the call response order.
			if ( current_ajax_count === ajax_tax_count ) {
				if ( tax_response.html ) {
					jQuery( '#cs_checkout_cart_form' ).replaceWith( tax_response.html );
				}
				jQuery( '.cs_cart_amount' ).html( tax_response.total );
				const tax_data = new Object();
				tax_data.postdata = postData;
				tax_data.response = tax_response;
				jQuery( 'body' ).trigger( 'cs_taxes_recalculated', [ tax_data ] );
			}
			jQuery( '.cs-recalculate-taxes-loading' ).remove();
		},
	} ).fail( function( data ) {
		if ( window.console && window.console.log ) {
			console.log( data );
			if ( current_ajax_count === ajax_tax_count ) {
				jQuery( 'body' ).trigger( 'cs_taxes_recalculated', [ tax_data ] );
			}
		}
	} );
}
