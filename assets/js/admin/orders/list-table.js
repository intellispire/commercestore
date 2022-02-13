/* global $, ajaxurl */

/**
 * Internal dependencies
 */
import { jQueryReady } from 'utils/jquery.js';

jQueryReady( () => {

	$( '.download_page_cs-payment-history .row-actions .delete a' ).on( 'click', function() {
		if( confirm( cs_vars.delete_payment ) ) {
			return true;
		}
		return false;
	});

} );
