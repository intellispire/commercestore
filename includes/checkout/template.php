<?php
/**
 * Checkout Template
 *
 * @package     CS
 * @subpackage  Checkout
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get Checkout Form
 *
 * @since 1.0
 * @return string
 */
function cs_checkout_form() {
	$payment_mode = cs_get_chosen_gateway();
	$form_action  = esc_url( cs_get_checkout_uri( 'payment-mode=' . $payment_mode ) );

	ob_start();
		echo '<div id="cs_checkout_wrap">';
		if ( cs_get_cart_contents() || cs_cart_has_fees() ) :
			cs_checkout_cart(); ?>
			<div id="cs_checkout_form_wrap" class="cs_clearfix">
				<?php do_action( 'cs_before_purchase_form' ); ?>
				<form id="cs_purchase_form" class="cs_form" action="<?php echo $form_action; ?>" method="POST">
					<?php
					/**
					 * Hooks in at the top of the checkout form
					 *
					 * @since 1.0
					 */
					do_action( 'cs_checkout_form_top' );

					if ( cs_is_ajax_disabled() && ! empty( $_REQUEST['payment-mode'] ) ) {
						do_action( 'cs_purchase_form' );
					} elseif ( cs_show_gateways() ) {
						do_action( 'cs_payment_mode_select'  );
					} else {
						do_action( 'cs_purchase_form' );
					}

					/**
					 * Hooks in at the bottom of the checkout form
					 *
					 * @since 1.0
					 */
					do_action( 'cs_checkout_form_bottom' )
					?>
				</form>
				<?php do_action( 'cs_after_purchase_form' ); ?>
			</div><!--end #cs_checkout_form_wrap-->
		<?php
		else:
			/**
			 * Fires off when there is nothing in the cart
			 *
			 * @since 1.0
			 */
			do_action( 'cs_cart_empty' );
		endif;
		echo '</div><!--end #cs_checkout_wrap-->';
	return ob_get_clean();
}

/**
 * Renders the Purchase Form, hooks are provided to add to the purchase form.
 * The default Purchase Form rendered displays a list of the enabled payment
 * gateways, a user registration form (if enable) and a credit card info form
 * if credit cards are enabled
 *
 * @since 1.4
 * @return string
 */
function cs_show_purchase_form() {
	$payment_mode = cs_get_chosen_gateway();

	/**
	 * Hooks in at the top of the purchase form.
	 *
	 * @since 1.4
	 */
	do_action( 'cs_purchase_form_top' );

	// Maybe load purchase form.
	if ( cs_can_checkout() ) {

		/**
		 * Fires before the register/login form.
		 *
		 * @since 1.4
		 */
		do_action( 'cs_purchase_form_before_register_login' );

		$show_register_form = cs_get_option( 'show_register_form', 'none' );
		if ( ( 'registration' === $show_register_form || ( 'both' === $show_register_form && ! isset( $_GET['login'] ) ) ) && ! is_user_logged_in() ) : ?>
			<div id="cs_checkout_login_register">
				<?php do_action( 'cs_purchase_form_register_fields' ); ?>
			</div>
		<?php elseif ( ( 'login' === $show_register_form || ( 'both' === $show_register_form && isset( $_GET['login'] ) ) ) && ! is_user_logged_in() ) : ?>
			<div id="cs_checkout_login_register">
				<?php do_action( 'cs_purchase_form_login_fields' ); ?>
			</div>
		<?php endif; ?>

		<?php
		if ( ( ! isset( $_GET['login'] ) && is_user_logged_in() ) || ! isset( $show_register_form ) || 'none' === $show_register_form || 'login' === $show_register_form ) { // WPCS: CSRF ok.
			do_action( 'cs_purchase_form_after_user_info' );
		}

		/**
		 * Hooks in before the credit card form.
		 *
		 * @since 1.4
		 */
		do_action( 'cs_purchase_form_before_cc_form' );

		if ( cs_get_cart_total() > 0 ) {

			// Load the credit card form and allow gateways to load their own if they wish.
			if ( has_action( 'cs_' . $payment_mode . '_cc_form' ) ) {
				do_action( 'cs_' . $payment_mode . '_cc_form' );
			} else {
				do_action( 'cs_cc_form' );
			}
		}

		/**
		 * Hooks in after the credit card form.
		 *
		 * @since 1.4
		 */
		do_action( 'cs_purchase_form_after_cc_form' );

	// Can't checkout.
	} else {
		do_action( 'cs_purchase_form_no_access' );
	}

	/**
	 * Hooks in at the bottom of the purchase form.
	 *
	 * @since 1.4
	 */
	do_action( 'cs_purchase_form_bottom' );
}
add_action( 'cs_purchase_form', 'cs_show_purchase_form' );

/**
 * Shows the User Info fields in the Personal Info box, more fields can be added
 * via the hooks provided.
 *
 * @since 1.3.3
 * @return void
 */
function cs_user_info_fields() {
	$customer = CS()->session->get( 'customer' );
	$customer = wp_parse_args( $customer, array( 'first_name' => '', 'last_name' => '', 'email' => '' ) );

	if ( is_user_logged_in() ) {
		$user_data = get_userdata( get_current_user_id() );
		foreach ( $customer as $key => $field ) {
			if ( 'email' === $key && empty( $field ) ) {
				$customer[ $key ] = $user_data->user_email;
			} elseif ( empty( $field ) ) {
				$customer[ $key ] = $user_data->$key;
			}
		}
	}

	$customer = array_map( 'sanitize_text_field', $customer );
	?>
	<fieldset id="cs_checkout_user_info">
		<legend><?php echo apply_filters( 'cs_checkout_personal_info_text', esc_html__( 'Personal info', 'commercestore' ) ); ?></legend>
		<?php do_action( 'cs_purchase_form_before_email' ); ?>
		<p id="cs-email-wrap">
			<label class="cs-label" for="cs-email">
				<?php esc_html_e( 'Email address', 'commercestore' ); ?>
				<?php if ( cs_field_is_required( 'cs_email' ) ) : ?>
					<span class="cs-required-indicator">*</span>
				<?php endif; ?>
			</label>
			<span class="cs-description" id="cs-email-description"><?php esc_html_e( 'We will send the purchase receipt to this address.', 'commercestore' ); ?></span>
			<input class="cs-input required" type="email" name="cs_email" placeholder="<?php esc_html_e( 'Email address', 'commercestore' ); ?>" id="cs-email" value="<?php echo esc_attr( $customer['email'] ); ?>" aria-describedby="cs-email-description"<?php if( cs_field_is_required( 'cs_email' ) ) {  echo ' required '; } ?>/>
		</p>
		<?php do_action( 'cs_purchase_form_after_email' ); ?>
		<p id="cs-first-name-wrap">
			<label class="cs-label" for="cs-first">
				<?php esc_html_e( 'First name', 'commercestore' ); ?>
				<?php if ( cs_field_is_required( 'cs_first' ) ) : ?>
					<span class="cs-required-indicator">*</span>
				<?php endif; ?>
			</label>
			<span class="cs-description" id="cs-first-description"><?php esc_html_e( 'We will use this to personalize your account experience.', 'commercestore' ); ?></span>
			<input class="cs-input required" type="text" name="cs_first" placeholder="<?php esc_html_e( 'First name', 'commercestore' ); ?>" id="cs-first" value="<?php echo esc_attr( $customer['first_name'] ); ?>"<?php if( cs_field_is_required( 'cs_first' ) ) {  echo ' required '; } ?> aria-describedby="cs-first-description" />
		</p>
		<p id="cs-last-name-wrap">
			<label class="cs-label" for="cs-last">
				<?php esc_html_e( 'Last name', 'commercestore' ); ?>
				<?php if ( cs_field_is_required( 'cs_last' ) ) : ?>
					<span class="cs-required-indicator">*</span>
				<?php endif; ?>
			</label>
			<span class="cs-description" id="cs-last-description"><?php esc_html_e( 'We will use this as well to personalize your account experience.', 'commercestore' ); ?></span>
			<input class="cs-input<?php if( cs_field_is_required( 'cs_last' ) ) { echo ' required'; } ?>" type="text" name="cs_last" id="cs-last" placeholder="<?php esc_html_e( 'Last name', 'commercestore' ); ?>" value="<?php echo esc_attr( $customer['last_name'] ); ?>"<?php if( cs_field_is_required( 'cs_last' ) ) {  echo ' required '; } ?> aria-describedby="cs-last-description"/>
		</p>
		<?php do_action( 'cs_purchase_form_user_info' ); ?>
		<?php do_action( 'cs_purchase_form_user_info_fields' ); ?>
	</fieldset>
	<?php
}
add_action( 'cs_purchase_form_after_user_info', 'cs_user_info_fields' );
add_action( 'cs_register_fields_before', 'cs_user_info_fields' );

/**
 * Renders the credit card info form.
 *
 * @since 1.0
 * @return void
 */
function cs_get_cc_form() {
	ob_start(); ?>

	<?php do_action( 'cs_before_cc_fields' ); ?>

	<fieldset id="cs_cc_fields" class="cs-do-validate">
		<legend><?php _e( 'Credit card info', 'commercestore' ); ?></legend>
		<?php if ( is_ssl() ) : ?>
			<div id="cs_secure_site_wrapper">
				<?php
					echo cs_get_payment_icon(
						array(
							'icon'    => 'lock',
							'width'   => 16,
							'height'  => 16,
							'title'   => __( 'Secure SSL encrypted payment', 'commercestore' ),
							'classes' => array( 'cs-icon', 'cs-icon-lock' )
						)
					);
				?>
				<span><?php _e( 'This is a secure SSL encrypted payment', 'commercestore' ); ?></span>
			</div>
		<?php endif; ?>
		<p id="cs-card-number-wrap">
			<label for="card_number" class="cs-label">
				<?php _e( 'Card number', 'commercestore' ); ?>
				<span class="cs-required-indicator">*</span>
				<span class="card-type"></span>
			</label>
			<span class="cs-description"><?php _e( 'The (typically) 16 digits on the front of your credit card.', 'commercestore' ); ?></span>
			<input type="tel" pattern="^[0-9!@#$%^&* ]*$" autocomplete="off" name="card_number" id="card_number" class="card-number cs-input required" placeholder="<?php _e( 'Card number', 'commercestore' ); ?>" />
		</p>
		<p id="cs-card-cvc-wrap">
			<label for="card_cvc" class="cs-label">
				<?php _e( 'CVC', 'commercestore' ); ?>
				<span class="cs-required-indicator">*</span>
			</label>
			<span class="cs-description"><?php _e( 'The 3 digit (back) or 4 digit (front) value on your card.', 'commercestore' ); ?></span>
			<input type="tel" pattern="[0-9]{3,4}" size="4" maxlength="4" autocomplete="off" name="card_cvc" id="card_cvc" class="card-cvc cs-input required" placeholder="<?php _e( 'Security code', 'commercestore' ); ?>" />
		</p>
		<p id="cs-card-name-wrap">
			<label for="card_name" class="cs-label">
				<?php _e( 'Name on the card', 'commercestore' ); ?>
				<span class="cs-required-indicator">*</span>
			</label>
			<span class="cs-description"><?php _e( 'The name printed on the front of your credit card.', 'commercestore' ); ?></span>
			<input type="text" autocomplete="off" name="card_name" id="card_name" class="card-name cs-input required" placeholder="<?php _e( 'Card name', 'commercestore' ); ?>" />
		</p>
		<?php do_action( 'cs_before_cc_expiration' ); ?>
		<p class="card-expiration">
			<label for="card_exp_month" class="cs-label">
				<?php _e( 'Expiration (MM/YY)', 'commercestore' ); ?>
				<span class="cs-required-indicator">*</span>
			</label>
			<span class="cs-description"><?php _e( 'The date your credit card expires, typically on the front of the card.', 'commercestore' ); ?></span>
			<select id="card_exp_month" name="card_exp_month" class="card-expiry-month cs-select cs-select-small required">
				<?php for( $i = 1; $i <= 12; $i++ ) { echo '<option value="' . $i . '">' . sprintf ('%02d', $i ) . '</option>'; } ?>
			</select>
			<span class="exp-divider">/</span>
			<select id="card_exp_year" name="card_exp_year" class="card-expiry-year cs-select cs-select-small required">
				<?php for( $i = date('Y'); $i <= date('Y') + 30; $i++ ) { echo '<option value="' . $i . '">' . substr( $i, 2 ) . '</option>'; } ?>
			</select>
		</p>
		<?php do_action( 'cs_after_cc_expiration' ); ?>

	</fieldset>
	<?php
	do_action( 'cs_after_cc_fields' );

	echo ob_get_clean();
}
add_action( 'cs_cc_form', 'cs_get_cc_form' );

/**
 * Outputs the default credit card address fields
 *
 * @since 1.0
 * @since 3.0 Updated to use `cs_get_customer_address()`.
 */
function cs_default_cc_address_fields() {
	$logged_in = is_user_logged_in();

	$customer = CS()->session->get( 'customer' );

	$customer = wp_parse_args( $customer, array(
		'address' => array(
			'line1'   => '',
			'line2'   => '',
			'city'    => '',
			'zip'     => '',
			'state'   => '',
			'country' => '',
		),
	) );

	$customer['address'] = array_map( 'sanitize_text_field', $customer['address'] );

	if ( $logged_in ) {
		$user_address = cs_get_customer_address();

		foreach ( $customer['address'] as $key => $field ) {
			if ( empty( $field ) && ! empty( $user_address[ $key ] ) ) {
				$customer['address'][ $key ] = $user_address[ $key ];
			} else {
				$customer['address'][ $key ] = '';
			}
		}
	}

	/**
	 * Filter the billing address details that will be pre-populated on the checkout form..
	 *
	 * @since 2.8
	 *
	 * @param array $address The customer address.
	 * @param array $customer The customer data from the session
	 */
	$customer['address'] = apply_filters( 'cs_checkout_billing_details_address', $customer['address'], $customer );

	ob_start(); ?>
	<fieldset id="cs_cc_address" class="cc-address">
		<legend><?php _e( 'Billing details', 'commercestore' ); ?></legend>
		<?php do_action( 'cs_cc_billing_top' ); ?>
		<p id="cs-card-address-wrap">
			<label for="card_address" class="cs-label">
				<?php _e( 'Billing address', 'commercestore' ); ?>
				<?php if ( cs_field_is_required( 'card_address' ) ) : ?>
					<span class="cs-required-indicator">*</span>
				<?php endif; ?>
			</label>
			<span class="cs-description"><?php _e( 'The primary billing address for your credit card.', 'commercestore' ); ?></span>
			<input type="text" id="card_address" name="card_address" class="card-address cs-input<?php if ( cs_field_is_required( 'card_address' ) ) { echo ' required'; } ?>" placeholder="<?php _e( 'Address line 1', 'commercestore' ); ?>" value="<?php echo $customer['address']['line1']; ?>"<?php if ( cs_field_is_required( 'card_address' ) ) {  echo ' required '; } ?>/>
		</p>
		<p id="cs-card-address-2-wrap">
			<label for="card_address_2" class="cs-label">
				<?php _e( 'Billing address line 2 (optional)', 'commercestore' ); ?>
				<?php if( cs_field_is_required( 'card_address_2' ) ) : ?>
					<span class="cs-required-indicator">*</span>
				<?php endif; ?>
			</label>
			<span class="cs-description"><?php _e( 'The suite, apt no, PO box, etc, associated with your billing address.', 'commercestore' ); ?></span>
			<input type="text" id="card_address_2" name="card_address_2" class="card-address-2 cs-input<?php if ( cs_field_is_required( 'card_address_2' ) ) { echo ' required'; } ?>" placeholder="<?php _e( 'Address line 2', 'commercestore' ); ?>" value="<?php echo $customer['address']['line2']; ?>"<?php if ( cs_field_is_required( 'card_address_2' ) ) {  echo ' required '; } ?>/>
		</p>
		<p id="cs-card-city-wrap">
			<label for="card_city" class="cs-label">
				<?php _e( 'Billing city', 'commercestore' ); ?>
				<?php if ( cs_field_is_required( 'card_city' ) ) : ?>
					<span class="cs-required-indicator">*</span>
				<?php endif; ?>
			</label>
			<span class="cs-description"><?php _e( 'The city for your billing address.', 'commercestore' ); ?></span>
			<input type="text" id="card_city" name="card_city" class="card-city cs-input<?php if ( cs_field_is_required( 'card_city' ) ) { echo ' required'; } ?>" placeholder="<?php _e( 'City', 'commercestore' ); ?>" value="<?php echo $customer['address']['city']; ?>"<?php if ( cs_field_is_required( 'card_city' ) ) {  echo ' required '; } ?>/>
		</p>
		<p id="cs-card-zip-wrap">
			<label for="card_zip" class="cs-label">
				<?php _e( 'Billing zip/Postal code', 'commercestore' ); ?>
				<?php if ( cs_field_is_required( 'card_zip' ) ) : ?>
					<span class="cs-required-indicator">*</span>
				<?php endif; ?>
			</label>
			<span class="cs-description"><?php _e( 'The zip or postal code for your billing address.', 'commercestore' ); ?></span>
			<input type="text" size="4" id="card_zip" name="card_zip" class="card-zip cs-input<?php if ( cs_field_is_required( 'card_zip' ) ) { echo ' required'; } ?>" placeholder="<?php _e( 'Zip/Postal code', 'commercestore' ); ?>" value="<?php echo $customer['address']['zip']; ?>"<?php if ( cs_field_is_required( 'card_zip' ) ) {  echo ' required '; } ?>/>
		</p>
		<p id="cs-card-country-wrap">
			<label for="billing_country" class="cs-label">
				<?php _e( 'Billing country', 'commercestore' ); ?>
				<?php if ( cs_field_is_required( 'billing_country' ) ) : ?>
					<span class="cs-required-indicator">*</span>
				<?php endif; ?>
			</label>
			<span class="cs-description"><?php _e( 'The country for your billing address.', 'commercestore' ); ?></span>
			<select name="billing_country" id="billing_country" data-nonce="<?php echo wp_create_nonce( 'cs-country-field-nonce' ); ?>" class="billing_country cs-select<?php if ( cs_field_is_required( 'billing_country' ) ) { echo ' required'; } ?>"<?php if ( cs_field_is_required( 'billing_country' ) ) {  echo ' required '; } ?>>
				<?php
				$selected_country = cs_get_shop_country();

				if ( ! empty( $customer['address']['country'] ) && '*' !== $customer['address']['country'] ) {
					$selected_country = $customer['address']['country'];
				}

				$countries = cs_get_country_list();
				foreach ( $countries as $country_code => $country ) {
				  echo '<option value="' . esc_attr( $country_code ) . '"' . selected( $country_code, $selected_country, false ) . '>' . $country . '</option>';
				}
				?>
			</select>
		</p>
		<p id="cs-card-state-wrap">
			<label for="card_state" class="cs-label">
				<?php _e( 'Billing state/Province', 'commercestore' ); ?>
				<?php if ( cs_field_is_required( 'card_state' ) ) { ?>
					<span class="cs-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="cs-description"><?php _e( 'The state or province for your billing address.', 'commercestore' ); ?></span>
			<?php
			$selected_state = cs_get_shop_state();
			$states         = cs_get_shop_states( $selected_country );

			if( ! empty( $customer['address']['state'] ) ) {
				$selected_state = $customer['address']['state'];
			}

			if( ! empty( $states ) ) : ?>
			<select name="card_state" id="card_state" class="card_state cs-select<?php if ( cs_field_is_required( 'card_state' ) ) { echo ' required'; } ?>">
				<?php
					foreach( $states as $state_code => $state ) {
						echo '<option value="' . $state_code . '"' . selected( $state_code, $selected_state, false ) . '>' . $state . '</option>';
					}
				?>
			</select>
			<?php
			else :
				$customer_state = ! empty( $customer['address']['state'] ) ? $customer['address']['state'] : ''; ?>
			<input type="text" size="6" name="card_state" id="card_state" class="card_state cs-input" value="<?php echo esc_attr( $customer_state ); ?>" placeholder="<?php _e( 'State/Province', 'commercestore' ); ?>"/>
			<?php endif; ?>
		</p>
		<?php do_action( 'cs_cc_billing_bottom' ); ?>
		<?php wp_nonce_field( 'cs-checkout-address-fields', 'cs-checkout-address-fields-nonce', false, true ); ?>
	</fieldset>
	<?php
	echo ob_get_clean();
}
add_action( 'cs_after_cc_fields', 'cs_default_cc_address_fields' );


/**
 * Renders the billing address fields for cart taxation.
 *
 * @since 1.6
 */
function cs_checkout_tax_fields() {
	if ( cs_cart_needs_tax_address_fields() && cs_get_cart_total() ) {
		cs_default_cc_address_fields();
	}
}
add_action( 'cs_purchase_form_after_cc_form', 'cs_checkout_tax_fields', 999 );


/**
 * Renders the user registration fields. If the user is logged in, a login
 * form is displayed other a registration form is provided for the user to
 * create an account.
 *
 * @since 1.0
 *
 * @return string
 */
function cs_get_register_fields() {
	$show_register_form = cs_get_option( 'show_register_form', 'none' );

	ob_start(); ?>
	<fieldset id="cs_register_fields">

		<?php if ( 'both' === $show_register_form ) { ?>
			<p id="cs-login-account-wrap"><?php _e( 'Already have an account?', 'commercestore' ); ?> <a href="<?php echo esc_url( add_query_arg( 'login', 1 ) ); ?>" class="cs_checkout_register_login" data-action="checkout_login" data-nonce="<?php echo wp_create_nonce( 'cs_checkout_login' ); ?>"><?php _e( 'Log in', 'commercestore' ); ?></a></p>
		<?php } ?>

		<?php do_action( 'cs_register_fields_before' ); ?>

		<fieldset id="cs_register_account_fields">
			<legend><?php _e( 'Create an account', 'commercestore' ); if( !cs_no_guest_checkout() ) { echo ' ' . __( '(optional)', 'commercestore' ); } ?></legend>
			<?php do_action( 'cs_register_account_fields_before' ); ?>
			<p id="cs-user-login-wrap">
				<label for="cs_user_login">
					<?php _e( 'Username', 'commercestore' ); ?>
					<?php if ( cs_no_guest_checkout() ) : ?>
					<span class="cs-required-indicator">*</span>
					<?php endif; ?>
				</label>
				<span class="cs-description"><?php _e( 'The username you will use to log into your account.', 'commercestore' ); ?></span>
				<input name="cs_user_login" id="cs_user_login" class="<?php if(cs_no_guest_checkout()) { echo sanitize_html_class( 'required ' ); } ?>cs-input" type="text" placeholder="<?php _e( 'Username', 'commercestore' ); ?>"/>
			</p>
			<p id="cs-user-pass-wrap">
				<label for="cs_user_pass">
					<?php _e( 'Password', 'commercestore' ); ?>
					<?php if ( cs_no_guest_checkout() ) : ?>
					<span class="cs-required-indicator">*</span>
					<?php endif; ?>
				</label>
				<span class="cs-description"><?php _e( 'The password used to access your account.', 'commercestore' ); ?></span>
				<input name="cs_user_pass" id="cs_user_pass" class="<?php if(cs_no_guest_checkout()) { echo sanitize_html_class( 'required ' ); } ?>cs-input" placeholder="<?php _e( 'Password', 'commercestore' ); ?>" type="password"/>
			</p>
			<p id="cs-user-pass-confirm-wrap" class="cs_register_password">
				<label for="cs_user_pass_confirm">
					<?php _e( 'Password again', 'commercestore' ); ?>
					<?php if ( cs_no_guest_checkout() ) : ?>
					<span class="cs-required-indicator">*</span>
					<?php endif; ?>
				</label>
				<span class="cs-description"><?php _e( 'Confirm your password.', 'commercestore' ); ?></span>
				<input name="cs_user_pass_confirm" id="cs_user_pass_confirm" class="<?php if ( cs_no_guest_checkout() ) { echo sanitize_html_class( 'required ' ); } ?>cs-input" placeholder="<?php _e( 'Confirm password', 'commercestore' ); ?>" type="password"/>
			</p>
			<?php do_action( 'cs_register_account_fields_after' ); ?>
		</fieldset>

		<?php do_action('cs_register_fields_after'); ?>

		<input type="hidden" name="cs-purchase-var" value="needs-to-register"/>

		<?php do_action( 'cs_purchase_form_user_info' ); ?>
		<?php do_action( 'cs_purchase_form_user_register_fields' ); ?>

	</fieldset>
	<?php
	echo ob_get_clean();
}
add_action( 'cs_purchase_form_register_fields', 'cs_get_register_fields' );

/**
 * Gets the login fields for the login form on the checkout. This function hooks
 * on the cs_purchase_form_login_fields to display the login form if a user already
 * had an account.
 *
 * @since 1.0
 * @return string
 */
function cs_get_login_fields() {
	$color = cs_get_option( 'checkout_color', 'gray' );

	$color = 'inherit' === $color
		? ''
		: $color;

	$style = cs_get_option( 'button_style', 'button' );

	$show_register_form = cs_get_option( 'show_register_form', 'none' );

	ob_start(); ?>
		<fieldset id="cs_login_fields">
			<?php if ( 'both' === $show_register_form ) : ?>
				<p id="cs-new-account-wrap">
					<?php _e( 'Need to create an account?', 'commercestore' ); ?>
					<a href="<?php echo esc_url( remove_query_arg( 'login' ) ); ?>" class="cs_checkout_register_login" data-action="checkout_register"  data-nonce="<?php echo wp_create_nonce( 'cs_checkout_register' ); ?>">
						<?php _e( 'Register', 'commercestore' ); if ( ! cs_no_guest_checkout() ) { echo esc_html( ' ' . __( 'or checkout as a guest', 'commercestore' ) ); } ?>
					</a>
				</p>
			<?php endif; ?>

			<?php do_action( 'cs_checkout_login_fields_before' ); ?>

			<p id="cs-user-login-wrap">
				<label class="cs-label" for="cs_user_login">
					<?php _e( 'Username or email', 'commercestore' ); ?>
					<?php if ( cs_no_guest_checkout() ) : ?>
					<span class="cs-required-indicator">*</span>
					<?php endif; ?>
				</label>
				<input class="<?php if(cs_no_guest_checkout()) { echo sanitize_html_class( 'required ' ); } ?>cs-input" type="text" name="cs_user_login" id="cs_user_login" value="" placeholder="<?php _e( 'Your username or email address', 'commercestore' ); ?>"/>
			</p>
			<p id="cs-user-pass-wrap" class="cs_login_password">
				<label class="cs-label" for="cs_user_pass">
					<?php _e( 'Password', 'commercestore' ); ?>
					<?php if ( cs_no_guest_checkout() ) : ?>
					<span class="cs-required-indicator">*</span>
					<?php endif; ?>
				</label>
				<input class="<?php if ( cs_no_guest_checkout() ) { echo sanitize_html_class( 'required '); } ?>cs-input" type="password" name="cs_user_pass" id="cs_user_pass" placeholder="<?php _e( 'Your password', 'commercestore' ); ?>"/>
				<?php if ( cs_no_guest_checkout() ) : ?>
					<input type="hidden" name="cs-purchase-var" value="needs-to-login"/>
				<?php endif; ?>
			</p>
			<p id="cs-user-login-submit">
				<input type="submit" class="cs-submit <?php echo sanitize_html_class( $color ); ?> <?php echo sanitize_html_class( $style ); ?>" name="cs_login_submit" value="<?php _e( 'Log in', 'commercestore' ); ?>"/>
				<?php wp_nonce_field( 'cs-login-form', 'cs_login_nonce', false, true ); ?>
			</p>

			<?php do_action( 'cs_checkout_login_fields_after' ); ?>
		</fieldset><!--end #cs_login_fields-->
	<?php
	echo ob_get_clean();
}
add_action( 'cs_purchase_form_login_fields', 'cs_get_login_fields' );

/**
 * Renders the payment mode form by getting all the enabled payment gateways and
 * outputting them as radio buttons for the user to choose the payment gateway. If
 * a default payment gateway has been chosen from the CommerceStore Settings, it will be
 * automatically selected.
 *
 * @since 1.2.2
 */
function cs_payment_mode_select() {
	$gateways = cs_get_enabled_payment_gateways( true );
	$page_URL = cs_get_current_page_url();
	$chosen_gateway = cs_get_chosen_gateway();
	?>
	<div id="cs_payment_mode_select_wrap">
		<?php do_action('cs_payment_mode_top'); ?>

		<?php if( cs_is_ajax_disabled() ) { ?>
		<form id="cs_payment_mode" action="<?php echo $page_URL; ?>" method="GET">
		<?php } ?>

			<fieldset id="cs_payment_mode_select">
				<legend><?php _e( 'Select payment method', 'commercestore' ); ?></legend>
				<?php do_action( 'cs_payment_mode_before_gateways_wrap' ); ?>
				<div id="cs-payment-mode-wrap">
					<?php
					do_action( 'cs_payment_mode_before_gateways' );

					foreach ( $gateways as $gateway_id => $gateway ) {
						$label         = apply_filters( 'cs_gateway_checkout_label_' . $gateway_id, $gateway['checkout_label'] );
						$checked       = checked( $gateway_id, $chosen_gateway, false );
						$checked_class = $checked ? ' cs-gateway-option-selected' : '';
						$nonce         = ' data-' . esc_attr( $gateway_id ) . '-nonce="' . wp_create_nonce( 'cs-gateway-selected-' . esc_attr( $gateway_id ) ) .'"';

						echo '<label for="cs-gateway-' . esc_attr( $gateway_id ) . '" class="cs-gateway-option' . $checked_class . '" id="cs-gateway-option-' . esc_attr( $gateway_id ) . '">';
							echo '<input type="radio" name="payment-mode" class="cs-gateway" id="cs-gateway-' . esc_attr( $gateway_id ) . '" value="' . esc_attr( $gateway_id ) . '"' . $checked . $nonce . '>' . esc_html( $label );
						echo '</label>';
					}

					do_action( 'cs_payment_mode_after_gateways' );
					?>
				</div>

				<?php do_action( 'cs_payment_mode_after_gateways_wrap' ); ?>
			</fieldset>

			<fieldset id="cs_payment_mode_submit" class="cs-no-js">
				<p id="cs-next-submit-wrap">
					<?php echo cs_checkout_button_next(); ?>
				</p>
			</fieldset>

		<?php if ( cs_is_ajax_disabled() ) : ?>
		</form>
		<?php endif; ?>

	</div>
	<div id="cs_purchase_form_wrap"></div><!-- the checkout fields are loaded into this-->

	<?php do_action( 'cs_payment_mode_bottom' );
}
add_action( 'cs_payment_mode_select', 'cs_payment_mode_select' );

/**
 * Show Payment Icons by getting all the accepted icons from the CommerceStore Settings
 * then outputting the icons.
 *
 * @since 1.0
 * @return void
*/
function cs_show_payment_icons() {

	if ( cs_show_gateways() && did_action( 'cs_payment_mode_top' ) ) {
		return;
	}

	$payment_methods = cs_get_option( 'accepted_cards', array() );

	if ( empty( $payment_methods ) ) {
		return;
	}

	// Get the icon order option
	$order = cs_get_option( 'payment_icons_order', '' );

	// If order is set, enforce it
	if ( ! empty( $order ) ) {
		$order           = array_flip( explode( ',', $order ) );
		$order           = array_intersect_key( $order, $payment_methods );
		$payment_methods = array_merge( $order, $payment_methods );
	}

	echo '<div class="cs-payment-icons">';

	foreach ( $payment_methods as $key => $option ) {
		if ( cs_string_is_image_url( $key ) ) {
			echo '<img class="payment-icon" src="' . esc_url( $key ) . '" alt="' . esc_attr( $option ) . '"/>';
		} else {
			$type = '';
			$card = strtolower( str_replace( ' ', '', $option ) );

			if ( has_filter( 'cs_accepted_payment_' . $card . '_image' ) ) {
				$image = apply_filters( 'cs_accepted_payment_' . $card . '_image', '' );

			} elseif ( has_filter( 'cs_accepted_payment_' . $key . '_image' ) ) {
				$image = apply_filters( 'cs_accepted_payment_' . $key  . '_image', '' );

			} else {
				// Set the type to SVG.
				$type = 'svg';

				// Get SVG dimensions.
				$dimensions = cs_get_payment_icon_dimensions( $key );

				// Get SVG markup.
				$image = cs_get_payment_icon(
					array(
						'icon'    => $key,
						'width'   => $dimensions['width'],
						'height'  => $dimensions['height'],
						'title'   => $option,
						'classes' => array( 'payment-icon' )
					)
				);
			}

			if ( cs_is_ssl_enforced() || is_ssl() ) {
				$image = cs_enforced_ssl_asset_filter( $image );
			}

			if ( 'svg' === $type ) {
				echo $image;
			} else {
				echo '<img class="payment-icon" src="' . esc_url( $image ) . '" alt="' . esc_attr( $option ) . '"/>';
			}
		}
	}

	echo '</div>';
}
add_action( 'cs_payment_mode_top', 'cs_show_payment_icons' );
add_action( 'cs_checkout_form_top', 'cs_show_payment_icons' );


/**
 * Renders the Discount Code field which allows users to enter a discount code.
 * This field is only displayed if there are any active discounts on the site else
 * it's not displayed.
 *
 * @since 1.2.2
 * @return void
*/
function cs_discount_field() {
	if ( isset( $_GET['payment-mode'] ) && cs_is_ajax_disabled() ) {
		return; // Only show before a payment method has been selected if ajax is disabled
	}

	if ( ! cs_is_checkout() ) {
		return;
	}

	if ( cs_has_active_discounts() && cs_get_cart_total() ) :
		$color = cs_get_option( 'checkout_color', 'blue' );
		$color = ( $color == 'inherit' ) ? '' : $color;
		$style = cs_get_option( 'button_style', 'button' ); ?>
		<fieldset id="cs_discount_code">
			<p id="cs_show_discount" style="display:none;">
				<?php _e( 'Have a discount code?', 'commercestore' ); ?> <a href="#" class="cs_discount_link"><?php echo _x( 'Click to enter it', 'Entering a discount code', 'commercestore' ); ?></a>
			</p>
			<p id="cs-discount-code-wrap" class="cs-cart-adjustment">
				<label class="cs-label" for="cs-discount">
					<?php _e( 'Discount', 'commercestore' ); ?>
				</label>
				<span class="cs-description"><?php _e( 'Enter a discount code if you have one.', 'commercestore' ); ?></span>
				<span class="cs-discount-code-field-wrap">
					<input class="cs-input" type="text" id="cs-discount" name="cs-discount" placeholder="<?php _e( 'Enter discount', 'commercestore' ); ?>"/>
					<input type="submit" class="cs-apply-discount cs-submit <?php echo sanitize_html_class( $color ); ?> <?php echo sanitize_html_class( $style ); ?>" value="<?php echo _x( 'Apply', 'Apply discount at checkout', 'commercestore' ); ?>"/>
				</span>
				<span class="cs-discount-loader cs-loading" id="cs-discount-loader" style="display:none;"></span>
				<span id="cs-discount-error-wrap" class="cs_error cs-alert cs-alert-error" aria-hidden="true" style="display:none;"></span>
			</p>
		</fieldset><?php
	endif;
}
add_action( 'cs_checkout_form_top', 'cs_discount_field', -1 );

/**
 * Renders the Checkout Agree to Terms, this displays a checkbox for users to
 * agree the T&Cs set in the CommerceStore Settings. This is only displayed if T&Cs are
 * set in the CommerceStore Settings.
 *
 * @since 1.3.2
 * @return void
 */
function cs_terms_agreement() {

	/**
	 * No terms agreement output of any kind should ever show unless the checkbox
	 * is present for the customer to check: 'Agree to Terms' setting.
	 */
	if ( cs_get_option( 'show_agree_to_terms', false ) ) {

		$agree_text  = cs_get_option( 'agree_text', '' );
		$agree_label = cs_get_option( 'agree_label', __( 'Agree to Terms?', 'commercestore' ) );

		ob_start();
		?>

		<fieldset id="cs_terms_agreement">

			<?php
			// Show Agreement Text output only if content exists. Remember that the Agree to Terms
			// label supports anchors tags, so the terms may be on a separate page.
			if ( ! empty( $agree_text ) ) {
				?>

				<div id="cs_terms" class="cs-terms" style="display:none;">
					<?php
					do_action( 'cs_before_terms' );
					echo wpautop( stripslashes( $agree_text ) );
					do_action( 'cs_after_terms' );
					?>
				</div>
				<div id="cs_show_terms" class="cs-show-terms">
					<a href="#" class="cs_terms_links"><?php _e( 'Show Terms', 'commercestore' ); ?></a>
					<a href="#" class="cs_terms_links" style="display:none;"><?php _e( 'Hide Terms', 'commercestore' ); ?></a>
				</div>
				<?php
			}
			?>

			<div class="cs-terms-agreement">
				<input name="cs_agree_to_terms" class="required" type="checkbox" id="cs_agree_to_terms" value="1"/>
				<label for="cs_agree_to_terms"><?php echo stripslashes( $agree_label ); ?></label>
			</div>
		</fieldset>

		<?php
		$html_output = ob_get_clean();

		echo apply_filters( 'cs_checkout_terms_agreement_html', $html_output );
	}
}
add_action( 'cs_purchase_form_before_submit', 'cs_terms_agreement' );

/**
 * Renders the Checkout Agree to Privacy Policy, this displays a checkbox for users to
 * agree the Privacy Policy set in the CommerceStore Settings. This is only displayed if T&Cs are
 * set in the CommerceStore Settings.
 *
 * @since 2.9.1
 * @return void
 */
function cs_privacy_agreement() {

	$show_privacy_policy_checkbox = cs_get_option( 'show_agree_to_privacy_policy', false );
	$show_privacy_policy_text     = cs_get_option( 'show_privacy_policy_on_checkout', false );

	/**
	 * Privacy Policy output has dual functionality, unlike Agree to Terms output:
	 *
	 * 1. A checkbox (and associated label) can show on checkout if the 'Agree to Privacy Policy' setting
	 *    is checked. This is because a Privacy Policy can be agreed upon without displaying the policy
	 *    itself. Keep in mind the label field supports anchor tags, so the policy can be linked to.
	 *
	 * 2. The Privacy Policy text, which is post_content pulled from the WP core Privacy Policy page when
	 *    you have the 'Show the Privacy Policy on checkout' setting checked, can be displayed on checkout
	 *    regardless of whether or not the customer has to explicitly agreed to the policy by checking the
	 *    checkbox from point #1 above.
	 *
	 * Because these two display options work independently, having either setting checked triggers output.
	 */
	if ( '1' === $show_privacy_policy_checkbox || '1' === $show_privacy_policy_text ) {

		$agree_label  = cs_get_option( 'privacy_agree_label', __( 'Agree to Privacy Policy?', 'commercestore' ) );
		$privacy_page = get_option( 'wp_page_for_privacy_policy' );
		$privacy_text = get_post_field( 'post_content', $privacy_page );

		ob_start();
		?>

		<fieldset id="cs-privacy-policy-agreement">

			<?php
			// Show Privacy Policy text if the setting is checked, the WP Privacy Page is set, and content exists.
			if ( '1' === $show_privacy_policy_text && ( $privacy_page && ! empty( $privacy_text ) ) ) {
				?>
				<div id="cs-privacy-policy" class="cs-terms" style="display:none;">
					<?php
					do_action( 'cs_before_privacy_policy' );
					echo wpautop( do_shortcode( stripslashes( $privacy_text ) ) );
					do_action( 'cs_after_privacy_policy' );
					?>
				</div>
				<div id="cs-show-privacy-policy" class="cs-show-terms">
					<a href="#"
					   class="cs_terms_links"><?php _e( 'Show Privacy Policy', 'commercestore' ); ?></a>
					<a href="#" class="cs_terms_links"
					   style="display:none;"><?php _e( 'Hide Privacy Policy', 'commercestore' ); ?></a>
				</div>
				<?php
			}

			// Show Privacy Policy checkbox and label if the setting is checked.
			if ( '1' === $show_privacy_policy_checkbox ) {
				?>
				<div class="cs-privacy-policy-agreement">
					<input name="cs_agree_to_privacy_policy" class="required" type="checkbox" id="cs-agree-to-privacy-policy" value="1"/>
					<label for="cs-agree-to-privacy-policy"><?php echo stripslashes( $agree_label ); ?></label>
				</div>
				<?php
			}
			?>

		</fieldset>

		<?php
		$html_output = ob_get_clean();

		echo apply_filters( 'cs_checkout_privacy_policy_agreement_html', $html_output );
	}
}
add_action( 'cs_purchase_form_before_submit', 'cs_privacy_agreement' );

/**
 * Shows the final purchase total at the bottom of the checkout page.
 *
 * @since 1.5
 */
function cs_checkout_final_total() {
?>
<p id="cs_final_total_wrap">
	<strong><?php _e( 'Purchase Total:', 'commercestore' ); ?></strong>
	<span class="cs_cart_amount" data-subtotal="<?php echo cs_get_cart_subtotal(); ?>" data-total="<?php echo cs_get_cart_total(); ?>"><?php cs_cart_total(); ?></span>
</p>
<?php
}
add_action( 'cs_purchase_form_before_submit', 'cs_checkout_final_total', 999 );

/**
 * Renders the Checkout Submit section.
 *
 * @since 1.3.3
 */
function cs_checkout_submit() {
?>
	<fieldset id="cs_purchase_submit">
		<?php do_action( 'cs_purchase_form_before_submit' ); ?>

		<?php cs_checkout_hidden_fields(); ?>

		<?php echo cs_checkout_button_purchase(); ?>

		<?php do_action( 'cs_purchase_form_after_submit' ); ?>

		<?php if ( cs_is_ajax_disabled() ) : ?>
			<p class="cs-cancel"><a href="<?php echo cs_get_checkout_uri(); ?>"><?php _e( 'Go back', 'commercestore' ); ?></a></p>
		<?php endif; ?>
	</fieldset>
<?php
}
add_action( 'cs_purchase_form_after_cc_form', 'cs_checkout_submit', 9999 );

/**
 * Renders the Next button on the Checkout
 *
 * @since 1.2
 * @return string
 */
function cs_checkout_button_next() {
	$color = cs_get_option( 'checkout_color', 'blue' );
	$color = ( $color == 'inherit' ) ? '' : $color;
	$style = cs_get_option( 'button_style', 'button' );
	$purchase_page = cs_get_option( 'purchase_page', '0' );

	ob_start(); ?>
	<input type="hidden" name="cs_action" value="gateway_select" />
	<input type="hidden" name="page_id" value="<?php echo absint( $purchase_page ); ?>"/>
	<input type="submit" name="gateway_submit" id="cs_next_button" class="cs-submit <?php echo sanitize_html_class( $color ); ?> <?php echo sanitize_html_class( $style ); ?>" value="<?php _e( 'Next', 'commercestore' ); ?>"/>

	<?php
	return apply_filters( 'cs_checkout_button_next', ob_get_clean() );
}

/**
 * Renders the Purchase button on the Checkout
 *
 * @since 1.2
 * @return string
 */
function cs_checkout_button_purchase() {

	ob_start();

	$enabled_gateways = cs_get_enabled_payment_gateways();
	$cart_total       = cs_get_cart_total();

	if ( ! empty( $enabled_gateways ) || empty( $cart_total ) ) {
		$color = cs_get_option( 'checkout_color', 'blue' );
		$color = ( $color == 'inherit' ) ? '' : $color;
		$style = cs_get_option( 'button_style', 'button' );
		$label = cs_get_checkout_button_purchase_label();

		?>
		<input type="submit" class="cs-submit <?php echo sanitize_html_class( $color ); ?> <?php echo sanitize_html_class( $style ); ?>" id="cs-purchase-button" name="cs-purchase" value="<?php echo $label; ?>"/>
		<?php
	}

	return apply_filters( 'cs_checkout_button_purchase', ob_get_clean() );
}

/**
 * Retrieves the label for the purchase button.
 *
 * @since 2.7.6
 *
 * @return string Purchase button label.
 */
function cs_get_checkout_button_purchase_label() {
	if ( cs_get_cart_total() ) {
		$label             = cs_get_option( 'checkout_label', '' );
		$complete_purchase = ! empty( $label )
			? $label
			: __( 'Purchase', 'commercestore' );
	} else {
		$label             = cs_get_option( 'free_checkout_label', '' );
		$complete_purchase = ! empty( $label )
			? $label
			: __( 'Free Download', 'commercestore' );
	}

	return apply_filters( 'cs_get_checkout_button_purchase_label', $complete_purchase, $label );
}

/**
 * Renders the hidden Checkout fields
 *
 * @since 1.3.2
 */
function cs_checkout_hidden_fields() {
	if ( is_user_logged_in() ) : ?>
	<input type="hidden" name="cs-user-id" value="<?php echo get_current_user_id(); ?>"/>
	<?php endif; ?>
	<input type="hidden" name="cs_action" value="purchase"/>
	<input type="hidden" name="cs-gateway" value="<?php echo cs_get_chosen_gateway(); ?>" />
	<?php wp_nonce_field( 'cs-process-checkout', 'cs-process-checkout-nonce', false, true );
}

/**
 * Applies filters to the success page content.
 *
 * @since 1.0
 *
 * @param string $content Content before filters.
 * @return string $content Filtered content.
 */
function cs_filter_success_page_content( $content ) {
	if ( isset( $_GET['payment-confirmation'] ) && cs_is_success_page() ) {
		if ( has_filter( 'cs_payment_confirm_' . $_GET['payment-confirmation'] ) ) {
			$content = apply_filters( 'cs_payment_confirm_' . $_GET['payment-confirmation'], $content );
		}
	}

	return $content;
}
add_filter( 'the_content', 'cs_filter_success_page_content', 99999 );

/**
 * Show a download's files in the purchase receipt.
 *
 * @since 1.8.6
 *
 * @param  int                          $item_id      Download ID.
 * @param  array                        $receipt_args Args specified in the [cs_receipt] shortcode.
 * @param  \CS\Orders\Order_Item|array $order_item   Order item object or cart item array.
 *
 * @return bool True if files should be shown, false otherwise.
 */
function cs_receipt_show_download_files( $item_id, $receipt_args, $order_item = array() ) {
	$ret = true;

	/*
	 * If re-download is disabled, set return to false.
	 *
	 * When the purchase session is still present AND the receipt being shown is for that purchase,
	 * file download links are still shown. Once session expires, links are disabled.
	 */
	if ( cs_no_redownload() ) {
		$key = isset( $_GET['payment_key'] )
			? sanitize_text_field( $_GET['payment_key'] )
			: '';

		$session = cs_get_purchase_session();

		// We have session data but the payment key provided is not for this session.
		if ( ! empty( $key ) && ! empty( $session ) && $key != $session['purchase_key'] ) {
			$ret = false;

		// No session data is present but a key has been provided.
		} elseif ( empty( $session ) ) {
			$ret = false;
		}
	}

	if ( has_filter( 'cs_receipt_show_download_files' ) ) {
		$item = $order_item;
		if ( ! empty( $order_item->order_id ) ) {
			$order = cs_get_order_by( 'id', $order_item->order_id );
			$cart  = cs_get_payment_meta_cart_details( $order->id, true );
			$item  = $cart[ $item->cart_index ];
		}
		$ret = apply_filters( 'cs_receipt_show_download_files', $ret, $item_id, $receipt_args, $item );
	}

	// If the $order_item is an array, get the order item object instead.
	if ( is_array( $order_item ) && ! empty( $order_item['order_item_id'] ) ) {
		$order_item = cs_get_order_item( $order_item['order_item_id'] );
	}

	/**
	 * Modifies whether the receipt should show download files.
	 *
	 * @since 3.0
	 * @param bool                   $ret          True if the download files should be shown.
	 * @param int                    $item_id      The download ID.
	 * @param array                  $receipt_args Args specified in the [cs_receipt] shortcode.
	 * @param \CS\Orders\Order_Item $item        The order item object.
	 */
	return apply_filters( 'cs_order_receipt_show_download_files', $ret, $item_id, $receipt_args, $order_item );
}
