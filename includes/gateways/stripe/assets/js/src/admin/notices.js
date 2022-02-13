/* global wp */

/**
 * Handle dismissing admin notices.
 */
const csStripeAdminNotices = function() {
	const notices = Array.prototype.slice.apply(
		document.querySelectorAll( '.csx-admin-notice' )
	);

	if ( 0 === notices.length ) {
		return;
	}

	/**
	 * Loops through each admin notice on the page for processing.
	 *
	 * @param {HTMLElement} noticeEl Notice element.
	 */
	notices.forEach( function( noticeEl ) {
		const dismissButtonEl = noticeEl.querySelector( '.notice-dismiss' );

		// Do nothing if we can't dismiss it.
		if ( ! dismissButtonEl ) {
			return;
		}

		const id = noticeEl.dataset.id;
		const nonce = noticeEl.dataset.nonce;

		/**
		 * Listens for a click event on the dismiss button, and dismisses the notice.
		 *
		 * @param {Event} e Click event.
		 * @return {jQuery.Deferred} Deferred object.
		 */
		dismissButtonEl.addEventListener( 'click', function( e ) {
			e.preventDefault();
			e.stopPropagation();

			return wp.ajax.post(
				'csx_admin_notices_dismiss_ajax',
				{
					id,
					nonce,
				}
			);
		} );
	} );
};

// Wait for the DOM.
document.addEventListener( 'DOMContentLoaded', csStripeAdminNotices );
