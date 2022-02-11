/**
 * Internal dependencies.
 */
import './components/agree-to-terms';
import { getCreditCardIcon, recalculateTaxes } from './utils.js';

// Backwards compatibility. Assign function to global namespace.
window.recalculate_taxes = recalculateTaxes;

window.CS_Checkout = ( function( $ ) {
	'use strict';

	let $body,
		$form,
		$cs_cart_amount,
		before_discount,
		$checkout_form_wrap;

	function init() {
		$body = $( document.body );
		$form = $( '#cs_purchase_form' );
		$cs_cart_amount = $( '.cs_cart_amount' );
		before_discount = $cs_cart_amount.text();
		$checkout_form_wrap = $( '#cs_checkout_form_wrap' );

		$body.on( 'cs_gateway_loaded', function( e ) {
			cs_format_card_number( $form );
		} );

		$body.on( 'keyup change', '.cs-do-validate .card-number', function() {
			cs_validate_card( $( this ) );
		} );

		$body.on( 'blur change', '.card-name', function() {
			const name_field = $( this );

			name_field.validateCreditCard( function( result ) {
				if ( result.card_type != null ) {
					name_field.removeClass( 'valid' ).addClass( 'error' );
					$( '#cs-purchase-button' ).attr( 'disabled', 'disabled' );
				} else {
					name_field.removeClass( 'error' ).addClass( 'valid' );
					$( '#cs-purchase-button' ).removeAttr( 'disabled' );
				}
			} );
		} );

		// Make sure a gateway is selected
		$body.on( 'submit', '#cs_payment_mode', function() {
			const gateway = $( '#cs-gateway option:selected' ).val();
			if ( gateway == 0 ) {
				alert( cs_global_vars.no_gateway );
				return false;
			}
		} );

		// Add a class to the currently selected gateway on click
		$body.on( 'click', '#cs_payment_mode_select input', function() {
			$( '#cs_payment_mode_select label.cs-gateway-option-selected' ).removeClass( 'cs-gateway-option-selected' );
			$( '#cs_payment_mode_select input:checked' ).parent().addClass( 'cs-gateway-option-selected' );
		} );

		// Validate and apply a discount
		$checkout_form_wrap.on( 'click', '.cs-apply-discount', apply_discount );

		// Prevent the checkout form from submitting when hitting Enter in the discount field
		$checkout_form_wrap.on( 'keypress', '#cs-discount', function( event ) {
			if ( event.keyCode == '13' ) {
				return false;
			}
		} );

		// Apply the discount when hitting Enter in the discount field instead
		$checkout_form_wrap.on( 'keyup', '#cs-discount', function( event ) {
			if ( event.keyCode == '13' ) {
				$checkout_form_wrap.find( '.cs-apply-discount' ).trigger( 'click' );
			}
		} );

		// Remove a discount
		$body.on( 'click', '.cs_discount_remove', remove_discount );

		// When discount link is clicked, hide the link, then show the discount input and set focus.
		$body.on( 'click', '.cs_discount_link', function( e ) {
			e.preventDefault();
			$( '.cs_discount_link' ).parent().hide();
			$( '#cs-discount-code-wrap' ).show().find( '#cs-discount' ).focus();
		} );

		// Hide / show discount fields for browsers without javascript enabled
		$body.find( '#cs-discount-code-wrap' ).hide();
		$body.find( '#cs_show_discount' ).show();

		// Update the checkout when item quantities are updated
		$body.on( 'change', '.cs-item-quantity', update_item_quantities );

		$body.on( 'click', '.cs-amazon-logout #Logout', function( e ) {
			e.preventDefault();
			amazon.Login.logout();
			window.location = cs_amazon.checkoutUri;
		} );
	}

	function cs_validate_card( field ) {
		const card_field = field;
		card_field.validateCreditCard( function( result ) {
			const $card_type = $( '.card-type' );

			if ( result.card_type == null ) {
				$card_type.removeClass().addClass( 'off card-type' );
				card_field.removeClass( 'valid' );
				card_field.addClass( 'error' );
			} else {
				$card_type.removeClass( 'off' );
				$card_type.html( getCreditCardIcon( result.card_type.name ) );
				$card_type.addClass( result.card_type.name );
				if ( result.length_valid && result.luhn_valid ) {
					card_field.addClass( 'valid' );
					card_field.removeClass( 'error' );
				} else {
					card_field.removeClass( 'valid' );
					card_field.addClass( 'error' );
				}
			}
		} );
	}

	function cs_format_card_number( form ) {
		const card_number = form.find( '.card-number' ),
			card_cvc = form.find( '.card-cvc' ),
			card_expiry = form.find( '.card-expiry' );

		if ( card_number.length && 'function' === typeof card_number.payment ) {
			card_number.payment( 'formatCardNumber' );
			card_cvc.payment( 'formatCardCVC' );
			card_expiry.payment( 'formatCardExpiry' );
		}
	}

	function apply_discount( event ) {
		event.preventDefault();

		const discount_code = $( '#cs-discount' ).val(),
			cs_discount_loader = $( '#cs-discount-loader' ),
			required_inputs = $( '#cs_cc_address .cs-input, #cs_cc_address .cs-select' ).filter( '[required]' );

		if ( discount_code == '' || discount_code == cs_global_vars.enter_discount ) {
			return false;
		}

		const postData = {
			action: 'cs_apply_discount',
			code: discount_code,
			form: $( '#cs_purchase_form' ).serialize(),
		};

		$( '#cs-discount-error-wrap' ).html( '' ).hide();
		cs_discount_loader.show();

		$.ajax( {
			type: 'POST',
			data: postData,
			dataType: 'json',
			url: cs_global_vars.ajaxurl,
			xhrFields: {
				withCredentials: true,
			},
			success: function( discount_response ) {
				if ( discount_response ) {
					if ( discount_response.msg == 'valid' ) {
						$( '.cs_cart_discount' ).html( discount_response.html );
						$( '.cs_cart_discount_row' ).show();

						$( '.cs_cart_amount' ).each( function() {
							// Format discounted amount for display.
							$( this ).text( discount_response.total );
							// Set data attribute to new (unformatted) discounted amount.'
							$( this ).data( 'total', discount_response.total_plain );
						} );

						$( '#cs-discount', $checkout_form_wrap ).val( '' );

						recalculateTaxes();

						if ( '0.00' == discount_response.total_plain ) {
							$( '#cs_cc_fields,#cs_cc_address,#cs_payment_mode_select' ).slideUp();
							required_inputs.prop( 'required', false );
							$( 'input[name="cs-gateway"]' ).val( 'manual' );
						} else {
							required_inputs.prop( 'required', true );
							$( '#cs_cc_fields,#cs_cc_address' ).slideDown();
						}

						$body.trigger( 'cs_discount_applied', [ discount_response ] );
					} else {
						$( '#cs-discount-error-wrap' ).html( '<span class="cs_error">' + discount_response.msg + '</span>' );
						$( '#cs-discount-error-wrap' ).show();
						$body.trigger( 'cs_discount_invalid', [ discount_response ] );
					}
				} else {
					if ( window.console && window.console.log ) {
						console.log( discount_response );
					}
					$body.trigger( 'cs_discount_failed', [ discount_response ] );
				}
				cs_discount_loader.hide();
			},
		} ).fail( function( data ) {
			if ( window.console && window.console.log ) {
				console.log( data );
			}
		} );

		return false;
	}

	function remove_discount( event ) {
		const $this = $( this ),
			postData = {
				action: 'cs_remove_discount',
				code: $this.data( 'code' ),
			};

		$.ajax( {
			type: 'POST',
			data: postData,
			dataType: 'json',
			url: cs_global_vars.ajaxurl,
			xhrFields: {
				withCredentials: true,
			},
			success: function( discount_response ) {
				const zero = '0' + cs_global_vars.decimal_separator + '00';

				$( '.cs_cart_amount' ).each( function() {
					if ( cs_global_vars.currency_sign + zero == $( this ).text() || zero + cs_global_vars.currency_sign == $( this ).text() ) {
						// We're removing a 100% discount code so we need to force the payment gateway to reload
						window.location.reload();
					}

					// Format discounted amount for display.
					$( this ).text( discount_response.total );
					// Set data attribute to new (unformatted) discounted amount.'
					$( this ).data( 'total', discount_response.total_plain );
				} );

				$( '.cs_cart_discount' ).html( discount_response.html );

				if ( discount_response.discounts && 0 === discount_response.discounts.length ) {
					$( '.cs_cart_discount_row' ).hide();
				}

				recalculateTaxes();

				$( '#cs_cc_fields,#cs_cc_address' ).slideDown();

				$body.trigger( 'cs_discount_removed', [ discount_response ] );
			},
		} ).fail( function( data ) {
			if ( window.console && window.console.log ) {
				console.log( data );
			}
		} );

		return false;
	}

	function update_item_quantities( event ) {
		const $this = $( this ),
			quantity = $this.val(),
			key = $this.data( 'key' ),
			download_id = $this.closest( '.cs_cart_item' ).data( 'download-id' ),
			options = $this.parent().find( 'input[name="cs-cart-download-' + key + '-options"]' ).val();

		const cs_cc_address = $( '#cs_cc_address' );
		const billing_country = cs_cc_address.find( '#billing_country' ).val(),
			card_state = cs_cc_address.find( '#card_state' ).val();

		const postData = {
			action: 'cs_update_quantity',
			quantity: quantity,
			download_id: download_id,
			options: options,
			billing_country: billing_country,
			card_state: card_state,
		};

		//cs_discount_loader.show();

		$.ajax( {
			type: 'POST',
			data: postData,
			dataType: 'json',
			url: cs_global_vars.ajaxurl,
			xhrFields: {
				withCredentials: true,
			},
			success: function( response ) {
				$( '.cs_cart_subtotal_amount' ).each( function() {
					$( this ).text( response.subtotal );
				} );

				$( '.cs_cart_tax_amount' ).each( function() {
					$( this ).text( response.taxes );
				} );

				$( '.cs_cart_amount' ).each( function() {
					$( this ).text( response.total );
					$body.trigger( 'cs_quantity_updated', [ response ] );
				} );
			},
		} ).fail( function( data ) {
			if ( window.console && window.console.log ) {
				console.log( data );
			}
		} );

		return false;
	}

	// Expose some functions or variables to window.CS_Checkout object
	return {
		init: init,
		recalculate_taxes: recalculateTaxes,
	};
}( window.jQuery ) );

// init on document.ready
window.jQuery( document ).ready( CS_Checkout.init );
