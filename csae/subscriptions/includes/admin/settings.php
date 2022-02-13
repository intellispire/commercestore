<?php

/**
* Register our settings section
*
* @since  2.4
* @return array
*/
function cs_recurring_settings_section( $sections ) {

	$sections['recurring'] = __( 'Recurring Payments', 'cs-recurring' );

	return $sections;
}
$settings_tab = version_compare( CS_VERSION, '2.11.3', '>=' ) ? 'gateways' : 'extensions';
add_filter( "cs_settings_sections_{$settings_tab}", 'cs_recurring_settings_section' );
add_filter( 'cs_settings_sections_emails', 'cs_recurring_settings_section' );

/**
* Register our settings
*
* @since  1.0
* @return array
*/
function cs_recurring_settings( $settings ) {

	$recurring_settings = array(
		'recurring' => array(
			array(
				'id'    => 'recurring_download_limit',
				'name'  => __( 'Limit File Downloads', 'cs-recurring' ),
				'desc'  => __( 'Check this if you\'d like to require users have an active subscription in order to download files associated with a recurring product.', 'cs-recurring' ),
				'type'  => 'checkbox'
			),
			array(
				'id'    => 'recurring_treat_completed_subs_as_active',
				'name'  => __( 'Allow "Completed" subscriptions to download their files', 'cs-recurring' ),
				'desc'  => __( 'When "Limit File Downloads" is enabled, would you like users with "Completed" subscriptions to be able to download their files, despite their subscription technically not being "Active"?', 'cs-recurring' ),
				'type'  => 'checkbox'
			),
			array(
				'id'   => 'recurring_show_terms_notice',
				'name' => __( 'Display Subscription Terms', 'cs-recurring' ),
				'desc' => __( 'When selected, the billing times and frequency will be shown below the purchase link.', 'cs-recurring' ),
				'type' => 'checkbox',
			),
			array(
				'id'   => 'recurring_show_signup_fee_notice',
				'name' => __( 'Display Signup Fee', 'cs-recurring' ),
				'desc' => __( 'When selected, signup fee associated with a subscription will be shown below the purchase link.', 'cs-recurring' ),
				'type' => 'checkbox'
			),
			array(
				'id'   => 'recurring_signup_fee_label',
				'name' => __( 'Signup Fee Label', 'cs-recurring' ),
				'desc' => __( 'The label used for signup fees, if any. This is shown on checkout and on individual purchase options if "Display Signup Fee" above is checked.', 'cs-recurring' ),
				'type' => 'text',
				'std'  => __( 'Signup Fee', 'cs-recurring' )
			),
			array(
				'id'   => 'recurring_cancel_button_text',
				'name' => __( 'Cancel Subscription Text', 'cs-recurring' ),
				'desc' => __( 'The label used for the Cancel action. This text is shown to the customer when managing their subscriptions.', 'cs-recurring' ),
				'type' => 'text',
				'std'  => __( 'Cancel', 'cs-recurring' )
			),
			array(
				'id'    => 'recurring_one_time_discounts',
				'name'  => __( 'One Time Discounts', 'cs-recurring' ),
				'desc'  => __( 'Check this if you\'d like discount codes to apply only to the initial subscription payment and not all payments. <strong>Note</strong>: one-time discount codes will not apply to free trials.', 'cs-recurring' ),
				'type'  => 'checkbox',
				'tooltip_title' => __( 'One Time Discounts', 'cs-recurring' ),
				'tooltip_desc'  => __( 'When one time discounts are enabled, only the first payment in a subscription will be discounted when a discount code is redeemed on checkout. Free trials and one time discounts, however, cannot be combined. If a customer purchases a free trial, discount codes will always apply to <em>all</em> payments made for the subscription.', 'commercestore' ),

			),
			array(
				'id'    => 'recurring_one_time_trials',
				'name'  => __( 'One Time Trials', 'cs-recurring' ),
				'desc'  => __( 'Check this if you\'d like customers to be prevented from purchasing a free trial multiple times.', 'cs-recurring' ),
				'type'  => 'checkbox'
			),
		)
	);

	return array_merge( $settings, $recurring_settings );
}
add_filter( "cs_settings_{$settings_tab}", 'cs_recurring_settings' );

/**
 * Adds the Recurring email settings to the Recurring section on the Emails tab.
 *
 * @since 2.11.4
 * @param array $settings
 * @return array
 */
function cs_recurring_email_settings( $settings ) {
	$recurring_settings = array(
		'recurring' => array(
			array(
				'id'    => 'enable_payment_received_email',
				'name'  => __( 'Payment Received Email', 'cs-recurring' ),
				'desc'  => __( 'Check this if you\'d like customers to be sent an email notice each time a renewal payment is processed.', 'cs-recurring' ),
				'type'  => 'checkbox'
			),
			array(
				'id'    => 'payment_received_subject',
				'name'  => __( 'Renewal Payment Received Subject', 'cs-recurring' ),
				'desc'  => __( 'Enter the subject line of the email sent when a renewal payment is processed.', 'cs-recurring' ),
				'type'  => 'text',
				'std'   => __( 'Renewal Payment Received', 'cs-recurring' )
			),
			array(
				'id'    => 'payment_received_message',
				'name'  => __( 'Renewal Payment Received Message', 'cs-recurring' ),
				'desc'  => __( 'Enter the body text of the email sent when a renewal payment is processed.', 'cs-recurring' ),
				'type'  => 'rich_editor',
				'std'   => __( "Hello {name}\n\nYour renewal payment in the amount of {amount} for {subscription_name} has been successfully processed.", 'cs-recurring' )
			),

			array(
				'id'    => 'enable_payment_failed_email',
				'name'  => __( 'Payment Failed Email', 'cs-recurring' ),
				'desc'  => __( 'Check this if you\'d like customers to be sent an email notice each time a payment fails to be processed.', 'cs-recurring' ),
				'type'  => 'checkbox'
			),
			array(
				'id'    => 'payment_failed_subject',
				'name'  => __( 'Renewal Payment Failed Subject', 'cs-recurring' ),
				'desc'  => __( 'Enter the subject line of the email sent when a renewal payment fails to be processed.', 'cs-recurring' ),
				'type'  => 'text',
				'std'   => __( 'Renewal Payment Failed', 'cs-recurring' )
			),
			array(
				'id'    => 'payment_failed_message',
				'name'  => __( 'Renewal Payment Failed Message', 'cs-recurring' ),
				'desc'  => __( 'Enter the body text of the email sent when a renewal payment fails to be processed.', 'cs-recurring' ),
				'type'  => 'rich_editor',
				'std'   => __( "Hello {name}\n\nYour renewal payment in the amount of {amount} for {subscription_name} has been failed to be processed.", 'cs-recurring' )
			),
			array(
				'id'    => 'enable_subscription_cancelled_email',
				'name'  => __( 'Subscription Cancelled Email', 'cs-recurring' ),
				'desc'  => __( 'Check this if you\'d like customers to be sent an email notice when they cancel a subscription.', 'cs-recurring' ),
				'type'  => 'checkbox'
			),
			array(
				'id'    => 'subscription_cancelled_subject',
				'name'  => __( 'Subscription Cancelled Subject', 'cs-recurring' ),
				'desc'  => __( 'Enter the subject line of the email sent when a subscription is cancelled.', 'cs-recurring' ),
				'type'  => 'text',
				'std'   => __( 'Subscription Cancelled', 'cs-recurring' )
			),
			array(
				'id'    => 'subscription_cancelled_message',
				'name'  => __( 'Subscription Cancelled Message', 'cs-recurring' ),
				'desc'  => __( 'Enter the body text of the email sent when a subscription is cancelled.', 'cs-recurring' ),
				'type'  => 'rich_editor',
				'std'   => __( "Hello {name}\n\nYour subscription for {subscription_name} has been successfully cancelled.", 'cs-recurring' )
			),
			array(
				'id'   => 'recurring_send_renewal_reminders',
				'name' => __( 'Send Renewal Reminders', 'cs-recurring' ),
				'desc' => __( 'Check this box if you want customers to receive a reminder when their subscription is about to renew.', 'cs-recurring' ),
				'type' => 'checkbox'
			),
			array(
				'id'   => 'recurring_renewal_reminders',
				'name' => __( 'Subscription Renewal Reminders', 'cs-recurring' ),
				'desc' => __( 'Configure the subscription renewal notice emails', 'cs-recurring' ),
				'type' => 'hook'
			),
			array(
				'id'   => 'recurring_send_expiration_reminders',
				'name' => __( 'Send Expiration Reminders', 'cs-recurring' ),
				'desc' => __( 'Check this box if you want customers to receive a reminder when their subscription is about to expire or complete.', 'cs-recurring' ),
				'type' => 'checkbox'
			),
			array(
				'id'   => 'recurring_expiration_reminders',
				'name' => __( 'Subscription Expiration Reminders', 'cs-recurring' ),
				'desc' => __( 'Configure the subscription expiration notice emails', 'cs-recurring' ),
				'type' => 'hook'
			),
			array(
				'id'    => 'enable_subscription_cancelled_admin_email',
				'name'  => __( 'Subscription Cancelled Email for Admins', 'cs-recurring' ),
				'desc'  => __( 'Check this if you\'d like admins to be sent an email notice when a customer cancels a subscription.', 'cs-recurring' ),
				'type'  => 'checkbox'
			),
			array(
				'id'    => 'subscription_cancelled_admin_subject',
				'name'  => __( 'Subscription Cancelled Subject for Admins', 'cs-recurring' ),
				'desc'  => __( 'Enter the subject line of the email sent to admins when a subscription is cancelled.', 'cs-recurring' ),
				'type'  => 'text',
				'std'   => __( 'Subscription {subscription_id} Cancelled', 'cs-recurring' )
			),
			array(
				'id'    => 'subscription_cancelled_admin_message',
				'name'  => __( 'Subscription Cancelled Message for Admins', 'cs-recurring' ),
				'desc'  => __( 'Enter the body text of the email sent to admins when a subscription is cancelled.', 'cs-recurring' ),
				'type'  => 'rich_editor',
				'std'   => __( "Subscription ID: {subscription_id}\n\nCustomer: {name}\n\nSubscription: {subscription_name}\n\nView Subscription: {subscription_link}", 'cs-recurring' )
			),
		),
	);

	return array_merge( $settings, $recurring_settings );
}
add_filter( 'cs_settings_emails', 'cs_recurring_email_settings' );

/**
 * Displays the subscription renewal reminders options
 *
 * @since       2.4
 *
 * @param        $args array option arguments
 *
 * @return      void
 */
function cs_recurring_renewal_reminders_settings( $args ) {

	$reminders = new CS_Recurring_Reminders();
	$notices  = $reminders->get_notices( 'renewal' );
	ob_start(); ?>
	<table id="cs_recurring_renewal_reminders" class="wp-list-table widefat fixed posts">
		<thead>
		<tr>
			<th scope="col" style="padding-left: 10px" class="cs-recurring-reminder-subject-col"><?php _e( 'Subject', 'cs-recurring' ); ?></th>
			<th scope="col" class="cs-recurring-reminder-period-col"><?php _e( 'Send Period', 'cs-recurring' ); ?></th>
			<th scope="col" class="cs-recurring-reminder-action-col"><?php _e( 'Actions', 'cs-recurring' ); ?></th>
		</tr>
		</thead>
		<?php if ( ! empty( $notices ) ) : $i = 1; ?>
			<?php foreach ( $notices as $key => $notice ) : $notice = $reminders->get_notice( $key ); ?>
				<tr <?php if ( $i % 2 == 0 ) {
					echo 'class="alternate"';
				} ?>>
					<td><?php echo esc_html( $notice['subject'] ); ?></td>
					<td><?php echo esc_html( $reminders->get_notice_period_label( $key ) ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=cs-subscription-reminder-notice&cs_recurring_action=edit-recurring-reminder-notice&notice=' . $key ) ); ?>" class="cs-recurring-edit-reminder-notice" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Edit', 'cs-recurring' ); ?></a>&nbsp;|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=download&page=cs-subscription-reminder-notice&cs_action=recurring_send_test_reminder_notice&notice-id=' . $key ) ) ); ?>" class="cs-recurring-send-test-reminder-notice"><?php _e( 'Send Test Email', 'cs-recurring' ); ?></a>&nbsp;|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=download&page=cs-subscription-reminder-notice&cs_action=recurring_delete_reminder_notice&notice-id=' . $key ) ) ); ?>" class="cs-delete"><?php _e( 'Delete', 'cs-recurring' ); ?></a>
					</td>
				</tr>
				<?php $i ++; endforeach; ?>
		<?php endif; ?>
	</table>
	<p>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=cs-subscription-reminder-notice&cs_recurring_action=add-recurring-reminder-notice&cs_recurring_reminder_type=renewal' ) ); ?>" class="button-secondary" id="cs_recurring_add_renewal_notice"><?php _e( 'Add Renewal Reminder', 'cs-recurring' ); ?></a>
	</p>
	<?php
	echo ob_get_clean();
}
add_action( 'cs_recurring_renewal_reminders', 'cs_recurring_renewal_reminders_settings' );

/**
 * Displays the subscription expiration reminders options
 *
 * @since       2.4
 *
 * @param        $args array option arguments
 *
 * @return      void
 */
function cs_recurring_expiration_reminders_settings( $args ) {

	$reminders = new CS_Recurring_Reminders();
	$notices  = $reminders->get_notices( 'expiration' );
	ob_start(); ?>
	<table id="cs_recurring_expiration_reminders" class="wp-list-table widefat fixed posts">
		<thead>
		<tr>
			<th scope="col" style="padding-left: 10px" class="cs-recurring-reminder-subject-col"><?php _e( 'Subject', 'cs-recurring' ); ?></th>
			<th scope="col" class="cs-recurring-reminder-period-col"><?php _e( 'Send Period', 'cs-recurring' ); ?></th>
			<th scope="col" class="cs-recurring-reminder-action-col"><?php _e( 'Actions', 'cs-recurring' ); ?></th>
		</tr>
		</thead>
		<?php if ( ! empty( $notices ) ) : $i = 1; ?>
			<?php foreach ( $notices as $key => $notice ) : $notice = $reminders->get_notice( $key ); ?>
				<tr <?php if ( $i % 2 == 0 ) {
					echo 'class="alternate"';
				} ?>>
					<td><?php echo esc_html( $notice['subject'] ); ?></td>
					<td><?php echo esc_html( $reminders->get_notice_period_label( $key ) ); ?></td>
					<td>
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=cs-subscription-reminder-notice&cs_recurring_action=edit-recurring-reminder-notice&notice=' . $key ) ); ?>" class="cs-recurring-edit-reminder-notice" data-key="<?php echo esc_attr( $key ); ?>"><?php _e( 'Edit', 'cs-recurring' ); ?></a>&nbsp;|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=download&page=cs-subscription-reminder-notice&cs_action=recurring_send_test_reminder_notice&notice-id=' . $key ) ) ); ?>" class="cs-recurring-send-test-reminder-notice"><?php _e( 'Send Test Email', 'cs-recurring' ); ?></a>&nbsp;|
						<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=download&page=cs-subscription-reminder-notice&cs_action=recurring_delete_reminder_notice&notice-id=' . $key ) ) ); ?>" class="cs-delete"><?php _e( 'Delete', 'cs-recurring' ); ?></a>
					</td>
				</tr>
				<?php $i ++; endforeach; ?>
		<?php endif; ?>
	</table>
	<p>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=cs-subscription-reminder-notice&cs_recurring_action=add-recurring-reminder-notice&cs_recurring_reminder_type=expiration' ) ); ?>" class="button-secondary" id="cs_recurring_add_expiration_notice"><?php _e( 'Add Expiration Reminder', 'cs-recurring' ); ?></a>
	</p>
	<?php
	echo ob_get_clean();
}
add_action( 'cs_recurring_expiration_reminders', 'cs_recurring_expiration_reminders_settings' );

/**
 * Add menu page for reminder emails
 * *
 * @access      private
 * @since       2.4
 * @return      void
 */
function cs_recurring_add_notices_page() {

	global $cs_recurring_reminders_page;

	$cs_recurring_reminders_page = add_submenu_page(
		'edit.php?post_type=download',
		__( 'Subscription Reminder', 'cs-recurring' ),
		__( 'Subscription Reminder', 'cs-recurring' ),
		'manage_shop_settings',
		'cs-subscription-reminder-notice',
		'cs_recurring_subscription_reminder_notice_edit'
	);

	add_action( 'admin_head', 'cs_recurring_hide_reminder_notice_page' );
}
add_action( 'admin_menu', 'cs_recurring_add_notices_page', 10 );

/**
 * Removes the Subscription Reminder Notice menu link
 *
 * @since       2.4
 * @return      void
 */
function cs_recurring_hide_reminder_notice_page() {
	remove_submenu_page( 'edit.php?post_type=download', 'cs-subscription-reminder-notice' );
}

/**
 * Renders the add / edit subscription reminder notice screen
 *
 * @since 2.4
 *
 * @param array $input The value inputted in the field
 *
 * @return string $input Sanitizied value
 */
function cs_recurring_subscription_reminder_notice_edit() {

	$action = isset( $_GET['cs_recurring_action'] ) ? sanitize_text_field( $_GET['cs_recurring_action'] ) : 'add-recurring-reminder-notice';

	if ( 'edit-recurring-reminder-notice' === $action ) {
		include CS_Recurring::$plugin_path . '/includes/admin/edit-reminder-notice.php';
	} else {
		include CS_Recurring::$plugin_path . '/includes/admin/add-reminder-notice.php';
	}

}

/**
 * Processes the creation of a new reminder notice
 *
 * @since 2.4
 *
 * @param array $data The post data
 *
 * @return void
 */
function cs_recurring_process_add_reminder_notice( $data ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		wp_die( __( 'You do not have permission to add reminder notices', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 401 ) );
	}

	if ( ! wp_verify_nonce( $data['cs-recurring-reminder-notice-nonce'], 'cs_recurring_reminder_nonce' ) ) {
		wp_die( __( 'Nonce verification failed', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 401 ) );
	}

	$subject = isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : __( 'Your Subscription is About to Renew', 'cs-recurring' );
	$period  = isset( $data['period'] ) ? sanitize_text_field( $data['period'] ) : '+1month';
	$message = isset( $data['message'] ) ? wp_kses( stripslashes( $data['message'] ), wp_kses_allowed_html( 'post' ) ) : false;
	$type    = isset( $data['type'] ) ? sanitize_text_field( $data['type'] ) : 'renewal';

	if ( empty( $message ) ) {
		$message = 'Hello {name},

Your subscription for {subscription_name} will renew on {expiration}.';
	}

	$reminders  = new CS_Recurring_Reminders();
	$notices    = $reminders->get_notices();
	$notices[]  = array(
		'subject'     => $subject,
		'message'     => $message,
		'send_period' => $period,
		'type'		  => $type
	);

	update_option( 'cs_recurring_reminder_notices', $notices );

	wp_safe_redirect( cs_recurring_get_email_settings_url() );
	exit;

}
add_action( 'cs_recurring_add_reminder_notice', 'cs_recurring_process_add_reminder_notice' );

/**
 * Processes the update of an existing reminder notice
 *
 * @since 2.4
 *
 * @param array $data The post data
 *
 * @return void
 */
function cs_recurring_process_update_reminder_notice( $data ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		wp_die( __( 'You do not have permission to add reminder notices', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 401 ) );
	}

	if ( ! wp_verify_nonce( $data['cs-recurring-reminder-notice-nonce'], 'cs_recurring_reminder_nonce' ) ) {
		wp_die( __( 'Nonce verification failed', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 401 ) );
	}

	if ( ! isset( $data['notice-id'] ) ) {
		wp_die( __( 'No reminder notice ID was provided', 'cs-recurring' ) );
	}

	$subject = isset( $data['subject'] ) ? sanitize_text_field( $data['subject'] ) : __( 'Your Subscription is About to Renew', 'cs-recurring' );
	$period  = isset( $data['period'] ) ? sanitize_text_field( $data['period'] ) : '+1month';
	$message = isset( $data['message'] ) ? wp_kses( stripslashes( $data['message'] ), wp_kses_allowed_html( 'post' ) ) : false;
	$type    = isset( $data['type'] ) ? sanitize_text_field( $data['type'] ) : 'renewal';

	if ( empty( $message ) ) {
		$message = 'Hello {name},

Your subscription for {subscription_name} will renew on {expiration}.';
	}

	$reminders                               = new CS_Recurring_Reminders();
	$notices                                 = $reminders->get_notices();
	$notices[ absint( $data['notice-id'] ) ] = array(
		'subject'     => $subject,
		'message'     => $message,
		'send_period' => $period,
		'type'		  => $type
	);

	update_option( 'cs_recurring_reminder_notices', $notices );

	wp_safe_redirect( cs_recurring_get_email_settings_url() );
	exit;

}
add_action( 'cs_recurring_edit_reminder_notice', 'cs_recurring_process_update_reminder_notice' );

/**
 * Processes the deletion of an existing reminder notice
 *
 * @since 2.4
 *
 * @param array $data The post data
 *
 * @return void
 */
function cs_recurring_process_delete_reminder_notice( $data ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		wp_die( __( 'You do not have permission to delete reminder notices', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 401 ) );
	}

	if ( ! wp_verify_nonce( $data['_wpnonce'] ) ) {
		wp_die( __( 'Nonce verification failed', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 401 ) );
	}

	if ( empty( $data['notice-id'] ) && 0 !== (int) $data['notice-id'] ) {
		wp_die( __( 'No reminder notice ID was provided', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 409 ) );
	}

	$reminders = new CS_Recurring_Reminders();
	$notices  = $reminders->get_notices();
	unset( $notices[ absint( $data['notice-id'] ) ] );

	update_option( 'cs_recurring_reminder_notices', $notices );

	wp_safe_redirect( cs_recurring_get_email_settings_url() );
	exit;

}
add_action( 'cs_recurring_delete_reminder_notice', 'cs_recurring_process_delete_reminder_notice' );

/**
 * Sends a test email for a reminder notice
 *
 * @since 2.4
 *
 * @param array $data The post data
 *
 * @return void
 */
function cs_recurring_process_send_test_reminder_notice( $data ) {

	if ( ! is_admin() ) {
		return;
	}

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		wp_die( __( 'You do not have permission to send test email reminder notices', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 401 ) );
	}

	if ( ! wp_verify_nonce( $data['_wpnonce'] ) ) {
		wp_die( __( 'Nonce verification failed', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 401 ) );
	}

	if ( empty( $data['notice-id'] ) && 0 !== (int) $data['notice-id'] ) {
		wp_die( __( 'No reminder notice ID was provided', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 409 ) );
	}

	$reminders = new CS_Recurring_Reminders();
	$notices  = $reminders->get_notices();
	$reminders->send_test_notice( absint( $data['notice-id'] ) );

	wp_safe_redirect( cs_recurring_get_email_settings_url() );
	exit;
}
add_action( 'cs_recurring_send_test_reminder_notice', 'cs_recurring_process_send_test_reminder_notice' );

/**
 * Add additional text to Item Quantities setting to explain why it is sometimes disabled
 *
 * @since 2.5.2
 *
 * @param array $settings
 *
 * @return array
 */
function cs_recurring_item_quantities_description( $settings ) {
	$settings['main']['item_quantities']['desc'] .= ' <strong>' . __( 'Note: Item Quantities will be disabled for all products in the cart if the cart contains a recurring product.', 'cs-recurring' ) . '</strong>';
	return $settings;
}
add_filter( 'cs_settings_misc', 'cs_recurring_item_quantities_description', 10 );

/**
 * Add additional text to Guest Checkout setting to explain why it is disabled
 *
 * @since 2.5.2
 *
 * @param array $settings
 *
 * @return array
 */
function cs_recurring_guest_checkout_description( $settings ) {
	if ( ! empty( $settings['checkout']['logged_in_only']['desc'] ) ) {
		$settings['checkout']['logged_in_only']['desc'] .= '<br /><strong>' . __( 'Guest checkout is not permitted when purchasing subscriptions.', 'cs-recurring' ) . '</strong>';
	}

	return $settings;
}
$settings_tab = version_compare( CS_VERSION, '2.11.3', '>=' ) ? 'gateways' : 'misc';
add_filter( "cs_settings_{$settings_tab}", 'cs_recurring_guest_checkout_description', 10 );

/**
 * Gets the URL for Recurring's email settings.
 *
 * @since 2.11.4
 * @return string
 */
function cs_recurring_get_email_settings_url() {
	return add_query_arg(
		array(
			'post_type' => 'download',
			'page'      => 'cs-settings',
			'tab'       => 'emails',
			'section'   => 'recurring',
		),
		admin_url( 'edit.php' )
	);
}
