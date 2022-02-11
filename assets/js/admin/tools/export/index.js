/**
 * Export screen JS
 */
const CS_Export = {

	init: function() {
		this.submit();
	},

	submit: function() {
		const self = this;

		$( document.body ).on( 'submit', '.cs-export-form', function( e ) {
			e.preventDefault();

			const form = $( this ),
				submitButton = form.find( 'button[type="submit"]' ).first();

			if ( submitButton.hasClass( 'button-disabled' ) || submitButton.is( ':disabled' ) ) {
				return;
			}

			const data = form.serialize();

			if ( submitButton.hasClass( 'button-primary' ) ) {
				submitButton.removeClass( 'button-primary' ).addClass( 'button-secondary' );
			}
			submitButton.attr( 'disabled', true ).addClass( 'updating-message' );
			form.find( '.notice-wrap' ).remove();
			form.append( '<div class="notice-wrap"><div class="cs-progress"><div></div></div></div>' );

			// start the process
			self.process_step( 1, data, self );
		} );
	},

	process_step: function( step, data, self ) {
		$.ajax( {
			type: 'POST',
			url: ajaxurl,
			data: {
				form: data,
				action: 'cs_do_ajax_export',
				step: step,
			},
			dataType: 'json',
			success: function( response ) {
				if ( 'done' === response.step || response.error || response.success ) {
					// We need to get the actual in progress form, not all forms on the page
					const export_form = $( '.cs-export-form' ).find( '.cs-progress' ).parent().parent();
					const notice_wrap = export_form.find( '.notice-wrap' );

					export_form.find( 'button' ).attr( 'disabled', false ).removeClass( 'updating-message' ).addClass( 'updated-message' );
					export_form.find( 'button .spinner' ).hide().css( 'visibility', 'visible' );

					if ( response.error ) {
						const error_message = response.message;
						notice_wrap.html( '<div class="updated error"><p>' + error_message + '</p></div>' );
					} else if ( response.success ) {
						const success_message = response.message;
						notice_wrap.html( '<div id="cs-batch-success" class="updated notice"><p>' + success_message + '</p></div>' );
						if ( response.data ) {
							$.each( response.data, function ( key, value ) {
								$( '.cs_' + key ).html( value );
							} );
						}
					} else {
						notice_wrap.remove();
						window.location = response.url;
					}
				} else {
					$( '.cs-progress div' ).animate( {
						width: response.percentage + '%',
					}, 50, function() {
						// Animation complete.
					} );
					self.process_step( parseInt( response.step ), data, self );
				}
			},
		} ).fail( function( response ) {
			if ( window.console && window.console.log ) {
				console.log( response );
			}
		} );
	},
};

jQuery( document ).ready( function( $ ) {
	CS_Export.init();
} );
