<?php

/**
 * Stripe Upgrade Notices
 *
 * @since 2.6
 *
 */
function cs_stripe_upgrade_notices() {

	global $wpdb;

	// Don't show notices on the upgrades page
	if ( isset( $_GET['page'] ) && $_GET['page'] == 'cs-upgrades' ) {
		return;
	}

	if ( ! cs_has_upgrade_completed( 'stripe_customer_id_migration' ) ) {

		$has_stripe_customers = $wpdb->get_var( "SELECT count(user_id) FROM $wpdb->usermeta WHERE meta_key IN ( '_cs_stripe_customer_id', '_cs_stripe_customer_id_test' ) LIMIT 1" );
		$needs_upgrade = ! empty( $has_stripe_customers );

		if( ! $needs_upgrade ) {
			cs_set_upgrade_complete( 'stripe_customer_id_migration' );
			return;
		}

		printf(
			'<div class="updated">' .
			'<p>' .
			__( 'Easy Digital Downloads - Stripe Gateway needs to upgrade the customers database, click <a href="%s">here</a> to start the upgrade. <a href="#" onClick="jQuery(this).parent().next(\'p\').slideToggle()">Learn more about this upgrade</a>', 'csx' ) .
			'</p>' .
			'<p style="display: none;">' .
			__( '<strong>About this upgrade:</strong><br />This upgrade will improve the reliability of associating purchase records with your existing customer records in Stripe by changing their Stripe Customer IDs to be stored locally on their CS customer record, instead of their user record.', 'csx' ) .
			'<br /><br />' .
			__( '<strong>Advanced User?</strong><br />This upgrade can also be run via WPCLI with the following command:<br /><code>wp cs-stripe migrate_customer_ids</code>', 'csx' ) .
			'</p>' .
			'</div>',
			esc_url( admin_url( 'index.php?page=cs-upgrades&cs-upgrade=stripe_customer_id_migration' ) )
		);
	}

}
add_action( 'admin_notices', 'cs_stripe_upgrade_notices' );

/**
 * Migrates Stripe Customer IDs from the usermeta table to the cs_customermeta table.
 *
 * @since  2.6
 * @return void
 */
function cs_stripe_customer_id_migration() {
	global $wpdb;

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		wp_die( __( 'You do not have permission to do shop upgrades', 'csx' ), __( 'Error', 'csx' ), array( 'response' => 403 ) );
	}

	ignore_user_abort( true );

	$step   = isset( $_GET['step'] )   ? absint( $_GET['step'] )   : 1;
	$number = isset( $_GET['number'] ) ? absint( $_GET['number'] ) : 10;
	$offset = $step == 1 ? 0 : ( $step - 1 ) * $number;

	$total = isset( $_GET['total'] ) ? absint( $_GET['total'] ) : false;
	if ( empty( $total ) || $total <= 1 ) {
		$total_sql = "SELECT COUNT(user_id) as total_users FROM $wpdb->usermeta WHERE meta_key IN ( '_cs_stripe_customer_id', '_cs_stripe_customer_id_test' )";
		$results   = $wpdb->get_row( $total_sql, 0 );
		$total     = $results->total_users;
	}

	$stripe_user_meta = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT * FROM $wpdb->usermeta WHERE meta_key IN ( '_cs_stripe_customer_id', '_cs_stripe_customer_id_test' ) ORDER BY umeta_id ASC LIMIT %d,%d;",
			$offset,
			$number
		)
	);

	if ( $stripe_user_meta ) {

		foreach ( $stripe_user_meta as $stripe_user ) {

			$user  = get_userdata( $stripe_user->user_id );
			$email = $user->user_email;

			$customer = new CS_Customer( $email );

			// If we don't have a customer on this site, just move along.
			if ( ! $customer->id > 0 ) {
				continue;
			}

			$stripe_customer_id = $stripe_user->meta_value;

			// We should try and use a recurring ID if one exists for this user
			if ( class_exists( 'CS_Recurring_Subscriber' ) ) {
				$subscriber         = new CS_Recurring_Subscriber( $customer->id );
				$stripe_customer_id = $subscriber->get_recurring_customer_id( 'stripe' );
			}

			$customer->update_meta( $stripe_user->meta_key, $stripe_customer_id );

		}

		$step ++;
		$redirect = add_query_arg( array(
			'page'        => 'cs-upgrades',
			'cs-upgrade' => 'stripe_customer_id_migration',
			'step'        => $step,
			'number'      => $number,
			'total'       => $total
		), admin_url( 'index.php' ) );

		wp_redirect( $redirect );
		exit;

	} else {

		update_option( 'csx_stripe_version', preg_replace( '/[^0-9.].*/', '', CS_STRIPE_VERSION ) );
		cs_set_upgrade_complete( 'stripe_customer_id_migration' );
		delete_option( 'cs_doing_upgrade' );

		wp_redirect( admin_url() );
		exit;

	}

}
add_action( 'cs_stripe_customer_id_migration', 'cs_stripe_customer_id_migration' );