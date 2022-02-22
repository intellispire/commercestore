<?php
/**
 * Plugin Name: Easy Digital Downloads - Auto Register
 * Plugin URI:  https://commercestore.com/downloads/auto-register/
 * Description: Automatically creates a WP user account at checkout, based on customer's email address.
 * Version:     1.3.14
 * Author:      Sandhills Development, LLC
 * Author URI:  https://sandhillsdev.com
 * Text Domain: cs-auto-register
 * Domain Path: languages
 * License:     GPL-2.0+
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'CS_Auto_Register' ) ) {

	final class CS_Auto_Register {

		/**
		 * Holds the instance
		 *
		 * Ensures that only one instance of CS Auto Register exists in memory at any one
		 * time and it also prevents needing to define globals all over the place.
		 *
		 * TL;DR This is a static property property that holds the singleton instance.
		 *
		 * @var object
		 * @static
		 * @since 1.0
		 */
		private static $instance;

		/**
		 * Main Instance
		 *
		 * Ensures that only one instance exists in memory at any one
		 * time. Also prevents needing to define globals all over the place.
		 *
		 * @since 1.0
		 *
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) && ! ( self::$instance instanceof CS_Auto_Register ) ) {
				self::$instance = new CS_Auto_Register;
				self::$instance->setup_globals();
				self::$instance->hooks();
			}

			return self::$instance;
		}

		/**
		 * Constructor Function
		 *
		 * @since 1.0
		 * @access private
		 */
		private function __construct() {
			self::$instance = $this;

		}

		/**
		 * Reset the instance of the class
		 *
		 * @since 1.0
		 * @access public
		 * @static
		 */
		public static function reset() {
			self::$instance = null;
		}

		/**
		 * Globals
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		private function setup_globals() {

			$this->version    = '1.3.14';

			// paths
			$this->file         = __FILE__;
			$this->basename     = apply_filters( 'cs_auto_register_plugin_basenname', plugin_basename( $this->file ) );
			$this->plugin_dir   = apply_filters( 'cs_auto_register_plugin_dir_path',  plugin_dir_path( $this->file ) );
			$this->plugin_url   = apply_filters( 'cs_auto_register_plugin_dir_url',   plugin_dir_url( $this->file ) );

		}

		/**
		 * Setup the default hooks and actions
		 *
		 * @since 1.0
		 *
		 * @return void
		 */
		private function hooks() {

			if ( ! class_exists( 'CS_Customer' ) ) {

				add_action( 'admin_notices', array( $this, 'admin_notices' ) );

				if( function_exists( 'cs_debug_log') ) {

					cs_debug_log( 'Auto Register: Not loaded, CS_Customer class is not available.' );

				}

				return;
			}

			// Force guest checkout to be enabled
			add_filter( 'cs_get_option_logged_in_only', '__return_false' );

			// Return if guest checkout is disabled
			if ( cs_no_guest_checkout() || apply_filters( 'cs_auto_register_disable', false ) ) {
				return;
			}

			// text domain
			add_action( 'after_setup_theme', array( $this, 'load_textdomain' ) );

			// add settings
			add_filter( 'cs_settings_sections_extensions', array( $this, 'settings_section' ) );
			add_filter( 'cs_settings_extensions', array( $this, 'settings' ) );

			// can the customer checkout?
			add_filter( 'cs_can_checkout', array( $this, 'can_checkout' ) );

			// create user when purchase is created
			add_action( 'cs_payment_saved', array( $this, 'maybe_insert_user' ), 10, 2 );

			// add our new email notifications
			add_action( 'cs_auto_register_insert_user', array( $this, 'email_notifications' ), 10, 3 );

			// Ensure registration form is never shown
			add_filter( 'cs_get_option_show_register_form', array( $this, 'remove_register_form' ), 10, 3 );

			do_action( 'cs_auto_register_setup_actions' );
		}

		/**
		 * Admin notices
		 *
		 * @since 1.0
		 */
		public function admin_notices() {
			echo '<div class="error"><p>' . __( 'CS Auto Register requires Easy Digital Downloads Version 2.3 or greater. Please update or install Easy Digital Downloads.', 'commercestore' ) . '</p></div>';
		}


		/**
		 * Loads the plugin language files
		 *
		 * @access public
		 * @since 1.0
		 * @return void
		 */
		public function load_textdomain() {
			// Set filter for plugin's languages directory
			$lang_dir = dirname( plugin_basename( $this->file ) ) . '/languages/';
			$lang_dir = apply_filters( 'cs_auto_register_languages_directory', $lang_dir );

			// Traditional WordPress plugin locale filter
			$locale        = apply_filters( 'plugin_locale',  get_locale(), 'cs-auto-register' );
			$mofile        = sprintf( '%1$s-%2$s.mo', 'cs-auto-register', $locale );

			// Setup paths to current locale file
			$mofile_local  = $lang_dir . $mofile;
			$mofile_global = WP_LANG_DIR . '/cs-auto-register/' . $mofile;

			if ( file_exists( $mofile_global ) ) {
				// Look in global /wp-content/languages/cs-auto-register folder
				load_textdomain( 'cs-auto-register', $mofile_global );
			} elseif ( file_exists( $mofile_local ) ) {
				// Look in local /wp-content/plugins/cs-auto-register/languages/ folder
				load_textdomain( 'cs-auto-register', $mofile_local );
			} else {
				// Load the default language files
				load_plugin_textdomain( 'cs-auto-register', false, $lang_dir );
			}
		}


		/**
		 * Notifications
		 * Sends the user an email with their logins details and also sends the site admin an email notifying them of a signup
		 *
		 * @since 1.1
		 */
		public function email_notifications( $user_id = 0, $user_data = array() ) {

			$user = get_userdata( $user_id );

			$user_email_disabled  = cs_get_option( 'cs_auto_register_disable_user_email', '' );
			$admin_email_disabled = cs_get_option( 'cs_auto_register_disable_admin_email', '' );

			// The blogname option is escaped with esc_html on the way into the database in sanitize_option
			// we want to reverse this for the plain text arena of emails.
			$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

			$message  = sprintf( __( 'New user registration on your site %s:', 'commercestore' ), $blogname ) . "\r\n\r\n";
			$message .= sprintf( __( 'Username: %s', 'commercestore' ), $user->user_login ) . "\r\n\r\n";
			$message .= sprintf( __( 'E-mail: %s', 'commercestore' ), $user->user_email ) . "\r\n";

			if ( ! $admin_email_disabled ) {
				@wp_mail( get_option( 'admin_email' ), sprintf( __( '[%s] New User Registration', 'commercestore' ), $blogname ), $message );
			}

			// user registration
			if ( empty( $user_data['user_pass'] ) ) {
				return;
			}

			// message
			$message = $this->get_email_body_content( $user_data['first_name'], sanitize_user( $user_data['user_login'], true ), $user_data['user_pass'] );

			// subject line
			$subject = apply_filters( 'cs_auto_register_email_subject', sprintf( __( '[%s] Your username and password', 'commercestore' ), $blogname ) );

			// get from name and email from CS options
			$from_name  = cs_get_option( 'from_name', get_bloginfo( 'name' ) );
			$from_email = cs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );

			$headers = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
			$headers .= "Reply-To: ". $from_email . "\r\n";
			$headers = apply_filters( 'cs_auto_register_headers', $headers );

			$emails = new CS_Emails;

			$emails->__set( 'from_name', $from_name );
			$emails->__set( 'from_email', $from_email );
			$emails->__set( 'headers', $headers );

			// Email the user
			if ( ! $user_email_disabled ) {
				$emails->send( $user_data['user_email'], $subject, $message );
			}

		}

		/**
		 * Email Template Body
		 *
		 * @since 1.0
		 * @return string $default_email_body Body of the email
		 */
		public function get_email_body_content( $first_name, $username, $password ) {

			// Email body
			$default_email_body = __( "Dear", 'commercestore' ) . ' ' . $first_name . ",\n\n";
			$default_email_body .= __( "Below are your login details:", 'commercestore' ) . "\n\n";
			$default_email_body .= __( "Your Username:", 'commercestore' ) . ' ' . $username . "\n\n";
			$default_email_body .= __( "Your Password:", 'commercestore' ) . ' ' . $password . "\n\n";
			$default_email_body .= __( "Login:", 'commercestore' ) . ' ' . wp_login_url() . "\r\n";

			$default_email_body = apply_filters( 'cs_auto_register_email_body', $default_email_body, $first_name, $username, $password );

			return $default_email_body;
		}

		/**
		 * Can checkout?
		 * Prevents the form from being displayed when User must be logged in (Guest Checkout disabled), but "Show Register / Login Form?" is not
		 *
		 * @since 1.0
		 */
		public function can_checkout( $can_checkout ) {

			if ( cs_no_guest_checkout() && ! cs_get_option( 'show_register_form' ) && ! is_user_logged_in() ) {
				return false;
			}

			return $can_checkout;
		}

		/**
		 * When a payment is inserted, possibly registers a user
		 *
		 * If this is the first purchase, disables the CS Core user verification system
		 *
		 * @since 1.3
		 */
		public function maybe_insert_user( $payment_id, $payment ) {

			// stop CS from sending new user notification, we want to customize this a bit
			remove_action( 'cs_insert_user', 'cs_new_user_notification', 10, 2 );

			cs_debug_log( 'CSAR: maybe_insert_user running...' );
			cs_debug_log( 'Payment: ' . print_r( $payment, true ) );

			// This function only creates users using a Payment. If the payment ID is empty, we can't do that.
			if ( empty( $payment->ID ) ) {
				return false;
			}

			// If the user is not logged in
			if ( ! is_user_logged_in() ) {

				$customer    = new CS_Customer( $payment->email );
				$payment_ids = explode( ',', $customer->payment_ids );

				if ( is_array( $payment_ids ) && ! empty( $payment_ids ) ) {

					$payment_ids = array_map( 'absint', $payment_ids );

					// If the payment inserted is the only payment, we don't need verification
					if ( 1 === count( $payment_ids ) && in_array( $payment_id, $payment_ids ) ) {
						remove_action( 'user_register', 'cs_connect_existing_customer_to_new_user', 10, 1 );
						remove_action( 'user_register', 'cs_add_past_purchases_to_new_user', 10, 1 );
					}

				}

				// We will manually re-build the purchase_data array the way that create_user expects it.
				// We have to do it this way, instead of passing a payment ID, because a payment ID may not yet exist, as is the case for recurring.
				$purchase_data = array(
					'price'        => $payment->total,
					'date'         => $payment->date,
					'user_email'   => $payment->email,
					'purchase_key' => $payment->key,
					'currency'     => $payment->currency,
					'downloads'    => $payment->downloads,
					'user_info' => array(
						'id'         => $payment->user_id,
						'email'      => $payment->email,
						'first_name' => $payment->first_name,
						'last_name'  => $payment->last_name,
						'discount'   => $payment->discounts,
						'address'    => $payment->address,
					),
					'cart_details' => $payment->cart_details,
					'status'       => $payment->status,
					'fees'         => $payment->fees,
				);

				$this->create_user( $purchase_data );

			} else {

				if( function_exists( 'did_action' ) && ! did_action( 'cs_create_payment' ) ) {

					// Don't use the current user ID when creating payments through Manual Purchases
					$user_id = get_current_user_id();

				}
			}

			// Validate inserted user
			if ( empty( $user_id ) || is_wp_error( $user_id ) ) {
				return;
			}

			$payment_meta = cs_get_payment_meta( $payment_id );

			$payment_meta['user_info']['id'] = $user_id;

			cs_update_payment_meta( $payment_id, '_cs_payment_user_id', $user_id );
			cs_update_payment_meta( $payment_id, '_cs_payment_meta', $payment_meta );

		}

		/**
		 * Processes the supplied payment data to possibly register a user
		 *
		 * @since  1.3.3
		 * @param  array   $payment_data The Payment data
		 * @param  int     $payment_id   The payment ID
		 * @return int|WP_Error          The User ID created or an instance of WP_Error if the insert fails
		 */
		public function create_user( $payment_data = array(), $payment_id = 0 ) {

			// User account already associated
			if ( $payment_data['user_info']['id'] > 0 ) {
				return false;
			}

			// User account already exists
			if ( get_user_by( 'email', $payment_data['user_info']['email'] ) ) {
				return false;
			}

			$user_name = sanitize_user( $payment_data['user_info']['email'] );

			// Username already exists
			if ( username_exists( $user_name ) ) {
				return false;
			}

			// Okay we need to create a user and possibly log them in

			// Since this filter existed before, we must send in a $payment_id, which we default to false if none is supplied
			$user_args = apply_filters( 'cs_auto_register_insert_user_args', array(
				'user_login'      => $user_name,
				'user_pass'       => wp_generate_password( 32 ),
				'user_email'      => $payment_data['user_info']['email'],
				'first_name'      => $payment_data['user_info']['first_name'],
				'last_name'       => $payment_data['user_info']['last_name'],
				'user_registered' => date( 'Y-m-d H:i:s' ),
				'role'            => get_option( 'default_role' )
			), $payment_id, $payment_data );

			// Insert new user
			$user_id = wp_insert_user( $user_args );

			if ( ! is_wp_error( $user_id ) ) {

				// Allow themes and plugins to hook
				do_action( 'cs_auto_register_insert_user', $user_id, $user_args, $payment_id );

				$maybe_login_user = function_exists( 'did_action' ) && ( did_action( 'cs_purchase' ) || did_action( 'cs_straight_to_gateway' ) || did_action( 'cs_free_download_process' ) );
				$maybe_login_user = apply_filters( 'cs_auto_register_login_user', $maybe_login_user );

				if ( true === $maybe_login_user ) {

					cs_log_user_in( $user_id, $user_args['user_login'], $user_args['user_pass'] );

				}

				$customer    = new CS_Customer( $payment_data['user_info']['email'] );
				$customer->update( array( 'user_id' => $user_id ) );
			}

			return $user_id;
		}

		/**
		 * Add a settings section
		 *
		 * @param array $sections
		 *
		 * @since 1.3.14
		 * @return array
		 */
		public function settings_section( $sections ) {
			$sections['auto_register'] = __( 'Auto Register', 'commercestore' );

			return $sections;
		}


		/**
		 * Settings
		 *
		 * @since 1.1
		 */
		public function settings( $settings ) {
			$cs_ar_settings = array(
				array(
					'id' => 'cs_auto_register_disable_user_email',
					'name' => __( 'Disable User Email', 'commercestore' ),
					'desc' => __( 'Disables the email sent to the user that contains login details', 'commercestore' ),
					'type' => 'checkbox',
				),
				array(
					'id' => 'cs_auto_register_disable_admin_email',
					'name' => __( 'Disable Admin Notification', 'commercestore' ),
					'desc' => __( 'Disables the new user registration email sent to the admin', 'commercestore' ),
					'type' => 'checkbox',
				),
			);

			if ( version_compare( CS_VERSION, 2.5, '>=' ) ) {
				$cs_ar_settings = array( 'auto_register' => $cs_ar_settings );
			}

			return array_merge( $settings, $cs_ar_settings );
		}

		/**
		 * Hide the registration form on checkout
		 *
		 * @since 1.3
		 */
		public function remove_register_form( $value, $key, $default ) {

			if ( 'both' === $value ){
				$value = 'login';
			} elseif ( 'registration' === $value ) {
				$value = 'none';
			}

			return $value;
		}

	}
}

/**
 * Loads a single instance of CS Auto Register
 *
 * This follows the PHP singleton design pattern.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * @example <?php $cs_auto_register = cs_auto_register(); ?>
 *
 * @since 1.0
 *
 * @see CS_Auto_Register::get_instance()
 *
 * @return object Returns an instance of the CS_Auto_Register class
 */
function cs_auto_register() {
	return CS_Auto_Register::get_instance();
}

/**
 * Loads plugin after all the others have loaded and have registered their hooks and filters
 *
 * @since 1.0
 */
add_action( 'plugins_loaded', 'cs_auto_register', apply_filters( 'cs_auto_register_action_priority', 10 ) );
