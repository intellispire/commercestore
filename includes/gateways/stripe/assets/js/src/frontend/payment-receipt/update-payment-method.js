/* global cs_stripe_vars */

/**
 * Internal dependencies
 */
/* eslint-disable */
import {
	getPaymentMethod,
	mountCardElement,
	handle as handleIntent,
	retrieve as retrieveIntent,
} from 'frontend/elements';

import { paymentMethods } from 'frontend/checkout/payment-methods.js';
import { generateNotice, apiRequest } from 'utils';
/* eslint-enable */

/**
 * Binds events and sets up "Update Payment Method" form.
 */
export function updatePaymentMethodForm() {
	// Mount Card Element.
	window.csStripe.cardElement = mountCardElement( window.csStripe.elements() );

	// Setup payment method selector.
	setPaymentMethods();

	document.getElementById( 'csx-update-payment-method' ).addEventListener( 'submit', onAuthorizePayment );
}

/**
 * Setup PaymentMethods.
 *
 * Moves the active item to the currently authenticating PaymentMethod.
 */
function setPaymentMethods() {
	paymentMethods();

	const form = document.getElementById( 'csx-update-payment-method' );
	const input = document.getElementById( form.dataset.paymentMethod );

	// Select the correct PaymentMethod after load.
	if ( input ) {
		const changeEvent = document.createEvent( 'Event' );

		changeEvent.initEvent( 'change', true, true );
		input.checked = true;
		input.dispatchEvent( changeEvent );
	}
}

/**
 * Authorize a PaymentIntent.
 *
 * @param {Event} e submtit event.
 */
async function onAuthorizePayment( e ) {
	e.preventDefault();

	const form = document.getElementById( 'csx-update-payment-method' );

	disableForm();

	try {
		const paymentMethod = await getPaymentMethod( form, window.csStripe.cardElement );

		// Handle PaymentIntent.
		const intent = await retrieveIntent( form.dataset.paymentIntent, 'payment_method' );

		const handledIntent = await handleIntent( intent, {
			payment_method: paymentMethod.id,
		} );

		// Attempt to transition payment status and redirect.
		const authorization = await completeAuthorization( handledIntent.id );

		if ( authorization.payment ) {
			window.location.reload();
		} else {
			throw authorization;
		}
	} catch ( error ) {
		handleException( error );
		enableForm();
	}
}

/**
 * Complete a Payment after the Intent has been authorized.
 *
 * @param {string} intentId Intent ID.
 * @return {Promise} jQuery Promise.
 */
export function completeAuthorization( intentId ) {
	return apiRequest( 'csx_complete_payment_authorization', {
		intent_id: intentId,
	} );
}

/**
 * Disables "Add New" form.
 */
function disableForm() {
	const submit = document.getElementById( 'csx-update-payment-method-submit' );

	submit.value = submit.dataset.loading;
	submit.disabled = true;
}

/**
 * Enables "Add New" form.
 */
function enableForm() {
	const submit = document.getElementById( 'csx-update-payment-method-submit' );

	submit.value = submit.dataset.submit;
	submit.disabled = false;
}

/**
 * Handles a notice (success or error) for authorizing a card.
 *
 * @param {Object} error Error with message to output.
 */
export function handleException( error ) {
	// Create the new notice.
	const notice = generateNotice(
		( error && error.message ) ? error.message : cs_stripe_vars.generic_error,
		'error'
	);

	const container = document.getElementById( 'csx-update-payment-method-errors' );

	container.innerHTML = '';
	container.appendChild( notice );
}
