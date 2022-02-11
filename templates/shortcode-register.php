<?php
/**
 * This template is used to display the registration form with [cs_register]
 */
global $cs_register_redirect;

do_action( 'cs_print_errors' ); ?>

<?php if ( ! is_user_logged_in() ) : ?>

<form id="cs_register_form" class="cs_form" action="" method="post">
	<?php do_action( 'cs_register_form_fields_top' ); ?>

	<fieldset>
		<legend><?php _e( 'Register New Account', 'commercestore' ); ?></legend>

		<?php do_action( 'cs_register_form_fields_before' ); ?>

		<p>
			<label for="cs-user-login"><?php _e( 'Username', 'commercestore' ); ?></label>
			<input id="cs-user-login" class="required cs-input" type="text" name="cs_user_login" />
		</p>

		<p>
			<label for="cs-user-email"><?php _e( 'Email', 'commercestore' ); ?></label>
			<input id="cs-user-email" class="required cs-input" type="email" name="cs_user_email" />
		</p>

		<p>
			<label for="cs-user-pass"><?php _e( 'Password', 'commercestore' ); ?></label>
			<input id="cs-user-pass" class="password required cs-input" type="password" name="cs_user_pass" />
		</p>

		<p>
			<label for="cs-user-pass2"><?php _e( 'Confirm Password', 'commercestore' ); ?></label>
			<input id="cs-user-pass2" class="password required cs-input" type="password" name="cs_user_pass2" />
		</p>


		<?php do_action( 'cs_register_form_fields_before_submit' ); ?>

		<p>
			<input type="hidden" name="cs_honeypot" value="" />
			<input type="hidden" name="cs_action" value="user_register" />
			<input type="hidden" name="cs_redirect" value="<?php echo esc_url( $cs_register_redirect ); ?>"/>
			<input class="cs-submit" name="cs_register_submit" type="submit" value="<?php esc_attr_e( 'Register', 'commercestore' ); ?>" />
		</p>

		<?php do_action( 'cs_register_form_fields_after' ); ?>
	</fieldset>

	<?php do_action( 'cs_register_form_fields_bottom' ); ?>
</form>

<?php else : ?>

	<?php do_action( 'cs_register_form_logged_in' ); ?>

<?php endif; ?>
