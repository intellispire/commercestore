<?php
/**
 * This template is used to display the login form with [cs_login]
 */
global $cs_login_redirect;
if ( ! is_user_logged_in() ) :

	// Show any error messages after form submission
	cs_print_errors(); ?>
	<form id="cs_login_form" class="cs_form" action="" method="post">
		<fieldset>
			<legend><?php _e( 'Log into Your Account', 'commercestore' ); ?></legend>
			<?php do_action( 'cs_login_fields_before' ); ?>
			<p class="cs-login-username">
				<label for="cs_user_login"><?php _e( 'Username or Email', 'commercestore' ); ?></label>
				<input name="cs_user_login" id="cs_user_login" class="cs-required cs-input" type="text"/>
			</p>
			<p class="cs-login-password">
				<label for="cs_user_pass"><?php _e( 'Password', 'commercestore' ); ?></label>
				<input name="cs_user_pass" id="cs_user_pass" class="cs-password cs-required cs-input" type="password"/>
			</p>
			<p class="cs-login-remember">
				<label><input name="rememberme" type="checkbox" id="rememberme" value="forever" /> <?php _e( 'Remember Me', 'commercestore' ); ?></label>
			</p>
			<p class="cs-login-submit">
				<input type="hidden" name="cs_redirect" value="<?php echo esc_url( $cs_login_redirect ); ?>"/>
				<input type="hidden" name="cs_login_nonce" value="<?php echo wp_create_nonce( 'cs-login-nonce' ); ?>"/>
				<input type="hidden" name="cs_action" value="user_login"/>
				<input id="cs_login_submit" type="submit" class="cs-submit" value="<?php _e( 'Log In', 'commercestore' ); ?>"/>
			</p>
			<p class="cs-lost-password">
				<a href="<?php echo esc_url( cs_get_lostpassword_url() ); ?>">
					<?php _e( 'Lost Password?', 'commercestore' ); ?>
				</a>
			</p>
			<?php do_action( 'cs_login_fields_after' ); ?>
		</fieldset>
	</form>
<?php else : ?>

	<?php do_action( 'cs_login_form_logged_in' ); ?>

<?php endif; ?>
