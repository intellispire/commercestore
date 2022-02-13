/* global cs_stripe_vars, location */

/**
 * Internal dependencies.
 */
/**
 * External dependencies
 */
import { mountCardElement, getBillingDetails } from 'frontend/elements';
import { apiRequest, hasValidInputs, triggerBrowserValidation, generateNotice, forEach } from 'utils';

/**
 * Binds events and sets up "Add New" form.
 */
export function addNewForm() {
	// Mount Card Element.
	window.csStripe.cardElement = mountCardElement( window.csStripe.elements() );

	// Toggles and submission.
	document.querySelector( '.cs-stripe-add-new' ).addEventListener( 'click', onToggleForm );
	document.getElementById( 'cs-stripe-add-new-cancel' ).addEventListener( 'click', onToggleForm );
	document.getElementById( 'cs-stripe-add-new-card' ).addEventListener( 'submit', onAddPaymentMethod );

	// Set "Card Name" field as required by HTML5
	document.getElementById( 'card_name' ).required = true;
}

/**
 * Handles toggling of "Add New" form button and submission.
 *
 * @param {Event} e click event.
 */
function onToggleForm( e ) {
	e.preventDefault();

	const form = document.getElementById( 'cs-stripe-add-new-card' );
	const formFields = form.querySelector( '.cs-stripe-add-new-card' );
	const isFormVisible = 'block' === formFields.style.display;

	const cancelButton = form.querySelector( '#cs-stripe-add-new-cancel' );

	// Trigger a `submit` event.
	if ( isFormVisible && cancelButton !== e.target ) {
		const submitEvent = document.createEvent( 'Event' );

		submitEvent.initEvent( 'submit', true, true );
		form.dispatchEvent( submitEvent );
	// Toggle form.
	} else {
		formFields.style.display = ! isFormVisible ? 'block' : 'none';
		cancelButton.style.display = ! isFormVisible ? 'inline-block' : 'none';
	}
}

/**
 * Adds a new Source to the Customer.
 *
 * @param {Event} e submit event.
 */
function onAddPaymentMethod( e ) {
	e.preventDefault();

	const form = e.target;

	if ( ! hasValidInputs( form ) ) {
		triggerBrowserValidation( form );
	} else {
		try {
			disableForm();

			createPaymentMethod( form )
				.then( addPaymentMethod )
				.catch( ( error ) => {
					handleNotice( error );
					enableForm();
				} );
		} catch ( error ) {
			handleNotice( error );
			enableForm();
		}
	}
}

/**
 * Add a PaymentMethod.
 *
 * @param {Object} paymentMethod
 */
export function addPaymentMethod( paymentMethod ) {
	apiRequest( 'csx_add_payment_method', {
		payment_method_id: paymentMethod.id,
		nonce: document.getElementById( 'cs-stripe-add-card-nonce' ).value,
	} )
		/**
		 * Shows an error when the API request fails.
		 *
		 * @param {Object} response API Request response.
		 */
		.fail( handleNotice )
		/**
		 * Shows a success notice and automatically redirect.
		 *
		 * @param {Object} response API Request response.
		 */
		.done( function( response ) {
			handleNotice( response, 'success' );

			// Automatically redirect on success.
			setTimeout( function() {
				location.reload();
			}, 1500 );
		} );
}

/**
 * Creates a PaymentMethod from a card and billing form.
 *
 * @param {HTMLElement} billingForm Form with billing fields to retrieve data from.
 * @return {Object} Stripe PaymentMethod.
 */
function createPaymentMethod( billingForm ) {
	return window.csStripe
		// Create a PaymentMethod with stripe.js
		.createPaymentMethod(
			'card',
			window.csStripe.cardElement,
			{
				billing_details: getBillingDetails( billingForm ),
			}
		)
		/**
		 * Handles PaymentMethod creation response.
		 *
		 * @param {Object} result PaymentMethod creation result.
		 */
		.then( function( result ) {
			if ( result.error ) {
				throw result.error;
			}

			return result.paymentMethod;
		} );
}

/**
 * Disables "Add New" form.
 */
function disableForm() {
	const submit = document.querySelector( '.cs-stripe-add-new' );

	submit.value = submit.dataset.loading;
	submit.disabled = true;
}

/**
 * Enables "Add New" form.
 */
function enableForm() {
	const submit = document.querySelector( '.cs-stripe-add-new' );

	submit.value = submit.dataset.submit;
	submit.disabled = false;
}

/**
 * Handles a notice (success or error) for card actions.
 *
 * @param {Object} error Error with message to output.
 * @param {string} type Notice type.
 */
export function handleNotice( error, type = 'error' ) {
	// Create the new notice.
	const notice = generateNotice(
		( error && error.message ) ? error.message : cs_stripe_vars.generic_error,
		type
	);

	// Hide previous notices.
	forEach( document.querySelectorAll( '.cs-stripe-alert' ), function( alert ) {
		alert.remove();
	} );

	// Show new notice.
	document.querySelector( '.cs-stripe-add-card-actions' )
		.insertBefore( notice, document.querySelector( '.cs-stripe-add-new' ) );
}
