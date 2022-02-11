<?php
/**
 * This template is used to display the profile editor with [cs_profile_editor]
 */
global $current_user;

if ( is_user_logged_in() ):
	$user_id      = get_current_user_id();
	$first_name   = get_user_meta( $user_id, 'first_name', true );
	$last_name    = get_user_meta( $user_id, 'last_name', true );
	$display_name = $current_user->display_name;
	$address      = cs_get_customer_address( $user_id );
	$states       = cs_get_shop_states( $address['country'] );
	$state 		  = $address['state'];

	if ( cs_is_cart_saved() ): ?>
		<?php $restore_url = add_query_arg( array( 'cs_action' => 'restore_cart', 'cs_cart_token' => cs_get_cart_token() ), cs_get_checkout_uri() ); ?>
		<div class="cs_success cs-alert cs-alert-success"><strong><?php _e( 'Saved cart','commercestore' ); ?>:</strong> <?php printf( __( 'You have a saved cart, <a href="%s">click here</a> to restore it.', 'commercestore' ), esc_url( $restore_url ) ); ?></div>
	<?php endif; ?>

	<?php if ( isset( $_GET['updated'] ) && $_GET['updated'] == true && ! cs_get_errors() ): ?>
		<div class="cs_success cs-alert cs-alert-success"><strong><?php _e( 'Success','commercestore' ); ?>:</strong> <?php _e( 'Your profile has been edited successfully.', 'commercestore' ); ?></div>
	<?php endif; ?>

	<?php cs_print_errors(); ?>

	<?php do_action( 'cs_profile_editor_before' ); ?>

	<form id="cs_profile_editor_form" class="cs_form" action="<?php echo cs_get_current_page_url(); ?>" method="post">

		<?php do_action( 'cs_profile_editor_fields_top' ); ?>

		<fieldset id="cs_profile_personal_fieldset">

			<legend id="cs_profile_name_label"><?php _e( 'Change your Name', 'commercestore' ); ?></legend>

			<p id="cs_profile_first_name_wrap">
				<label for="cs_first_name"><?php _e( 'First Name', 'commercestore' ); ?></label>
				<input name="cs_first_name" id="cs_first_name" class="text cs-input" type="text" value="<?php echo esc_attr( $first_name ); ?>" />
			</p>

			<p id="cs_profile_last_name_wrap">
				<label for="cs_last_name"><?php _e( 'Last Name', 'commercestore' ); ?></label>
				<input name="cs_last_name" id="cs_last_name" class="text cs-input" type="text" value="<?php echo esc_attr( $last_name ); ?>" />
			</p>

			<p id="cs_profile_display_name_wrap">
				<label for="cs_display_name"><?php _e( 'Display Name', 'commercestore' ); ?></label>
				<select name="cs_display_name" id="cs_display_name" class="select cs-select">
					<?php if ( ! empty( $current_user->first_name ) ): ?>
					<option <?php selected( $display_name, $current_user->first_name ); ?> value="<?php echo esc_attr( $current_user->first_name ); ?>"><?php echo esc_html( $current_user->first_name ); ?></option>
					<?php endif; ?>
					<option <?php selected( $display_name, $current_user->user_nicename ); ?> value="<?php echo esc_attr( $current_user->user_nicename ); ?>"><?php echo esc_html( $current_user->user_nicename ); ?></option>
					<?php if ( ! empty( $current_user->last_name ) ): ?>
					<option <?php selected( $display_name, $current_user->last_name ); ?> value="<?php echo esc_attr( $current_user->last_name ); ?>"><?php echo esc_html( $current_user->last_name ); ?></option>
					<?php endif; ?>
					<?php if ( ! empty( $current_user->first_name ) && ! empty( $current_user->last_name ) ): ?>
					<option <?php selected( $display_name, $current_user->first_name . ' ' . $current_user->last_name ); ?> value="<?php echo esc_attr( $current_user->first_name . ' ' . $current_user->last_name ); ?>"><?php echo esc_html( $current_user->first_name . ' ' . $current_user->last_name ); ?></option>
					<option <?php selected( $display_name, $current_user->last_name . ' ' . $current_user->first_name ); ?> value="<?php echo esc_attr( $current_user->last_name . ' ' . $current_user->first_name ); ?>"><?php echo esc_html( $current_user->last_name . ' ' . $current_user->first_name ); ?></option>
					<?php endif; ?>
				</select>
				<?php do_action( 'cs_profile_editor_name' ); ?>
			</p>

			<?php do_action( 'cs_profile_editor_after_name' ); ?>

			<p id="cs_profile_primary_email_wrap">
				<label for="cs_email"><?php _e( 'Primary Email Address', 'commercestore' ); ?></label>
				<?php $customer = new CS_Customer( $user_id, true ); ?>
				<?php if ( $customer->id > 0 ) : ?>

					<?php if ( 1 === count( $customer->emails ) ) : ?>
						<input name="cs_email" id="cs_email" class="text cs-input required" type="email" value="<?php echo esc_attr( $customer->email ); ?>" />
					<?php else: ?>
						<?php
							$emails           = array();
							$customer->emails = array_reverse( $customer->emails, true );

							foreach ( $customer->emails as $email ) {
								$emails[ $email ] = $email;
							}

							$email_select_args = array(
								'options'          => $emails,
								'name'             => 'cs_email',
								'id'               => 'cs_email',
								'selected'         => $customer->email,
								'show_option_none' => false,
								'show_option_all'  => false,
							);

							echo CS()->html->select( $email_select_args );
						?>
					<?php endif; ?>
				<?php else: ?>
					<input name="cs_email" id="cs_email" class="text cs-input required" type="email" value="<?php echo esc_attr( $current_user->user_email ); ?>" />
				<?php endif; ?>

				<?php do_action( 'cs_profile_editor_email' ); ?>
			</p>

			<?php if ( $customer->id > 0 && count( $customer->emails ) > 1 ) : ?>
				<p id="cs_profile_emails_wrap">
					<label for="cs_emails"><?php _e( 'Additional Email Addresses', 'commercestore' ); ?></label>
					<ul class="cs-profile-emails">
					<?php foreach ( $customer->emails as $email ) : ?>
						<?php if ( $email === $customer->email ) { continue; } ?>
						<li class="cs-profile-email">
							<?php echo $email; ?>
							<span class="actions">
								<?php
									$remove_url = wp_nonce_url(
										add_query_arg(
											array(
												'email'      => rawurlencode( $email ),
												'cs_action' => 'profile-remove-email',
												'redirect'   => esc_url( cs_get_current_page_url() ),
											)
										),
										'cs-remove-customer-email'
									);
								?>
								<a href="<?php echo $remove_url ?>" class="delete"><?php _e( 'Remove', 'commercestore' ); ?></a>
							</span>
						</li>
					<?php endforeach; ?>
					</ul>
				</p>
			<?php endif; ?>

			<?php do_action( 'cs_profile_editor_after_email' ); ?>

		</fieldset>

		<?php do_action( 'cs_profile_editor_after_personal_fields' ); ?>

		<fieldset id="cs_profile_address_fieldset">

			<legend id="cs_profile_billing_address_label"><?php _e( 'Change your Billing Address', 'commercestore' ); ?></legend>

			<p id="cs_profile_billing_address_line_1_wrap">
				<label for="cs_address_line1"><?php _e( 'Line 1', 'commercestore' ); ?></label>
				<input name="cs_address_line1" id="cs_address_line1" class="text cs-input" type="text" value="<?php echo esc_attr( $address['line1'] ); ?>" />
			</p>

			<p id="cs_profile_billing_address_line_2_wrap">
				<label for="cs_address_line2"><?php _e( 'Line 2', 'commercestore' ); ?></label>
				<input name="cs_address_line2" id="cs_address_line2" class="text cs-input" type="text" value="<?php echo esc_attr( $address['line2'] ); ?>" />
			</p>

			<p id="cs_profile_billing_address_city_wrap">
				<label for="cs_address_city"><?php _e( 'City', 'commercestore' ); ?></label>
				<input name="cs_address_city" id="cs_address_city" class="text cs-input" type="text" value="<?php echo esc_attr( $address['city'] ); ?>" />
			</p>

			<p id="cs_profile_billing_address_postal_wrap">
				<label for="cs_address_zip"><?php _e( 'Zip / Postal Code', 'commercestore' ); ?></label>
				<input name="cs_address_zip" id="cs_address_zip" class="text cs-input" type="text" value="<?php echo esc_attr( $address['zip'] ); ?>" />
			</p>

			<p id="cs_profile_billing_address_country_wrap">
				<label for="cs_address_country"><?php _e( 'Country', 'commercestore' ); ?></label>
				<select name="cs_address_country" id="cs_address_country" class="select cs-select" data-nonce="<?php echo wp_create_nonce( 'cs-country-field-nonce' ); ?>">
					<?php foreach( cs_get_country_list() as $key => $country ) : ?>
					<option value="<?php echo $key; ?>"<?php selected( $address['country'], $key ); ?>><?php echo esc_html( $country ); ?></option>
					<?php endforeach; ?>
				</select>
			</p>

			<p id="cs_profile_billing_address_state_wrap">
				<label for="cs_address_state"><?php _e( 'State / Province', 'commercestore' ); ?></label>
				<?php if( ! empty( $states ) ) : ?>
					<select name="cs_address_state" id="cs_address_state" class="select cs-select">
						<?php
							foreach( $states as $state_code => $state_name ) {
								echo '<option value="' . $state_code . '"' . selected( $state_code, $state, false ) . '>' . $state_name . '</option>';
							}
						?>
					</select>
				<?php else : ?>
					<input name="cs_address_state" id="cs_address_state" class="text cs-input" type="text" value="<?php echo esc_attr( $state ); ?>" />
				<?php endif; ?>

				<?php do_action( 'cs_profile_editor_address' ); ?>
			</p>

			<?php do_action( 'cs_profile_editor_after_address' ); ?>

		</fieldset>

		<?php do_action( 'cs_profile_editor_after_address_fields' ); ?>

		<fieldset id="cs_profile_password_fieldset">

			<legend id="cs_profile_password_label"><?php _e( 'Change your Password', 'commercestore' ); ?></legend>

			<p id="cs_profile_password_wrap">
				<label for="cs_new_user_pass1"><?php esc_html_e( 'New Password', 'commercestore' ); ?></label>
				<input name="cs_new_user_pass1" id="cs_new_user_pass1" class="password cs-input" type="password"/>
			</p>

			<p id="cs_profile_confirm_password_wrap">
				<label for="cs_new_user_pass2"><?php esc_html_e( 'Re-enter Password', 'commercestore' ); ?></label>
				<input name="cs_new_user_pass2" id="cs_new_user_pass2" class="password cs-input" type="password"/>
				<?php do_action( 'cs_profile_editor_password' ); ?>
			</p>

			<?php do_action( 'cs_profile_editor_after_password' ); ?>

		</fieldset>

		<?php do_action( 'cs_profile_editor_after_password_fields' ); ?>

		<fieldset id="cs_profile_submit_fieldset">

			<p id="cs_profile_submit_wrap">
				<input type="hidden" name="cs_profile_editor_nonce" value="<?php echo wp_create_nonce( 'cs-profile-editor-nonce' ); ?>"/>
				<input type="hidden" name="cs_action" value="edit_user_profile" />
				<input type="hidden" name="cs_redirect" value="<?php echo esc_url( cs_get_current_page_url() ); ?>" />
				<input name="cs_profile_editor_submit" id="cs_profile_editor_submit" type="submit" class="cs_submit cs-submit" value="<?php _e( 'Save Changes', 'commercestore' ); ?>"/>
			</p>

		</fieldset>

		<?php do_action( 'cs_profile_editor_fields_bottom' ); ?>

	</form><!-- #cs_profile_editor_form -->

	<?php do_action( 'cs_profile_editor_after' ); ?>

	<?php
else:
	do_action( 'cs_profile_editor_logged_out' );
endif;
