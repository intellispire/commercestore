/**
 * Internal dependencies.
 */
import { jQueryReady } from 'utils/jquery.js';

/**
 * DOM ready.
 */
jQueryReady( () => {
	const products = $( '#cs_products' );
	if ( ! products ) {
		return;
	}

	/**
	 * Show/hide conditions based on input value.
	 */
	products.change( function() {
		$( '#cs-discount-product-conditions' ).toggle( null !== products.val() );
	} );
} );
