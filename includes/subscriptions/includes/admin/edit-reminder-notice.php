<?php
/**
 * Edit Reminder Notice
 *
 * @package     CommerceStore Recurring
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! isset( $_GET['notice'] ) || ! is_numeric( $_GET['notice'] ) ) {
	//wp_die( __( 'Something went wrong.', 'cs-recurring' ), __( 'Error', 'cs-recurring' ) );
}

$notices  = new CS_Recurring_Reminders();
$notice_id = absint( $_GET['notice'] );
$notice    = $notices->get_notice( $notice_id );
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Edit Reminder Notice', 'commercestore' ); ?></h1>
	<a href="<?php echo esc_url( cs_recurring_get_email_settings_url() ); ?>"><?php esc_html_e( 'Return to Email Settings', 'commercestore' ); ?></a>

	<form id="cs-edit-reminder-notice" action="" method="post">
		<table class="form-table">
			<tbody>
			<tr>
				<th scope="row" valign="top">
					<label for="cs-notice-type"><?php _e( 'Notice Type', 'commercestore' ); ?></label>
				</th>
				<td>
					<select name="type" id="cs-notice-type">
						<?php foreach ( $notices->get_notice_types() as $type => $label ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>"<?php selected( $type, $notice['type'] ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>

					<p class="description"><?php _e( 'Is this a renewal notice or an expiration notice?', 'commercestore' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="cs-notice-subject"><?php _e( 'Email Subject', 'commercestore' ); ?></label>
				</th>
				<td>
					<input name="subject" id="cs-notice-subject" class="cs-notice-subject" type="text" value="<?php echo esc_attr( stripslashes( $notice['subject'] ) ); ?>" />

					<p class="description"><?php _e( 'The subject line of the reminder notice email', 'commercestore' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="cs-notice-period"><?php _e( 'Email Period', 'commercestore' ); ?></label>
				</th>
				<td>
					<select name="period" id="cs-notice-period">
						<?php foreach ( $notices->get_notice_periods() as $period => $label ) : ?>
							<option value="<?php echo esc_attr( $period ); ?>"<?php selected( $period, $notice['send_period'] ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>

					<p class="description"><?php _e( 'When should this email be sent?', 'commercestore' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row" valign="top">
					<label for="cs-notice-message"><?php _e( 'Email Message', 'commercestore' ); ?></label>
				</th>
				<td>
					<?php wp_editor( wpautop( wp_kses_post( wptexturize( $notice['message'] ) ) ), 'message', array( 'textarea_name' => 'message' ) ); ?>
					<p class="description"><?php _e( 'The email message to be sent with the reminder notice. The following template tags can be used in the message:', 'commercestore' ); ?></p>
					<ul>
						<li>{name} <?php _e( 'The customer\'s name', 'commercestore' ); ?></li>
						<li>{subscription_name} <?php _e( 'The name of the product the subscription belongs to', 'commercestore' ); ?></li>
						<li>{expiration} <?php _e( 'The expiration date for the subscription', 'commercestore' ); ?></li>
						<li>{amount} <?php _e( 'The recurring amount of the subscription', 'commercestore' ); ?></li>
					</ul>
				</td>
			</tr>

			</tbody>
		</table>
		<p class="submit">
			<input type="hidden" name="cs-action" value="recurring_edit_reminder_notice" />
			<input type="hidden" name="notice-id" value="<?php echo esc_attr( $notice_id ); ?>" />
			<input type="hidden" name="cs-recurring-reminder-notice-nonce" value="<?php echo wp_create_nonce( 'cs_recurring_reminder_nonce' ); ?>" />
			<input type="submit" value="<?php _e( 'Update Reminder Notice', 'commercestore' ); ?>" class="button-primary" />
		</p>
	</form>
</div>
