/**
 * Internal dependencies
 */
import { apiRequest } from 'utils'; // eslint-disable-line @wordpress/dependency-group

/**
 * Retrieve a PaymentIntent.
 *
 * @param {string} intentId Intent ID.
 * @param {string} intentType Intent type. payment_intent or setup_intent.
 * @return {Promise} jQuery Promise.
 */
export function retrieve( intentId, intentType = 'payment_intent' ) {
	return apiRequest( 'csx_get_intent', {
		intent_id: intentId,
		intent_type: intentType,
	} )
		// Returns just the PaymentIntent object.
		.then( function( response ) {
			return response.intent;
		} );
}

/**
 * Confirm a PaymentIntent.
 *
 * @param {Object} intent Stripe PaymentIntent or SetupIntent.
 * @return {Promise} jQuery Promise.
 */
export function confirm( intent ) {
	return apiRequest( 'csx_confirm_intent', {
		intent_id: intent.id,
		intent_type: intent.object,
	} )
		// Returns just the PaymentIntent object for easier reprocessing.
		.then( function( response ) {
			return response.intent;
		} );
}

/**
 * Capture a PaymentIntent.
 *
 * @param {Object} intent Stripe PaymentIntent or SetupIntent.
 * @param {Object} data Extra data to pass to the intent action.
 * @return {Promise} jQuery Promise.
 */
export function capture( intent, data ) {
	if ( 'requires_capture' !== intent.status ) {
		return Promise.resolve( intent );
	}

	return apiRequest( 'csx_capture_intent', {
		intent_id: intent.id,
		intent_type: intent.object,
		...data,
	} )
		// Returns just the PaymentIntent object for easier reprocessing.
		.then( function( response ) {
			return response.intent;
		} );
}

/**
 * Update a PaymentIntent.
 *
 * @param {Object} intent Stripe PaymentIntent or SetupIntent.
 * @param {Object} data PaymentIntent data to update.
 * @return {Promise} jQuery Promise.
 */
export function update( intent, data ) {
	return apiRequest( 'csx_update_intent', {
		intent_id: intent.id,
		intent_type: intent.object,
		...data,
	} )
		// Returns just the PaymentIntent object for easier reprocessing.
		.then( function( response ) {
			return response.intent;
		} );
}

/**
 * Determines if the PaymentIntent requires further action.
 *
 * @link https://stripe.com/docs/stripe-js/reference
 *
 * @param {Object} intent Stripe PaymentIntent or SetupIntent.
 * @param {Object} data Extra data to pass to the intent action.
 */
export async function handle( intent, data ) {
	// requires_confirmation
	if ( 'requires_confirmation' === intent.status ) {
		// Attempt to capture.
		const confirmedIntent = await confirm( intent );

		// Run through again.
		return await handle( confirmedIntent );
	}

	// requires_payment_method
	// @link https://stripe.com/docs/payments/intents#intent-statuses
	if (
		'requires_payment_method' === intent.status ||
		'requires_source' === intent.status
	) {
		// Attempt to update.
		const updatedIntent = await update( intent, data );

		// Run through again.
		return await handle( updatedIntent, data );
	}

	// requires_action
	// @link https://stripe.com/docs/payments/intents#intent-statuses
	if (
		( 'requires_action' === intent.status && 'use_stripe_sdk' === intent.next_action.type ) ||
		( 'requires_source_action' === intent.status && 'use_stripe_sdk' === intent.next_action.type )
	) {
		let cardHandler = 'setup_intent' === intent.object ? 'handleCardSetup' : 'handleCardAction';

		if ( 'automatic' === intent.confirmation_method ) {
			cardHandler = 'handleCardPayment';
		}

		return window.csStripe[ cardHandler ]( intent.client_secret )
			.then( async ( result ) => {
				if ( result.error ) {
					throw result.error;
				}

				const {
					setupIntent,
					paymentIntent,
				} = result;

				// Run through again.
				return await handle( setupIntent || paymentIntent );
			} );
	}

	// Nothing done, return Intent.
	return intent;
}
