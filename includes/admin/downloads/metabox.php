<?php
/**
 * Metabox Functions
 *
 * @package     CS
 * @subpackage  Admin/Downloads
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** All Downloads *************************************************************/

/**
 * Register all the meta boxes for the Download custom post type
 *
 * @since 1.0
 * @return void
 */
function cs_add_download_meta_box() {
	$post_types = apply_filters( 'cs_download_metabox_post_types', array( 'download' ) );

	foreach ( $post_types as $post_type ) {

		/** Product Prices **/
		add_meta_box( 'cs_product_prices', sprintf( __( '%1$s Prices', 'commercestore' ), cs_get_label_singular(), cs_get_label_plural() ),  'cs_render_download_meta_box', $post_type, 'normal', 'high' );

		/** Product Files (and bundled products) **/
		add_meta_box( 'cs_product_files', sprintf( __( '%1$s Files', 'commercestore' ), cs_get_label_singular(), cs_get_label_plural() ),  'cs_render_files_meta_box', $post_type, 'normal', 'high' );

		/** Product Settings **/
		add_meta_box( 'cs_product_settings', sprintf( __( '%1$s Settings', 'commercestore' ), cs_get_label_singular(), cs_get_label_plural() ),  'cs_render_settings_meta_box', $post_type, 'side', 'default' );

		/** Product Notes */
		add_meta_box( 'cs_product_notes', sprintf( __( '%1$s Instructions', 'commercestore' ), cs_get_label_singular(), cs_get_label_plural() ), 'cs_render_product_notes_meta_box', $post_type, 'normal', 'high' );

		if ( current_user_can( 'view_product_stats', get_the_ID() ) ) {
			/** Product Stats */
			add_meta_box( 'cs_product_stats', sprintf( __( '%1$s Stats', 'commercestore' ), cs_get_label_singular(), cs_get_label_plural() ), 'cs_render_stats_meta_box', $post_type, 'side', 'high' );
		}
	}
}
add_action( 'add_meta_boxes', 'cs_add_download_meta_box' );

/**
 * Returns default CommerceStore Download meta fields.
 *
 * @since 1.9.5
 * @return array $fields Array of fields.
 */
function cs_download_metabox_fields() {

	$fields = array(
		'_cs_product_type',
		'cs_price',
		'_variable_pricing',
		'_cs_price_options_mode',
		'cs_variable_prices',
		'cs_download_files',
		'_cs_purchase_text',
		'_cs_purchase_style',
		'_cs_purchase_color',
		'_cs_bundled_products',
		'_cs_hide_purchase_link',
		'_cs_download_tax_exclusive',
		'_cs_button_behavior',
		'_cs_quantities_disabled',
		'cs_product_notes',
		'_cs_default_price_id',
		'_cs_bundled_products_conditions'
	);

	if ( current_user_can( 'manage_shop_settings' ) ) {
		$fields[] = '_cs_download_limit';
		$fields[] = '_cs_refundability';
		$fields[] = '_cs_refund_window';
	}

	if ( cs_use_skus() ) {
		$fields[] = 'cs_sku';
	}

	return apply_filters( 'cs_metabox_fields_save', $fields );
}

/**
 * Save post meta when the save_post action is called
 *
 * @since 1.0
 * @param int $post_id Download (Post) ID
 * @global array $post All the data of the the current post
 * @return void
 */
function cs_download_meta_box_save( $post_id, $post ) {
	if ( ! isset( $_POST['cs_download_meta_box_nonce'] ) || ! wp_verify_nonce( $_POST['cs_download_meta_box_nonce'], basename( __FILE__ ) ) ) {
		return;
	}

	if ( cs_doing_autosave() || cs_doing_ajax() || isset( $_REQUEST['bulk_edit'] ) ) {
		return;
	}

	if ( isset( $post->post_type ) && 'revision' == $post->post_type ) {
		return;
	}

	if ( ! current_user_can( 'edit_product', $post_id ) ) {
		return;
	}

	// The default fields that get saved
	$fields = cs_download_metabox_fields();
	foreach ( $fields as $field ) {
		if ( '_cs_default_price_id' == $field && cs_has_variable_prices( $post_id ) ) {

			if ( isset( $_POST[ $field ] ) ) {
				$new_default_price_id = ( ! empty( $_POST[ $field ] ) && is_numeric( $_POST[ $field ] ) ) || ( 0 === (int) $_POST[ $field ] ) ? (int) $_POST[ $field ] : 1;
			} else {
				$new_default_price_id = 1;
			}

			update_post_meta( $post_id, $field, $new_default_price_id );
		} elseif ( '_cs_product_type' === $field && '0' === $_POST[ $field ] ) {
			// No value stored when product type is "default" ("0") for backwards compatibility.
			delete_post_meta( $post_id, '_cs_product_type' );
		} else {

			$new = false;
			if ( ! empty( $_POST[ $field ] ) ) {
				$new = apply_filters( 'cs_metabox_save_' . $field, $_POST[ $field ] );
			}
			if ( ! empty( $new ) ) {
				update_post_meta( $post_id, $field, $new );
			} else {
				delete_post_meta( $post_id, $field );
			}
		}
	}

	if ( cs_has_variable_prices( $post_id ) ) {
		$lowest = cs_get_lowest_price_option( $post_id );
		update_post_meta( $post_id, 'cs_price', $lowest );
	}

	do_action( 'cs_save_download', $post_id, $post );
}

add_action( 'save_post', 'cs_download_meta_box_save', 10, 2 );

/**
 * Sanitize bundled products on save
 *
 * Ensures a user doesn't try and include a product's ID in the products bundled with that product
 *
 * @since       1.6
 *
 * @param array $products
 * @return array
 */
function cs_sanitize_bundled_products_save( $products = array() ) {

	$products = array_map( function( $value ) {
		return preg_replace( '/[^0-9_]/', '', $value );
	}, (array) $products );

	foreach ( $products as $key => $value ) {
		$underscore_pos = strpos( $value, '_' );
		if ( is_numeric( $underscore_pos ) ) {
			$product_id = substr( $value, 0, $underscore_pos );
		} else {
			$product_id = $value;
		}

		if ( in_array( $product_id, array( 0, get_the_ID() ) ) ) {
			unset( $products[ $key ] );
		}
	}

	return array_values( array_unique( $products ) );
}
add_filter( 'cs_metabox_save__cs_bundled_products', 'cs_sanitize_bundled_products_save' );

/**
 * Don't save blank rows.
 *
 * When saving, check the price and file table for blank rows.
 * If the name of the price or file is empty, that row should not
 * be saved.
 *
 * @since 1.2.2
 * @param array $new Array of all the meta values
 * @return array $new New meta value with empty keys removed
 */
function cs_metabox_save_check_blank_rows( $new ) {
	foreach ( $new as $key => $value ) {
		if ( empty( $value['name'] ) && empty( $value['amount'] ) && empty( $value['file'] ) )
			unset( $new[ $key ] );
	}

	return $new;
}

/** Download Configuration ****************************************************/

/**
 * Download Metabox
 *
 * Extensions (as well as the core plugin) can add items to the main download
 * configuration metabox via the `cs_meta_box_fields` action.
 *
 * @since 1.0
 * @return void
 */
function cs_render_download_meta_box() {
	$post_id = get_the_ID();

	/*
	 * Output the price fields
	 * @since 1.9
	 */
	do_action( 'cs_meta_box_price_fields', $post_id );

	/*
	 * Output the price fields
	 *
	 * Left for backwards compatibility
	 *
	 */
	do_action( 'cs_meta_box_fields', $post_id );

	wp_nonce_field( basename( __FILE__ ), 'cs_download_meta_box_nonce' );
}

/**
 * Download Files Metabox
 *
 * @since 1.9
 * @return void
 */
function cs_render_files_meta_box() {
	/*
	 * Output the files fields
	 * @since 1.9
	 */
	do_action( 'cs_meta_box_files_fields', get_the_ID() );
}

/**
 * Download Settings Metabox
 *
 * @since 1.9
 * @return void
 */
function cs_render_settings_meta_box() {
	/*
	 * Output the files fields
	 * @since 1.9
	 */
	do_action( 'cs_meta_box_settings_fields', get_the_ID() );
}

/**
 * Price Section
 *
 * If variable pricing is not enabled, simply output a single input box.
 *
 * If variable pricing is enabled, outputs a table of all current prices.
 * Extensions can add column heads to the table via the `cs_download_file_table_head`
 * hook, and actual columns via `cs_download_file_table_row`
 *
 * @since 1.0
 *
 * @see cs_render_price_row()
 *
 * @param $post_id
 */
function cs_render_price_field( $post_id ) {
	$price              = cs_get_download_price( $post_id );
	$variable_pricing   = cs_has_variable_prices( $post_id );
	$prices             = cs_get_variable_prices( $post_id );
	$single_option_mode = cs_single_price_option_mode( $post_id );

	$price_display      = $variable_pricing ? ' style="display:none;"' : '';
	$variable_display   = $variable_pricing ? '' : ' style="display:none;"';
	$currency_position  = cs_get_option( 'currency_position', 'before' );
	?>
	<p>
		<strong><?php echo apply_filters( 'cs_price_options_heading', __( 'Pricing Options:', 'commercestore' ) ); ?></strong>
	</p>
<?php if ( CS_FEATURE_VARIABLE_PRICE ) { ?>
	<div class="cs-form-group">
		<div class="cs-form-group__control">
			<input type="checkbox" class="cs-form-group__input" name="_variable_pricing" id="cs_variable_pricing" value="1" <?php checked( 1, $variable_pricing ); ?> />
			<label for="cs_variable_pricing">
				<?php echo esc_html( apply_filters( 'cs_variable_pricing_toggle_text', __( 'Enable variable pricing', 'commercestore' ) ) ); ?>
			</label>
		</div>
	</div>
<?php } ?>

	<div id="cs_regular_price_field" class="cs-form-group cs_pricing_fields" <?php echo $price_display; ?>>
		<label for="cs_price" class="cs-form-group__label screen-reader-text"><?php esc_html_e( 'Price', 'commercestore' ); ?></label>
		<div class="cs-form-group__control">
		<?php
			$price_args = array(
				'name'  => 'cs_price',
				'id'    => 'cs_price',
				'value' => isset( $price ) ? esc_attr( cs_format_amount( $price ) ) : '',
				'class' => 'cs-form-group__input cs-price-field',
			);
			if ( 'before' === $currency_position ) {
				?>
				<span class="cs-amount-control__currency is-before"><?php echo esc_html( cs_currency_filter( '' ) ); ?></span>
				<?php
				echo CS()->html->text( $price_args );
			} else {
				echo CS()->html->text( $price_args );
				?>
				<span class="cs-amount-control__currency is-after"><?php echo esc_html( cs_currency_filter( '' ) ); ?></span>
				<?php
			}

			do_action( 'cs_price_field', $post_id );
			?>
		</div>
	</div>

	<?php do_action( 'cs_after_price_field', $post_id ); ?>

	<div id="cs_variable_price_fields" class="cs_pricing_fields" <?php echo $variable_display; ?>>
		<input type="hidden" id="cs_variable_prices" class="cs_variable_prices_name_field" value=""/>
			<div class="cs-form-group">
				<div class="cs-form-group__control">
					<?php echo CS()->html->checkbox( array( 'name' => '_cs_price_options_mode', 'current' => $single_option_mode, 'class' => 'cs-form-group__input', ) ); ?>
					<label for="_cs_price_options_mode"><?php echo esc_html( apply_filters( 'cs_multi_option_purchase_text', __( 'Enable multi-option purchase mode. Allows multiple price options to be added to your cart at once', 'commercestore' ) ) ); ?></label>
				</div>
			</div>
		<div id="cs_price_fields" class="cs_meta_table_wrap">
			<div class="widefat cs_repeatable_table">

				<div class="cs-price-option-fields cs-repeatables-wrap">
					<?php
						if ( ! empty( $prices ) ) :

							foreach ( $prices as $key => $value ) :
								$name   = ( isset( $value['name'] ) && ! empty( $value['name'] ) ) ? $value['name']   : '';
								$index  = ( isset( $value['index'] ) && $value['index'] !== '' )   ? $value['index']  : $key;
								$amount = isset( $value['amount'] ) ? $value['amount'] : '';
								$args   = apply_filters( 'cs_price_row_args', compact( 'name', 'amount' ), $value );
								?>
								<div class="cs_variable_prices_wrapper cs_repeatable_row" data-key="<?php echo esc_attr( $key ); ?>">
									<?php do_action( 'cs_render_price_row', $key, $args, $post_id, $index ); ?>
								</div>
							<?php
							endforeach;
						else :
					?>
						<div class="cs_variable_prices_wrapper cs_repeatable_row" data-key="1">
							<?php do_action( 'cs_render_price_row', 1, array(), $post_id, 1 ); ?>
						</div>
					<?php endif; ?>

				</div>

				<div class="cs-add-repeatable-row">
					<button class="button-secondary cs_add_repeatable"><?php _e( 'Add New Price', 'commercestore' ); ?></button>
				</div>
			</div>
		</div>
	</div><!--end #cs_variable_price_fields-->
<?php
}
add_action( 'cs_meta_box_price_fields', 'cs_render_price_field', 10 );

/**
 * Individual Price Row
 *
 * Used to output a table row for each price associated with a download.
 * Can be called directly, or attached to an action.
 *
 * @since 1.2.2
 *
 * @param       $key
 * @param array $args
 * @param       $post_id
 */
function cs_render_price_row( $key, $args, $post_id, $index ) {
	global $wp_filter;

	$defaults = array(
		'name'   => null,
		'amount' => null
	);

	$args = wp_parse_args( $args, $defaults );

	$default_price_id     = cs_get_default_variable_price( $post_id );
	$currency_position    = cs_get_option( 'currency_position', 'before' );
	$custom_price_options = isset( $wp_filter['cs_download_price_option_row'] ) ? true : false;

	// Run our advanced settings now, so we know if we need to display the settings.
	// Output buffer so that the headers run, so we can log them and use them later
	ob_start();
	if ( has_action( 'cs_download_price_table_head' ) ) {
		do_action_deprecated( 'cs_download_price_table_head', array( $post_id ), '2.10', 'cs_download_price_option_row' );
	}
	ob_end_clean();

	ob_start();
	$found_fields = isset( $wp_filter['cs_download_price_table_row'] ) ? $wp_filter['cs_download_price_table_row'] : false;
	if ( ! empty( $found_fields->callbacks ) ) {
		if ( 1 !== count( $found_fields->callbacks ) ) {
			do_action_deprecated( 'cs_download_price_table_row', array( $post_id, $key, $args ), '2.10', 'cs_download_price_option_row' );
		} else {
			do_action( 'cs_download_price_table_row', $post_id, $key, $args );
		}
	}
	$show_advanced = ob_get_clean();
?>
	<div class="cs-repeatable-row-header cs-draghandle-anchor">
		<span class="cs-repeatable-row-title" title="<?php _e( 'Click and drag to re-order price options', 'commercestore' ); ?>">
			<?php printf( __( 'Price ID: %s', 'commercestore' ), '<span class="cs_price_id">' . $key . '</span>' ); ?>
			<input type="hidden" name="cs_variable_prices[<?php echo $key; ?>][index]" class="cs_repeatable_index" value="<?php echo $index; ?>"/>
		</span>
		<?php
		$actions = array();
		if ( ! empty( $show_advanced ) || $custom_price_options ) {
			$actions['show_advanced'] = '<a href="#" class="toggle-custom-price-option-section">' . __( 'Show advanced settings', 'commercestore' ) . '</a>';
		}

		$actions['remove'] = '<a class="cs-remove-row cs-delete" data-type="price">' . sprintf( __( 'Remove', 'commercestore' ), $key ) . '<span class="screen-reader-text">' . sprintf( __( 'Remove price option %s', 'commercestore' ), $key ) . '</span></a>';
		?>
		<span class="cs-repeatable-row-actions">
			<?php echo implode( '&nbsp;&#124;&nbsp;', $actions ); ?>
		</span>
	</div>

	<div class="cs-repeatable-row-standard-fields">

		<div class="cs-form-group cs-option-name">
			<label for="cs_variable_prices-<?php echo esc_attr( $key ); ?>-name" class="cs-form-group__label cs-repeatable-row-setting-label"><?php esc_html_e( 'Option Name', 'commercestore' ); ?></label>
			<div class="cs-form-group__control">
			<?php echo CS()->html->text( array(
				'name'        => 'cs_variable_prices[' . $key . '][name]',
				'id'          => 'cs_variable_prices-' . $key . '-name',
				'value'       => esc_attr( $args['name'] ),
				'placeholder' => __( 'Option Name', 'commercestore' ),
				'class'       => 'cs_variable_prices_name large-text'
			) ); ?>
			</div>
		</div>

		<div class="cs-form-group cs-option-price">
			<label for="cs_variable_prices-<?php echo esc_attr( $key ); ?>-amount" class="cs-repeatable-row-setting-label"><?php esc_html_e( 'Price', 'commercestore' ); ?></label>
			<?php
			$price_args = array(
				'name'        => 'cs_variable_prices[' . $key . '][amount]',
				'id'          => 'cs_variable_prices-' . $key . '-amount',
				'value'       => $args['amount'],
				'placeholder' => cs_format_amount( 9.99 ),
				'class'       => 'cs-form-group__input cs-price-field',
			);
			?>

			<div class="cs-form-group__control cs-price-input-group">
				<?php
				if ( 'before' === $currency_position ) {
					?>
					<span class="cs-amount-control__currency is-before"><?php echo esc_html( cs_currency_filter( '' ) ); ?></span>
					<?php
					echo CS()->html->text( $price_args );
				} else {
					echo CS()->html->text( $price_args );
					?>
					<span class="cs-amount-control__currency is-after"><?php echo esc_html( cs_currency_filter( '' ) ); ?></span>
					<?php
				}
				?>
			</div>
		</div>

		<div class="cs-form-group cs_repeatable_default cs_repeatable_default_wrapper">
			<div class="cs-form-group__control">
			<label for="cs_default_price_id_<?php echo esc_attr( $key ); ?>" class="cs-repeatable-row-setting-label"><?php esc_html_e( 'Default', 'commercestore' ); ?></label>
				<input type="radio" <?php checked( $default_price_id, $key, true ); ?> class="cs_repeatable_default_input" name="_cs_default_price_id" id="cs_default_price_id_<?php echo esc_attr( $key ); ?>" value="<?php echo $key; ?>" />
				<span class="screen-reader-text"><?php printf( __( 'Set ID %s as default price', 'commercestore' ), $key ); ?></span>
			</div>
		</div>

	</div>

	<?php
		/**
		 * Intercept extension-specific settings and rebuild the markup
		 */
		if ( ! empty( $show_advanced ) || $custom_price_options ) {
			?>

			<div class="cs-custom-price-option-sections-wrap">
				<?php
				$elements = str_replace(
					array(
						'<td>',
						'<td ',
						'</td>',
						'<th>',
						'<th ',
						'</th>',
						'class="times"',
						'class="signup_fee"',
					),
					array(
						'<span class="cs-custom-price-option-section">',
						'<span ',
						'</span>',
						'<label class="cs-legacy-setting-label">',
						'<label ',
						'</label>',
						'class="cs-recurring-times times"', // keep old class for back compat
						'class="cs-recurring-signup-fee signup_fee"' // keep old class for back compat
					),
					$show_advanced
				);
				?>
				<div class="cs-custom-price-option-sections">
					<?php
						echo $elements;
						do_action( 'cs_download_price_option_row', $post_id, $key, $args );
					?>
				</div>
			</div>

			<?php
		}
}
add_action( 'cs_render_price_row', 'cs_render_price_row', 10, 4 );

/**
 * Product type options
 *
 * @access      private
 * @since       1.6
 * @return      void
 */
function cs_render_product_type_field( $post_id = 0 ) {

	$types = cs_get_download_types();
	$type  = cs_get_download_type( $post_id );
	?>
	<div class="cs-form-group">
		<label for="_cs_product_type" class="cs-form-group__label"><?php echo apply_filters( 'cs_product_type_options_heading', __( 'Product Type Options:', 'commercestore' ) ); ?></label>
		<div class="cs-form-group__control">
			<?php echo CS()->html->select(
				array(
					'options'          => $types,
					'name'             => '_cs_product_type',
					'id'               => '_cs_product_type',
					'selected'         => $type,
					'show_option_all'  => false,
					'show_option_none' => false,
					'class'            => 'cs-form-group__input',
				)
			);
			?>
			<span class="description"><?php esc_html_e( 'Select a product type', 'commercestore' ); ?></span>
			<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( '<strong>Product Type</strong>: Sell this item as a single product, or use the Bundle type to sell a collection of products.', 'commercestore' ); ?>"></span>
		</div>
	</div>
	<?php
}
add_action( 'cs_meta_box_files_fields', 'cs_render_product_type_field', 10 );

/**
 * Renders product field
 * @since 1.6
 *
 * @param $post_id
 */
function cs_render_products_field( $post_id ) {
	$download         = new CS_Download( $post_id );
	$type             = $download->get_type();
	$display          = $type == 'bundle' ? '' : ' style="display:none;"';
	$products         = $download->get_bundled_downloads();
	$variable_pricing = $download->has_variable_prices();
	$variable_display = $variable_pricing ? '' : 'display:none;';
	$variable_class   = $variable_pricing ? ' has-variable-pricing' : '';
	$prices           = $download->get_prices(); ?>

	<div id="cs_products"<?php echo $display; ?>>
		<div id="cs_file_fields_bundle" class="cs_meta_table_wrap">
			<div class="widefat cs_repeatable_table">

				<?php do_action( 'cs_download_products_table_head', $post_id ); ?>

				<div class="cs-bundled-product-select cs-repeatables-wrap">

					<?php if ( $products ) : ?>

						<div class="cs-bundle-products-header">
							<span class="cs-bundle-products-title"><?php printf( __( 'Bundled %s', 'commercestore' ), cs_get_label_plural() ); ?></span>
						</div>

						<?php $index = 1; ?>
						<?php foreach ( $products as $key => $product ) : ?>
							<div class="cs_repeatable_product_wrapper cs_repeatable_row" data-key="<?php echo esc_attr( $index ); ?>">
								<div class="cs-bundled-product-row<?php echo $variable_class; ?>">
									<div class="cs-bundled-product-item-reorder">
										<span class="cs-product-file-reorder cs-draghandle-anchor dashicons dashicons-move"  title="<?php printf( __( 'Click and drag to re-order bundled %s', 'commercestore' ), cs_get_label_plural() ); ?>"></span>
										<input type="hidden" name="cs_bundled_products[<?php echo $index; ?>][index]" class="cs_repeatable_index" value="<?php echo $index; ?>"/>
									</div>
									<div class="cs-form-group cs-bundled-product-item">
										<label for="cs_bundled_products_<?php echo esc_attr( $index ); ?>" class="cs-form-group__label cs-repeatable-row-setting-label"><?php printf( esc_html__( 'Select %s:', 'commercestore' ), cs_get_label_singular() ); ?></label>
										<div class="cs-form-group__control">
										<?php
										echo CS()->html->product_dropdown(
											array(
												'name'                 => '_cs_bundled_products[]',
												'id'                   => 'cs_bundled_products_' . esc_attr( $index ),
												'selected'             => $product,
												'multiple'             => false,
												'chosen'               => true,
												'bundles'              => false,
												'variations'           => true,
												'show_variations_only' => true,
												'class'                => 'cs-form-group__input',
											)
										);
										?>
										</div>
									</div>
									<div class="cs-form-group cs-bundled-product-price-assignment pricing" style="<?php echo $variable_display; ?>">
										<label class="cs-form-group__label cs-repeatable-row-setting-label" for="cs_bundled_products_conditions_<?php echo esc_attr( $index ); ?>"><?php esc_html_e( 'Price assignment:', 'commercestore' ); ?></label>
										<div class="cs-form-group__control">
										<?php
											$options = array();

											if ( $prices ) {
												foreach ( $prices as $price_key => $price ) {
													$options[ $price_key ] = $prices[ $price_key ]['name'];
												}
											}

											$price_assignments = cs_get_bundle_pricing_variations( $post_id );
											if ( ! empty( $price_assignments[0] ) ) {
												$price_assignments = $price_assignments[0];
											}

											$selected = isset( $price_assignments[ $index ] ) ? $price_assignments[ $index ] : null;

											echo CS()->html->select( array(
												'name'             => '_cs_bundled_products_conditions['. $index .']',
												'id'               => 'cs_bundled_products_conditions_'. esc_attr( $index ),
												'class'            => 'cs_repeatable_condition_field',
												'options'          => $options,
												'show_option_none' => false,
												'selected'         => $selected
											) );
										?>
										</div>
									</div>
									<div class="cs-bundled-product-actions">
										<a class="cs-remove-row cs-delete" data-type="file"><?php printf( __( 'Remove', 'commercestore' ), $index ); ?><span class="screen-reader-text"><?php printf( __( 'Remove bundle option %s', 'commercestore' ), $index ); ?></span></a>
									</div>
									<?php do_action( 'cs_download_products_table_row', $post_id ); ?>
								</div>
							</div>
							<?php $index++; ?>
						<?php endforeach; ?>

					<?php else: ?>

						<div class="cs-bundle-products-header">
							<span class="cs-bundle-products-title"><?php printf( __( 'Bundled %s:', 'commercestore' ), cs_get_label_plural() ); ?></span>
						</div>
						<div class="cs_repeatable_product_wrapper cs_repeatable_row" data-key="1">
							<div class="cs-bundled-product-row<?php echo $variable_class; ?>">

								<div class="cs-bundled-product-item-reorder">
									<span class="cs-product-file-reorder cs-draghandle-anchor dashicons dashicons-move" title="<?php printf( __( 'Click and drag to re-order bundled %s', 'commercestore' ), cs_get_label_plural() ); ?>"></span>
									<input type="hidden" name="cs_bundled_products[1][index]" class="cs_repeatable_index" value="1"/>
								</div>
								<div class="cs-form-group cs-bundled-product-item">
									<label class="cs-form-group__label cs-repeatable-row-setting-label" for="cs_bundled_products_1"><?php printf( esc_html__( 'Select %s:', 'commercestore' ), cs_get_label_singular() ); ?></label>
									<div class="cs-form-group__control">
									<?php
									echo CS()->html->product_dropdown( array(
										'name'                 => '_cs_bundled_products[]',
										'id'                   => 'cs_bundled_products_1',
										'multiple'             => false,
										'chosen'               => true,
										'bundles'              => false,
										'variations'           => true,
										'show_variations_only' => true,
									) );
									?>
									</div>
								</div>
								<div class="cs-form-group cs-bundled-product-price-assignment pricing" style="<?php echo $variable_display; ?>">
									<label class="cs-form-group__label cs-repeatable-row-setting-label" for="cs_bundled_products_conditions_1"><?php esc_html_e( 'Price assignment:', 'commercestore' ); ?></label>
									<div class="cs-form-group__control">
									<?php
										$options = array();

										if ( $prices ) {
											foreach ( $prices as $price_key => $price ) {
												$options[ $price_key ] = $prices[ $price_key ]['name'];
											}
										}

										$price_assignments = cs_get_bundle_pricing_variations( $post_id );

										echo CS()->html->select( array(
											'name'             => '_cs_bundled_products_conditions[1]',
											'id'               => 'cs_bundled_products_conditions_1',
											'class'            => 'cs-form-group__input cs_repeatable_condition_field',
											'options'          => $options,
											'show_option_none' => false,
											'selected'         => null,
										) );
									?>
									</div>
								</div>
								<div class="cs-bundled-product-actions">
									<a class="cs-remove-row cs-delete" data-type="file" ><?php printf( __( 'Remove', 'commercestore' ) ); ?><span class="screen-reader-text"><?php __( 'Remove bundle option 1', 'commercestore' ); ?></span></a>
								</div>
								<?php do_action( 'cs_download_products_table_row', $post_id ); ?>
							</div>
						</div>

					<?php endif; ?>

				</div>

				<div class="cs-add-repeatable-row">
					<button class="button-secondary cs_add_repeatable"><?php _e( 'Add New File', 'commercestore' ); ?></button>
				</div>
			</div>
		</div>
	</div>
<?php
}
add_action( 'cs_meta_box_files_fields', 'cs_render_products_field', 10 );

/**
 * File Downloads section.
 *
 * Outputs a table of all current files. Extensions can add column heads to the table
 * via the `cs_download_file_table_head` hook, and actual columns via
 * `cs_download_file_table_row`
 *
 * @since 1.0
 * @see cs_render_file_row()
 * @param int $post_id Download (Post) ID
 * @return void
 */
function cs_render_files_field( $post_id = 0 ) {
	$type    = cs_get_download_type( $post_id );
	$files   = cs_get_download_files( $post_id );
	$display = $type == 'bundle' ? ' style="display:none;"' : ''; ?>

	<div id="cs_download_files"<?php echo $display; ?>>
		<div id="cs_file_fields_default" class="cs_meta_table_wrap">
			<div class="widefat cs_repeatable_table">

				<div class="cs-file-fields cs-repeatables-wrap">
					<?php

					if ( ! empty( $files ) && is_array( $files ) ) :
						foreach ( $files as $key => $value ) :
							$index          = isset( $value['index'] )          ? $value['index']                   : $key;
							$name           = isset( $value['name'] )           ? $value['name']                    : '';
							$file           = isset( $value['file'] )           ? $value['file']                    : '';
							$condition      = isset( $value['condition'] )      ? $value['condition']               : false;
							$thumbnail_size = isset( $value['thumbnail_size'] ) ? $value['thumbnail_size']          : '';
							$attachment_id  = isset( $value['attachment_id'] )  ? absint( $value['attachment_id'] ) : false;

							$args = apply_filters( 'cs_file_row_args', compact( 'name', 'file', 'condition', 'attachment_id', 'thumbnail_size' ), $value ); ?>

							<div class="cs_repeatable_upload_wrapper cs_repeatable_row" data-key="<?php echo esc_attr( $key ); ?>">
								<?php do_action( 'cs_render_file_row', $key, $args, $post_id, $index ); ?>
							</div>

							<?php
						endforeach;
					else : ?>

						<div class="cs_repeatable_upload_wrapper cs_repeatable_row">
							<?php do_action( 'cs_render_file_row', 1, array(), $post_id, 0 ); ?>
						</div>

					<?php endif; ?>

				</div>

				<div class="cs-add-repeatable-row">
					<button class="button-secondary cs_add_repeatable"><?php _e( 'Add New File', 'commercestore' ); ?></button>
				</div>
			</div>
		</div>
	</div>
<?php
}
add_action( 'cs_meta_box_files_fields', 'cs_render_files_field', 20 );


/**
 * Individual file row.
 *
 * Used to output a table row for each file associated with a download.
 * Can be called directly, or attached to an action.
 *
 * @since 1.2.2
 * @param string $key Array key
 * @param array $args Array of all the arguments passed to the function
 * @param int $post_id Download (Post) ID
 * @return void
 */
function cs_render_file_row( $key, $args, $post_id, $index ) {

	$args = wp_parse_args( $args, array(
		'name'           => null,
		'file'           => null,
		'condition'      => null,
		'attachment_id'  => null,
		'thumbnail_size' => null,
	) );

	$prices           = cs_get_variable_prices( $post_id );
	$variable_pricing = cs_has_variable_prices( $post_id );
	$variable_display = $variable_pricing ? '' : ' style="display:none;"';
	$variable_class   = $variable_pricing ? ' has-variable-pricing' : ''; ?>

	<div class="cs-repeatable-row-header cs-draghandle-anchor">
		<span class="cs-repeatable-row-title" title="<?php _e( 'Click and drag to re-order files', 'commercestore' ); ?>">
			<?php printf( __( '%1$s file ID: %2$s', 'commercestore' ), cs_get_label_singular(), '<span class="cs_file_id">' . esc_html( $key ) . '</span>' ); ?>
			<input type="hidden" name="cs_download_files[<?php echo esc_attr( $key ); ?>][index]" class="cs_repeatable_index" value="<?php echo esc_attr( $index ); ?>"/>
		</span>
		<span class="cs-repeatable-row-actions">
			<a class="cs-remove-row cs-delete" data-type="file">
				<?php _e( 'Remove', 'commercestore' ); ?><span class="screen-reader-text"><?php printf( __( 'Remove file %s', 'commercestore' ), $key ); ?></span>
			</a>
		</span>
	</div>

	<div class="cs-repeatable-row-standard-fields<?php echo $variable_class; ?>">
		<div class="cs-form-group cs-file-name">
			<label for="cs_download_files-<?php echo esc_attr( $key ); ?>-name" class="cs-form-group__label cs-repeatable-row-setting-label"><?php esc_html_e( 'File Name', 'commercestore' ); ?></label>
			<div class="cs-form-group__control">
			<input type="hidden" name="cs_download_files[<?php echo absint( $key ); ?>][attachment_id]" class="cs_repeatable_attachment_id_field" value="<?php echo esc_attr( absint( $args['attachment_id'] ) ); ?>"/>
			<input type="hidden" name="cs_download_files[<?php echo absint( $key ); ?>][thumbnail_size]" class="cs_repeatable_thumbnail_size_field" value="<?php echo esc_attr( $args['thumbnail_size'] ); ?>"/>
			<?php echo CS()->html->text( array(
				'name'        => 'cs_download_files[' . $key . '][name]',
				'id'          => 'cs_download_files-' . $key . '-name',
				'value'       => $args['name'],
				'placeholder' => __( 'My Neat File', 'commercestore' ),
				'class'       => 'cs-form-group__input cs_repeatable_name_field large-text',
			) ); ?>
			</div>
		</div>

		<div class="cs-form-group cs-file-url">
			<label for="cs_download_files-<?php echo esc_attr( $key ); ?>-file" class="cs-form-group__label cs-repeatable-row-setting-label"><?php esc_html_e( 'File URL', 'commercestore' ); ?></label>
			<div class="cs-form-group__control cs_repeatable_upload_field_container">
				<?php echo CS()->html->text( array(
					'name'        => 'cs_download_files[' . $key . '][file]',
					'id'          => 'cs_download_files-' . $key . '-file',
					'value'       => $args['file'],
					'placeholder' => __( 'Enter, upload, choose from Media Library', 'commercestore' ),
					'class'       => 'cs-form-group__input cs_repeatable_upload_field cs_upload_field large-text',
				) ); ?>

				<span class="cs_upload_file">
					<button data-uploader-title="<?php esc_attr_e( 'Select Files', 'commercestore' ); ?>" data-uploader-button-text="<?php esc_attr_e( 'Select', 'commercestore' ); ?>" class="cs_upload_file_button" onclick="return false;">
						<span class="dashicons dashicons-admin-links"></span>
						<span class="screen-reader-text"><?php esc_html_e( 'Select Files', 'commercestore' ); ?></span>
				</button>
				</span>
			</div>
		</div>

		<div class="cs-form-group cs-file-assignment pricing"<?php echo $variable_display; ?>>

			<label for="cs_download_files_<?php echo esc_attr( $key ); ?>_condition" class="cs-form-group__label cs-repeatable-row-setting-label"><?php esc_html_e( 'Price Assignment', 'commercestore' ); ?><span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( '<strong>Price Assignment</strong>: With variable pricing enabled, you can choose to allow certain price variations access to specific files, or allow all price variations to access a file.', 'commercestore' ); ?>"></span></label>
			<div class="cs-form-group__control">
			<?php
				$options = array();

				if ( ! empty( $prices ) ) {
					foreach ( $prices as $price_key => $price ) {
						$options[ $price_key ] = $prices[ $price_key ]['name'];
					}
				}

				echo CS()->html->select( array(
					'name'             => 'cs_download_files[' . $key . '][condition]',
					'id'               => 'cs_download_files-' . $key . '-condition',
					'class'            => 'cs-form-group__input cs_repeatable_condition_field',
					'options'          => $options,
					'selected'         => $args['condition'],
					'show_option_none' => false,
				) );
			?>
			</div>
		</div>

		<?php do_action( 'cs_download_file_table_row', $post_id, $key, $args ); ?>

	</div>
<?php
}
add_action( 'cs_render_file_row', 'cs_render_file_row', 10, 4 );

/**
 * Alter the Add to post button in the media manager for downloads
 *
 * @since  2.2
 * @param  array $strings Array of default strings for media manager
 * @return array          The altered array of strings for media manager
 */
function cs_download_media_strings( $strings ) {
	global $post;

	if ( empty( $post ) || ( $post->post_type !== 'download' ) ) {
		return $strings;
	}

	$downloads_object = get_post_type_object( 'download' );
	$labels           = $downloads_object->labels;

	$strings['insertIntoPost'] = sprintf( __( 'Insert into %s', 'commercestore' ), strtolower( $labels->singular_name ) );

	return $strings;
}
add_filter( 'media_view_strings', 'cs_download_media_strings', 10, 1 );

/**
 * Refund Window
 *
 * The refund window is the maximum number of days each
 * can be downloaded by the buyer
 *
 * @since 3.0
 * @param int $post_id Download (Post) ID
 * @return void
 */
function cs_render_refund_row( $post_id ) {

	// Bail if user cannot manage shop settings
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	$types             = cs_get_refundability_types();
	$global_ability    = cs_get_option( 'refundability', 'refundable' );
	$refundability     = cs_get_download_refundability( $post_id );
	$global_window     = cs_get_option( 'refund_window', 30 );
	$cs_refund_window = cs_get_download_refund_window( $post_id ); ?>

	<div class="cs-form-group cs-product-options-wrapper">
		<div class="cs-product-options__title">
				<?php esc_html_e( 'Refunds', 'commercestore' ); ?>
				<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php echo wp_kses( __( '<strong>Refundable</strong>: Allow or disallow refunds for this specific product. When allowed, the refund window will be used on all future purchases.<br /><strong>Refund Window</strong>: Limit the number of days this product can be refunded after purchasing.', 'commercestore' ), array( 'strong' => true, 'br' => true ) ); ?>"></span>
		</div>

		<div class="cs-form-group__control">
			<label for="cs_refundability" class="cs-form-group__label">
				<?php esc_html_e( 'Refund Status', 'commercestore' ); ?>
			</label>
			<?php echo CS()->html->select( array(
				'name'             => '_cs_refundability',
				'id'               => 'cs_refundability',
				'class'            => 'cs-form-group__input',
				'options'          => array_merge(
					// Manually define a "none" option to set a blank value, vs. -1.
					array(
						'' => sprintf(
							/* translators: Default refund status */
							esc_html_x( 'Default (%1$s)', 'Download refund status', 'commercestore' ),
							ucwords( $refundability )
						),
					),
					$types
				),
				// Use the direct meta value to avoid falling back to default.
				'selected'         => get_post_meta( $post_id, '_cs_refundability', true ),
				'show_option_all'  => '',
				'show_option_none' => false,
			) ); ?>
		</div>

		<div class="cs-form-group__control">
			<label for="_cs_refund_window" class="cs-form-group__label">
				<?php esc_html_e( 'Refund Window', 'commercestore' ); ?>
			</label>
			<input class="cs-form-group__input small-text" id="_cs_refund_window" name="_cs_refund_window" type="number" min="0" max="3650" step="1" value="<?php echo esc_attr( $cs_refund_window ); ?>" placeholder="<?php echo absint( $global_window ); ?>" />
			<?php echo esc_html( _x( 'Days', 'refund window interval', 'commercestore' ) ); ?>
		</div>
		<p class="cs-form-group__help description">
			<?php _e( 'Leave blank to use global setting. Enter <code>0</code> for unlimited', 'commercestore' ); ?>
		</p>
	</div>
<?php
}
add_action( 'cs_meta_box_settings_fields', 'cs_render_refund_row', 25 );

/**
 * File Download Limit Row
 *
 * The file download limit is the maximum number of times each file
 * can be downloaded by the buyer
 *
 * @since 1.3.1
 * @param int $post_id Download (Post) ID
 * @return void
 */
function cs_render_download_limit_row( $post_id ) {
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	$cs_download_limit = cs_get_file_download_limit( $post_id );
	$display = 'bundle' == cs_get_download_type( $post_id ) ? ' style="display: none;"' : '';
?>
	<div class="cs-form-group cs-product-options-wrapper" id="cs_download_limit_wrap"<?php echo $display; ?>>
		<div class="cs-form-group__control">
			<label class="cs-form-group__label cs-product-options__title" for="cs_download_limit">
				<?php esc_html_e( 'File Download Limit', 'commercestore' ); ?><span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( '<strong>File Download Limit</strong>: Limit the number of times a customer who purchased this product can access their download links.', 'commercestore' ); ?>"></span>
			</label>
			<input class="cs-form-group__input small-text" name="_cs_download_limit" id="cs_download_limit" type="number" min="0" max="5000" step="1" value="<?php echo esc_attr ( $cs_download_limit ); ?>" />
		</div>
		<p class="cs-form-group__help description">
			<?php _e( 'Leave blank to use global setting. Enter <code>0</code> for unlimited', 'commercestore' ); ?>
		</p>
	</div>
<?php
}
add_action( 'cs_meta_box_settings_fields', 'cs_render_download_limit_row', 20 );

/**
 * Product tax settings
 *
 * Outputs the option to mark whether a product is exclusive of tax
 *
 * @since 1.9
 * @param int $post_id Download (Post) ID
 * @return void
 */
function cs_render_dowwn_tax_options( $post_id = 0 ) {
	cs_render_down_tax_options( $post_id );
}

/**
 * Product tax settings
 *
 * Outputs the option to mark whether a product is exclusive of tax
 *
 * @since 1.9
 * @since 2.8.12 Fixed miss-spelling in function name. See https://github.com/commercestore/commercestore/issues/5101
 * @param int $post_id Download (Post) ID
 * @return void
 */
function cs_render_down_tax_options( $post_id = 0 ) {

	// Bail if current user cannot view shop reports, or taxes are disabled,
	if ( ! current_user_can( 'view_shop_reports' ) || ! cs_use_taxes() ) {
		return;
	}

	$exclusive = cs_download_is_tax_exclusive( $post_id ); ?>

	<div class="cs-form-group cs-product-options-wrapper">
		<div class="cs-product-options__title"><?php esc_html_e( 'Taxability', 'commercestore' ); ?><span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( '<strong>Taxability</strong>: When taxes are enabled, all products are taxable by default. Check this box to mark this product as non-taxable.', 'commercestore' ); ?>"></span></div>
		<div class="cs-form-group__control">
			<?php echo CS()->html->checkbox(
				array(
					'name'    => '_cs_download_tax_exclusive',
					'id'      => '_cs_download_tax_exclusive',
					'current' => $exclusive,
					'class'   => 'cs-form-group__input',
				)
			); ?>
			<label for="_cs_download_tax_exclusive" class="cs-form-group__label">
				<?php esc_html_e( 'This product is non-taxable', 'commercestore' ); ?>
			</label>
		</div>
	</div>
	<?php
}
add_action( 'cs_meta_box_settings_fields', 'cs_render_down_tax_options', 30 );

/**
 * Product quantity settings
 *
 * Outputs the option to disable quantity field on product.
 *
 * @since 2.7
 * @param int $post_id Download (Post) ID
 * @return void
 */
function cs_render_download_quantity_option( $post_id = 0 ) {
	if ( ! current_user_can( 'manage_shop_settings' ) || ! cs_item_quantities_enabled() ) {
		return;
	}

	$disabled = cs_download_quantities_disabled( $post_id ); ?>

	<div class="cs-form-group cs-product-options-wrapper">
		<div class="cs-product-options__title"><?php esc_html_e( 'Item Quantities', 'commercestore' ); ?><span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( '<strong>Item Quantities</strong>: if disabled, customers will not be provided an option to change the number they wish to purchase.', 'commercestore' ); ?>"></span></div>
		<div class="cs-form-group__control">
			<?php
			echo CS()->html->checkbox(
				array(
					'name'    => '_cs_quantities_disabled',
					'id'      => '_cs_quantities_disabled',
					'current' => $disabled,
					'class'   => 'cs-form-group__input',
				)
			);
			?>
			<label for="_cs_quantities_disabled" class="cs-form-group__label">
				<?php esc_html_e( 'Disable quantity input for this product', 'commercestore' ); ?>
			</label>
		</div>
	</div>

<?php
}
add_action( 'cs_meta_box_settings_fields', 'cs_render_download_quantity_option', 30 );

/**
 * Add shortcode to settings meta box
 *
 * @since 2.5
 *
 * @return void
 */
function cs_render_meta_box_shortcode() {

	if ( get_post_type() !== 'download' ) {
		return;
	}

	$purchase_text = cs_get_option( 'add_to_cart_text', __( 'Purchase', 'commercestore' ) );
	$style         = cs_get_option( 'button_style', 'button' );
	$color         = cs_get_option( 'checkout_color', 'blue' );
	$color         = ( $color == 'inherit' ) ? '' : $color;
	$shortcode     = '[purchase_link id="' . absint( get_the_ID() ) . '" text="' . esc_html( $purchase_text ) . '" style="' . $style . '" color="' . esc_attr( $color ) . '"]'; ?>

	<div class="cs-form-group cs-product-options-wrapper">
		<div class="cs-form-group__control">
			<label class="cs-form-group__label cs-product-options__title" for="cs-purchase-shortcode">
				<?php esc_html_e( 'Purchase Shortcode', 'commercestore' ); ?><span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( '<strong>Purchase Shortcode</strong>: Use this shortcode to output a purchase link for this product in the location of your choosing.', 'commercestore' ); ?>"></span>
			</label>
			<input type="text" id="cs-purchase-shortcode" class="cs-form-group__input" readonly value="<?php echo htmlentities( $shortcode ); ?>">
		</div>
	</div>
	<?php
}
add_action( 'cs_meta_box_settings_fields', 'cs_render_meta_box_shortcode', 35 );

/**
 * Render Accounting Options
 *
 * @since 1.6
 * @param int $post_id Download (Post) ID
 * @return void
 */
function cs_render_accounting_options( $post_id ) {
	if ( ! cs_use_skus() ) {
		return;
	}

	$cs_sku = get_post_meta( $post_id, 'cs_sku', true ); ?>

	<div class="cs-form-group cs-product-options-wrapper">
		<div class="cs-product-options__title"><?php esc_html_e( 'Accounting Options', 'commercestore' ); ?><span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( '<strong>SKU</strong>: If an SKU is entered for this product, it will be shown on the purchase receipt and exported purchase histories.', 'commercestore' ); ?>"></span></div>
		<div class="cs-form-group__control">
			<label class="cs-form-group__label" for="cs_sku">
				<?php esc_html_e( 'Enter an SKU for this product.', 'commercestore' ); ?>
			</label>
			<?php echo CS()->html->text(
				array(
					'name'  => 'cs_sku',
					'id'    => 'cs_sku',
					'value' => $cs_sku,
					'class' => 'cs-form-group__input small-text',
				)
			);
			?>
		</div>
	</div>
<?php
}
add_action( 'cs_meta_box_settings_fields', 'cs_render_accounting_options', 25 );


/**
 * Render Disable Button
 *
 * @since 1.0
 * @param int $post_id Download (Post) ID
 * @return void
 */
function cs_render_disable_button( $post_id ) {
	$supports_buy_now           = cs_shop_supports_buy_now();
	$hide_button                = get_post_meta( $post_id, '_cs_hide_purchase_link', true ) ? 1 : 0;
	$behavior                   = get_post_meta( $post_id, '_cs_button_behavior',    true );
	$buy_now_support_tooltip    = __( '<strong>Purchase button behavior</strong>: Add to Cart buttons follow a traditional eCommerce flow. A Buy Now button bypasses most of the process, taking the customer directly from button click to payment, greatly speeding up the process of buying the product.', 'commercestore' );
	$no_buy_now_support_tooltip = __( '<strong>Purchase button behavior</strong>: Add to Cart buttons follow a traditional eCommerce flow. Buy Now buttons are only available for stores that have a single supported gateway active and that do not use taxes.', 'commercestore' );
	?>

	<div class="cs-form-group cs-product-options-wrapper">
		<div class="cs-product-options__title"><?php esc_html_e( 'Button Options', 'commercestore' ); ?><span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php _e( '<strong>Hide purchase button</strong>: By default, the purchase buttons will be displayed at the bottom of the download, when disabled you will need to use the Purchase link shortcode below to output the ability to buy the product where you prefer.', 'commercestore' ); echo '<br><br>'; echo ! empty( $supports_buy_now ) ? $buy_now_support_tooltip : $no_buy_now_support_tooltip; ?>"></span></div>
		<div class="cs-form-group__control">
			<?php echo CS()->html->checkbox(
				array(
					'name'    => '_cs_hide_purchase_link',
					'id'      => '_cs_hide_purchase_link',
					'current' => $hide_button,
					'class'   => 'cs-form-group__input',
				)
			);
			?>
			<label class="cs-form-group__label" for="_cs_hide_purchase_link">
				<?php esc_html_e( 'Hide purchase button', 'commercestore' ); ?>
			</label>
		</div>
		<?php if ( ! empty( $supports_buy_now ) ) { ?>
			<div class="cs-form-group__control">
				<label for="cs_button_behavior" class="cs-form-group__label">
					<?php esc_html_e( 'Purchase button behavior', 'commercestore' ); ?>
				</label>
				<?php
				$args = array(
					'name'             => '_cs_button_behavior',
					'id'               => 'cs_button_behavior',
					'selected'         => $behavior,
					'options'          => array(
						'add_to_cart' => __( 'Add to Cart', 'commercestore' ),
						'direct'      => __( 'Buy Now', 'commercestore' ),
					),
					'show_option_all'  => null,
					'show_option_none' => null,
					'class'            => 'cs-form-group__input',
				);
				echo CS()->html->select( $args );
				?>
			</div>
			<?php
		}
		?>
	</div>

<?php
}
add_action( 'cs_meta_box_settings_fields', 'cs_render_disable_button', 30 );


/** Product Notes *************************************************************/

/**
 * Product Notes Meta Box
 *
 * Renders the Product Notes meta box
 *
 * @since 1.2.1
 *
 * @return void
 */
function cs_render_product_notes_meta_box() {
	do_action( 'cs_product_notes_meta_box_fields', get_the_ID() );
}

/**
 * Render Product Notes Field
 *
 * @since 1.2.1
 * @param int $post_id Download (Post) ID
 * @return void
 */
function cs_render_product_notes_field( $post_id ) {
	$product_notes = cs_get_product_notes( $post_id );
	?>
	<div class="cs-form-group">
		<div class="cs-form-group__control">
			<label for="cs_product_notes_field" class="cs-form-group__label screen-reader-text"><?php esc_html_e( 'Download Instructions', 'commercestore' ); ?></label>
			<textarea rows="1" cols="40" class="cs-form-group__input large-textarea" name="cs_product_notes" id="cs_product_notes_field"><?php echo esc_textarea( $product_notes ); ?></textarea>
		</div>
		<p><?php printf( esc_html__( 'Special instructions for this %s. These will be added to the purchase receipt, and may be used by some extensions or themes.', 'commercestore' ), cs_get_label_singular() ); ?></p>
	</div>
	<?php
}
add_action( 'cs_product_notes_meta_box_fields', 'cs_render_product_notes_field' );


/** Stats *********************************************************************/

/**
 * Render Stats Meta Box
 *
 * @since 1.0
 * @return void
 */
function cs_render_stats_meta_box() {
	$post_id = get_the_ID();

	if ( ! current_user_can( 'view_product_stats', $post_id ) ) {
		return;
	}

	$earnings = cs_get_download_earnings_stats( $post_id );
	$sales    = cs_get_download_sales_stats( $post_id );

	$sales_url = add_query_arg( array(
		'page'       => 'cs-payment-history',
		'product-id' => urlencode( $post_id )
	), cs_get_admin_base_url() );

	$earnings_report_url = cs_get_admin_url( array(
		'page'     => 'cs-reports',
		'view'     => 'downloads',
		'products' => $post_id,
	) );
	?>

	<p class="product-sales-stats">
		<span class="label"><?php esc_html_e( 'Net Sales:', 'commercestore' ); ?></span>
		<span><a href="<?php echo esc_url( $sales_url ); ?>"><?php echo esc_html( $sales ); ?></a></span>
	</p>

	<p class="product-earnings-stats">
		<span class="label"><?php esc_html_e( 'Net Revenue:', 'commercestore' ); ?></span>
		<span><a href="<?php echo esc_url( $earnings_report_url ); ?>"><?php echo cs_currency_filter( cs_format_amount( $earnings ) ); ?></a></span>
	</p>

	<hr />

	<p class="file-download-log">
		<span><a href="<?php echo admin_url( 'edit.php?page=cs-tools&view=file_downloads&post_type=download&tab=logs&download=' . $post_id ); ?>"><?php _e( 'View File Download Log', 'commercestore' ); ?></a></span><br/>
	</p>
<?php
	do_action('cs_stats_meta_box');
}

/**
 * Outputs a metabox for promotional content.
 *
 * @since 2.9.20
 * @return void
 */
function cs_render_promo_metabox() {
	ob_start();

	// Build the main URL for the promotion.
	$args = array(
		'utm_source'   => 'download-metabox',
		'utm_medium'   => 'wp-admin',
		'utm_campaign' => 'bfcm2019',
		'utm_content'  => 'bfcm-metabox',
	);
	$url  = add_query_arg( $args, 'https://commercestore.com/pricing/' );
	?>
	<p>
		<?php
		// Translators: The %s represents the link to the pricing page on the CommerceStore website.
		echo wp_kses_post( sprintf( __( 'Save 25&#37; on all CommerceStore purchases <strong>this week</strong>, including renewals and upgrades! Sale ends 23:59 PM December 6th CST. <a target="_blank" href="%s">Don\'t miss out</a>!', 'commercestore' ), $url ) );
		?>
	</p>
	<?php
	$rendered = ob_get_contents();
	ob_end_clean();

	echo wp_kses_post( $rendered );
}

/**
 * Internal use only: This is to help with https://github.com/commercestore/commercestore/issues/2704
 *
 * This function takes any hooked functions for cs_download_price_table_head and re-registers them into the cs_download_price_table_row
 * action. It will also de-register any original table_row data, so that labels appear before their setting, then re-registers the table_row.
 *
 * @since 2.8
 *
 * @param $arg1
 * @param $arg2
 * @param $arg3
 *
 * @return void
 */
function cs_hijack_cs_download_price_table_head( $arg1, $arg2, $arg3 ) {
	global $wp_filter;

	$found_fields  = isset( $wp_filter['cs_download_price_table_row'] )  ? $wp_filter['cs_download_price_table_row']  : false;
	$found_headers = isset( $wp_filter['cs_download_price_table_head'] ) ? $wp_filter['cs_download_price_table_head'] : false;

	$re_register = array();

	if ( ! $found_fields && ! $found_headers ) {
		return;
	}

	foreach ( $found_fields->callbacks as $priority => $callbacks ) {
		if ( -1 === $priority ) {
			continue; // Skip our -1 priority so we don't break the interwebs
		}

		if ( is_object( $found_headers ) && property_exists( $found_headers, 'callbacks' ) && array_key_exists( $priority, $found_headers->callbacks ) ) {

			// De-register any row data.
			foreach ( $callbacks as $callback ) {
				$re_register[ $priority ][] = $callback;
				remove_action( 'cs_download_price_table_row', $callback['function'], $priority, $callback['accepted_args'] );
			}

			// Register any header data.
			foreach( $found_headers->callbacks[ $priority ] as $callback ) {
				if ( is_callable( $callback['function'] ) ) {
					add_action( 'cs_download_price_table_row', $callback['function'], $priority, 1 );
				}
			}
		}

	}

	// Now that we've re-registered our headers first...re-register the inputs
	foreach ( $re_register as $priority => $callbacks ) {
		foreach ( $callbacks as $callback ) {
			add_action( 'cs_download_price_table_row', $callback['function'], $priority, $callback['accepted_args'] );
		}
	}
}
add_action( 'cs_download_price_table_row', 'cs_hijack_cs_download_price_table_head', -1, 3 );
