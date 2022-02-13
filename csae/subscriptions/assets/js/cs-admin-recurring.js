/**
 * CS Admin Recurring JS
 *
 * @description: JS for CS's Recurring Add-on applied in admin download (single download post) screen
 *
 */
var CS_Recurring_Vars;

jQuery( document ).ready( function ( $ ) {

	var CS_Recurring = {
		init: function () {

			//Recurring select field conditionals
			this.variable_pricing();
			this.recurring_select();
			this.custom_price_toggle();
			this.free_trial_toggle();
			this.variable_price_free_trial_toggle();
			this.validate_times();
			this.edit_expiration();
			this.edit_product_id();
			this.edit_profile_id();
			this.edit_txn_id();
			this.new();
			this.delete();
			//Ensure when new rows are added recurring fields respect recurring select option
			$( '.cs_add_repeatable' ).on( 'click', this.recurring_select() );

			// Toggle display of Billing Cycle details
			$( '.cs-item-toggle-next-hidden-row' ).on( 'click', function(e) {
				e.preventDefault();
				$(this).parents('tr').siblings('.cs-item-hidden-row').slideToggle();
			});

		},

		/**
		 * Toggle the single recurring fields when the variable pricing option changes.
		 */
		variable_pricing: function () {
			$( 'body' ).on( 'change', '#cs_variable_pricing', function () {
				$( '.cs-recurring-single' ).toggle( !$( this ).is( ':checked' ) );
			} );
		},

		/**
		 * Recurring Select
		 * @description: Ensures that the "period", "times", and "signup fees" fields are disabled/enabled according to the "Recurring" selection yes/no option
		 */
		recurring_select: function () {
			$( 'body' ).on( 'change', '.cs-recurring-enabled select, select#cs_recurring, select#cs_custom_recurring', function () {
				var $this  = $( this ),
					fields = $this.parents( '.cs-recurring-single' ).find( 'select,input' ),
					val    = $( 'option:selected', this ).val();

				if( ! $this.is(':visible') ) {
					return;
				}


				// Is this a variable select? Check parent
				if ( $this.parents( '.cs_variable_prices_wrapper' ).length > 0 ) {

					fields = $this.parents('.cs_repeatable_row').find( '.times input, .cs-recurring-period select, .cs-recurring-free-trial input, .cs-recurring-free-trial select, .signup_fee input' );

				} else if( 'cs_custom_recurring' == $(this).attr('id') ) {

					fields = $('.cs_recurring_custom_wrap').find( '.times input, #cs_custom_period, .signup_fee input' );

					if( $('#cs_recurring_free_trial').is(':checked') ) {
						$('.signup_fee input, #cs_signup_fee').val(0).attr('disabled', true );
					}
				}

				// Enable/disable fields based on user selection
				if ( val == 'no' ) {
					fields.attr( 'disabled', true );
				} else {
					fields.attr( 'disabled', false );
				}

				$this.attr( 'disabled', false );

			} );

			// Kick it off
			$( '.cs-recurring-enabled select, select#cs_recurring, select#cs_custom_recurring' ).change();

			$( 'input[name$="[times]"], input[name$=times]' ).change( function () {
				$( this ).next( '.times' ).text( $( this ).val() == 1 ? CS_Recurring_Vars.singular : CS_Recurring_Vars.plural );
			} );
		},

		/**
		 * Custom Price toggle
		 * @description: Hides / shows recurring options for a custom price
		 */
		custom_price_toggle: function () {
			$('body').on('click', '#cs_cp_custom_pricing', function() {
				$('.cs_recurring_custom_wrap').toggle();
			});
		},

		/**
		 * Free trial toggle
		 * @description: Hides / shows recurring options for a free trial
		 */
		free_trial_toggle: function () {
			$('body').on('click', '#cs_recurring_free_trial', function() {
				if( $(this).is(':checked') ) {
					$('#cs_recurring_free_trial_options,#cs-sl-free-trial-length-notice').show();
					$('.signup_fee input, #cs_signup_fee').val(0).attr('disabled', true );
				} else {
					$('.signup_fee input, #cs_signup_fee').attr('disabled', false );
					$('#cs-sl-free-trial-length-notice,#cs_recurring_free_trial_options').hide();
				}
			});

			$('body').on( 'change', '#cs_variable_pricing', function() {
				var checked   = $(this).is(':checked');
				var single    = $( '#cs_recurring_free_trial_options_wrap' );
				if ( checked ) {
					single.hide();
				} else {
					single.show();
				}
			});
		},

		variable_price_free_trial_toggle: function () {
			$( 'body' ).on( 'load change', '.trial-quantity', function () {
				var $this  = $( this ),
					fields = $this.parents().siblings( '.signup_fee' ).find( ':input' ),
					val    = $this.val();

				// Enable/disable fields based on user selection
				if ( val > 0 ) {
					fields.attr( 'disabled', true );
				} else {
					fields.attr( 'disabled', false );
				}

				$this.attr( 'disabled', false );
			});
		},

		/**
		 * Validate Times
		 * @description: Used for client side validation of times set for various recurring gateways
		 */
		validate_times: function () {

			var recurring_times = $( '.times' ).find( 'input[type="number"]' );

			//Validate times on times input blur (client side then server side)
			recurring_times.on( 'change', function () {

				var time_val = $( this ).val();
				var is_variable = $( 'input#cs_variable_pricing' ).prop( 'checked' );
				var recurring_option = $( this ).parents( '#cs_regular_price_field' ).find( '[id^=cs_recurring]' ).val();
				if ( is_variable ) {
					recurring_option = $( this ).parents( '.cs_variable_prices_wrapper' ).find( '[id^=cs_recurring]' ).val();
				}

				//Verify this is a recurring download first
				//Sanity check: only validate if recurring is set to Yes
				if ( recurring_option == 'no' ) {
					return false;
				}

				//Check if PayPal Standard is set & Validate times are over 1 - https://github.com/commercestore/cs-recurring/issues/58
				if ( typeof CS_Recurring_Vars.enabled_gateways.paypal !== 'undefined' && (time_val == 1 || time_val >= 53) ) {

					//Alert user of issue
					alert( CS_Recurring_Vars.invalid_time.paypal );
					//Refocus on the faulty input
					$( this ).focus();

				}

			} );

		},

        /**
         * Edit Subscription Text Input
         *
         * @since
         *
         * @description: Handles actions when a user clicks the edit or cancel buttons in sub details
         *
         * @param link object The edit/cancelled element the user clicked
         * @param input the editable field
         */
        edit_subscription_input: function (link, input) {

            //User clicks edit
            if (link.text() === CS_Recurring_Vars.action_edit) {
                //Preserve current value
                link.data('current-value', input.val());
                //Update text to 'cancel'
                link.text(CS_Recurring_Vars.action_cancel);
            } else {
                //User clicked cancel, return previous value
                input.val(link.data('current-value'));
                //Update link text back to 'edit'
                link.text(CS_Recurring_Vars.action_edit);
            }

        },

		edit_expiration: function() {

			$('.cs-edit-sub-expiration').on('click', function(e) {
				e.preventDefault();

				var link = $(this);
				var exp_input = $('input.cs-sub-expiration');
				CS_Recurring.edit_subscription_input(link, exp_input);

				$('.cs-sub-expiration').toggle();
				$('#cs-sub-expiration-update-notice').slideToggle();
			});

		},

		edit_profile_id: function() {

			$('.cs-edit-sub-profile-id').on('click', function(e) {
				e.preventDefault();

				var link = $(this);
				var profile_input = $('input.cs-sub-profile-id');
				CS_Recurring.edit_subscription_input(link, profile_input);

				$('.cs-sub-profile-id').toggle();
				$('#cs-sub-profile-id-update-notice').slideToggle();
			});

		},

		edit_product_id: function() {

			$('.cs-sub-product-id').on('change', function(e) {
				e.preventDefault();

				$('#cs-sub-product-update-notice').slideDown();
			});

		},

		edit_txn_id: function() {

			$('.cs-edit-sub-transaction-id').on('click', function(e) {
				e.preventDefault();

				var link = $(this);
				var txn_input = $('input.cs-sub-transaction-id');
				CS_Recurring.edit_subscription_input(link, txn_input);

				$('.cs-sub-transaction-id').toggle();
			});

		},

		new: function() {

			$('.cs-recurring-new-customer,.cs-recurring-select-customer').on('click', function(e) {

				e.preventDefault();
				if($(this).hasClass('cs-recurring-new-customer')) {
					$('.cs-recurring-customer-wrap-new').show();
					$('.cs-recurring-customer-wrap-existing').hide();
				} else {
					$('.cs-recurring-customer-wrap-existing').show();
					$('.cs-recurring-customer-wrap-new').hide();
				}
				$('.cs-recurring-customer-wrap:visible').find('select,input').focus();

			});

			$('.cs-recurring-select-payment').on('change', function(e) {
				$('.cs-recurring-payment-id').toggle().val( '' );
				$('.cs-recurring-gateway-wrap').toggle();
			});

			$('#cs-recurring-new-subscription-wrap').on('change', 'select#products', function() {

				var $this = $(this), download_id = $this.val();

				if( parseInt( download_id ) > 0 ) {

					var postData = {
						action : 'cs_check_for_download_price_variations',
						download_id: download_id
					};

					$.ajax({
						type: "POST",
						data: postData,
						url: ajaxurl,
						success: function (prices) {

							$this.parent().find( '.cs-recurring-price-option-wrap' ).html( prices );

						}

					}).fail(function (data) {
						if ( window.console && window.console.log ) {
							console.log( data );
						}
					});

				}
			});

		},

		delete: function() {

			$('.cs-delete-subscription').on('click', function(e) {

				if( confirm( CS_Recurring_Vars.delete_subscription ) ) {
					return true;
				}

				return false;
			});

		}

	};

	CS_Recurring.init();

} );
