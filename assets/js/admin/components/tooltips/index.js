/**
 * Attach tooltips
 *
 * @param {string} selector
 */
export const cs_attach_tooltips = function( selector ) {
	selector.tooltip( {
		content: function() {
			return $( this ).prop( 'title' );
		},
		tooltipClass: 'cs-ui-tooltip',
		position: {
			my: 'center top',
			at: 'center bottom+10',
			collision: 'flipfit',
		},
		hide: {
			duration: 200,
		},
		show: {
			duration: 200,
		},
	} );
};

jQuery( document ).ready( function( $ ) {
	cs_attach_tooltips( $( '.cs-help-tip' ) );
} );
