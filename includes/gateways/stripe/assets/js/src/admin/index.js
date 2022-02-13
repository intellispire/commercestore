/* global $, cs_stripe_admin */

let testModeCheckbox;
let testModeToggleNotice;

$( document ).ready( function() {
	testModeCheckbox = document.getElementById( 'cs_settings[test_mode]' );
	if ( testModeCheckbox ) {
		testModeToggleNotice = document.getElementById( 'cs_settings[stripe_connect_test_mode_toggle_notice]' );
		CS_Stripe_Connect_Scripts.init();
	}

	// Show the hidden API key fields
	$( '#csx-api-keys-row-reveal a' ).on( 'click', function( event ) {
		event.preventDefault();
		$( '.csx-api-key-row' ).removeClass( 'cs-hidden' );
		$( this ).parent().addClass( 'cs-hidden' );
		$( '#csx-api-keys-row-hide' ).removeClass( 'cs-hidden' );
	} );

	// Hide API key fields
	$( '#csx-api-keys-row-hide a' ).on( 'click', function( event ) {
		event.preventDefault();
		$( '.csx-api-key-row' ).addClass( 'cs-hidden' );
		$( this ).parent().addClass( 'cs-hidden' );
		$( '#csx-api-keys-row-reveal' ).removeClass( 'cs-hidden' );
	} );
} );

const CS_Stripe_Connect_Scripts = {

	init() {
		this.listeners();
	},

	listeners() {
		const self = this;

		testModeCheckbox.addEventListener( 'change', function() {
			// Don't run these events if Stripe is not enabled.
			if ( ! cs_stripe_admin.stripe_enabled ) {
				return;
			}

			if ( this.checked ) {
				if ( 'false' === cs_stripe_admin.test_key_exists ) {
					self.showNotice( testModeToggleNotice, 'error' );
					self.addHiddenMarker();
				} else {
					self.hideNotice( testModeToggleNotice );
					const hiddenMarker = document.getElementById( 'cs-test-mode-toggled' );
					if ( hiddenMarker ) {
						hiddenMarker.parentNode.removeChild( hiddenMarker );
					}
				}
			}

			if ( ! this.checked ) {
				if ( 'false' === cs_stripe_admin.live_key_exists ) {
					self.showNotice( testModeToggleNotice, 'error' );
					self.addHiddenMarker();
				} else {
					self.hideNotice( testModeToggleNotice );
					const hiddenMarker = document.getElementById( 'cs-test-mode-toggled' );
					if ( hiddenMarker ) {
						hiddenMarker.parentNode.removeChild( hiddenMarker );
					}
				}
			}
		} );
	},

	addHiddenMarker() {
		const submit = document.getElementById( 'submit' );

		if ( ! submit ) {
			return;
		}

		submit.parentNode.insertAdjacentHTML( 'beforeend', '<input type="hidden" class="cs-hidden" id="cs-test-mode-toggled" name="cs-test-mode-toggled" />' );
	},

	showNotice( element = false, type = 'error' ) {
		if ( ! element ) {
			return;
		}

		if ( typeof element !== 'object' ) {
			return;
		}

		element.className = 'notice notice-' + type;
	},

	hideNotice( element = false ) {
		if ( ! element ) {
			return;
		}

		if ( typeof element !== 'object' ) {
			return;
		}

		element.className = 'cs-hidden';
	},
};
