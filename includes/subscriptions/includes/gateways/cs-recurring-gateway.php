<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_Recurring_Gateway {

	public $id;
	public $friendly_name = '';
	public $subscriptions = array();
	public $purchase_data = array();
	public $offsite = false;
	public $email = 0;
	public $customer_id = 0;
	public $user_id = 0;
	public $payment_id = 0;
	public $failed_subscriptions = array();
	public $custom_meta = array();

	/**
	 * Registers additionally supported functionalities for specific gateways.
	 *
	 * @since 2.9.0
	 * @type array
	 */
	public $supports = array();

	/**
	 * Get things started
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function __construct() {

		$this->init();

		add_action( 'cs_checkout_error_checks', array( $this, 'checkout_errors' ), 0, 2 );
		add_action( 'cs_gateway_' . $this->id, array( $this, 'process_checkout' ), 0 );
		add_action( 'init', array( $this, 'require_login' ), 9 );
		add_action( 'init', array( $this, 'process_webhooks' ), 9 );
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ), 10 );
		add_action( 'cs_cancel_subscription', array( $this, 'process_cancellation' ) );
		add_action( 'cs_reactivate_subscription', array( $this, 'process_reactivation' ) );
		add_filter( 'cs_subscription_can_cancel', array( $this, 'can_cancel' ), 10, 2 );
		add_filter( 'cs_subscription_can_update', array( $this, 'can_update' ), 10, 2 );
		add_filter( 'cs_subscription_can_reactivate', array( $this, 'can_reactivate' ), 10, 2 );
		add_filter( 'cs_subscription_can_retry', array( $this, 'can_retry' ), 10, 2 );
		add_filter( 'cs_recurring_retry_subscription_' . $this->id, array( $this, 'retry' ), 10, 2 );
		add_action( 'cs_recurring_cancel_' . $this->id . '_subscription', array( $this, 'cancel' ), 10, 2 );
		add_action( 'cs_recurring_reactivate_' . $this->id . '_subscription', array( $this, 'reactivate' ), 10, 2 );
		add_action( 'cs_recurring_update_payment_form', array( $this, 'update_payment_method_form' ), 10, 1 );
		add_action( 'cs_recurring_update_subscription_payment_method', array( $this, 'process_payment_method_update' ), 10, 3 );
		add_action( 'cs_recurring_update_' . $this->id . '_subscription', array( $this, 'update_payment_method' ), 10, 2 );
		add_action( 'cs_after_cc_fields', array( $this, 'after_cc_fields' ) );

		add_filter( 'cs_subscription_profile_link_' . $this->id, array( $this, 'link_profile_id' ), 10, 2 );
	}

	/**
	 * Setup gateway ID and possibly load API libraries
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function init() {

		$this->id = '';

	}

	/**
	 * Enqueue necessary scripts. Perhaps only enqueue when cs_is_checkout()
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function scripts() {
	}

	/**
	 * Validate checkout fields
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function validate_fields( $data, $posted ) {

		/*

		if( true ) {
			cs_set_error( 'error_id_here', __( 'Error message here', 'cs-recurring' ) );
		}

		*/

	}

	/**
	 * Whether or not payments should automatically be set to `complete` during `record_signup()`.
	 *
	 * Defaults to the opposite of `CS_Recurring_Gateway::$offsite`.
	 *
	 * @since 2.11
	 *
	 * @return bool
	 */
	protected function should_auto_complete_payment() {
		return ! $this->offsite;
	}

	/**
	 * Creates subscription payment profiles and sets the IDs so they can be stored
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function create_payment_profiles() {

		// Gateways loop through each download and creates a payment profile and then sets the profile ID

		foreach ( $this->subscriptions as $key => $subscription ) {
			$this->subscriptions[ $key ]['profile_id'] = '1234';
		}

	}

	/**
	 * Finishes the signup process by redirecting to the success page or to an off-site payment page
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function complete_signup() {

		wp_redirect( cs_get_success_page_uri() );
		exit;
	}

	/**
	 * Processes webhooks from the payment processor
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function process_webhooks() {

		// set webhook URL to: home_url( 'index.php?cs-listener=' . $this->id );

		if ( empty( $_GET['cs-listener'] ) || $this->id !== $_GET['cs-listener'] ) {
			return;
		}

		// process webhooks here

	}

	/**
	 * Determines if a subscription can be cancelled through the gateway
	 *
	 * @access      public
	 * @since       2.4
	 * @return      bool
	 */
	public function can_cancel( $ret, $subscription ) {
		return $ret;
	}

	/**
	 * Returns an array of subscription statuses that can be cancelled
	 *
	 * @access      public
	 * @since       2.6.3
	 * @return      array
	 */
	public function get_cancellable_statuses() {
		return apply_filters( 'cs_recurring_cancellable_statuses', array( 'active', 'trialling', 'failing' ) );
	}

	/**
	 * Cancels a subscription. If possible, cancel at the period end. If not possible, cancel immediately.
	 *
	 * @access      public
	 * @since       2.4
	 * @param       CS_Subscription $subscription The CS_Subscription object for the CommerceStore Subscription being cancelled.
	 * @param       bool             $valid Currently this defaults to be true at all times.
	 * @return      bool
	 */
	public function cancel( $subscription, $valid ) {}

	/**
	 * Cancels a subscription immediately.
	 *
	 * @access      public
	 * @since       2.4
	 * @param       CS_Subscription $subscription The CS_Subscription object for the CommerceStore Subscription being cancelled.
	 * @return      bool
	 */
	public function cancel_immediately( $subscription ) {
		// Fallback to the original cancel method.
		return $this->cancel( $subscription, true );
	}

	/**
	 * Determines if a subscription can be reactivated through the gateway
	 *
	 * @access      public
	 * @since       2.7.10
	 * @return      bool
	 */
	public function can_reactivate( $ret, $subscription ) {
		return $ret;
	}

	/**
	 * Reactivates a cancelled subscription
	 *
	 * @access      public
	 * @since       2.7.10
	 * @return      bool
	 */
	public function reactivate( $subscription, $valid ) {}

	/**
	 * Determines if a subscription can be retried through the gateway
	 *
	 * @access      public
	 * @since       2.7.10
	 * @return      bool
	 */
	public function can_retry( $ret, $subscription ) {
		return $ret;
	}

	/**
	 * Retries a failing subscription
	 *
	 * This method is connected to a filter instead of an action so we can return a nice error message.
	 *
	 * @access      public
	 * @since       2.7.10
	 * @return      bool|WP_Error
	 */
	public function retry( $result, $subscription ) {
		return $result;
	}

	/**
	 * Determines if a subscription can be cancelled through a gateway
	 *
	 * @since  2.4
	 * @param  bool   $ret            Default stting (false)
	 * @param  object $subscription   The subscription
	 * @return bool
	 */
	public function can_update( $ret, $subscription ) {
		return $ret;
	}

	/**
	 * Process the update payment form
	 *
	 * @since  2.4
	 * @param  int  $subscriber    CS_Recurring_Subscriber
	 * @param  int  $subscription  CS_Subscription
	 * @return void
	 */
	public function update_payment_method( $subscriber, $subscription ) { }

	/**
	 * Outputs the payment method update form
	 *
	 * @since  2.4
	 * @param  object $subscription The subscription object
	 * @return void
	 */
	public function update_payment_method_form( $subscription ) {

		if ( $subscription->gateway !== $this->id ) {
			return;
		}

		ob_start();
		cs_get_cc_form();
		echo ob_get_clean();

	}

	/**
	 * Get the expiration date with merchant processor
	 *
	 * @since  2.6.6
	 * @param  object $subscription The subscription object
	 * @return string Expiration date in Y-n-d H:i:s format
	 */
	public function get_expiration( $subscription ) {

		// Return existing expiration date by default
		return date( 'Y-n-d H:i:s', $subscription->get_expiration_time() );
	}

	/**
	 * Outputs any information after the Credit Card Fields
	 *
	 * @since  2.4
	 * @return void
	 */
	public function after_cc_fields() {}

	/**
	 * Determines if the gateway allows multiple subscriptions to be purchased at once.

	 * @since 2.8.5
	 * @return bool
	 */
	public function can_purchase_multiple_subs() {
		return true;
	}


	/****************************************************************
	 * Below methods should not be extended except in rare cases
	 ***************************************************************/


	/**
	 * Processes the checkout screen and sends sets up the subscription data for hand-off to the gateway
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function process_checkout( $purchase_data ) {

		if ( ! cs_recurring()->is_purchase_recurring( $purchase_data ) ) {
			return; // Not a recurring purchase so bail
		}

		if ( ! wp_verify_nonce( $purchase_data['gateway_nonce'], 'cs-gateway' ) ) {
			wp_die( __( 'Nonce verification has failed', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}

		if ( $purchase_data['user_info']['id'] < 1 && ! class_exists( 'CS_Auto_Register' ) ) {
			cs_set_error( 'cs_recurring_logged_in', __( 'You must log in or create an account to purchase a subscription', 'commercestore' ) );
		}

		// Never let a user_id be lower than 0 since WP Core absints when doing get_user_meta lookups
		if ( $purchase_data['user_info']['id'] < 1 ) {
			$purchase_data['user_info']['id'] = 0;
		}

		// Initial validation
		do_action( 'cs_recurring_process_checkout', $purchase_data, $this );

		$errors = cs_get_errors();

		if ( $errors ) {

			cs_send_back_to_checkout( '?payment-mode=' . $this->id );

		}

		$this->purchase_data = apply_filters( 'cs_recurring_purchase_data', $purchase_data, $this );
		$this->user_id       = $purchase_data['user_info']['id'];
		$this->email         = $purchase_data['user_info']['email'];

		if ( empty( $this->user_id ) ) {
			$subscriber = new CS_Recurring_Subscriber( $this->email );
		} else {
			$subscriber = new CS_Recurring_Subscriber( $this->user_id, true );
		}

		if ( empty( $subscriber->id ) ) {

			$name = '';
			if ( ! empty( $purchase_data['user_info']['first_name'] ) ) {
				$name = $purchase_data['user_info']['first_name'];
			}

			if( ! empty( $purchase_data['user_info']['last_name'] ) ) {
				$name .= ' ' . $purchase_data['user_info']['last_name'];
			}

			$subscriber_data = array(
				'name'        => $name,
				'email'       => $purchase_data['user_info']['email'],
				'user_id'     => $this->user_id,
			);

			$subscriber->create( $subscriber_data );

		}

		$this->customer_id = $subscriber->id;

		foreach ( $this->purchase_data['cart_details'] as $key => $item ) {

			if ( ! isset( $item['item_number']['options'] ) || ! isset( $item['item_number']['options']['recurring'] ) ) {
				continue;
			}

			// Check if one time discounts are enabled in the admin settings, which prevent discounts from being used on renewals
			$recurring_one_time_discounts = cs_get_option( 'recurring_one_time_discounts' ) ? true : false;

			// If there is a trial in the cart for this item, One-Time Discounts have no relevance, and discounts are used no matter what.
			if( ! empty( $item['item_number']['options']['recurring']['trial_period']['unit'] ) && ! empty( $item['item_number']['options']['recurring']['trial_period']['quantity'] ) ) {
				$recurring_one_time_discounts = false;
			}

			$prices_include_tax         = cs_prices_include_tax();
			$download_is_tax_exclusive  = cs_download_is_tax_exclusive( $item['id'] );

			// If we should NOT apply the discount to the renewal
			if( $recurring_one_time_discounts ) {

				// If entered prices do not include tax
				if ( ! $prices_include_tax ) {

					// Set the tax to be the full amount as well for recurs. Recalculate it using the amount without discounts, which is the subtotal
					$recurring_tax = $download_is_tax_exclusive ? 0 : cs_calculate_tax( $item['subtotal'] );

					// When prices don't include tax, the $item['subtotal'] is the cost of the item, including quantities, but NOT including discounts or taxes
					// Set the recurring amount to be the full amount, with no discounts
					$recurring_amount = $item['subtotal'] + $recurring_tax;

				} else {

					// If prices include tax, we can't use the $item['subtotal'] like we do above, because it does not include taxes, and we need it to include taxes.
					// So instead, we use the item_price, which is the entered price of the product, without any discounts, and with taxes included.
					$recurring_amount = $item['item_price'];
					$recurring_tax    = $download_is_tax_exclusive ? 0 : cs_calculate_tax( $item['item_price'] );

				}

			} else {

				// The $item['price'] includes all discounts and taxes.
				// Since discounts are allowed on renewals, we don't need to make any changes at all to the price or the tax.
				$recurring_amount = $item['price'];
				$recurring_tax    = $download_is_tax_exclusive ? 0 : $item['tax'];

			}

			$fees = $item['item_number']['options']['recurring']['signup_fee'];

			if( ! empty( $item['fees'] ) ) {
				foreach( $item['fees'] as $fee ) {

					// Negative fees are already accounted for on $item['price']
					if( $fee['amount'] <= 0 ) {
						continue;
					}

					$fees += $fee['amount'];
				}

			}

			/**
			 * Determine tax amount for any fees if it's more than $0
			 *
			 * Fees (at this time) must be exclusive of tax
			 * @see CS_Cart::get_tax_on_fees()
			 */
			add_filter( 'cs_prices_include_tax', '__return_false' );
			$fee_tax = $fees > 0 ? cs_calculate_tax( $fees ) : 0;
			remove_filter( 'cs_prices_include_tax', '__return_false' );

			// Format the tax rate.
			$tax_rate = round( floatval( $this->purchase_data['tax_rate'] ), 4 );
			if ( 4 > strlen( $tax_rate ) ) {
				/*
				 * Enforce a minimum of 2 decimals for backwards compatibility.
				 * @link https://github.com/commercestore/cs-recurring/pull/1386#issuecomment-745350210
				 */
				$tax_rate = number_format( $tax_rate, 2, '.', '' );
			}

			$args = array(
				'cart_index'         => $key,
				'id'                 => $item['id'],
				'name'               => $item['name'],
				'price_id'           => isset( $item['item_number']['options']['price_id'] ) ? $item['item_number']['options']['price_id'] : false,
				'initial_amount'     => cs_sanitize_amount( $item['price'] + $fees + $fee_tax ),
				'recurring_amount'   => cs_sanitize_amount( $recurring_amount ),
				'initial_tax'        => cs_use_taxes() ? cs_sanitize_amount( $item['tax'] + $fee_tax ) : 0,
				'initial_tax_rate'   => $tax_rate,
				'recurring_tax'      => cs_use_taxes() ? cs_sanitize_amount( $recurring_tax ) : 0,
				'recurring_tax_rate' => $tax_rate,
				'signup_fee'         => cs_sanitize_amount( $fees ),
				'period'             => $item['item_number']['options']['recurring']['period'],
				'frequency'          => 1, // Hard-coded to 1 for now but here in case we offer it later. Example: charge every 3 weeks
				'bill_times'         => $item['item_number']['options']['recurring']['times'],
				'profile_id'         => '', // Profile ID for this subscription - This is set by the payment gateway
				'transaction_id'     => '', // Transaction ID for this subscription - This is set by the payment gateway
			);

			$args = apply_filters( 'cs_recurring_subscription_pre_gateway_args', $args, $item );

			if( ! cs_get_option( 'recurring_one_time_trials' ) || ! $subscriber->has_trialed( $item['id'] ) ) {

				// If the item in the cart has a free trial period
				if( ! empty( $item['item_number']['options']['recurring']['trial_period']['unit'] ) && ! empty( $item['item_number']['options']['recurring']['trial_period']['quantity'] ) ) {

					$args['has_trial']         = true;
					$args['trial_unit']        = $item['item_number']['options']['recurring']['trial_period']['unit'];
					$args['trial_quantity']    = $item['item_number']['options']['recurring']['trial_period']['quantity'];
					$args['status']            = 'trialling';
					$args['initial_amount']    = 0;
					$args['initial_tax_rate']  = 0;
					$args['initial_tax']       = 0;
				}

			}

			$this->subscriptions[] = $args;
		}

		// Store this so we can detect if the count changes due to failed subscriptions
		$initial_subscription_count = count( $this->subscriptions );

		do_action( 'cs_recurring_pre_create_payment_profiles', $this );

		if ( ! is_user_logged_in() ) {
			cs_set_error( 'cs_recurring_login', __( 'You must be logged in to purchase a subscription', 'commercestore' ) );

			$this->handle_errors( cs_get_errors() );
		}

		// Create subscription payment profiles in the gateway
		$this->create_payment_profiles();

		// See if the gateway reported some subscriptions that failed
		if ( ! empty( $this->failed_subscriptions ) ) {

			// See if any subscriptions failed and remove them if necessary
			foreach ( $this->failed_subscriptions as $failed_sub ) {

				$item_key = $failed_sub['key'];
				// Remove it from the subscriptions array so we don't create an CommerceStore Subscription entry
				unset( $this->subscriptions[ $item_key ] );

				// Remove it from the cart details and downloads so we don't charge the customer and give accees to it
				unset( $this->purchase_data['downloads'][ $item_key ] );
				unset( $this->purchase_data['cart_details'][ $item_key ] );

			}

			// Since we allow subscriptions to be marked as failed, make sure that we at least have one valid subscription
			if ( count( $this->failed_subscriptions ) === $initial_subscription_count ) {
				if ( ! empty( $failed_sub['error'] ) ) {
					cs_set_error( 'recurring-failed-sub-error-' . $item_key, $failed_sub['error'] );
				} else {
					cs_set_error( 'recurring-all-subscriptions-failed', __( 'There was an error processing your order. Please contact support.', 'commercestore' ) );
				}
			}

		}

		do_action( 'cs_recurring_post_create_payment_profiles', $this );

		// Look for errors after trying to create payment profiles
		$errors = cs_get_errors();

		if ( $errors ) {
			$this->handle_errors( $errors );
		}

		// Record the subscriptions and finish up
		$this->record_signup();

		// Finish the signup process. Gateways can perform off-site redirects here if necessary
		$this->complete_signup();

		// Look for any last errors
		$errors = cs_get_errors();

		// We shouldn't usually get here, but just in case a new error was recorded, we need to check for it
		if ( $errors ) {
			$this->handle_errors( $errors );
		}

	}

	/**
	 * Handles errors that occur during checkout processing.
	 *
	 * @param array|false $errors
	 *
	 * @since 2.11
	 */
	protected function handle_errors( $errors = false ) {
		cs_send_back_to_checkout( '?payment-mode=' . $this->id );
	}

	/**
	 * Records purchased subscriptions in the database and creates an cs_payment record
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function record_signup() {


		$payment_data = array(
			'price'        => $this->purchase_data['price'],
			'date'         => $this->purchase_data['date'],
			'user_email'   => $this->purchase_data['user_email'],
			'purchase_key' => $this->purchase_data['purchase_key'],
			'currency'     => cs_get_currency(),
			'downloads'    => $this->purchase_data['downloads'],
			'user_info'    => $this->purchase_data['user_info'],
			'cart_details' => $this->purchase_data['cart_details'],
			'status'       => 'pending',
		);

		foreach( $this->subscriptions as $key => $item ) {

			if ( ! empty( $item['has_trial'] ) ) {
				$payment_data['cart_details'][ $key ]['item_price'] = $item['initial_amount'] - $item['initial_tax'];
				$payment_data['cart_details'][ $key ]['tax']        = $item['initial_tax'];
				$payment_data['cart_details'][ $key ]['price']      = 0;
				$payment_data['cart_details'][ $key ]['discount']   = 0;

			}

		}

		// Record the pending payment
		$this->payment_id = cs_insert_payment( $payment_data );
		$payment          = cs_get_payment( $this->payment_id );

		if ( $this->should_auto_complete_payment() ) {

			// Offsite payments get verified via a webhook so are completed in webhooks()
			$payment->status = 'publish';
			$payment->save();

		}

		// Set subscription_payment
		$payment->update_meta( '_cs_subscription_payment', true );


		/*
		 * We need to delete pending subscription records to prevent duplicates. This ensures no duplicate subscription records are created when a purchase is being recovered. See:
		 * https://github.com/commercestore/cs-recurring/issues/707
		 * https://github.com/commercestore/cs-recurring/issues/762
		 */
		global $wpdb;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}cs_subscriptions WHERE parent_payment_id = %d AND status = 'pending';", $this->payment_id ) );

		$subscriber = new CS_Recurring_Subscriber( $this->customer_id );

		// Now create the subscription record(s)
		foreach ( $this->subscriptions as $subscription ) {

			if( isset( $subscription['status'] ) ) {
				$status  = $subscription['status'];
			} else {
				$status = ! $this->should_auto_complete_payment() ? 'pending' : 'active';
			}

			$trial_period = ! empty( $subscription['has_trial'] ) ? $subscription['trial_quantity'] . ' ' . $subscription['trial_unit'] : '';
			$expiration   = $subscriber->get_new_expiration( $subscription['id'], $subscription['price_id'], $trial_period );

			// Check and see if we have a custom recurring period from the Custom Prices extension.
			if ( defined( 'CS_CUSTOM_PRICES' ) ) {

				$cart_item = $this->purchase_data['cart_details'][ $subscription['cart_index'] ];

				if ( isset( $cart_item['item_number']['options']['custom_price'] ) ) {
					switch( $subscription['period'] ) {

						case 'quarter' :

							$period = '+ 3 months';

							break;

						case 'semi-year' :

							$period = '+ 6 months';

							break;

						default :

							$period = '+ 1 ' . $subscription['period'];

							break;

					}

					$expiration = date( 'Y-m-d H:i:s', strtotime( $period . ' 23:59:59', current_time( 'timestamp' ) ) );
				}

			}

			$args = array(
				'product_id'            => $subscription['id'],
				'price_id'              => isset( $subscription['price_id'] ) ? $subscription['price_id'] : null,
				'user_id'               => $this->purchase_data['user_info']['id'],
				'parent_payment_id'     => $this->payment_id,
				'status'                => $status,
				'period'                => $subscription['period'],
				'initial_amount'        => $subscription['initial_amount'],
				'initial_tax_rate'      => $subscription['initial_tax_rate'],
				'initial_tax'           => $subscription['initial_tax'],
				'recurring_amount'      => $subscription['recurring_amount'],
				'recurring_tax_rate'    => $subscription['recurring_tax_rate'],
				'recurring_tax'         => $subscription['recurring_tax'],
				'bill_times'            => $subscription['bill_times'],
				'expiration'            => $expiration,
				'trial_period'          => $trial_period,
				'profile_id'            => $subscription['profile_id'],
				'transaction_id'        => $subscription['transaction_id'],
			);

			$args = apply_filters( 'cs_recurring_pre_record_signup_args', $args, $this );
			$sub = $subscriber->add_subscription( $args );

			if ( $this->should_auto_complete_payment() && $trial_period ) {
				$subscriber->add_meta( 'cs_recurring_trials', $subscription['id'] );
			}

			/**
			 * Triggers right after a subscription is created.
			 *
			 * @param CS_Subscription      $sub          New subscription object.
			 * @param array                 $subscription Gateway subscription arguments.
			 * @param CS_Recurring_Gateway $this         Gateway object.
			 *
			 * @since 2.10.2
			 */
			do_action( 'cs_recurring_post_record_signup', $sub, $subscription, $this );

		}

		// Now look if the gateway reported any failed subscriptions and log a payment note
		if ( ! empty( $this->failed_subscriptions ) ) {

			foreach ( $this->failed_subscriptions as $failed_subscription ) {
				$note = sprintf( __( 'Failed creating subscription for %s. Gateway returned: %s', 'commercestore' ), $failed_subscription['subscription']['name'], $failed_subscription['error'] );
				$payment->add_note( $note );
			}

			$payment->update_meta( '_cs_recurring_failed_subscriptions', $this->failed_subscriptions );
		}

		if ( ! empty( $this->custom_meta ) ) {
			foreach ( $this->custom_meta as $key => $value ) {
				$payment->update_meta( $key, $value );
			}
		}

	}

	/**
	 * Triggers the validate_fields() method for the gateway during checkout submission
	 *
	 * This should not be extended
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function checkout_errors( $data, $posted ) {

		if ( $this->id !== $posted['cs-gateway'] ) {
			return;
		}

		if ( ! cs_recurring()->cart_contains_recurring() ) {
			return;
		}

		if ( cs_recurring()->cart_is_mixed_with_trials() ) {
			cs_set_error( 'cs_recurring_mixed_trials_cart', __( 'Free trials and non-trials may not be purchased at the same time. Please purchase each separately.', 'commercestore' ) );
		}

		// Show errors related to mixed carts.

		$enabled_gateways = cs_get_enabled_payment_gateways();

		$show_mixed_error = (
			! in_array( 'mixed_cart', $this->supports, true ) &&
			cs_recurring()->cart_is_mixed()
		);

		if ( $show_mixed_error ) {

			if ( ! isset( $enabled_gateways['stripe'] ) ) {

				// Show generic error to non show managers if no other gateways can be used to complete the purchase.
				if ( ! current_user_can( 'manage_shop_settings' ) ) {
					cs_set_error( 'cs_recurring_mixed_cart', __( 'Subscriptions and non-subscriptions may not be purchased at the same time. Please purchase each separately.', 'commercestore' ) );

				// Alert shop managers that Stripe supports mixed carts.
				} else {
					cs_set_error(
						'cs_recurring_mixed_cart_install_gateway',
						wp_kses(
							wpautop(
								'<em>' . __( 'This message is showing because you are a shop manager', 'commercestore' ) . '</em>'
							) .
							wpautop(
								sprintf(
									/** translators: %1$s Opening anchor tag, do not translate. %2$s Closing anchor tag, do not translate. */
									__( 'Your active payment gateways do not support mixed carts. The %1$sStripe Payment Gateway%2$s allows customers to purchase carts containing both recurring subscriptions and one-time charges at the same time.', 'commercestore' ),
									'<a href="https://commercestore.com/downloads/stripe-gateway/?utm_source=checkout&utm_medium=recurring&utm_campaign=admin" rel="noopener noreferrer" target="_blank">',
									'</a>'
								)
							),
							array(
								'em' => true,
								'p'  => true,
								'a'  => array(
									'href'   => true,
									'rel'    => true,
									'target' => true,
								),
							)
						)
					);
				}

			// Show an error to switch to the Stripe gateway to complete purchase.
			} else {

				$gateway_checkout_uri = add_query_arg(
					array(
						'payment-mode' => 'stripe',
					),
					cs_get_checkout_uri()
				);

				cs_set_error(
					'cs_recurring_mixed_cart_use_gateway',
					wp_kses(
						sprintf(
							__( 'Sorry, purchasing a subscription and non-subscription product is only supported when paying by credit card. %1$sSwitch to this payment method%2$s.', 'commercestore' ),
							'<a href="' . esc_url( $gateway_checkout_uri ) . '">',
							'</a>'
						),
						array(
							'a' => array(
								'href'   => true,
							),
						)
					)
				);
			}
		}

		$this->validate_fields( $data, $posted );
	}

	/**
	 * Process the update payment form
	 *
	 * @since  2.4
	 * @param  int  $user_id            User ID
	 * @param  int  $subscription_id    Subscription ID
	 * @param  bool $verified           Sanity check that the request to update is coming from a verified source
	 * @return void
	 */
	public function process_payment_method_update( $user_id, $subscription_id, $verified ) {

		if ( 1 !== $verified ) {
			wp_die( __( 'Unable to verify payment update.', 'commercestore' ) );
		}

		if ( ! is_user_logged_in() ) {
			wp_die( __( 'You must be logged in to update a payment method.', 'commercestore' ) );
		}

		$subscription = new CS_Subscription( $subscription_id );
		if ( $subscription->gateway !== $this->id ) {
			return;
		}

		if ( empty( $subscription->id ) ) {
			wp_die( __( 'Invalid subscription id.', 'commercestore' ) );
		}

		$subscriber   = new CS_Recurring_Subscriber( $subscription->customer_id );
		if ( empty( $subscriber->id ) ) {
			wp_die( __( 'Invalid subscriber.', 'commercestore' ) );
		}

		// Make sure the User doing the udpate is the user the subscription belongs to
		if ( $user_id != $subscriber->user_id ) {
			wp_die( __( 'User ID and Subscriber do not match.', 'commercestore' ) );
		}

		// make sure we don't have any left over errors present
		cs_clear_errors();

		do_action( 'cs_recurring_update_' . $subscription->gateway .'_subscription', $subscriber, $subscription );

		$errors = cs_get_errors();

		if ( empty( $errors ) ) {

			$url = add_query_arg( array( 'updated' => true ) );
			wp_redirect( $url );
			die();
		}

		$url = add_query_arg( array( 'action' => 'update', 'subscription_id' => $subscription->id ) );
		wp_redirect( $url );
		die();

	}

	/**
	 * Handles cancellation requests for a subscription
	 *
	 * This should not be extended
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function process_cancellation( $data ) {

		if( empty( $data['sub_id'] ) ) {
			return;
		}

		if( ! is_user_logged_in() ) {
			return;
		}

		if( ! wp_verify_nonce( $data['_wpnonce'], 'cs-recurring-cancel' ) ) {
			wp_die( __( 'Nonce verification failed', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}

		$data['sub_id'] = absint( $data['sub_id'] );
		$subscription   = new CS_Subscription( $data['sub_id'] );

		try {

			$subscription->cancel();

			if( is_admin() ) {

				wp_redirect( admin_url( 'edit.php?post_type=' . CS_POST_TYPE . '&page=cs-subscriptions&cs-message=cancelled&id=' . $subscription->id ) );
				exit;

			} else {

				$redirect = remove_query_arg( array( '_wpnonce', 'cs_action', 'sub_id' ), add_query_arg( array( 'cs-message' => 'cancelled' ) ) );
				$redirect = apply_filters( 'cs_recurring_cancellation_redirect', $redirect, $subscription );
				wp_safe_redirect( $redirect );
				exit;

			}

		} catch ( Exception $e ) {
			wp_die( $e->getMessage(), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}

	}

	/**
	 * Process subscription reactivation
	 *
	 * @access      public
	 * @since       2.6
	 * @return      void
	 */
	public function process_reactivation( $data ) {

		if( empty( $data['sub_id'] ) ) {
			return;
		}

		if( ! is_user_logged_in() ) {
			return;
		}

		if( ! wp_verify_nonce( $data['_wpnonce'], 'cs-recurring-reactivate' ) ) {
			wp_die( __( 'Nonce verification failed', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}


		$data['sub_id'] = absint( $data['sub_id'] );
		$subscription   = new CS_Subscription( $data['sub_id'] );

		if( ! $subscription->can_reactivate() ) {
			wp_die( __( 'This subscription cannot be reactivated', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}

		try {

			do_action( 'cs_recurring_reactivate_' . $subscription->gateway . '_subscription', $subscription, true );

			$user = is_user_logged_in() ? wp_get_current_user()->user_login : 'gateway';
			$note = sprintf( __( 'Subscription reactivated by %s', 'commercestore' ), $user );
			$subscription->add_note( $note );

			if( is_admin() ) {

				wp_redirect( admin_url( 'edit.php?post_type=' . CS_POST_TYPE . '&page=cs-subscriptions&cs-message=reactivated&id=' . $subscription->id ) );
				exit;

			} else {

				$redirect = remove_query_arg( array( '_wpnonce', 'cs_action', 'sub_id' ), add_query_arg( array( 'cs-message' => 'reactivated' ) ) );
				$redirect = apply_filters( 'cs_recurring_reactivation_redirect', $redirect, $subscription );
				wp_safe_redirect( $redirect );
				exit;

			}

		} catch ( Exception $e ) {
			wp_die( $e->getMessage(), __( 'Error', 'commercestore' ), array( 'response' => 403 ) );
		}

	}

	/**
	 * Make it so that accounts are required
	 *
	 * @access      public
	 * @since       2.4
	 * @return      void
	 */
	public function require_login() {

		$cart_items    = cs_get_cart_contents();
		$has_recurring = false;

		if ( empty( $cart_items ) ) {
			return;
		}


		// Loops through each item to see if any of them are recurring
		foreach( $cart_items as $item ) {

			if( ! isset( $item['options']['recurring'] ) ) {
				continue;
			}

			$has_recurring = true;

		}

		$auto_register = class_exists( 'CS_Auto_Register' );

		if( $has_recurring && ! $auto_register ) {

			add_filter( 'cs_no_guest_checkout', '__return_true' );
			add_filter( 'cs_logged_in_only', '__return_true' );

		}

	}

	/**
	 * Retrieve subscription details
	 *
	 * This method should be extended by each gateway in order to call the gateway API to determine the status and expiration of the subscription
	 *
	 * @access      public
	 * @since       2.4
	 * @return      array
	 */
	public function get_subscription_details( CS_Subscription $subscription ) {

		/*
		 * Return value for valid subscriptions should be an array containing the following keys:
		 *
		 * - status: The status of the subscription (active, cancelled, expired, completed, pending, failing)
		 * - expiration: The expiration / renewal date of the subscription
		 * - error: An instance of WP_Error with error code and message (if any)
		 */

		$ret = array(
			'status'     => '',
			'expiration' => '',
			'error'      => '',
		);

		return $ret;

	}

	public function link_profile_id( $profile_id, $subscription ) {
		return $profile_id;
	}

}
