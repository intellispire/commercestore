/* global eddRecurringPayPal */

document.addEventListener( 'DOMContentLoaded', function() {
	var paymentProcessingWrap = document.getElementById( 'cs-payment-processing' );
	if ( ! paymentProcessingWrap ) {
		return;
	}

	var processingForm = new FormData( paymentProcessingWrap );
	processingForm.append( 'action', 'cs_recurring_confirm_transaction' );
	processingForm.append( 'nonce', eddRecurringPayPal.nonce );
	processingForm.append( 'timestamp', eddRecurringPayPal.timestamp );
	processingForm.append( 'token', eddRecurringPayPal.token );

	eddRecurringConfirmPayPalTransaction( processingForm, 1 );
} );

function eddRecurringConfirmPayPalTransaction( form, attemptNumber ) {
	form.set( 'attempt_number', attemptNumber );

	fetch( eddRecurringPayPal.ajaxurl, {
		method: 'POST',
		body: form
	} ).then( function( response ) {
		return response.json();
	} ).then( function( responseData ) {
		if ( responseData.data.redirect_url ) {
			window.location = responseData.data.redirect_url;
		} else if ( responseData.data && true === responseData.data.retry ) {
			var milliseconds = responseData.data && responseData.data.milliseconds ? responseData.data.milliseconds : 3000;

			setTimeout( function() {
				eddRecurringConfirmPayPalTransaction( form, attemptNumber + 1 );
			}, milliseconds );
		}
	} );
}
