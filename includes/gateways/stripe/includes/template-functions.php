<?php
/**
 * Add an errors div
 *
 * @since       1.0
 * @return      void
 */
function csx_add_stripe_errors() {
	echo '<div id="cs-stripe-payment-errors"></div>';
}
add_action( 'cs_after_cc_fields', 'csx_add_stripe_errors', 999 );

/**
 * Stripe uses it's own credit card form because the card details are tokenized.
 *
 * We don't want the name attributes to be present on the fields in order to prevent them from getting posted to the server
 *
 * @since       1.7.5
 * @return      void
 */
function csx_credit_card_form( $echo = true ) {

	global $cs_options;

	if ( cs_stripe()->rate_limiting->has_hit_card_error_limit() ) {
		cs_set_error( 'cs_stripe_error_limit', __( 'We are unable to process your payment at this time, please try again later or contact support.', 'csx' ) );
		return;
	}

	ob_start(); ?>

	<?php if ( ! wp_script_is ( 'cs-stripe-js' ) ) : ?>
		<?php cs_stripe_js( true ); ?>
	<?php endif; ?>

	<?php do_action( 'cs_before_cc_fields' ); ?>

	<fieldset id="cs_cc_fields" class="cs-do-validate">
		<legend><?php _e( 'Credit Card Info', 'csx' ); ?></legend>
		<?php if( is_ssl() ) : ?>
			<div id="cs_secure_site_wrapper">
				<span class="padlock">
					<svg class="cs-icon cs-icon-lock" xmlns="http://www.w3.org/2000/svg" width="18" height="28" viewBox="0 0 18 28" aria-hidden="true">
						<path d="M5 12h8V9c0-2.203-1.797-4-4-4S5 6.797 5 9v3zm13 1.5v9c0 .828-.672 1.5-1.5 1.5h-15C.672 24 0 23.328 0 22.5v-9c0-.828.672-1.5 1.5-1.5H2V9c0-3.844 3.156-7 7-7s7 3.156 7 7v3h.5c.828 0 1.5.672 1.5 1.5z"/>
					</svg>
				</span>
				<span><?php _e( 'This is a secure SSL encrypted payment.', 'csx' ); ?></span>
			</div>
		<?php endif; ?>

		<?php
		$existing_cards = cs_stripe_get_existing_cards( get_current_user_id() );
		?>
		<?php if ( ! empty( $existing_cards ) ) { cs_stripe_existing_card_field_radio( get_current_user_id() ); } ?>

		<div class="cs-stripe-new-card" <?php if ( ! empty( $existing_cards ) ) { echo 'style="display: none;"'; } ?>>
			<?php do_action( 'cs_stripe_new_card_form' ); ?>
			<?php do_action( 'cs_after_cc_expiration' ); ?>
		</div>

	</fieldset>
	<?php

	do_action( 'cs_after_cc_fields' );

	$form = ob_get_clean();

	if ( false !== $echo ) {
		echo $form;
	}

	return $form;
}
add_action( 'cs_stripe_cc_form', 'csx_credit_card_form' );

/**
 * Display the markup for the Stripe new card form
 *
 * @since 2.6
 * @return void
 */
function cs_stripe_new_card_form() {
	if ( cs_stripe()->rate_limiting->has_hit_card_error_limit() ) {
		cs_set_error( 'cs_stripe_error_limit', __( 'Adding new payment methods is currently unavailable.', 'csx' ) );
		cs_print_errors();
		return;
	}
?>

<p id="cs-card-name-wrap">
	<label for="card_name" class="cs-label">
		<?php esc_html_e( 'Name on the Card', 'csx' ); ?>
		<span class="cs-required-indicator">*</span>
	</label>
	<span class="cs-description"><?php esc_html_e( 'The name printed on the front of your credit card.', 'csx' ); ?></span>
	<input type="text" name="card_name" id="card_name" class="card-name cs-input required" placeholder="<?php esc_attr_e( 'Card name', 'csx' ); ?>" autocomplete="cc-name" />
</p>

<div id="cs-card-wrap">
	<label for="cs-card-element" class="cs-label">
		<?php esc_html_e( 'Credit Card', 'csx' ); ?>
		<span class="cs-required-indicator">*</span>
	</label>

	<div id="cs-stripe-card-element"></div>
	<div id="cs-stripe-card-errors" role="alert"></div>

	<p></p><!-- Extra spacing -->
</div>

<?php
	/**
	 * Allow output of extra content before the credit card expiration field.
	 *
	 * This content no longer appears before the credit card expiration field
	 * with the introduction of Stripe Elements.
	 *
	 * @deprecated 2.7
	 * @since unknown
	 */
	do_action( 'cs_before_cc_expiration' );
}
add_action( 'cs_stripe_new_card_form', 'cs_stripe_new_card_form' );

/**
 * Show the checkbox for updating the billing information on an existing Stripe card
 *
 * @since 2.6
 * @return void
 */
function cs_stripe_update_billing_address_field() {
	$payment_mode   = strtolower( cs_get_chosen_gateway() );
	if ( cs_is_checkout() && 'stripe' !== $payment_mode ) {
		return;
	}

	$existing_cards = cs_stripe_get_existing_cards( get_current_user_id() );
	if ( empty( $existing_cards ) ) {
		return;
	}

	if ( ! did_action( 'cs_stripe_cc_form' ) ) {
		return;
	}

	$default_card = false;

	foreach ( $existing_cards as $existing_card ) {
		if ( $existing_card['default'] ) {
			$default_card = $existing_card['source'];
			break;
		}
	}
	?>
	<p class="cs-stripe-update-billing-address-current">
		<?php
		if ( $default_card ) :
			$address_fields = array( 
				'line1'   => isset( $default_card->address_line1 ) ? $default_card->address_line1 : null,
				'line2'   => isset( $default_card->address_line2 ) ? $default_card->address_line2 : null,
				'city'    => isset( $default_card->address_city ) ? $default_card->address_city : null,
				'state'   => isset( $default_card->address_state ) ? $default_card->address_state : null,
				'zip'     => isset( $default_card->address_zip ) ? $default_card->address_zip : null,
				'country' => isset( $default_card->address_country ) ? $default_card->address_country : null,
			);

			$address_fields = array_filter( $address_fields );

			echo esc_html( implode( ', ', $address_fields ) );
		endif;
		?>
	</p>

	<p class="cs-stripe-update-billing-address-wrapper">
		<input type="checkbox" name="cs_stripe_update_billing_address" id="cs-stripe-update-billing-address" value="1" />
		<label for="cs-stripe-update-billing-address"><?php _e( 'Enter new billing address', 'csx' ); ?></label>
	</p>
	<?php
}
add_action( 'cs_cc_billing_top', 'cs_stripe_update_billing_address_field', 10 );

/**
 * Display a radio list of existing cards on file for a user ID
 *
 * @since 2.6
 * @param int $user_id
 *
 * @return void
 */
function cs_stripe_existing_card_field_radio( $user_id = 0 ) {
	if ( cs_stripe()->rate_limiting->has_hit_card_error_limit() ) {
		cs_set_error( 'cs_stripe_error_limit', __( 'We are unable to process your payment at this time, please try again later or contacts support.', 'csx' ) );
		return;
	}

	// Can't use just cs_is_checkout() because this could happen in an AJAX request.
	$is_checkout = cs_is_checkout() || ( isset( $_REQUEST['action'] ) && 'cs_load_gateway' === $_REQUEST['action'] );

	cs_stripe_css( true );
	$existing_cards = cs_stripe_get_existing_cards( $user_id );
	if ( ! empty( $existing_cards ) ) : ?>
	<div class="cs-stripe-card-selector cs-card-selector-radio">
		<?php foreach ( $existing_cards as $card ) : ?>
			<?php $source = $card['source']; ?>
			<div class="cs-stripe-card-radio-item existing-card-wrapper <?php if ( $card['default'] ) { echo ' selected'; } ?>">
				<input type="hidden" id="<?php echo $source->id; ?>-billing-details"
					   data-address_city="<?php echo $source->address_city; ?>"
					   data-address_country="<?php echo $source->address_country; ?>"
					   data-address_line1="<?php echo $source->address_line1; ?>"
					   data-address_line2="<?php echo $source->address_line2; ?>"
					   data-address_state="<?php echo $source->address_state; ?>"
					   data-address_zip="<?php echo $source->address_zip; ?>"
				/>
				<label for="<?php echo $source->id; ?>">
					<input <?php checked( true, $card['default'], true ); ?> type="radio" id="<?php echo $source->id; ?>" name="cs_stripe_existing_card" value="<?php echo $source->id; ?>" class="cs-stripe-existing-card">
					<span class="card-label">
						<span class="card-data">
							<span class="card-name-number">
								<span class="card-brand"><?php echo $source->brand; ?></span>
								<span class="card-ending-label"><?php _e( 'ending in', 'csx' ); ?></span>
								<span class="card-last-4"><?php echo $source->last4; ?></span>
							</span>
							<span class="card-expires-on">
								<span class="default-card-sep"><?php echo '&mdash; '; ?></span>
								<span class="card-expiration-label"><?php _e( 'expires', 'csx' ); ?></span>
								<span class="card-expiration">
									<?php echo $source->exp_month . '/' . $source->exp_year; ?>
								</span>
							</span>
						</span>
						<?php
							$current  = strtotime( date( 'm/Y' ) );
							$exp_date = strtotime( $source->exp_month . '/' . $source->exp_year );
							if ( $exp_date < $current ) :
							?>
							<span class="card-expired">
									<?php _e( 'Expired', 'csx' ); ?>
								</span>
							<?php
							endif;
						?>
					</span>
					<?php if ( $card['default'] && $is_checkout ) { ?>
						<span class="card-status">
							<span class="default-card-sep"><?php echo '&mdash; '; ?></span>
							<span class="card-is-default"><?php _e( 'Default', 'csx'); ?></span>
						</span>
					<?php } ?>
				</label>
			</div>
		<?php endforeach; ?>
		<div class="cs-stripe-card-radio-item new-card-wrapper">
			<input type="radio" id="cs-stripe-add-new" class="cs-stripe-existing-card" name="cs_stripe_existing_card" value="new" />
			<label for="cs-stripe-add-new"><span class="add-new-card"><?php _e( 'Add New Card', 'csx' ); ?></span></label>
		</div>
	</div>
	<?php endif;
}

/**
 * Output the management interface for a user's Stripe card
 *
 * @since 2.6
 * @return void
 */
function cs_stripe_manage_cards() {
	$enabled = cs_stripe_existing_cards_enabled();
	if ( ! $enabled ) {
		return;
	}

	$stripe_customer_id = csx_get_stripe_customer_id( get_current_user_id() );
	if ( empty( $stripe_customer_id ) ) {
		return;
	}

	if ( cs_stripe()->rate_limiting->has_hit_card_error_limit() ) {
		cs_set_error( 'cs_stripe_error_limit', __( 'Payment method management is currently unavailable.', 'csx' ) );
		cs_print_errors();
		return;
	}

	$existing_cards = cs_stripe_get_existing_cards( get_current_user_id() );

	cs_stripe_css( true );
	cs_stripe_js( true );
	$display = cs_get_option( 'stripe_billing_fields', 'full' );
?>
	<div id="cs-stripe-manage-cards">
		<fieldset>
			<legend><?php _e( 'Manage Payment Methods', 'csx' ); ?></legend>
			<input type="hidden" id="stripe-update-card-user_id" name="stripe-update-user-id" value="<?php echo get_current_user_id(); ?>" />
			<?php if ( ! empty( $existing_cards ) ) : ?>
				<?php foreach( $existing_cards as $card ) : ?>
				<?php $source = $card['source']; ?>
				<div id="<?php echo esc_attr( $source->id ); ?>_card_item" class="cs-stripe-card-item">

					<span class="card-details">
						<span class="card-brand"><?php echo $source->brand; ?></span>
						<span class="card-ending-label"><?php _e( 'ending in', 'csx' ); ?></span>
						<span class="card-last-4"><?php echo $source->last4; ?></span>
						<?php if ( $card['default'] ) { ?>
							<span class="default-card-sep"><?php echo '&mdash; '; ?></span>
							<span class="card-is-default"><?php _e( 'Default', 'csx'); ?></span>
						<?php } ?>
					</span>

					<span class="card-meta">
						<span class="card-expiration"><span class="card-expiration-label"><?php _e( 'Expires', 'csx' ); ?>: </span><span class="card-expiration-date"><?php echo $source->exp_month; ?>/<?php echo $source->exp_year; ?></span></span>
						<span class="card-address">
							<?php
							$address_fields = array( 
								'line1'   => isset( $source->address_line1 ) ? $source->address_line1 : '',
								'zip'     => isset( $source->address_zip ) ? $source->address_zip : '',
								'country' => isset( $source->address_country ) ? $source->address_country : '',
							);

							echo esc_html( implode( ' ', $address_fields ) );
							?>
						</span>
					</span>

					<span id="<?php echo esc_attr( $source->id ); ?>-card-actions" class="card-actions">
						<span class="card-update">
							<a href="#" class="cs-stripe-update-card" data-source="<?php echo esc_attr( $source->id ); ?>"><?php _e( 'Update', 'csx' ); ?></a>
						</span>

						<?php if ( ! $card['default'] ) : ?>
						 |
						<span class="card-set-as-default">
							<a href="#" class="cs-stripe-default-card" data-source="<?php echo esc_attr( $source->id ); ?>"><?php _e( 'Set as Default', 'csx' ); ?></a>
						</span>
						<?php
						endif;

						$can_delete = apply_filters( 'cs_stripe_can_delete_card', true, $card, $existing_cards );
						if ( $can_delete ) :
						?>
						|
						<span class="card-delete">
							<a href="#" class="cs-stripe-delete-card delete" data-source="<?php echo esc_attr( $source->id ); ?>"><?php _e( 'Delete', 'csx' ); ?></a>
						</span>
						<?php endif; ?>
						
						<span style="display: none;" class="cs-loading-ajax cs-loading"></span>
					</span>

					<form id="<?php echo esc_attr( $source->id ); ?>-update-form" class="card-update-form" data-source="<?php echo esc_attr( $source->id ); ?>">
						<label><?php _e( 'Billing Details', 'csx' ); ?></label>

						<div class="card-address-fields">
							<p class="csx-card-address-field csx-card-address-field--address1">
							<?php
							echo CS()->html->text( array(
								'id'    => sprintf( 'csx_address_line1_%1$s', $source->id ),
								'value' => sanitize_text_field( isset( $source->address_line1 ) ? $source->address_line1 : '' ),
								'label' => esc_html__( 'Address Line 1', 'csx' ),
								'name'  => 'address_line1',
								'class' => 'card-update-field address_line1 text cs-input',
								'data'  => array(
									'key' => 'address_line1',
								)
							) );
							?>
							</p>
							<p class="csx-card-address-field csx-card-address-field--address2">
							<?php
							echo CS()->html->text( array(
								'id'    => sprintf( 'csx_address_line2_%1$s', $source->id ),
								'value' => sanitize_text_field( isset( $source->address_line2 ) ? $source->address_line2 : '' ),
								'label' => esc_html__( 'Address Line 2', 'csx' ),
								'name'  => 'address_line2',
								'class' => 'card-update-field address_line2 text cs-input',
								'data'  => array(
									'key' => 'address_line2',
								)
							) );
							?>
							</p>
							<p class="csx-card-address-field csx-card-address-field--city">
							<?php
							echo CS()->html->text( array(
								'id'    => sprintf( 'csx_address_city_%1$s', $source->id ),
								'value' => sanitize_text_field( isset( $source->address_city ) ? $source->address_city : '' ),
								'label' => esc_html__( 'City', 'csx' ),
								'name'  => 'address_city',
								'class' => 'card-update-field address_city text cs-input',
								'data'  => array(
									'key' => 'address_city',
								)
							) );
							?>
							</p>
							<p class="csx-card-address-field csx-card-address-field--zip">
							<?php
							echo CS()->html->text( array(
								'id'    => sprintf( 'csx_address_zip_%1$s', $source->id ),
								'value' => sanitize_text_field( isset( $source->address_zip ) ? $source->address_zip : '' ),
								'label' => esc_html__( 'ZIP Code', 'csx' ),
								'name'  => 'address_zip',
								'class' => 'card-update-field address_zip text cs-input',
								'data'  => array(
									'key' => 'address_zip',
								)
							) );
							?>
							</p>
							<p class="csx-card-address-field csx-card-address-field--country">
								<label for="<?php echo esc_attr( sprintf( 'csx_address_country_%1$s', $source->id ) ); ?>">
									<?php esc_html_e( 'Country', 'csx' ); ?>
								</label>

								<?php
								$countries = array_filter( cs_get_country_list() );
								$country   = isset( $source->address_country ) ? $source->address_country : cs_get_shop_country();
								echo CS()->html->select( array(
									'id'               => sprintf( 'csx_address_country_%1$s', $source->id ),
									'name'             => 'address_country',
									'label'            => esc_html__( 'Country', 'csx' ),
									'options'          => $countries,
									'selected'         => $country,
									'class'            => 'card-update-field address_country',
									'data'             => array( 'key' => 'address_country' ),
									'show_option_all'  => false,
									'show_option_none' => false,
								) );
								?>
							</p>

							<p class="csx-card-address-field csx-card-address-field--state">
								<label for="<?php echo esc_attr( sprintf( 'csx_address_state_%1$s', $source->id ) ); ?>">
									<?php esc_html_e( 'State', 'csx' ); ?>
								</label>

								<?php
								$selected_state = isset( $source->address_state ) ? $source->address_state : cs_get_shop_state();
								$states         = cs_get_shop_states( $country );
								echo CS()->html->select( array(
									'id'               => sprintf( 'csx_address_state_%1$s', $source->id ),
									'name'             => 'address_state',
									'options'          => $states,
									'selected'         => $selected_state,
									'class'            => 'card-update-field address_state card_state',
									'data'             => array( 'key' => 'address_state' ),
									'show_option_all'  => false,
									'show_option_none' => false,
								) );
								?>
							</p>
						</div>

						<p class="card-expiration-fields">
							<label for="<?php echo esc_attr( sprintf( 'csx_card_exp_month_%1$s', $source->id ) ); ?>" class="cs-label">
								<?php _e( 'Expiration (MM/YY)', 'csx' ); ?>
							</label>

							<?php
								$months = array_combine( $r = range( 1, 12 ), $r );
								echo CS()->html->select( array(
									'id'               => sprintf( 'csx_card_exp_month_%1$s', $source->id ),
									'name'             => 'exp_month',
									'options'          => $months,
									'selected'         => $source->exp_month,
									'class'            => 'card-expiry-month cs-select cs-select-small card-update-field exp_month',
									'data'             => array( 'key' => 'exp_month' ),
									'show_option_all'  => false,
									'show_option_none' => false,
								) );
							?>

							<span class="exp-divider"> / </span>

							<?php
								$years = array_combine( $r = range( date( 'Y' ), date( 'Y' ) + 30 ), $r );
								echo CS()->html->select( array(
									'id'               => sprintf( 'csx_card_exp_year_%1$s', $source->id ),
									'name'             => 'exp_year',
									'options'          => $years,
									'selected'         => $source->exp_year,
									'class'            => 'card-expiry-year cs-select cs-select-small card-update-field exp_year',
									'data'             => array( 'key' => 'exp_year' ),
									'show_option_all'  => false,
									'show_option_none' => false,
								) );
							?>
						</p>

						<p>
							<input
								type="submit"
								class="cs-stripe-submit-update"
								data-loading="<?php echo esc_attr__( 'Please Wait…', 'csx' ); ?>"
								data-submit="<?php echo esc_attr__( 'Update Card', 'csx' ); ?>"
								value="<?php echo esc_attr__( 'Update Card', 'csx' ); ?>"
							/>

							<a href="#" class="cs-stripe-cancel-update" data-source="<?php echo esc_attr( $source->id ); ?>"><?php _e( 'Cancel', 'csx' ); ?></a>

							<input type="hidden" name="card_id" data-key="id" value="<?php echo $source->id; ?>" />
							<?php wp_nonce_field( $source->id . '_update', 'card_update_nonce_' . $source->id, true ); ?>
						</p>
					</form>
				</div>
				<?php endforeach; ?>
			<?php endif; ?>
			<form id="cs-stripe-add-new-card">
				<div class="cs-stripe-add-new-card" style="display: none;">
					<label><?php _e( 'Add New Card', 'csx' ); ?></label>
					<fieldset id="cs_cc_card_info" class="cc-card-info">
						<legend><?php _e( 'Credit Card Details', 'commercestore' ); ?></legend>
						<?php do_action( 'cs_stripe_new_card_form' ); ?>
					</fieldset>
					<?php
					switch( $display ) {
					case 'full' :
						cs_default_cc_address_fields();
						break;

					case 'zip_country' :
						cs_stripe_zip_and_country();
						add_filter( 'cs_purchase_form_required_fields', 'cs_stripe_require_zip_and_country' );

						break;
					}
					?>
				</div>
				<div class="cs-stripe-add-card-errors"></div>
				<div class="cs-stripe-add-card-actions">

					<input
						type="submit"
						class="cs-button cs-stripe-add-new"
						data-loading="<?php echo esc_attr__( 'Please Wait…', 'csx' ); ?>"
						data-submit="<?php echo esc_attr__( 'Add new card', 'csx' ); ?>"
						value="<?php echo esc_attr__( 'Add new card', 'csx' ); ?>"
					/>
					<a href="#" id="cs-stripe-add-new-cancel" style="display: none;"><?php _e( 'Cancel', 'csx' ); ?></a>
					<?php wp_nonce_field( 'cs-stripe-add-card', 'cs-stripe-add-card-nonce', false, true ); ?>
				</div>
			</form>
		</fieldset>
	</div>
	<?php
}
add_action( 'cs_profile_editor_after', 'cs_stripe_manage_cards' );

/**
 * Zip / Postal Code field for when full billing address is disabled
 *
 * @since       2.5
 * @return      void
 */
function cs_stripe_zip_and_country() {

	$logged_in = is_user_logged_in();
	$customer  = CS()->session->get( 'customer' );
	$customer  = wp_parse_args( $customer, array( 'address' => array(
		'line1'   => '',
		'line2'   => '',
		'city'    => '',
		'zip'     => '',
		'state'   => '',
		'country' => ''
	) ) );

	$customer['address'] = array_map( 'sanitize_text_field', $customer['address'] );

	if( $logged_in ) {
		$existing_cards = cs_stripe_get_existing_cards( get_current_user_id() );
		if ( empty( $existing_cards ) ) {

			$user_address = cs_get_customer_address( get_current_user() );

			foreach( $customer['address'] as $key => $field ) {

				if ( empty( $field ) && ! empty( $user_address[ $key ] ) ) {
					$customer['address'][ $key ] = $user_address[ $key ];
				} else {
					$customer['address'][ $key ] = '';
				}

			}
		} else {
			foreach ( $existing_cards as $card ) {
				if ( false === $card['default'] ) {
					continue;
				}

				$source = $card['source'];
				$customer['address'] = array(
					'line1'   => $source->address_line1,
					'line2'   => $source->address_line2,
					'city'    => $source->address_city,
					'zip'     => $source->address_zip,
					'state'   => $source->address_state,
					'country' => $source->address_country,
				);
			}
		}

	}
?>
	<fieldset id="cs_cc_address" class="cc-address">
		<legend><?php _e( 'Billing Details', 'csx' ); ?></legend>
		<p id="cs-card-country-wrap">
			<label for="billing_country" class="cs-label">
				<?php _e( 'Billing Country', 'csx' ); ?>
				<?php if( cs_field_is_required( 'billing_country' ) ) { ?>
					<span class="cs-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="cs-description"><?php _e( 'The country for your billing address.', 'csx' ); ?></span>
			<select name="billing_country" id="billing_country" class="billing_country cs-select<?php if( cs_field_is_required( 'billing_country' ) ) { echo ' required'; } ?>"<?php if( cs_field_is_required( 'billing_country' ) ) {  echo ' required '; } ?> autocomplete="billing country">
				<?php

				$selected_country = cs_get_shop_country();

				if( ! empty( $customer['address']['country'] ) && '*' !== $customer['address']['country'] ) {
					$selected_country = $customer['address']['country'];
				}

				$countries = cs_get_country_list();
				foreach( $countries as $country_code => $country ) {
				  echo '<option value="' . esc_attr( $country_code ) . '"' . selected( $country_code, $selected_country, false ) . '>' . $country . '</option>';
				}
				?>
			</select>
		</p>
		<p id="cs-card-zip-wrap">
			<label for="card_zip" class="cs-label">
				<?php _e( 'Billing Zip / Postal Code', 'csx' ); ?>
				<?php if( cs_field_is_required( 'card_zip' ) ) { ?>
					<span class="cs-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="cs-description"><?php _e( 'The zip or postal code for your billing address.', 'csx' ); ?></span>
			<input type="text" size="4" name="card_zip" id="card_zip" class="card-zip cs-input<?php if( cs_field_is_required( 'card_zip' ) ) { echo ' required'; } ?>" placeholder="<?php _e( 'Zip / Postal Code', 'csx' ); ?>" value="<?php echo $customer['address']['zip']; ?>"<?php if( cs_field_is_required( 'card_zip' ) ) {  echo ' required '; } ?> autocomplete="billing postal-code" />
		</p>
	</fieldset>
<?php
}

/**
 * Determine how the billing address fields should be displayed
 *
 * @access      public
 * @since       2.5
 * @return      void
 */
function cs_stripe_setup_billing_address_fields() {

	if( ! function_exists( 'cs_use_taxes' ) ) {
		return;
	}

	if( cs_use_taxes() || 'stripe' !== cs_get_chosen_gateway() || ! cs_get_cart_total() > 0 ) {
		return;
	}

	$display = cs_get_option( 'stripe_billing_fields', 'full' );

	switch( $display ) {

		case 'full' :

			// Make address fields required
			add_filter( 'cs_require_billing_address', '__return_true' );

			break;

		case 'zip_country' :

			remove_action( 'cs_after_cc_fields', 'cs_default_cc_address_fields', 10 );
			add_action( 'cs_after_cc_fields', 'cs_stripe_zip_and_country', 9 );

			// Make Zip required
			add_filter( 'cs_purchase_form_required_fields', 'cs_stripe_require_zip_and_country' );

			break;

		case 'none' :

			remove_action( 'cs_after_cc_fields', 'cs_default_cc_address_fields', 10 );

			break;

	}

}
add_action( 'init', 'cs_stripe_setup_billing_address_fields', 9 );

/**
 * Force zip code and country to be required when billing address display is zip only
 *
 * @access      public
 * @since       2.5
 * @return      array $fields The required fields
 */
function cs_stripe_require_zip_and_country( $fields ) {

	$fields['card_zip'] = array(
		'error_id' => 'invalid_zip_code',
		'error_message' => __( 'Please enter your zip / postal code', 'csx' )
	);

	$fields['billing_country'] = array(
		'error_id' => 'invalid_country',
		'error_message' => __( 'Please select your billing country', 'csx' )
	);

	return $fields;
}