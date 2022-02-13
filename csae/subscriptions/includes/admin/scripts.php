<?php

/**
 * Load the admin javascript
 *
 * @access      public
 * @since       1.0
 * @return      void
 */
function cs_recurring_admin_scripts( $hook ) {
	global $post, $cs_recurring;

	if ( ! is_object( $post ) && ! in_array( $hook, array( 'download_page_cs-subscriptions', 'download_page_cs-payment-history' ), true ) ) {
		return;
	}

	if ( is_object( $post ) && 'download' != $post->post_type ) {
		return;
	}

	$pages = array( 'post.php', 'post-new.php', 'download_page_cs-subscriptions', 'download_page_cs-payment-history' );

	if ( ! in_array( $hook, $pages ) ) {
		return;
	}

	wp_register_script( 'cs-admin-recurring', CS_Recurring::$plugin_dir . '/assets/js/cs-admin-recurring.js', array('jquery'));
	wp_enqueue_script( 'cs-admin-recurring' );
	wp_enqueue_style( 'cs-admin-recurring', CS_Recurring::$plugin_dir . '/assets/css/admin.css', array(), CS_RECURRING_VERSION );

	$ajax_vars = array(
		'singular'            => _x( 'time', 'Referring to billing period', 'cs-recurring' ),
		'plural'              => _x( 'times', 'Referring to billing period', 'cs-recurring' ),
		'enabled_gateways'    => cs_get_enabled_payment_gateways(),
		'invalid_time'        => array(
			'paypal'          => __( 'PayPal Standard requires recurring times to be set to 0 for indefinite subscriptions or a minimum value of 2 and a maximum value of 52 for limited subscriptions.', 'cs-recurring' ),
		),
		'delete_subscription' => __( 'Are you sure you want to delete this subscription?', 'cs-recurring' ),
		'action_edit'         => __( 'Edit', 'cs-recurring' ),
		'action_cancel'       => __( 'Cancel', 'cs-recurring' ),
	);

	wp_localize_script( 'cs-admin-recurring', 'CS_Recurring_Vars', $ajax_vars );

	wp_enqueue_script( 'dashicons' ); // Just to be sure
}

add_action( 'admin_enqueue_scripts', 'cs_recurring_admin_scripts' );
