<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $cs_recurring_stripe;

class CS_Recurring_Stripe extends CS_Recurring_Gateway {

	/**
	 * Store \CS_Payment object once retrieved.
	 *
	 * @since 2.9.0
	 *
	 * @type \CS_Payment
	 */
	private $payment;

	/**
	 * Store \CS_Subscriber object once retrieved.
	 *
	 * @since 2.9.0
	 *
	 * @type \CS_Recurring_Subscriber
	 */
	private $subscriber;

	/**
	 * Registers additionally supported functionalities for specific gateways.
	 *
	 * @since 2.9.0
	 *
	 * @type array
	 */
	public $supports = array();

	/**
	 * Ensures Easy Digital Downloads - Stripe Payment Gateway is active.
	 *
	 * @since unknown
	 */
	public function __construct() {
		if ( ! defined( 'CSS_PLUGIN_DIR' ) ) {
			return;
		}

		parent::__construct();

		// Ensure Stripe 2.7.0+ is available.
		add_filter( 'cs_enabled_payment_gateways', array( $this, '_require_stripe_270' ), 20 );
		add_action( 'admin_notices', array( $this, '_require_stripe_270_notice' ) );
	}

	/**
	 * Registers gateway and hooks.
	 *
	 * @since unknown
	 */
	public function init() {
		$this->id            = 'stripe';
		$this->friendly_name = __( 'Stripe', 'cs-recurring' );
		$this->supports      = array(
			'mixed_cart',
		);

		// Make sure the user is logged in if they are using an already existing user email.
		add_action( 'css_pre_process_purchase_form', array( $this, 'require_login_for_existing_users' ) );

		// Watch for subscription payment method updates.
		add_action( 'wp_ajax_cs_recurring_update_subscription_payment_method', array( $this, 'update_subscription_payment_method' ) );

		// Tell CommerceStore Auto Register to log its newly-created user in.
		add_filter( 'cs_auto_register_login_user', array( $this, 'auto_register' ) );

		// Bail early if the \Stripe\Customer currency does not match the stores.
		add_action( 'css_process_purchase_form_before_intent', array( $this, 'check_customer_currency' ), 10, 2 );

		// Purchase flow:

		// 0. Adjust \Stripe\PaymentIntent behavior for the parent \CS_payment.
		add_filter( 'css_create_payment_intent_args', array( $this, 'create_payment_intent_args' ), 10, 2 );

		// 1. Create \CS_Subscription(s) on initial gateway processing.
		// 2. Create \Stripe\Subscription(s).
		//    Remove any \CS_Subscription(s) that no longer have a corresponding \Stripe\Subscription.
		add_action( 'css_payment_created', array( $this, 'process_purchase_form' ), 20, 2 );

		// 3. Capture original \Stripe\PaymentIntent using an amount equal to the number of \Stripe\Subscription(s) created.
		add_action( 'css_capture_payment_intent', array( $this, 'capture_payment_intent' ) );

		// 4. Transition created \CS_Subscriptions to their next status.
		add_action( 'css_payment_complete', array( $this, 'complete_subscriptions' ) );

		add_action( 'cs_pre_refund_payment', array( $this, 'process_refund' ) );
		add_action( 'cs_recurring_stripe_check_txn', array( $this, 'check_transaction_id' ) );
		add_action( 'cs_recurring_setup_subscription', array( $this, 'maybe_check_subscription' ) );
		add_action( 'cs_subscription_completed', array( $this, 'cancel_on_completion' ), 10, 2 );

		// Ensure expiration date on renewal matches next invoice billing date.
		add_filter( 'cs_subscription_renewal_expiration', array( $this, 'set_renewal_expiration' ), 10, 3 );
		add_action( 'cs_recurring_setup_subscription', array( $this, 'check_renewal_expiration' ), 10, 1 );
	}

	/**
	 * Removes Stripe from active gateways if the base gateway < 2.7.0
	 *
	 * @since 2.9.0
	 *
	 * @param array $enabled_gateways Enabled gateways that allow purchasing.
	 * @return array
	 */
	public function _require_stripe_270( $enabled_gateways ) {
		if (
			isset( $enabled_gateways['stripe'] ) &&
			defined( 'CS_STRIPE_VERSION' ) &&
			! version_compare( CS_STRIPE_VERSION, '2.6.20', '>' )
		) {
			unset( $enabled_gateways['stripe'] );
		}

		return $enabled_gateways;
	}

	/**
	 * Adds notice if the base gateway < 2.7.0
	 *
	 * @since 2.9.0
	 */
	public function _require_stripe_270_notice() {
		remove_filter( 'cs_enabled_payment_gateways', array( $this, '_require_stripe_270' ), 20 );
		$enabled_gateways = cs_get_enabled_payment_gateways();
		add_filter( 'cs_enabled_payment_gateways', array( $this, '_require_stripe_270' ), 20 );

		if (
			isset( $enabled_gateways['stripe'] ) &&
			defined( 'CS_STRIPE_VERSION' ) &&
			! version_compare( CS_STRIPE_VERSION, '2.6.20', '>' )
		) {
			echo '<div class="notice notice-error">';

			echo wpautop( wp_kses(
				sprintf(
					/* translators: %1$s Opening strong tag, do not translate. %2$s Closing strong tag, do not translate. */
					__( '%1$sCredit card payments with Stripe are currently disabled.%2$s', 'cs-recurring' ),
					'<strong>',
					'</strong>'
				)
				. '<br />' .
				sprintf(
					/* translators: %1$s Opening code tag, do not translate. %2$s Closing code tag, do not translate. */
					__( 'To continue accepting recurring credit card payments with Stripe please update the Stripe Payment Gateway extension to version %1$s2.7%2$s.', 'cs-recurring' ),
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
	 * Require existing emails to log in prior to making a recurring purchase.
	 * This replaces the "require_login" function in the base gateway class because of
	 * some re-ordering which had to take place with PaymentIntents.
	 *
	 * @since 2.9.3
	 * @throws \Exception If a user account exists for the email in question and the user is logged out, throw an Exception.
	 * @return void
	 */
	public function require_login_for_existing_users() {

		$purchase_data = cs_get_purchase_session();

		if ( ! cs_recurring()->is_purchase_recurring( $purchase_data ) ) {
			return;
		}

		// Check if this email is already attached to a WP user.
		if ( email_exists( $purchase_data['user_email'] ) ) {
			// Check if the user exists and is not logged in.
			if ( ! is_user_logged_in() ) {
				/* translators: %1$s Email address of an existing account used during checkout. */
				throw new \Exception( sprintf( __( 'A customer account for %1$s already exists. Please log in to complete your purchase.', 'cs-recurring' ), esc_html( $purchase_data['user_email'] ) ) );
			}
		}
	}

	// Override methods that are automatically called in the parent class.
	public function process_checkout( $purchase_data ) {}
	public function complete_signup() {}
	public function create_payment_profiles() {}
	public function record_signup() {}

	/**
	 * Ensure subsequent API requests use the correct information.
	 *
	 * @since 2.9.0
	 */
	public function setup_stripe_api() {
		_doing_it_wrong(
			__METHOD__,
			__( 'Use csx_api_request() to make Stripe API requests.', 'cs-recurring' ),
			'2.10.0'
		);
	}

	/**
	 * Tell Auto Register to log the user in.
	 *
	 * @since  2.9.0
	 * @param  bool $should_log_in_user This indicates whether the user should be automatically logged in when their user is created by CommerceStore Auto Register.
	 * @return bool
	 */
	public function auto_register( $should_log_in_user ) {

		// If this is a manual payment, do not log the newly created user in, as it would just switch from the admin to the customer user.
		if ( isset( $_POST['manual_purchases'] ) ) { //phpcs:ignore WordPress.Security.NonceVerification.Missing
			return $should_log_in_user;
		}

		$purchase_data = cs_get_purchase_session();

		if ( ! cs_recurring()->is_purchase_recurring( $purchase_data ) ) {
			return $should_log_in_user;
		}

		return true;

	}

	/**
	 * Check the customer currency prior to allowing checkout.
	 *
	 * If a customer has previously purchased a subscription, any future subscriptions must be made in the same currency.
	 *
	 * @since 2.9.0
	 * @throws \Exception If the Stripe customer currency does not match the currency attempting to checkout, throw an Exception.
	 *
	 * @param array            $purchase_data Purchase data.
	 * @param \Stripe\Customer $customer Stripe Customer object.
	 */
	public function check_customer_currency( $purchase_data, $customer ) {
		if ( ! cs_recurring()->is_purchase_recurring( $purchase_data ) ) {
			return;
		}

		// First purchase, \Stripe\Customer has not taken an action that assigns
		// a currency, so any currency purchase can be made.
		if ( ! $customer->currency ) {
			return;
		}

		$store_currency    = strtolower( cs_get_currency() );
		$customer_currency = strtolower( $customer->currency );

		if ( $customer_currency !== $store_currency ) {
			throw new \Exception(
				sprintf(
					/* translators: %1$s Customer currency. */
					__( 'Unable to complete your purchase. Your order must be completed in %1$s.', 'cs-recurring' ),
					strtoupper( $customer->currency )
				)
			);
		}
	}

	/**
	 * Sets the PaymentIntent capture method to manual.
	 *
	 * Creating \Stripe\Subscriptions can fail individually.
	 * Capturing after all attempts have been made ensures we only charge
	 * for fulfilled items.
	 *
	 * @since 2.9.0
	 *
	 * @param array $payment_intent_args PaymentIntent creation arguments.
	 * @param array $purchase_data       Cart purchase data.
	 * @return array
	 */
	public function create_payment_intent_args( $payment_intent_args, $purchase_data ) {
		if ( cs_recurring()->is_purchase_recurring( $purchase_data ) ) {
			$payment_intent_args['capture_method'] = 'manual';
		}

		return $payment_intent_args;
	}

	/**
	 * Handles creating CS_Subscription and \Stripe\Subscription records
	 * on checkout form submission.
	 *
	 * @since 2.9.0
	 *
	 * @param array                                     $purchase_data Purchase data.
	 * @param \CS_Payment                              $payment CommerceStore Payment.
	 * @param \Stripe\PaymentIntent|\Stripe\SetupIntent $intent Created Stripe Intent.
	 */
	public function process_purchase_form( $payment, $intent ) {
		$purchase_data = cs_get_purchase_session();

		if ( ! cs_recurring()->is_purchase_recurring( $purchase_data ) ) {
			return;
		}

		// Store for direct access later.
		$this->payment       = $payment;
		$this->payment_id    = $payment->ID;
		$this->purchase_data = $purchase_data;

		$this->purchase_data = apply_filters( 'cs_recurring_purchase_data', $purchase_data, $this );
		$this->user_id       = $this->purchase_data['user_info']['id'];
		$this->email         = $this->purchase_data['user_info']['email'];

		// Never let a user_id be lower than 0 since WP Core absints when doing get_user_meta lookups
		if ( $this->purchase_data['user_info']['id'] < 1 ) {
			$this->purchase_data['user_info']['id'] = 0;
		}

		do_action( 'cs_recurring_process_checkout', $this->purchase_data, $this );

		$errors = cs_get_errors();

		// Throw an exception with the latest error (for backwards compat with `cs_recurring_process_checkout`).
		if ( $errors ) {
			throw new \Exception( current( cs_get_errors() ) );
		}

		// Use cart purchase data to find CS_Customer and CS_Recurring_Subscriber.
		$this->setup_customer_subscriber();

		// Map cart purchase data to gateway object (this).
		$this->build_subscriptions();

		// Use mapped data to create CS_Subscription records.
		$this->create_cs_subscriptions();

		// Save any custom meta added via hooks.
		$this->payment->update_meta( '_cs_subscription_payment', true );

		if ( ! empty( $this->custom_meta ) ) {
			foreach ( $this->custom_meta as $key => $value ) {
				$this->payment->update_meta( $key, $value );
			}
		}

		// Use mapped data to create \Stripe\Subscription records.
		$this->create_stripe_subscriptions( $intent );

		// There is a bug in CommerceStore core that causes adjusting tax amounts on
		// individual line items to improperly recalculate total taxes.
		//
		// Line item amounts are adjusted when a Subscription has a free trial
		// so the total amount captured is accurate.
		//
		// Set the value directly instead.
		//
		// @link https://github.com/commercestore/commercestore/issues/7385
		if ( cs_recurring()->cart_has_free_trial() ) {
			$this->payment->tax = 0;
			$this->payment->total = 0;
		}

		// Save any changes to parent \CS_Payment.
		$this->payment->save();
	}

	/**
	 * Sets up CS_Customer (ID only) and CS_Recurring_Subscriber based on purchase data.
	 *
	 * @todo This is not gateway-specific and can be moved up.
	 *
	 * @since 2.9.0
	 */
	public function setup_customer_subscriber() {
		if ( empty( $this->user_id ) ) {
			$subscriber = new CS_Recurring_Subscriber( $this->email );
		} else {
			$subscriber = new CS_Recurring_Subscriber( $this->user_id, true );
		}

		if ( empty( $subscriber->id ) ) {
			$name = '';

			if ( ! empty( $this->purchase_data['user_info']['first_name'] ) ) {
				$name = $this->purchase_data['user_info']['first_name'];
			}

			if ( ! empty( $this->purchase_data['user_info']['last_name'] ) ) {
				$name .= ' ' . $this->purchase_data['user_info']['last_name'];
			}

			$subscriber_data = array(
				'name'        => $name,
				'email'       => $this->purchase_data['user_info']['email'],
				'user_id'     => $this->user_id,
			);

			$subscriber->create( $subscriber_data );
		}

		$this->subscriber  = $subscriber;
		$this->customer_id = $subscriber->id;
	}

	/**
	 * Maps/normalizes cart data to a list of subscription data.
	 *
	 * @todo This is not gateway-specific and can be moved up.
	 *
	 * @since 2.9.0
	 */
	public function build_subscriptions() {
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

			if ( ! empty( $item['fees'] ) ) {
				foreach ( $item['fees'] as $fee ) {

					// Negative fees are already accounted for on $item['price']
					if ( $fee['amount'] <= 0 ) {
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
				'transaction_id'     => $this->payment->transaction_id, // No charges are created for the Subscription initially, so use the parent payment's transaction ID.
			);

			$args = apply_filters( 'cs_recurring_subscription_pre_gateway_args', $args, $item );

			if ( ! cs_get_option( 'recurring_one_time_trials' ) || ! $this->subscriber->has_trialed( $item['id'] ) ) {

				// If the item in the cart has a free trial period
				if ( ! empty( $item['item_number']['options']['recurring']['trial_period']['unit'] ) && ! empty( $item['item_number']['options']['recurring']['trial_period']['quantity'] ) ) {

					$args['has_trial']         = true;
					$args['trial_unit']        = $item['item_number']['options']['recurring']['trial_period']['unit'];
					$args['trial_quantity']    = $item['item_number']['options']['recurring']['trial_period']['quantity'];
					$args['status']            = 'trialling';
					$args['initial_amount']    = 0;
					$args['initial_tax_rate']  = 0;
					$args['initial_tax']       = 0;
				}

			}

			$this->subscriptions[ $key ] = $args;
		}
	}

	/**
	 * Creates CS_Subscription records.
	 *
	 * @todo This is not gateway-specific and can be moved up.
	 */
	public function create_cs_subscriptions() {
		/*
		 * We need to delete pending subscription records to prevent duplicates. This ensures no duplicate subscription records are created when a purchase is being recovered. See:
		 * https://github.com/commercestore/cs-recurring/issues/707
		 * https://github.com/commercestore/cs-recurring/issues/762
		 */
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}cs_subscriptions WHERE parent_payment_id = %d AND status = 'pending';", $this->payment_id ) );

		// Now create the subscription record(s)
		foreach ( $this->subscriptions as $key => $subscription ) {

			if( isset( $subscription['status'] ) ) {
				$status = $subscription['status'];
			} else {
				$status = 'pending';
			}

			$trial_period = ! empty( $subscription['has_trial'] ) ? $subscription['trial_quantity'] . ' ' . $subscription['trial_unit'] : '';
			$expiration   = $this->subscriber->get_new_expiration( $subscription['id'], $subscription['price_id'], $trial_period );

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

			// If the expiration is beyond Stripe's allowed billing cycle anchor
			// change the expiration to the respective anchor.
			$billing_cycle_anchor = $this->get_billing_cycle_anchor( $subscription );

			if ( strtotime( $expiration ) > $billing_cycle_anchor ) {
				$expiration = date( 'Y-m-d H:i:s', strtotime( date( 'Y-m-d', $billing_cycle_anchor ) . ' 23:59:59' ) );
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

			$sub = $this->subscriber->add_subscription( $args );

			if( ! $this->offsite && $trial_period ) {
				$this->subscriber->add_meta( 'cs_recurring_trials', $subscription['id'] );
			}

			// Track newly created \CS_Subscription in the gateway object.
			$this->subscriptions[ $key ]['cs_subscription'] = $sub;

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
	}

	/**
	 * Creates \Stripe\Subscription records.
	 *
	 * @since 2.9.0
	 *
	 * @param \Stripe\PaymentIntent Stripe PaymentIntent, used to retrieve the parent \CS_Payment
	 */
	public function create_stripe_subscriptions( $intent ) {
		/** This action is documented in incldues/gateways/cs-recurring-gateway.php */
		do_action( 'cs_recurring_pre_create_payment_profiles', $this );

		// Retrieve the \Stripe\Customer used to create the \Stripe\PaymentIntent.
		//
		// Could use ID directly to avoid another API request, however
		// the full object is needed for the `cs_recurring_create_stripe_subscription_args`
		// filter below.
		$customer = $this->get_customer( $intent->customer );

		// Sync the gateway's recurring customer ID with the subscriber.
		$this->subscriber->set_recurring_customer_id( $customer->id, $this->id );

		// Ensure that one-time purchases through Stripe use the same customer ID.
		if ( function_exists( 'cs_stripe_get_customer_key' ) ) {
			update_user_meta( $this->user_id, cs_stripe_get_customer_key(), $customer->id );
			$this->subscriber->update_meta( cs_stripe_get_customer_key(), $customer->id );
		}

		foreach ( $this->subscriptions as $key => $subscription ) {
			try {
				$plan_details = $this->get_stripe_plan( $subscription );

				$args = array(
					'customer'               => $customer->id,
					'default_payment_method' => $intent->payment_method,
					'off_session'            => true,
					'items'                  => array(
						array(
							'plan'     => $plan_details->id,
							'quantity' => 1,
						),
					),
					'metadata'               => array(
						'payment_key' => $this->purchase_data['purchase_key'],
						'download'    => $subscription['name'],
						'download_id' => $subscription['id'],
						'price_id'    => $subscription['price_id'],
						'caller'      => __CLASS__ . '|' . __METHOD__ . '|' . __LINE__ . '|' . CS_RECURRING_VERSION,
					)
				);

				if ( ! empty( $subscription['has_trial'] ) ) {
					$args['trial_end'] = strtotime( '+' . $subscription['trial_quantity'] . ' ' . $subscription['trial_unit'] );
					$set_anchor        = false;
				} else {
					$args['billing_cycle_anchor'] = $this->get_billing_cycle_anchor( $subscription );
					$args['prorate']              = false;
					$set_anchor                   = true;
				}

				/**
				 * Filters the arguments used to create all Recurring subscriptions.
				 *
				 * @since unknown
				 *
				 * @param array  $args       Arguments used to create the gateway-specific Subscription record.
				 * @param array  $downloads  Cart downloads.
				 * @param string $id         Gateway ID.
				 * @param string $product_id Download ID.
				 * @param string $price_id   Download price ID.
				 */
				$args = apply_filters(
					'cs_recurring_create_subscription_args',
					$args,
					$this->purchase_data['downloads'],
					$this->id,
					$subscription['id'],
					$subscription['price_id']
				);

				/**
				 * Filters the arguments used to create \Stripe\Subscription records.
				 *
				 * @since unknown
				 *
				 * @param array  $args      Arguments used to create the \Stripe\Subscription.
				 * @param array  $downloads Cart downloads.
				 * @param string $id        Gateway ID.
				 * @param \Stripe\Customer  Stripe customer.
				 */
				$args = apply_filters(
					'cs_recurring_create_stripe_subscription_args',
					$args,
					$this->purchase_data,
					$customer
				);

				// Avoid sending unnecessary parameters to Stripe.
				if ( ! empty( $args['needs_one_time'] ) ) {
					unset( $args['needs_one_time'] );
					unset( $args['license_id'] );
				}

				/*
				 * If we have a `billing_cycle_anchor` AND a `trial_end`, then we need to unset whichever one
				 * we set, and leave the customer's custom one in tact.
				 *
				 * This is done to account for people who filter the arguments to customize the next bill
				 * date. If `trial_end` is used in conjunction with `billing_cycle_anchor` then it will create
				 * unexpected results and the next bill date will not be what they want.
				 *
				 * This may not be completely perfect but it's the best way to try to account for any errors.
				 */
				if ( ! empty( $args['trial_end'] ) && ! empty( $args['billing_cycle_anchor'] ) ) {
					// If we set an anchor, remove that, because this means the customer has set their own `trial_end`.
					if ( $set_anchor ) {
						unset( $args['billing_cycle_anchor'] );
					} else {
						// We set a trial, which means the customer has set their own `billing_cycle_anchor`.
						unset( $args['trial_end'] );
					}
				}

				$stripe_subscription = csx_api_request( 'Subscription', 'create', $args );

				// Set profile ID.
				$subscription['cs_subscription']->update( array(
					'profile_id' => $stripe_subscription->id,
				) );

				wp_schedule_single_event( strtotime( '+2 minutes' ), 'cs_recurring_stripe_check_txn', array( $stripe_subscription->id ) );

				// Update parent \CS_Payment downloads that have a trial.
				if ( ! empty( $subscription['has_trial'] ) ) {
					$this->payment->modify_cart_item( $key, array(
						'item_price' => 0,
						// Tax amount needs to be the same to avoid a bug in CommerceStore core.
						// If the amount is less it will accidentally increase the value.
						//
						// @link https://github.com/commercestore/commercestore/issues/7385
						'tax'        => $subscription['initial_tax'],
						'price'      => 0,
						'discount'   => 0,
					) );
				}

			// Note any Subscription failures.
			} catch( \Exception $e ) {
				$this->failed_subscriptions[] = array(
					'key'          => $key,
					'error'        => $e->getMessage(),
					'subscription' => $subscription,
				);
			}
		}

		// Clean up subscriptions.
		foreach ( $this->failed_subscriptions as $failed_subscription ) {
			// Remove an CommerceStore record to match other gateways that create a
			// record after talking to the gateway.
			$failed_subscription['subscription']['cs_subscription']->delete();

			$this->payment->add_note( sprintf( __( 'Failed creating subscription for %s. Gateway returned: %s', 'cs-recurring' ), $failed_subscription['subscription']['name'], $failed_subscription['error'] ) );

			$this->payment->remove_download( $failed_subscription['subscription']['id'], array(
				'price_id' => $failed_subscription['subscription']['price_id'],
			) );
		}

		$this->payment->update_meta( '_cs_recurring_failed_subscriptions', $this->failed_subscriptions );

		/** This action is documented in incldues/gateways/cs-recurring-gateway.php */
		do_action( 'cs_recurring_post_create_payment_profiles', $this );
	}

	/**
	 * Adjusts the capture amount for the \Stripe\PaymentIntent and captures.
	 *
	 * The parent \CS_Payment record's current total is used to
	 * determine the amount that is captured.
	 *
	 * @since 2.9.0
	 *
	 * @param \Stripe\PaymentIntent $intent PaymentIntent to capture.
	 */
	public function capture_payment_intent( $intent ) {
		$payment_id = $intent->metadata->cs_payment_id;
		$payment    = cs_get_payment( $payment_id );

		if ( csx_is_zero_decimal_currency() ) {
			$amount = round( $payment->total, 0 );
		} else {
			$amount = round( $payment->total * 100, 0 );
		}

		// Capture amount must be positive (and over $0.50).
		// No Subscriptions were left on the Parent Payment Record.
		//
		// The cart is also manually cleared here to avoid confusion.
		if ( 0 === intval( $amount ) ) {
			$intent->cancel( array(
				'cancellation_reason' => 'abandoned',
			) );

			$payment->add_note( esc_html__( 'PaymentIntent cancelled because there is nothing to collect.', 'cs-recurring' ) );

			cs_empty_cart();
			return;
		}

		return $intent->capture( array(
			'amount_to_capture' => $amount,
		) );
	}

	/**
	 * Transitions \CS_Subscription records to their next status when
	 * the parent \CS_Payment record is transitioned.
	 *
	 * @since 2.9.0
	 *
	 * @param \CS_Payment $parent_payment Parent payment.
	 */
	public function complete_subscriptions( $parent_payment ) {
		$purchase_data = cs_get_purchase_session();

		if ( ! cs_recurring()->is_purchase_recurring( $purchase_data ) ) {
			return;
		}

		$subscription_db = new CS_Subscriptions_DB;
		$subscriptions   = $subscription_db->get_subscriptions( array(
			'parent_payment_id' => $parent_payment->ID,
		) );

		foreach ( $subscriptions as $subscription ) {
			$subscription->update( array(
				'status' => empty( $subscription->trial_period ) ? 'active' : 'trialling',
			) );
		}
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

		if( empty( $_GET['cs-listener'] ) || $this->id !== $_GET['cs-listener'] ) {
			return;
		}

		// retrieve the request's body and parse it as JSON
		$body       = @file_get_contents( 'php://input' );
		$event_json = json_decode( $body );

		if ( isset( $event_json->id ) ) {

			status_header( 200 );

			try {

				$event = csx_api_request( 'Event', 'retrieve', $event_json->id );

			} catch ( Exception $e ) {

				die( 'Invalid event ID' );

			}

			// Create the object with a different data key based on the type of event sent.
			$data         = $event->data->object;
			$subscription = null;
			switch ( $event->type ) {
				case 'invoice.payment_failed' :
				case 'invoice.payment_succeeded' :
					if ( empty( $data->subscription ) ) {
						return;
					}

					$subscription = new CS_Subscription( $data->subscription, true );
					if ( 'invoice.payment_succeeded' == $event->type ) {
						if ( ! $subscription || $subscription->id < 1 ) {
							$subscription = $this->backfill_subscription( $data->customer, $data->subscription );
							if ( ! $subscription || $subscription->id < 1 ) {
								return;
							}
						}
					}
				break;
				case 'customer.subscription.created' :
				case 'customer.subscription.deleted' :
				case 'customer.subscription.updated' :
					if ( empty( $data->id ) ) {
						return;
					}

					$subscription = new CS_Subscription( $data->id, true );
				break;
			}

			if ( $subscription ) {
				$parent_payment_id = $subscription->get_original_payment_id();
				$webhook_key       = sprintf( '_cs_recurring_stripe_event_%s', $event->id );
				$webhook_attempt   = cs_get_payment_meta( $parent_payment_id, $webhook_key );

				if ( ! empty( $webhook_attempt ) ) {
					die(
						sprintf(
							'CS Recurring: %s - No action; event completed previously.',
							$event->type
						)
					);
				}

				// Log this Event ID in the Subscription's parent ayment meta to avoid running the process again.
				cs_update_payment_meta( $parent_payment_id, $webhook_key, $event->id );
			}

			do_action( 'cs_pre_recurring_stripe_event', $event->type, $event, $subscription );
			do_action( 'cs_pre_recurring_stripe_event_' . $event->type, $event, $subscription );

			switch ( $event->type ) :

				case 'invoice.payment_failed' :

					$subscription->failing();
					$subscription->add_note( sprintf( __( 'Failing invoice URL: %s', 'cs-recurring' ), $event->data->object->hosted_invoice_url ) );

					do_action( 'cs_recurring_payment_failed', $subscription );
					do_action( 'cs_recurring_stripe_event_' . $event->type, $event );

					break;

				case 'invoice.payment_succeeded' :

					$subscription_id = ! empty( $data->subscription ) ? $event->data->object->subscription : false;

					// See if the trial is still in place before allowing a 0 transaction.
					// https://github.com/commercestore/cs-recurring/issues/611
					$stripe_sub = ! empty( $event->data->object->subscription )
						? csx_api_request( 'Subscription', 'retrieve', $event->data->object->subscription )
						: false;

					if ( 0 === (int) $data->total && ( $stripe_sub && current_time( 'timestamp' ) < $stripe_sub->trial_end ) ) {
						die( 'CS Recurring: Initial Trial Invoice' );
					}

					$args = array(
						'amount'         => $this->stripe_amount_to_cs_amount( $data->total ),
						'transaction_id' => $data->charge,
					);

					if ( ! empty( $data->tax ) ) {
						$args['tax'] = $data->tax / 100;
					}

					// This is a renewal charge
					$payment_id = $subscription->add_payment( $args );

					if ( empty( $stripe_sub->metadata->reactivated ) || empty( $stripe_sub->metadata->reactivation_processed ) ) {

						if ( ! empty( $payment_id ) ) {

							// Renew the subscription but only if this is not a reactivation and we got a renewal payment ID.
							$subscription->renew( $payment_id );

						}

					} elseif ( ! empty( $stripe_sub->metadata->reactivated ) ) {

						// Set a flag so we know that this reactivation has been processed.
						csx_api_request( 'Subscription', 'update', $stripe_sub->id, array(
							'metadata' => array(
								'reactivation_processed' => true,
							),
						) );
					}

					do_action( 'cs_recurring_stripe_event_' . $event->type, $event );

					die( 'CS Recurring: ' . $event->type );

					break;

				case 'customer.subscription.created' :

					if (
						! empty( $data->status ) &&
						'active' === $data->status &&
						$subscription->id &&
						! $subscription->is_active()
					) {
						cs_debug_log( sprintf(
							'Activating subscription #%d via webhook. Current status: %s.',
							$subscription->id,
							$subscription->status
						) );

						$subscription->update( array(
							'status' => empty( $subscription->trial_period ) ? 'active' : 'trialling',
						) );
					}

					do_action( 'cs_recurring_stripe_event_' . $event->type, $event );

					die( 'CS Recurring: ' . $event->type );

					break;

				case 'customer.subscription.updated' :

					if( ! empty( $data->subscription->cancel_at_period_end ) ) {
						// This is a subscription that has been cancelled but not deleted until period end
						$subscription->cancel();
					}

					$old_amount = $subscription->recurring_amount;
					$new_amount = $data->plan->amount;

					if ( ! csx_is_zero_decimal_currency() ) {
						$new_amount /= 100;
					}

					$old_amount = cs_sanitize_amount( $old_amount );
					$new_amount = cs_sanitize_amount( $new_amount );

					if ( $new_amount !== $old_amount ) {
						$subscription->update( array( 'recurring_amount' => $new_amount ) );
						$subscription->add_note( sprintf( __( 'Recurring amount changed from %s to %s in Stripe.', ' cs-recurring' ), $old_amount, $new_amount ) );

					}

					do_action( 'cs_recurring_stripe_event_' . $event->type, $event );

					die( 'CS Recurring: ' . $event->type );

					break;


				case 'customer.subscription.deleted':
					if ( 'completed' !== $subscription->status && 'canceled' !== $subscription->status ) {
						$subscription->cancel();
					}

					do_action( 'cs_recurring_stripe_event_' . $event->type, $event );

					die( 'CS Recurring: ' . $event->type );

					break;

			endswitch;

		}

	}

	/**
	 * Retrieve the customer object from Stripe.
	 *
	 * @since 2.4
	 * @since 2.9.0 All payments go through the base Stripe gateway ensuring a
	 *              customer record is associated with each user.
	 *
	 * @param string $customer_id Optional \Stripe\Customer ID. If not supplied the current user record will be used.
	 * @return null|\Stripe\Customer Null if a saved customer ID reference cannot be found.
	 */
	public function get_customer( $customer_id = null ) {
		$customer = null;

		if ( ! $customer_id ) {
			$customer_id = csx_get_stripe_customer_id( get_current_user_id() );
		}

		if ( ! empty( $customer_id ) ) {
			try {
				$customer = csx_api_request( 'Customer', 'retrieve', $customer_id );
			} catch( \Exception $e ) {
				$customer = null;
			}
		}

		return $customer;
	}

	/**
	 * Backfills missing subscription data.
	 *
	 * This runs when a renewal payment is processed in Stripe for a subscription that is
	 * missing the profile_id field. This happens occassionally with subscriptions created
	 * pre Recurring Payments 2.4
	 *
	 * @access      public
	 * @since       2.4
	 * @return      object CS_Subscription
	 */
	public function backfill_subscription( $customer_id = '', $subscription_id = '' ) {

		$subscription = false;

		try {
			// Update the customer to ensure their card data is up to date
			$customer     = csx_api_request( 'Customer', 'retrieve', $customer_id );
			$stripe_sub   = csx_api_request( 'Subscription', 'retrieve', $subscription_id );

			if ( ! empty( $stripe_sub->plan->product ) ) {
				$product   = csx_api_request( 'Product', 'retrieve', $stripe_sub->plan->product );
				$plan_name = $product->name;
			} else {
				$plan_name = $stripe_sub->plan->name;
			}

			// Look up payment by email
			$payments = cs_get_payments( array(
				's'        => $customer->email,
				'status'   => 'publish',
				'number'   => 100,
				'output'   => 'payments'
			) );

			//echo '<pre>';print_r( $payments );echo '</pre>';

			if( $payments ) {

				foreach( $payments as $payment ) {

					if( ! is_array( $payment->cart_details ) ) {

						continue;

					}

					if( ! cs_get_payment_meta( $payment->ID, '_cs_subscription_payment', true ) ) {

						continue;

					}

					foreach( $payment->cart_details as $download ) {

						$slug = get_post_field( 'post_name', $download['id'] );

						if( $slug != $plan_name ) {
							continue;
						}

						// We have found a matching subscription, let's look up the sub record and fix it
						$subs_db = new CS_Subscriptions_DB;
						$subs    = $subs_db->get_subscriptions( array( 'parent_payment_id' => $payment->ID ) );
						$sub     = reset( $subs );

						if( $sub && $sub->id > 0 ) {

							$sub->update( array( 'profile_id' => $subscription_id ) );

							$subscription = $sub;

							break;

						}

					}

				}

			}

			// No customer found
		} catch ( Exception $e ) {

		}

		return $subscription;

	}

	/**
	 * Retrieve the plan ID for an item in the cart
	 *
	 * @access      public
	 * @since       2.4
	 * @param       array $subscription The CommerceStore Subscription data in question.
	 * @return      int|false
	 */
	public function get_plan_id( $subscription = array() ) {
		$plan    = $this->get_stripe_plan( $subscription );
		$plan_id = false !== $plan ? $plan->id : false;

		return $plan_id;
	}

	/**
	 * Retrieve the stripe Plan details. It also creates a plan if none is found that matches.
	 *
	 * @access      public
	 * @since       2.9.6
	 * @param       array $subscription The CommerceStore Subscription data in question.
	 * @return      \Stripe\Plan|false Stripe Plan object or false if one cannot be created or retrieved.
	 */
	public function get_stripe_plan( $subscription = array() ) {

		$name = get_post_field( 'post_name', $subscription['id'] );

		if ( isset( $subscription['price_id'] ) && false !== $subscription['price_id'] ) {

			$name .= ' - ' . cs_get_price_option_name( $subscription['id'], $subscription['price_id'] );

		}

		$plan_id = $name . '_' . $subscription['recurring_amount'] . '_' . $subscription['period'];
		/**
		 * Allows the Stripe Plan ID to be modified.
		 * By default the Plan ID is formed by concatenating the Download name, recurring amount, and recurring billing period.
		 * Changing the Plan ID will cause a new Product and Plan to be created in Stripe and all purchases moving forward will be attributed to the new Plan.
		 *
		 * @since 2.10.3
		 * @param string $plan_id      The ID of the Plan in Stripe. Must be unique across all Plans in your Stripe account.
		 * @param array  $subscription The array of subscription data.
		 */
		$plan_id = sanitize_key( apply_filters( 'cs_recurring_stripe_plan_id', $plan_id, $subscription ) );

		try {
			$plan     = csx_api_request( 'Plan', 'retrieve', $plan_id );
			$currency = strtolower( cs_get_currency() );

			if ( $plan->currency !== $currency ) {

				$plan_id = $plan_id . '_' . $currency;
				$args    = $this->get_plan_args( $subscription, $name, $plan_id );

				try {

					$plan = csx_api_request( 'Plan', 'retrieve', $plan_id );

				} catch ( Exception $e ) {

					$plan = $this->create_stripe_plan( $args );

				}
			}
		} catch ( Exception $e ) {

			$args = $this->get_plan_args( $subscription, $name, $plan_id );
			$plan = $this->create_stripe_plan( $args );

		}

		return $plan;

	}

	/**
	 * Build the argument array for creating a plan in Stripe
	 *
	 * @since 2.7
	 * @param array  $subscription
	 * @param string $name
	 * @param string $plan_id
	 *
	 * @return array
	 */
	public function get_plan_args( $subscription, $name, $plan_id = '' ) {
		$statement_descriptor   = $name;
		$unsupported_characters = array( '<', '>', '"', '\'' );
		$statement_descriptor   = apply_filters( 'cs_recurring_stripe_statement_descriptor', substr( $statement_descriptor, 0, 22 ), $subscription );
		$statement_descriptor   = str_replace( $unsupported_characters, '', $statement_descriptor );

		switch( $subscription['period'] ) {

			case 'quarter' :

				$frequency = 3;
				$period    = 'month';
				break;

			case 'semi-year' :

				$frequency = 6;
				$period    = 'month';
				break;

			default :

				$frequency = 1;
				$period    = $subscription['period'];
				break;

		}

		$amount = $subscription['recurring_amount'];
		/**
		 * Stripe requires the amount to be in a number of 'cents' in the currency.
		 * Additionally, Stripe uses a different list of "zero decimal" currencies
		 * than CommerceStore core, so whether the amount should be converted uses that logic.
		 */
		if ( ! csx_is_zero_decimal_currency() ) {
			$amount = round( $amount * 100, 0 );
		}

		$args = array(
			'amount'               => $amount,
			'interval'             => $period,
			'interval_count'       => $frequency,
			'currency'             => cs_get_currency(),
			'name'                 => $name,
			'id'                   => $plan_id,
			'statement_descriptor' => $statement_descriptor,
			'metadata' => array (
				'caller' => __CLASS__ . '|' . __METHOD__ . '|' . __LINE__ . '|' . CS_RECURRING_VERSION,
			)
		);

		/**
		 * Stripe plan arguments.
		 * Allows filtering of the arguments that are sent to Stripe when creating the plan.
		 *
		 * @since 2.4
		 *
		 * @param array $args {
		 *     The plan arguments that will be sent to Stripe.
		 *     int    $amount         The amount that will be charged for the plan.
		 *     string $interval       The period at which the plan will renew at.
		 *     int    $interval_count The frequency at which the plan will renew.
		 *     string $name           The human readable name for the plan.
		 *     string $currency       The currency for the plan.
		 *     string $id             The string identifier for the plan in Stripe.
		 *     string $statement_descriptor The value that will show on a customer's financial institution for charges of this plan.
		 * }
		 * @param array $subscription
		 */
		return apply_filters( 'cs_recurring_create_stripe_plan_args', $args, $subscription );
	}

	/**
	 * Creates a plan in Stripe and returns the plan ID
	 *
	 * @access  public
	 * @since   2.4
	 * @param   array $args The values to use when creating the Stripe Plan.
	 * @return  \Stripe\Plan|false
	 */
	private function create_stripe_plan( $args = array() ) {

		/*
		 * If we're using API version 2018-02-05 or greater, create a product
		 *
		 * See https://github.com/commercestore/cs-recurring/issues/925
		 */

		try {

			$id = md5( serialize( $args ) );

			$product = csx_api_request( 'Product', 'retrieve', $id );

		} catch ( Exception $e ) {

			// No product found, create one

			$product = csx_api_request( 'Product', 'create', array(
				'id'   => $id,
				'name' => $args['name'],
				'type' => 'service',
				'statement_descriptor' => $args['statement_descriptor'],
				'metadata' => array(
					'caller'      => __CLASS__ . '|' . __METHOD__ . '|' . __LINE__ . '|' . CS_RECURRING_VERSION,
				)
			) );

		}

		try {

			if( ! empty( $product ) ) {

				$args['product'] = $product;

				if( isset( $args['name'] ) ) {

					unset( $args['name'] );

				}

				if( isset( $args['statement_descriptor'] ) ) {

					unset( $args['statement_descriptor'] );

				}

			}

			$plan    = csx_api_request( 'Plan', 'create', $args );

		} catch ( Exception $e ) {

			$plan = false;

		}


		return $plan;

	}

	/**
	 * Returns a timestamp for a Subscription's biling cycle anchor point.
	 *
	 * @since 2.9.7
	 *
	 * @link https://github.com/commercestore/cs-recurring/issues/1268
	 * @link https://stripe.com/docs/billing/subscriptions/billing-cycle
	 *
	 * @param array    $subscription Subscription arguments.
	 * @param null|int $now          Starting point for determining the current calendar positions.
	 *                               Default time()
	 * @return int Timestamp.
	 */
	public function get_billing_cycle_anchor( $subscription, $now = null ) {
		$anchor = null;

		if ( null === $now ) {
			$now = time();
		}

		$day     = date( 'j', $now );
		$month   = date( 'n', $now );
		$year    = date( 'Y', $now );
		$hours   = date( 'G', $now );
		$minutes = date( 'i', $now );
		$seconds = date( 's', $now );

		if ( in_array( $subscription['period'], array( 'day', 'week' ), true ) ) {
			$anchor = strtotime( sprintf( '+1 %s', $subscription['period'] ) );
		} else {
			switch( $subscription['period'] ) {
				case 'month':
					$month = $month + 1;
					break;
				case 'quarter':
					$month = $month + 3;
					break;
				case 'semi-year':
					$month = $month + 6;
					break;
				case 'year':
					$year = $year + 1;
					break;
			}

			// If the month count goes beyond 12 (the current year) roll over
			// to the next year and find the appropriate month.
			//
			// mktime() accepts month counts above 12 but the year would still
			// be anchored in the current year.
			if ( $month > 12 ) {
				$year  = $year + 1;
				$month = $month - 12;
			}

			// This is a real date, use it as the anchor.
			if ( true === checkdate( $month, $day, $year ) ) {
				$anchor = strtotime( sprintf( '%s-%s-%s %s:%s:%s', $year, $month, $day, $hours, $minutes, $seconds ) );
			} else {
				$anchor = strtotime( sprintf( '%s:%s:%s last day of %s %s', $hours, $minutes, $seconds, date( 'F', mktime( 0, 0, 0, $month, 1, $year ) ), $year ) );
			}
		}

		// Account for innacurate server clocks to prevent
		// "billing_cycle_anchor cannot be later than next natural billing date" errors.
		//
		// @link https://github.com/commercestore/cs-recurring/issues/1253
		$billing_cycle_anchor_negative_offset = MINUTE_IN_SECONDS / 4;

		return ( $anchor - $billing_cycle_anchor_negative_offset );
	}

	/**
	 * Matches the Subscription's expiration date with Stripe's renewal date.
	 *
	 * @since 2.9.7
	 *
	 * @param int              $expiration Renewal expiration timestamp.
	 * @param int              $subscription_id ID of the current Subscription.
	 * @param CS_Subscription $subscription Current subscription.
	 * @return int Renewal expiration timestamp.
	 */
	public function set_renewal_expiration( $expiration, $subscription_id, $subscription ) {
		try {
			$stripe_sub = csx_api_request( 'Subscription', 'retrieve', $subscription->profile_id );

			/**
			 * Since Stripe can process a renewal charge roughly 1 hour after the expiration of a subscription,
			 * we should account for this as subscription renewal payments are not immediately collected, but they are
			 * immediately invoiced, and charged later.
			 *
			 * @see https://stripe.com/docs/billing/lifecycle#subscription-lifecycle
			 */
			$stripe_sub_expiration  = $stripe_sub->current_period_end + ( HOUR_IN_SECONDS * 1.5 );
			$expiration             = date( 'Y-m-d H:i:s', $stripe_sub_expiration );
		} catch( \Exception $e ) {
			// Do nothing, use original value.
		}

		return $expiration;
	}

	/**
	 * Fixes an issue in subscriptions that got the incorrect expiration date.
	 *
	 * @see https://github.com/commercestore/cs-recurring/pull/1281
	 * @since 2.9.8
	 *
	 * @param $sub CS_Subscription
	 */
	public function check_renewal_expiration( $sub ) {
		if ( 'stripe' === $this->id && '0000-00-00 00:00:00' === $sub->expiration ) {
			$sub->check_expiration();
		}
	}

	/**
	 * Determines if the subscription can be cancelled
	 *
	 * @access      public
	 * @since       2.4
	 * @return      bool
	 */
	public function can_cancel( $ret, $subscription ) {
		if( $subscription->gateway === 'stripe' && ! empty( $subscription->profile_id ) && in_array( $subscription->status, $this->get_cancellable_statuses() ) ) {
			return true;
		}
		return $ret;
	}

	/**
	 * Cancels a subscription at period end, unless the status of the subscription is failing. If failing, cancel immediately.
	 *
	 * @access      public
	 * @since       2.4
	 * @param       CS_Subscription $subscription The CommerceStore Subscription object being cancelled.
	 * @param       bool             $valid Currently this defaults to be true at all times.
	 * @return      bool
	 */
	public function cancel( $subscription, $valid ) {

		if ( empty( $valid ) ) {
			return false;
		}

		try {
			// Before we cancel, lets make sure this subscription exists at Stripe.
			$sub = csx_api_request( 'Subscription', 'retrieve', $subscription->profile_id );

			if ( 'canceled' === $sub->status ) {
				return false;
			}

			$at_period_end = 'failing' === $subscription->status ? false : true;

			if ( $at_period_end ) {
				$sub = csx_api_request( 'Subscription', 'update', $subscription->profile_id, array(
					'cancel_at_period_end' => true,
				) );
			} else {
				$sub->cancel();
			}

			// We must now loop through and cancel all unpaid invoice to ensure that additional payment attempts are not made.
			$invoices = csx_api_request( 'Invoice', 'all', array( 'subscription' => $subscription->profile_id ) );

			if ( $invoices ) {

				foreach ( $invoices->data as $invoice ) {

					// Skip paid invoices.
					if ( $invoice->paid ) {
						continue;
					}

					$invoice->voidInvoice();
				}
			}
		} catch ( Exception $e ) {
			// Translators: The error message from Stripe.
			$subscription->add_note( sprintf( esc_html__( 'Attempted cancellation but was unable. Message was "%s".', 'cs-recurring' ), wp_json_encode( $e ) ) );
			return false;
		}

		return true;

	}

	/**
	 * Cancels a subscription immediately.
	 *
	 * @access      public
	 * @since       2.9.4
	 * @param       CS_Subscription $subscription The CommerceStore Subscription object being cancelled.
	 * @return      bool
	 */
	public function cancel_immediately( $subscription ) {

		try {
			$sub = csx_api_request( 'Subscription', 'retrieve', $subscription->profile_id );
			$sub->cancel();

			// We must now loop through and cancel all unpaid invoice to ensure that additional payment attempts are not made.
			$invoices = csx_api_request( 'Invoice', 'all', array( 'subscription' => $subscription->profile_id ) );

			if ( $invoices ) {

				foreach ( $invoices->data as $invoice ) {

					// Skip paid invoices.
					if ( $invoice->paid ) {
						continue;
					}

					$invoice->voidInvoice();
				}
			}
		} catch ( Exception $e ) {
			// Translators: The error message from Stripe.
			$subscription->add_note( sprintf( esc_html__( 'Attempted cancellation but was unable. Message was "%s".', 'cs-recurring' ), wp_json_encode( $e ) ) );
			return false;
		}

		return true;

	}

	/**
	 * Determines if a subscription can be reactivated through the gateway.
	 *
	 * @since 2.6
	 *
	 * @param bool $ret                       True if the Subscription can be reactivated.
	 * @param \CS_Subscription $subscription Subscription to determine reactivation status of.
	 *
	 * @return bool
	 */
	public function can_reactivate( $ret, $subscription ) {
		if ( $subscription->gateway !== 'stripe' || empty( $subscription->profile_id ) || 'cancelled' !== $subscription->status ) {
			return $ret;
		}

		$payment = cs_get_payment( $subscription->get_original_payment_id() );
		$status  = $payment->status;

		// Can't reactivate with a refunded or revoked original payment.
		if ( 'publish' !== $status && 'revoked' !== $status ) {
			return false;
		}

		// Can't reactivate a Subscription that was automatically cancelled as part of a
		// Software Licensing upgrade.
		$was_upgraded = $payment->get_meta( '_cs_sl_upgraded_to_payment_id' );

		if ( ! empty( $was_upgraded ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Determines if the subscription can be retried when failing
	 *
	 * @access      public
	 * @since       2.8
	 * @return      bool
	 */
	public function can_retry( $ret, $subscription ) {
		if( $subscription->gateway === 'stripe' && ! empty( $subscription->profile_id ) && 'failing' === $subscription->status ) {
			return true;
		}
		return $ret;
	}

	/**
	 * Reactivates a subscription.
	 *
	 * @access      public
	 * @since       2.6
	 *
	 * @param CS_Subscription $subscription The CS_Subscription object.
	 * @param boolean          $valid        A verification call that this call came from a valid source.
	 *
	 * @return boolean
	 */
	public function reactivate( $subscription, $valid ) {

		if ( empty( $valid ) ) {
			return false;
		}

		try {
			$sub = csx_api_request( 'Subscription', 'retrieve', $subscription->profile_id );

			// This Subscription was cancelled in Stripe, so we have to create a new subscription.
			if ( empty( $sub->cancel_at_period_end ) || in_array( $sub->status, array( 'canceled', 'incomplete', 'incomplete_expired' ), true ) ) {
				$existing_meta = array();

				// Support Stripe PHP 6.x
				if ( method_exists( $sub->metadata, '__toArray' ) ) {
					$existing_meta = $sub->metadata->__toArray();

				// Support Stripe PHP 7.x
				// @link https://github.com/stripe/stripe-php/pull/704
				} elseif ( method_exists( $sub->metadata, 'toArray' ) ) {
					$existing_meta = $sub->metadata->toArray();
				}

				$args = array(
					'customer'               => $sub->customer,
					'items'                  => array(
						array(
							'plan'     => $sub->plan->id,
							'quantity' => $sub->quantity,
						)
					),
					'tax_percent'            => $sub->tax_percent,
					'default_payment_method' => $sub->default_payment_method,
					'default_source'         => $sub->default_source,
					'off_session'            => true,
					'metadata'               => array_merge(
						array(
							'reactivated' => true,
							'old_sub_id'  => $subscription->profile_id,
							'caller'      => __CLASS__ . '|' . __METHOD__ . '|' . __LINE__ . '|' . CS_RECURRING_VERSION,
						),
						$existing_meta
					),
				);

				// If the expiration date is in the future, we need to reactivate without charge.
				if ( current_time( 'timestamp' ) < $subscription->get_expiration_time() ) {
					if ( ! empty( $sub->current_period_end ) ) {
						$args['trial_end'] = $sub->current_period_end;
					} else {
						$args['trial_end'] = strtotime( $subscription->get_expiration() );
					}
				}

				$stripe_sub = csx_api_request( 'Subscription', 'create', $args );

				// Subscription could not be fully reactivated.
				if ( 'incomplete' === $stripe_sub->status ) {
					$subscription->add_note( esc_html__( 'Subscription reactivation requires payment by customer and will be cancelled in 24 hours if no action is taken.', 'cs-recurring' ) );
				}

				$subscription->update(
					array(
						'status'     => 'incomplete' === $stripe_sub->status ? 'failing' : 'active',
						'profile_id' => $stripe_sub->id,
						'expiration' => date( 'Y-n-d H:i:s', $stripe_sub->current_period_end ),
					)
				);


			} else { // This Subscription is still active in Stripe, remove cancellation notice.
				csx_api_request( 'Subscription', 'update',
					$sub->id,
					array(
						'cancel_at_period_end' => false,
					)
				);

				$subscription->update(
					array(
						'status'     => 'active',
						'expiration' => date( 'Y-n-d H:i:s', $sub->current_period_end ),
					)
				);
			}

		} catch ( Exception $e ) {
			wp_die( esc_html( $e->getMessage() ), esc_html( __( 'Error', 'cs-recurring' ) ), array( 'response' => 403 ) );
		}

		return true;
	}

	/**
	 * Retries a failing Subscription's latest invoice.
	 *
	 * This method is connected to a filter instead of an action so we can return a nice error message.
	 *
	 * @todo This uses a different amount of paid invoices than the Stripe Account settings may require.
	 *
	 * @access      public
	 * @since       2.8
	 *
	 * @param bool             $result       If the result was successful.
	 * @param CS_Subscription $subscription The CS_Subscription object to retry.
	 *
	 * @return      bool|WP_Error
	 */
	public function retry( $result, $subscription ) {
		if ( ! $this->can_retry( false, $subscription ) ) {
			return $result;
		}

		$subscriber  = new CS_Recurring_Subscriber( $subscription->customer_id );
		$customer_id = $subscriber->get_recurring_customer_id( 'stripe' );

		if ( empty( $customer_id ) ) {
			return $result;
		}

		$void_past_due_invoices = true;

		/** This filter is documented in includes/gateways/cs-recurring-stripe.php */
		$void_past_due_invoices = apply_filters(
			'cs_recurring_stripe_void_past_due_invoices',
			$void_past_due_invoices,
			$subscription
		);

		try {
			// Manual retries are limited to 7 days, so it's unlikely there will
			// be more invoices than that.
			$invoices = csx_api_request( 'Invoice', 'all',
				array(
					'subscription' => $subscription->profile_id,
					'limit'        => 7,
					'status'       => 'open',
					'customer'     => $customer_id,
				)
			);

			if ( empty( $invoices->data ) ) {
				return $result;
			}

			$has_paid_invoice = false;

			foreach ( $invoices->data as $invoice ) {
				/* @var \Stripe\Invoice $invoice */

				// We have found an invoice and paid it, void the rest.
				if ( true === $has_paid_invoice && true === $void_past_due_invoices ) {
					$invoice->voidInvoice();
				} else {
					$paid_invoice = $invoice->pay(
						array(
							'off_session' => true,
						)
					);

					if ( 'paid' === $paid_invoice->status ) {
						$has_paid_invoice = true;
						$payment_intent   = csx_api_request( 'PaymentIntent', 'retrieve', $paid_invoice->payment_intent );
						$charges          = $payment_intent->charges->data;

						if ( ! empty( $charges ) ) {
							$charge     = current( $charges );
							$payment_id = $subscription->add_payment(
								array(
									'transaction_id' => $charge->id,
									'amount'         => $this->stripe_amount_to_cs_amount( $paid_invoice->total ),
									'gateway'        => 'stripe',
								)
							);

							$subscription->renew( $payment_id );
						}
					}
				}
			}

			$result = $has_paid_invoice;
		} catch ( Exception $e ) {
			$result = new WP_Error( 'cs_recurring_stripe_error', $e->getMessage() );
		}

		return $result;
	}

	/**
	 * Converts a Stripe amount (integer) to an CommerceStore amount for storage.
	 * Non-zero-decimal currencies get divided by 100.
	 *
	 * @uses csx_is_zero_decimal_currency()
	 *
	 * @since 2.10.5
	 *
	 * @param int $amount Stripe always gives us an integer.
	 *
	 * @return float|int
	 */
	private function stripe_amount_to_cs_amount( $amount ) {
		if ( ! csx_is_zero_decimal_currency() ) {
			$amount /= 100;
		}

		return $amount;
	}

	/**
	 * Get the expiration date with Stripe
	 *
	 * @since  2.6.6
	 * @param  object $subscription The subscription object
	 * @return string Expiration date or WP_Error if something went wrong
	 */
	public function get_expiration( $subscription ) {

		try {

			$subscription = csx_api_request( 'Subscription', 'retrieve', $subscription->profile_id );

		} catch( Exception $e ) {

			return new WP_Error( 'cs_recurring_stripe_error', $e->getMessage() );

		}

		return date( 'Y-n-d H:i:s', $subscription->current_period_end );
	}

	/**
	 * Determines if the subscription can be updated
	 *
	 * @access      public
	 * @since       2.4
	 * @return      bool
	 */
	public function can_update( $ret, $subscription ) {
		if( $subscription->gateway === 'stripe' && ! empty( $subscription->profile_id ) && ( 'active' === $subscription->status || 'failing' === $subscription->status || 'trialling' === $subscription->status ) ) {
			return true;
		}
		return $ret;
	}

	/**
	 * Refund charges for renewals when refunding via View Order Details.
	 *
	 * @access      public
	 * @since       2.4.11
	 * @param       CS_Payment $payment The CS_Payment object that is being refunded.
	 * @return      void
	 */
	public function process_refund( CS_Payment $payment ) {
		if ( empty( $_POST['cs_refund_in_stripe'] ) ) {
			return;
		}

		$statuses = array( 'cs_subscription' );

		if ( ! in_array( $payment->old_status, $statuses ) ) {
			return;
		}

		if ( 'stripe' !== $payment->gateway ) {
			return;
		}

		switch( $payment->old_status ) {

			// Renewal.
			case 'cs_subscription' :

				// No valid charge ID.
				if ( empty( $payment->transaction_id ) || $payment->transaction_id == $payment->ID ) {
					return;
				}

				try {
					if ( version_compare( Stripe\Stripe::VERSION, '7.0', '<' ) ) {
						$refund = csx_api_request( 'Charge', 'retrieve', $payment->transaction_id );
						$refund->refund();
					} else {
						$args = array(
							'charge' => $payment->transaction_id,
						);

						/**
						 * Filters the arguments used to create a Refund object in Stripe.
						 *
						 * @since 2.10.0
						 *
						 * @param array $args {
						 *   Refund object arguments.
						 *
						 *   https://stripe.com/docs/api/refunds/create
						 * }
						 */
						$args = apply_filters( 'css_create_refund_args', $args );

						$opt_args = array();

						/**
						 * Filters the per-request arguments used when creating a Refund object in Stripe.
						 *
						 * @since 2.10.0
						 *
						 * @param array $opt_args {
						 *   Per request arguments.
						 * }
						 */
						$opt_args = apply_filters( 'css_create_refund_secondary_args', $opt_args );

						$refund = csx_api_request( 'Refund', 'create', $args, $opt_args );
					}

					$payment->add_note(
						sprintf(
							/* translators: %s Refund ID. */
							__( 'Charge refunded in Stripe. Refund ID %s', 'cs-recurring' ),
							$refund->id
						)
					);
				} catch ( \Exception $e ) {
					wp_die( $e->getMessage(), __( 'Error', 'cs-recurring' ), array( 'response' => 400 ) );
				}

				break;
		}

	}

	/**
	 * Outputs the payment method update form
	 *
	 * @since  2.4
	 * @param  CS_Subscription object $subscription The subscription object.
	 * @return void
	 */
	public function update_payment_method_form( $subscription ) {
		if ( $subscription->gateway !== $this->id ) {
			return;
		}

		// Enqueue core scripts.
		add_filter( 'cs_is_checkout', '__return_true' );

		cs_load_scripts();

		remove_filter( 'cs_is_checkout', '__return_true' );

		cs_stripe_js( true );

		wp_enqueue_script(
			'cs-frontend-recurring-stripe',
			CS_RECURRING_PLUGIN_URL . 'assets/js/cs-frontend-recurring-stripe.js',
			array( 'jquery', 'cs-stripe-js' ),
			CS_RECURRING_VERSION
		);

		wp_localize_script(
			'cs-frontend-recurring-stripe',
			'csRecurringStripe',
			array(
				'i18n' => array(
					'loading' => esc_html__( 'Please Wait…', 'cs-recurring' ),
				),
			)
		);

		try {
			$stripe_subscription = csx_api_request( 'Subscription', 'retrieve',
				array(
					'id' => $subscription->profile_id,
				)
			);

			// Find the latest open (unpaid) invoice.
			// Scheduled invoices have no PaymentIntent, which Stripe can return in `latest_invoice`
			// on the Subscription object.
			//
			// When the form is submitted any previously stacked Past due invoices will be voided.
			//
			// @link https://github.com/commercestore/cs-recurring/issues/1177.
			$latest_open_invoice = csx_api_request( 'Invoice', 'all',
				array(
					'subscription' => $stripe_subscription->id,
					'limit'        => 1,
					'status'       => 'open',
					'customer'     => $stripe_subscription->customer,
				)
			);

			if ( ! empty( $latest_open_invoice->data ) ) {
				$invoice = current( $latest_open_invoice->data );

				if ( $invoice->payment_intent ) {
					$payment_intent = csx_api_request( 'PaymentIntent', 'retrieve', $invoice->payment_intent );

					if ( 'succeeded' !== $payment_intent->status ) {
						echo '<input type="hidden" name="cs_recurring_stripe_payment_intent" value="' . esc_attr( $payment_intent->id ) . '" />';
					}
				}
			}

			echo '<input type="hidden" name="cs_recurring_stripe_profile_id" value="' . esc_attr( $stripe_subscription->id ) . '" />';
			echo '<input type="hidden" name="cs_recurring_stripe_default_payment_method" value="' . esc_attr( $stripe_subscription->default_payment_method ) . '" />';

			csx_credit_card_form();
		} catch ( \Exception $e ) {
			echo esc_html( $e->getMessage() );
		}
	}

	/**
	 * Updates a Subscription's default payment method.
	 */
	public function update_subscription_payment_method() {
		$subscription_id       = isset( $_POST['subscription_id'] ) ? sanitize_text_field( $_POST['subscription_id'] ) : false;
		$payment_method_id     = isset( $_POST['payment_method_id'] ) ? sanitize_text_field( $_POST['payment_method_id'] ) : false;
		$payment_method_exists = isset( $_POST['payment_method_exists'] ) ? 'true' == $_POST['payment_method_exists'] : false;
		$nonce                 = isset( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : false;

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'update-payment' ) ) {
			return wp_send_json_error( array(
				'message' => esc_html__( 'Invalid request. Please try again', 'cs-recurring' ),
			) );
		}

		if ( ! $subscription_id || ! $payment_method_id ) {
			return wp_send_json_error( array(
				'message' => esc_html__( 'Unable to locate Subscription. Please try again', 'cs-recurring' ),
			) );
		}

		try {
			$customer       = $this->get_customer();
			$payment_method = csx_api_request( 'PaymentMethod', 'retrieve', $payment_method_id );

			// Attach method if it's new.
			if ( ! $payment_method_exists ) {
				$payment_method->attach( array(
					'customer' => $customer->id,
				) );

			// Update an existing method's address.
			} else {
				$address_info    = isset( $_POST['billing_address'] ) ? $_POST['billing_address'] : array();
				$billing_address = array();

				foreach ( $address_info as $key => $value ) {
					$billing_address[ $key ] = ! empty( $value ) ? sanitize_text_field( $value ) : null;
				}

				csx_api_request( 'PaymentMethod', 'update', $payment_method_id, array(
					'billing_details' => array(
						'address' => $billing_address,
					),
				) );
			}

			// Set the Subscription's default payment method.
			$subscription = csx_api_request( 'Subscription', 'update', $subscription_id, array(
				'default_payment_method' => $payment_method_id,
			) );

			return wp_send_json_success( array(
				'message'      => esc_html__( 'Payment method updated.', 'cs-recurring' ),
				'subscription' => $subscription,
			) );
		} catch( \Exception $e ) {
			return wp_send_json_error( array(
				'message' => $e->getMessage(),
			) );
		}
	}

	/**
	 * Processes the update payment form.
	 *
	 * Handling of the latest open invoice with an attached PaymentIntent is done
	 * on the client. In order to avoid a loop of paying for multiple "Past due" invoices
	 * that haven't affected the the \CS_Subscription status, void them.
	 *
	 * @link https://github.com/commercestore/cs-recurring/issues/1177
	 *
	 * @since 2.9.0
	 *
	 * @param CS_Recurring_Subscriber $subscriber   CS_Recurring_Subscriber.
	 * @param CS_Subscription         $subscription CS_Subscription.
	 */
	public function update_payment_method( $subscriber, $subscription ) {
		$void_past_due_invoices = true;

		/**
		 * Filters if stacked past due invoices should be voided when updating
		 * a Subscription's payment method.
		 *
		 * @since 2.9.0
		 *
		 * @param bool $void_past_due_invoices Void stacked past due invoices. Defaults true.
		 * @param int  $subscriber CS_Recurring_Subscriber
		 */
		$void_past_due_invoices = apply_filters(
			'cs_recurring_stripe_void_past_due_invoices',
			$void_past_due_invoices,
			$subscription
		);

		if ( true !== $void_past_due_invoices ) {
			return;
		}

		if ( empty( $subscription->profile_id ) ) {
			return;
		}

		$customer_id = $subscriber->get_recurring_customer_id( 'stripe' );

		if ( empty( $customer_id ) ) {
			return;
		}

		try {
			// Manual retries are limited to 7 days, so it's unlikely there will
			// be more invoices than that.
			$invoices = csx_api_request( 'Invoice', 'all',
				array(
					'subscription' => $subscription->profile_id,
					'limit'        => 7,
					'status'       => 'open',
					'customer'     => $customer_id,
				)
			);

			if ( empty( $invoices->data ) ) {
				return;
			}

			foreach ( $invoices->data as $invoice ) {
				/* @var \Stripe\Invoice $invoice */
				$invoice->voidInvoice();
			}
		} catch ( \Exception $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
	}

	/**
	 * Cancels subscription in Stripe when marked as completed
	 *
	 * @access      public
	 * @since       2.4.15
	 * @return      bool
	 */
	public function cancel_on_completion( $subscription_id, $subscription ) {

		if ( $subscription->gateway !== $this->id ) {
			return;
		}

		return $this->cancel( $subscription, true );

	}

	/**
	 * Link the recurring profile in Stripe.
	 *
	 * @since  2.4.4
	 * @param  string $profile_id   The recurring profile id
	 * @param  object $subscription The Subscription object
	 * @return string               The link to return or just the profile id
	 */
	public function link_profile_id( $profile_id, $subscription ) {

		if( ! empty( $profile_id ) ) {
			$payment    = cs_get_payment( $subscription->parent_payment_id );
			$html       = '<a href="%s" target="_blank">' . $profile_id . '</a>';
			$base_url   = 'test' === $payment->mode ? 'https://dashboard.stripe.com/test/' : 'https://dashboard.stripe.com/';
			$link       = esc_url( $base_url . 'subscriptions/' . $profile_id );
			$profile_id = sprintf( $html, $link );
		}

		return $profile_id;

	}

	/**
	 * Looks up the transaction ID for a subscription record by the profile ID.
	 *
	 * @since  2.4.11
	 * @param  string $profile_id The recurring profile id
	 * @return object|false CS_Subsciption object or false if no updates are made
	 */
	public function check_transaction_id( $profile_id = '' ) {
		if ( empty( $profile_id ) ) {
			return false;
		}

		$subscription = new CS_Subscription( $profile_id, true );

		if ( ! $subscription || ! $subscription->id > 0 ) {
			return false;
		}

		// Already transformed a PaymentIntent to Charge ID.
		if ( 'ch_' === substr( $subscription->transaction_id, 0, 3 ) ) {
			return false;
		}

		// A parent CS_Payment's PaymentIntent was used temporarily.
		// Try to find a charge from the Intent.
		if ( 'pi_' === substr( $subscription->transaction_id, 0, 3 ) ) {

			try {
				$payment_intent = csx_api_request( 'PaymentIntent', 'retrieve', $subscription->transaction_id );

				if ( ! empty( $payment_intent->charges->data ) ) {
					$charge_id = current( $payment_intent->charges->data )->id;

					$subscription->update( array(
						'transaction_id' => $charge_id,
					) );

					return $subscription;
				}
			} catch( \Exception $e ) {
				return false;
			}

		// Try to find it through any existing invoices.
		} else {

			$subscriber  = new CS_Recurring_Subscriber( $subscription->customer_id );
			$customer_id = $subscriber->get_recurring_customer_id( 'stripe' );

			if ( empty( $customer_id ) ) {
				return false;
			}

			try {
				$customer = csx_api_request( 'Customer', 'retrieve', $customer_id );
				$invoices = csx_api_request( 'Invoice', 'all', array(
					'customer' => $customer_id,
					'limit' => 20,
				) );

				if ( empty( $invoices->data ) ) {
					return false;
				}

				foreach ( $invoices->data as $invoice ) {
					if ( empty( $invoice->subscription ) ) {
						continue;
					}

					if ( $profile_id != $invoice->subscription ) {
						continue;
					}

					if ( empty( $invoice->charge ) ) {
						continue;
					}

					$subscription->update( array(
						'transaction_id' => $invoice->charge,
					) );

					$subscription->transaction_id = $invoice->charge;

					return $subscription;

					break;
				}

			} catch( \Exception $e ) {
				return false;
			}
		}

		return false;
	}

	/**
	 * Determines if the subscription data needs checked against Stripe's database.
	 *
	 * Right now this only checks if the transaction ID is missing and retrieves it. In the future this could also check status, expiration date, etc.
	 *
	 * @since  2.4.11
	 * @param  object $subscription The CS_Subscription object
	 * @return void
	 */
	public function maybe_check_subscription( CS_Subscription $subscription ) {
		if ( ! $subscription || ! $subscription->id > 0 ) {
			return;
		}

		if ( 'stripe' !== $subscription->gateway ) {
			return;
		}

		if ( empty( $subscription->profile_id ) ) {
			return;
		}

		// Already transformed a PaymentIntent to Charge ID.
		if ( 'ch_' === substr( $subscription->transaction_id, 0, 3 ) ) {
			return;
		}

		// Make sure we don't cause an infinite loop
		remove_action( 'cs_recurring_setup_subscription', array( $this, 'maybe_check_subscription' ), 10 );

		if ( false !== $this->check_transaction_id( $subscription->profile_id ) ) {
			// Remove the scheduled event for this subscription if it hasn't already run
			wp_clear_scheduled_hook( 'cs_recurring_stripe_check_txn', array( $subscription->profile_id ) );
		}

		add_action( 'cs_recurring_setup_subscription', array( $this, 'maybe_check_subscription' ) );
	}

	/**
	 * Verify that the user has acknowledged to updating their payment form as a default for all subscriptions
	 *
	 * @since 2.4
	 * @since 2.9.0 No longer used, always returns value sent.
	 *
	 * @param bool  $is_valid  If the data passed so far was valid from CommerceStore Core
	 * @param array $post_data The array of $_POST sent by the form
	 *
	 * @return bool
	 */
	public function confirm_default_payment_method_change( $is_valid, $post_data ) {
		return $is_valid;
	}

}
$cs_recurring_stripe = new CS_Recurring_Stripe;
