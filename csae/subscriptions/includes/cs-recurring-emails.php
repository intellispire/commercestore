<?php

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * The Recurring Emails Class
 *
 * @since  2.4
 */
class CS_Recurring_Emails {

	public $subscription;

	public function __construct() {
		$this->init();
	}

	public function init() {

		if ( cs_get_option( 'enable_payment_received_email' ) ) {
			add_action( 'cs_subscription_post_renew', array( $this, 'send_payment_received' ), 10, 4 );
		}

		if ( cs_get_option( 'enable_payment_failed_email' ) ) {
			add_action( 'cs_recurring_payment_failed', array( $this, 'send_payment_failed' ), 10 );
		}

		if ( cs_get_option( 'enable_subscription_cancelled_email' ) ) {
			add_action( 'cs_subscription_cancelled', array( $this, 'send_subscription_cancelled' ), 10, 3 );
		}

		if ( cs_get_option( 'enable_subscription_cancelled_admin_email' ) ) {
			add_action( 'cs_subscription_cancelled', array( $this, 'send_subscription_cancelled_admin' ), 10, 3 );
		}
	}

	/**
	 * Sends an email when a subscription payment is received.
	 *
	 * @param int              $subscription_id The subscription ID.
	 * @param int              $expiration      The expiration date.
	 * @param CS_Subscription $subscription    The subscription object.
	 * @param int              $payment_id      The renewal payment ID.
	 * @return void
	 */
	public function send_payment_received( $subscription_id, $expiration, CS_Subscription $subscription, $payment_id = 0 ) {

		// Since it's possible to renew a subscription without a payment, we should not send an email if none is specified.
		if ( empty( $payment_id ) ) {
			return;
		}

		$this->subscription = new CS_Subscription( $subscription_id );
		$payment            = cs_get_payment( $payment_id );

		$email_to = $this->subscription->customer->email;
		$subject  = apply_filters( 'cs_recurring_payment_received_subject', cs_get_option( 'payment_received_subject' ) );
		$subject  = $this->payment_received_template_tags( $subject, $payment->total );
		$subject  = cs_do_email_tags( $subject, $payment_id );
		$message  = apply_filters( 'cs_recurring_payment_received_message', cs_get_option( 'payment_received_message' ) );
		$message  = $this->payment_received_template_tags( $message, $payment->total );
		$message  = cs_do_email_tags( $message, $payment_id );

		CS()->emails->send( $email_to, $subject, $message );
	}

	public function send_payment_failed( CS_Subscription $subscription ) {

		$this->subscription = new CS_Subscription( $subscription->id );

		$email_to = $subscription->customer->email;
		$subject  = apply_filters( 'cs_recurring_payment_failed_subject', cs_get_option( 'payment_failed_subject' ) );
		$subject  = $this->payment_received_template_tags( $subject, $subscription->recurring_amount );
		$message  = apply_filters( 'cs_recurring_payment_failed_message', cs_get_option( 'payment_failed_message' ) );
		$message  = $this->payment_received_template_tags( $message, $subscription->recurring_amount );

		CS()->emails->send( $email_to, $subject, $message );

	}

	public function send_subscription_cancelled( $subscription_id, CS_Subscription $subscription ) {

		$this->subscription = new CS_Subscription( $subscription_id );

		$email_to = $subscription->customer->email;

		$subject  = apply_filters( 'cs_recurring_subscription_cancelled_subject', cs_get_option( 'subscription_cancelled_subject' ) );
		$subject  = $this->filter_reminder_template_tags( $subject, $subscription_id );

		$message  = apply_filters( 'cs_recurring_subscription_cancelled_message', cs_get_option( 'subscription_cancelled_message' ) );
		$message  = $this->filter_reminder_template_tags( $message, $subscription_id );

		CS()->emails->send( $email_to, $subject, $message );

	}

	public function send_subscription_cancelled_admin( $subscription_id, CS_Subscription $subscription ) {

		$this->subscription = new CS_Subscription( $subscription_id );

		$email_to = cs_get_admin_notice_emails();

		$subject  = apply_filters( 'cs_recurring_subscription_cancelled_subject', cs_get_option( 'subscription_cancelled_admin_subject' ) );
		$subject  = $this->filter_reminder_template_tags( $subject, $subscription_id );

		$message  = apply_filters( 'cs_recurring_subscription_cancelled_message', cs_get_option( 'subscription_cancelled_admin_message' ) );
		$message  = $this->filter_reminder_template_tags( $message, $subscription_id );
		$message = str_replace( '{subscription_link}', admin_url( 'edit.php?post_type=download&page=cs-subscriptions&id=' . $subscription_id ), $message );

		CS()->emails->send( $email_to, $subject, $message );

	}

	public function send_reminder( $subscription_id = 0, $notice_id = 0 ) {

		if ( empty( $subscription_id ) ) {
			return;
		}

		$this->subscription = new CS_Subscription( $subscription_id );

		if ( empty( $this->subscription ) ) {
			return;
		}

		$notices = new CS_Recurring_Reminders();
		$send    = apply_filters( 'cs_recurring_send_reminder', true, $subscription_id, $notice_id );

		if ( ! $send ) {
			return;
		}

		$email_to   = $this->subscription->customer->email;
		$notice     = $notices->get_notice( $notice_id );
		$message    = ! empty( $notice['message'] ) ? $notice['message'] : __( "Hello {name},\n\nYour subscription for {subscription_name} will renew or expire on {expiration}.", 'cs-recurring' );
		$message    = $this->filter_reminder_template_tags( $message, $subscription_id );

		$subject    = ! empty( $notice['subject'] ) ? $notice['subject'] : __( 'Your Subscription is About to Renew or Expire', 'cs-recurring' );
		$subject    = $this->filter_reminder_template_tags( $subject, $subscription_id );

		CS()->emails->send( $email_to, $subject, $message );

		$log_id = wp_insert_post(
			array(
				'post_title'   => __( 'LOG - Subscription Reminder Notice Sent', 'cs-recurring' ),
				'post_name'    => 'log-subscription-reminder-notice-' . $subscription_id . '_sent-' . $this->subscription->customer_id . '-' . md5( time() ),
				'post_type'    => 'cs_subscription_log',
				'post_status'  => 'publish',
			 )
		);

		add_post_meta( $log_id, '_cs_recurring_log_customer_id', $this->subscription->customer_id );
		add_post_meta( $log_id, '_cs_recurring_log_subscription_id', $subscription_id );
		add_post_meta( $log_id, '_cs_recurring_reminder_notice_id', (int) $notice_id );

		if ( isset( $notice['type'] ) ) {
			add_post_meta( $log_id, '_cs_recurring_reminder_notice_type', $notice['type'] );
		}

		wp_set_object_terms( $log_id, 'subscription_reminder_notice', 'cs_log_type', false );

		if ( ! empty( $this->subscription->customer->user_id ) ) {

			// Prevents reminder notices from being sent more than once.
			add_user_meta( $this->subscription->customer->user_id, sanitize_key( '_cs_recurring_reminder_sent_' . $subscription_id . '_' . $notice_id . '_' . $this->subscription->get_total_payments() ), time() );

		}

	}

	public function filter_reminder_template_tags( $text = '', $subscription_id = 0 ) {

		$download      = cs_get_download( $this->subscription->product_id );
		$customer_name = $this->subscription->customer->name;
		$expiration    = strtotime( $this->subscription->expiration );

		$text = str_replace( '{name}', $customer_name,  $text );

		// Make sure a valid download object was found before attempting to use its methods.
		if ( $download instanceof CS_Download ) {
			$text = str_replace( '{subscription_name}', $download->get_name(), $text );
		}

		$text = str_replace( '{expiration}', date_i18n( get_option( 'date_format' ), $expiration ), $text );
		$text = str_replace( '{amount}', cs_currency_filter( cs_format_amount( $this->subscription->recurring_amount ) ), $text );
		$text = str_replace( '{subscription_id}', absint( $this->subscription->id ), $text );

		return apply_filters( 'cs_recurring_filter_reminder_template_tags', $text, $subscription_id );
	}

	/**
	 * Replaces template tags in a payment received email.
	 *
	 * @param string $text   The text to be parsed (subject or message).
	 * @param string $amount The payment amount.
	 * @return string
	 */
	public function payment_received_template_tags( $text = '', $amount = '' ) {

		$download      = cs_get_download( $this->subscription->product_id );
		$customer_name = $this->subscription->customer->name;
		$expiration    = strtotime( $this->subscription->expiration );

		$text = str_replace( '{name}', $customer_name, $text );

		// Make sure a valid download object was found before attempting to use its methods.
		if ( $download instanceof CS_Download ) {
			$text = str_replace( '{subscription_name}', $download->get_name(), $text );
		}

		$text = str_replace( '{expiration}', date_i18n( get_option( 'date_format' ), $expiration ), $text );
		$text = str_replace( '{amount}', cs_currency_filter( cs_format_amount( $amount ) ), $text );
		$text = str_replace( '{subscription_id}', absint( $this->subscription->id ), $text );

		return apply_filters( 'cs_recurring_payment_received_template_tags', $text, $amount, $this->subscription->id );
	}


}
