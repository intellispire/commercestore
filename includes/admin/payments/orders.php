<?php
/**
 * Order Details/Add New Order Sections
 *
 * @package     CS
 * @subpackage  Admin/Orders
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Publishing ******************************************************************/

/**
 * Outputs publishing actions.
 *
 * UI is modelled off block-editor header region.
 *
 * @since 3.0
 *
 * @param CS\Orders\Order $order Current order.
 */
function cs_order_details_publish( $order ) {
	$action_name = cs_is_add_order_page()
		? __( 'Create Order', 'commercestore' )
		: __( 'Save Order', 'commercestore' )
?>

	<div class="edit-post-editor-regions__header">
		<div class="edit-post-header">

			<div class="edit-post-header__settings">
				<?php if ( cs_is_add_order_page() ) : ?>
					<div class="cs-send-purchase-receipt">
						<div class="cs-form-group">
							<div class="cs-form-group__control">
								<input type="checkbox" name="cs_order_send_receipt" id="cs-order-send-receipt" class="cs-form-group__input" value="1" checked />

								<label for="cs-order-send-receipt">
								<?php esc_html_e( 'Send Purchase Receipt', 'commercestore' ); ?>
								</label>
								<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Checking this box will email the purchase receipt to the selected customer.', 'commercestore' ); ?>"></span>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<div id="publishing-action">
					<span class="spinner"></span>
					<input
						type="submit"
						id="cs-order-submit"
						class="button button-primary right"
						value="<?php echo esc_html( $action_name ); ?>"
						<?php if ( ! cs_is_add_order_page() ) : ?>
							autofocus
						<?php endif; ?>
					/>
				</div>
			</div>

			<div class="edit-post-header__toolbar">
			</div>

		</div>

	</div>

<?php
}

/** Sections ******************************************************************/

/**
 * Contains code to setup tabs & views using CS\Admin\Order_Sections().
 *
 * @since 3.0
 *
 * @param mixed $item
 */
function cs_order_sections( $item = false ) {

	// Instantiate the Sections class and sections array
	$sections = new CS\Admin\Order_Sections();

	// Setup sections variables
	$sections->use_js          = true;
	$sections->current_section = 'customer';
	$sections->item            = $item;
	$sections->base_url        = '';

	// Get all registered tabs & views
	$o_sections = cs_get_order_details_sections( $item );

	// Set the customer sections
	$sections->set_sections( $o_sections );

	// Display the sections
	$sections->display();
}

/**
 * Return the order details sections.
 *
 * @since 3.0
 *
 * @param object $order
 * @return array Sections.
 */
function cs_get_order_details_sections( $order ) {
	$sections = array(
		array(
			'id'       => 'customer',
			'label'    => __( 'Customer', 'commercestore' ),
			'icon'     => 'businessman',
			'callback' => 'cs_order_details_customer',
		),
		array(
			'id'       => 'email',
			'label'    => __( 'Email', 'commercestore' ),
			'icon'     => 'email',
			'callback' => 'cs_order_details_email',
		),
		array(
			'id'       => 'address',
			'label'    => __( 'Address', 'commercestore' ),
			'icon'     => 'admin-home',
			'callback' => 'cs_order_details_addresses',
		),
		array(
			'id'       => 'notes',
			'label'    => __( 'Notes', 'commercestore' ),
			'icon'     => 'admin-comments',
			'callback' => 'cs_order_details_notes',
		),
		array(
			'id'       => 'logs',
			'label'    => __( 'Logs', 'commercestore' ),
			'icon'     => 'admin-tools',
			'callback' => 'cs_order_details_logs',
		),
	);

	// Override sections if adding a new order.
	if ( cs_is_add_order_page() ) {
		$sections = array(
			array(
				'id'       => 'customer',
				'label'    => __( 'Customer', 'commercestore' ),
				'icon'     => 'businessman',
				'callback' => 'cs_order_details_customer',
			),
			array(
				'id'       => 'address',
				'label'    => __( 'Address', 'commercestore' ),
				'icon'     => 'admin-home',
				'callback' => 'cs_order_details_addresses',
			),
		);
	}

	/**
	 * Filter the sections.
	 *
	 * @since 3.0
	 *
	 * @param array  $sections Sections.
	 * @param object $order    Order object.
	 */
	return (array) apply_filters( 'cs_get_order_details_sections', $sections, $order );
}

/**
 * Output the order details customer section
 *
 * @since 3.0
 *
 * @param object $order
 */
function cs_order_details_customer( $order ) {
	$customer  = cs_get_customer( $order->customer_id );
	$payment   = cs_get_payment( $order->id );
	$user_info = $payment
		? $payment->user_info
		: array();

	$change_text = cs_is_add_order_page()
		? esc_html__( 'Assign', 'commercestore' )
		: esc_html__( 'Switch Customer', 'commercestore' );

	$customer_id = ! empty( $customer )
		? $customer->id
		: 0; ?>

	<div>
		<div class="column-container order-customer-info">
			<div class="column-container change-customer">
				<div class="cs-form-group">
					<label for="customer_id" class="cs-form-group__label"><?php esc_html_e( 'Assign to an existing customer', 'commercestore' ); ?></label>
					<div class="cs-form-group__control">
						<?php
						echo CS()->html->customer_dropdown(
							array(
								'class'         => 'cs-payment-change-customer-input cs-form-group__input',
								'selected'      => $customer_id,
								'id'            => 'customer-id',
								'name'          => 'customer-id',
								'none_selected' => esc_html__( 'Search for a customer', 'commercestore' ),
								'placeholder'   => esc_html__( 'Search for a customer', 'commercestore' ),
							)
						); // WPCS: XSS ok.
						?>
					</div>
				</div>

				<input type="hidden" name="current-customer-id" value="<?php echo esc_attr( $customer_id ); ?>" />
				<?php wp_nonce_field( 'cs_customer_details_nonce', 'cs_customer_details_nonce' ); ?>
			</div>

			<div class="customer-details-wrap" style="display: <?php echo esc_attr( ! empty( $customer ) ? 'flex' : 'none' ); ?>">
				<div class="avatar-wrap" id="customer-avatar">
					<span class="spinner is-active"></span>
				</div>
				<div class="customer-details" style="display: none;">
					<strong class="customer-name"></strong>
					<em class="customer-since">
						<?php
						echo wp_kses(
							sprintf(
								__( 'Customer since %s', 'commercestore' ), '<span>&hellip;</span>' ),
							array(
								'span' => true,
							)
						);
						?>
					</em>

					<span class="customer-record">
						<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=cs-customers' ) ); ?>"><?php esc_html_e( 'View customer record', 'commercestore' ); ?></a>
					</span>
				</div>
			</div>

			<p class="description">
				or <button class="cs-payment-new-customer button-link"><?php esc_html_e( 'create a new customer', 'commercestore' ); ?></button>
			</p>
		</div>

		<div class="column-container new-customer" style="display: none">
			<p style="margin-top: 0;">
				<input type="hidden" id="cs-new-customer" name="cs-new-customer" value="0" />
				<button class="cs-payment-new-customer-cancel button-link"><?php esc_html_e( '&larr; Use an existing customer', 'commercestore' ); ?></button>
			</p>

			<div class="cs-form-group">
				<label class="cs-form-group__label" for="cs_new_customer_first_name">
					<?php esc_html_e( 'First Name', 'commercestore' ); ?>
				</label>

				<div class="cs-form-group__control">
					<input type="text" id="cs_new_customer_first_name" name="cs-new-customer-first-name" value="" class="cs-form-group__input regular-text" />
				</div>
			</div>

			<div class="cs-form-group">
				<label class="cs-form-group__label" for="cs_new_customer_last_name">
					<?php esc_html_e( 'Last Name', 'commercestore' ); ?>
				</label>

				<div class="cs-form-group__control">
					<input type="text" id="cs_new_customer_last_name" name="cs-new-customer-last-name" value="" class="cs-form-group__input regular-text" />
				</div>
			</div>

			<div class="cs-form-group">
				<label class="cs-form-group__label" for="cs_new_customer_email">
					<?php esc_html_e( 'Email', 'commercestore' ); ?>
				</label>

				<div class="cs-form-group__control">
					<input type="email" id="cs_new_customer_email" name="cs-new-customer-email" value="" class="cs-form-group__input regular-text" />
				</div>
			</div>
		</div>
	</div>

	<?php

	// The cs_payment_personal_details_list hook is left here for backwards compatibility
	if ( ! cs_is_add_order_page() && $payment instanceof CS_Payment ) {
		do_action( 'cs_payment_personal_details_list', $payment->get_meta(), $user_info );
	}
	do_action( 'cs_payment_view_details',          $order->id );
}

/**
 * Output the order details email section
 *
 * @since 3.0
 *
 * @param object $order
 */
function cs_order_details_email( $order ) {
	$customer   = cs_get_customer( $order->customer_id );
	$all_emails = array( 'primary' => $customer->email );

	foreach ( $customer->emails as $key => $email ) {
		if ( $customer->email === $email ) {
			continue;
		}

		$all_emails[ $key ] = $email;
	}

	$help = __( 'Send a new copy of the purchase receipt to the email address used for this order. If download URLs were included in the original receipt, new ones will be included.', 'commercestore' );
?>

	<div>
		<?php
		if ( ! empty( $customer->emails ) && count( (array) $customer->emails ) > 1 ) : ?>
			<fieldset class="cs-form-group">
				<legend class="cs-form-group__label">
					<?php _e( 'Send email receipt to', 'commercestore' ); ?>
				</legend>

				<?php foreach ( $all_emails as $key => $email ) : ?>
				<div class="cs-form-group__control is-radio">
					<input id="<?php echo rawurlencode( sanitize_email( $email ) ); ?>" class="cs-form-group__input" name="cs-order-resend-receipt-address" type="radio" value="<?php echo rawurlencode( sanitize_email( $email ) ); ?>" <?php checked( true, ( 'primary' === $key ) ); ?> />

					<label for="<?php echo rawurlencode( sanitize_email( $email ) ); ?>">
						<?php echo esc_attr( $email ); ?>
					</label>
				</div>
				<?php endforeach; ?>

				<p class="cs-form-group__help description">
					<?php echo esc_html( $help ); ?>
				</p>
			</fieldset>

		<?php else : ?>

			<div class="cs-form-group">
				<label class="cs-form-group__label screen-reader-text" for="<?php echo esc_attr( $order->email ); ?>">
					<?php esc_html_e( 'Email Address', 'commercestore' ); ?>
				</label>

				<div class="cs-form-group__control">
					<input readonly type="email" id="<?php echo esc_attr( $order->email ); ?>" class="cs-form-group__input regular-text" value="<?php echo esc_attr( $order->email ); ?>" />
				</div>

				<p class="cs-form-group__help description">
					<?php echo esc_html( $help ); ?>
				</p>
			</div>

		<?php endif; ?>

		<p>
			<a href="<?php echo esc_url( add_query_arg( array(
				'cs-action'  => 'email_links',
				'purchase_id' => $order->id,
			) ) ); ?>" id="<?php if ( ! empty( $customer->emails ) && count( (array) $customer->emails ) > 1 ) {
				echo esc_attr( 'cs-select-receipt-email' );
			} else {
				echo esc_attr( 'cs-resend-receipt' );
			} ?>" class="button-secondary"><?php esc_html_e( 'Resend Receipt', 'commercestore' ); ?></a>
		</p>

		<?php do_action( 'cs_view_order_details_resend_receipt_after', $order->id ); ?>

	</div>
	<?php
}

/**
 * Output the order details addresses section
 *
 * @since 3.0
 *
 * @param object $order
 */
function cs_order_details_addresses( $order ) {
	$address = cs_is_add_order_page()
		? (object) array(
			'id'          => 0,
			'order_id'    => 0,
			'first_name'  => '',
			'last_name'   => '',
			'address'     => '',
			'address2'    => '',
			'city'        => '',
			'region'      => '',
			'postal_code' => '',
			'country'     => '',
		)
		: $order->get_address(); ?>

	<div id="cs-order-address">
		<?php do_action( 'cs_view_order_details_billing_before', $order->id ); ?>

		<div class="order-data-address">
			<h3><?php esc_html_e( 'Billing Address', 'commercestore' ); ?></h3>

			<div class="customer-address-select-wrap cs-form-group" style="display: none; padding: 16px 0; border-bottom: 1px solid #ccd0d4;">
				<label for="cs_customer_existing_addresses" class="cs-form-group__label"><?php esc_html_e( 'Existing Address:', 'commercestore' ); ?></label>
				<div class="cs-form-group__control"></div>
			</div>

			<div class="cs-form-group">
				<label for="cs_order_address_address" class="cs-form-group__label"><?php esc_html_e( 'Line 1:', 'commercestore' ); ?></label>
				<div class="cs-form-group__control">
					<input type="text" name="cs_order_address[address]" id="cs_order_address_address" class="cs-form-group__input regular-text" value="<?php echo esc_attr( $address->address ); ?>" />
				</div>
			</div>

			<div class="cs-form-group">
				<label for="cs_order_address_address2" class="cs-form-group__label"><?php esc_html_e( 'Line 2:', 'commercestore' ); ?></label>
				<div class="cs-form-group__control">
					<input type="text" name="cs_order_address[address2]" class="cs-form-group__input regular-text" id="cs_order_address_address2" value="<?php echo esc_attr( $address->address2 ); ?>" />
				</div>
			</div>

			<div class="cs-form-group">
				<label for="cs_order_address_city" class="cs-form-group__label"><?php echo esc_html_x( 'City:', 'Address City', 'commercestore' ); ?></label>
				<div class="cs-form-group__control">
					<input type="text" name="cs_order_address[city]" class="cs-form-group__input regular-text" id="cs_order_address_city" value="<?php echo esc_attr( $address->city ); ?>" />
				</div>
			</div>

			<div class="cs-form-group">
				<label for="cs_order_address_postal_code" class="cs-form-group__label"><?php echo esc_html_x( 'Zip / Postal Code:', 'Zip / Postal code of address', 'commercestore' ); ?></label>
				<div class="cs-form-group__control">
					<input type="text" name="cs_order_address[postal_code]" class="cs-form-group__input regular-text" id="cs_order_address_postal_code" value="<?php echo esc_attr( $address->postal_code ); ?>" class="med-text" />
				</div>
			</div>

			<div class="cs-form-group">
				<label for="cs_order_address_country" class="cs-form-group__label"><?php echo esc_html_x( 'Country:', 'Address country', 'commercestore' ); ?></label>
				<div class="cs-form-group__control" id="cs-order-address-country-wrap">
					<?php
					echo CS()->html->country_select(
						array(
							'name'            => 'cs_order_address[country]',
							'id'              => 'cs-order-address-country',
							'class'           => 'cs-order-address-country cs-form-group__input',
							'show_option_all' => false,
							'data'            => array(
								'nonce'              => wp_create_nonce( 'cs-country-field-nonce' ),
								'search-type'        => 'no_ajax',
								'search-placeholder' => esc_html__( 'Search Countries', 'commercestore' ),
							),
						),
						$address->country
					); // WPCS: XSS ok.
					?>
				</div>
			</div>

			<div class="cs-form-group">
				<label for="cs_order_address_region" class="cs-form-group__label"><?php echo esc_html_x( 'Region:', 'Region of address', 'commercestore' ); ?></label>
				<div class="cs-form-group__control" id="cs-order-address-state-wrap">
					<?php
					$states = cs_get_shop_states( $address->country );
					if ( ! empty( $states ) ) {
						echo CS()->html->region_select(
							array(
								'name'             => 'cs_order_address[region]',
								'id'               => 'cs_order_address_region',
								'class'            => 'cs-order-address-region cs-form-group__input',
								'data'             => array(
									'search-type'        => 'no_ajax',
									'search-placeholder' => esc_html__( 'Search Regions', 'commercestore' ),
								),
							),
							$address->country,
							$address->region
						); // WPCS: XSS ok.
					} else {
						?>
						<input type="text" name="cs_order_address[region]" class="cs-form-group__input" value="<?php echo esc_attr( $address->region ); ?>" />
						<?php
					}
					?>
				</div>
			</div>

			<input type="hidden" name="cs_order_address[address_id]" value="<?php echo esc_attr( $address->id ); ?>" />
			<?php wp_nonce_field( 'cs_get_tax_rate_nonce', 'cs_get_tax_rate_nonce' ); ?>
		</div>

	</div><!-- /#cs-order-address -->

	<?php
	do_action( 'cs_payment_billing_details', $order->id );
}

/**
 * Output the order details notes section
 *
 * @since 3.0
 *
 * @param object $order
 */
function cs_order_details_notes( $order ) {
	$notes = cs_get_payment_notes( $order->id ); ?>

	<div>
		<?php echo cs_admin_get_notes_html( $notes ); // WPCS: XSS ok. ?>
		<?php echo cs_admin_get_new_note_form( $order->id, 'order' ); // WPCS: XSS ok. ?>
	</div>

	<?php
}

/**
 * Outputs the Order Details logs section.
 *
 * @since 3.0
 *
 * @param \CS\Orders\Order $order
 */
function cs_order_details_logs( $order ) {
?>

	<div>
		<?php
		/**
		 * Allows output before the list of logs.
		 *
		 * @since 3.0.0
		 *
		 * @param int $order_id ID of the current order.
		 */
		do_action( 'cs_view_order_details_logs_before', $order->id );
		?>

		<p><a href="<?php echo admin_url( 'edit.php?post_type=download&page=cs-tools&tab=logs&payment=' . $order->id ); ?>"><?php esc_html_e( 'File Download Log for Order', 'commercestore' ); ?></a></p>
		<p><a href="<?php echo admin_url( 'edit.php?post_type=download&page=cs-tools&tab=logs&customer=' . $order->customer_id ); ?>"><?php esc_html_e( 'Customer Download Log', 'commercestore' ); ?></a></p>
		<p><a href="<?php echo admin_url( 'edit.php?post_type=download&page=cs-payment-history&user=' . esc_attr( cs_get_payment_user_email( $order->id ) ) ); ?>"><?php esc_html_e( 'Customer Orders', 'commercestore' ); ?></a></p>

		<?php
		/**
		 * Allows further output after the list of logs.
		 *
		 * @since 3.0.0
		 *
		 * @param int $order_id ID of the current order.
		 */
		do_action( 'cs_view_order_details_logs_after', $order->id );
		?>
	</div>

<?php
}

/** Main **********************************************************************/

/**
 * Output the order details items box
 *
 * @since 3.0
 *
 * @param object $order
 */
function cs_order_details_overview( $order ) {
	$_items       = array();
	$_adjustments = array();
	$_refunds     = array();

	if ( true !== cs_is_add_order_page() ) {
		$items = cs_get_order_items( array(
			'order_id' => $order->id,
			'number'   => 999,
		) );

		foreach ( $items as $item ) {
			$item_adjustments = array();

			$adjustments = cs_get_order_adjustments( array(
				'object_id'   => $item->id,
				'number'      => 999,
				'object_type' => 'order_item',
				'type'        => array(
					'discount',
					'credit',
					'fee',
				),
			) );

			foreach ( $adjustments as $adjustment ) {
				// @todo cs_get_order_adjustment_to_json()?
				$adjustment_args = array(
					'id'           => esc_html( $adjustment->id ),
					'objectId'     => esc_html( $adjustment->object_id ),
					'objectType'   => esc_html( $adjustment->object_type ),
					'typeId'       => esc_html( $adjustment->type_id ),
					'type'         => esc_html( $adjustment->type ),
					'description'  => esc_html( $adjustment->description ),
					'subtotal'     => esc_html( $adjustment->subtotal ),
					'tax'          => esc_html( $adjustment->tax ),
					'total'        => esc_html( $adjustment->total ),
					'dateCreated'  => esc_html( $adjustment->date_created ),
					'dateModified' => esc_html( $adjustment->date_modified ),
					'uuid'         => esc_html( $adjustment->uuid ),
				);

				$item_adjustments[] = $adjustment_args;
				$_adjustments[]     = $adjustment_args;
			}

			// @todo cs_get_order_item_to_json()?
			$_items[] = array(
				'id'           => esc_html( $item->id ),
				'orderId'      => esc_html( $item->order_id ),
				'productId'    => esc_html( $item->product_id ),
				'productName'  => esc_html( $item->get_order_item_name() ),
				'priceId'      => esc_html( $item->price_id ),
				'cartIndex'    => esc_html( $item->cart_index ),
				'type'         => esc_html( $item->type ),
				'status'       => esc_html( $item->status ),
				'statusLabel'  => esc_html( cs_get_status_label( $item->status ) ),
				'quantity'     => esc_html( $item->quantity ),
				'amount'       => esc_html( $item->amount ),
				'subtotal'     => esc_html( $item->subtotal ),
				'discount'     => esc_html( $item->discount ),
				'tax'          => esc_html( $item->tax ),
				'total'        => esc_html( $item->total ),
				'dateCreated'  => esc_html( $item->date_created ),
				'dateModified' => esc_html( $item->date_modified ),
				'uuid'         => esc_html( $item->uuid ),

				'adjustments'  => $item_adjustments,
			);
		}

		$adjustments = cs_get_order_adjustments( array(
			'object_id'   => $order->id,
			'number'      => 999,
			'object_type' => 'order',
			'type'        => array(
				'discount',
				'credit',
				'fee',
			),
		) );

		foreach ( $adjustments as $adjustment ) {
			// @todo cs_get_order_adjustment_to_json()?
			$_adjustments[] = array(
				'id'           => esc_html( $adjustment->id ),
				'objectId'     => esc_html( $adjustment->object_id ),
				'objectType'   => esc_html( $adjustment->object_type ),
				'typeId'       => esc_html( $adjustment->type_id ),
				'type'         => esc_html( $adjustment->type ),
				'description'  => esc_html( $adjustment->description ),
				'subtotal'     => esc_html( $adjustment->subtotal ),
				'tax'          => esc_html( $adjustment->tax ),
				'total'        => esc_html( $adjustment->total ),
				'dateCreated'  => esc_html( $adjustment->date_created ),
				'dateModified' => esc_html( $adjustment->date_modified ),
				'uuid'         => esc_html( $adjustment->uuid ),
			);
		}

		$refunds = cs_get_order_refunds( $order->id );

		foreach ( $refunds as $refund ) {
			$_refunds[] = array(
				'id'              => esc_html( $refund->id ),
				'number'          => esc_html( $refund->order_number ),
				'total'           => esc_html( $refund->total ),
				'dateCreated'     => esc_html( $refund->date_created ),
				'dateCreatedi18n' => esc_html( cs_date_i18n( $refund->date_created ) ),
				'uuid'            => esc_html( $refund->uuid ),
			);
		}
	}

	$has_tax  = 'none';
	$tax_rate = $order->id ? $order->get_tax_rate() : false;

	$location = array(
		'rate'      => $tax_rate,
		'country'   => '',
		'region'    => '',
		'inclusive' => cs_prices_include_tax(),
	);

	if ( cs_is_add_order_page() && cs_use_taxes() ) {
		$has_tax = $location;
	} elseif ( $tax_rate ) {
		$has_tax         = $location;
		$has_tax['rate'] = $tax_rate;

		if ( $order->tax_rate_id ) {
			$tax_rate_object = $order->get_tax_rate_object();

			if ( $tax_rate_object ) {
				$has_tax['country'] = $tax_rate_object->name;
				$has_tax['region']  = $tax_rate_object->description;
			}
		}
	}

	$has_quantity = true;
	if ( cs_is_add_order_page() && ! cs_item_quantities_enabled() ) {
		$has_quantity = false;
	}

	wp_localize_script(
		'cs-admin-orders',
		'csAdminOrderOverview',
		array(
			'items'        => $_items,
			'adjustments'  => $_adjustments,
			'refunds'      => $_refunds,
			'isAdding'     => true === cs_is_add_order_page(),
			'hasQuantity'  => $has_quantity,
			'hasTax'       => $has_tax,
			'hasDiscounts' => true === cs_has_active_discounts(),
			'order'        => array(
				'status'         => $order->status,
				'currency'       => $order->currency,
				'currencySymbol' => html_entity_decode( cs_currency_symbol( $order->currency ) ),
				'subtotal'       => $order->subtotal,
				'discount'       => $order->discount,
				'tax'            => $order->tax,
				'total'          => $order->total,
			),
			'nonces'       => array(
				'cs_admin_order_get_item_amounts' => wp_create_nonce( 'cs_admin_order_get_item_amounts' ),
			),
			'i18n'         => array(
				'closeText' => esc_html__( 'Close', 'commercestore' ),
			),
		)
	);

	$templates = array(
		'actions',
		'subtotal',
		'tax',
		'total',
		'item',
		'adjustment',
		'adjustment-discount',
		'refund',
		'no-items',
		'copy-download-link',
		'form-add-order-item',
		'form-add-order-discount',
		'form-add-order-adjustment',
	);

	foreach ( $templates as $tmpl ) {
		echo '<script type="text/html" id="tmpl-cs-admin-order-' . esc_attr( $tmpl ) . '">';
		require_once CS_PLUGIN_DIR . 'includes/admin/views/tmpl-order-' . $tmpl . '.php';
		echo '</script>';
	}
?>

<div id="cs-order-overview" class="postbox cs-edit-purchase-element cs-order-overview">
	<table id="cs-order-overview-summary" class="widefat wp-list-table cs-order-overview-summary">
		<thead>
			<tr>
				<th class="column-name column-primary"><?php echo esc_html( cs_get_label_singular() ); ?></th>
				<th class="column-amount"><?php esc_html_e( 'Unit Price', 'commercestore' ); ?></th>
				<?php if ( $has_quantity ) : ?>
				<th class="column-quantity"><?php esc_html_e( 'Quantity', 'commercestore' ); ?></th>
				<?php endif; ?>
				<th class="column-subtotal column-right"><?php esc_html_e( 'Amount', 'commercestore' ); ?></th>
			</tr>
		</thead>
	</table>

	<div id="cs-order-overview-actions" class="cs-order-overview-actions inside"></div>
</div>

<?php

	/**
	 * @since unknown
	 */
	do_action( 'cs_view_order_details_files_after', $order->id );
}

/**
 * Output the order details sections box
 *
 * @since 3.0
 *
 * @param object $order
 */
function cs_order_details_sections( $order ) {
?>

	<div id="cs-customer-details" class="postbox">
		<h2 class="hndle">
			<span><?php esc_html_e( 'Order Details', 'commercestore' ); ?></span>
		</h2>
		<?php cs_order_sections( $order ); ?>
	</div>

<?php
}

/** Sidebar *******************************************************************/

/**
 * Output the order details extras box
 *
 * @since 3.0
 *
 * @param object $order
 */
function cs_order_details_extras( $order = false ) {
	$transaction_id = ! empty( $order->id )
		? $order->get_transaction_id()
		: '';

	$unlimited = ! empty( $order->id )
		? $order->has_unlimited_downloads()
		: false;

	$readonly = ! empty( $order->id )
		? 'readonly'
		: '';

	// Setup gateway list.
	if ( empty( $order->id ) ) {
		$known_gateways = cs_get_payment_gateways();

		$gateways = array();

		foreach ( $known_gateways as $id => $data ) {
			$gateways[ $id ] = esc_html( $data['admin_label'] );
		}
	}

	// Filter the transaction ID (here specifically for back-compat)
	if ( ! empty( $transaction_id ) ) {
		$transaction_id = apply_filters( 'cs_payment_details_transaction_id-' . $order->gateway, $transaction_id, $order->id );
	} ?>

	<div id="cs-order-extras" class="postbox cs-order-data">
		<h2 class="hndle">
			<span><?php esc_html_e( 'Order Extras', 'commercestore' ); ?></span>
		</h2>

		<div class="inside">
			<div class="cs-admin-box">
				<?php do_action( 'cs_view_order_details_payment_meta_before', $order->id ); ?>


				<?php if ( ! cs_is_add_order_page() ) : ?>
					<div class="cs-order-gateway cs-admin-box-inside cs-admin-box-inside--row">
						<span class="label"><?php esc_html_e( 'Gateway', 'commercestore' ); ?></span>
						<span class="value"><?php echo cs_get_gateway_admin_label( $order->gateway ); ?></span>
					</div>
				<?php else : ?>
					<div class="cs-order-gateway cs-admin-box-inside">
						<div class="cs-form-group">
							<label for="cs_gateway_select" class="cs-form-group__label"><?php esc_html_e( 'Gateway', 'commercestore' ); ?></label>
							<div class="cs-form-group__control">
								<?php
								echo CS()->html->select(
									array(
										'name'             => 'gateway',
										'class'            => 'cs-form-group__input',
										'id'               => 'cs_gateway_select',
										'options'          => $gateways,
										'selected'         => 'manual',
										'show_option_none' => false,
										'show_option_all'  => false,
									)
								); // WPCS: XSS ok.
								?>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<div class="cs-admin-box-inside">
					<div class="cs-form-group">
						<label for="cs_payment_key" class="cs-form-group__label"><?php esc_html_e( 'Key', 'commercestore' ); ?></label>
						<div class="cs-form-group__control">
							<input type="text" name="payment_key" id="cs_payment_key" class="cs-form-group__input regular-text" <?php echo esc_attr( $readonly ); ?> value="<?php echo esc_attr( $order->payment_key ); ?>" />
						</div>
					</div>
				</div>

				<?php if ( cs_is_add_order_page() ) : ?>
					<div class="cs-order-ip cs-admin-box-inside">
						<div class="cs-form-group">
							<label for="cs_ip" class="cs-form-group__label"><?php esc_html_e( 'IP', 'commercestore' ); ?></label>
							<div class="cs-form-group__control">
								<input type="text" name="ip" id="cs_ip" class="cs-form-group__input" value="<?php echo esc_attr( cs_get_ip() ); ?>" />
							</div>
						</div>
					</div>
				<?php else : ?>
					<div class="cs-order-gateway cs-admin-box-inside cs-admin-box-inside--row">
						<span class="label"><?php esc_html_e( 'IP', 'commercestore' ); ?></span>
						<span class="value"><?php echo cs_payment_get_ip_address_url( $order->id ); // WPCS: XSS ok. ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $transaction_id ) : ?>
					<div class="cs-order-tx-id cs-admin-box-inside cs-admin-box-inside--row">
						<span class="label"><?php esc_html_e( 'Transaction ID', 'commercestore' ); ?></span>
						<span><?php echo $transaction_id; ?></span>
					</div>
				<?php endif; ?>

				<?php if ( cs_is_add_order_page() ) : ?>
					<div class="cs-order-tx-id cs-admin-box-inside cs-admin-box-inside--row">
						<div class="cs-form-group">
							<label for="cs_transaction_id" class="cs-form-group__label"><?php esc_html_e( 'Transaction ID', 'commercestore' ); ?></label>
							<div class="cs-form-group__control">
								<input type="text" name="transaction_id" class="cs-form-group__input" id="cs_transaction_id" value="" />
							</div>
						</div>
					</div>
				<?php endif; ?>

				<div class="cs-unlimited-downloads cs-admin-box-inside">
					<div class="cs-form-group">
						<div class="cs-form-group__control">
							<input type="checkbox" name="cs-unlimited-downloads" id="cs_unlimited_downloads" class="cs-form-group__input" value="1"<?php checked( true, $unlimited, true ); ?>/>

							<label for="cs_unlimited_downloads">
							<?php esc_html_e( 'Unlimited Downloads', 'commercestore' ); ?></label>
							<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Checking this box will override all other file download limits for this purchase, granting the customer unlimited downloads of all files included on the purchase.', 'commercestore' ); ?>"></span>
						</div>
					</div>
				</div>

				<?php do_action( 'cs_view_order_details_payment_meta_after', $order->id ); ?>
			</div>
		</div>
	</div>

<?php
}

/**
 * Output the order details attributes box
 *
 * @since 3.0
 *
 * @param object $order
 */
function cs_order_details_attributes( $order ) {

	$recovery_url = cs_is_add_order_page()
		? ''
		: cs_get_payment( $order->id )->get_recovery_url();

	$order_date = cs_get_cs_timezone_equivalent_date_from_utc( CS()->utils->date( $order->date_created, 'utc', true ) );

	?>

	<div id="cs-order-update" class="postbox cs-order-data">
		<h2 class="hndle">
			<span><?php esc_html_e( 'Order Attributes', 'commercestore' ); ?></span>
		</h2>

		<div class="inside">
			<div class="cs-order-update-box cs-admin-box">
				<div class="cs-admin-box-inside">
					<div class="cs-form-group">
						<label for="cs_payment_status" class="cs-form-group__label">
							<?php
							esc_html_e( 'Status', 'commercestore' );

							$status_help  = '<ul>';
							$status_help .= '<li>' . __( '<strong>Pending</strong>: order is still processing or was abandoned by customer. Successful orders will be marked as Complete automatically once processing is finalized.', 'commercestore' ) . '</li>';
							$status_help .= '<li>' . __( '<strong>Complete</strong>: all processing is completed for this purchase.', 'commercestore' ) . '</li>';
							$status_help .= '<li>' . __( '<strong>Revoked</strong>: access to purchased items is disabled, perhaps due to policy violation or fraud.', 'commercestore' ) . '</li>';
							$status_help .= '<li>' . __( '<strong>Refunded</strong>: the purchase amount is returned to the customer and access to items is disabled.', 'commercestore' ) . '</li>';
							$status_help .= '<li>' . __( '<strong>Abandoned</strong>: the purchase attempt was not completed by the customer.', 'commercestore' ) . '</li>';
							$status_help .= '<li>' . __( '<strong>Failed</strong>: customer clicked Cancel before completing the purchase.', 'commercestore' ) . '</li>';
							$status_help .= '</ul>';
							?>
							<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php echo $status_help; // WPCS: XSS ok. ?>"></span>
						</label>
						<div class="cs-form-group__control">
							<select name="cs-payment-status" id="cs_payment_status" class="cs-form-group__input">
							<?php foreach ( cs_get_payment_statuses() as $key => $status ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>"<?php selected( $order->status, $key, true ); ?>><?php echo esc_html( $status ); ?></option>
							<?php endforeach; ?>
							</select>
						</div>
					</div>

					<?php
					if ( ! cs_is_add_order_page() ) :
						$trash_url = wp_nonce_url(
							cs_get_admin_url( array(
								'page'        => 'cs-payment-history',
								'order_type'  => 'sale',
								'cs-action'  => 'trash_order',
								'purchase_id' => $order->id,
							) ),
							'cs_payment_nonce'
						);
					?>
					<div style="margin-top: 8px;">
						<a href="<?php echo esc_url( $trash_url ); ?>" class="cs-delete-payment cs-delete">
							<?php esc_html_e( 'Move to Trash', 'commercestore' ); ?>
						</a>
					</div>
					<?php endif; ?>
				</div>

				<?php if ( ! cs_is_add_order_page() && cs_is_order_recoverable( $order->id ) && ! empty( $recovery_url ) ) : ?>
					<div class="cs-admin-box-inside">
						<div class="cs-form-group">
							<label class="cs-form-group__label" for="cs_recovery_url">
								<?php esc_html_e( 'Recover', 'commercestore' ); ?>
								<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php esc_html_e( 'Pending and abandoned payments can be resumed by the customer, using this custom URL. Payments can be resumed only when they do not have a transaction ID from the gateway.', 'commercestore' ); ?>"></span>
							</label>
							<div class="cs-form-group__control">
								<input type="text" class="cs-form-group__input" id="cs_recovery_url" readonly="readonly" value="<?php echo esc_url( $recovery_url ); ?>"/>
							</div>
						</div>
					</div>
				<?php endif; ?>

				<div class="cs-admin-box-inside">
					<div class="cs-form-group">
						<label for="cs-payment-date" class="cs-form-group__label"><?php esc_html_e( 'Date', 'commercestore' ); ?>
						</label>
						<div class="cs-form-group__control">
							<input type="text" id="cs-payment-date" class="cs-form-group__input cs_datepicker" name="cs-payment-date" value="<?php echo esc_attr( $order_date->format( 'Y-m-d' ) ); ?>"placeholder="<?php echo esc_attr( cs_get_date_picker_format() ); ?>"/>
						</div>
					</div>
				</div>

				<div class="cs-admin-box-inside">
					<fieldset class="cs-form-group">
						<legend class="cs-form-group__label">
							<?php echo esc_html( __( 'Time', 'commercestore' ) . ' (' . cs_get_timezone_abbr() . ')' ); ?>
						</legend>

						<div class="cs-form-group__control">
							<label for="cs-payment-time-hour" class="screen-reader-text">
								<?php esc_html_e( 'Hour', 'commercestore' ); ?>
							</label>
							<input type="number" class="cs-form-group__input small-text" min="0" max="24" step="1" name="cs-payment-time-hour" id="cs-payment-time-hour" value="<?php echo esc_attr( $order_date->format( 'H' ) ); ?>" />
							:

							<label for="cs-payment-time-min" class="screen-reader-text">
								<?php esc_html_e( 'Minute', 'commercestore' ); ?>
							</label>
							<input type="number" class="cs-form-group__input small-text" min="0" max="59" step="1" name="cs-payment-time-min" id="cs-payment-time-min" value="<?php echo esc_attr( $order_date->format( 'i' ) ); ?>" />
						</div>
					</fieldset>
				</div>

				<?php do_action( 'cs_view_order_details_update_inner', $order->id ); ?>

			</div><!-- /.cs-admin-box -->
		</div><!-- /.inside -->

	</div>

<?php
}

/**
 * Check if we are on the `Add New Order` page, or editing an existing one.
 *
 * @since 3.0
 *
 * @return boolean True if on the `Add Order` page, false otherwise.
 */
function cs_is_add_order_page() {
	return isset( $_GET['view'] ) && 'add-order' === sanitize_key( $_GET['view'] ); // WPCS: CSRF ok.
}

/**
 * Returns markup for an Order status badge.
 *
 * @since 3.0
 *
 * @param string $order_status Order status slug.
 * @return string
 */
function cs_get_order_status_badge( $order_status ) {

	switch( $order_status ) {
		case 'refunded' :
			$icon = '<span class="cs-admin-order-status-badge__icon dashicons dashicons-undo"></span>';
			break;
		case 'failed' :
			$icon = '<span class="cs-admin-order-status-badge__icon dashicons dashicons-no-alt"></span>';
			break;
		case 'complete' :
			$icon = '<span class="cs-admin-order-status-badge__icon dashicons dashicons-yes"></span>';
			break;
		default:
			$icon = '';
	}

	/**
	 * Filters the markup for the order status badge icon.
	 *
	 * @since 3.0
	 *
	 * @param string $icon Icon HTML markup.
	 */
	$icon = apply_filters( 'cs_get_order_status_badge_icon', $icon, $order_status );

	ob_start();
?>

<span class="cs-admin-order-status-badge cs-admin-order-status-badge--<?php echo esc_attr( $order_status ); ?>">

	<span class="cs-admin-order-status-badge__text">
		<?php echo cs_get_payment_status_label( $order_status ); ?>
	</span>
	<span class="cs-admin-order-status-badge__icon">
		<?php
		echo wp_kses(
			$icon,
			array(
				'span'    => array(
					'class' => true,
				),
				'svg'     => array(
					'class'       => true,
					'xmlns'       => true,
					'width'       => true,
					'height'      => true,
					'viewbox'     => true,
					'aria-hidden' => true,
					'role'        => true,
					'focusable'   => true,
				),
				'path'    => array(
					'fill'      => true,
					'fill-rule' => true,
					'd'         => true,
					'transform' => true,
				),
				'polygon' => array(
					'fill'      => true,
					'fill-rule' => true,
					'points'    => true,
					'transform' => true,
					'focusable' => true,
				),
			)
		);
		?>
	</span>

</span>

<?php
	return ob_get_clean();
}
