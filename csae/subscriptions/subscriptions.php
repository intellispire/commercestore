<?php
/**
 * Plugin Name: Easy Digital Downloads - Recurring Payments
 * Plugin URI: https://commercestore.com/downloads/cs-recurring/
 * Description: Sell subscriptions with Easy Digital Downloads
 * Author: Easy Digital Downloads
 * Author URI: https://commercestore.com
 * Version: 2.11.6
 * Text Domain: cs-recurring
 * Domain Path: languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CS_RECURRING_STORE_API_URL', 'https://commercestore.com' );
define( 'CS_RECURRING_PRODUCT_NAME', 'Recurring Payments' );

if ( ! defined( 'CS_RECURRING_PLUGIN_DIR' ) ) {
	define( 'CS_RECURRING_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'CS_RECURRING_PLUGIN_URL' ) ) {
	define( 'CS_RECURRING_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'CS_RECURRING_PLUGIN_FILE' ) ) {
	define( 'CS_RECURRING_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'CS_RECURRING_VERSION' ) ) {
	define( 'CS_RECURRING_VERSION', '2.11.6' );
}

final class CS_Recurring {


	/** Singleton *************************************************************/

	/**
	 * @var CS_Recurring The one true CS_Recurring
	 */
	private static $instance;

	static $plugin_path;
	static $plugin_dir;

	public static $gateways = array();


	/**
	 * @var CS_Recurring_Customer
	 */
	public static $customers;

	/**
	 * @var CS_Recurring_Content_Restriction
	 */
	public static $content_restriction;

	/**
	 * @var CS_Recurring_Software_Licensing
	 */
	public static $software_licensing;

	/**
	 * @var CS_Recurring_Auto_Register
	 */
	public static $auto_register;

	/**
	 * @var CS_Recurring_Invoices
	 */
	public static $invoices;

	/**
	 * @var CS_Recurring_Fraud_Monitor
	 */
	public static $fraud_monitor;

	/**
	 * @var CS_Recurring_Reminders
	 */
	public static $reminders;

	/**
	 * @var CS_Recurring_Emails
	 */
	public static $emails;

	/**
	 * @var CS_Recurring_Cron
	 */
	public static $cron;

	/**
	 * @var CS_Subscriptions_API
	 */
	public static $api;

	/**
	 * @var CS_Recurring_Checkout
	 */
	public static $checkout;

	/**
	 * Main CS_Recurring Instance
	 *
	 * Insures that only one instance of CS_Recurring exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since     v1.0
	 * @staticvar array $instance
	 * @uses      CS_Recurring::setup_globals() Setup the globals needed
	 * @uses      CS_Recurring::includes() Include the required files
	 * @uses      CS_Recurring::setup_actions() Setup the hooks and actions
	 * @see       CS()
	 * @return CS_Recurring The one true CS_Recurring
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new CS_Recurring;

			self::$plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );
			self::$plugin_dir  = untrailingslashit( plugin_dir_url( __FILE__ ) );

			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Constructor -- prevent new instances
	 *
	 * @since 2.4.1
	 */
	private function __construct(){
		//You shall not pass.
	}

	/**
	 * Get things started
	 *
	 * Sets up globals, loads text domain, loads includes, inits actions and filters, starts customer class
	 *
	 * @since v1.0
	 */
	function init() {

		self::includes_global();

		if ( is_admin() ) {
			self::includes_admin();
		}

		if ( CS_RECURRING_VERSION != get_option( 'cs_recurring_version' ) ) {
			cs_recurring_install();
		}

		self::load_textdomain();

		self::actions();
		self::filters();

		self::$customers           = new CS_Recurring_Customer();
		self::$content_restriction = new CS_Recurring_Content_Restriction();
		self::$software_licensing  = new CS_Recurring_Software_Licensing();
		self::$auto_register       = new CS_Recurring_Auto_Register();
		self::$invoices            = new CS_Recurring_Invoices();
		self::$fraud_monitor       = new CS_Recurring_Fraud_Monitor();
		self::$api                 = new CS_Subscriptions_API();
		self::$reminders           = new CS_Recurring_Reminders();
		self::$emails              = new CS_Recurring_Emails();
		self::$cron                = new CS_Recurring_Cron();
		self::$checkout            = new CS_Recurring_Checkout();

		self::$gateways = array(
			'2checkout'        => 'CS_Recurring_2Checkout',
			'2checkout_onsite' => 'CS_Recurring_2Checkout_Onsite',
			'authorize'        => 'CS_Recurring_Authorize',
			'manual'           => 'CS_Recurring_Manual_Payments',
			'paypal'           => 'CS_Recurring_PayPal',
			'paypalexpress'    => 'CS_Recurring_PayPal_Express',
			'paypalpro'        => 'CS_Recurring_PayPal_Website_Payments_Pro',
			'paypal_commerce'  => 'CS_Recurring_PayPal_Commerce',
			'stripe'           => 'CS_Recurring_Stripe',
		);

	}


	/**
	 * Load global files
	 *
	 * @since  1.0
	 * @return void
	 */
	public function includes_global() {
		$files = array(
			'cs-subscriptions-db.php',
			'cs-subscription.php',
			'cs-subscriptions-api.php',
			'cs-recurring-cron.php',
			'cs-recurring-subscriber.php',
			'cs-recurring-shortcodes.php',
			'gateways/cs-recurring-gateway.php',
			'plugin-content-restriction.php',
			'cs-recurring-checkout.php',
			'cs-recurring-emails.php',
			'cs-recurring-reminders.php',
			'plugin-software-licensing.php',
			'plugin-auto-register.php',
			'plugin-invoices.php',
			'plugin-fraud-monitor.php',
			'deprecated/cs-recurring-customer.php',
			'logging.php',
			'functions.php',
		);

		//Load main files
		foreach ( $files as $file ) {
			require( sprintf( '%s/includes/%s', self::$plugin_path, $file ) );
		}

		//Load gateway functions
		foreach ( cs_get_payment_gateways() as $key => $gateway ) {
			$potential_files = array(
				CS_RECURRING_PLUGIN_DIR . 'includes/gateways/' . $key . '/functions.php',
				CS_RECURRING_PLUGIN_DIR . 'includes/gateways/' . str_replace( '_', '-', $key ) . '/functions.php'
			);

			foreach ( $potential_files as $file_path ) {
				if ( file_exists( $file_path ) ) {
					require_once $file_path;
				}
			}
		}

		/*
		 * Make sure PayPal functions are always loaded.
		 * In CommerceStore <2.11 this wasn't necessary because `paypal` was always available as a gateway.
		 * In CommerceStore 2.11, the new gateway is `paypal_commerce`, which means this file wasn't getting loaded.
		 */
		if ( ! function_exists( 'cs_recurring_get_paypal_api_credentials' ) ) {
			require_once CS_RECURRING_PLUGIN_DIR . 'includes/gateways/paypal/functions.php';
		}

		//Load gateway classes
		foreach ( cs_get_payment_gateways() as $gateway_id => $gateway ) {
			if( file_exists( sprintf( '%s/includes/gateways/cs-recurring-%s.php', self::$plugin_path, $gateway_id ) ) ) {
				require( sprintf( '%s/includes/gateways/cs-recurring-%s.php', self::$plugin_path, $gateway_id ) );
			}
		}

	}

	/**
	 * Load admin files
	 *
	 * @since  1.0
	 * @return void
	 */
	public function includes_admin() {
		$files = array(
			'upgrade-functions.php',
			'customers.php',
			'class-admin-notices.php',
			'class-subscriptions-list-table.php',
			'class-summary-widget.php',
			'class-recurring-reports.php',
			'reports/class-recurring-reports-chart.php',
			'reports/report-data-callbacks.php',
			'subscriptions.php',
			'metabox.php',
			'refunds.php',
			'settings.php',
			'scripts.php',
			'class-reports-filters.php',
		);

		foreach ( $files as $file ) {
			require_once( sprintf( '%s/includes/admin/%s', self::$plugin_path, $file ) );
		}
	}

	/**
	 * Loads the plugin language files
	 *
	 * @since  v1.0
	 * @access private
	 * @uses   dirname()
	 * @uses   plugin_basename()
	 * @uses   apply_filters()
	 * @uses   load_textdomain()
	 * @uses   get_locale()
	 * @uses   load_plugin_textdomain()
	 *
	 */
	private function load_textdomain() {

		// Set filter for plugin's languages directory
		$cs_lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
		$cs_lang_dir = apply_filters( 'cs_languages_directory', $cs_lang_dir );


		// Traditional WordPress plugin locale filter
		$locale = apply_filters( 'plugin_locale', get_locale(), 'cs-recurring' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'cs-recurring', $locale );

		// Setup paths to current locale file
		$mofile_local  = $cs_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/cs-recurring/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/cs-recurring folder
			load_textdomain( 'cs-recurring', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/cs-recurring/languages/ folder
			load_textdomain( 'cs-recurring', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'cs-recurring', false, $cs_lang_dir );
		}

	}


	/**
	 * Add our actions
	 *
	 * @since  1.0
	 * @return void
	 */
	private function actions() {

		// @todo The `elseif` can be removed once CommerceStore minimum is 2.11.4.
		if ( class_exists( '\\CS\\Extensions\\ExtensionRegistry' ) ) {
			add_action( 'cs_extension_license_init', function( \CS\Extensions\ExtensionRegistry $registry ) {
				$registry->addExtension( __FILE__, CS_RECURRING_PRODUCT_NAME, 28530, CS_RECURRING_VERSION, 'recurring_license_key' );
			} );
		} elseif ( class_exists( 'CS_License' ) ) {
			$recurring_license = new CS_License( __FILE__, CS_RECURRING_PRODUCT_NAME, CS_RECURRING_VERSION, 'Easy Digital Downloads', 'recurring_license_key', null, 28530 );
		}

		// Register our custom post status
		$this->register_post_statuses();

		add_action( 'admin_menu', array( $this, 'subscriptions_list' ), 10 );

		// Maybe remove the Signup fee from the cart
		add_action( 'init', array( $this, 'maybe_add_remove_fees' ) );

		// Check for subscription status on file download
		add_action( 'cs_process_verified_download', array( $this, 'process_download' ), 10, 4 );

		// Tells CommerceStore to include subscription payments in Payment History
		add_action( 'cs_pre_get_payments', array( $this, 'enable_child_payments' ), 100 );

		// Register styles
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );

		// Register scripts
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'init', array( $this, 'add_non_persistent_cache' ) );

		// Ensure Authorize.net 2.0+ is available.
		if ( defined( 'CSA_VERSION' ) ) {
			add_action( 'admin_notices', array( $this, '_require_authnet_20_notice' ) );
		}

	}

	/**
	 * Add our filters
	 *
	 * @since  1.0
	 * @return void
	 */
	private function filters() {

		// Register our new payment statuses
		add_filter( 'cs_payment_statuses', array( $this, 'register_cs_cancelled_status' ) );

		// Set the payment stati that can download files (legacy)
		add_filter( 'cs_allowed_download_stati', array( $this, 'add_allowed_payment_status' ) );
		add_filter( 'cs_is_payment_complete', array( $this, 'is_payment_complete' ), 10, 3 );

		// Disable item quantities if cart contains subscriptions
		add_filter( 'cs_item_quantities_enabled', array( $this, 'maybe_disable_quantities' ) );

		add_filter( 'cs_file_download_has_access', array( $this, 'allow_file_access' ), 10, 3 );

		// Show the Cancelled and Subscription status links in Payment History
		add_filter( 'cs_payments_table_views', array( $this, 'payments_view' ) );

		// Modify the cart details when purchasing a subscription
		add_filter( 'cs_add_to_cart_item', array( $this, 'add_subscription_cart_details' ), 10 );

		// Include subscription payments in the calulation of earnings
		add_filter( 'cs_get_total_earnings_args', array( $this, 'earnings_query' ) );
		add_filter( 'cs_stats_earnings_args', array( $this, 'earnings_query' ) );

		// Deprecated in CommerceStore 2.7
		add_filter( 'cs_get_earnings_by_date_args', array( $this, 'earnings_query' ) );
		add_filter( 'cs_get_sales_by_date_args', array( $this, 'earnings_query' ) );

		add_filter( 'cs_get_users_purchases_args', array( $this, 'has_purchased_query' ) );

		// Allow PDF Invoices to be downloaded for subscription payments
		add_filter( 'cspdfi_is_invoice_link_allowed', array( $this, 'is_invoice_allowed' ), 10, 2 );

		// Allow cs_subscription to run a refund to the gateways
		add_filter( 'cs_refundable_order_statuses', array( $this, 'refundable_order_statuses' ) );
		add_filter( 'cs_should_process_refund', array( $this, 'maybe_process_refund' ), 10, 2 );
		add_filter( 'cs_decrease_sales_on_undo', array( $this, 'maybe_decrease_sales' ), 10, 2 );
		add_filter( 'cs_decrease_customer_purchase_count_on_refund', array( $this, 'maybe_decrease_sales' ), 10, 2 );

		// Don't count renewals towards a customer purchase count when using recount
		add_filter( 'cs_customer_recount_sholud_increase_count', array( $this, 'maybe_increase_customer_sales' ), 10, 2 );

		// Add cs_subscription to payment stats in CommerceStore Core
		add_filter( 'cs_payment_stats_post_statuses', array( $this, 'cs_payment_stats_post_status' ) );

		// Ensure Authorize.net 2.0+ is available.
		if ( defined( 'CSA_VERSION' ) ) {
			add_action( 'cs_enabled_payment_gateways', array( $this, '_require_authnet_20' ) );
		}
	}

	/**
	 * Registers renewal payment post status
	 *
	 * @since  1.0
	 * @return void
	 */
	public function register_post_statuses() {
		register_post_status( 'cancelled', array(
			'label'                     => _x( 'Cancelled', 'Cancelled payment status', 'commercestore' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => false,
			'show_in_admin_status_list' => false,
			'label_count'               => _n_noop( 'Cancelled <span class="count">(%s)</span>', 'Cancelled <span class="count">(%s)</span>', 'commercestore' )
		) );
		register_post_status( 'cs_subscription', array(
			'label'                     => _x( 'Renewal', 'Subscription renewal payment status', 'commercestore' ),
			'public'                    => true,
			'exclude_from_search'       => false,
			'show_in_admin_all_list'    => true,
			'show_in_admin_status_list' => true,
			'label_count'               => _n_noop( 'Renewal <span class="count">(%s)</span>', 'Renewals <span class="count">(%s)</span>', 'commercestore' )
		) );
	}

	/**
	 * Register our Subscriptions submenu
	 *
	 * @since  2.4
	 * @return void
	 */
	public function subscriptions_list() {
		add_submenu_page(
			'edit.php?post_type=download',
			__( 'Subscriptions', 'commercestore' ),
			__( 'Subscriptions', 'commercestore' ),
			'view_shop_reports',
			'cs-subscriptions',
			'cs_subscriptions_page'
		);
	}


	/**
	 * Allow file downloads for payments with a status of cancelled
	 *
	 * @since  1.4.2
	 * @return array
	 */
	public function add_allowed_payment_status( $stati ) {
		$stati[] = 'cancelled';

		return $stati;
	}


	/**
	 * Allow file downloads for payments with a status of cancelled
	 *
	 * @since  1.4.2
	 * @return array
	 */
	public function is_payment_complete( $ret, $payment_id, $status ) {

		if ( 'cancelled' === $status ) {

			$ret = true;

		} elseif ( 'cs_subscription' === $status ) {

			$payment = cs_get_payment( $payment_id );
			if ( ! empty( $payment->parent_payment ) && cs_is_payment_complete( $payment->parent_payment ) ) {
				$ret = true;
			}
		}

		return $ret;
	}

	/**
	 * Disable item quantities if the cart contains a subscription
	 *
	 * @since  2.5
	 * @return array
	 */
	public function maybe_disable_quantities( $ret ) {

		if( self::cart_contains_recurring() ) {

			$ret = false;

		}

		return $ret;
	}

	/**
	 * Allow file download access once a renewal has processed
	 *
	 * @since  2.4.6
	 * @param  bool  $has_access   If the user has access to the file
	 * @param  int   $payment_id    The payment ID associated with the download
	 * @param  array $args        Array of arguments for the file request
	 * @return bool               If the file should be delivered or not.
	 */
	public function allow_file_access( $has_access, $payment_id, $args ) {
		if ( ! $payment_id ) {
			return $has_access;
		}

		$payment = cs_get_payment( $payment_id );
		if ( $payment && 'cs_subscription' === $payment->status ) {
			$has_access = true;
		}

		return $has_access;
	}


	/**
	 * Tells CommerceStore about our new payment status
	 *
	 * @since  1.0
	 * @return array
	 */
	public function register_cs_cancelled_status( $stati ) {
		$stati['cs_subscription'] = __( 'Renewal', 'commercestore' );
		return $stati;
	}


	/**
	 * Displays the cancelled payments filter link
	 *
	 * @since  1.0
	 * @return array
	 */
	public function payments_view( $views ) {
		if ( function_exists( 'cs_count_orders' ) ) {
			return $views;
		}
		$base          = admin_url( 'edit.php?post_type=download&page=cs-payment-history' );
		$payment_count = wp_count_posts( 'cs_payment' );
		$current       = isset( $_GET['status'] ) ? $_GET['status'] : '';

		$subscription_count        = '&nbsp;<span class="count">(' . $payment_count->cs_subscription . ')</span>';
		$views['cs_subscription'] = sprintf(
			'<a href="%s"%s>%s</a>',
			esc_url( add_query_arg( 'status', 'cs_subscription', $base ) ),
			$current === 'cs_subscription' ? ' class="current"' : '',
			__( 'Renewals', 'commercestore' ) . $subscription_count
		);

		return $views;
	}


	/**
	 * Add or remove the signup fees
	 *
	 * @since  2.1.6
	 * @return void
	 */
	public function maybe_add_remove_fees() {
		if ( is_admin() ) {
			return;
		}

		$fee_amount    = 0;
		$has_recurring = false;
		$cart_details  = cs_get_cart_contents();

		if ( $cart_details ) {
			foreach ( $cart_details as $item ) {

				if ( isset( $item['options'] ) && isset( $item['options']['recurring'] ) && isset( $item['options']['recurring']['signup_fee'] ) ) {

					$has_recurring = true;
					$fee_amount   += $item['options']['recurring']['signup_fee'];
				}

			}
		}

		if ( $has_recurring && ( $fee_amount > 0 || $fee_amount < 0 ) ) {
			$args = array(
				'amount' => $fee_amount,
				'label'  => cs_get_option( 'recurring_signup_fee_label', __( 'Signup Fee', 'commercestore' ) ),
				'id'     => 'signup_fee',
				'type'   => 'fee'
			);
			CS()->fees->add_fee( $args );
		} else {
			CS()->fees->remove_fee( 'signup_fee' );
		}

	}

	/**
	 * Checks if a user has permission to download a file
	 *
	 * This allows file downloads to be limited to activesubscribers
	 *
	 * @since  1.0
	 * @return void
	 */
	public function process_download( $download_id = 0, $email = '', $payment_id = 0, $args = array() ) {

		global $cs_options;

		if ( ! cs_get_option( 'recurring_download_limit', false ) ) {
			return;
		} // Downloads not restricted to subscribers

		// Allow user to download by default
		$has_access = true;

		// Check if this is a variable priced product
		$is_variable = isset( $_GET['price_id'] ) && (int) $_GET['price_id'] !== false ? true : false;

		if ( $is_variable && cs_has_variable_prices( $download_id ) ) {
			$recurring = self::is_price_recurring( $download_id, (int) $_GET['price_id'] );
		} else {
			$recurring = self::is_recurring( $download_id );
		}

		if ( ! $recurring ) {
			return;
		} // Product isn't recurring

		$customer = new CS_Recurring_Subscriber( $email );

		// No customer found so access is denied
		if ( ! $customer->id > 0 ) {
			$has_access = false;
		}

		// Check for active subscription
		if ( $customer->id > 0 && ! $customer->has_active_product_subscription( $download_id ) ) {

			$has_access = false;

			// Check if the purchase included a bundle
			$payment = cs_get_payment( $payment_id );

			foreach( $payment->downloads as $download ) {

				if( cs_is_bundled_product( $download['id'] ) ) {

					$bundled = cs_get_bundled_products( $download['id'] );

					if( ! in_array( $download_id, $bundled ) ) {
						continue;
					}

					if( $customer->has_active_product_subscription( $download['id'] ) ) {

						$has_access = true;

					}

				}

			}

		}

		// User doesn't have an active subscription so deny access
		if ( ! apply_filters( 'cs_recurring_download_has_access', $has_access, $customer->user_id, $download_id, $is_variable ) ) {

			wp_die(
				sprintf(
					__( 'You must have an active subscription to %s in order to download this file.', 'commercestore' ),
					get_the_title( $download_id )
				),
				__( 'Access Denied', 'commercestore' )
			);
		}

	}


	/**
	 * Adds recurring product details to the shopping cart
	 *
	 * This fires when items are added to the cart
	 *
	 * @since  1.0
	 * @return array
	 */
	static function add_subscription_cart_details( $cart_item ) {

		if ( empty( $cart_item['id'] ) ) {
			return $cart_item;
		}

		$download_id = $cart_item['id'];
		$price_id    = isset( $cart_item['options']['price_id'] ) ? intval( $cart_item['options']['price_id'] ) : null;

		if( isset( $cart_item['options']['custom_price'] ) ) {

			if ( 'yes' == get_post_meta( $download_id, 'cs_custom_recurring', true ) ) {

				$cart_item['options']['recurring'] = array(
					'period'       => self::get_custom_period( $download_id ),
					'times'        => self::get_custom_times( $download_id ),
					'signup_fee'   => self::get_custom_signup_fee( $download_id ),
					'trial_period' => self::get_trial_period( $download_id )
				);

			}

		} else if ( cs_has_variable_prices( $download_id ) && ( ! empty( $price_id ) || 0 === (int) $price_id ) ) {

			// add the recurring info for a variable price
			if ( self::is_price_recurring( $download_id, $price_id ) ) {

				$cart_item['options']['recurring'] = array(
					'period'       => self::get_period( $price_id, $download_id ),
					'times'        => self::get_times( $price_id, $download_id ),
					'signup_fee'   => self::get_signup_fee( $price_id, $download_id ),
					'trial_period' => self::get_trial_period( $download_id, $price_id )
				);

			}

		} else {

			// add the recurring info for a normal priced item
			if ( self::is_recurring( $download_id ) ) {

				$cart_item['options']['recurring'] = array(
					'period'       => self::get_period_single( $download_id ),
					'times'        => self::get_times_single( $download_id ),
					'signup_fee'   => self::get_signup_fee_single( $download_id ),
					'trial_period' => self::get_trial_period( $download_id )
				);

			}

		}

		return $cart_item;

	}

	/**
	 * Set up the time period IDs and labels
	 *
	 * @since  1.0
	 * @return array
	 */

	static function periods() {
		$periods = array(
			'day'       => _x( 'Daily', 'Billing period', 'commercestore' ),
			'week'      => _x( 'Weekly', 'Billing period', 'commercestore' ),
			'month'     => _x( 'Monthly', 'Billing period', 'commercestore' ),
			'quarter'   => _x( 'Quarterly', 'Billing period', 'commercestore' ),
			'semi-year' => _x( 'Semi-Yearly', 'Billing period', 'commercestore' ),
			'year'      => _x( 'Yearly', 'Billing period', 'commercestore' ),
		);

		$periods = apply_filters( 'cs_recurring_periods', $periods );

		return $periods;
	}

	/**
	 * Set up the singular time period IDs and labels
	 *
	 * @since  1.0
	 * @return array
	 */
	static function singular_periods() {
		$periods = array(
			'day'       => _x( 'Day(s)', 'Billing period', 'commercestore' ),
			'week'      => _x( 'Week(s)', 'Billing period', 'commercestore' ),
			'month'     => _x( 'Month(s)', 'Billing period', 'commercestore' ),
			'quarter'   => _x( 'Quarter(s)', 'Billing period', 'commercestore' ),
			'semi-year' => _x( 'Semi-Year(s)', 'Billing period', 'commercestore' ),
			'year'      => _x( 'Year(s)', 'Billing period', 'commercestore' ),
		);

		$periods = apply_filters( 'cs_recurring_singular_periods', $periods );

		return $periods;
	}


	/**
	 * Get the time period for a variable priced product
	 *
	 * @since  1.0
	 * @return string
	 */

	static function get_period( $price_id, $post_id = null ) {
		global $post;

		$period = 'never';

		if ( ! $post_id && is_object( $post ) ) {
			$post_id = $post->ID;
		}

		$prices = get_post_meta( $post_id, 'cs_variable_prices', true );

		if ( isset( $prices[ $price_id ]['period'] ) ) {
			$period = $prices[ $price_id ]['period'];
		}

		return $period;
	}


	/**
	 * Get the time period for a single-price product
	 *
	 * @since  1.0
	 * @return string
	 */

	static function get_period_single( $post_id ) {
		global $post;

		$period = get_post_meta( $post_id, 'cs_period', true );

		if ( $period ) {
			return $period;
		}

		return 'never';
	}


	/**
	 * Get the number of times a price ID recurs
	 *
	 * @since  1.0
	 * @return int
	 */

	static function get_times( $price_id, $post_id = null ) {
		global $post;

		if ( empty( $post_id ) && is_object( $post ) ) {
			$post_id = $post->ID;
		}

		$prices = get_post_meta( $post_id, 'cs_variable_prices', true );

		if ( isset( $prices[ $price_id ]['times'] ) ) {
			return intval( $prices[ $price_id ]['times'] );
		}

		return 0;
	}

	/**
	 * Get the signup fee a price ID
	 *
	 * @since  1.1
	 * @return float
	 */

	static function get_signup_fee( $price_id, $post_id = null ) {
		global $post;

		if ( empty( $post_id ) && is_object( $post ) ) {
			$post_id = $post->ID;
		}

		$prices = get_post_meta( $post_id, 'cs_variable_prices', true );

		$fee = isset( $prices[ $price_id ]['signup_fee'] ) ? $prices[ $price_id ]['signup_fee'] : 0;
		$fee = apply_filters( 'cs_recurring_signup_fee', $fee, $price_id, $prices );
		if ( $fee ) {
			return floatval( $fee );
		}

		return 0;
	}


	/**
	 * Get the number of times a single-price product recurs
	 *
	 * @since  1.0
	 * @return int
	 */

	static function get_times_single( $post_id ) {
		global $post;

		$times = get_post_meta( $post_id, 'cs_times', true );

		if ( $times ) {
			return $times;
		}

		return 0;
	}


	/**
	 * Get the signup fee of a single-price product
	 *
	 * @since  1.1
	 * @return float
	 */

	static function get_signup_fee_single( $post_id ) {
		global $post;

		$signup_fee = get_post_meta( $post_id, 'cs_signup_fee', true );

		if ( $signup_fee ) {
			return $signup_fee;
		}

		return 0;
	}

	/**
	 * Get the time period for a custom-price product
	 *
	 * For Custom Prices plugin
	 *
	 * @since  2.5
	 * @return string
	 */
	static function get_custom_period( $post_id ) {
		global $post;

		$period = get_post_meta( $post_id, 'cs_custom_period', true );

		if ( $period ) {
			return $period;
		}

		return 'never';
	}

	/**
	 * Get the number of times a custom-price product recurs
	 *
	 * For Custom Prices plugin
	 *
	 * @since  2.5
	 * @return int
	 */
	static function get_custom_times( $post_id ) {
		global $post;

		$times = get_post_meta( $post_id, 'cs_custom_times', true );

		if ( $times ) {
			return $times;
		}

		return 0;
	}


	/**
	 * Get the signup fee of a custom price product
	 *
	 * For Custom Prices plugin
	 *
	 * @since  2.5
	 * @return float
	 */
	static function get_custom_signup_fee( $post_id ) {
		global $post;

		$signup_fee = get_post_meta( $post_id, 'cs_custom_signup_fee', true );

		if ( $signup_fee ) {
			return $signup_fee;
		}

		return 0;
	}


	/**
	 * Check if a price is recurring
	 *
	 * @since  1.0
	 * @return bool
	 */

	static function is_price_recurring( $download_id, $price_id ) {

		global $post;

		if ( empty( $download_id ) && is_object( $post ) ) {
			$download_id = $post->ID;
		}

		$prices = get_post_meta( $download_id, 'cs_variable_prices', true );
		$period = self::get_period( $price_id, $download_id );

		if ( isset( $prices[ $price_id ]['recurring'] ) && 'never' != $period ) {
			return true;
		}

		return false;

	}


	/**
	 * Check if a product is recurring
	 *
	 * @since  1.0
	 *
	 * @param int $download_id
	 *
	 * @return bool
	 */
	public static function is_recurring( $download_id = 0 ) {

		global $post;

		if ( empty( $download_id ) && is_object( $post ) ) {
			$download_id = $post->ID;
		}

		if ( get_post_meta( $download_id, 'cs_recurring', true ) == 'yes' ) {
			return true;
		}

		return false;

	}


	/**
	 * Check if a custom price product is recurring
	 *
	 * @since  2.5
	 *
	 * @param int $download_id
	 *
	 * @return bool
	 */
	public static function is_custom_recurring( $download_id = 0 ) {

		global $post;

		if ( empty( $download_id ) && is_object( $post ) ) {
			$download_id = $post->ID;
		}

		if ( get_post_meta( $download_id, 'cs_custom_recurring', true ) == 'yes' ) {
			return true;
		}

		return false;

	}

	/**
	 * Check if a product has a free trial
	 *
	 * @since  2.6
	 *
	 * @param int $download_id
	 *
	 * @return bool
	 */
	public static function has_free_trial( $download_id = 0, $price_id = null ) {

		global $post;

		if ( empty( $download_id ) && is_object( $post ) ) {
			$download_id = $post->ID;
		}

		$prices = cs_get_variable_prices( $download_id );
		if ( ( ! empty( $price_id ) || 0 === (int) $price_id ) && is_array( $prices ) && ! empty( $prices[ $price_id ]['trial-quantity'] ) ) {
			$trial = array();
			$trial['quantity'] = $prices[ $price_id ]['trial-quantity'];
			$has_trial = ( $trial > 0 ? true : false );

			return apply_filters( 'cs_recurring_download_has_free_trial', (bool) $has_trial, $download_id, $price_id );

		} else {
			$has_trial = get_post_meta( $download_id, 'cs_trial_period', true );

			return apply_filters( 'cs_recurring_download_has_free_trial', (bool) $has_trial, $download_id );
		}
	}

	/**
	 * Determine if the currently logged in customer or email address has used their free trial
	 *
	 * @since  2.6
	 *
	 * @param int $download_id
	 * @param string $email
	 *
	 * @return bool
	 */
	public static function has_trialed( $download_id = 0, $email = '' ) {

		$ret = false;

		if( ! empty( $email ) ) {

			$subscriber = new CS_Recurring_Subscriber( $email );

		} elseif( is_user_logged_in() ) {

			$subscriber = new CS_Recurring_Subscriber( get_current_user_id(), true );

		}

		if( ! empty( $subscriber ) && $subscriber->id > 0 ) {

			$ret = $subscriber->has_trialed( $download_id );

		}

		return $ret;

	}

	/**
	 * Get the time period for a product
	 *
	 * @since  2.6
	 * @return array
	 */
	static function get_trial_period( $post_id, $price_id = null ) {
		global $post;

		$period = false;

		if( self::has_free_trial( $post_id, $price_id ) ) {

			$default = array(
				'quantity' => 1,
				'unit'     => 'month',
			);

			$prices = cs_get_variable_prices( $post_id );
			if ( ( ! empty( $price_id ) || 0 === (int) $price_id ) && is_array( $prices ) && ! empty( $prices[ $price_id ]['trial-quantity'] ) && ! empty( $prices[ $price_id ]['trial-unit'] ) ) {
				$period['quantity'] = $prices[ $price_id ]['trial-quantity'];
				$period['unit'] = $prices[ $price_id ]['trial-unit'];
			} else {
				$period = (array) get_post_meta( $post_id, 'cs_trial_period', true );
				$period = wp_parse_args( $period, $default );
				$period['quantity'] = absint( $period['quantity'] );
				$period['quantity'] = $period['quantity'] < 1 ? 1 : $period['quantity'];
			}
		}

		return $period;

	}

	/**
	 * Record a subscription payment
	 *
	 * @deprecated 2.4
	 * @since  1.0.1
	 * @return void
	 */
	public function record_subscription_payment( $parent_id = 0, $amount = '', $txn_id = '', $unique_key = 0 ) {

		global $cs_options;

		_cs_deprecated_function( __FUNCTION__, '2.5', 'CS_Recurring_Subscription::add_payment()', debug_backtrace() );

		if ( self::payment_exists( $unique_key ) ) {
			return;
		}

		// increase the earnings for each product in the subscription
		$downloads = cs_get_payment_meta_downloads( $parent_id );
		if ( $downloads ) {
			foreach ( $downloads as $download ) {
				cs_increase_earnings( $download['id'], $amount );
			}
		}

		// setup the payment data
		$payment_data = array(
			'parent'       => $parent_id,
			'price'        => $amount,
			'user_email'   => cs_get_payment_user_email( $parent_id ),
			'purchase_key' => cs_get_payment_meta( $parent_id, '_cs_payment_purchase_key', true ),
			'currency'     => cs_get_option( 'currency', 'usd' ),
			'downloads'    => $downloads,
			'user_info'    => cs_get_payment_meta_user_info( $parent_id ),
			'cart_details' => cs_get_payment_meta_cart_details( $parent_id ),
			'status'       => 'cs_subscription',
			'gateway'      => cs_get_payment_gateway( $parent_id )
		);

		// record the subscription payment
		$payment = cs_insert_payment( $payment_data );

		if ( ! empty( $unique_key ) ) {
			cs_update_payment_meta( $payment, '_cs_recurring_' . $unique_key, '1' );
		}

		// Record transaction ID
		if ( ! empty( $txn_id ) ) {

			if ( function_exists( 'cs_set_payment_transaction_id' ) ) {
				cs_set_payment_transaction_id( $payment, $txn_id );
			}
		}

		// Update the expiration date of license keys, if CommerceStore Software Licensing is active
		if ( function_exists( 'cs_software_licensing' ) ) {
			$licenses = cs_software_licensing()->get_licenses_of_purchase( $parent_id );

			if ( ! empty( $licenses ) ) {
				foreach ( $licenses as $license ) {
					// Update the expiration dates of the license key

					cs_software_licensing()->renew_license( $license->ID, $payment->ID );

				}
			}
		}

		do_action( 'cs_recurring_record_payment', $payment, $parent_id, $amount, $txn_id, $unique_key );

	}

	/**
	 * Checks if a payment already exists
	 *
	 * @deprecated 2.4
	 * @since  1.0.2
	 * @return bool
	 */
	public function payment_exists( $unique_key = 0 ) {
		global $wpdb;

		_cs_deprecated_function( __FUNCTION__, '2.5', null, debug_backtrace() );

		if ( empty( $unique_key ) ) {
			return false;
		}

		$unique_key = esc_sql( $unique_key );

		$purchase = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_cs_recurring_{$unique_key}' LIMIT 1" );

		if ( $purchase != null ) {
			return true;
		}

		return false;
	}


	/**
	 * Determines if a purchase contains a recurring product
	 *
	 * @since  1.0.1
	 * @return bool
	 */
	public function is_purchase_recurring( $purchase_data ) {

		if( ! empty( $purchase_data['downloads'] ) && is_array( $purchase_data['downloads'] ) ) {

			foreach ( $purchase_data['downloads'] as $download ) {

				if ( isset( $download['options'] ) && isset( $download['options']['recurring'] ) ) {
					return true;
				}
			}

		}

		return false;

	}

	/**
	 * Looks at the cart to determine if there is a recurring subscription in the cart
	 *
	 * @since   2.4
	 * @return  bool
	 */
	public function cart_contains_recurring() {

		$contains_recurring = false;

		$cart_contents = cs_get_cart_contents();
		foreach ( $cart_contents as $cart_item ) {

			if ( isset( $cart_item['options'] ) && isset( $cart_item['options']['recurring'] ) ) {

				$contains_recurring = true;
				break;

			}

		}

		return $contains_recurring;
	}

	/**
	 * Looks at the cart to determine if there are recurring and non-recurring items
	 *
	 * @since   2.4.13
	 * @return  bool
	 */
	public function cart_is_mixed() {

		$has_recurring     = false;
		$has_non_recurring = false;
		$cart_contents     = cs_get_cart_contents();

		foreach ( $cart_contents as $cart_item ) {

			if ( isset( $cart_item['options'] ) && isset( $cart_item['options']['recurring'] ) ) {

				$has_recurring = true;

			} else {

				$item_price = cs_get_cart_item_price( $cart_item['id'], $cart_item['options'] );

				if ( 0.00 < $item_price ) {

					// Free items are allowed
					$has_non_recurring = true;

				}

			}

		}

		$mixed = $has_recurring && $has_non_recurring;

		return apply_filters( 'cs_recurring_cart_is_mixed', $mixed, $has_recurring, $has_non_recurring );
	}

	/**
	 * Looks at the cart to determine if there are free trials and non-trials together
	 *
	 * Trials and non-trials cannot be purchased together.
	 *
	 * @since   2.6
	 * @return  bool
	 */
	public function cart_is_mixed_with_trials() {

		$has_trial     = false;
		$has_non_trial = false;

		if( self::cart_contains_recurring() ) {

			$cart_contents = cs_get_cart_contents();

			foreach ( $cart_contents as $cart_item ) {

				if ( cs_has_variable_prices( $cart_item['id'] ) && isset( $cart_item['options']['price_id'] ) ) {

					if( self::has_free_trial( $cart_item['id'], $cart_item['options']['price_id'] ) ) {

						$has_trial = true;

					} else {

						$has_non_trial = true;

					}

				} else if ( self::has_free_trial( $cart_item['id'] ) ) {

					$has_trial = true;

				} else {

					$item_price = cs_get_cart_item_price( $cart_item['id'], $cart_item['options'] );

					if ( 0.00 < $item_price ) {

						// Free items are allowed
						$has_non_trial = true;

					}

				}

			}

		}

		$mixed = $has_trial && $has_non_trial;

		return apply_filters( 'cs_recurring_cart_is_mixed_with_trials', $mixed, $has_trial, $has_non_trial );
	}

	/**
	 * Overwrites cart total line when free trials are present
	 *
	 * @since  2.6
	 * @return string
	 */
	public static function maybe_set_cart_total( $total ) {

		if( self::cart_has_free_trial() ) {

			$total         = cs_get_cart_total();
			$cart_contents = cs_get_cart_contents();

			foreach ( $cart_contents as $cart_item ) {

				if ( self::has_free_trial( $cart_item['id'] ) && isset( $cart_item['options']['recurring']['trial_period'] ) ) {

					$total -= cs_get_cart_item_price( $cart_item['id'], $cart_item['options'] );

				}

			}

			$total = cs_currency_filter( cs_format_amount( $total ) );

		}

		return $total;

	}

	/**
	 * Looks at the cart to determine if there is a subscription with a free trial
	 *
	 * @since   2.6
	 * @return  bool
	 */
	public function cart_has_free_trial() {

		if( ! self::cart_contains_recurring() ) {
			return false;
		}

		$has_trial       = false;
		$one_time_trials = cs_get_option( 'recurring_one_time_trials', false );
		$cart_contents   = cs_get_cart_contents();

		foreach ( $cart_contents as $cart_item ) {

			$price_id = isset( $cart_item['options']['price_id'] ) ? $cart_item['options']['price_id'] : null;

			if ( self::has_free_trial( $cart_item['id'], $price_id ) && isset( $cart_item['options']['recurring']['trial_period'] ) ) {

				if( ! $one_time_trials || ! self::has_trialed( $cart_item['id'] ) ) {

					$has_trial = true;

				}

				break;

			}

		}

		return $has_trial;
	}

	/**
	 * Make sure subscription payments get included in earning reports
	 *
	 * @since  1.0
	 * @return array
	 */
	public function earnings_query( $args ) {

		$statuses_to_include = array( 'cancelled', 'cs_subscription' );

		// Include post_status in case we are filtering to direct database queries like in the cs_stats_earnings_args filter
		if ( isset( $args['post_status'] ) && is_array( $args['post_status'] ) ) {
			$args['post_status'] = array_unique( array_merge( $args['post_status'], $statuses_to_include ) );
		}

		// Include status in case we are filtering to queries done through cs_get_payments like in the cs_get_total_earnings_args filter
		if ( isset( $args['status'] ) && is_array( $args['status'] ) ) {
			$args['status'] = array_unique( array_merge( $args['status'], $statuses_to_include ) );
		}

		return $args;
	}


	/**
	 * Make sure subscription payments get included in has user purchased query
	 *
	 * @since  2.1.5
	 * @param  array $args The array of query arguments
	 * @return array
	 */
	public function has_purchased_query( $args ) {
		if ( ! isset( $args['status'] ) ) {
		    $args['status'] = array();// if unset, cast to array
		} else if ( ! is_array( $args['status'] ) && is_string( $args['status'] ) ){
		     $args['status'] = array( $args['status'] ); // if string, cast to array
		} else if ( ! $args['status'] ) {
		    $args['status'] = array(); // if boolean false, cast to array
		}

		if ( is_array( $args['status'] ) ) {
			$statuses       = array_unique( array_merge( $args['status'], array( 'cs_subscription' ) ) );
			$args['status'] = $statuses;
		}

		return $args;
	}

	/**
	 * Add cs_subscription post type to CommerceStore Payment Stats
	 *
	 * @since  2.6.10
	 * @param  array $statuses Post statuses.
	 */
	public function cs_payment_stats_post_status( $statuses ) {
		$statuses[] = 'cs_subscription';
		return $statuses;
	}

	/**
	 * Tells CommerceStore to include child payments in queries
	 *
	 * @since  2.2
	 * @return void
	 */
	public function enable_child_payments( $query ) {

		$query_has_recurring = true;

		if( ! empty( $query->initial_args['download'] ) ) {
			$query_has_recurring = false;
			$download            = $query->initial_args['download'];

			if ( ! is_array( $download ) && strpos( $download, ',' ) ) {
				$download = explode( ',', $download );
			}

			if ( is_array( $download ) ) {
				foreach( $download as $download_id ) {
					$item_has_recurring = cs_recurring()->is_recurring( $download_id );
					if ( $item_has_recurring ) {
						$query_has_recurring = true;
						break;
					}
				}
			} else {
				$query_has_recurring = cs_recurring()->is_recurring( $download );
			}
		}

		// This does not appear to need to be updated for CommerceStore 3.0.
		if ( $query_has_recurring ) {
			$query->__set( 'post_parent', null );
		}

	}


	/**
	 * Load frontend CSS files
	 *
	 * @since  2.4
	 * @return bool
	 */
	public function enqueue_styles() {
		wp_register_style( 'cs-recurring', CS_RECURRING_PLUGIN_URL . 'assets/css/styles.css', array(), CS_RECURRING_VERSION );
		wp_enqueue_style( 'cs-recurring' );
	}

	/**
	 * Load frontend javascript files
	 *
	 * @since  2.4
	 * @return bool
	 */
	public function enqueue_scripts() {
		global $post;

		$load_js = false;

		wp_register_script( 'cs-frontend-recurring', CS_RECURRING_PLUGIN_URL . 'assets/js/cs-frontend-recurring.js', array( 'jquery' ), CS_RECURRING_VERSION );

		wp_localize_script( 'cs-frontend-recurring', 'cs_recurring_vars', array(
			'confirm_cancel' => __( 'Are you sure you want to cancel your subscription?', 'commercestore' ),
			'has_trial'      => $this->cart_has_free_trial(),
			'total'          => $this->cart_has_free_trial() ? cs_currency_filter( '0.00' ) : cs_cart_total( false ),
			'total_plain'    => $this->cart_has_free_trial() ? '0.00' : cs_get_cart_total()
		) );

		// The page checks could be broken out, but this is a far more readable format for troubleshooting.
		if ( cs_is_checkout() ||
		   ( is_object( $post ) && (
		     ( 'page' === $post->post_type && has_shortcode( $post->post_content, 'purchase_link' ) ) ||
		     ( 'page' === $post->post_type && has_shortcode( $post->post_content, 'cs_downloads' ) ) ||
		     ( 'page' === $post->post_type && has_shortcode( $post->post_content, 'downloads' ) ) ||
		     ( 'page' === $post->post_type && has_shortcode( $post->post_content, 'cs_subscriptions' ) ) ||
		       'download' === $post->post_type )
		   ) ) {
			$load_js = true;
		}

		$load_js = apply_filters( 'cs_recurring_load_js', $load_js );

		if ( $load_js ) {
			wp_enqueue_script( 'cs-frontend-recurring' );
		}
	}

	/**
	 * Instruct CommerceStore PDF Invoices that subscription paymentsare eligible for Invoices
	 *
	 * @since  2.2
	 * @return bool
	 */
	public function is_invoice_allowed( $ret, $payment_id ) {

		$payment_status = cs_get_payment_status( $payment_id );

		if ( 'cs_subscription' === $payment_status ) {

			$payment = cs_get_payment( $payment_id );
			if ( ! empty( $payment->parent_payment ) && cs_is_payment_complete( $payment->parent_payment ) ) {
				$ret = true;
			}

		}

		return $ret;
	}

	/**
	 * Adds `cs_subscription` to the list of order statuses that support refunds.
	 *
	 * @param array $statuses
	 *
	 * @since 2.10.1
	 * @return array
	 */
	public function refundable_order_statuses( $statuses ) {
		$statuses[] = 'cs_subscription';

		return $statuses;
	}

	/**
	 * Checks the payment status during the refund process and allows it to be processed through the gateway
	 * if it's an cs_subscription
	 *
	 * @since  2.4
	 * @param  bool   $process_refund The current status of if a refund should be processed
	 * @param  object $payment        The CS_Payment object of the refund being processed
	 * @return bool                   If the payment should be procssed as a refund
	 */
	public function maybe_process_refund( $process_refund, $payment ) {

		if ( 'cs_subscription' === $payment->old_status ) {
			$process_refund = true;
		}

		return $process_refund;

	}

	/**
	 * Checks the payment status during the refund process and tells CommerceStore to not decrease sales
	 * if it's an cs_subscription
	 *
	 * @since  2.4
	 * @param  bool   $decrease_sales The current status of if sales counts should be decreased
	 * @param  object $payment        The CS_Payment object of the refund being processed
	 * @return bool                   If the sales counts should be decreased
	 */
	public function maybe_decrease_sales( $decrease_sales, $payment ) {

		if ( ! empty( $payment->parent_payment ) && 'refunded' === $payment->status ) {
			$decrease_sales = false;
		}

		return $decrease_sales;

	}

	/**
	 * Checks if the payment being added to a customer via recount should increase the purchase_count
	 *
	 * @since  2.4.5
	 * @param  bool   $increase_sales The current status of if we should increase sales.
	 * @param  object $payment        The WP_Post object of the payment.
	 * @return bool                   If we should increase the customer sales count.
	 */
	public function maybe_increase_customer_sales( $increase_sales, $payment ) {

		// This does not need to be updated for CommerceStore 3.0.
		if ( 'cs_subscription' === $payment->post_status ) {
			$increase_sales = false;
		}

		return $increase_sales;

	}

	/**
	 * Get User ID from customer recurring ID
	 *
	 * @since  2.4
	 * @return int
	 */
	public function get_user_id_by_recurring_customer_id( $recurring_id = '' ) {

		global $wpdb;

		$user_id = $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = '_cs_recurring_id' AND meta_value = %s LIMIT 1", $recurring_id ) );

		if ( $user_id != NULL ) {
			return $user_id;
		}

		return 0;

	}


	/**
	 * Get pretty subscription frequency
	 *
	 * @param $period
	 *
	 * @return mixed|string|void
	 */
	public function get_pretty_subscription_frequency( $period ) {
		$frequency = '';
		//Format period details
		switch ( $period ) {
			case 'day' :
				$frequency = __( 'Daily', 'commercestore' );
				break;
			case 'week' :
				$frequency = __( 'Weekly', 'commercestore' );
				break;
			case 'month' :
				$frequency = __( 'Monthly', 'commercestore' );
				break;
			case 'quarter' :
				$frequency = __( 'Quarterly', 'commercestore' );
				break;
			case 'semi-year' :
				$frequency = __( 'Semi-Yearly', 'commercestore' );
				break;
			case 'year' :
				$frequency = __( 'Yearly', 'commercestore' );
				break;
			default :
				$frequency = apply_filters( 'cs_recurring_subscription_frequency', $frequency, $period );
				break;
		}

		return $frequency;

	}

	/**
	 * Get pretty subscription frequency for singular periods
	 *
	 * @param $period
	 * @since 2.6
	 * @return mixed|string|void
	 */
	public function get_pretty_singular_subscription_frequency( $period ) {
		$frequency = '';
		//Format period details
		switch ( $period ) {
			case 'day' :
				$frequency = __( 'Day', 'commercestore' );
				break;
			case 'week' :
				$frequency = __( 'Week', 'commercestore' );
				break;
			case 'month' :
				$frequency = __( 'Month', 'commercestore' );
				break;
			case 'quarter' :
				$frequency = __( 'Quarter', 'commercestore' );
				break;
			case 'semi-year' :
				$frequency = __( 'Semi-Year', 'commercestore' );
				break;
			case 'year' :
				$frequency = __( 'Year', 'commercestore' );
				break;
			default :
				$frequency = apply_filters( 'cs_recurring_singular_subscription_frequency', $frequency, $period );
				break;
		}

		return $frequency;

	}

	/**
	 * Get gateway class
	 *
	 * @param  string $gateway The gateway whose class is being retrieved.
	 * @return string The name of the gateway class.
	 */
	public function get_gateway_class( $gateway = '' ) {

		$class = false;

		if ( isset( self::$gateways[ $gateway ] ) ) {
			$class = self::$gateways[ $gateway ];
		}

		return $class;

	}

	/**
	 * Get instantiated gateway class.
	 *
	 * @param  string $gateway_id The gateway whose class is being retrieved.
	 * @return object The instantiated gateway class for the $gateway_id requested.
	 */
	public function get_gateway( $gateway_id = '' ) {
		$gateway = false;
		$class   = $this->get_gateway_class( $gateway_id );

		if ( $class && class_exists( $class ) ) {
			$gateway = new $class();
		}

		return apply_filters( 'cs_recurring_gateway', $gateway, $gateway_id );
	}

	/** Backwards Compatible Functions for Recurring terms */
	/**
	 * Display the signup fee notice under the purchase link
	 *
	 * @since  2.4
	 * @param  int   $download_id The download ID beign displayed
	 * @param  array $args      Array of arguements for the purcahse link
	 * @return void
	 */
	public function show_single_signup_fee_notice( $download_id, $args ) {
		self::$checkout->show_single_signup_fee_notice( $download_id, $args );
	}

	/**
	 * Display the signup fee notice under the purchase link for Custom Prices
	 *
	 * @since  2.5
	 * @param  int   $download_id The download ID beign displayed
	 * @param  array $args      Array of arguements for the purcahse link
	 * @return void
	 */
	public function show_single_custom_signup_fee_notice( $download_id, $args ) {
		self::$checkout->show_single_custom_signup_fee_notice( $download_id, $args );
	}

	/**
	 * Show the signup fees by variable prices
	 *
	 * @since  2.4
	 * @param  int    $price_id    The price ID key
	 * @param  string $price       The Price
	 * @param  int    $download_id The download ID
	 * @return void
	 */
	public function show_variable_signup_fee_notice( $price_id, $price, $download_id ) {
		self::$checkout->show_variable_signup_fee_notice( $price_id, $price, $download_id );
	}

	/**
	 * Show the signup fees for Custom Prices
	 *
	 * @since  2.5
	 * @param  int    $price_id    The price ID key
	 * @param  string $price       The Price
	 * @param  int    $download_id The download ID
	 * @return void
	 */
	public function show_multi_custom_signup_fee_notice( $download_id, $prices, $type ) {
		self::$checkout->show_multi_custom_signup_fee_notice( $download_id, $prices, $type );
	}

	/**
	 * Display the signup fee notice under the purchase link
	 *
	 * @since  2.4
	 * @param  int   $download_id The download ID beign displayed
	 * @param  array $args      Array of arguements for the purcahse link
	 * @return void
	 */
	public function show_single_terms_notice( $download_id, $args ) {
		self::$checkout->show_single_terms_notice( $download_id, $args );
	}

	/**
	 * Show the signup fees by vraible prices
	 *
	 * @since  2.4
	 * @param  int    $price_id    The price ID key
	 * @param  string $price       The Price
	 * @param  int    $download_id The download ID
	 * @return void
	 */
	public function show_variable_terms_notice( $price_id, $price, $download_id ) {
		self::$checkout->show_variable_terms_notice( $price_id, $price, $download_id );
	}

	/**
	 * Show the subscription terms for variable prices
	 *
	 * @since  2.5
	 * @param  int    $download_id The download ID
	 * @param  array  $prices      Variable prices
	 * @param  string $type        Product type
	 * @return void
	 */
	public function show_variable_custom_terms_notice( $download_id, $prices, $type ) {
		self::$checkout->show_variable_custom_terms_notice( $download_id, $prices, $type );
	}

	/**
	 * Disclose the subscription terms on the cart item
	 *
	 * @since  2.4
	 * @param  array $item The cart item
	 * @return void
	 */
	public function show_terms_on_cart_item( $item ) {
		self::$checkout->show_terms_on_cart_item( $item );
	}

	/**
	 * Show the subscriptions management UI
	 *
	 * @since 2.7.14
	 *
	 * @param string $action Optional. Which view to show. Options: update|list. If not set, $_GET[ 'action ] or "list" is used.
	 *
	 * @return string
	 */
	public function subscriptions_view( $action = '' ) {

		if( empty( $action ) ) {
			$action = ! empty( $_GET['action'] ) ? sanitize_text_field( $_GET['action'] ) : 'list';
		}

		ob_start();
		cs_print_errors();
		switch( $action ) {
			case 'update':
				cs_get_template_part( 'shortcode', 'subscription-update' );
				break;

			case 'view_transactions':
				cs_get_template_part( 'shortcode', 'subscription-transactions' );
				break;

			case 'list':
			default:
				cs_get_template_part( 'shortcode', 'subscriptions' );
				break;
		}

		return ob_get_clean();

	}

	/**
	 * Since we cannot invalidate the wp_cache by group, we need to avoid allowing the 'subscriptions' group
	 * from being added to the persistent caching solutions. While this might hurt page speed overall, it will still
	 * help with single page lads times when we are doing complex queries for subscriptions.
	 *
	 * @since 2.8.5
	 * @return void
	 */
	public function add_non_persistent_cache() {
		wp_cache_add_non_persistent_groups( 'cs_subscriptions' );
	}

	/**
	 * Conditonally load a notice for Authorize.net 2.0
	 *
	 * @since 2.9.6
	 * @return void
	 */
	public function _require_authnet_20_notice() {
		$enabled_gateways = cs_get_enabled_payment_gateways();

		if (
			isset( $enabled_gateways['authorize'] ) &&
			defined( 'CSA_VERSION' ) &&
			! version_compare( CSA_VERSION, '1.1.3', '>' )
		) {
			echo '<div class="notice notice-error">';

			echo wpautop( wp_kses(
				sprintf(
					/* translators: %1$s Opening strong tag, do not translate. %2$s Closing strong tag, do not translate. */
					__( '%1$sCredit card payments with Authorize.net are currently disabled.%2$s', 'commercestore' ),
					'<strong>',
					'</strong>'
				)
				. '<br />' .
				sprintf(
					/* translators: %1$s Opening code tag, do not translate. %2$s Closing code tag, do not translate. */
					__( 'To continue accepting recurring credit card payments with Authorize.net please update the Authorize.net Payment Gateway extension to version %1$s2.0%2$s.', 'commercestore' ),
					'<code>',
					'</code>'
				),
				array(
					'br'     => true,
					'strong' => true,
					'code'   => true,
				)
			) );

			echo '</div>';
		}
	}

	/**
	 * Conditionally remove the Authorize.net gateway from the active gateways to account for the
	 * 2.0 release of Authorize.net, where the code is moved into the gateway itself.
	 *
	 * @since 2.9.6
	 *
	 * @param array $enabled_gateways The list of active gateways
	 * @return array
	 */
	public function _require_authnet_20( $enabled_gateways = array() ) {
		if ( is_admin() || ( defined( 'CS_DOING_AJAX' ) && ! CS_DOING_AJAX ) ) {
			return $enabled_gateways;
		}

		if (
			isset( $enabled_gateways['authorize'] ) &&
			defined( 'CSA_VERSION' ) &&
			! version_compare( CSA_VERSION, '1.1.3', '>' )
		) {
			unset( $enabled_gateways['authorize'] );
		}

		return $enabled_gateways;
	}

}

/**
 * The main function responsible for returning the one true CS_Recurring Instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $recurring = CS_Recurring(); ?>
 *
 * @since v1.0
 *
 * @return CS_Recurring The one true CS_Recurring Instance
 */
function CS_Recurring() {

	if ( ! class_exists( 'CommerceStore' ) ) {
		return;
	}

	return CS_Recurring::instance();
}
add_action( 'plugins_loaded', 'CS_Recurring', 98 );


/**
 * Install hook
 *
 * @since v2.4
 */
function cs_recurring_install() {

	global $wpdb;

	CS_Recurring();

	if ( class_exists( 'CS_Subscriptions_DB' ) ) {

		$db = new CS_Subscriptions_DB;
		@$db->create_table();

		add_role( 'cs_subscriber', __( 'CS Subscriber', 'commercestore' ), array( 'read' ) );

		$version = get_option( 'cs_recurring_version' );

		if( ! is_admin() ) {
			// Make sure our admin files with cs_recurring_needs_24_stripe_fix() definition are loaded
			CS_Recurring()->includes_admin();
		}

		if ( ! function_exists( 'cs_set_upgrade_complete' ) ) {
			require_once CS_PLUGIN_DIR . 'includes/admin/upgrades/upgrade-functions.php';
		}

		if( empty( $version ) ) {

			// This is a new install or an update from pre 2.4, look to see if we have recurring products
			$has_recurring = $wpdb->get_var( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'cs_period' OR ( meta_key = 'cs_variable_prices' AND meta_value LIKE '%recurring%' AND meta_value LIKE '%yes%' ) AND 1=1 LIMIT 1" );
			$needs_upgrade = ! empty( $has_recurring );

			if( ! $needs_upgrade ) {
				// Make sure this upgrade routine is never shown as needed
				cs_set_upgrade_complete( 'upgrade_24_subscriptions' );
			}

			$total_sql = "SELECT COUNT(ID) as total_error_logs FROM $wpdb->posts WHERE post_title = 'PayPal Express Error' AND post_type = 'cs_log'";
			$results   = $wpdb->get_row( $total_sql );
			$total     = $results->total_error_logs;

			// Set any other upgrades as completed on a fresh install.
			cs_set_upgrade_complete( 'recurring_paypalproexpress_logs' );
			cs_set_upgrade_complete( 'recurring_add_price_id_column' );
			cs_set_upgrade_complete( 'recurring_update_price_id_column' );
			cs_set_upgrade_complete( 'recurring_cancel_subs_if_times_met' );
			cs_set_upgrade_complete( 'recurring_add_tax_columns_to_subs_table' );
			cs_set_upgrade_complete( 'recurring_27_subscription_meta' );
			cs_set_upgrade_complete( 'recurring_increase_transaction_profile_id_cols_and_collate' );
			cs_set_upgrade_complete( 'recurring_wipe_invalid_paypal_plan_ids' );
		}

		if ( false === cs_recurring_needs_24_stripe_fix() ) {
			cs_set_upgrade_complete( 'fix_24_stripe_customers' );
		}
		if ( ! cs_has_upgrade_completed( 'recurring_increase_transaction_profile_id_cols_and_collate' ) ) {
			@$db->create_table();
			cs_set_upgrade_complete( 'recurring_increase_transaction_profile_id_cols_and_collate' );
		}

		update_option( 'cs_recurring_version', CS_RECURRING_VERSION );

	}

	if ( class_exists( 'CS_Recurring_PayPal_Commerce' ) && function_exists( '\\CS\\Gateways\\PayPal\\Webhooks\\sync_webhook' ) && \CS\Gateways\PayPal\has_rest_api_connection() ) {
		try {
			global $wp_rewrite;

			/*
			 * If `$wp_rewrite` isn't available, we can't get the REST API endpoint URL, which
			 * would cause a fatal during webhook syncing.
			 * @link https://github.com/commercestore/cs-recurring/pull/1451#issuecomment-871515068
			 */
			if ( empty( $wp_rewrite ) ) {
				$wp_rewrite = new WP_Rewrite();
			}

			\CS\Gateways\PayPal\Webhooks\sync_webhook();
		} catch ( \Exception $e ) {

		}
	}

}
register_activation_hook( __FILE__, 'cs_recurring_install' );
