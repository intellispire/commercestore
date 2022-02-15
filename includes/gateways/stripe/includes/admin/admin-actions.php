<?php

/**
 * Trigger preapproved payment charge
 *
 * @since 1.6
 * @return void
 */
function csx_process_preapproved_charge() {

	if( empty( $_GET['nonce'] ) )
		return;

	if( ! wp_verify_nonce( $_GET['nonce'], 'csx-process-preapproval' ) )
		return;

	$payment_id  = absint( $_GET['payment_id'] );
	$charge      = csx_charge_preapproved( $payment_id );

	if ( $charge ) {
		wp_redirect( esc_url_raw( add_query_arg( array( 'cs-message' => 'preapproval-charged' ), admin_url( 'edit.php?post_type=download&page=cs-payment-history' ) ) ) ); exit;
	} else {
		wp_redirect( esc_url_raw( add_query_arg( array( 'cs-message' => 'preapproval-failed' ), admin_url( 'edit.php?post_type=download&page=cs-payment-history' ) ) ) ); exit;
	}

}
add_action( 'cs_charge_stripe_preapproval', 'csx_process_preapproved_charge' );


/**
 * Cancel a preapproved payment
 *
 * @since 1.6
 * @return void
 */
function csx_process_preapproved_cancel() {
	global $cs_options;

	if( empty( $_GET['nonce'] ) )
		return;

	if( ! wp_verify_nonce( $_GET['nonce'], 'csx-process-preapproval' ) )
		return;

	$payment_id  = absint( $_GET['payment_id'] );
	$customer_id = get_post_meta( $payment_id, '_csx_stripe_customer_id', true );

	if( empty( $customer_id ) || empty( $payment_id ) ) {
		return;
	}

	if ( 'preapproval' !== get_post_status( $payment_id ) ) {
		return;
	}

	cs_insert_payment_note( $payment_id, __( 'Preapproval cancelled', 'commercestore' ) );
	cs_update_payment_status( $payment_id, 'cancelled' );
	delete_post_meta( $payment_id, '_csx_stripe_customer_id' );

	wp_redirect( esc_url_raw( add_query_arg( array( 'cs-message' => 'preapproval-cancelled' ), admin_url( 'edit.php?post_type=download&page=cs-payment-history' ) ) ) ); exit;
}
add_action( 'cs_cancel_stripe_preapproval', 'csx_process_preapproved_cancel' );

/**
 * Admin Messages
 *
 * @since 1.6
 * @return void
 */
function csx_admin_messages() {

	if ( isset( $_GET['cs-message'] ) && 'preapproval-charged' == $_GET['cs-message'] ) {
		 add_settings_error( 'csx-notices', 'csx-preapproval-charged', __( 'The preapproved payment was successfully charged.', 'commercestore' ), 'updated' );
	}
	if ( isset( $_GET['cs-message'] ) && 'preapproval-failed' == $_GET['cs-message'] ) {
		 add_settings_error( 'csx-notices', 'csx-preapproval-charged', __( 'The preapproved payment failed to be charged. View order details for further details.', 'commercestore' ), 'error' );
	}
	if ( isset( $_GET['cs-message'] ) && 'preapproval-cancelled' == $_GET['cs-message'] ) {
		 add_settings_error( 'csx-notices', 'csx-preapproval-cancelled', __( 'The preapproved payment was successfully cancelled.', 'commercestore' ), 'updated' );
	}
	if ( isset( $_GET['cs-message'] ) && 'connect-to-stripe' === $_GET['cs-message'] ) {
		add_settings_error( 'csx-notices', 'csx-connect-to-stripe', __( 'Connect your Stripe account using the "Connect with Stripe" button below.', 'commercestore' ), 'updated' );
		// I feel dirty, but CS does not remove `cs-message` params from settings URLs and the message carries to all links if not removed, and well I wanted this all to work without touching CS core yet.
		add_filter( 'wp_parse_str', function( $ar ) {
			if( isset( $ar['cs-message'] ) && 'connect-to-stripe' === $ar['cs-message'] ) {
				unset( $ar['cs-message'] );
			}
			return $ar;
		});
	}

	if( isset( $_GET['cs_gateway_connect_error'], $_GET['cs-message'] ) ) {
		echo '<div class="notice notice-error"><p>' . sprintf( __( 'There was an error connecting your Stripe account. Message: %s. Please <a href="%s">try again</a>.', 'commercestore' ), esc_html( urldecode( $_GET['cs-message'] ) ), esc_url( admin_url( 'edit.php?post_type=download&page=cs-settings&tab=gateways&section=cs-stripe' ) ) ) . '</p></div>';
		add_filter( 'wp_parse_str', function( $ar ) {
			if( isset( $ar['cs_gateway_connect_error'] ) ) {
				unset( $ar['cs_gateway_connect_error'] );
			}

			if( isset( $ar['cs-message'] ) ) {
				unset( $ar['cs-message'] );
			}
			return $ar;
		});
	}

	settings_errors( 'csx-notices' );
}
add_action( 'admin_notices', 'csx_admin_messages' );

/**
 * Add payment meta item to payments that used an existing card
 *
 * @since 2.6
 * @param $payment_id
 * @return void
 */
function csx_show_existing_card_meta( $payment_id ) {
	$payment = new CS_Payment( $payment_id );
	$existing_card = $payment->get_meta( '_csx_used_existing_card' );
	if ( ! empty( $existing_card ) ) {
		?>
		<div class="cs-order-stripe-existing-card cs-admin-box-inside">
			<p>
				<span class="label"><?php _e( 'Used Existing Card:', 'commercestore' ); ?></span>&nbsp;
				<span><?php _e( 'Yes', 'commercestore' ); ?></span>
			</p>
		</div>
		<?php
	}
}
add_action( 'cs_view_order_details_payment_meta_after', 'csx_show_existing_card_meta', 10, 1 );

/**
 * Handles redirects to the Stripe settings page under certain conditions.
 *
 * @since 2.6.14
 */
function csx_stripe_connect_test_mode_toggle_redirect() {

	// Check for our marker
	if( ! isset( $_POST['cs-test-mode-toggled'] ) ) {
		return;
	}

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	if( ! cs_is_gateway_active( 'stripe' ) ) {
		return;
	}

	/**
	 * Filter the redirect that happens when options are saved and
	 * add query args to redirect to the Stripe settings page
	 * and to show a notice about connecting with Stripe.
	 */
	add_filter( 'wp_redirect', function( $location ) {
		if( false !== strpos( $location, 'page=cs-settings' ) && false !== strpos( $location, 'settings-updated=true' ) ) {
			$location = add_query_arg(
				array(
					'section' => 'cs-stripe',
					'cs-message' => 'connect-to-stripe',
				),
				$location
			);
		}
		return $location;
	} );

}
add_action( 'admin_init', 'csx_stripe_connect_test_mode_toggle_redirect' );
