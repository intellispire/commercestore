/**
 * Internal dependencies.
 */
import { getChosenVars } from 'utils/chosen.js';
import { cs_attach_tooltips } from 'admin/components/tooltips';
import './bulk-edit.js';

/**
 * Download Configuration Metabox
 */
var CS_Download_Configuration = {
	init: function() {
		this.add();
		this.move();
		this.remove();
		this.type();
		this.prices();
		this.files();
		this.updatePrices();
		this.showAdvanced();
	},
	clone_repeatable: function( row ) {
		// Retrieve the highest current key
		let key = 1;
		let highest = 1;
		row.parent().find( '.cs_repeatable_row' ).each( function() {
			const current = $( this ).data( 'key' );
			if ( parseInt( current ) > highest ) {
				highest = current;
			}
		} );
		key = highest += 1;

		const clone = row.clone();

		clone.removeClass( 'cs_add_blank' );

		clone.attr( 'data-key', key );
		clone.find( 'input, select, textarea' ).val( '' ).each( function() {
			let elem = $( this ),
				name = elem.attr( 'name' ),
				id = elem.attr( 'id' );

			if ( name ) {
				name = name.replace( /\[(\d+)\]/, '[' + parseInt( key ) + ']' );
				elem.attr( 'name', name );
			}

			elem.attr( 'data-key', key );

			if ( typeof id !== 'undefined' ) {
				id = id.replace( /(\d+)/, parseInt( key ) );
				elem.attr( 'id', id );
			}
		} );

		/** manually update any select box values */
		clone.find( 'select' ).each( function() {
			$( this ).val( row.find( 'select[name="' + $( this ).attr( 'name' ) + '"]' ).val() );
		} );

		/** manually uncheck any checkboxes */
		clone.find( 'input[type="checkbox"]' ).each( function() {
			// Make sure checkboxes are unchecked when cloned
			const checked = $( this ).is( ':checked' );
			if ( checked ) {
				$( this ).prop( 'checked', false );
			}

			// reset the value attribute to 1 in order to properly save the new checked state
			$( this ).val( 1 );
		} );

		clone.find( 'span.cs_price_id' ).each( function() {
			$( this ).text( parseInt( key ) );
		} );

		clone.find( 'input.cs_repeatable_index' ).each( function() {
			$( this ).val( parseInt( $( this ).data( 'key' ) ) );
		} );

		clone.find( 'span.cs_file_id' ).each( function() {
			$( this ).text( parseInt( key ) );
		} );

		clone.find( '.cs_repeatable_default_input' ).each( function() {
			$( this ).val( parseInt( key ) ).removeAttr( 'checked' );
		} );

		clone.find( '.cs_repeatable_condition_field' ).each( function() {
			$( this ).find( 'option:eq(0)' ).prop( 'selected', 'selected' );
		} );

		clone.find( 'label' ).each( function () {
			var labelFor = $( this ).attr( 'for' );
			if ( labelFor ) {
				$( this ).attr( 'for', labelFor.replace( /(\d+)/, parseInt( key ) ) );
			}
		} );

		// Remove Chosen elements
		clone.find( '.search-choice' ).remove();
		clone.find( '.chosen-container' ).remove();
		cs_attach_tooltips( clone.find( '.cs-help-tip' ) );

		return clone;
	},

	add: function() {
		$( document.body ).on( 'click', '.cs_add_repeatable', function( e ) {
			e.preventDefault();

			const button = $( this ),
				row = button.closest( '.cs_repeatable_table' ).find( '.cs_repeatable_row' ).last(),
				clone = CS_Download_Configuration.clone_repeatable( row );

			clone.insertAfter( row ).find( 'input, textarea, select' ).filter( ':visible' ).eq( 0 ).focus();

			// Setup chosen fields again if they exist
			clone.find( '.cs-select-chosen' ).each( function() {
				const el = $( this );
				el.chosen( getChosenVars( el ) );
			} );
			clone.find( '.cs-select-chosen' ).css( 'width', '100%' );
			clone.find( '.cs-select-chosen .chosen-search input' ).attr( 'placeholder', cs_vars.search_placeholder );
		} );
	},

	move: function() {
		$( '.cs_repeatable_table .cs-repeatables-wrap' ).sortable( {
			axis: 'y',
			handle: '.cs-draghandle-anchor',
			items: '.cs_repeatable_row',
			cursor: 'move',
			tolerance: 'pointer',
			containment: 'parent',
			distance: 2,
			opacity: 0.7,
			scroll: true,

			update: function() {
				let count = 0;
				$( this ).find( '.cs_repeatable_row' ).each( function() {
					$( this ).find( 'input.cs_repeatable_index' ).each( function() {
						$( this ).val( count );
					} );
					count++;
				} );
			},
			start: function( e, ui ) {
				ui.placeholder.height( ui.item.height() - 2 );
			},
		} );
	},

	remove: function() {
		$( document.body ).on( 'click', '.cs-remove-row, .cs_remove_repeatable', function( e ) {
			e.preventDefault();

			let row = $( this ).parents( '.cs_repeatable_row' ),
				count = row.parent().find( '.cs_repeatable_row' ).length,
				type = $( this ).data( 'type' ),
				repeatable = 'div.cs_repeatable_' + type + 's',
				focusElement,
				focusable,
				firstFocusable;

			// Set focus on next element if removing the first row. Otherwise set focus on previous element.
			if ( $( this ).is( '.ui-sortable .cs_repeatable_row:first-child .cs-remove-row, .ui-sortable .cs_repeatable_row:first-child .cs_remove_repeatable' ) ) {
				focusElement = row.next( '.cs_repeatable_row' );
			} else {
				focusElement = row.prev( '.cs_repeatable_row' );
			}

			focusable = focusElement.find( 'select, input, textarea, button' ).filter( ':visible' );
			firstFocusable = focusable.eq( 0 );

			if ( type === 'price' ) {
				const price_row_id = row.data( 'key' );
				/** remove from price condition */
				$( '.cs_repeatable_condition_field option[value="' + price_row_id + '"]' ).remove();
			}

			if ( count > 1 ) {
				$( 'input, select', row ).val( '' );
				row.fadeOut( 'fast' ).remove();
				firstFocusable.focus();
			} else {
				switch ( type ) {
					case 'price' :
						alert( cs_vars.one_price_min );
						break;
					case 'file' :
						$( 'input, select', row ).val( '' );
						break;
					default:
						alert( cs_vars.one_field_min );
						break;
				}
			}

			/* re-index after deleting */
			$( repeatable ).each( function( rowIndex ) {
				$( this ).find( 'input, select' ).each( function() {
					let name = $( this ).attr( 'name' );
					name = name.replace( /\[(\d+)\]/, '[' + rowIndex + ']' );
					$( this ).attr( 'name', name ).attr( 'id', name );
				} );
			} );
		} );
	},

	type: function() {
		$( document.body ).on( 'change', '#_cs_product_type', function( e ) {
			const cs_products = $( '#cs_products' ),
				cs_download_files = $( '#cs_download_files' ),
				cs_download_limit_wrap = $( '#cs_download_limit_wrap' );

			if ( 'bundle' === $( this ).val() ) {
				cs_products.show();
				cs_download_files.hide();
				cs_download_limit_wrap.hide();
			} else {
				cs_products.hide();
				cs_download_files.show();
				cs_download_limit_wrap.show();
			}
		} );
	},

	prices: function() {
		$( document.body ).on( 'change', '#cs_variable_pricing', function( e ) {
			const checked = $( this ).is( ':checked' ),
				single = $( '#cs_regular_price_field' ),
				variable = $( '#cs_variable_price_fields, .cs_repeatable_table .pricing' ),
				bundleRow = $( '.cs-bundled-product-row, .cs-repeatable-row-standard-fields' );

			if ( checked ) {
				single.hide();
				variable.show();
				bundleRow.addClass( 'has-variable-pricing' );
			} else {
				single.show();
				variable.hide();
				bundleRow.removeClass( 'has-variable-pricing' );
			}
		} );
	},

	files: function() {
		var file_frame;
		window.formfield = '';

		$( document.body ).on( 'click', '.cs_upload_file_button', function( e ) {
			e.preventDefault();

			const button = $( this );

			window.formfield = button.closest( '.cs_repeatable_upload_wrapper' );

			// If the media frame already exists, reopen it.
			if ( file_frame ) {
				file_frame.open();
				return;
			}

			// Create the media frame.
			file_frame = wp.media.frames.file_frame = wp.media( {
				title: button.data( 'uploader-title' ),
				frame: 'post',
				state: 'insert',
				button: { text: button.data( 'uploader-button-text' ) },
				multiple: $( this ).data( 'multiple' ) === '0' ? false : true, // Set to true to allow multiple files to be selected
			} );

			file_frame.on( 'menu:render:default', function( view ) {
				// Store our views in an object.
				const views = {};

				// Unset default menu items
				view.unset( 'library-separator' );
				view.unset( 'gallery' );
				view.unset( 'featured-image' );
				view.unset( 'embed' );

				// Initialize the views in our view object.
				view.set( views );
			} );

			// When an image is selected, run a callback.
			file_frame.on( 'insert', function() {
				const selection = file_frame.state().get( 'selection' );
				selection.each( function( attachment, index ) {
					attachment = attachment.toJSON();

					let selectedSize = 'image' === attachment.type ? $( '.attachment-display-settings .size option:selected' ).val() : false,
						selectedURL = attachment.url,
						selectedName = attachment.title.length > 0 ? attachment.title : attachment.filename;

					if ( selectedSize && typeof attachment.sizes[ selectedSize ] !== 'undefined' ) {
						selectedURL = attachment.sizes[ selectedSize ].url;
					}

					if ( 'image' === attachment.type ) {
						if ( selectedSize && typeof attachment.sizes[ selectedSize ] !== 'undefined' ) {
							selectedName = selectedName + '-' + attachment.sizes[ selectedSize ].width + 'x' + attachment.sizes[ selectedSize ].height;
						} else {
							selectedName = selectedName + '-' + attachment.width + 'x' + attachment.height;
						}
					}

					if ( 0 === index ) {
						// place first attachment in field
						window.formfield.find( '.cs_repeatable_attachment_id_field' ).val( attachment.id );
						window.formfield.find( '.cs_repeatable_thumbnail_size_field' ).val( selectedSize );
						window.formfield.find( '.cs_repeatable_upload_field' ).val( selectedURL );
						window.formfield.find( '.cs_repeatable_name_field' ).val( selectedName );
					} else {
						// Create a new row for all additional attachments
						const row = window.formfield,
							clone = CS_Download_Configuration.clone_repeatable( row );

						clone.find( '.cs_repeatable_attachment_id_field' ).val( attachment.id );
						clone.find( '.cs_repeatable_thumbnail_size_field' ).val( selectedSize );
						clone.find( '.cs_repeatable_upload_field' ).val( selectedURL );
						clone.find( '.cs_repeatable_name_field' ).val( selectedName );
						clone.insertAfter( row );
					}
				} );
			} );

			// Finally, open the modal
			file_frame.open();
		} );

		// @todo Break this out and remove jQuery.
		$( '.cs_repeatable_upload_field' )
			.on( 'focus', function() {
				const input = $( this );

				input.data( 'originalFile', input.val() );
			} )
			.on( 'change', function() {
				const input = $( this );
				const originalFile = input.data( 'originalFile' );

				if ( originalFile !== input.val() ) {
					input
						.closest( '.cs-repeatable-row-standard-fields' )
						.find( '.cs_repeatable_attachment_id_field' )
						.val( 0 );
				}
			} );

		var file_frame;
		window.formfield = '';
	},

	updatePrices: function() {
		$( '#cs_price_fields' ).on( 'keyup', '.cs_variable_prices_name', function() {
			const key = $( this ).parents( '.cs_repeatable_row' ).data( 'key' ),
				name = $( this ).val(),
				field_option = $( '.cs_repeatable_condition_field option[value=' + key + ']' );

			if ( field_option.length > 0 ) {
				field_option.text( name );
			} else {
				$( '.cs_repeatable_condition_field' ).append(
					$( '<option></option>' )
						.attr( 'value', key )
						.text( name )
				);
			}
		} );
	},

	showAdvanced: function() {
		// Toggle display of entire custom settings section for a price option
		$( document.body ).on( 'click', '.toggle-custom-price-option-section', function( e ) {
			e.preventDefault();

			const toggle = $( this ),
				  show = toggle.html() === cs_vars.show_advanced_settings ?
					  true :
					  false;

			if ( show ) {
				toggle.html( cs_vars.hide_advanced_settings );
			} else {
				toggle.html( cs_vars.show_advanced_settings );
			}

			const header = toggle.parents( '.cs-repeatable-row-header' );
			header.siblings( '.cs-custom-price-option-sections-wrap' ).slideToggle();

			let first_input;
			if ( show ) {
				first_input = $( ':input:not(input[type=button],input[type=submit],button):visible:first', header.siblings( '.cs-custom-price-option-sections-wrap' ) );
			} else {
				first_input = $( ':input:not(input[type=button],input[type=submit],button):visible:first', header.siblings( '.cs-repeatable-row-standard-fields' ) );
			}
			first_input.focus();
		} );
	}
};

jQuery( document ).ready( function( $ ) {
	CS_Download_Configuration.init();
} );
