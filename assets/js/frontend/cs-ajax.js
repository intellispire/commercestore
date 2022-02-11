/* global cs_scripts, cs_global_vars */

/**
 * Internal dependencies.
 */
import { recalculateTaxes } from './checkout/utils.js';

jQuery( document ).ready( function( $ ) {
	// Hide unneeded elements. These are things that are required in case JS breaks or isn't present
	$( '.cs-no-js' ).hide();
	$( 'a.cs-add-to-cart' ).addClass( 'cs-has-js' );

	// Send Remove from Cart requests
	$( document.body ).on( 'click.eddRemoveFromCart', '.cs-remove-from-cart', function( event ) {
		const $this = $( this ),
			item = $this.data( 'cart-item' ),
			action = $this.data( 'action' ),
			id = $this.data( 'download-id' ),
			nonce = $this.data( 'nonce' ),
			data = {
				action: action,
				cart_item: item,
				nonce: nonce,
				timestamp: $this.data( 'timestamp' ),
				token: $this.data( 'token' )
			};

		 $.ajax( {
			type: 'POST',
			data: data,
			dataType: 'json',
			url: cs_scripts.ajaxurl,
			xhrFields: {
				withCredentials: true,
			},
			success: function( response ) {
				if ( response.removed ) {
					if ( ( parseInt( cs_scripts.position_in_cart, 10 ) === parseInt( item, 10 ) ) || cs_scripts.has_purchase_links ) {
						window.location = window.location;
						return false;
					}

					// Remove the selected cart item
					$( '.cs-cart' ).each( function() {
						$( this ).find( "[data-cart-item='" + item + "']" ).parent().remove();
					} );

					//Reset the data-cart-item attributes to match their new values in the CommerceStore session cart array
					$( '.cs-cart' ).each( function() {
						let cart_item_counter = 0;
						$( this ).find( '[data-cart-item]' ).each( function() {
							$( this ).attr( 'data-cart-item', cart_item_counter );
							cart_item_counter = cart_item_counter + 1;
						} );
					} );

					// Check to see if the purchase form(s) for this download is present on this page
					if ( $( '[id^=cs_purchase_' + id + ']' ).length ) {
						$( '[id^=cs_purchase_' + id + '] .cs_go_to_checkout' ).hide();
						$( '[id^=cs_purchase_' + id + '] a.cs-add-to-cart' ).show().removeAttr( 'data-cs-loading' );
						if ( cs_scripts.quantities_enabled === '1' ) {
							$( '[id^=cs_purchase_' + id + '] .cs_download_quantity_wrapper' ).show();
						}
					}

					$( 'span.cs-cart-quantity' ).text( response.cart_quantity );
					$( document.body ).trigger( 'cs_quantity_updated', [ response.cart_quantity ] );
					if ( cs_scripts.taxes_enabled ) {
						$( '.cart_item.cs_subtotal span' ).html( response.subtotal );
						$( '.cart_item.cs_cart_tax span' ).html( response.tax );
					}

					$( '.cart_item.cs_total span' ).html( response.total );

					if ( response.cart_quantity === 0 ) {
						$( '.cart_item.cs_subtotal,.cs-cart-number-of-items,.cart_item.cs_checkout,.cart_item.cs_cart_tax,.cart_item.cs_total' ).hide();
						$( '.cs-cart' ).each( function() {
							const cart_wrapper = $( this ).parent();
							if ( cart_wrapper.length ) {
								cart_wrapper.addClass( 'cart-empty' );
								cart_wrapper.removeClass( 'cart-not-empty' );
							}

							$( this ).append( '<li class="cart_item empty">' + cs_scripts.empty_cart_message + '</li>' );
						} );
					}

					$( document.body ).trigger( 'cs_cart_item_removed', [ response ] );
				}
			},
		} ).fail( function( response ) {
			if ( window.console && window.console.log ) {
				console.log( response );
			}
		} ).done( function( response ) {

		} );

		return false;
	} );

	// Send Add to Cart request
	$( document.body ).on( 'click.eddAddToCart', '.cs-add-to-cart', function( e ) {
		e.preventDefault();

		var $this = $( this ),
			form = $this.closest( 'form' );

		// Disable button, preventing rapid additions to cart during ajax request
		$this.prop( 'disabled', true );

		const $spinner = $this.find( '.cs-loading' );
		const container = $this.closest( 'div' );

		// Show the spinner
		$this.attr( 'data-cs-loading', '' );

		var form = $this.parents( 'form' ).last();
		const download = $this.data( 'download-id' );
		const variable_price = $this.data( 'variable-price' );
		const price_mode = $this.data( 'price-mode' );
		const nonce = $this.data( 'nonce' );
		const item_price_ids = [];
		let free_items = true;

		if ( variable_price === 'yes' ) {
			if ( form.find( '.cs_price_option_' + download + '[type="hidden"]' ).length > 0 ) {
				item_price_ids[ 0 ] = $( '.cs_price_option_' + download, form ).val();
				if ( form.find( '.cs-submit' ).data( 'price' ) && form.find( '.cs-submit' ).data( 'price' ) > 0 ) {
					free_items = false;
				}
			} else {
				if ( ! form.find( '.cs_price_option_' + download + ':checked', form ).length ) {
					 // hide the spinner
					$this.removeAttr( 'data-cs-loading' );
					alert( cs_scripts.select_option );
					e.stopPropagation();
					$this.prop( 'disabled', false );
					return false;
				}

				form.find( '.cs_price_option_' + download + ':checked', form ).each( function( index ) {
					item_price_ids[ index ] = $( this ).val();

					// If we're still only at free items, check if this one is free also
					if ( true === free_items ) {
						const item_price = $( this ).data( 'price' );
						if ( item_price && item_price > 0 ) {
							// We now have a paid item, we can't use add_to_cart
							free_items = false;
						}
					}
				} );
			}
		} else {
			item_price_ids[ 0 ] = download;
			if ( $this.data( 'price' ) && $this.data( 'price' ) > 0 ) {
				free_items = false;
			}
		}

		// If we've got nothing but free items being added, change to add_to_cart
		if ( free_items ) {
			form.find( '.cs_action_input' ).val( 'add_to_cart' );
		}

		if ( 'straight_to_gateway' === form.find( '.cs_action_input' ).val() ) {
			form.submit();
			return true; // Submit the form
		}

		const action = $this.data( 'action' );
		const data = {
			action: action,
			download_id: download,
			price_ids: item_price_ids,
			post_data: $( form ).serialize(),
			nonce: nonce,
		};

		$.ajax( {
			type: 'POST',
			data: data,
			dataType: 'json',
			url: cs_scripts.ajaxurl,
			xhrFields: {
				withCredentials: true,
			},
			success: function( response ) {
				const store_redirect = cs_scripts.redirect_to_checkout === '1';
				const item_redirect = form.find( '#cs_redirect_to_checkout' ).val() === '1';

				if ( ( store_redirect && item_redirect ) || ( ! store_redirect && item_redirect ) ) {
					window.location = cs_scripts.checkout_page;
				} else {
					// Add the new item to the cart widget
					if ( cs_scripts.taxes_enabled === '1' ) {
						$( '.cart_item.cs_subtotal' ).show();
						$( '.cart_item.cs_cart_tax' ).show();
					}

					$( '.cart_item.cs_total' ).show();
					$( '.cart_item.cs_checkout' ).show();

					if ( $( '.cart_item.empty' ).length ) {
						$( '.cart_item.empty' ).hide();
					}

					$( '.widget_cs_cart_widget .cs-cart' ).each( function( cart ) {
						const target = $( this ).find( '.cs-cart-meta:first' );
						$( response.cart_item ).insertBefore( target );

						const cart_wrapper = $( this ).parent();
						if ( cart_wrapper.length ) {
							cart_wrapper.addClass( 'cart-not-empty' );
							cart_wrapper.removeClass( 'cart-empty' );
						}
					} );

					// Update the totals
					if ( cs_scripts.taxes_enabled === '1' ) {
						$( '.cs-cart-meta.cs_subtotal span' ).html( response.subtotal );
						$( '.cs-cart-meta.cs_cart_tax span' ).html( response.tax );
					}

					$( '.cs-cart-meta.cs_total span' ).html( response.total );

					// Update the cart quantity
					const items_added = $( '.cs-cart-item-title', response.cart_item ).length;

					$( 'span.cs-cart-quantity' ).each( function() {
						$( this ).text( response.cart_quantity );
						$( document.body ).trigger( 'cs_quantity_updated', [ response.cart_quantity ] );
					} );

					// Show the "number of items in cart" message
					if ( $( '.cs-cart-number-of-items' ).css( 'display' ) === 'none' ) {
						$( '.cs-cart-number-of-items' ).show( 'slow' );
					}

					if ( variable_price === 'no' || price_mode !== 'multi' ) {
						// Switch purchase to checkout if a single price item or variable priced with radio buttons
						$( 'a.cs-add-to-cart', container ).toggle();
						$( '.cs_go_to_checkout', container ).css( 'display', 'inline-block' );
					}

					if ( price_mode === 'multi' ) {
						// remove spinner for multi
						$this.removeAttr( 'data-cs-loading' );
					}

					// Update all buttons for same download
					if ( $( '.cs_download_purchase_form' ).length && ( variable_price === 'no' || ! form.find( '.cs_price_option_' + download ).is( 'input:hidden' ) ) ) {
						const parent_form = $( '.cs_download_purchase_form *[data-download-id="' + download + '"]' ).parents( 'form' );
						$( 'a.cs-add-to-cart', parent_form ).hide();
						if ( price_mode !== 'multi' ) {
							parent_form.find( '.cs_download_quantity_wrapper' ).slideUp();
						}
						$( '.cs_go_to_checkout', parent_form ).show().removeAttr( 'data-cs-loading' );
					}

					if ( response !== 'incart' ) {
						// Show the added message
						$( '.cs-cart-added-alert', container ).fadeIn();
						setTimeout( function() {
							$( '.cs-cart-added-alert', container ).fadeOut();
						}, 3000 );
					}

					// Re-enable the add to cart button
					$this.prop( 'disabled', false );

					$( document.body ).trigger( 'cs_cart_item_added', [ response ] );
				}
			},
		} ).fail( function( response ) {
			if ( window.console && window.console.log ) {
				console.log( response );
			}
		} ).done( function( response ) {

		} );

		return false;
	} );

	// Show the login form on the checkout page
	$( '#cs_checkout_form_wrap' ).on( 'click', '.cs_checkout_register_login', function() {
		const $this = $( this ),
			data = {
				action: $this.data( 'action' ),
				nonce: $this.data( 'nonce' ),
			};

		// Show the ajax loader
		$( '.cs-cart-ajax' ).show();

		$.post( cs_scripts.ajaxurl, data, function( checkout_response ) {
			$( '#cs_checkout_login_register' ).html( cs_scripts.loading );
			$( '#cs_checkout_login_register' ).html( checkout_response );
			// Hide the ajax loader
			$( '.cs-cart-ajax' ).hide();
		} );
		return false;
	} );

	// Process the login form via ajax
	$( document ).on( 'click', '#cs_purchase_form #cs_login_fields input[type=submit]', function( e ) {
		e.preventDefault();

		const complete_purchase_val = $( this ).val();

		$( this ).attr( 'data-original-value', complete_purchase_val );

		$( this ).val( cs_global_vars.purchase_loading );

		$( this ).after( '<span class="cs-loading-ajax cs-loading"></span>' );

		const data = {
			action: 'cs_process_checkout_login',
			cs_ajax: 1,
			cs_user_login: $( '#cs_login_fields #cs_user_login' ).val(),
			cs_user_pass: $( '#cs_login_fields #cs_user_pass' ).val(),
			cs_login_nonce: $( '#cs_login_nonce' ).val(),
		};

		$.post( cs_global_vars.ajaxurl, data, function( data ) {
			if ( $.trim( data ) === 'success' ) {
				$( '.cs_errors' ).remove();
				window.location = cs_scripts.checkout_page;
			} else {
				$( '#cs_login_fields input[type=submit]' ).val( complete_purchase_val );
				$( '.cs-loading-ajax' ).remove();
				$( '.cs_errors' ).remove();
				$( '#cs-user-login-submit' ).before( data );
			}
		} );
	} );

	// Load the fields for the selected payment method
	$(document).on('change', 'select#cs-gateway, input.cs-gateway', function (e) {
		const payment_mode = $( '#cs-gateway option:selected, input.cs-gateway:checked' ).val();

		if ( payment_mode === '0' ) {
			return false;
		}

		cs_load_gateway( payment_mode );

		return false;
	} );

	// Auto load first payment gateway
	if ( cs_scripts.is_checkout === '1' ) {
		let chosen_gateway = false;
		let ajax_needed = false;

		if ( $( 'select#cs-gateway, input.cs-gateway' ).length ) {
			chosen_gateway = $( "meta[name='cs-chosen-gateway']" ).attr( 'content' );
			ajax_needed = true;
		}

		if ( ! chosen_gateway ) {
			chosen_gateway = cs_scripts.default_gateway;
		}

		if ( ajax_needed ) {
			// If we need to ajax in a gateway form, send the requests for the POST.
			setTimeout( function() {
				cs_load_gateway( chosen_gateway );
			}, 200 );
		} else {
			// The form is already on page, just trigger that the gateway is loaded so further action can be taken.
			$( 'body' ).trigger( 'cs_gateway_loaded', [ chosen_gateway ] );
		}
	}

	// Process checkout
	$( document ).on( 'click', '#cs_purchase_form #cs_purchase_submit [type=submit]', function( e ) {
		const eddPurchaseform = document.getElementById( 'cs_purchase_form' );

		if ( typeof eddPurchaseform.checkValidity === 'function' && false === eddPurchaseform.checkValidity() ) {
			return;
		}

		e.preventDefault();

		const complete_purchase_val = $( this ).val();

		$( this ).val( cs_global_vars.purchase_loading );

		$( this ).prop( 'disabled', true );

		$( this ).after( '<span class="cs-loading-ajax cs-loading"></span>' );

		$.post( cs_global_vars.ajaxurl, $( '#cs_purchase_form' ).serialize() + '&action=cs_process_checkout&cs_ajax=true', function( data ) {
			if ( $.trim( data ) === 'success' ) {
				$( '.cs_errors' ).remove();
				$( '.cs-error' ).hide();
				$( eddPurchaseform ).submit();
			} else {
				$( '#cs-purchase-button' ).val( complete_purchase_val );
				$( '.cs-loading-ajax' ).remove();
				$( '.cs_errors' ).remove();
				$( '.cs-error' ).hide();
				$( cs_global_vars.checkout_error_anchor ).before( data );
				$( '#cs-purchase-button' ).prop( 'disabled', false );

				$( document.body ).trigger( 'cs_checkout_error', [ data ] );
			}
		} );
	} );

	// Update state field
	$( document.body ).on( 'change', '#cs_cc_address input.card_state, #cs_cc_address select, #cs_address_country', update_state_field );

	function update_state_field() {
		const $this = $( this );
		let $form;
		const is_checkout = typeof cs_global_vars !== 'undefined';
		let field_name = 'card_state';
		if ( $( this ).attr( 'id' ) === 'cs_address_country' ) {
			field_name = 'cs_address_state';
		}

		let state_inputs = document.getElementById( field_name );

		if ( 'card_state' !== $this.attr( 'id' ) && null != state_inputs ) {
			const nonce = $( this ).data( 'nonce' );

			// If the country field has changed, we need to update the state/province field
			const postData = {
				action: 'cs_get_shop_states',
				country: $this.val(),
				field_name: field_name,
				nonce: nonce,
			};

			$.ajax( {
				type: 'POST',
				data: postData,
				url: cs_scripts.ajaxurl,
				xhrFields: {
					withCredentials: true,
				},
				success: function( response ) {
					if ( is_checkout ) {
						$form = $( '#cs_purchase_form' );
					} else {
						$form = $this.closest( 'form' );
					}

					const state_inputs = 'input[name="card_state"], select[name="card_state"], input[name="cs_address_state"], select[name="cs_address_state"]';

					if ( 'nostates' === $.trim( response ) ) {
						const text_field = '<input type="text" id="' + field_name + '" name="card_state" class="card-state cs-input required" value=""/>';
						$form.find( state_inputs ).replaceWith( text_field );
					} else {
						$form.find( state_inputs ).replaceWith( response );
					}

					if ( is_checkout ) {
						$( document.body ).trigger( 'cs_cart_billing_address_updated', [ response ] );
					}
				},
			} ).fail( function( data ) {
				if ( window.console && window.console.log ) {
					console.log( data );
				}
			} ).done( function( data ) {
				if ( is_checkout ) {
					recalculateTaxes();
				}
			} );
		} else if ( is_checkout ) {
			recalculateTaxes();
		}

		return false;
	}

	// Backwards compatibility. Assign function to global namespace.
	window.update_state_field = update_state_field;

	// If is_checkout, recalculate sales tax on postalCode change.
	$( document.body ).on( 'change', '#cs_cc_address input[name=card_zip]', function() {
		if ( typeof cs_global_vars !== 'undefined' ) {
			recalculateTaxes();
		}
	} );
} );

// Load a payment gateway
function cs_load_gateway( payment_mode ) {
	// Show the ajax loader
	jQuery( '.cs-cart-ajax' ).show();
	jQuery( '#cs_purchase_form_wrap' ).html( '<span class="cs-loading-ajax cs-loading"></span>' );

	const nonce = document.getElementById( 'cs-gateway-' + payment_mode ).getAttribute( 'data-' + payment_mode + '-nonce' );
	let url = cs_scripts.ajaxurl;

	if ( url.indexOf( '?' ) > 0 ) {
		url = url + '&';
	} else {
		url = url + '?';
	}

	url = url + 'payment-mode=' + payment_mode;

	jQuery.post( url, { action: 'cs_load_gateway', cs_payment_mode: payment_mode, nonce: nonce },
		function( response ) {
			jQuery( '#cs_purchase_form_wrap' ).html( response );
			jQuery( '.cs-no-js' ).hide();
			jQuery( 'body' ).trigger( 'cs_gateway_loaded', [ payment_mode ] );
		}
	);
}

// Backwards compatibility. Assign function to global namespace.
window.cs_load_gateway = cs_load_gateway;
