/* global CSExtensionManager, ajaxurl */

; ( function ( document, $ ) {
	'use strict';

	$( '.cs-extension-manager__action' ).on( 'click', function ( e ) {
		e.preventDefault();

		var $btn = $( this ),
			action = $btn.attr( 'data-action' ),
			plugin = $btn.attr( 'data-plugin' ),
			type = $btn.attr( 'data-type' ),
			ajaxAction = '';

		if ( $btn.attr( 'disabled' ) ) {
			return;
		}

		switch ( action ) {
			case 'activate':
				ajaxAction = 'cs_activate_extension';
				$btn.text( CSExtensionManager.activating );
				break;

			case 'install':
				ajaxAction = 'cs_install_extension';
				$btn.text( CSExtensionManager.installing );
				break;

			default:
				return;
		}

		$btn.removeClass( 'button-primary' ).attr( 'disabled', true ).addClass( 'updating-message' );

		var data = {
			action: ajaxAction,
			nonce: CSExtensionManager.extension_manager_nonce,
			plugin: plugin,
			type: type,
			pass: $btn.attr( 'data-pass' ),
			id: $btn.attr( 'data-id' ),
			product: $btn.attr( 'data-product' ),
		};

		$.post( ajaxurl, data )
			.done( function ( res ) {
				console.log( res );
				var thisStep = $btn.closest( '.cs-extension-manager__step' );
				if ( res.success ) {
					var nextStep = thisStep.next();
					if ( nextStep.length ) {
						thisStep.fadeOut();
						nextStep.prepend( '<div class="notice inline-notice notice-success"><p>' + res.data.message + '</p></div>' );
						nextStep.fadeIn();
					}
				} else {
					thisStep.fadeOut();
					var message = res.data.message;
					/**
					 * The install class returns an array of error messages, and res.data.message will be undefined.
					 * In that case, we'll use the standard failure messages.
					 */
					if ( ! message ) {
						if ( 'plugin' !== type ) {
							message = CSExtensionManager.extension_install_failed;
						} else {
							message = CSExtensionManager.plugin_install_failed;
						}
					}
					thisStep.after( '<div class="notice inline-notice notice-warning"><p>' + message + '</p></div>' );
				}
			} );
	} );
} )( document, jQuery );
