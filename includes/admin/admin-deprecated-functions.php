<?php

/**
 * Admin Deprecated Functions
 *
 * All admin functions that have been deprecated.
 *
 * @package     CS
 * @subpackage  Deprecated
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

/**
 * Display the ban emails tab
 *
 * @since 2.0
 * @deprecated 3.0 replaced by Order Blocking in settings.
 */
function cs_tools_banned_emails_display() {
	_cs_deprecated_function( __FUNCTION__, '3.0' );
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	do_action( 'cs_tools_banned_emails_before' );
	?>
	<div class="postbox">
		<h3><span><?php _e( 'Banned Emails', 'commercestore' ); ?></span></h3>
		<div class="inside">
			<p><?php _e( 'Emails placed in the box below will not be allowed to make purchases.', 'commercestore' ); ?></p>
			<form method="post"
					action="<?php echo admin_url( 'edit.php?post_type=download&page=cs-tools&tab=general' ); ?>">
				<p>
					<textarea name="banned_emails" rows="10"
								class="large-text"><?php echo implode( "\n", cs_get_banned_emails() ); ?></textarea>
					<span class="description"><?php _e( 'Enter emails and/or domains (starting with "@") and/or TLDs (starting with ".") to disallow, one per line.', 'commercestore' ); ?></span>
				</p>
				<p>
					<input type="hidden" name="cs_action" value="save_banned_emails"/>
					<?php wp_nonce_field( 'cs_banned_emails_nonce', 'cs_banned_emails_nonce' ); ?>
					<?php submit_button( __( 'Save', 'commercestore' ), 'secondary', 'submit', false ); ?>
				</p>
			</form>
		</div><!-- .inside -->
	</div><!-- .postbox -->
	<?php
	do_action( 'cs_tools_banned_emails_after' );
	do_action( 'cs_tools_after' );
}

/**
 * Trigger a Purchase Deletion
 *
 * @since 1.3.4
 * @deprecated 3.0 replaced by cs_trigger_destroy_order.
 * @param array $data Arguments passed.
 * @return void
 */
function cs_trigger_purchase_delete( $data ) {
	if ( wp_verify_nonce( $data['_wpnonce'], 'cs_payment_nonce' ) ) {

		$payment_id = absint( $data['purchase_id'] );

		if ( ! current_user_can( 'delete_shop_payments', $payment_id ) ) {
			wp_die( __( 'You do not have permission to edit this payment record', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}

		cs_delete_purchase( $payment_id );

		cs_redirect( admin_url( '/edit.php?post_type=download&page=cs-payment-history&cs-message=payment_deleted' ) );
	}
}
add_action( 'cs_delete_payment', 'cs_trigger_purchase_delete' );
