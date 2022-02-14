/* global $, cs_stripe_vars */

/**
 * Internal dependencies.
 */
import { generateNotice, fieldValueOrNull } from 'utils'; // eslint-disable-line @wordpress/dependency-group

// Intents.
export * from './intents.js';

/**
 * Mounts an Elements Card to the DOM and adds event listeners to submission.
 *
 * @link https://stripe.com/docs/stripe-js/reference#the-elements-object
 *
 * @param {Elements} elementsInstance Stripe Elements instance.
 * @param {string} toMount Selector to mount Element on.
 * @return {Element} Stripe Element.
 */
export function mountCardElement( elementsInstance, toMount = '#cs-stripe-card-element' ) {
	const elementsOptions = cs_stripe_vars.elementsOptions || {};

	// Try to mimick existing input styles.
	const cardNameEl = document.querySelector( '.card-name.cs-input' );

	if ( cardNameEl ) {
		const inputStyles = window.getComputedStyle( cardNameEl );

		// Inject inline CSS instead of applying to the Element so it can be overwritten.
		const styleTag = document.createElement( 'style' );
		styleTag.innerHTML = `.StripeElement {
			background-color: ${ inputStyles.getPropertyValue( 'background-color' ) };
			border-color: ${ inputStyles.getPropertyValue( 'border-color' ) };
			border-width: ${ inputStyles.getPropertyValue( 'border-width' ) };
			border-style: ${ inputStyles.getPropertyValue( 'border-style' ) };
			border-radius: ${ inputStyles.getPropertyValue( 'border-radius' ) };
			padding: ${ inputStyles.getPropertyValue( 'padding' ) };
		}`;
		document.body.appendChild( styleTag );

		// Add default styles for the iframe input if none exist.
		if ( ! elementsOptions.style ) {
			let fontFamily = inputStyles.getPropertyValue( 'font-family' );

			if ( null !== fontFamily.match( /[\\\/\<\>\!\@\$\%\^\&\*\=\~\`\|\{\}\[\]]/g ) ) {
				fontFamily = 'system-ui';
			}

			elementsOptions.style = {
				base: {
					color: inputStyles.getPropertyValue( 'color' ),
					fontFamily,
					fontSize: inputStyles.getPropertyValue( 'font-size' ),
					fontWeight: inputStyles.getPropertyValue( 'font-weight' ),
					fontSmoothing: inputStyles.getPropertyValue( '-webkit-font-smoothing' ),
				},
			};
		}
	}

	const card = elementsInstance
		.create( 'card', elementsOptions );

	const toMountEl = document.querySelector( toMount );

	card
		.addEventListener( 'change', ( event ) => {
			handleElementError( event, toMountEl );
		} )
		.mount( toMountEl );

	return card;
}

/**
 * Handles error output for Elements Card.
 *
 * @param {Event} event Change event on the Card Element.
 * @param {HTMLElement} toMountEl Element the card field is being mounted on.
 */
function handleElementError( event, toMountEl ) {
	const errorsContainer = toMountEl.nextElementSibling;

	// Only show one error at once.
	errorsContainer.innerHTML = '';

	if ( event.error ) {
		errorsContainer.appendChild( generateNotice( event.error.message ) );
	}
}

/**
 * Retrieves (or creates) a PaymentMethod.
 *
 * @param {HTMLElement} billingDetailsForm Form to find data from.
 * @return {Object} PaymentMethod ID and if it previously existed.
 */
export function getPaymentMethod( billingDetailsForm, cardElement ) {
	const selectedPaymentMethod = $( 'input[name="cs_stripe_existing_card"]:checked' );

	// An existing PaymentMethod is selected.
	if ( selectedPaymentMethod.length > 0 && 'new' !== selectedPaymentMethod.val() ) {
		return Promise.resolve( {
			id: selectedPaymentMethod.val(),
			exists: true,
		} );
	}

	// Create a PaymentMethod using the Element data.
	return window.csStripe
		.createPaymentMethod(
			'card',
			cardElement,
			{
				billing_details: getBillingDetails( billingDetailsForm ),
			}
		)
		.then( function( result ) {
			if ( result.error ) {
				throw result.error;
			}

			return {
				id: result.paymentMethod.id,
				exists: false,
			};
		} );
}

/**
 * Retrieves billing details from the Billing Details sections of a form.
 *
 * @param {HTMLElement} form Form to find data from.
 * @return {Object} Billing details
 */
export function getBillingDetails( form ) {
	return {
		// @todo add Phone
		// @todo add Email
		name: fieldValueOrNull( form.querySelector( '.card-name' ) ),
		address: {
			line1: fieldValueOrNull( form.querySelector( '.card-address' ) ),
			line2: fieldValueOrNull( form.querySelector( '.card-address-2' ) ),
			city: fieldValueOrNull( form.querySelector( '.card-city' ) ),
			state: fieldValueOrNull( form.querySelector( '.card_state' ) ),
			postal_code: fieldValueOrNull( form.querySelector( '.card-zip' ) ),
			country: fieldValueOrNull( form.querySelector( '#billing_country' ) ),
		},
	};
}
