jQuery( document ).ready( function ( $ ) {
	/**
	 * Connect to PayPal
	 */
	$( '#cs-paypal-commerce-connect' ).on( 'click', function ( e ) {
		e.preventDefault();

		// Clear errors.
		var errorContainer = $( '#cs-paypal-commerce-errors' );
		errorContainer.empty().removeClass( 'notice notice-error' );

		var button = document.getElementById( 'cs-paypal-commerce-connect' );
		button.classList.add( 'updating-message' );
		button.disabled = true;

		$.post( ajaxurl, {
			action: 'cs_paypal_commerce_connect',
			_ajax_nonce: $( this ).data( 'nonce' )
		}, function( response ) {
			if ( ! response.success ) {
				console.log( 'Connection failure', response.data );
				button.classList.remove( 'updating-message' );
				button.disabled = false;

				// Set errors.
				errorContainer.html( '<p>' + response.data + '</p>' ).addClass( 'notice notice-error' );
				return;
			}

			var paypalLinkEl = document.getElementById( 'cs-paypal-commerce-link' );
			paypalLinkEl.href = response.data.signupLink + '&displayMode=minibrowser';

			paypalLinkEl.click();
		} );
	} );

	/**
	 * Checks the PayPal connection & webhook status.
	 */
	function csPayPalGetAccountStatus() {
		var accountInfoEl = document.getElementById( 'cs-paypal-commerce-connect-wrap' );
		if ( accountInfoEl ) {
			$.post( ajaxurl, {
				action: 'cs_paypal_commerce_get_account_info',
				_ajax_nonce: accountInfoEl.getAttribute( 'data-nonce' )
			}, function( response ) {
				var newHtml = '<p>' + csPayPalConnectVars.defaultError + '</p>';

				if ( response.success ) {
					newHtml = response.data.account_status;

					if ( response.data.actions && response.data.actions.length ) {
						newHtml += '<p class="cs-paypal-connect-actions">' + response.data.actions.join( ' ' ) + '</p>';
					}
				} else if ( response.data && response.data.message ) {
					newHtml = response.data.message;
				}

				accountInfoEl.innerHTML = newHtml;

				// Remove old status messages.
				accountInfoEl.classList.remove( 'notice-success', 'notice-warning', 'notice-error' );

				// Add new one.
				var newClass = response.success && response.data.status ? 'notice-' + response.data.status : 'notice-error';
				accountInfoEl.classList.add( newClass );
			} );
		}
	}
	csPayPalGetAccountStatus();

	/**
	 * Create webhook
	 */
	$( document ).on( 'click', '.cs-paypal-connect-action', function ( e ) {
		e.preventDefault();

		var button = $( this );
		button.prop( 'disabled', true );
		button.addClass( 'updating-message' );

		var errorWrap = $( '#cs-paypal-commerce-connect-wrap' ).find( '.cs-paypal-actions-error-wrap' );
		if ( errorWrap.length ) {
			errorWrap.remove();
		}

		$.post( ajaxurl, {
			action: button.data( 'action' ),
			_ajax_nonce: button.data( 'nonce' )
		}, function( response ) {
			button.prop( 'disabled', false );
			button.removeClass( 'updating-message' );

			if ( response.success ) {
				button.addClass( 'updated-message' );

				// Refresh account status.
				csPayPalGetAccountStatus();
			} else {
				button.parent().after( '<p class="cs-paypal-actions-error-wrap">' + response.data + '</p>' );
			}
		} );
	} );
} );

window.csPayPalOnboardingCallback = function csPayPalOnboardingCallback( authCode, shareId ) {
	var connectButton = document.getElementById( 'cs-paypal-commerce-connect' );
	var errorContainer = document.getElementById( 'cs-paypal-commerce-errors' );

	jQuery.post( ajaxurl, {
		action: 'cs_paypal_commerce_get_access_token',
		auth_code: authCode,
		share_id: shareId,
		_ajax_nonce: connectButton.getAttribute( 'data-nonce' )
	}, function( response ) {
		connectButton.classList.remove( 'updating-message' );

		if ( ! response.success ) {
			connectButton.disabled = false;

			errorContainer.innerHTML = '<p>' + response.data + '</p>';
			errorContainer.classList.add( 'notice notice-error' );
			return;
		}

		connectButton.classList.add( 'updated-message' );

		window.location.reload();
	} );
}
