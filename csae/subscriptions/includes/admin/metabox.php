<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
|--------------------------------------------------------------------------
| Variable Prices
|--------------------------------------------------------------------------
*/


/**
 * Meta box table header
 *
 * @access      public
 * @since       1.0
 * @return      void
 */
function cs_recurring_metabox_head( $download_id ) {
	?>
	<th><?php _e( 'Recurring', 'cs-recurring' ); ?></th>
	<th><?php _e( 'Free Trial', 'cs-recurring' ); ?></th>
	<th><?php _e( 'Period', 'cs-recurring' ); ?></th>
	<th><?php echo _x( 'Times', 'Referring to billing period', 'cs-recurring' ); ?></th>
	<th><?php echo _x( 'Signup Fee', 'Referring to subscription signup fee', 'cs-recurring' ); ?></th>
	<?php
}

/**
 * Add a hook to the variable price rows that all of our other fields can hook into
 *
 * @access      public
 * @since       2.6
 * @return      void
 */
function cs_recurring_price_row_hook( $download_id, $price_id, $args ) {
	?>
	<div class="cs-custom-price-option-section">
		<?php
		if ( version_compare( CS_VERSION, '2.10.999', '>' ) ) {
			printf( '<span class="cs-custom-price-option-section-title">%s</span>', esc_html__( 'Recurring Payments Settings', 'cs-recurring' ) );
		}
		?>
		<div class="cs-custom-price-option-section-content cs-form-row">
		<?php
			do_action( 'cs_recurring_download_price_row', $download_id, $price_id, $args );
		?>
		</div>
	</div>
	<?php
}
add_action( 'cs_download_price_option_row', 'cs_recurring_price_row_hook', 999, 3 );


/**
 * Meta box is recurring yes/no field
 *
 * @access      public
 * @since       1.0
 * @return      void
 */
function cs_recurring_metabox_recurring( $download_id, $price_id, $args ) {

	$recurring = CS_Recurring()->is_price_recurring( $download_id, $price_id );

	?>
	<div class="cs-form-group cs-form-row__column cs-recurring-enabled">
		<label for="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][recurring]" class="cs-form-group__label"><?php esc_html_e( 'Recurring', 'cs-recurring' ); ?></label>
		<div class="cs-form-group__control">
			<select name="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][recurring]" id="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][recurring]" class="cs-form-group__input">
				<option value="no" <?php selected( $recurring, false ); ?>><?php echo esc_attr_e( 'No', 'cs-recurring' ); ?></option>
				<option value="yes" <?php selected( $recurring, true ); ?>><?php echo esc_attr_e( 'Yes', 'cs-recurring' ); ?></option>
			</select>
		</div>
	</div>
	<?php
}
add_action( 'cs_recurring_download_price_row', 'cs_recurring_metabox_recurring', 999, 3 );


/**
 * Meta box free trial field
 *
 * @access      public
 * @since       1.0
 * @return      void
 */
function cs_recurring_metabox_free_trial( $download_id, $price_id, $args ) {

	$recurring = CS_Recurring()->is_price_recurring( $download_id, $price_id );
	$periods   = CS_Recurring()->singular_periods();
	$trial     = CS_Recurring()->get_trial_period( $download_id, $price_id );
	$quantity  = empty( $trial['quantity'] ) ? '' : $trial['quantity'];
	$unit      = empty( $trial['unit'] ) ? '' : $trial['unit'];
	$disabled  = $recurring ? '' : 'disabled ';
	// Remove non-valid trial periods
	unset( $periods['quarter'] );
	unset( $periods['semi-year'] );

	?>
	<fieldset class="cs-form-group cs-form-row__column cs-recurring-free-trial">
		<legend class="cs-form-group__label"><?php esc_html_e( 'Free Trial', 'cs-recurring' ); ?></legend>
		<div class="cs-form-group__control cs-form-group__control--is-inline">
			<div class="eddrecurring-trial-quantity">
				<label for="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][trial-quantity]" class="screen-reader-text cs-form-group__label"><?php esc_html_e( 'Trial Quantity', 'cs-recurring' ); ?></label>
				<input <?php echo $disabled; ?> name="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][trial-quantity]" class="cs-form-group__input small-text trial-quantity" id="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][trial-quantity]" type="number" min="0" step="1" value="<?php echo esc_attr( $quantity ); ?>" placeholder="0"/>
			</div>
			<div class="eddrecurring-trial-period">
				<label for="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][trial-unit]" class="screen-reader-text cs-form-group__label"><?php esc_html_e( 'Trial Period', 'cs-recurring' ); ?></label>
				<select <?php echo $disabled; ?> name="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][trial-unit]" id="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][trial-unit]">
					<?php foreach ( $periods as $key => $value ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $unit, $key ); ?>><?php echo esc_attr( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
	</fieldset>
	<?php
}
add_action( 'cs_recurring_download_price_row', 'cs_recurring_metabox_free_trial', 999, 3 );

/**
 * Meta box recurring period field
 *
 * @access      public
 * @since       1.0
 * @return      void
 */
function cs_recurring_metabox_period( $download_id, $price_id, $args ) {

	$recurring = CS_Recurring()->is_price_recurring( $download_id, $price_id );
	$periods   = CS_Recurring()->periods();
	$period    = CS_Recurring()->get_period( $price_id );

	$disabled = $recurring ? '' : 'disabled ';

	?>
	<div class="cs-form-group cs-form-row__column cs-recurring-period">
		<label for="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][period]" class="cs-form-group__label"><?php esc_html_e( 'Period', 'cs-recurring' ); ?></label>
		<div class="cs-form-group__control">
			<select class="cs-form-group__input" <?php echo $disabled; ?>name="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][period]" id="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][period]">
				<?php foreach ( $periods as $key => $value ) : ?>
					<option value="<?php echo $key; ?>" <?php selected( $period, $key ); ?>><?php echo esc_attr( $value ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<?php
}
add_action( 'cs_recurring_download_price_row', 'cs_recurring_metabox_period', 999, 3 );

/**
 * Meta box recurring times field
 *
 * @access      public
 * @since       1.0
 * @return      void
 */
function cs_recurring_metabox_times( $download_id, $price_id, $args ) {

	$recurring = CS_Recurring()->is_price_recurring( $download_id, $price_id );
	$times     = CS_Recurring()->get_times( $price_id );
	$period    = CS_Recurring()->get_period( $price_id );

	$disabled = $recurring ? '' : 'disabled ';

	?>
	<div class="cs-form-row__column cs-form-group times cs-recurring-times">
		<label for="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][times]" class="cs-form-group__label"><?php echo esc_html_x( 'Times', 'Referring to billing period', 'cs-recurring' ); ?></label>
		<div class="cs-form-group__control">
			<input class="cs-form-group__input small-text" <?php echo $disabled; ?>type="number" min="0" step="1" name="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][times]" id="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][times]" value="<?php echo esc_attr( $times ); ?>" />
		</div>
	</div>
	<?php
}
add_action( 'cs_recurring_download_price_row', 'cs_recurring_metabox_times', 999, 3 );

/**
 * Meta box recurring fee field
 *
 * @access      public
 * @since       1.1
 * @return      void
 */
function cs_recurring_metabox_signup_fee( $download_id, $price_id, $args ) {

	$recurring         = CS_Recurring()->is_price_recurring( $download_id, $price_id );
	$has_trial         = CS_Recurring()->has_free_trial( $download_id, $price_id );
	$signup_fee        = CS_Recurring()->get_signup_fee( $price_id, $download_id );
	$currency_position = cs_get_option( 'currency_position', 'before' );

	$disabled = $recurring && ! $has_trial ? '' : 'disabled ';

	?>
	<div class="cs-form-group cs-form-row__column signup_fee cs-recurring-fee">
		<label for="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][signup_fee]" class="cs-form-group__label"><?php echo esc_html_x( 'Signup Fee', 'Referring to subscription signup fee', 'cs-recurring' ); ?></label>
		<div class="cs-form-group__control">
			<?php
			if ( 'before' === $currency_position ) {
				?>
				<span class="cs-amount-control__currency is-before"><?php echo esc_html( cs_currency_filter( '' ) ); ?></span>
				<input type="text" name="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][signup_fee]" id="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][signup_fee]" class="cs-form-group__input cs-price-field" value="<?php echo esc_attr( $signup_fee ); ?>"<?php echo $disabled;?>/>
				<?php
			} else {
				?>
				<input type="text" name="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][signup_fee]" id="cs_variable_prices[<?php echo esc_attr( $price_id ); ?>][signup_fee]" class="cs-form-group__input cs-price-field" value="<?php echo esc_attr( $signup_fee ); ?>"<?php echo $disabled;?>/>
				<span class="cs-amount-control__currency is-after"><?php echo esc_html( cs_currency_filter( '' ) ); ?></span>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}
add_action( 'cs_recurring_download_price_row', 'cs_recurring_metabox_signup_fee', 999, 3 );

/**
 * Meta fields for CS to save
 *
 * @access      public
 * @since       1.0
 * @return      array
 */
function cs_recurring_save_single( $fields ) {
	$fields[] = 'cs_period';
	$fields[] = 'cs_times';
	$fields[] = 'cs_recurring';
	$fields[] = 'cs_signup_fee';

	if( defined( 'CS_CUSTOM_PRICES' ) ) {
		$fields[] = 'cs_custom_signup_fee';
		$fields[] = 'cs_custom_recurring';
		$fields[] = 'cs_custom_times';
		$fields[] = 'cs_custom_period';
	}

	return $fields;
}
add_filter( 'cs_metabox_fields_save', 'cs_recurring_save_single' );

/**
 * Store the trial options
 *
 * @access      public
 * @since       2.6
 * @return      void
 */
function cs_recurring_save_trial_period( $post_id, $post ) {

	if ( ! current_user_can( 'edit_product', $post_id ) ) {
		return;
	}

	if( ! empty( $_POST['cs_recurring_free_trial'] ) && empty( $_POST['_variable_pricing'] ) ) {

		$default = array(
			'quantity' => 1,
			'unit'     => 'month',
		);

		$period             = array();
		$period['unit']     = sanitize_text_field( $_POST['cs_recurring_trial_unit'] );
		$period['quantity'] = absint( $_POST['cs_recurring_trial_quantity'] );
		$period             = wp_parse_args( $period, $default );

		update_post_meta( $post_id, 'cs_trial_period', $period );

	} else {

		delete_post_meta( $post_id, 'cs_trial_period' );

	}
}
add_action( 'cs_save_download', 'cs_recurring_save_trial_period', 10, 2 );


/**
 * Set colspan on submit row
 *
 * This is a little hacky, but it's the best way to adjust the colspan on the submit row to make sure it goes full width
 *
 * @access      private
 * @since       1.0
 * @return      void
 */
function cs_recurring_metabox_colspan() {
	echo '<script type="text/javascript">jQuery(function($){ $("#cs_price_fields td.submit").attr("colspan", 7)});</script>';
}
add_action( 'cs_meta_box_fields', 'cs_recurring_metabox_colspan', 20 );


/*
|--------------------------------------------------------------------------
| Single Price Options
|--------------------------------------------------------------------------
*/

/**
 * Add a hook to the Prices metabox that all of our other fields can hook into
 *
 * @access      public
 * @since       2.6
 * @return      void
 */
function cs_recurring_metabox_hook( $download_id ) {
	$is_variable = cs_has_variable_prices( $download_id );
	$display     = $is_variable ? ' style="display:none;"' : '';
	?>
	<div class="cs-form-row cs-recurring-single"<?php echo $display; ?>>
		<?php do_action( 'cs_recurring_download_metabox', $download_id ); ?>
	</div>
	<?php
}
add_action( 'cs_after_price_field', 'cs_recurring_metabox_hook', 1 );


/**
 * Meta box is recurring yes/no field
 *
 * @access      public
 * @since       1.0
 * @return      void
 */
function cs_recurring_metabox_single_recurring( $download_id ) {

	$recurring = CS_Recurring()->is_recurring( $download_id );

	?>
	<div class="cs-form-group cs-form-row__column">
		<label for="cs_recurring" class="cs-form-group__label"><?php esc_html_e( 'Recurring', 'cs-recurring' ); ?></label>
		<div class="cs-form-group__control">
			<select name="cs_recurring" id="cs_recurring" class="cs-form-group__input">
				<option value="no" <?php selected( $recurring, false ); ?>><?php esc_attr_e( 'No', 'cs-recurring' ); ?></option>
				<option value="yes" <?php selected( $recurring, true ); ?>><?php esc_attr_e( 'Yes', 'cs-recurring' ); ?></option>
			</select>
		</div>
	</div>
	<?php
}
add_action( 'cs_recurring_download_metabox', 'cs_recurring_metabox_single_recurring' );

/**
 * Meta box recurring period field
 *
 * @access      public
 * @since       1.0
 * @return      void
 */
function cs_recurring_metabox_single_period( $download_id ) {

	$periods = CS_Recurring()->periods();
	$period  = CS_Recurring()->get_period_single( $download_id );
	?>
	<div class="cs-form-group cs-form-row__column">
		<label for="cs_period" class="cs-form-group__label"><?php esc_html_e( 'Period', 'cs-recurring' ); ?></label>
		<div class="cs-form-group__control">
			<select name="cs_period" id="cs_period" class="cs-form-group__input">
				<?php foreach ( $periods as $key => $value ) : ?>
					<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $period, $key ); ?>><?php echo esc_html( $value ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
	</div>
	<?php
}
add_action( 'cs_recurring_download_metabox', 'cs_recurring_metabox_single_period' );


/**
 * Meta box recurring times field
 *
 * @access      public
 * @since       1.0
 * @return      void
 */
function cs_recurring_metabox_single_times( $download_id ) {

	$times = CS_Recurring()->get_times_single( $download_id );
	?>

	<div class="cs-form-group cs-form-row__column">
		<label for="cs_times" class="cs-form-group__label"><?php esc_html_e( 'Times', 'cs-recurring' ); ?></label>
		<div class="cs-form-group__control">
			<input type="number" min="0" step="1" name="cs_times" id="cs_times" class="cs-form-group__input small-text" value="<?php echo esc_attr( $times ); ?>" />
		</div>
	</div>
	<?php
}
add_action( 'cs_recurring_download_metabox', 'cs_recurring_metabox_single_times' );

/**
 * Meta box recurring signup fee field
 *
 * @access      public
 * @since       1.1
 * @return      void
 */
function cs_recurring_metabox_single_signup_fee( $download_id ) {

	$has_trial         = CS_Recurring()->has_free_trial( $download_id );
	$signup_fee        = CS_Recurring()->get_signup_fee_single( $download_id );
	$disabled          = $has_trial ? ' disabled="disabled"' : '';
	$currency_position = cs_get_option( 'currency_position', 'before' );
	?>

	<div class="cs-form-group cs-form-row__column">
		<label for="cs_signup_fee" class="cs-form-group__label"><?php esc_html_e( 'Signup Fee', 'cs-recurring' ); ?></label>
		<div class="cs-form-group__control">
			<?php
			if ( 'before' === $currency_position ) {
				?>
				<span class="cs-amount-control__currency is-before"><?php echo esc_html( cs_currency_filter( '' ) ); ?></span>
				<input type="text" name="cs_signup_fee" id="cs_signup_fee" class="cs-form-group__input cs-price-field" value="<?php echo esc_attr( $signup_fee ); ?>"<?php echo $disabled;?>/>
				<?php
			} else {
				?>
				<input type="text" name="cs_signup_fee" id="cs_signup_fee" class="cs-form-group__input cs-price-field" value="<?php echo esc_attr( $signup_fee ); ?>"<?php echo $disabled;?>/>
				<span class="cs-amount-control__currency is-after"><?php echo esc_html( cs_currency_filter( '' ) ); ?></span>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}
add_action( 'cs_recurring_download_metabox', 'cs_recurring_metabox_single_signup_fee' );

/**
 * Free trial options
 *
 * @access      public
 * @since       2.6
 * @return      void
 */
function cs_recurring_metabox_trial_options( $download_id ) {

	$has_trial      = CS_Recurring()->has_free_trial( $download_id );
	$periods        = CS_Recurring()->singular_periods();
	$period         = CS_Recurring()->get_trial_period( $download_id );
	$quantity       = empty( $period['quantity'] ) ? '' : $period['quantity'];
	$unit           = empty( $period['unit'] ) ? '' : $period['unit'];
	$option_display = $has_trial ? '' : ' style="display:none;"';

	// Remove non-valid trial periods
	unset( $periods['quarter'] );
	unset( $periods['semi-year'] );

	$one_one_discount_help = '';
	if( cs_get_option( 'recurring_one_time_discounts' ) ) {
		$one_one_discount_help = ' ' . __( '<strong>Additional note</strong>: with free trials, one time discounts are not supported and discount codes for this product will apply to all payments after the trial period.', 'cs-recurring' );
	}

	$variable_pricing   = cs_has_variable_prices( $download_id );
	$variable_display   = $variable_pricing ? ' style="display:none;"' : '';

	?>
	<div id="cs_recurring_free_trial_options_wrap" class="cs-form-group"<?php echo $variable_display; ?>>

		<?php if( cs_is_gateway_active( '2checkout' ) || cs_is_gateway_active( '2checkout_onsite' ) ) : ?>
			<p><strong><?php _e( '2Checkout does not support free trial periods. Subscriptions purchased through 2Checkout cannot include free trials.', 'cs-recurring' ); ?></strong></p>
		<?php endif; ?>

		<p>
			<input type="checkbox" name="cs_recurring_free_trial" id="cs_recurring_free_trial" value="yes"<?php checked( true, $has_trial ); ?>/>
			<label for="cs_recurring_free_trial">
				<?php esc_html_e( 'Enable free trial for subscriptions', 'cs-recurring' ); ?>
				<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( 'Check this box to include a free trial with subscriptions for this product. When signing up for a free trial, the customer\'s payment details will be taken at checkout but the customer will not be charged until the free trial is completed. <strong>Note:</strong> this only applies when purchasing a subscription. If a price option is not set to recurring, this free trial will not be used.', 'cs-recurring' ); echo $one_one_discount_help; ?>"></span>
			</label>
		</p>
		<fieldset id="cs_recurring_free_trial_options" class="cs-form-group"<?php echo $option_display; ?>>
			<legend class="screen-reader-text"><?php esc_html_e( 'Free Trial Options', 'cs-recurring' ); ?></legend>
			<div class="cs-form-group__control cs-form-group__control--is-inline">
				<div class="cs-recurring-trial-quantity">
					<label for="cs_recurring_trial_quantity" class="cs-form-group__label screen-reader-text"><?php esc_html_e( 'Trial Quantity', 'cs-recurring' ); ?></label>
					<input name="cs_recurring_trial_quantity" id="cs_recurring_trial_quantity" class="cs-form-group__input small-text" type="number" min="1" step="1" value="<?php echo esc_attr( $quantity ); ?>" placeholder="1"/>
				</div>
				<div class="cs-recurring-trial-unit">
					<label for="cs_recurring_trial_unit" class="cs-form-group__label screen-reader-text"><?php esc_html_e( 'Trial Period', 'cs-recurring' ); ?></label>
					<select name="cs_recurring_trial_unit" id="cs_recurring_trial_unit" class="cs-form-group__input">
						<?php foreach ( $periods as $key => $value ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $unit, $key ); ?>><?php echo esc_attr( $value ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
			</div>
		</fieldset>
	</div>
	<?php
}
add_action( 'cs_meta_box_price_fields', 'cs_recurring_metabox_trial_options' );

/**
 * Recurring options for Custom Prices
 *
 * @access      public
 * @since       2.5
 * @return      void
 */
function cs_recurring_metabox_custom_options( $download_id ) {

	if ( ! defined( 'CS_CUSTOM_PRICES' ) ) {
		return;
	}

	$custom            = get_post_meta( $download_id, '_cs_cp_custom_pricing', true );
	$recurring         = CS_Recurring()->is_custom_recurring( $download_id );
	$periods           = CS_Recurring()->periods();
	$period            = CS_Recurring()->get_custom_period( $download_id );
	$times             = CS_Recurring()->get_custom_times( $download_id );
	$signup_fee        = CS_Recurring()->get_custom_signup_fee( $download_id );
	$display           = $custom ? '' : ' style="display:none;"';
	$currency_position = cs_get_option( 'currency_position', 'before' );
	$disabled          = $custom && $recurring ? '' : ' disabled';
	?>
	<fieldset id="cs_custom_recurring" class="cs_recurring_custom_wrap cs-form-row"<?php echo $display; ?>>
		<legend><?php esc_html_e( 'Recurring Options for Custom Prices', 'cs-recurring' ); ?></legend>
		<p><?php esc_html_e( 'Select the recurring options for customers that pay with a custom price.', 'cs-recurring' ); ?></p>
		<div class="cs-form-group cs-form-row__column">
			<label for="cs_custom_recurring" class="cs-form-group__label"><?php esc_html_e( 'Recurring', 'cs-recurring' ); ?></label>
			<div class="cs-form-group__control">
				<select name="cs_custom_recurring" id="cs_custom_recurring">
					<option value="no" <?php selected( $recurring, false ); ?>><?php esc_attr_e( 'No', 'cs-recurring' ); ?></option>
					<option value="yes" <?php selected( $recurring, true ); ?>><?php esc_attr_e( 'Yes', 'cs-recurring' ); ?></option>
				</select>
			</div>
		</div>
		<div class="cs-form-group cs-form-row__column">
			<label for="cs_custom_period" class="cs-form-group__label"><?php esc_html_e( 'Period', 'cs-recurring' ); ?></label>
			<div class="cs-form-group__control">
				<select name="cs_custom_period" id="cs_custom_period" class="cs-form-group__input"<?php echo esc_attr( $disabled ); ?>>
					<?php foreach ( $periods as $key => $value ) : ?>
						<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $period, $key ); ?>><?php echo esc_html( $value ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="cs-form-group cs-form-row__column times">
			<label for="cs_custom_times" class="cs-form-group__label"><?php esc_html_e( 'Times', 'cs-recurring' ); ?></label>
			<div class="cs-form-group__control">
				<input type="number" min="0" step="1" name="cs_custom_times" id="cs_custom_times" class="cs-form-group__input small-text" value="<?php echo esc_attr( $times ); ?>"<?php echo esc_attr( $disabled ); ?> />
			</div>
		</div>
		<div class="cs-form-group cs-form-row__column signup_fee">
			<label for="cs_custom_signup_fee" class="cs-form-group__label"><?php esc_html_e( 'Signup Fee', 'cs-recurring' ); ?></label>
			<div class="cs-form-group__control">
				<?php
				if ( 'before' === $currency_position ) {
					?>
					<span class="cs-amount-control__currency is-before"><?php echo esc_html( cs_currency_filter( '' ) ); ?></span>
					<input type="text" name="cs_custom_signup_fee" id="cs_custom_signup_fee" class="cs-form-group__input cs-price-field" value="<?php echo esc_attr( $signup_fee ); ?>"<?php echo esc_attr( $disabled ); ?>/>
					<?php
				} else {
					?>
					<input type="text" name="cs_custom_signup_fee" id="cs_custom_signup_fee" class="cs-form-group__input cs-price-field" value="<?php echo esc_attr( $signup_fee ); ?>"<?php echo esc_attr( $disabled ); ?>/>
					<span class="cs-amount-control__currency is-after"><?php echo esc_html( cs_currency_filter( '' ) ); ?></span>
					<?php
				}
				?>
			</div>
		</div>
	</fieldset><!--close .cs_recurring_custom_wrap-->
	<?php
}
add_action( 'cs_after_price_field', 'cs_recurring_metabox_custom_options', 10 );

/**
 * Display Subscription Payment Notice
 *
 * @description Adds a subscription payment indicator within the single payment view "Update Payment" metabox (top)
 * @since       2.4
 *
 * @param $payment_id
 *
 */
function cs_display_subscription_payment_meta( $payment_id ) {

	$is_sub = cs_get_payment_meta( $payment_id, '_cs_subscription_payment' );

	if ( $is_sub ) :
		$subs_db = new CS_Subscriptions_DB;
		$subs    = $subs_db->get_subscriptions( array( 'parent_payment_id' => $payment_id, 'order' => 'ASC' ) );
?>
		<div id="cs-order-subscriptions" class="postbox">
			<h3 class="hndle">
				<span><?php _e( 'Subscriptions', 'cs-recurring' ); ?></span>
			</h3>
			<div class="inside">

				<?php foreach( $subs as $sub ) : ?>
					<?php $sub_url = admin_url( 'edit.php?post_type=download&page=cs-subscriptions&id=' . $sub->id ); ?>
					<p>
						<span class="label"><span class="dashicons dashicons-update"></span> <?php printf( __( 'Subscription ID: <a href="%s">#%d</a>', 'cs_recurring' ), $sub_url, $sub->id ); ?></span>&nbsp;
					</p>
					<?php $payments = $sub->get_child_payments(); ?>
					<?php if( $payments ) : ?>
						<p><strong><?php _e( 'Associated Payments', 'cs-recurring' ); ?>:</strong></p>
						<ul id="cs-recurring-sub-payments">
						<?php foreach( $payments as $payment ) : ?>
							<li>
								<span class="howto"><?php echo date_i18n( get_option( 'date_format' ), strtotime( $payment->date ) ); ?></span>
								<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=cs-payment-history&view=view-order-details&id=' . $payment->ID ) ); ?>">
									<?php if( function_exists( 'cs_get_payment_number' ) ) : ?>
										<?php echo '#' . $payment->number ?>
									<?php else : ?>
										<?php echo '#' . $payment->ID; ?>
									<?php endif; ?>
								</a>&nbsp;&ndash;&nbsp;
								<span><?php echo cs_currency_filter( cs_format_amount( $payment->total ) ); ?>&nbsp;&ndash;&nbsp;</span>
								<span><?php echo $payment->status_nicename; ?></span>
							</li>
						<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				<?php endforeach; ?>
			</div>
		</div>
<?php
	endif;
}
add_action( 'cs_view_order_details_sidebar_before', 'cs_display_subscription_payment_meta', 10, 1 );

/**
 * List subscription (sub) payments of a particular parent payment
 *
 * The parent payment ID is the very first payment made. All payments made after for the profile are sub.
 *
 * @since  1.0
 * @return void
 */
function cs_recurring_display_parent_payment( $payment_id = 0 ) {

	$payment = cs_get_payment( $payment_id );

	if( $payment->parent_payment ) :

		$parent_payment = cs_get_payment( $payment->parent_payment );
		$sub_id = $payment->get_meta( 'subscription_id', true );
		if( ! $sub_id ) {
			$subs_db = new CS_Subscriptions_DB;
			$subs    = $subs_db->get_subscriptions( array( 'parent_payment_id' => $payment->parent_payment, 'order' => 'ASC' ) );
			$sub     = reset( $subs );
			$sub_id  = $sub->id;
		}
		$parent_url = admin_url( 'edit.php?post_type=download&page=cs-payment-history&view=view-order-details&id=' . $payment->parent_payment );
?>
		<div id="cs-order-subscription-payments" class="postbox">
			<h3 class="hndle">
				<span><?php _e( 'Subscription', 'cs-recurring' ); ?></span>
			</h3>
			<div class="inside">
				<?php $sub_url = admin_url( 'edit.php?post_type=download&page=cs-subscriptions&id=' . $sub_id ); ?>
				<p>
					<span class="label"><span class="dashicons dashicons-update"></span> <?php printf( __( 'Subscription ID: <a href="%s">#%d</a>', 'cs_recurring' ), $sub_url, $sub_id ); ?></span>&nbsp;
				</p>
				<p><?php printf( __( 'Parent Payment: <a href="%s">%s</a>' ), $parent_url, $parent_payment->number ); ?></p>
			</div><!-- /.inside -->
		</div><!-- /#cs-order-subscription-payments -->
<?php
	endif;
}
add_action( 'cs_view_order_details_sidebar_before', 'cs_recurring_display_parent_payment', 10 );

/**
 * Display Subscription transaction IDs for parent payments
 *
 * @since 2.4.4
 * @param $payment_id
 */
function cs_display_subscription_txn_ids( $payment_id ) {

	$is_sub = cs_get_payment_meta( $payment_id, '_cs_subscription_payment' );

	if ( $is_sub ) :
		$subs_db = new CS_Subscriptions_DB;
		$subs    = $subs_db->get_subscriptions( array( 'parent_payment_id' => $payment_id ) );

		if( ! $subs ) {
			return;
		}
?>
		<div class="cs-subscription-tx-id cs-admin-box-inside">
			<?php foreach( $subs as $sub ) : ?>
				<?php if( ! $sub->get_transaction_id() ) { continue; } ?>
				<p>
					<span class="label"><?php _e( 'Subscription TXN ID:', 'cs-recurring' ); ?></span>&nbsp;
					<span><?php echo apply_filters( 'cs_payment_details_transaction_id-' . $sub->gateway, $sub->get_transaction_id(), $payment_id ); ?></span>
				</p>
			<?php endforeach; ?>
		</div>
<?php
	endif;
}
add_action( 'cs_view_order_details_payment_meta_after', 'cs_display_subscription_txn_ids', 10, 1 );
