<?php

/**
 * Class CS_Helper_Payment.
 *
 * Helper class to create and delete a payment easily.
 */
class CS_Helper_Payment extends WP_UnitTestCase {

	/**
	 * Delete a payment.
	 *
	 * @since 2.3
	 *
	 * @param int $payment_id ID of the payment to delete.
	 */
	public static function delete_payment( $payment_id ) {

		// Delete the payment
		cs_delete_purchase( $payment_id );

	}

	/**
	 * Create a simple payment.
	 *
	 * @since 2.3
	 */
	public static function create_simple_payment( $args = array() ) {

		global $cs_options;

		$defaults = array(
			'discount' => 'none'
		);

		$args = wp_parse_args( $args, $defaults );

		// Enable a few options
		$cs_options['sequential_prefix'] = 'CS-';

		$simple_download   = CS_Helper_Download::create_simple_download();
		$variable_download = CS_Helper_Download::create_variable_download();

		/** Generate some sales */
		$user      = get_userdata(1);
		$user_info = array(
			'id'            => $user->ID,
			'email'         => $user->user_email,
			'first_name'    => $user->first_name,
			'last_name'     => $user->last_name,
			'discount'      => $args['discount'],
		);

		$download_details = array(
			array(
				'id' => $simple_download->ID,
				'options' => array(
					'price_id' => 0
				)
			),
			array(
				'id' => $variable_download->ID,
				'options' => array(
					'price_id' => 1
				)
			),
		);

		$total                  = 0;
		$simple_price           = get_post_meta( $simple_download->ID, 'cs_price', true );
		$variable_prices        = get_post_meta( $variable_download->ID, 'cs_variable_prices', true );
		$variable_item_price    = $variable_prices[1]['amount']; // == $100

		$total += $variable_item_price + $simple_price;

		$cart_details = array(
			array(
				'name'          => 'Test Download',
				'id'            => $simple_download->ID,
				'item_number'   => array(
					'id'        => $simple_download->ID,
					'options'   => array(
						'price_id' => 1
					)
				),
				'price'         => $simple_price,
				'item_price'    => $simple_price,
				'tax'           => 0,
				'quantity'      => 1
			),
			array(
				'name'          => 'Variable Test Download',
				'id'            => $variable_download->ID,
				'item_number'   => array(
					'id'        => $variable_download->ID,
					'options'   => array(
						'price_id' => 1
					)
				),
				'price'         => $variable_item_price,
				'item_price'    => $variable_item_price,
				'tax'           => 0,
				'quantity'      => 1
			),
		);

		$purchase_data = array(
			'price'         => number_format( (float) $total, 2 ),
			'date'          => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			'purchase_key'  => strtolower( md5( uniqid() ) ),
			'user_email'    => $user_info['email'],
			'user_info'     => $user_info,
			'currency'      => 'USD',
			'downloads'     => $download_details,
			'cart_details'  => $cart_details,
			'status'        => 'pending'
		);

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['SERVER_NAME'] = 'cs-virtual.local';

		$payment_id = cs_insert_payment( $purchase_data );
		$key        = $purchase_data['purchase_key'];

		$transaction_id = 'CS_ORDER';
		$payment = new CS_Payment( $payment_id );
		$payment->transaction_id = $transaction_id;
		$payment->save();

		cs_insert_payment_note( $payment_id, sprintf( __( 'PayPal Transaction ID: %s', 'commercestore' ), $transaction_id ) );

		return $payment_id;

	}

	/**
	 * Create a simple payment.
	 *
	 * @since 2.3
	 */
	public static function create_simple_guest_payment() {

		global $cs_options;

		// Enable a few options
		$cs_options['sequential_prefix'] = 'CS-';

		$simple_download   = CS_Helper_Download::create_simple_download();
		$variable_download = CS_Helper_Download::create_variable_download();

		/** Generate some sales */
		$user_info = array(
			'id'            => 0,
			'email'         => 'guest@example.org',
			'first_name'    => 'Guest',
			'last_name'     => 'User',
			'discount'      => 'none'
		);

		$download_details = array(
			array(
				'id' => $simple_download->ID,
				'options' => array(
					'price_id' => 0
				)
			),
			array(
				'id' => $variable_download->ID,
				'options' => array(
					'price_id' => 1
				)
			),
		);

		$total                  = 0;
		$simple_price           = get_post_meta( $simple_download->ID, 'cs_price', true );
		$variable_prices        = get_post_meta( $variable_download->ID, 'cs_variable_prices', true );
		$variable_item_price    = $variable_prices[1]['amount']; // == $100

		$total += $variable_item_price + $simple_price;

		$cart_details = array(
			array(
				'name'          => 'Test Download',
				'id'            => $simple_download->ID,
				'item_number'   => array(
					'id'        => $simple_download->ID,
					'options'   => array(
						'price_id' => 1
					)
				),
				'price'         => $simple_price,
				'item_price'    => $simple_price,
				'tax'           => 0,
				'quantity'      => 1
			),
			array(
				'name'          => 'Variable Test Download',
				'id'            => $variable_download->ID,
				'item_number'   => array(
					'id'        => $variable_download->ID,
					'options'   => array(
						'price_id' => 1
					)
				),
				'price'         => $variable_item_price,
				'item_price'    => $variable_item_price,
				'tax'           => 0,
				'quantity'      => 1
			),
		);

		$purchase_data = array(
			'price'         => number_format( (float) $total, 2 ),
			'date'          => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			'purchase_key'  => strtolower( md5( uniqid() ) ),
			'user_email'    => $user_info['email'],
			'user_info'     => $user_info,
			'currency'      => 'USD',
			'downloads'     => $download_details,
			'cart_details'  => $cart_details,
			'status'        => 'pending'
		);

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['SERVER_NAME'] = 'cs-virtual.local';

		$payment_id = cs_insert_payment( $purchase_data );
		$key        = $purchase_data['purchase_key'];

		$transaction_id = 'CS_GUEST_ORDER';
		cs_set_payment_transaction_id( $payment_id, $transaction_id );
		cs_insert_payment_note( $payment_id, sprintf( __( 'PayPal Transaction ID: %s', 'commercestore' ), $transaction_id ) );

		return $payment_id;

	}

	/**
	 * Create a simple payment with tax.
	 *
	 * @since 2.3
	 */
	public static function create_simple_payment_with_tax() {

		global $cs_options;

		// Enable a few options
		$cs_options['sequential_prefix'] = 'CS-';

		$simple_download   = CS_Helper_Download::create_simple_download();
		$variable_download = CS_Helper_Download::create_variable_download();

		/** Generate some sales */
		$user      = get_userdata(1);
		$user_info = array(
			'id'            => $user->ID,
			'email'         => $user->user_email,
			'first_name'    => $user->first_name,
			'last_name'     => $user->last_name,
			'discount'      => 'none'
		);

		$download_details = array(
			array(
				'id' => $simple_download->ID,
				'options' => array(
					'price_id' => 0
				)
			),
			array(
				'id' => $variable_download->ID,
				'options' => array(
					'price_id' => 1
				)
			),
		);

		$total                  = 0;
		$simple_price           = get_post_meta( $simple_download->ID, 'cs_price', true );
		$variable_prices        = get_post_meta( $variable_download->ID, 'cs_variable_prices', true );
		$variable_item_price    = $variable_prices[1]['amount']; // == $100

		$total += $variable_item_price + $simple_price + 10 + 1; // Add our tax into the payment total

		$cart_details = array(
			array(
				'name'          => 'Test Download',
				'id'            => $simple_download->ID,
				'item_number'   => array(
					'id'        => $simple_download->ID,
					'options'   => array(
						'price_id' => 1
					)
				),
				'price'         => $simple_price,
				'item_price'    => $simple_price,
				'tax'           => 1,
				'quantity'      => 1
			),
			array(
				'name'          => 'Variable Test Download',
				'id'            => $variable_download->ID,
				'item_number'   => array(
					'id'        => $variable_download->ID,
					'options'   => array(
						'price_id' => 1
					)
				),
				'price'         => $variable_item_price,
				'item_price'    => $variable_item_price,
				'tax'           => 10,
				'quantity'      => 1
			),
		);

		$purchase_data = array(
			'price'         => number_format( (float) $total, 2 ),
			'date'          => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			'purchase_key'  => strtolower( md5( uniqid() ) ),
			'user_email'    => $user_info['email'],
			'user_info'     => $user_info,
			'currency'      => 'USD',
			'downloads'     => $download_details,
			'cart_details'  => $cart_details,
			'status'        => 'pending',
			'tax'           => 11,
		);

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['SERVER_NAME'] = 'cs-virtual.local';

		$payment_id = cs_insert_payment( $purchase_data );
		$key        = $purchase_data['purchase_key'];

		$transaction_id = 'CS_ORDER_TAX';
		$payment = new CS_Payment( $payment_id );
		$payment->transaction_id = $transaction_id;
		$payment->save();

		cs_insert_payment_note( $payment_id, sprintf( __( 'PayPal Transaction ID: %s', 'commercestore' ), $transaction_id ) );

		return $payment_id;

	}

	/**
	 * Create a simple payment with a quantity of two
	 *
	 * @since 2.3
	 */
	public static function create_simple_payment_with_quantity_tax() {

		global $cs_options;

		// Enable a few options
		$cs_options['sequential_prefix'] = 'CS-';

		$simple_download   = CS_Helper_Download::create_simple_download();
		$variable_download = CS_Helper_Download::create_variable_download();

		/** Generate some sales */
		$user      = get_userdata(1);
		$user_info = array(
			'id'            => $user->ID,
			'email'         => $user->user_email,
			'first_name'    => $user->first_name,
			'last_name'     => $user->last_name,
			'discount'      => 'none'
		);

		$download_details = array(
			array(
				'id' => $simple_download->ID,
				'options' => array(
					'price_id' => 0
				),
				'quantity' => 2,
			),
			array(
				'id' => $variable_download->ID,
				'options' => array(
					'price_id' => 1
				),
				'quantity' => 2,
			),
		);

		$total                  = 0;
		$simple_price           = get_post_meta( $simple_download->ID, 'cs_price', true );
		$variable_prices        = get_post_meta( $variable_download->ID, 'cs_variable_prices', true );
		$variable_item_price    = $variable_prices[1]['amount']; // == $100

		$total += $variable_item_price + $simple_price + 20 + 2; // Add our tax into the payment total

		$cart_details = array(
			array(
				'name'          => 'Test Download',
				'id'            => $simple_download->ID,
				'item_number'   => array(
					'id'        => $simple_download->ID,
					'options'   => array(
						'price_id' => 1
					)
				),
				'price'         => $simple_price * 2,
				'item_price'    => $simple_price,
				'tax'           => 2,
				'quantity'      => 2
			),
			array(
				'name'          => 'Variable Test Download',
				'id'            => $variable_download->ID,
				'item_number'   => array(
					'id'        => $variable_download->ID,
					'options'   => array(
						'price_id' => 1
					)
				),
				'price'         => $variable_item_price * 2,
				'item_price'    => $variable_item_price,
				'tax'           => 20,
				'quantity'      => 2
			),
		);

		$purchase_data = array(
			'price'         => number_format( (float) $total, 2 ),
			'date'          => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			'purchase_key'  => strtolower( md5( uniqid() ) ),
			'user_email'    => $user_info['email'],
			'user_info'     => $user_info,
			'currency'      => 'USD',
			'downloads'     => $download_details,
			'cart_details'  => $cart_details,
			'status'        => 'pending',
			'tax'           => 22,
		);

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['SERVER_NAME'] = 'cs-virtual.local';

		$payment_id = cs_insert_payment( $purchase_data );
		$key        = $purchase_data['purchase_key'];

		$transaction_id = 'CS_ORDER_QUANTITY_TAX';
		$payment = new CS_Payment( $payment_id );
		$payment->transaction_id = $transaction_id;
		$payment->save();

		cs_insert_payment_note( $payment_id, sprintf( __( 'PayPal Transaction ID: %s', 'commercestore' ), $transaction_id ) );

		return $payment_id;

	}

	public static function create_simple_payment_with_fee() {

		global $cs_options;

		// Enable a few options
		$cs_options['sequential_prefix'] = 'CS-';

		$simple_download   = CS_Helper_Download::create_simple_download();

		add_filter( 'cs_cart_contents', function( $cart ) use ( $simple_download ) {
			return array( 0 => array(
				'id' => $simple_download->ID,
				'options' => array(),
				'quantity' => 1
			) );
		}, 10 );

		add_filter( 'cs_item_quantities_enabled', '__return_true' );

		/** Generate some sales */
		$user      = get_userdata(1);
		$user_info = array(
			'id'            => $user->ID,
			'email'         => $user->user_email,
			'first_name'    => $user->first_name,
			'last_name'     => $user->last_name,
			'discount'      => 'none'
		);

		$download_details = array(
			array(
				'id' => $simple_download->ID,
				'options' => array(
					'price_id' => 0
				),
				'quantity' => 2,
			),
		);

		$total                  = 0;
		$simple_price           = get_post_meta( $simple_download->ID, 'cs_price', true );

		$total += $simple_price + 2; // Add our tax into the payment total

		$cart_details = array(
			array(
				'name'          => 'Test Download',
				'id'            => $simple_download->ID,
				'item_number'   => array(
					'id'        => $simple_download->ID,
					'options'   => array(
						'price_id' => 1
					),
				),
				'price'         => $simple_price * 2,
				'item_price'    => $simple_price,
				'tax'           => 2,
				'quantity'      => 2
			),
		);

		$purchase_data = array(
			'price'         => number_format( (float) $total, 2 ),
			'date'          => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
			'purchase_key'  => strtolower( md5( uniqid() ) ),
			'user_email'    => $user_info['email'],
			'user_info'     => $user_info,
			'currency'      => 'USD',
			'downloads'     => $download_details,
			'cart_details'  => $cart_details,
			'status'        => 'pending',
			'tax'           => 2,
		);

		$fee_args = array(
			'label'  => 'Test Fee',
			'type'   => 'fee',
			'amount' => 5,
		);

		CS()->fees->add_fee( $fee_args );

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['SERVER_NAME'] = 'cs-virtual.local';

		$payment_id = cs_insert_payment( $purchase_data );
		$key        = $purchase_data['purchase_key'];

		$transaction_id = 'CS_ORDER_FEE';
		$payment = new CS_Payment( $payment_id );
		$payment->transaction_id = $transaction_id;
		$payment->save();

		cs_insert_payment_note( $payment_id, sprintf( __( 'PayPal Transaction ID: %s', 'commercestore' ), $transaction_id ) );

		remove_all_filters( 'cs_cart_contents' );
		remove_filter( 'cs_item_quantities_enabled', '__return_true' );

		return $payment_id;

	}

	/**
	 * Create a simple payment allowing a payment date to be passed
	 *
	 * @since 2.3
	 */
	public static function create_simple_payment_with_date( $date ) {

		global $cs_options;

		// Enable a few options
		$cs_options['sequential_prefix'] = 'CS-';

		$simple_download   = CS_Helper_Download::create_simple_download();
		$variable_download = CS_Helper_Download::create_variable_download();

		/** Generate some sales */
		$user      = get_userdata(1);
		$user_info = array(
			'id'            => $user->ID,
			'email'         => $user->user_email,
			'first_name'    => $user->first_name,
			'last_name'     => $user->last_name,
			'discount'      => 'none',
		);

		$download_details = array(
			array(
				'id' => $simple_download->ID,
				'options' => array(
					'price_id' => 0
				)
			),
			array(
				'id' => $variable_download->ID,
				'options' => array(
					'price_id' => 1
				)
			),
		);

		$total                  = 0;
		$simple_price           = get_post_meta( $simple_download->ID, 'cs_price', true );
		$variable_prices        = get_post_meta( $variable_download->ID, 'cs_variable_prices', true );
		$variable_item_price    = $variable_prices[1]['amount']; // == $100

		$total += $variable_item_price + $simple_price;

		$cart_details = array(
			array(
				'name'          => 'Test Download',
				'id'            => $simple_download->ID,
				'item_number'   => array(
					'id'        => $simple_download->ID,
					'options'   => array(
						'price_id' => 1
					)
				),
				'price'         => $simple_price,
				'item_price'    => $simple_price,
				'tax'           => 0,
				'quantity'      => 1
			),
			array(
				'name'          => 'Variable Test Download',
				'id'            => $variable_download->ID,
				'item_number'   => array(
					'id'        => $variable_download->ID,
					'options'   => array(
						'price_id' => 1
					)
				),
				'price'         => $variable_item_price,
				'item_price'    => $variable_item_price,
				'tax'           => 0,
				'quantity'      => 1
			),
		);

		$purchase_data = array(
			'price'         => number_format( (float) $total, 2 ),
			'date'          => $date,
			'purchase_key'  => strtolower( md5( uniqid() ) ),
			'user_email'    => $user_info['email'],
			'user_info'     => $user_info,
			'currency'      => 'USD',
			'downloads'     => $download_details,
			'cart_details'  => $cart_details,
			'status'        => 'pending'
		);

		$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
		$_SERVER['SERVER_NAME'] = 'cs-virtual.local';

		$payment_id = cs_insert_payment( $purchase_data );
		$key        = $purchase_data['purchase_key'];

		$transaction_id = 'CS_ORDER_DATE';
		$payment = new CS_Payment( $payment_id );
		$payment->transaction_id = $transaction_id;
		$payment->date = $date;
		$payment->save();

		cs_insert_payment_note( $payment_id, sprintf( __( 'PayPal Transaction ID: %s', 'commercestore' ), $transaction_id ) );

		return $payment_id;

	}

	public function fake_cart_contents_check() {
		return true;
	}

}
