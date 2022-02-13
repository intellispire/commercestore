var cs_scripts;
jQuery(document).ready(function($) {
	$('.cs_subscription_cancel').on('click',function(e) {
		if( confirm( cs_recurring_vars.confirm_cancel ) ) {
			return true;
		}
		return false;
	});

	// Force subscription terms to behave for Custom Prices
	$('.cs_download_purchase_form').each(function() {

		var form = $(this);

		if ( form.find( '.cs-cp-container' ).length && form.find( '.cs_price_options' ).length ) {

			var terms = form.find('.eddr-custom-terms-notice');
			var signup_fee = form.find('.eddr-custom-signup-fee-notice');
			terms.prev().append(terms);
			signup_fee.prev().append(signup_fee);
			terms.show();
			signup_fee.show();

		} else if ( form.find( '.cs-cp-container' ).length ) {

			form.find('.cs_cp_price').keyup(function() {
				form.find('.eddr-terms-notice,.eddr-signup-fee-notice').hide();
				form.find('.eddr-custom-terms-notice,.eddr-custom-signup-fee-notice').show();
			});

		}

	});

	if( cs_recurring_vars.has_trial ) {
		$('.cs_cart_amount').html( cs_recurring_vars.total );
	}

	// Look to see if the customer has purchased a free trial after email is entered on checkout
	$('#cs_purchase_form').on( 'focusout', '#cs-email', function() {

		if( 'undefined' == cs_scripts ) {
			return;
		}

		// We don't need to make this AJAX call, if there isn't a trial in the cart.
		if ( ! cs_recurring_vars.has_trial ) {
			return;
		}

		var email = $(this).val();
		var product_ids = [];

		$('body').find('.cs_cart_item').each(function() {
			product_ids.push( $(this).data( 'download-id' ) );
		});

		 $.ajax({
			type: "POST",
			data: {
				action: 'cs_recurring_check_repeat_trial',
				email: email,
				downloads: product_ids
			},
			dataType: "json",
			url: cs_scripts.ajaxurl,
			xhrFields: {
				withCredentials: true
			},
			success: function (response) {

				if( response.message ) {
					$('<div class="cs_errors"><p class="cs_error">' + response.message + '</p></div>').insertBefore( '#cs_purchase_submit' );
				}

			}
		}).fail(function (response) {
			if ( window.console && window.console.log ) {
				console.log( response );
			}
		}).done(function (response) {

		});

	});

});