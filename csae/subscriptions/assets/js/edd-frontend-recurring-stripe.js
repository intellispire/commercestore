/* global eddStripe, eddRecurringStripe, edd_stripe_vars, $ */

var eddStripe = window.eddStripe;
var api = eddStripe._plugin;

/**
 * Map jQuery to $
 */
( function( $ ) {

	/**
	 * DOM ready.
	 */
	api.domReady( function() {
		var updatePaymentMethodForm              = document.getElementById( 'edd-recurring-form' );
		var updatePaymentMethodFormSubmit        = document.getElementById( 'edd-recurring-update-submit' );
		var updatePaymentMethodFormSubmitDefault = updatePaymentMethodFormSubmit.value;

		if ( ! updatePaymentMethodForm ) {
			return;
		}

		setupPaymentMethods();
		setupPaymentForm();

		updatePaymentMethodForm.addEventListener( 'submit', onUpdatePaymentMethod );

		/**
		 * Sets up payment method selectors.
		 */
		function setupPaymentMethods() {
			api.paymentMethods();

			// Attempt to select the method used.
			var defaultPaymentMethod = document.querySelector( '[name="edd_recurring_stripe_default_payment_method"]' ).value;
			var defaultPaymentMethodOption = document.getElementById( defaultPaymentMethod );

			if ( ! defaultPaymentMethodOption ) {
				return;
			}

			var paymentMethodChangeEvent = document.createEvent( 'Event' );
			paymentMethodChangeEvent.initEvent( 'change', true, false );
			defaultPaymentMethodOption.dispatchEvent( paymentMethodChangeEvent );

			defaultPaymentMethodOption.checked = true;
		};

		/**
		 * Enables form.
		 */
		function setupPaymentForm() {
			eddStripe.cardElement = api.mountCardElement(
				eddStripe.elements(),
				'#edd-recurring-form #edd-stripe-card-element'
			);
		};

		/**
		 * Disables form.
		 */
		function disableForm() {
			updatePaymentMethodFormSubmit.disabled  = true;
			updatePaymentMethodFormSubmit.value = eddRecurringStripe.i18n.loading;
		}

		/**
		 * Enables form.
		 */
		function enableForm() {
			updatePaymentMethodFormSubmit.disabled  = false;
			updatePaymentMethodFormSubmit.value = updatePaymentMethodFormSubmitDefault;
		}

		/**
		 * Shows exceptions.
		 */
		function handleException( error ) {
			var form = document.getElementById( 'edd-recurring-form' );

			// Reenable form.
			enableForm();
			form.addEventListener( 'submit', onUpdatePaymentMethod );

			var notice = api.generateNotice( ( error && error.message ) ? error.message : edd_stripe_vars.generic_error );

			// Hide previous messages.
			// @todo don't use jQuery
			$( '.edd-stripe-alert' ).remove();

			form.appendChild( notice );
		}

		/**
		 * Attaches a PaymentMethod to a Subscription.
		 *
		 * @param {String} subscriptionId \Stripe\Subscription ID.
		 * @param {String} paymentMethodId \Stripe\PaymentMethod ID.
		 * @return {Promise} jQuery Promise.
		 */
		function updateSubscriptionPaymentMethod( subscriptionId, paymentMethod, billingAddress ) {
			return api.apiRequest( 'edd_recurring_update_subscription_payment_method', {
				subscription_id: subscriptionId,
				payment_method_id: paymentMethod.id,
				payment_method_exists: paymentMethod.exists,
				billing_address: billingAddress,
				nonce: document.getElementById( 'edd_recurring_update_nonce' ).value,
			} );
		}

		/**
		 * Handles form submission.
		 *
		 * @param {Event} e submit event.
		 */
		function onUpdatePaymentMethod( e ) {
			var form = e.target;
			var subscriptionId = document.querySelector( '[name="edd_recurring_stripe_profile_id"]' );

			if ( ! subscriptionId ) {
				return;
			}

			e.preventDefault();
			disableForm();

			var paymentIntentId = document.querySelector( '[name="edd_recurring_stripe_payment_intent"]' );

			// Callback hell.
			// @todo Modernize.

			try {
				// Retrieve or create a PaymentMethod.
				api.getPaymentMethod( form, eddStripe.cardElement )
					.then( function( paymentMethod ) {

						// Update an existing Subscription default_payment_method
						updateSubscriptionPaymentMethod(
							subscriptionId.value,
							paymentMethod,
							api.getBillingDetails( form ).address
						)
							.then( function( response ) {

								// Simply updating default PaymentMethod for future purchases, do no more here.
								if ( ! paymentIntentId ) {
									form.removeEventListener( 'submit', onUpdatePaymentMethod );
									form.submit();

									return;
								}

								// Retrieve the latest intent failure.
								api.retrieveIntent( paymentIntentId.value, 'payment_intent' )
									.then( function( paymentIntent ) {

										// Handle any further PaymentIntent actions.
										api.handleIntent( paymentIntent, {
											payment_method: paymentMethod.id,
										} )
											.then( function( result ) {
												if ( result.error ) {
													throw error;
												}

												form.removeEventListener( 'submit', onUpdatePaymentMethod );
												form.submit();
											} )
											.catch( handleException );
									} )
									.fail( handleException );
							} )
							.fail( handleException )
					} )
					.catch( handleException );
			} catch ( error ) {
				handleException( error );
			}
		}
	} )
} ) ( jQuery );
