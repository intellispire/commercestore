/**
 * Customer management screen JS
 */
var CS_Customer = {

	vars: {
		customer_card_wrap_editable: $( '#edit-customer-info .editable' ),
		customer_card_wrap_edit_item: $( '#edit-customer-info .edit-item' ),
		user_id: $( 'input[name="customerinfo[user_id]"]' ),
	},
	init: function() {
		this.edit_customer();
		this.add_email();
		this.user_search();
		this.remove_user();
		this.cancel_edit();
		this.change_country();
		this.delete_checked();
	},
	edit_customer: function() {
		$( document.body ).on( 'click', '#edit-customer', function( e ) {
			e.preventDefault();

			CS_Customer.vars.customer_card_wrap_editable.hide();
			CS_Customer.vars.customer_card_wrap_edit_item.show().css( 'display', 'block' );
		} );
	},
	add_email: function() {
		$( document.body ).on( 'click', '#add-customer-email', function( e ) {
			e.preventDefault();
			const button = $( this ),
				wrapper = button.parent().parent().parent().parent(),
				customer_id = wrapper.find( 'input[name="customer-id"]' ).val(),
				email = wrapper.find( 'input[name="additional-email"]' ).val(),
				primary = wrapper.find( 'input[name="make-additional-primary"]' ).is( ':checked' ),
				nonce = wrapper.find( 'input[name="add_email_nonce"]' ).val(),
				postData = {
					cs_action: 'customer-add-email',
					customer_id: customer_id,
					email: email,
					primary: primary,
					_wpnonce: nonce,
				};

			wrapper.parent().find( '.notice-container' ).remove();
			wrapper.find( '.spinner' ).css( 'visibility', 'visible' );
			button.attr( 'disabled', true );

			$.post( ajaxurl, postData, function( response ) {
				setTimeout( function() {
					if ( true === response.success ) {
						window.location.href = response.redirect;
					} else {
						button.attr( 'disabled', false );
						wrapper.before( '<div class="notice-container"><div class="notice notice-error inline"><p>' + response.message + '</p></div></div>' );
						wrapper.find( '.spinner' ).css( 'visibility', 'hidden' );
					}
				}, 342 );
			}, 'json' );
		} );
	},
	user_search: function() {
		// Upon selecting a user from the dropdown, we need to update the User ID
		$( document.body ).on( 'click.eddSelectUser', '.cs_user_search_results a', function( e ) {
			e.preventDefault();
			const user_id = $( this ).data( 'userid' );
			CS_Customer.vars.user_id.val( user_id );
		} );
	},
	remove_user: function() {
		$( document.body ).on( 'click', '#disconnect-customer', function( e ) {
			e.preventDefault();

			if ( confirm( cs_vars.disconnect_customer ) ) {
				const customer_id = $( 'input[name="customerinfo[id]"]' ).val(),
					postData = {
						cs_action: 'disconnect-userid',
						customer_id: customer_id,
						_wpnonce: $( '#edit-customer-info #_wpnonce' ).val(),
					};

				$.post( ajaxurl, postData, function( response ) {
					// Weird
					window.location.href = window.location.href;
				}, 'json' );
			}
		} );
	},
	cancel_edit: function() {
		$( document.body ).on( 'click', '#cs-edit-customer-cancel', function( e ) {
			e.preventDefault();
			CS_Customer.vars.customer_card_wrap_edit_item.hide();
			CS_Customer.vars.customer_card_wrap_editable.show();

			$( '.cs_user_search_results' ).html( '' );
		} );
	},
	change_country: function() {
		$( 'select[name="customerinfo[country]"]' ).change( function() {
			const select = $( this ),
				state_input = $( ':input[name="customerinfo[region]"]' ),
				data = {
					action: 'cs_get_shop_states',
					country: select.val(),
					nonce: select.data( 'nonce' ),
					field_name: 'customerinfo[region]',
				};

			$.post( ajaxurl, data, function( response ) {
				console.log( response );
				if ( 'nostates' === response ) {
					state_input.replaceWith( '<input type="text" name="' + data.field_name + '" value="" class="cs-edit-toggles medium-text"/>' );
				} else {
					state_input.replaceWith( response );
				}
			} );

			return false;
		} );
	},
	delete_checked: function() {
		$( '#cs-customer-delete-confirm' ).change( function() {
			const records_input = $( '#cs-customer-delete-records' );
			const submit_button = $( '#cs-delete-customer' );

			if ( $( this ).prop( 'checked' ) ) {
				records_input.attr( 'disabled', false );
				submit_button.attr( 'disabled', false );
			} else {
				records_input.attr( 'disabled', true );
				records_input.prop( 'checked', false );
				submit_button.attr( 'disabled', true );
			}
		} );
	},
};

jQuery( document ).ready( function( $ ) {
	CS_Customer.init();
} );
