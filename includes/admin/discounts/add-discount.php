<?php
/**
 * Add Discount Page
 *
 * @package     CS
 * @subpackage  Admin/Discounts
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Add New Discount', 'commercestore' ); ?></h1>

	<hr class="wp-header-end">

	<form id="cs-add-discount" action="" method="post">

		<?php do_action( 'cs_add_discount_form_top' ); ?>

		<table class="form-table">
			<tbody>

				<?php do_action( 'cs_add_discount_form_before_name' ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-name"><?php esc_html_e( 'Name', 'commercestore' ); ?></label>
					</th>
					<td>
						<input name="name" required="required" id="cs-name" type="text" value="" placeholder="<?php esc_html_e( 'Summer Sale', 'commercestore' ); ?>" />
						<p class="description"><?php esc_html_e( 'The name of this discount. Customers will see this on checkout.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_add_discount_form_before_code' ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-code"><?php esc_html_e( 'Code', 'commercestore' ); ?></label>
					</th>
					<td>
						<input type="text" required="required" id="cs-code" name="code" class="code" value="" pattern="[a-zA-Z0-9-_]+" placeholder="<?php esc_html_e( '10PERCENT', 'commercestore' ); ?>" />
						<p class="description"><?php esc_html_e( 'The code customers will enter to apply this discount. Only alphanumeric characters are allowed.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_add_discount_form_before_type' ); ?>

				<?php do_action( 'cs_add_discount_form_before_amount' ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-amount"><?php esc_html_e( 'Amount', 'commercestore' ); ?></label>
					</th>
					<td>
						<span class="cs-amount-type-wrapper">
							<input type="text" required="required" class="cs-price-field" id="cs-amount" name="amount" value="" placeholder="<?php esc_html_e( '10.00', 'commercestore' ); ?>"/>
							<label for="cs-amount-type" class="screen-reader-text"><?php esc_html_e( 'Amount Type', 'commercestore' ); ?></label>
							<select name="amount_type" id="cs-amount-type">
								<option value="percent">%</option>
								<option value="flat"><?php echo esc_html( cs_currency_symbol() ); ?></option>
							</select>
						</span>
						<p class="description"><?php esc_html_e( 'The amount as a percentage or flat rate. Cannot be left blank.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_add_discount_form_before_products' ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs_products"><?php printf( esc_html__( '%s Requirements', 'commercestore' ), cs_get_label_singular() ); ?></label>
					</th>
					<td>
						<?php echo CS()->html->product_dropdown( array(
							'name'        => 'product_reqs[]',
							'id'          => 'cs_products',
							'selected'    => array(),
							'multiple'    => true,
							'chosen'      => true,
							'placeholder' => sprintf( esc_html__( 'Select %s', 'commercestore' ), esc_html( cs_get_label_plural() ) ),
						) ); // WPCS: XSS ok. ?>
						<div id="cs-discount-product-conditions" style="display:none;">
							<p>
								<select id="cs-product-condition" name="product_condition">
									<option value="all"><?php printf( esc_html__( 'Cart must contain all selected %s', 'commercestore' ), esc_html( cs_get_label_plural() ) ); ?></option>
									<option value="any"><?php printf( esc_html__( 'Cart needs one or more of the selected %s', 'commercestore' ), esc_html( cs_get_label_plural() ) ); ?></option>
								</select>
							</p>
							<p>
								<label>
									<input type="radio" class="tog" name="scope" value="global" checked="checked"/>
									<?php esc_html_e( 'Apply discount to entire purchase.', 'commercestore' ); ?>
								</label><br/>
								<label>
									<input type="radio" class="tog" name="scope" value="not_global"/>
									<?php printf( esc_html__( 'Apply discount only to selected %s.', 'commercestore' ), esc_html( cs_get_label_plural() ) ); ?>
								</label>
							</p>
						</div>
						<p class="description"><?php printf( esc_html__( '%s this discount can only be applied to. Leave blank for any.', 'commercestore' ), esc_html( cs_get_label_plural() ) ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_add_discount_form_before_excluded_products' ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-excluded-products"><?php printf( esc_html__( 'Excluded %s', 'commercestore' ), esc_html( cs_get_label_plural() ) ); ?></label>
					</th>
					<td>
						<?php echo CS()->html->product_dropdown( array(
							'name'        => 'excluded_products[]',
							'id'          => 'excluded_products',
							'selected'    => array(),
							'multiple'    => true,
							'chosen'      => true,
							'placeholder' => sprintf( esc_html__( 'Select %s', 'commercestore' ), esc_html( cs_get_label_plural() ) ),
						) ); // WPCS: XSS ok. ?>
						<p class="description"><?php printf( esc_html__( '%s this discount cannot be applied to. Leave blank for none.', 'commercestore' ), esc_html( cs_get_label_plural() ) ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_add_discount_form_before_start' ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-start"><?php esc_html_e( 'Start date', 'commercestore' ); ?></label>
					</th>
					<td class="cs-discount-datetime">
						<input name="start_date" id="cs-start" type="text" value="" class="cs_datepicker" data-format="<?php echo esc_attr( cs_get_date_picker_format() ); ?>" placeholder="<?php echo esc_attr( cs_get_date_picker_format() ); ?>" />

						<label class="screen-reader-text" for="start-date-hour">
							<?php esc_html_e( 'Start Date Hour', 'commercestore' ); ?>
						</label>
						<input type="number" min="0" max="24" step="1" name="start_date_hour" id="start-date-hour" placeholder="00" />
						:

						<label class="screen-reader-text" for="start-date-minute">
							<?php esc_html_e( 'Start Date Minute', 'commercestore' ); ?>
						</label>
						<input type="number" min="0" max="59" step="1" name="start_date_minute" id="start-date-minute" placeholder="00" />

						<?php echo esc_html( ' (' . cs_get_timezone_abbr() . ')' ); ?>
						<p class="description"><?php esc_html_e( 'Pick the date and time this discount will start on. Leave blank for no start date.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_add_discount_form_before_expiration' ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-expiration"><?php esc_html_e( 'Expiration date', 'commercestore' ); ?></label>
					</th>
					<td class="cs-discount-datetime">
						<input name="end_date" id="cs-expiration" type="text" class="cs_datepicker" data-format="<?php echo esc_attr( cs_get_date_picker_format() ); ?>" placeholder="<?php echo esc_attr( cs_get_date_picker_format() ); ?>" />

						<label class="screen-reader-text" for="end-date-hour">
							<?php esc_html_e( 'Expiration Date Hour', 'commercestore' ); ?>
						</label>
						<input type="number" min="0" max="24" step="1" name="end_date_hour" id="end-date-hour" placeholder="23" />
						:

						<label class="screen-reader-text" for="end-date-minute">
							<?php esc_html_e( 'Expiration Date Minute', 'commercestore' ); ?>
						</label>
						<input type="number" min="0" max="59" step="1" name="end_date_minute" id="end-date-minute" placeholder="59" />

						<?php echo esc_html( ' (' . cs_get_timezone_abbr() . ')' ); ?>
						<p class="description"><?php esc_html_e( 'Pick the date and time this discount will expire on. Leave blank to never expire.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_add_discount_form_before_min_cart_amount' ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-min-cart-amount"><?php esc_html_e( 'Minimum Amount', 'commercestore' ); ?></label>
					</th>
					<td>
						<input type="text" id="cs-min-cart-amount" name="min_charge_amount" value="" placeholder="<?php esc_html_e( 'No minimum', 'commercestore' ); ?>" />
						<p class="description"><?php esc_html_e( 'The minimum subtotal of item prices in a cart before this discount may be applied.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_add_discount_form_before_max_uses' ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-max-uses"><?php esc_html_e( 'Max Uses', 'commercestore' ); ?></label>
					</th>
					<td>
						<input type="text" id="cs-max-uses" name="max_uses" value="" placeholder="<?php esc_html_e( 'Unlimited', 'commercestore' ); ?>" />
						<p class="description"><?php esc_html_e( 'The maximum number of times this discount can be used.', 'commercestore' ); ?></p>
					</td>
				</tr>

				<?php do_action( 'cs_add_discount_form_before_use_once' ); ?>

				<tr>
					<th scope="row" valign="top">
						<label for="cs-use-once"><?php esc_html_e( 'Use Once Per Customer', 'commercestore' ); ?></label>
					</th>
					<td>
						<input type="checkbox" id="cs-use-once" name="once_per_customer" value="1"/>
						<span class="description"><?php esc_html_e( 'Prevent customers from using this discount more than once.', 'commercestore' ); ?></span>
					</td>
				</tr>
				
				<?php
				/**
				 * Action after "Use Once Per Customer" checkbox.
				 *
				 * @since 3.0
				 */
				?>
				<?php do_action( 'cs_add_discount_form_after_use_once' ); ?>
				
			</tbody>
		</table>

		<?php do_action( 'cs_add_discount_form_bottom' ); ?>

		<p class="submit">
			<input type="hidden" name="type" value="discount" />
			<input type="hidden" name="cs-action" value="add_discount"/>
			<input type="hidden" name="cs-redirect" value="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=cs-discounts' ) ); ?>"/>
			<input type="hidden" name="cs-discount-nonce" value="<?php echo wp_create_nonce( 'cs_discount_nonce' ); // WPCS: XSS ok. ?>"/>
			<input type="submit" value="<?php esc_html_e( 'Add Discount Code', 'commercestore' ); ?>" class="button-primary"/>
		</p>
	</form>
</div>
