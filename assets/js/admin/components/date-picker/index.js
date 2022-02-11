/**
 * Date picker
 *
 * This juggles a few CSS classes to avoid styling collisions with other
 * third-party plugins.
 */
jQuery( document ).ready( function( $ ) {
	const cs_datepicker = $( 'input.cs_datepicker' );

	if ( cs_datepicker.length > 0 ) {
		cs_datepicker

		// Disable autocomplete to avoid it covering the calendar
			.attr( 'autocomplete', 'off' )

		// Invoke the datepickers
			.datepicker( {
				dateFormat: cs_vars.date_picker_format,
				beforeShow: function() {
					$( '#ui-datepicker-div' )
						.removeClass( 'ui-datepicker' )
						.addClass( 'cs-datepicker' );
				},
			} );
	}
} );
