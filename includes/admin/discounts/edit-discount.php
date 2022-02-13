<?php
/**
 * Edit Discount Page
 *
 * @package     CS
 * @subpackage  Admin/Discounts
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// Bail if no discount passed
if ( ! isset( $_GET['discount'] ) || ! is_numeric( $_GET['discount'] ) ) {
	wp_die( __( 'Something went wrong.', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 400 ) );
}

// Load discount
$discount_id = absint( $_GET['discount'] );

/** @var CS_Discount */
$discount = cs_get_discount( $discount_id );

// Bail if discount does not exist
if ( empty( $discount ) ) {
	wp_die( __( 'Something went wrong.', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 400 ) );
}

// Setup discount vars
$product_requirements = $discount->get_product_reqs();
$excluded_products    = $discount->get_excluded_products();
$condition            = $discount->get_product_condition();
$single_use           = $discount->get_once_per_customer();
$type                 = $discount->get_type();
$notes                = cs_get_discount_notes( $discount->id );

// Show/Hide
$flat_display         = ( 'flat'    === $type          ) ? '' : ' style="display:none;"';
$percent_display      = ( 'percent' === $type          ) ? '' : ' style="display:none;"';
$no_notes_display     =   empty( $notes                ) ? '' : ' style="display:none;"';
$condition_display    = ! empty( $product_requirements ) ? '' : ' style="display:none;"';

// Dates & times
$discount_start_date  = cs_get_cs_timezone_equivalent_date_from_utc( CS()->utils->date( $discount->start_date, 'utc' ) );
$discount_end_date    = cs_get_cs_timezone_equivalent_date_from_utc( CS()->utils->date( $discount->end_date, 'utc' ) );
$start_date           = $discount_start_date->format( 'Y-m-d' );
$start_hour           = $discount_start_date->format( 'H' );
$start_minute         = $discount_start_date->format( 'i' );
$end_date             = $discount_end_date->format( 'Y-m-d' );
$end_hour             = $discount_end_date->format( 'H' );
$end_minute           = $discount_end_date->format( 'i' );
$hours                = cs_get_hour_values();
$minutes              = cs_get_minute_values();
?>
<div class="wrap">
	<h1><?php _e( 'Edit Discount', 'commercestore' ); ?></h1>

	<hr class="wp-header-end">

	<form id="cs-edit-discount" action="" method="post">
		<?php do_action( 'cs_edit_discount_form_top', $discount->id, $discount ); ?>

		<table class="form-table">
			<tbody>

				<?php do_action( 'cs_edit_discount_form_before_name', $discount->id, $discount ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-name"><?php _e( 'Name', 'commercestore' ); ?></label>
					</th>
					<td>
						<input name="name" required="required" id="cs-name" type="text" value="<?php echo esc_attr( stripslashes( $discount->name ) ); ?>" placeholder="<?php esc_html_e( 'Summer Sale', 'commercestore' ); ?>" />
						<p class="description"><?php _e( 'The name of this discount. Customers will see this on checkout.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_edit_discount_form_before_code', $discount->id, $discount ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-code"><?php _e( 'Code', 'commercestore' ); ?></label>
					</th>
					<td>
						<input type="text" required="required" id="cs-code" name="code" value="<?php echo esc_attr( $discount->code ); ?>" pattern="[a-zA-Z0-9-_]+" class="code" placeholder="<?php esc_html_e( '10PERCENT', 'commercestore' ); ?>" />
						<p class="description"><?php _e( 'The code customers will enter to apply this discount. Only alphanumeric characters are allowed.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_edit_discount_form_before_type', $discount->id, $discount ); ?>

				<?php do_action( 'cs_edit_discount_form_before_amount', $discount->id, $discount ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-amount"><?php _e( 'Amount', 'commercestore' ); ?></label>
					</th>
					<td>
						<span class="cs-amount-type-wrapper">
							<input type="text" required="required" class="cs-price-field" id="cs-amount" name="amount" value="<?php echo esc_attr( cs_format_amount( $discount->amount ) ); ?>" placeholder="<?php esc_html_e( '10.00', 'commercestore' ); ?>" />
							<label for="cs-amount-type" class="screen-reader-text"><?php esc_html_e( 'Amount Type', 'commercestore' ); ?></label>
							<select name="amount_type" id="cs-amount-type">
								<option value="percent" <?php selected( $type, 'percent' ); ?>>%</option>
								<option value="flat"<?php selected( $type, 'flat' ); ?>><?php echo esc_html( cs_currency_symbol() ); ?></option>
							</select>
						</span>
						<p class="description"><?php _e( 'The amount as a percentage or flat rate. Cannot be left blank.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_edit_discount_form_before_products', $discount->id, $discount ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs_products"><?php printf( __( '%s Requirements', 'commercestore' ), cs_get_label_singular() ); ?></label>
					</th>
					<td>
						<?php echo CS()->html->product_dropdown( array(
							'name'        => 'product_reqs[]',
							'id'          => 'cs_products',
							'selected'    => $product_requirements,
							'multiple'    => true,
							'chosen'      => true,
							'placeholder' => sprintf( __( 'Select %s', 'commercestore' ), cs_get_label_plural() )
						) ); ?>
						<div id="cs-discount-product-conditions"<?php echo $condition_display; ?>>
							<p>
								<select id="cs-product-condition" name="product_condition">
									<option value="all"<?php selected( 'all', $condition ); ?>><?php printf( __( 'Cart must contain all selected %s', 'commercestore' ), cs_get_label_plural() ); ?></option>
									<option value="any"<?php selected( 'any', $condition ); ?>><?php printf( __( 'Cart needs one or more of the selected %s', 'commercestore' ), cs_get_label_plural() ); ?></option>
								</select>
							</p>
							<p>
								<label>
									<input type="radio" class="tog" name="scope" value="global"<?php checked( 'global', $discount->scope ); ?>/>
									<?php _e( 'Apply discount to entire purchase.', 'commercestore' ); ?>
								</label><br/>
								<label>
									<input type="radio" class="tog" name="scope" value="not_global"<?php checked( 'not_global', $discount->scope ); ?>/>
									<?php printf( __( 'Apply discount only to selected %s.', 'commercestore' ), cs_get_label_plural() ); ?>
								</label>
							</p>
						</div>
						<p class="description"><?php printf( __( '%s this discount can only be applied to. Leave blank for any.', 'commercestore' ), cs_get_label_plural() ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_edit_discount_form_before_excluded_products', $discount->id, $discount ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-excluded-products"><?php printf( __( 'Excluded %s', 'commercestore' ), cs_get_label_plural() ); ?></label>
					</th>
					<td>
						<?php echo CS()->html->product_dropdown( array(
							'name'        => 'excluded_products[]',
							'id'          => 'excluded_products',
							'selected'    => $excluded_products,
							'multiple'    => true,
							'chosen'      => true,
							'placeholder' => sprintf( __( 'Select %s', 'commercestore' ), cs_get_label_plural() )
						) ); ?>
						<p class="description"><?php printf( __( '%s this discount cannot be applied to. Leave blank for none.', 'commercestore' ), cs_get_label_plural() ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_edit_discount_form_before_start', $discount->id, $discount ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-start"><?php _e( 'Start date', 'commercestore' ); ?></label>
					</th>
					<td class="cs-discount-datetime">
						<input name="start_date" id="cs-start" type="text" value="<?php echo esc_attr( false !== $discount->start_date ? $start_date : '' ); ?>" class="cs_datepicker" data-format="<?php echo esc_attr( cs_get_date_picker_format() ); ?>" placeholder="<?php echo esc_attr( cs_get_date_picker_format() ); ?>" />

						<label class="screen-reader-text" for="start-date-hour">
							<?php esc_html_e( 'Start Date Hour', 'commercestore' ); ?>
						</label>
						<input type="number" min="0" max="24" step="1" name="start_date_hour" id="start-date-hour" value="<?php echo esc_attr( false !== $discount->start_date ? $start_hour : '' ); ?>" placeholder="00" />
						:

						<label class="screen-reader-text" for="start-date-minute">
							<?php esc_html_e( 'Start Date Minute', 'commercestore' ); ?>
						</label>
						<input type="number" min="0" max="59" step="1" name="start_date_minute" id="start-date-minute" value="<?php echo esc_attr( false !== $discount->start_date ? $start_minute : '' ); ?>" placeholder="00" />

						<?php echo esc_html( ' (' . cs_get_timezone_abbr() . ')' ); ?>
						<p class="description"><?php _e( 'Pick the date and time this discount will start on. Leave blank for no start date.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_edit_discount_form_before_expiration', $discount->id, $discount ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-expiration"><?php _e( 'Expiration date', 'commercestore' ); ?></label>
					</th>
					<td class="cs-discount-datetime">
						<input name="end_date" id="cs-expiration" type="text" value="<?php echo esc_attr( false !== $discount->end_date ? $end_date : '' ); ?>"  class="cs_datepicker" data-format="<?php echo esc_attr( cs_get_date_picker_format() ); ?>" placeholder="<?php echo esc_attr( cs_get_date_picker_format() ); ?>" />

						<label class="screen-reader-text" for="end-date-hour">
							<?php esc_html_e( 'Expiration Date Hour', 'commercestore' ); ?>
						</label>
						<input type="number" min="0" max="24" step="1" name="end_date_hour" id="end-date-hour" value="<?php echo esc_attr( false !== $discount->end_date ? $end_hour : '' ); ?>" placeholder="23" />
						:

						<label class="screen-reader-text" for="end-date-minute">
							<?php esc_html_e( 'Expiration Date Minute', 'commercestore' ); ?>
						</label>
						<input type="number" min="0" max="59" step="1" name="end_date_minute" id="end-date-minute" value="<?php echo esc_attr( false !== $discount->end_date ? $end_minute : '' ); ?>" placeholder="59" />

						<?php echo esc_html( ' (' . cs_get_timezone_abbr() . ')' ); ?>
						<p class="description"><?php _e( 'Pick the date and time this discount will expire on. Leave blank to never expire.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_edit_discount_form_before_min_cart_amount', $discount->id, $discount ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-min-cart-amount"><?php _e( 'Minimum Amount', 'commercestore' ); ?></label>
					</th>
					<td>
						<input type="text" id="cs-min-cart-amount" name="min_charge_amount" value="<?php echo esc_attr( cs_format_amount( $discount->min_charge_amount ) ); ?>" placeholder="<?php esc_html_e( 'No minimum', 'commercestore' ); ?>" />
						<p class="description"><?php _e( 'The minimum subtotal of item prices in a cart before this discount may be applied.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_edit_discount_form_before_max_uses', $discount->id, $discount ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-max-uses"><?php _e( 'Max Uses', 'commercestore' ); ?></label>
					</th>
					<td>
						<input type="text" id="cs-max-uses" name="max_uses" value="<?php echo esc_attr( $discount->max_uses ); ?>" placeholder="<?php esc_html_e( 'Unlimited', 'commercestore' ); ?>" />
						<p class="description"><?php _e( 'The maximum number of times this discount can be used.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_edit_discount_form_before_use_once', $discount->id, $discount ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-use-once"><?php _e( 'Use Once Per Customer', 'commercestore' ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="cs-use-once" name="once_per_customer" value="1"<?php checked( true, $single_use ); ?>/>
						<span class="description"><?php _e( 'Prevent customers from using this discount more than once.', 'commercestore' ); ?></span>
					</td>
				</tr>

				<?php do_action( 'cs_edit_discount_form_before_status', $discount->id, $discount ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-status"><?php _e( 'Status', 'commercestore' ); ?></label>
					</th>
					<td>
						<select name="status" id="cs-status">
						<option value="active" <?php selected( $discount->status, 'active' ); ?>><?php _e( 'Active', 'commercestore' ); ?></option>
						<option value="inactive"<?php selected( $discount->status, 'inactive' ); ?>><?php _e( 'Inactive', 'commercestore' ); ?></option>
						</select>
						<p class="description"><?php _e( 'The status of this discount code.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_edit_discount_form_before_notes', $discount->id, $discount ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="notes"><?php _e( 'Discount Notes', 'commercestore' ); ?></label>
					</th>
					<td>
						<div class="cs-notes-wrapper">
							<?php echo cs_admin_get_notes_html( $notes ); ?>
							<?php echo cs_admin_get_new_note_form( $discount->id, 'discount' ); ?>
						</div>
					</td>
				</tr>
			</tbody>
		</table>

		<?php do_action( 'cs_edit_discount_form_bottom', $discount->id, $discount ); ?>

		<p class="submit">
			<input type="hidden" name="type" value="discount" />
			<input type="hidden" name="cs-action" value="edit_discount" />
			<input type="hidden" name="discount-id" value="<?php echo esc_attr( $discount->id ); ?>" />
			<input type="hidden" name="cs-redirect" value="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=cs-discounts&cs-action=edit_discount&discount=' . $discount->id ) ); ?>" />
			<input type="hidden" name="cs-discount-nonce" value="<?php echo wp_create_nonce( 'cs_discount_nonce' ); ?>" />
			<input type="submit" value="<?php _e( 'Update Discount Code', 'commercestore' ); ?>" class="button-primary" />
		</p>
	</form>
</div>
