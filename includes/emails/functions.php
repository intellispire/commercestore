<?php
/**
 * Email Functions
 *
 * @package     CS
 * @subpackage  Emails
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Email the download link(s) and payment confirmation to the buyer in a
 * customizable Purchase Receipt
 *
 * @since 1.0
 * @since 2.8 - Add parameters for CS_Payment and CS_Customer object.
 *
 * @param int          $payment_id   Payment ID
 * @param bool         $admin_notice Whether to send the admin email notification or not (default: true)
 * @param CS_Payment  $payment      Payment object for payment ID.
 * @param CS_Customer $customer     Customer object for associated payment.
 * @return bool Whether the email was sent successfully.
 */
function cs_email_purchase_receipt( $payment_id, $admin_notice = true, $to_email = '', $payment = null, $customer = null ) {
	if ( is_null( $payment ) ) {
		$payment = cs_get_payment( $payment_id );
	}

	$payment_data = $payment->get_meta( '_cs_payment_meta', true );

	$from_name    = cs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name    = apply_filters( 'cs_purchase_from_name', $from_name, $payment_id, $payment_data );

	$from_email   = cs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email   = apply_filters( 'cs_purchase_from_address', $from_email, $payment_id, $payment_data );

	if ( empty( $to_email ) ) {
		$to_email = $payment->email;
	}

	$subject      = cs_get_option( 'purchase_subject', __( 'Purchase Receipt', 'commercestore' ) );
	$subject      = apply_filters( 'cs_purchase_subject', wp_strip_all_tags( $subject ), $payment_id );
	$subject      = wp_specialchars_decode( cs_do_email_tags( $subject, $payment_id ) );

	$heading      = cs_get_option( 'purchase_heading', __( 'Purchase Receipt', 'commercestore' ) );
	$heading      = apply_filters( 'cs_purchase_heading', $heading, $payment_id, $payment_data );
	$heading      = cs_do_email_tags( $heading, $payment_id );

	$attachments  = apply_filters( 'cs_receipt_attachments', array(), $payment_id, $payment_data );

	$message      = cs_do_email_tags( cs_get_email_body_content( $payment_id, $payment_data ), $payment_id );

	$emails = CS()->emails;

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', $heading );

	$headers = apply_filters( 'cs_receipt_headers', $emails->get_headers(), $payment_id, $payment_data );
	$emails->__set( 'headers', $headers );

	$sent = $emails->send( $to_email, $subject, $message, $attachments );

	if ( $admin_notice && ! cs_admin_notices_disabled( $payment_id ) ) {
		do_action( 'cs_admin_sale_notice', $payment_id, $payment_data );
	}

	return $sent;
}

/**
 * Email the download link(s) and payment confirmation to the admin accounts for testing.
 *
 * @since 1.5
 * @return void
 */
function cs_email_test_purchase_receipt() {

	$from_name   = cs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name   = apply_filters( 'cs_purchase_from_name', $from_name, 0, array() );

	$from_email  = cs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email  = apply_filters( 'cs_test_purchase_from_address', $from_email, 0, array() );

	$subject     = cs_get_option( 'purchase_subject', __( 'Purchase Receipt', 'commercestore' ) );
	$subject     = apply_filters( 'cs_purchase_subject', wp_strip_all_tags( $subject ), 0 );
	$subject     = wp_specialchars_decode( cs_do_email_tags( $subject, 0 ) );

	$heading     = cs_get_option( 'purchase_heading', __( 'Purchase Receipt', 'commercestore' ) );
	$heading     = cs_email_preview_template_tags( apply_filters( 'cs_purchase_heading', $heading, 0, array() ) );

	$attachments = apply_filters( 'cs_receipt_attachments', array(), 0, array() );

	$message     = cs_email_preview_template_tags( cs_get_email_body_content( 0, array() ), 0 );

	$emails = CS()->emails;
	$emails->__set( 'from_name' , $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading'   , $heading );

	$headers = apply_filters( 'cs_receipt_headers', $emails->get_headers(), 0, array() );
	$emails->__set( 'headers', $headers );

	$emails->send( cs_get_admin_notice_emails(), $subject, $message, $attachments );

}

/**
 * Sends the Admin Sale Notification Email
 *
 * @since 1.4.2
 * @param int $payment_id Payment ID (default: 0)
 * @param array $payment_data Payment Meta and Data
 * @return void
 */
function cs_admin_email_notice( $payment_id = 0, $payment_data = array() ) {

	$payment_id = absint( $payment_id );

	if( empty( $payment_id ) ) {
		return;
	}

	if( ! cs_get_payment_by( 'id', $payment_id ) ) {
		return;
	}

	$from_name   = cs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name   = apply_filters( 'cs_purchase_from_name', $from_name, $payment_id, $payment_data );

	$from_email  = cs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email  = apply_filters( 'cs_admin_sale_from_address', $from_email, $payment_id, $payment_data );

	$subject     = cs_get_option( 'sale_notification_subject', sprintf( __( 'New download purchase - Order #%1$s', 'commercestore' ), $payment_id ) );
	$subject     = apply_filters( 'cs_admin_sale_notification_subject', wp_strip_all_tags( $subject ), $payment_id );
	$subject     = wp_specialchars_decode( cs_do_email_tags( $subject, $payment_id ) );

	$heading     = cs_get_option( 'sale_notification_heading', __( 'New Sale!', 'commercestore' ) );
	$heading     = apply_filters( 'cs_admin_sale_notification_heading', $heading, $payment_id, $payment_data );
	$heading     = cs_do_email_tags( $heading, $payment_id );

	$attachments = apply_filters( 'cs_admin_sale_notification_attachments', array(), $payment_id, $payment_data );

	$message     = cs_get_sale_notification_body_content( $payment_id, $payment_data );

	$emails = CS()->emails;

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', $heading );

	$headers = apply_filters( 'cs_admin_sale_notification_headers', $emails->get_headers(), $payment_id, $payment_data );
	$emails->__set( 'headers', $headers );

	$emails->send( cs_get_admin_notice_emails(), $subject, $message, $attachments );

}
add_action( 'cs_admin_sale_notice', 'cs_admin_email_notice', 10, 2 );

/**
 * Retrieves the emails for which admin notifications are sent to (these can be
 * changed in the CommerceStore Settings)
 *
 * @since 1.0
 * @return mixed
 */
function cs_get_admin_notice_emails() {
	$emails = cs_get_option( 'admin_notice_emails', false );
	$emails = strlen( trim( $emails ) ) > 0 ? $emails : get_bloginfo( 'admin_email' );
	$emails = array_map( 'trim', explode( "\n", $emails ) );

	return apply_filters( 'cs_admin_notice_emails', $emails );
}

/**
 * Checks whether admin sale notices are disabled
 *
 * @since 1.5.2
 *
 * @param int $payment_id
 * @return mixed
 */
function cs_admin_notices_disabled( $payment_id = 0 ) {
	$ret = cs_get_option( 'disable_admin_notices', false );
	return (bool) apply_filters( 'cs_admin_notices_disabled', $ret, $payment_id );
}

/**
 * Get sale notification email text
 *
 * Returns the stored email text if available, the standard email text if not
 *
 * @since 1.7
 * @author Daniel J Griffiths
 * @return string $message
 */
function cs_get_default_sale_notification_email() {
	$default_email_body = __( 'Hello', 'commercestore' ) . "\n\n" . sprintf( __( 'A %s purchase has been made', 'commercestore' ), cs_get_label_plural() ) . ".\n\n";
	$default_email_body .= sprintf( __( '%s sold:', 'commercestore' ), cs_get_label_plural() ) . "\n\n";
	$default_email_body .= '{download_list}' . "\n\n";
	$default_email_body .= __( 'Purchased by: ', 'commercestore' ) . ' {name}' . "\n";
	$default_email_body .= __( 'Amount: ', 'commercestore' ) . ' {price}' . "\n";
	$default_email_body .= __( 'Payment Method: ', 'commercestore' ) . ' {payment_method}' . "\n\n";
	$default_email_body .= __( 'Thank you', 'commercestore' );

	$message = cs_get_option( 'sale_notification', false );
	$message = ! empty( $message ) ? $message : $default_email_body;

	return $message;
}

/**
 * Get various correctly formatted names used in emails
 *
 * @since 1.9
 * @param $user_info
 * @param $payment   CS_Payment for getting the names
 *
 * @return array $email_names
 */
function cs_get_email_names( $user_info, $payment = false ) {
	$email_names = array();
	$email_names['fullname'] = '';

	if ( $payment instanceof CS_Payment ) {

		$email_names['name']     = $payment->email;
		$email_names['username'] = $payment->email;
		if ( $payment->user_id > 0 ) {

			$user_data               = get_userdata( $payment->user_id );
			$email_names['name']     = $payment->first_name;
			$email_names['fullname'] = trim( $payment->first_name . ' ' . $payment->last_name );
			if ( ! empty( $user_data->user_login ) ) {
				$email_names['username'] = $user_data->user_login;
			}

		} elseif ( ! empty( $payment->first_name ) ) {

			$email_names['name']     = $payment->first_name;
			$email_names['fullname'] = trim( $payment->first_name . ' ' . $payment->last_name );
			$email_names['username'] = $payment->first_name;

		}
	} else {

		if ( is_serialized( $user_info ) ) {

			preg_match( '/[oO]\s*:\s*\d+\s*:\s*"\s*(?!(?i)(stdClass))/', $user_info, $matches );
			if ( ! empty( $matches ) ) {
				return array(
					'name'     => '',
					'fullname' => '',
					'username' => '',
				);
			} else {
				$user_info = maybe_unserialize( $user_info );
			}

		}

		if ( isset( $user_info['id'] ) && $user_info['id'] > 0 && isset( $user_info['first_name'] ) ) {
			$user_data = get_userdata( $user_info['id'] );
			$email_names['name']      = $user_info['first_name'];
			$email_names['fullname']  = $user_info['first_name'] . ' ' . $user_info['last_name'];
			$email_names['username']  = $user_data->user_login;
		} elseif ( isset( $user_info['first_name'] ) ) {
			$email_names['name']     = $user_info['first_name'];
			$email_names['fullname'] = $user_info['first_name'] . ' ' . $user_info['last_name'];
			$email_names['username'] = $user_info['first_name'];
		} else {
			$email_names['name']     = $user_info['email'];
			$email_names['username'] = $user_info['email'];
		}

	}

	return $email_names;
}

/**
 * Handle installation and connection for Recapture via ajax
 *
 * @since 2.10.2
 */
function cs_recapture_remote_install_handler () {

	if ( ! current_user_can( 'manage_shop_settings' ) || ! current_user_can( 'install_plugins' ) ) {
		wp_send_json_error( array(
			'error' => __( 'You do not have permission to do this.', 'commercestore' )
		) );
	}

	include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	include_once ABSPATH . 'wp-admin/includes/file.php';
	include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

	$plugins = get_plugins();

	if( ! array_key_exists( 'recapture-for-cs/recapture.php', $plugins ) ) {

		/*
		* Use the WordPress Plugins API to get the plugin download link.
		*/
		$api = plugins_api( 'plugin_information', array(
			'slug' => 'recapture-for-cs',
		) );

		if ( is_wp_error( $api ) ) {
			wp_send_json_error( array(
				'error' => $api->get_error_message(),
				'debug' => $api
			) );
		}

		/*
		* Use the AJAX Upgrader skin to quietly install the plugin.
		*/
		$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
		$install = $upgrader->install( $api->download_link );
		if ( is_wp_error( $install ) ) {
			wp_send_json_error( array(
				'error' => $install->get_error_message(),
				'debug' => $api
			) );
		}

		$activated = activate_plugin( $upgrader->plugin_info() );

	} else {

		$activated = activate_plugin( 'recapture-for-cs/recapture.php' );

	}

	/*
	* Final check to see if Recapture is available.
	*/
	if ( is_wp_error( $activated ) ) {
		wp_send_json_error( array(
			'error' => __( 'Something went wrong. Recapture for CommerceStore was not installed correctly.', 'commercestore' )
		) );
	}

	wp_send_json_success();
}
add_action( 'wp_ajax_cs_recapture_remote_install', 'cs_recapture_remote_install_handler' );

/**
 * Maybe adds a notice to abandoned payments if Recapture isn't installed.
 *
 * @since 2.10.2
 *
 * @param int $payment_id The ID of the abandoned payment, for which a Recapture notice is being thrown.
 */
function cs_maybe_add_recapture_notice_to_abandoned_payment( $payment_id ) {

	if ( ! class_exists( 'Recapture' )
		&& 'abandoned' === cs_get_payment_status( $payment_id )
		&& ! get_user_meta( get_current_user_id(), '_cs_try_recapture_dismissed', true )
	) {
		?>
		<div class="notice notice-warning recapture-notice">
			<p>
				<?php
				echo wp_kses_post(
					sprintf(
						/* Translators: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s - </a> tag */
						__( '%1$sRecover abandoned purchases like this one.%2$s %3$sTry Recapture for free%4$s.', 'commercestore' ),
						'<strong>',
						'</strong>',
						'<a href="https://recapture.io/abandoned-carts-commercestore" rel="noopener" target="_blank">',
						'</a>'
					)
				);
				?>
			</p>
			<?php
			echo wp_kses_post(
				sprintf(
					/* Translators: %1$s - Opening anchor tag, %2$s - The url to dismiss the ajax notice, %3$s - Complete the opening of the anchor tag, %4$s - Open span tag, %4$s - Close span tag */
					__( '%1$s %2$s %3$s %4$s Dismiss this notice. %5$s', 'commercestore' ),
					'<a href="',
					esc_url(
						add_query_arg(
							array(
								'cs_action' => 'dismiss_notices',
								'cs_notice' => 'try_recapture',
							)
						)
					),
					'" type="button" class="notice-dismiss">',
					'<span class="screen-reader-text">',
					'</span>
					</a>'
				)
			);
			?>
		</div>
		<?php
	}
}
add_action( 'cs_view_order_details_before', 'cs_maybe_add_recapture_notice_to_abandoned_payment' );
