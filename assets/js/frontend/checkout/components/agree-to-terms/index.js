/* global $ */

/**
 * Internal dependencies.
 */
import { jQueryReady } from 'utils/jquery.js';

/**
 * DOM ready.
 *
 * @since 3.0
 */
jQueryReady( () => {
	/**
	 * Toggles term content when clicked.
	 *
	 * @since unknown
	 *
	 * @param {Object} e Click event.
	 */
	$( document.body ).on( 'click', '.cs_terms_links', function( e ) {
		e.preventDefault();

		const terms = $( this ).parent();

		terms.prev( '.cs-terms' ).slideToggle();
		terms.find( '.cs_terms_links' ).toggle();
	} );
} );
