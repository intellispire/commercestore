<?php
/**
 * Render the Subscriptions table
 *
 * @access      public
 * @since       2.4
 * @return      void
 */
function cs_subscriptions_page() {

	if ( ! empty( $_GET['id'] ) ) {

		cs_recurring_subscription_details();

		return;

	} else if ( isset( $_GET['cs-action'] ) && $_GET['cs-action'] == 'add_subscription' ) {

		cs_recurring_new_subscription_details();

		return;

	}
	?>
	<div class="wrap">

		<h1>
			<?php _e( 'Subscriptions', 'cs-recurring' ); ?>
			<a href="<?php echo esc_url( add_query_arg( array( 'cs-action' => 'add_subscription' ) ) ); ?>" class="add-new-h2"><?php _e( 'Add New', 'cs-recurring' ); ?></a>
		</h1>
		<?php
		$subscribers_table = new CS_Subscription_Reports_Table();
		$subscribers_table->prepare_items();
		?>

		<form id="subscribers-filter" method="get">

			<input type="hidden" name="post_type" value="download" />
			<input type="hidden" name="page" value="cs-subscriptions" />
			<?php $subscribers_table->views() ?>
			<?php $subscribers_table->search_box( __( 'Search', 'commercestore' ), 'subscriptions' ) ?>
			<?php $subscribers_table->display() ?>

		</form>
		<?php _e( 'To narrow results, search can be prefixed with the following:', 'cs-recurring' ); ?><code>id:</code>, <code>profile_id:</code>, <code>product_id:</code>, <code>txn:</code>, <code>customer_id:</code>
	</div>
	<?php
}

/**
 * Recurring Subscription Details
 * @description Outputs the subscriber details
 * @since       2.5
 */
function cs_recurring_new_subscription_details() {

	$render = true;
	if ( ! current_user_can( 'edit_shop_payments' ) ) {
		cs_set_error( 'cs-no-access', __( 'You are not permitted to create new subscriptions.', 'cs-recurring' ) );
		$render = false;
	}

	$periods = CS_Recurring()->periods();
	?>
	<div class="wrap" id="cs-recurring-new-subscription-wrap">
		<h2><?php _e( 'Subscription Details', 'cs-recurring' ); ?></h2>
		<?php if ( cs_get_errors() ) : ?>
			<div class="error settings-error">
				<?php cs_print_errors(); ?>
			</div>
		<?php endif; ?>

		<?php if ( $render ) : ?>


			<div id="cs-item-card-wrapper">

				<?php do_action( 'cs_new_subscription_card_top' ); ?>

				<div class="info-wrapper item-section">

					<p style="margin-top: 0;"><?php _e( '<strong>Note: </strong> This tool allows you to create a new subscription record. It will not create a payment profile in your merchant processor. Payment profiles in the merchant processor must be created through your merchant portal. Once created in the merchant portal, details such as transaction ID and billing profile id, can be entered here.', 'cs-recurring' ); ?></p>

					<form id="edit-item-info" method="post" action="<?php echo admin_url( 'edit.php?post_type=download&page=cs-subscriptions' ); ?>">

						<div class="item-info">


							<table class="widefat striped">
								<tbody>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Price and Billing Cycle:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<input type="text" name="initial_amount" placeholder="0.00" value="" style="width: 80px;"/>
											<?php echo _x( 'then', 'Inital subscription amount then billing cycle and amount', 'cs-recurring' ); ?>
											<input type="text" name="recurring_amount" placeholder="0.00" value="" style="width: 80px;"/>
											<select name="period">
												<?php foreach ( $periods as $key => $value ) : ?>
													<option value="<?php echo $key; ?>"><?php echo esc_attr( $value ); ?></option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Times Billed:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<input type="number" min="0" step="1" name="bill_times" value="0"/>
											<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'This refers to the number of times the subscription will be billed before being marked as Completed and payments stopped. Enter 0 if payments continue indefinitely.', 'cs-recurring' ); ?>"></span>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Customer Email:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<p class="cs-recurring-customer-wrap-existing">
												<?php echo CS()->html->customer_dropdown( array( 'name' => 'customer_id', 'class' => 'cs-recurring-customer' ) ); ?>
												<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'Required. Select the customer this subscription belongs to.', 'cs-recurring' ); ?>"></span>
											</p>
											<p class="cs-recurring-customer-wrap-new hidden">
												<input type="text" name="customer_email" value="" class="cs-recurring-customer" placeholder="<?php _e( 'Enter customer email', 'cs-recurring' ); ?>"/>
												<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'Required. Enter the email address of the customer to create a new record.', 'cs-recurring' ); ?>"></span>
											</p>
											<p>
												<?php printf(
													__( '%sSelect existing%s customer or %screate new customer%s', 'cs-recurring' ),
													'<a href="#" class="cs-recurring-select-customer">',
													'</a>',
													'<a href="#" class="cs-recurring-new-customer">',
													'</a>'
												); ?>
											</p>
										</td>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Product:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<?php echo CS()->html->product_dropdown( array( 'name' => 'product_id', 'chosen' => true, 'variations' => true ) ); ?>
											<span class="cs-recurring-price-option-wrap"></span>
											<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'Required. Select the product this subscription grants access to.', 'cs-recurring' ); ?>"></span>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Initial Purchase ID:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<select class="cs-recurring-select-payment">
												<option value="0"><?php _e( 'Create new payment record', 'cs-recurring' ); ?></option>
												<option value="1"><?php _e( 'Enter existing payment ID', 'cs-recurring' ); ?></option>
											</select>
											<input type="number" min="1" name="parent_payment_id" class="cs-recurring-payment-id hidden" value="" style="width: 80px;"/>
											<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'A payment record will be automatically created unless you choose to enter an existing ID. If using an existing payment record, enter the ID here.', 'cs-recurring' ); ?>"></span>
										</td>
									</tr>
									<tr class="cs-recurring-gateway-wrap">
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Gateway:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<select name="gateway">
												<?php foreach( cs_get_payment_gateways() as $key => $gateway ) : ?>
													<option value="<?php echo esc_attr( $key ); ?>"><?php echo $gateway['admin_label']; ?></option>
												<?php endforeach; ?>
											</select>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Profile ID:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<input type="text" name="profile_id" class="cs-sub-profile-id" value="" />
											<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'Required. This is the unique ID of the subscription in the merchant processor, such as PayPal or Stripe.', 'cs-recurring' ); ?>"></span>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Transaction ID:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<input type="text" name="transaction_id" class="cs-sub-transaction-id" value="" />
											<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'Optional. This is the unique ID of the initial transaction inside of the merchant processor, such as PayPal or Stripe.', 'cs-recurring' ); ?>"></span>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Date Created:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<input type="text" name="created" class="cs_datepicker cs-sub-created" value="" />
											<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'Optional. The date this subscription was created.', 'cs-recurring' ); ?>"></span>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Expiration Date:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<input type="text" name="expiration" class="cs_datepicker cs-sub-expiration" value="" />
											<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'Required. The date the subscription expires or the date of the next automatic renewal payment.', 'cs-recurring' ); ?>"></span>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Subscription Status:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<select name="status">
												<option value="pending"><?php _e( 'Pending', 'cs-recurring' ); ?></option>
												<option value="active"><?php _e( 'Active', 'cs-recurring' ); ?></option>
												<option value="cancelled"><?php _e( 'Cancelled', 'cs-recurring' ); ?></option>
												<option value="expired"><?php _e( 'Expired', 'cs-recurring' ); ?></option>
												<option value="trialling"><?php _e( 'Trialling', 'cs-recurring' ); ?></option>
												<option value="failing"><?php _e( 'Failing', 'cs-recurring' ); ?></option>
												<option value="completed"><?php _e( 'Completed', 'cs-recurring' ); ?></option>
											</select>
											<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'Required. Select the status of this subscription.', 'cs-recurring' ); ?>"></span>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div id="item-edit-actions" class="edit-item" style="float:right; margin: 10px 0 20px; display: block;">
							<?php wp_nonce_field( 'cs-recurring-add-subscription', 'cs-recurring-add-subscription-nonce', false, true ); ?>
							<input type="hidden" name="cs_action" class="button button-primary" value="add_subscription"/>
							<input type="submit" name="cs_new_subscription" id="cs_add_subscription" class="button button-primary" value="<?php _e( 'Add Subscription', 'cs-recurring' ); ?>"/>
						</div>

					</form>
				</div>

				<?php do_action( 'cs_new_subscription_card_bottom' ); ?>
			</div>

		<?php endif; ?>

	</div>
	<?php
}

/**
 * Recurring Subscription Details
 * @description Outputs the subscriber details
 * @since       2.4
 *
 */
function cs_recurring_subscription_details() {

	$render = true;

	if ( ! current_user_can( 'view_shop_reports' ) ) {
		cs_set_error( 'cs-no-access', __( 'You are not permitted to view this data.', 'cs-recurring' ) );
		$render = false;
	}

	if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
		cs_set_error( 'cs-invalid_subscription', __( 'Invalid subscription ID Provided.', 'cs-recurring' ) );
		$render = false;
	}

	$sub_id  = (int) $_GET['id'];
	$sub     = new CS_Subscription( $sub_id );

	if ( empty( $sub ) ) {
		cs_set_error( 'cs-invalid_subscription', __( 'Invalid subscription ID Provided.', 'cs-recurring' ) );
		$render = false;
	}

	$tax_rate   = false;
	$tax_amount = false;

	if ( ! empty( $sub->initial_tax_rate ) || ! empty( $sub->recurring_tax_rate ) ) {
		$tax_rate = true;
	}

	if ( ! empty( $sub->initial_tax ) || ! empty( $sub->recurring_tax ) ) {
		$tax_amount = true;
	}

	$currency_code = cs_get_payment_currency_code( $sub->parent_payment_id );

	?>
	<div class="wrap">
		<h2><?php _e( 'Subscription Details', 'cs-recurring' ); ?></h2>
		<?php if ( cs_get_errors() ) : ?>
			<div class="error settings-error">
				<?php cs_print_errors(); ?>
			</div>
		<?php endif; ?>

		<?php if ( $sub && $render ) : ?>

			<div id="cs-item-card-wrapper">

				<?php do_action( 'cs_subscription_card_top', $sub ); ?>

				<div class="info-wrapper item-section">

					<form id="edit-item-info" method="post" action="<?php echo admin_url( 'edit.php?post_type=download&page=cs-subscriptions&id=' . $sub->id ); ?>">

						<div class="item-info">

							<table class="widefat striped">
								<tbody>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Billing Cycle:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<?php
											$frequency = CS_Recurring()->get_pretty_subscription_frequency( $sub->period );
											$billing   = cs_currency_filter( cs_format_amount( $sub->recurring_amount ), $currency_code ) . ' / ' . $frequency;
											$initial   = cs_currency_filter( cs_format_amount( $sub->initial_amount ), $currency_code );
											printf( _x( '%s then %s', 'Inital subscription amount then billing cycle and amount', 'cs-recurring' ), $initial, $billing );
											?>
											<?php if ( $tax_rate || $tax_amount ) { ?>
												<span>&nbsp;&ndash;&nbsp;</span>
												<a class="cs-item-toggle-next-hidden-row" href="#"><?php _ex( 'View Details', 'view billing cycle details on single subscription admin page','cs-recurring' ) ?></a>
											<?php } ?>
										</td>
									</tr>
									<?php if ( $tax_rate || $tax_amount ) { ?>
										<tr><?php // needed in order to maintain alternate row background colors ?></tr>
										<tr class="cs-item-hidden-row" style="display: none;">
											<td colspan="2" style="background: #fff;">
												<?php if ( $tax_rate ) { ?>

													<div style="padding-left: 10px; border-left: 1px solid #e5e5e5;">
														<span><strong><?php _e( 'Tax Rate:', 'cs-recurring' ); ?></strong></span>
														<?php
														$initial_tax_rate   = ! empty( $sub->initial_tax_rate ) && is_numeric( $sub->initial_tax_rate ) ? ( $sub->initial_tax_rate * 100 ) : 0.00;
														$recurring_tax_rate = ! empty( $sub->recurring_tax_rate ) && is_numeric( $sub->recurring_tax_rate ) ? ( $sub->recurring_tax_rate * 100 ) : 0.00;
														printf(
															/* translators: %1$s Initial tax rate. %2$s Billing tax rate and cycle length */
															_x( '%1$s then %2$s', 'cs-recurring' ),
															esc_html( $initial_tax_rate ) . '%',
															esc_html( $recurring_tax_rate . '% / ' . $frequency )
														);
														?>
													</div>

												<?php }
												if ( $tax_amount ) { ?>

													<div style="padding-left: 10px; border-left: 1px solid #e5e5e5;">
														<span><strong><?php _e( 'Tax Amount:', 'cs-recurring' ); ?></strong></span>
														<?php
														printf(
															/* translators: %1$s Initial tax value. %2$s Billing tax value and cycle length */
															_x( '%1$s then %2$s', 'Initial subscription tax value then recurring tax value and billing cycle.', 'cs-recurring' ),
															cs_currency_filter( cs_format_amount( $sub->initial_tax ), $currency_code ),
															cs_currency_filter( cs_format_amount( $sub->recurring_tax ), $currency_code ) . ' / ' . $frequency
														);
														?>
													</div>

												<?php } ?>
											</td>
										</tr>
									<?php } ?>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Times Billed:', 'cs-recurring' ); ?></label>
										</td>
										<td><?php echo $sub->get_times_billed() . ' / ' . ( ( $sub->bill_times == 0 ) ? 'Until Cancelled' : $sub->bill_times ); ?></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Customer:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<?php $subscriber = new CS_Recurring_Subscriber( $sub->customer_id ); ?>
											<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=cs-customers&view=overview&id=' . $subscriber->id ) ); ?>"><?php echo ! empty( $subscriber->name ) ? $subscriber->name : $subscriber->email; ?></a>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Initial Purchase ID:', 'cs-recurring' ); ?></label>
										</td>
										<td><?php echo '<a href="' . add_query_arg( 'id', $sub->parent_payment_id, admin_url( 'edit.php?post_type=download&page=cs-payment-history&view=view-order-details' ) ) . '">' . $sub->parent_payment_id . '</a>'; ?></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Product:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<?php
											$selected = $sub->product_id;
											$download = cs_get_download( $selected );
											if ( ! is_null( $sub->price_id ) && cs_has_variable_prices( $sub->product_id ) ) {
												$selected .= '_' . $sub->price_id;
											}

											// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
											echo CS()->html->product_dropdown(
												array(
													'selected'             => $selected,
													'chosen'               => true,
													'name'                 => 'product_id',
													'class'                => 'cs-sub-product-id',
													'variations'           => true,
													'show_variations_only' => true,
												)
											);

											if ( $download instanceof CS_Download ) :
											?>
											<a href="<?php echo esc_url( add_query_arg( array(
													'post'   => $sub->product_id,
													'action' => 'edit'
												), admin_url( 'post.php' ) ) ); ?>"><?php printf( __( 'View %s', 'cs-recurring' ), cs_get_label_singular() ); ?></a>
											<?php endif; ?>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Payment Method:', 'cs-recurring' ); ?></label>
										</td>
										<td><?php echo cs_get_gateway_admin_label( cs_get_payment_gateway( $sub->parent_payment_id ) ); ?></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Profile ID:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<span class="cs-sub-profile-id">
												<?php echo apply_filters( 'cs_subscription_profile_link_' . $sub->gateway, $sub->profile_id, $sub ); ?>
											</span>
											<input type="text" name="profile_id" class="hidden cs-sub-profile-id" value="<?php echo esc_attr( $sub->profile_id ); ?>" />
											<span>&nbsp;&ndash;&nbsp;</span>
											<a href="#" class="cs-edit-sub-profile-id"><?php _e( 'Edit', 'cs-recurring' ); ?></a>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Transaction ID:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<span class="cs-sub-transaction-id"><?php echo esc_html( apply_filters( 'cs_subscription_details_transaction_id_' . $sub->gateway, $sub->get_transaction_id(), $sub ) ); ?></span>
											<input type="text" name="transaction_id" class="hidden cs-sub-transaction-id" value="<?php echo esc_attr( $sub->get_transaction_id() ); ?>" />
											<span>&nbsp;&ndash;&nbsp;</span>
											<a href="#" class="cs-edit-sub-transaction-id"><?php _e( 'Edit', 'cs-recurring' ); ?></a>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Date Created:', 'cs-recurring' ); ?></label>
										</td>
										<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $sub->created, current_time( 'timestamp' ) ) ); ?></td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell">
												<?php if( 'trialling' == $sub->status ) : ?>
													<?php _e( 'Trialling Until:', 'cs-recurring' ); ?>
												<?php else: ?>
													<?php _e( 'Expiration Date:', 'cs-recurring' ); ?>
												<?php endif; ?>
											</label>
										</td>
										<td>
											<span class="cs-sub-expiration"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $sub->expiration, current_time( 'timestamp' ) ) ); ?></span>
											<input type="text" name="expiration" class="cs_datepicker hidden cs-sub-expiration" value="<?php echo esc_attr( $sub->expiration ); ?>" />
											<span>&nbsp;&ndash;&nbsp;</span>
											<a href="#" class="cs-edit-sub-expiration"><?php _e( 'Edit', 'cs-recurring' ); ?></a>
										</td>
									</tr>
									<tr>
										<td class="row-title">
											<label for="tablecell"><?php _e( 'Subscription Status:', 'cs-recurring' ); ?></label>
										</td>
										<td>
											<select name="status">
												<option value="pending"<?php selected( 'pending', $sub->status ); ?>><?php _e( 'Pending', 'cs-recurring' ); ?></option>
												<option value="active"<?php selected( 'active', $sub->status ); ?>><?php _e( 'Active', 'cs-recurring' ); ?></option>
												<option value="cancelled"<?php selected( 'cancelled', $sub->status ); ?>><?php _e( 'Cancelled', 'cs-recurring' ); ?></option>
												<option value="expired"<?php selected( 'expired', $sub->status ); ?>><?php _e( 'Expired', 'cs-recurring' ); ?></option>
												<option value="trialling"<?php selected( 'trialling', $sub->status ); ?>><?php _e( 'Trialling', 'cs-recurring' ); ?></option>
												<option value="failing"<?php selected( 'failing', $sub->status ); ?>><?php _e( 'Failing', 'cs-recurring' ); ?></option>
												<option value="completed"<?php selected( 'completed', $sub->status ); ?>><?php _e( 'Completed', 'cs-recurring' ); ?></option>
											</select>
											<?php if( $sub->can_reactivate() ) : ?>
												<a class="button" href="<?php echo $sub->get_reactivation_url(); ?>" ><?php _e( 'Reactivate Subscription', 'cs-recurring' ); ?></a>
											<?php endif; ?>
											<?php if( $sub->can_retry() ) : ?>
												<a class="button" href="<?php echo $sub->get_retry_url(); ?>" ><?php _e( 'Retry Renewal', 'cs-recurring' ); ?></a>
											<?php endif; ?>
										</td>
									</tr>
								</tbody>
							</table>
						</div>
						<div id="cs-sub-notices">
							<div class="notice notice-info inline hidden" id="cs-sub-expiration-update-notice"><p><?php _e( 'Changing the expiration date will not affect when renewal payments are processed.', 'cs-recurring' ); ?></p></div>
							<div class="notice notice-info inline hidden" id="cs-sub-product-update-notice"><p><?php _e( 'Changing the product assigned will not automatically adjust any pricing.', 'cs-recurring' ); ?></p></div>
							<div class="notice notice-warning inline hidden" id="cs-sub-profile-id-update-notice"><p><?php _e( 'Changing the profile ID can result in renewals not being processed. Do this with caution.', 'cs-recurring' ); ?></p></div>
						</div>
						<div id="item-edit-actions" class="edit-item" style="float:right; margin: 10px 0 0; display: block;">
							<?php wp_nonce_field( 'cs-recurring-update', 'cs-recurring-update-nonce', false, true ); ?>
							<input type="submit" name="cs_update_subscription" id="cs_update_subscription" class="button button-primary" value="<?php _e( 'Update Subscription', 'cs-recurring' ); ?>"/>
							<input type="hidden" name="sub_id" value="<?php echo absint( $sub->id ); ?>" />
							<?php if( $sub->can_cancel() ) : ?>
								<a class="button button-primary" href="<?php echo $sub->get_cancel_url(); ?>" ><?php _e( 'Cancel Subscription', 'cs-recurring' ); ?></a>
							<?php endif; ?>
							&nbsp;<input type="submit" name="cs_delete_subscription" class="cs-delete-subscription button" value="<?php _e( 'Delete Subscription', 'cs-recurring' ); ?>"/>
						</div>

					</form>
				</div>

				<?php do_action( 'cs_subscription_before_stats', $sub ); ?>

				<div id="item-stats-wrapper" class="item-section" style="margin:25px 0; font-size: 20px;">
					<ul>
						<li>
							<span class="dashicons dashicons-chart-area"></span>
							<?php echo cs_currency_filter( cs_format_amount( $sub->get_lifetime_value() ), $currency_code ); ?>
						</li>
						<?php do_action( 'cs_subscription_stats_list', $sub ); ?>
					</ul>
				</div>

				<?php do_action( 'cs_subscription_before_tables_wrapper', $sub ); ?>

				<div id="item-tables-wrapper" class="item-section">

					<?php do_action( 'cs_subscription_before_tables', $sub ); ?>

					<h3><?php _e( 'Renewal Payments:', 'cs-recurring' ); ?></h3>
					<?php $payments = $sub->get_child_payments(); ?>
					<?php if( 'manual' == $sub->gateway ) : ?>
						<p><strong><?php _e( 'Note:', 'cs-recurring' ); ?></strong> <?php _e( 'subscriptions purchased with the Test Payment gateway will not renew automatically.', 'cs-recurring' ); ?></p>
					<?php endif; ?>
					<table class="wp-list-table widefat striped payments">
						<thead>
						<tr>
							<th><?php _e( 'ID', 'cs-recurring' ); ?></th>
							<th><?php _e( 'Amount', 'cs-recurring' ); ?></th>
							<th><?php _e( 'Date', 'cs-recurring' ); ?></th>
							<th><?php _e( 'Status', 'cs-recurring' ); ?></th>
							<th><?php _e( 'Actions', 'cs-recurring' ); ?></th>
						</tr>
						</thead>
						<tbody>
						<?php if ( ! empty( $payments ) ) : ?>
							<?php foreach ( $payments as $payment ) : ?>
								<tr>
									<td><?php echo $payment->ID; ?></td>
									<td><?php echo cs_currency_filter( cs_format_amount( $payment->total ), $payment->currency ) ?></td>
									<td><?php echo date_i18n( get_option( 'date_format' ), strtotime( $payment->date ) ); ?></td>
									<td><?php echo $payment->status_nicename; ?></td>
									<td>
										<a title="<?php _e( 'View Details for Payment', 'cs-recurring' );
										echo ' ' . $payment->ID; ?>" href="<?php echo admin_url( 'edit.php?post_type=download&page=cs-payment-history&view=view-order-details&id=' . $payment->ID ); ?>">
											<?php _e( 'View Details', 'cs-recurring' ); ?>
										</a>
										<?php do_action( 'cs_subscription_payments_actions', $sub, $payment ); ?>
									</td>
								</tr>
							<?php endforeach; ?>
						<?php else: ?>
							<tr>
								<td colspan="5"><?php _e( 'No Payments Found', 'cs-recurring' ); ?></td>
							</tr>
						<?php endif; ?>
						</tbody>
						<tfoot>
							<tr class="alternate">
								<td colspan="5">
									<form id="cs-sub-add-renewal" method="POST">
										<p><?php _e( 'Use this form to manually record a renewal payment.', 'cs-recurring' ); ?><span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'Note: this does not initiate a charge in your merchant processor. This should only be used for recording a missed payment or one that was manually collected.', 'cs-recurring' ); ?>"></span></p>
										<?php if( cs_use_taxes() ) : ?>
										<p>
											<label>
												<span style="display: inline-block; width: 150px; padding: 3px;"><?php _e( 'Tax:', 'cs-recurring' ); ?></span>
												<input type="text" class="regular-text" style="width: 100px; padding: 3px;" name="tax" value="" placeholder="0.00"/>
											</label>
										</p>
										<?php endif; ?>
										<p>
											<label>
												<span style="display: inline-block; width: 150px; padding: 3px;"><?php _e( 'Total:', 'cs-recurring' ); ?></span>
												<input type="text" class="regular-text" style="width: 100px; padding: 3px;" name="amount" value="" placeholder="0.00"/>
											</label>
										</p>
										<p>
											<label>
												<span style="display: inline-block; width: 150px; padding: 3px;"><?php _e( 'Transaction ID:', 'cs-recurring' ); ?></span>
												<input type="text" class="regular-text" style="width: 100px; padding: 3px;" name="txn_id" value="" placeholder=""/>
											</label>
										</p>
										<?php wp_nonce_field( 'cs-recurring-add-renewal-payment', '_wpnonce', false, true ); ?>
										<input type="hidden" name="sub_id" value="<?php echo absint( $sub->id ); ?>" />
										<input type="hidden" name="cs_action" value="add_renewal_payment" />
										<input type="submit" name="renew_and_add_payment" class="button alignright" style="margin-left: 8px;" value="<?php esc_attr_e( 'Record Payment and Renew Subscription', 'cs-recurring' ); ?>"/>
										<input type="submit" name="add_payment_only" class="button alignright" value="<?php esc_attr_e( 'Record Payment Only', 'cs-recurring' ); ?>"/>
									</form>
								</td>
							</tr>
						</tfoot>
					</table>

					<?php do_action( 'cs_subscription_after_tables', $sub ); ?>

				</div>

				<div id="item-tables-wrapper" class="item-section">

					<?php do_action( 'cs_subscription_before_notes', $sub ); ?>

					<h3><?php _e( 'Notes:', 'cs-recurring' ); ?></h3>
					<?php
					$notes = $sub->get_notes( 1000 );
					if( $notes ) {
						foreach( $notes as $key => $note ) {
							$class = cs_is_odd( $key ) ? ' class="alternate"' : '';
							echo '<p' . $class . ' style="padding: 7px 0 7px 7px">' . stripslashes( $note ) .'</p>';
						}
					}
					?>
					<form id="cs-sub-add-note" method="POST">
						<textarea name="note" class="cs-subscription-note-input" style="width:100%;" rows="8"></textarea>
						<?php wp_nonce_field( 'cs-recurring-add-note', '_wpnonce', false, true ); ?>
						<input type="hidden" name="sub_id" value="<?php echo absint( $sub->id ); ?>" />
						<input type="hidden" name="cs_action" value="add_subscription_note" />
						<p class="submit">
							<input type="submit" name="add_note" class="button alignright" value="<?php esc_attr_e( 'Add Note', 'cs-recurring' ); ?>"/>
						</p>
					</form>
					<?php do_action( 'cs_subscription_after_notes', $sub ); ?>

				</div>

				<?php do_action( 'cs_subscription_card_bottom', $sub ); ?>
			</div>

		<?php endif; ?>

	</div>
	<?php
}

/**
 * Handles subscription update
 *
 * @access      public
 * @since       2.4
 * @return      void
 */
function cs_recurring_process_subscription_update() {

	if( empty( $_POST['sub_id'] ) ) {
		return;
	}

	if( empty( $_POST['cs_update_subscription'] ) ) {
		return;
	}

	if( ! current_user_can( 'edit_shop_payments') ) {
		return;
	}

	if( ! wp_verify_nonce( $_POST['cs-recurring-update-nonce'], 'cs-recurring-update' ) ) {
		wp_die( __( 'Nonce verification failed', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 403 ) );
	}

	$expiration      = date( 'Y-m-d 23:59:59', strtotime( $_POST['expiration'] ) );
	$profile_id      = sanitize_text_field( $_POST['profile_id'] );
	$transaction_id  = sanitize_text_field( $_POST['transaction_id'] );
	$product_id      = sanitize_text_field( $_POST['product_id'] );
	$subscription    = new CS_Subscription( absint( $_POST['sub_id'] ) );
	$status          = sanitize_text_field( $_POST['status'] );

	$product_details = explode( '_', $product_id );
	$product_id      = $product_details[0];
	$has_variations  = cs_has_variable_prices( $product_id );
	if ( $has_variations ) {
		if ( ! isset( $product_details[1] ) ) {
			wp_die( __( 'A variation is required for the selected product', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 401 ) );
		}

		$price_id = $product_details[1];
	}

	$args            = array(
		'status'         => $status,
		'expiration'     => $expiration,
		'profile_id'     => $profile_id,
		'product_id'     => $product_id,
		'transaction_id' => $transaction_id,
	);

	if ( $has_variations && isset( $price_id ) ) {
		$args['price_id'] = $price_id;
	}

	if( 'pending' !== $status && 'active' !== $status ) {
		unset( $args['status'] );
	}

	$subscription->update( $args  );


	switch( $status ) {

		case 'cancelled' :

			$subscription->cancel();
			break;

		case 'expired' :

			$subscription->expire();
			break;

		case 'completed' :

			$subscription->complete();
			break;

		case 'failing' :

			$subscription->failing();
			break;

	}

	wp_redirect( admin_url( 'edit.php?post_type=download&page=cs-subscriptions&cs-message=updated&id=' . $subscription->id ) );
	exit;

}
add_action( 'admin_init', 'cs_recurring_process_subscription_update', 1 );

/**
 * Handles subscription creation
 *
 * @access      public
 * @since       2.5
 * @return      void
 */
function cs_recurring_process_subscription_creation() {

	if( empty( $_POST['cs_new_subscription'] ) ) {
		return;
	}

	if( ! current_user_can( 'edit_shop_payments') ) {
		return;
	}

	if( ! wp_verify_nonce( $_POST['cs-recurring-add-subscription-nonce'], 'cs-recurring-add-subscription' ) ) {
		wp_die( __( 'Nonce verification failed', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 403 ) );
	}

	if( empty( $_POST['expiration'] ) ) {
		wp_die( __( 'Please enter an expiration date', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 403 ) );
	}

	if( empty( $_POST['product_id'] ) ) {
		wp_die( __( 'Please select a product', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 403 ) );
	}

	if( empty( $_POST['initial_amount'] ) ) {
		wp_die( __( 'Please enter an initial amount', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 403 ) );
	}

	if( empty( $_POST['recurring_amount'] ) ) {
		wp_die( __( 'Please enter a recurring amount', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 403 ) );
	}

	if( ! empty( $_POST['created'] ) ) {
		$created_date = date( 'Y-m-d ' . date( 'H:i:s', current_time( 'timestamp' ) ), strtotime( $_POST['created'], current_time( 'timestamp' ) ) );
	} else {
		$created_date = date( 'Y-m-d H:i:s',current_time( 'timestamp' ) );
	}

	if( ! empty( $_POST['customer_id'] ) ) {

		$customer    = new CS_Recurring_Subscriber( absint( $_POST['customer_id'] ) );
		$customer_id = $customer->id;
		$email       = $customer->email;

	} else {

		$email       = sanitize_email( $_POST['customer_email'] );
		$user        = get_user_by( 'email', $email );
		$user_id     = $user ? $user->ID : 0;
		$customer    = new CS_Recurring_Subscriber;
		$customer_id = $customer->create( array( 'email' => $email, 'user_id' => $user_id ) );

	}

	$customer_id = absint( $customer_id );

	if( ! empty( $_POST['parent_payment_id'] ) ) {

		$payment_id = absint( $_POST['parent_payment_id'] );
		$payment    = cs_get_payment( $payment_id );

		if ( ! $payment ) {
			/* translators: the existing payment ID. */
			wp_die( sprintf( esc_html__( 'Payment %s does not exist.', 'cs-recurring' ), absint( $payment_id ) ), esc_html__( 'Error', 'cs-recurring' ), array( 'response' => 400 ) );
		}
	} else {

		$options = array();
		if ( ! empty( $_POST['cs_price_option'] ) ) {
			$options['price_id'] = absint( $_POST['cs_price_option'] );
		}

		$payment = new CS_Payment();
		$payment->add_download( absint( $_POST['product_id'] ), $options );
		$payment->customer_id = $customer_id;
		$payment->email       = $email;
		$payment->user_id     = $customer->user_id;
		$payment->gateway     = sanitize_text_field( $_POST['gateway'] );
		$payment->total       = cs_sanitize_amount( sanitize_text_field( $_POST['initial_amount'] ) );
		$payment->date        = $created_date;
		$payment->status      = 'pending';
		$payment->save();
		$payment->status = 'complete';
		$payment->save();
	}

	$args = array(
		'expiration'        => date( 'Y-m-d 23:59:59', strtotime( $_POST['expiration'], current_time( 'timestamp' ) ) ),
		'created'           => $created_date,
		'status'            => sanitize_text_field( $_POST['status'] ),
		'profile_id'        => sanitize_text_field( $_POST['profile_id'] ),
		'transaction_id'    => sanitize_text_field( $_POST['transaction_id'] ),
		'initial_amount'    => cs_sanitize_amount( sanitize_text_field( $_POST['initial_amount'] ) ),
		'recurring_amount'  => cs_sanitize_amount( sanitize_text_field( $_POST['recurring_amount'] ) ),
		'bill_times'        => absint( $_POST['bill_times'] ),
		'period'            => sanitize_text_field( $_POST['period'] ),
		'parent_payment_id' => $payment->ID,
		'product_id'        => absint( $_POST['product_id'] ),
		'price_id'          => absint( $_POST['cs_price_option'] ),
		'customer_id'       => $customer_id
	);

	$subscription = new CS_Subscription;
	$subscription->create( $args );

	if( 'trialling' === $subscription->status ) {
		$customer->add_meta( 'cs_recurring_trials', $subscription->product_id );
	}

	$payment->update_meta( '_cs_subscription_payment', true );

	wp_redirect( admin_url( 'edit.php?post_type=download&page=cs-subscriptions&cs-message=updated&id=' . $subscription->id ) );
	exit;

}
add_action( 'cs_add_subscription', 'cs_recurring_process_subscription_creation', 1 );

/**
 * Handles subscription cancellation
 *
 * @access      public
 * @since       2.4
 * @return      void
 */
function cs_recurring_process_subscription_cancel() {

	if( empty( $_POST['sub_id'] ) ) {
		return;
	}

	if( empty( $_POST['cs_cancel_subscription'] ) ) {
		return;
	}

	if( ! current_user_can( 'edit_shop_payments') ) {
		return;
	}

	if( ! wp_verify_nonce( $_POST['_wpnonce'], 'cs-recurring-cancel' ) ) {
		wp_die( __( 'Nonce verification failed', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 403 ) );
	}

	$subscription    = new CS_Subscription( absint( $_POST['sub_id'] ) );
	$subscription->cancel();

	wp_redirect( admin_url( 'edit.php?post_type=download&page=cs-subscriptions&cs-message=cancelled&id=' . $subscription->id ) );
	exit;

}
add_action( 'admin_init', 'cs_recurring_process_subscription_cancel', 1 );


/**
 * Handles adding a manual renewal payment
 *
 * @access      public
 * @since       2.4
 * @return      void
 */
function cs_recurring_process_add_renewal_payment() {

	if( empty( $_POST['sub_id'] ) ) {
		return;
	}

	if( ! current_user_can( 'edit_shop_payments') ) {
		return;
	}

	if( ! wp_verify_nonce( $_POST['_wpnonce'], 'cs-recurring-add-renewal-payment' ) ) {
		wp_die( __( 'Nonce verification failed', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 403 ) );
	}

	$amount  = isset( $_POST['amount'] ) ? cs_sanitize_amount( $_POST['amount'] ) : '0.00';
	$tax     = isset( $_POST['tax'] ) ? cs_sanitize_amount( $_POST['tax'] ) : 0;
	$txn_id  = isset( $_POST['txn_id'] ) ? sanitize_text_field( $_POST['txn_id'] ) : md5( strtotime( 'NOW' ) );
	$sub     = new CS_Subscription( absint( $_POST['sub_id'] ) );

	$payment_id = $sub->add_payment( array(
		'amount'         => $amount,
		'transaction_id' => $txn_id,
		'tax'            => $tax
	) );

	if( ! empty( $_POST['renew_and_add_payment'] ) ) {
		$sub->renew( $payment_id );
	}

	if( $payment_id ) {
		$message = 'renewal-added';
	} else {
		$message = 'renewal-not-added';
	}

	wp_redirect( admin_url( 'edit.php?post_type=download&page=cs-subscriptions&cs-message=' . $message . '&id=' . $sub->id ) );
	exit;

}
add_action( 'cs_add_renewal_payment', 'cs_recurring_process_add_renewal_payment', 1 );


/**
 * Handles retrying a renewal payment for a failing subscription
 *
 * @access      public
 * @since       2.8
 * @return      void
 */
function cs_recurring_process_renewal_charge_retry() {

	if( empty( $_GET['sub_id'] ) ) {
		return;
	}

	if( ! current_user_can( 'edit_shop_payments') ) {
		return;
	}

	if( ! wp_verify_nonce( $_GET['_wpnonce'], 'cs-recurring-retry' ) ) {
		wp_die( __( 'Nonce verification failed', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 403 ) );
	}

	$sub = new CS_Subscription( absint( $_GET['sub_id'] ) );

	if( ! $sub->can_retry() ) {
		wp_die( __( 'This subscription does not support being retried.', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 403 ) );
	}

	$result = $sub->retry();

	if( $result && ! is_wp_error( $result ) ) {
		$message = 'retry-success';
	} else {
		$message = 'retry-failed&error-message=' . urlencode( $result->get_error_message() );
	}

	wp_redirect( admin_url( 'edit.php?post_type=download&page=cs-subscriptions&cs-message=' . $message . '&id=' . $sub->id ) );
	exit;

}
add_action( 'cs_retry_subscription', 'cs_recurring_process_renewal_charge_retry', 1 );

/**
 * Handles adding a subscription note
 *
 * @access      public
 * @since       2.7
 * @return      void
 */
function cs_recurring_process_add_subscription_note() {

	if( empty( $_POST['sub_id'] ) ) {
		return;
	}

	if( ! current_user_can( 'edit_shop_payments') ) {
		return;
	}

	if( ! wp_verify_nonce( $_POST['_wpnonce'], 'cs-recurring-add-note' ) ) {
		wp_die( __( 'Nonce verification failed', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 403 ) );
	}

	$note    = trim( sanitize_text_field( $_POST['note'] ) );
	$sub     = new CS_Subscription( absint( $_POST['sub_id'] ) );
	$added   = $sub->add_note( $note );

	if( $added ) {
		$message = 'subscription-note-added';
	} else {
		$message = 'subscription-note-not-added';
	}

	wp_redirect( admin_url( 'edit.php?post_type=download&page=cs-subscriptions&cs-message=' . $message . '&id=' . $sub->id ) );
	exit;

}
add_action( 'cs_add_subscription_note', 'cs_recurring_process_add_subscription_note', 1 );

/**
 * Handles subscription deletion
 *
 * @access      public
 * @since       2.4
 * @return      void
 */
function cs_recurring_process_subscription_deletion() {

	if( empty( $_POST['sub_id'] ) ) {
		return;
	}

	if( empty( $_POST['cs_delete_subscription'] ) ) {
		return;
	}

	if( ! current_user_can( 'edit_shop_payments') ) {
		return;
	}

	if( ! wp_verify_nonce( $_POST['cs-recurring-update-nonce'], 'cs-recurring-update' ) ) {
		wp_die( __( 'Nonce verification failed', 'cs-recurring' ), __( 'Error', 'cs-recurring' ), array( 'response' => 403 ) );
	}

	$subscription = new CS_Subscription( absint( $_POST['sub_id'] ) );

	$payment = new CS_Payment( $subscription->parent_payment_id );
	if ( $payment ) {
		$payment->delete_meta( '_cs_subscription_payment' );
	}

	// Delete subscription from list of trials customer has used
	$subscription->customer->delete_meta( 'cs_recurring_trials', $subscription->product_id );

	$subscription->delete();

	wp_redirect( admin_url( 'edit.php?post_type=download&page=cs-subscriptions&cs-message=deleted' ) );
	exit;

}
add_action( 'admin_init', 'cs_recurring_process_subscription_deletion', 2 );

/**
 * Update customer ID on subscriptions when payment's customer ID is updated
 *
 * @access      public
 * @since       2.4.15
 * @return      void
 */
function cs_recurring_update_customer_id_on_payment_update( $meta_id, $object_id, $meta_key, $meta_value ) {

	if( '_cs_payment_customer_id' == $meta_key ) {

		$subs_db = new CS_Subscriptions_DB;
		$subs    = $subs_db->get_subscriptions( array( 'parent_payment_id' => $object_id ) );
		if( $subs ) {

			foreach( $subs as $sub ) {

				$sub->update( array( 'customer_id' => $meta_value ) );

			}

		}

	}

}
add_action( 'updated_postmeta', 'cs_recurring_update_customer_id_on_payment_update', 10, 4 );

/**
 * Find all subscription IDs
 *
 * @since  2.4
 * @param  array $items Current items to remove from the reset
 * @return array        The items with all subscriptions
 */
function cs_recurring_reset_delete_subscriptions( $items ) {

	$db = new CS_Subscriptions_DB;

	$args = array(
		'number'  => -1,
		'orderby' => 'id',
		'order'   => 'ASC',
	);

	$subscriptions = $db->get_subscriptions( $args );

	foreach ( $subscriptions as $subscription ) {
		$items[] = array(
			'id'   => (int) $subscription->id,
			'type' => 'cs_subscription',
		);
	}

	return $items;
}
add_filter( 'cs_reset_store_items', 'cs_recurring_reset_delete_subscriptions', 10, 1 );

/**
 * Isolate the subscription items during the reset process
 *
 * @since  2.4
 * @param  stirng $type The type of item to remove from the initial findings
 * @param  array  $item The item to remove
 * @return string       The determine item type
 */
function cs_recurring_reset_recurring_type( $type, $item ) {

	if ( 'cs_subscription' === $item['type'] ) {
		$type = $item['type'];
	}

	return $type;

}
add_filter( 'cs_reset_item_type', 'cs_recurring_reset_recurring_type', 10, 2 );

/**
 * Add an SQL item to the reset process for the given subscription IDs
 *
 * @since  2.4
 * @param  array  $sql An Array of SQL statements to run
 * @param  string $ids The IDs to remove for the given item type
 * @return array       Returns the array of SQL statements with subscription statement added
 */
function cs_recurring_reset_queries( $sql, $ids ) {

	global $wpdb;
	$table = $wpdb->prefix . 'cs_subscriptions';
	$sql[] = "DELETE FROM $table WHERE id IN ($ids)";

	return $sql;

}
add_filter( 'cs_reset_add_queries_cs_subscription', 'cs_recurring_reset_queries', 10, 2 );
