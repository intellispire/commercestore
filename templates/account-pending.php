<?php if( ! empty( $_GET['cs-verify-request'] ) ) : ?>
<p class="cs-account-pending cs_success">
	<?php _e( 'An email with an activation link has been sent.', 'commercestore' ); ?>
</p>
<?php endif; ?>
<p class="cs-account-pending">
	<?php $url = esc_url( cs_get_user_verification_request_url() ); ?>
	<?php printf( __( 'Your account is pending verification. Please click the link in your email to activate your account. No email? <a href="%s">Click here</a> to send a new activation code.', 'commercestore' ), $url ); ?>
</p>