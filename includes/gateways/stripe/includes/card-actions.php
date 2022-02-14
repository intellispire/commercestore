<?php
/**
 * Card actions.
 *
 * @package CS_Stripe
 * @since   2.7.0
 */

/**
 * Process the card update actions from the manage card form.
 *
 * @since 2.6
 * @param $data
 * @return void
 */
function cs_stripe_process_card_update() {
	$enabled = cs_stripe_existing_cards_enabled();

	// Feature not enabled.
	if ( ! $enabled ) {
		return wp_send_json_error( array(
			'message' => __( 'This feature is not available at this time.', 'csx' ),
		) );
	}

	// Source can't be found.
	$payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : '';

	if ( empty ( $payment_method ) ) {
		return wp_send_json_error( array(
			'message' => __( 'Error updating card.', 'csx' ),
		) );
	}

	// Nonce failed.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], $payment_method . '_update' ) ) {
		return wp_send_json_error( array(
			'message' => __( 'Error updating card.', 'csx' ),
		) );
	}

	// Customer can't be found.
	$stripe_customer_id = csx_get_stripe_customer_id( get_current_user_id() );

	if ( empty( $stripe_customer_id ) ) {
		return wp_send_json_error( array(
			'message' => __( 'Error updating card.', 'csx' ),
		) );
	}

	try {
		$card_args   = array();
		$card_fields = array(
			'address_city',
			'address_country',
			'address_line1',
			'address_line2',
			'address_zip',
			'address_state',
			'exp_month',
			'exp_year',
		);

		foreach ( $card_fields as $card_field ) {
			$card_args[ $card_field ] = ( isset( $_POST[ $card_field ] ) && '' !== $_POST[ $card_field ] )
				? sanitize_text_field( $_POST[ $card_field ] )
				: null;
		}

		// Update a PaymentMethod
		if ( 'pm_' === substr( $payment_method, 0, 3 ) ) {
			$address_args = array(
				'city'        => $card_args['address_city'],
				'country'     => $card_args['address_country'],
				'line1'       => $card_args['address_line1'],
				'line2'       => $card_args['address_line2'],
				'postal_code' => $card_args['address_zip'],
				'state'       => $card_args['address_state'],
			);

			csx_api_request( 'PaymentMethod', 'update', $payment_method, array(
				'billing_details' => array(
					'address' => $address_args,
				),
				'card'  => array(
					'exp_month' => $card_args['exp_month'],
					'exp_year'  => $card_args['exp_year'],
				),
			) );

		// Update a legacy Card.
		} else {
			csx_api_request( 'Customer', 'updateSource', $stripe_customer_id, $payment_method, $card_args );
		}

		return wp_send_json_success( array(
			'message' => esc_html__( 'Card successfully updated.', 'csx' ),
		) );
	} catch( \Exception $e ) {
		return wp_send_json_error( array(
			'message' => esc_html( $e->getMessage() ),
		) );
	}
}
add_action( 'wp_ajax_csx_update_payment_method', 'cs_stripe_process_card_update' );

/**
 * Process the set default card action from the manage card form.
 *
 * @since 2.6
 * @param $data
 * @return void
 */
function cs_stripe_process_card_default( $data ) {
	$enabled = cs_stripe_existing_cards_enabled();

	// Feature not enabled.
	if ( ! $enabled ) {
		return wp_send_json_error( array(
			'message' => __( 'This feature is not available at this time.', 'csx' ),
		) );
	}

	// Source can't be found.
	$payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : '';

	if ( empty ( $payment_method ) ) {
		return wp_send_json_error( array(
			'message' => __( 'Error updating card.', 'csx' ),
		) );
	}

	// Nonce failed.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], $payment_method . '_update' ) ) {
		return wp_send_json_error( array(
			'message' => __( 'Error updating card.', 'csx' ),
		) );
	}

	// Customer can't be found.
	$stripe_customer_id = csx_get_stripe_customer_id( get_current_user_id() );

	if ( empty( $stripe_customer_id ) ) {
		return wp_send_json_error( array(
			'message' => __( 'Error updating card.', 'csx' ),
		) );
	}

	try {

		csx_api_request( 'Customer', 'update', $stripe_customer_id, array(
			'invoice_settings' => array(
				'default_payment_method' => $payment_method,
			),
		) );

		return wp_send_json_success( array(
			'message' =>	esc_html__( 'Card successfully set as default.', 'csx' ),
		) );
	} catch( \Exception $e ) {
		return wp_send_json_error( array(
			'message' => esc_html( $e->getMessage() ),
		) );
	}
}
add_action( 'wp_ajax_csx_set_payment_method_default', 'cs_stripe_process_card_default' );

/**
 * Process the delete card action from the manage card form.
 *
 * @since 2.6
 * @param $data
 * @return void
 */
function cs_stripe_process_card_delete( $data ) {
	$enabled = cs_stripe_existing_cards_enabled();

	// Feature not enabled.
	if ( ! $enabled ) {
		return wp_send_json_error( array(
			'message' => __( 'This feature is not available at this time.', 'csx' ),
		) );
	}

	// Source can't be found.
	$payment_method = isset( $_POST['payment_method'] ) ? sanitize_text_field( $_POST['payment_method'] ) : '';

	if ( empty ( $payment_method ) ) {
		return wp_send_json_error( array(
			'message' => __( 'Error updating card.', 'csx' ),
		) );
	}

	// Nonce failed.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], $payment_method . '_update' ) ) {
		return wp_send_json_error( array(
			'message' => __( 'Error updating card.', 'csx' ),
		) );
	}

	// Customer can't be found.
	$stripe_customer_id = csx_get_stripe_customer_id( get_current_user_id() );

	if ( empty( $stripe_customer_id ) ) {
		return wp_send_json_error( array(
			'message' => __( 'Error updating card.', 'csx' ),
		) );
	}

	// Removal is disabled for this card.
	$should_remove = apply_filters(
		'cs_stripe_should_remove_card',
		array(
			'remove' => true,
			'message' => ''
		),
		$payment_method,
		$stripe_customer_id,
		$data
	);

	if ( ! $should_remove['remove'] ) {
		return wp_send_json_error( array(
			'message' => esc_html__( 'This feature is not available at this time.', 'csx' ),
		) );
	}

	try {
		// Detach a PaymentMethod.
		if ( 'pm_' === substr( $payment_method, 0, 3 ) ) {
			$payment_method = csx_api_request( 'PaymentMethod', 'retrieve', $payment_method );
			$payment_method->detach();

		// Delete a Card.
		} else {
			csx_api_request( 'Customer', 'deleteSource', $stripe_customer_id, $payment_method );
		}

		return wp_send_json_success( array(
			'message' =>	esc_html__( 'Card successfully removed.', 'csx' ),
		) );
	} catch( \Exception $e ) {
		return wp_send_json_error( array(
			'message' => esc_html( $e->getMessage() ),
		) );
	}
}
add_action( 'wp_ajax_csx_delete_payment_method', 'cs_stripe_process_card_delete' );

/**
 * Handles adding a new PaymentMethod (via AJAX).
 *
 * @since 2.6
 * @param $data
 * @return void
 */
function csx_add_payment_method() {
	$enabled = cs_stripe_existing_cards_enabled();

	// Feature not enabled.
	if ( ! $enabled ) {
		return wp_send_json_error( array(
			'message' => __( 'This feature is not available at this time.', 'csx' ),
		) );
	}

	if ( cs_stripe()->rate_limiting->has_hit_card_error_limit()  ) {
		// Increase the card error count.
		cs_stripe()->rate_limiting->increment_card_error_count();

		return wp_send_json_error( array(
			'message' => __( 'Unable to update your account at this time, please try again later', 'csx' ),
		) );
	}

	// PaymetnMethod can't be found.
	$payment_method_id = isset( $_POST['payment_method_id'] ) ? sanitize_text_field( $_POST['payment_method_id'] ) : false;

	if ( ! $payment_method_id ) {
		return wp_send_json_error( array(
			'message' => __( 'Missing card ID.', 'csx' ),
		) );
	}

	// Nonce failed.
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'cs-stripe-add-card' ) ) {
		return wp_send_json_error( array(
			'message' => __( 'Error adding card.', 'csx' ),
		) );
	}

	// Customer can't be found.
	$stripe_customer_id = csx_get_stripe_customer_id( get_current_user_id() );

	if ( empty( $stripe_customer_id ) ) {
		return wp_send_json_error( array(
			'message' => __( 'Unable to find user.', 'csx' ),
		) );
	}

	try {
		$payment_method = csx_api_request( 'PaymentMethod', 'retrieve', $payment_method_id );
		$payment_method->attach( array(
			'customer' => $stripe_customer_id,
		) );

		return wp_send_json_success( array(
			'message' => esc_html__( 'Card successfully added.', 'csx' ),
		) );
	} catch( \Exception $e ) {
		// Increase the card error count.
		cs_stripe()->rate_limiting->increment_card_error_count();

		return wp_send_json_error( array(
			'message' => esc_html( $e->getMessage() ),
		) );
	}
}
add_action( 'wp_ajax_csx_add_payment_method', 'csx_add_payment_method' );