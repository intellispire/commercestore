/* global csPayPalVars, cs_global_vars */

var CS_PayPal = {
	isMounted: false,

	/**
	 * Initializes PayPal buttons and sets up some events.
	 */
	init: function() {
		if ( document.getElementById( 'cs-paypal-container' ) ) {
			this.initButtons( '#cs-paypal-container', 'checkout' );
		}

		jQuery( document.body ).on( 'cs_discount_applied', this.maybeRefreshPage );
		jQuery( document.body ).on( 'cs_discount_removed', this.maybeRefreshPage );
	},

	/**
	 * Determines whether or not the selected gateway is PayPal.
	 * @returns {boolean}
	 */
	isPayPal: function() {
		var chosenGateway = false;
		if ( jQuery('select#cs-gateway, input.cs-gateway').length ) {
			chosenGateway = jQuery("meta[name='cs-chosen-gateway']").attr('content');
		}

		if ( ! chosenGateway && cs_scripts.default_gateway ) {
			chosenGateway = cs_scripts.default_gateway;
		}

		return 'paypal_commerce' === chosenGateway;
	},

	/**
	 * Refreshes the page when adding or removing a 100% discount.
	 *
	 * @param e
	 * @param {object} data
	 */
	maybeRefreshPage: function( e, data ) {
		if ( 0 === data.total_plain && CS_PayPal.isPayPal() ) {
			window.location.reload();
		} else if ( ! CS_PayPal.isMounted && CS_PayPal.isPayPal() && data.total_plain > 0 ) {
			window.location.reload();
		}
	},

	/**
	 * Sets the error HTML, depending on the context.
	 *
	 * @param {string|HTMLElement} container
	 * @param {string} context
	 * @param {string} errorHtml
	 */
	setErrorHtml: function( container, context, errorHtml ) {
		// Format errors.

		if ( 'checkout' === context && 'undefined' !== typeof cs_global_vars && cs_global_vars.checkout_error_anchor ) {
			// Checkout errors.
			var errorWrapper = document.getElementById( 'cs-paypal-errors-wrap' );
			if ( errorWrapper ) {
				errorWrapper.innerHTML = errorHtml;
			}
		} else if ( 'buy_now' === context ) {
			// Buy Now errors
			var form = container.closest( '.cs_download_purchase_form' );
			var errorWrapper = form ? form.querySelector( '.cs-paypal-checkout-buy-now-error-wrapper' ) : false;

			if ( errorWrapper ) {
				errorWrapper.innerHTML = errorHtml;
			}
		}

		jQuery( document.body ).trigger( 'cs_checkout_error', [ errorHtml ] );
	},

	/**
	 * Initializes PayPal buttons
	 *
	 * @param {string|HTMLElement} container Element to render the buttons in.
	 * @param {string} context   Context for the button. Either `checkout` or `buy_now`.
	 */
	initButtons: function( container, context ) {
		CS_PayPal.isMounted = true;

		paypal.Buttons( CS_PayPal.getButtonArgs( container, context ) ).render( container );

		document.dispatchEvent( new CustomEvent( 'cs_paypal_buttons_mounted' ) );
	},

	/**
	 * Retrieves the arguments used to build the PayPal button.
	 *
	 * @param {string|HTMLElement} container Element to render the buttons in.
	 * @param {string} context   Context for the button. Either `checkout` or `buy_now`.
	 */
	getButtonArgs: function ( container, context ) {
		var form = ( 'checkout' === context ) ? document.getElementById( 'cs_purchase_form' ) : container.closest( '.cs_download_purchase_form' );
		var errorWrapper = ( 'checkout' === context ) ? form.querySelector( '#cs-paypal-errors-wrap' ) : form.querySelector( '.cs-paypal-checkout-buy-now-error-wrapper' );
		var spinner = ( 'checkout' === context ) ? document.getElementById( 'cs-paypal-spinner' ) : form.querySelector( '.cs-paypal-spinner' );
		var nonceEl = form.querySelector( 'input[name="cs_process_paypal_nonce"]' );
		var tokenEl = form.querySelector( 'input[name="cs-process-paypal-token"]' );
		var createFunc = ( 'subscription' === csPayPalVars.intent ) ? 'createSubscription' : 'createOrder';

		var buttonArgs = {
			onApprove: function( data, actions ) {
				var formData = new FormData();
				formData.append( 'action', csPayPalVars.approvalAction );
				formData.append( 'cs_process_paypal_nonce', nonceEl.value );
				formData.append( 'token', tokenEl.getAttribute('data-token') );
				formData.append( 'timestamp', tokenEl.getAttribute('data-timestamp' ) );

				if ( data.orderID ) {
					formData.append( 'paypal_order_id', data.orderID );
				}
				if ( data.subscriptionID ) {
					formData.append( 'paypal_subscription_id', data.subscriptionID );
				}

				return fetch( cs_scripts.ajaxurl, {
					method: 'POST',
					body: formData
				} ).then( function( response ) {
					return response.json();
				} ).then( function( responseData ) {
					if ( responseData.success && responseData.data.redirect_url ) {
						window.location = responseData.data.redirect_url;
					} else {
						// Hide spinner.
						spinner.style.display = 'none';

						var errorHtml = responseData.data.message ? responseData.data.message : csPayPalVars.defaultError;

						CS_PayPal.setErrorHtml( container, context, errorHtml );

						// @link https://developer.paypal.com/docs/checkout/integration-features/funding-failure/
						if ( responseData.data.retry ) {
							return actions.restart();
						}
					}
				} );
			},
			onError: function( error ) {
				// Hide spinner.
				spinner.style.display = 'none';

				error.name = '';
				CS_PayPal.setErrorHtml( container, context, error );
			},
			onCancel: function( data ) {
				// Hide spinner.
				spinner.style.display = 'none';

				const formData = new FormData();
				formData.append( 'action', 'cs_cancel_paypal_order' );
				return fetch( cs_scripts.ajaxurl, {
					method: 'POST',
					body: formData
				} ).then( function ( response ) {
					return response.json();
				} ).then( function ( responseData ) {
					if ( responseData.success ) {
						const nonces = responseData.data.nonces;
						Object.keys( nonces ).forEach( function ( key ) {
							document.getElementById( 'cs-gateway-' + key ).setAttribute( 'data-' + key + '-nonce', nonces[ key ] );
						} );
					}
				} );
			}
		};

		/*
		 * Add style if we have any
		 *
		 * @link https://developer.paypal.com/docs/checkout/integration-features/customize-button/
		 */
		if ( csPayPalVars.style ) {
			buttonArgs.style = csPayPalVars.style;
		}

		/*
		 * Add the `create` logic. This gets added to `createOrder` for one-time purchases
		 * or `createSubscription` for recurring.
		 */
		buttonArgs[ createFunc ] = function ( data, actions ) {
			// Show spinner.
			spinner.style.display = 'block';

			// Clear errors at the start of each attempt.
			if ( errorWrapper ) {
				errorWrapper.innerHTML = '';
			}

			// Submit the form via AJAX.
			return fetch( cs_scripts.ajaxurl, {
				method: 'POST',
				body: new FormData( form )
			} ).then( function( response ) {
				return response.json();
			} ).then( function( orderData ) {
				if ( orderData.data && orderData.data.paypal_order_id ) {

					// Add the nonce to the form so we can validate it later.
					if ( orderData.data.nonce ) {
						nonceEl.value = orderData.data.nonce;
					}

					// Add the token to the form so we can validate it later.
					if ( orderData.data.token ) {
						jQuery(tokenEl).attr( 'data-token', orderData.data.token );
						jQuery(tokenEl).attr( 'data-timestamp', orderData.data.timestamp );
					}

					return orderData.data.paypal_order_id;
				} else {
					// Error message.
					var errorHtml = csPayPalVars.defaultError;
					if ( orderData.data && 'string' === typeof orderData.data ) {
						errorHtml = orderData.data;
					} else if ( 'string' === typeof orderData ) {
						errorHtml = orderData;
					}

					return new Promise( function( resolve, reject ) {
						reject( errorHtml );
					} );
				}
			} );
		};

		return buttonArgs;
	}
};

/**
 * Initialize on checkout.
 */
jQuery( document.body ).on( 'cs_gateway_loaded', function( e, gateway ) {
	if ( 'paypal_commerce' !== gateway ) {
		return;
	}

	CS_PayPal.init();
} );

/**
 * Initialize Buy Now buttons.
 */
jQuery( document ).ready( function( $ ) {
	var buyButtons = document.querySelectorAll( '.cs-paypal-checkout-buy-now' );
	for ( var i = 0; i < buyButtons.length; i++ ) {
		var element = buyButtons[ i ];
		// Skip if "Free Downloads" is enabled for this download.
		if ( element.classList.contains( 'cs-free-download' ) ) {
			continue;
		}

		var wrapper = element.closest( '.cs_purchase_submit_wrapper' );
		if ( ! wrapper ) {
			continue;
		}

		// Clear contents of the wrapper.
		wrapper.innerHTML = '';

		// Add error container after the wrapper.
		var errorNode = document.createElement( 'div' );
		errorNode.classList.add( 'cs-paypal-checkout-buy-now-error-wrapper' );
		wrapper.before( errorNode );

		// Add spinner container.
		var spinnerWrap = document.createElement( 'span' );
		spinnerWrap.classList.add( 'cs-paypal-spinner', 'cs-loading-ajax', 'cs-loading' );
		spinnerWrap.style.display = 'none';
		wrapper.after( spinnerWrap );

		// Initialize button.
		CS_PayPal.initButtons( wrapper, 'buy_now' );
	}
} );
