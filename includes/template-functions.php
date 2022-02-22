<?php
/**
 * Template Functions
 *
 * @package     CS
 * @subpackage  Functions/Templates
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Append Purchase Link
 *
 * Automatically appends the purchase link to download content, if enabled.
 *
 * @since 1.0
 * @param int $download_id Download ID
 * @return void
 */

function cs_append_purchase_link( $download_id ) {
	if ( ! get_post_meta( $download_id, '_cs_hide_purchase_link', true ) ) {
		echo cs_get_purchase_link( array( 'download_id' => $download_id ) );
	}
}
add_action( 'cs_after_download_content', 'cs_append_purchase_link' );


/**
 * Get Purchase Link
 *
 * Builds a Purchase link for a specified download based on arguments passed.
 * This function is used all over CommerceStore to generate the Purchase or Add to Cart
 * buttons. If no arguments are passed, the function uses the defaults that have
 * been set by the plugin. The Purchase link is built for simple and variable
 * pricing and filters are available throughout the function to override
 * certain elements of the function.
 *
 * $download_id = null, $link_text = null, $style = null, $color = null, $class = null
 *
 * @since 1.0
 * @param array $args Arguments for display
 * @return string $purchase_form
 */
function cs_get_purchase_link( $args = array() ) {
	global $post, $cs_displayed_form_ids;

	$purchase_page = cs_get_option( 'purchase_page', false );
	if ( ! $purchase_page || $purchase_page == 0 ) {

		global $no_checkout_error_displayed;
		if ( ! is_null( $no_checkout_error_displayed ) ) {
			return false;
		}


		cs_set_error( 'set_checkout', sprintf( __( 'No checkout page has been configured. Visit <a href="%s">Settings</a> to set one.', 'commercestore' ), admin_url( 'edit.php?post_type=download&page=cs-settings&tab=general&section=pages' ) ) );
		cs_print_errors();

		$no_checkout_error_displayed = true;

		return false;

	}

	$post_id = is_object( $post ) ? $post->ID : 0;
	$button_behavior = cs_get_download_button_behavior( $post_id );

	$defaults = apply_filters( 'cs_purchase_link_defaults', array(
		'download_id' => $post_id,
		'price'       => (bool) true,
		'price_id'    => isset( $args['price_id'] ) ? $args['price_id'] : false,
		'direct'      => $button_behavior == 'direct' ? true : false,
		'text'        => $button_behavior == 'direct' ? cs_get_option( 'buy_now_text', __( 'Buy Now', 'commercestore' ) ) : cs_get_option( 'add_to_cart_text', __( 'Purchase', 'commercestore' ) ),
		'checkout'    => cs_get_option( 'checkout_button_text', _x( 'Checkout', 'text shown on the Add to Cart Button when the product is already in the cart', 'commercestore' ) ),
		'style'       => cs_get_option( 'button_style', 'button' ),
		'color'       => cs_get_option( 'checkout_color', 'blue' ),
		'class'       => 'cs-submit'
	) );

	$args = wp_parse_args( $args, $defaults );

	// Override the straight_to_gateway if the shop doesn't support it
	if ( ! cs_shop_supports_buy_now() ) {
		$args['direct'] = false;
	}

	$download = new CS_Download( $args['download_id'] );

	if( empty( $download->ID ) ) {
		return false;
	}

	if( 'publish' !== $download->post_status && ! current_user_can( 'edit_product', $download->ID ) ) {
		return false; // Product not published or user doesn't have permission to view drafts
	}

	// Override color if color == inherit
	$args['color'] = ( $args['color'] == 'inherit' ) ? '' : $args['color'];

	$options          = array();
	$variable_pricing = $download->has_variable_prices();
	$data_variable    = $variable_pricing ? ' data-variable-price="yes"' : 'data-variable-price="no"';
	$type             = $download->is_single_price_mode() ? 'data-price-mode=multi' : 'data-price-mode=single';

	$show_price       = $args['price'] && $args['price'] !== 'no';
	$data_price_value = 0;
	$price            = false;

	if ( $variable_pricing && false !== $args['price_id'] ) {

		$price_id            = $args['price_id'];
		$prices              = $download->prices;
		$options['price_id'] = $args['price_id'];
		$found_price         = isset( $prices[$price_id] ) ? $prices[$price_id]['amount'] : false;

		$data_price_value    = $found_price;

		if ( $show_price ) {
			$price = $found_price;
		}

	} elseif ( ! $variable_pricing ) {

		$data_price_value = $download->price;

		if ( $show_price ) {
			$price = $download->price;
		}

	}

	$data_price  = 'data-price="' . $data_price_value . '"';

	$button_text = ! empty( $args['text'] ) ? '&nbsp;&ndash;&nbsp;' . $args['text'] : '';

	if ( false !== $price ) {

		if ( 0 == $price ) {
			$args['text'] = __( 'Free', 'commercestore' ) . $button_text;
		} else {
			$args['text'] = cs_currency_filter( cs_format_amount( $price ) ) . $button_text;
		}

	}

	if ( cs_item_in_cart( $download->ID, $options ) && ( ! $variable_pricing || ! $download->is_single_price_mode() ) ) {
		$button_display   = 'style="display:none;"';
		$checkout_display = '';
	} else {
		$button_display   = '';
		$checkout_display = 'style="display:none;"';
	}

	// Collect any form IDs we've displayed already so we can avoid duplicate IDs
	if ( isset( $cs_displayed_form_ids[ $download->ID ] ) ) {
		$cs_displayed_form_ids[ $download->ID ]++;
	} else {
		$cs_displayed_form_ids[ $download->ID ] = 1;
	}

	$form_id = ! empty( $args['form_id'] ) ? $args['form_id'] : 'cs_purchase_' . $download->ID;

	// If we've already generated a form ID for this download ID, append -#
	if ( $cs_displayed_form_ids[ $download->ID ] > 1 ) {
		$form_id .= '-' . $cs_displayed_form_ids[ $download->ID ];
	}

	$args = apply_filters( 'cs_purchase_link_args', $args );

	ob_start();
?>
	<form id="<?php echo $form_id; ?>" class="cs_download_purchase_form cs_purchase_<?php echo absint( $download->ID ); ?>" method="post">

		<?php do_action( 'cs_purchase_link_top', $download->ID, $args ); ?>

		<div class="cs_purchase_submit_wrapper">
			<?php
			$class = implode( ' ', array( $args['style'], $args['color'], trim( $args['class'] ) ) );

			if ( ! cs_is_ajax_disabled() ) {
				$timestamp = time();
				echo '<a href="#" class="cs-add-to-cart ' . esc_attr( $class ) . '" data-nonce="' .  wp_create_nonce( 'cs-add-to-cart-' . $download->ID ) . '" data-timestamp="' . esc_attr( $timestamp ) . '" data-token="' . esc_attr( CS\Utils\Tokenizer::tokenize( $timestamp ) ) . '" data-action="cs_add_to_cart" data-download-id="' . esc_attr( $download->ID ) . '" ' . $data_variable . ' ' . $type . ' ' . $data_price . ' ' . $button_display . '><span class="cs-add-to-cart-label">' . $args['text'] . '</span> <span class="cs-loading" aria-label="' . esc_attr__( 'Loading', 'commercestore' ) . '"></span></a>';

			}

			echo '<input type="submit" class="cs-add-to-cart cs-no-js ' . esc_attr( $class ) . '" name="cs_purchase_download" value="' . esc_attr( $args['text'] ) . '" data-action="cs_add_to_cart" data-download-id="' . esc_attr( $download->ID ) . '" ' . $data_variable . ' ' . $type . ' ' . $button_display . '/>';
			echo '<a href="' . esc_url( cs_get_checkout_uri() ) . '" class="cs_go_to_checkout ' . esc_attr( $class ) . '" ' . $checkout_display . '>' . $args['checkout'] . '</a>';
			?>

			<?php if ( ! cs_is_ajax_disabled() ) : ?>
				<span class="cs-cart-ajax-alert" aria-live="assertive">
					<span class="cs-cart-added-alert" style="display: none;">
						<svg class="cs-icon cs-icon-check" xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 28 28" aria-hidden="true">
							<path d="M26.11 8.844c0 .39-.157.78-.44 1.062L12.234 23.344c-.28.28-.672.438-1.062.438s-.78-.156-1.06-.438l-7.782-7.78c-.28-.282-.438-.673-.438-1.063s.156-.78.438-1.06l2.125-2.126c.28-.28.672-.438 1.062-.438s.78.156 1.062.438l4.594 4.61L21.42 5.656c.282-.28.673-.438 1.063-.438s.78.155 1.062.437l2.125 2.125c.28.28.438.672.438 1.062z"/>
						</svg>
						<?php echo __( 'Added to cart', 'commercestore' ); ?>
					</span>
				</span>
			<?php endif; ?>
			<?php if( ! $download->is_free( $args['price_id'] ) && ! cs_download_is_tax_exclusive( $download->ID ) ): ?>
				<?php if ( cs_display_tax_rate() && cs_prices_include_tax() ) {
					echo '<span class="cs_purchase_tax_rate">' . sprintf( __( 'Includes %1$s&#37; tax', 'commercestore' ), cs_get_formatted_tax_rate() ) . '</span>';
				} elseif ( cs_display_tax_rate() && ! cs_prices_include_tax() ) {
					echo '<span class="cs_purchase_tax_rate">' . sprintf( __( 'Excluding %1$s&#37; tax', 'commercestore' ), cs_get_formatted_tax_rate() ) . '</span>';
				} ?>
			<?php endif; ?>
		</div><!--end .cs_purchase_submit_wrapper-->

		<input type="hidden" name="download_id" value="<?php echo esc_attr( $download->ID ); ?>">
		<?php if ( $variable_pricing && isset( $price_id ) && isset( $prices[$price_id] ) ): ?>
			<input type="hidden" name="cs_options[price_id][]" id="cs_price_option_<?php echo esc_attr( $download->ID ); ?>_<?php echo esc_attr( $price_id ); ?>" class="cs_price_option_<?php echo esc_attr( $download->ID ); ?>" value="<?php echo esc_attr( $price_id ); ?>">
		<?php endif; ?>
		<?php if( ! empty( $args['direct'] ) && ! $download->is_free( $args['price_id'] ) ) { ?>
			<input type="hidden" name="cs_action" class="cs_action_input" value="straight_to_gateway">
		<?php } else { ?>
			<input type="hidden" name="cs_action" class="cs_action_input" value="add_to_cart">
		<?php } ?>

		<?php if( apply_filters( 'cs_download_redirect_to_checkout', cs_straight_to_checkout(), $download->ID, $args ) ) : ?>
			<input type="hidden" name="cs_redirect_to_checkout" id="cs_redirect_to_checkout" value="1">
		<?php endif; ?>

		<?php do_action( 'cs_purchase_link_end', $download->ID, $args ); ?>

	</form><!--end #<?php echo esc_attr( $form_id ); ?>-->
<?php
	$purchase_form = ob_get_clean();


	return apply_filters( 'cs_purchase_download_form', $purchase_form, $args );
}

/**
 * Variable price output
 *
 * Outputs variable pricing options for each download or a specified downloads in a list.
 * The output generated can be overridden by the filters provided or by removing
 * the action and adding your own custom action.
 *
 * @since 1.2.3
 * @param int $download_id Download ID
 * @return void
 */
function cs_purchase_variable_pricing( $download_id = 0, $args = array() ) {
	global $cs_displayed_form_ids;

	// If we've already generated a form ID for this download ID, append -#
	$form_id = '';
	if ( $cs_displayed_form_ids[ $download_id ] > 1 ) {
		$form_id .= '-' . $cs_displayed_form_ids[ $download_id ];
	}

	$variable_pricing = cs_has_variable_prices( $download_id );

	if ( ! $variable_pricing ) {
		return;
	}

	$prices = apply_filters( 'cs_purchase_variable_prices', cs_get_variable_prices( $download_id ), $download_id );

	// If the price_id passed is found in the variable prices, do not display all variable prices.
	if ( false !== $args['price_id'] && isset( $prices[ $args['price_id'] ] ) ) {
		return;
	}

	$type   = cs_single_price_option_mode( $download_id ) ? 'checkbox' : 'radio';
	$mode   = cs_single_price_option_mode( $download_id ) ? 'multi' : 'single';

	// Filter the class names for the cs_price_options div
	$css_classes_array = apply_filters( 'cs_price_options_classes', array(
		'cs_price_options',
		'cs_' . esc_attr( $mode ) . '_mode'
	), $download_id );

	// Sanitize those class names and form them into a string
	$css_classes_string = implode( ' ', array_map( 'sanitize_html_class', $css_classes_array ) );

	if ( cs_item_in_cart( $download_id ) && ! cs_single_price_option_mode( $download_id ) ) {
		return;
	}

	do_action( 'cs_before_price_options', $download_id ); ?>
	<div class="<?php echo esc_attr( rtrim( $css_classes_string ) ); ?>">
		<ul>
			<?php
			if ( $prices ) :
				$checked_key = isset( $_GET['price_option'] ) ? absint( $_GET['price_option'] ) : cs_get_default_variable_price( $download_id );
				foreach ( $prices as $key => $price ) :
					echo '<li id="cs_price_option_' . $download_id . '_' . sanitize_key( $price['name'] ) . $form_id . '">';
						echo '<label for="' . esc_attr( 'cs_price_option_' . $download_id . '_' . $key . $form_id ) . '">';
							echo '<input type="' . $type . '" ' . checked( apply_filters( 'cs_price_option_checked', $checked_key, $download_id, $key ), $key, false ) . ' name="cs_options[price_id][]" id="' . esc_attr( 'cs_price_option_' . $download_id . '_' . $key . $form_id ) . '" class="' . esc_attr( 'cs_price_option_' . $download_id ) . '" value="' . esc_attr( $key ) . '" data-price="' . cs_get_price_option_amount( $download_id, $key ) .'"/>&nbsp;';

							// Construct the default price output.
							$price_output = '<span class="cs_price_option_name">' . esc_html( $price['name'] ) . '</span><span class="cs_price_option_sep">&nbsp;&ndash;&nbsp;</span><span class="cs_price_option_price">' . cs_currency_filter( cs_format_amount( $price['amount'] ) ) . '</span>';

							$item_prop = ''; // Changed to an empty string due to migration from microdata to JSON-LD in CommerceStore 3.0

							// Filter the default price output
							$price_output = apply_filters( 'cs_price_option_output', $price_output, $download_id, $key, $price, $form_id, $item_prop );

							// Output the filtered price output
							echo $price_output;

						echo '</label>';
						do_action( 'cs_after_price_option', $key, $price, $download_id );
					echo '</li>';
				endforeach;
			endif;
			do_action( 'cs_after_price_options_list', $download_id, $prices, $type );
			?>
		</ul>
	</div><!--end .cs_price_options-->
<?php
	do_action( 'cs_after_price_options', $download_id );
}
add_action( 'cs_purchase_link_top', 'cs_purchase_variable_pricing', 10, 2 );

/**
 * Display the quantity field for a variable price when multi-purchase mode is enabled
 *
 * @since 2.2
 * @param int $download_id Download ID
 * @param array $args Argument array
 * @return void
 */
function cs_download_purchase_form_quantity_field( $download_id = 0, $args = array() ) {

	$options = array();
	if( false !== $args['price_id'] ) {
		$options['price_id'] = $args['price_id'];
	}

	if ( ! cs_item_quantities_enabled() || cs_download_quantities_disabled( $download_id ) ) {
		return;
	}

	if ( cs_item_in_cart( $download_id ) && ! cs_has_variable_prices( $download_id ) ) {
		return;
	}

	if ( cs_single_price_option_mode( $download_id ) && cs_has_variable_prices( $download_id ) && ! cs_item_in_cart( $download_id, $options ) ) {
		return;
	}

	if ( cs_single_price_option_mode( $download_id ) && cs_has_variable_prices( $download_id ) && cs_item_in_cart( $download_id, $options ) ) {
		return;
	}

	if ( ! cs_single_price_option_mode( $download_id ) && cs_has_variable_prices( $download_id ) && cs_item_in_cart( $download_id, $options ) ) {
		return;
	}

	ob_start();
?>
	<div class="cs_download_quantity_wrapper">
		<input type="number" min="1" step="1" name="cs_download_quantity" class="cs-input cs-item-quantity" value="1" />
	</div>
<?php
	$quantity_input = ob_get_clean();

	echo apply_filters( 'cs_purchase_form_quantity_input', $quantity_input, $download_id, $args );
}
add_action( 'cs_purchase_link_top', 'cs_download_purchase_form_quantity_field', 10, 2 );

/**
 * Display the quantity field for a variable price when multi-purchase mode is enabled
 *
 * @since 2.2
 * @param int $key Price ID
 * @param array $price price option array
 * @param int $download_id Download ID
 * @return void
 */
function cs_variable_price_quantity_field( $key, $price, $download_id ) {

	if( ! cs_item_quantities_enabled() || cs_download_quantities_disabled( $download_id ) ) {
		return;
	}

	if( ! cs_single_price_option_mode( $download_id ) ) {
		return;
	}

	ob_start();
?>
	<div class="cs_download_quantity_wrapper cs_download_quantity_price_option_<?php echo sanitize_key( $price['name'] ) ?>">
		<span class="cs_price_option_sep">&nbsp;x&nbsp;</span>
		<input type="number" min="1" step="1" name="cs_download_quantity_<?php echo esc_attr( $key ) ?>" class="cs-input cs-item-quantity" value="1" />
	</div>
<?php
	$quantity_input = ob_get_clean();

	echo apply_filters( 'cs_purchase_form_variation_quantity_input', $quantity_input, $download_id, $key, $price );
}
add_action( 'cs_after_price_option', 'cs_variable_price_quantity_field', 10, 3 );

/**
 * Before Download Content
 *
 * Adds an action to the beginning of download post content that can be hooked to
 * by other functions.
 *
 * @since 1.0.8
 * @global $post
 *
 * @param $content The the_content field of the download object
 * @return string the content with any additional data attached
 */
function cs_before_download_content( $content ) {
	global $post;

	if ( $post && $post instanceof WP_Post && CS_POST_TYPE === $post->post_type && is_singular( CS_POST_TYPE ) && is_main_query() && ! post_password_required() ) {
		ob_start();
		do_action( 'cs_before_download_content', $post->ID );
		$content = ob_get_clean() . $content;
	}

	return $content;
}
add_filter( 'the_content', 'cs_before_download_content' );

/**
 * After Download Content
 *
 * Adds an action to the end of download post content that can be hooked to by
 * other functions.
 *
 * @since 1.0.8
 * @global $post
 *
 * @param $content The the_content field of the download object
 * @return string the content with any additional data attached
 */
function cs_after_download_content( $content ) {
	global $post;

	if ( $post && $post->post_type == CS_POST_TYPE && is_singular( CS_POST_TYPE ) && is_main_query() && !post_password_required() ) {
		ob_start();
		do_action( 'cs_after_download_content', $post->ID );
		$content .= ob_get_clean();
	}

	return $content;
}
add_filter( 'the_content', 'cs_after_download_content' );

/**
 * Get Button Colors
 *
 * Returns an array of button colors.
 *
 * @since 1.0
 * @return array $colors Button colors
 */
function cs_get_button_colors() {
	$colors = array(
		'white'     => array(
			'label' => __( 'White', 'commercestore' ),
			'hex'   => '#ffffff'
		),
		'gray'      => array(
			'label' => __( 'Gray', 'commercestore' ),
			'hex'   => '#f0f0f0'
		),
		'blue'      => array(
			'label' => __( 'Blue', 'commercestore' ),
			'hex'   => '#428bca'
		),
		'red'       => array(
			'label' => __( 'Red', 'commercestore' ),
			'hex'   => '#d9534f'
		),
		'green'     => array(
			'label' => __( 'Green', 'commercestore' ),
			'hex'   => '#5cb85c'
		),
		'yellow'    => array(
			'label' => __( 'Yellow', 'commercestore' ),
			'hex'   => '#f0ad4e'
		),
		'orange'    => array(
			'label' => __( 'Orange', 'commercestore' ),
			'hex'   => '#ed9c28'
		),
		'dark-gray' => array(
			'label' => __( 'Dark Gray', 'commercestore' ),
			'hex'   => '#363636'
		),
		'inherit'	=> array(
			'label' => __( 'Inherit', 'commercestore' ),
			'hex'   => ''
		)
	);

	return apply_filters( 'cs_button_colors', $colors );
}

/**
 * Get Button Styles
 *
 * Returns an array of button styles.
 *
 * @since 1.2.2
 * @return array $styles Button styles
 */
function cs_get_button_styles() {
	$styles = array(
		'button'	=> __( 'Button', 'commercestore' ),
		'plain'     => __( 'Plain Text', 'commercestore' )
	);

	return apply_filters( 'cs_button_styles', $styles );
}

/**
 * Default formatting for download excerpts
 *
 * This excerpt is primarily used in the [downloads] shortcode
 *
 * @since 1.0.8.4
 * @param string $excerpt Content before filtering
 * @return string $excerpt Content after filtering
 * @return string
 */
function cs_downloads_default_excerpt( $excerpt ) {
	return do_shortcode( wpautop( $excerpt ) );
}
add_filter( 'cs_downloads_excerpt', 'cs_downloads_default_excerpt' );

/**
 * Default formatting for full download content
 *
 * This is primarily used in the [downloads] shortcode
 *
 * @since 1.0.8.4
 * @param string $content Content before filtering
 * @return string $content Content after filtering
 */
function cs_downloads_default_content( $content ) {
	return do_shortcode( wpautop( $content ) );
}
add_filter( 'cs_downloads_content', 'cs_downloads_default_content' );

/**
 * Gets the download links for each item purchased
 *
 * @since 1.1.5
 * @param int $payment_id The ID of the payment to retrieve download links for
 * @return string
 */
function cs_get_purchase_download_links( $payment_id = 0 ) {

	$downloads   = cs_get_payment_meta_cart_details( $payment_id, true );
	$payment_key = cs_get_payment_key( $payment_id );
	$email       = cs_get_payment_user_email( $payment_id );
	$links       = '<ul class="cs_download_links">';

	foreach ( $downloads as $download ) {
		$links .= '<li>';
			$links .= '<h3 class="cs_download_link_title">' . esc_html( get_the_title( $download['id'] ) ) . '</h3>';
			$price_id = isset( $download['options'] ) && isset( $download['options']['price_id'] ) ? $download['options']['price_id'] : null;
			$files    = cs_get_download_files( $download['id'], $price_id );
			if ( is_array( $files ) ) {
				foreach ( $files as $filekey => $file ) {
					$links .= '<div class="cs_download_link_file">';
						$links .= '<a href="' . esc_url( cs_get_download_file_url( $payment_key, $email, $filekey, $download['id'], $price_id ) ) . '">';
						$links .= cs_get_file_name( $file );
						$links .= '</a>';
					$links .= '</div>';
				}
			}
		$links .= '</li>';
	}

	$links .= '</ul>';

	return $links;
}

/**
 * Returns the path to the CommerceStore templates directory
 *
 * @since 1.2
 * @return string
 */
function cs_get_templates_dir() {
	return CS_PLUGIN_DIR . 'templates';
}

/**
 * Returns the URL to the CommerceStore templates directory
 *
 * @since 1.3.2.1
 * @return string
 */
function cs_get_templates_url() {
	return CS_PLUGIN_URL . 'templates';
}

/**
 * Retrieves a template part
 *
 * @since v1.2
 *
 * Taken from bbPress
 *
 * @param string $slug
 * @param string $name Optional. Default null
 * @param bool   $load
 *
 * @return string
 *
 * @uses cs_locate_template()
 * @uses load_template()
 * @uses get_template_part()
 */
function cs_get_template_part( $slug, $name = null, $load = true ) {
	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	$load_template = apply_filters( 'cs_allow_template_part_' . $slug . '_' . $name, true );
	if ( false === $load_template ) {
		return '';
	}

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	// Allow template parts to be filtered
	$templates = apply_filters( 'cs_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return cs_locate_template( $templates, $load, false );
}

/**
 * Only allow the pending verification message to display once
 * @since 2.7.8
 * @param $load_template
 *
 * @return bool
 */
function cs_load_verification_template_once( $load_template ) {
	static $account_pending_loaded;
	if ( ! is_null( $account_pending_loaded ) ) {
		return false;
	}

	$account_pending_loaded = true;
	return $load_template;
}
add_filter( 'cs_allow_template_part_account_pending', 'cs_load_verification_template_once', 10, 1 );

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
 * inherit from a parent theme can just overload one file. If the template is
 * not found in either of those, it looks in the theme-compat folder last.
 *
 * Taken from bbPress
 *
 * @since 1.2
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true.
 *   Has no effect if $load is false.
 * @return string The template filename if one is located.
 */
function cs_locate_template( $template_names, $load = false, $require_once = true ) {
	// No file found yet
	$located = false;

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) )
			continue;

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// try locating this template file by looping through the template paths
		foreach( cs_get_theme_template_paths() as $template_path ) {

			if( file_exists( $template_path . $template_name ) ) {
				$located = $template_path . $template_name;
				break;
			}
		}

		if( $located ) {
			break;
		}
	}

	if ( ( true == $load ) && ! empty( $located ) )
		load_template( $located, $require_once );

	return $located;
}

/**
 * Returns a list of paths to check for template locations
 *
 * @since 1.8.5
 * @return mixed|void
 */
function cs_get_theme_template_paths() {

	$template_dir = cs_get_theme_template_dir_name();

	$file_paths = array(
		1 => trailingslashit( get_stylesheet_directory() ) . $template_dir,
		10 => trailingslashit( get_template_directory() ) . $template_dir,
		100 => cs_get_templates_dir()
	);

	$file_paths = apply_filters( 'cs_template_paths', $file_paths );

	// sort the file paths based on priority
	ksort( $file_paths, SORT_NUMERIC );

	return array_map( 'trailingslashit', $file_paths );
}

/**
 * Returns the template directory name.
 *
 * Themes can filter this by using the cs_templates_dir filter.
 *
 * @since 1.6.2
 * @return string
*/
function cs_get_theme_template_dir_name() {
	return trailingslashit( apply_filters( 'cs_templates_dir', 'cs_templates' ) );
}

/**
 * Add no-index and no-follow to CommerceStore checkout and purchase confirmation pages
 *
 * @since 2.0
 *
 * @return void
 */
function cs_checkout_meta_tags() {

	$pages   = array();
	$pages[] = cs_get_option( 'success_page' );
	$pages[] = cs_get_option( 'failure_page' );
	$pages[] = cs_get_option( 'purchase_history_page' );

	if( ! cs_is_checkout() && ! is_page( $pages ) ) {
		return;
	}

	echo '<meta name="cs-chosen-gateway" content="' . cs_get_chosen_gateway() . '"/>' . "\n";
	echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
}
add_action( 'wp_head', 'cs_checkout_meta_tags' );

/**
 * Adds CommerceStore Version to the <head> tag
 *
 * @since 1.4.2
 * @return void
*/
function cs_version_in_header(){
	echo '<meta name="generator" content="CommerceStore v' . CS_VERSION . '" />' . "\n";
}
add_action( 'wp_head', 'cs_version_in_header' );

/**
 * Determines if we're currently on the Purchase History page.
 *
 * @since 2.1
 * @return bool True if on the Purchase History page, false otherwise.
 */
function cs_is_purchase_history_page() {
	$ret = cs_get_option( 'purchase_history_page', false );
	$ret = $ret ? is_page( $ret ) : false;
	return apply_filters( 'cs_is_purchase_history_page', $ret );
}

/**
 * Adds body classes for CommerceStore pages
 *
 * @since 2.1
 * @param array $class current classes
 * @return array Modified array of classes
 */
function cs_add_body_classes( $class ) {
	$classes = (array) $class;

	if( cs_is_checkout() ) {
		$classes[] = 'cs-checkout';
		$classes[] = 'cs-page';
	}

	if( cs_is_success_page() ) {
		$classes[] = 'cs-success';
		$classes[] = 'cs-page';
	}

	if( cs_is_failed_transaction_page() ) {
		$classes[] = 'cs-failed-transaction';
		$classes[] = 'cs-page';
	}

	if( cs_is_purchase_history_page() ) {
		$classes[] = 'cs-purchase-history';
		$classes[] = 'cs-page';
	}

	if( cs_is_test_mode() ) {
		$classes[] = 'cs-test-mode';
	}

	return array_unique( $classes );
}
add_filter( 'body_class', 'cs_add_body_classes' );

/**
 * Adds post classes for downloads
 *
 * @since 2.1
 * @param array $classes Current classes
 * @param string|array $class
 * @param int $post_id The ID of the current post
 * @return array Modified array of classes
 */
function cs_add_download_post_classes( $classes, $class = '', $post_id = false ) {
	if( ! $post_id || get_post_type( $post_id ) !== CS_POST_TYPE || is_admin() ) {
		return $classes;
	}

	$download = cs_get_download( $post_id );

	if( $download ) {
		$classes[] = 'cs-download';

		// Add category slugs
		$categories = get_the_terms( $post_id, 'download_category' );
		if( ! empty( $categories ) ) {
			foreach( $categories as $key => $value ) {
				$classes[] = 'cs-download-cat-' . $value->slug;
			}
		}

		// Add tag slugs
		$tags = get_the_terms( $post_id, 'download_tag' );
		if( ! empty( $tags ) ) {
			foreach( $tags as $key => $value ) {
				$classes[] = 'cs-download-tag-' . $value->slug;
			}
		}

		// Add cs-download
		if( is_singular( CS_POST_TYPE ) ) {
			$classes[] = 'cs-download';
		}
	}

	return $classes;
}
add_filter( 'post_class', 'cs_add_download_post_classes', 20, 3 );

/**
 * Adds Download product price to oembed display
 *
 * @since 2.6
 * @return void
 */
function cs_add_oembed_price() {

	if( CS_POST_TYPE !== get_post_type( get_the_ID() ) ) {
		return;
	}

	$show = ! get_post_meta( get_the_ID(), '_cs_hide_purchase_link', true );

	if ( apply_filters( 'cs_show_oembed_purchase_links', $show ) ) {
		echo '<style>.wp-embed-cs-price { margin: 20px 0 0 0; }</style>';
		echo '<div class="wp-embed-cs-price">';
			if ( cs_has_variable_prices( get_the_ID() ) ) {
				echo cs_price_range( get_the_ID() );
			} else {
				cs_price( get_the_ID(), true );
			}

		echo '</div>';
	}
}
add_action( 'embed_content', 'cs_add_oembed_price' );

/**
 * Remove comments button for download embeds
 *
 * @since 2.6
 * @return  void
 */
function cs_remove_embed_comments_button() {
	global $post;

	$hide_comments = apply_filters( 'cs_embed_hide_comments', true, $post );

	if ( ! empty( $post ) && $post->post_type == CS_POST_TYPE && true === $hide_comments ) {
		remove_action( 'embed_content_meta', 'print_embed_comments_button' );
	}
}
add_action( 'embed_content_meta', 'cs_remove_embed_comments_button', 5 );

/**
 * Get a fully formatted title of a bundle item
 *
 * @since 2.7
 *
 * @param array $bundle_item Bundle item.
 * @return string Bundle item title.
 */
function cs_get_bundle_item_title( $bundle_item ) {
	$bundle_item_pieces = explode( '_', $bundle_item );
	$bundle_item_id = $bundle_item_pieces[0];
	$bundle_price_id = isset( $bundle_item_pieces[1] ) ? $bundle_item_pieces[1] : null;

	$prices = cs_get_variable_prices( $bundle_item_id );
	$bundle_title = get_the_title( $bundle_item_id );

	if ( null !== $bundle_price_id ) {
		$bundle_title .= ' - ' . $prices[ $bundle_price_id ]['name'];
	}

	return $bundle_title;
}

/**
 * Retrieve the ID of an item in a bundle.
 *
 * @since 2.7
 *
 * @param array $bundle_item Bundle item.
 * @return string Bundle item ID.
 */
function cs_get_bundle_item_id( $bundle_item ) {
	$bundle_item_pieces = explode( '_', $bundle_item );
	$bundle_item_id = $bundle_item_pieces[0];
	return $bundle_item_id;
}

/**
 * Retrieve the price ID of a bundle item.
 *
 * @since 2.7
 *
 * @param array $bundle_item Bundle item.
 * @return string Bundle item ID.
 */
function cs_get_bundle_item_price_id( $bundle_item ) {
	$bundle_item_pieces = explode( '_', $bundle_item );
	$bundle_item_id = $bundle_item_pieces[0];
	$bundle_price_id = isset( $bundle_item_pieces[1] ) ? $bundle_item_pieces[1] : null;

	return $bundle_price_id;
}

/**
 * Load a template file for a single download item.
 *
 * This is a wrapper function for backwards compatibility so the
 * shortcode's attributes can be passed to the template file via
 * a global variable.
 *
 * @since 2.8.0
 *
 * @param array $atts The [downloads] shortcode attributes.
 * @param int   $i The current item count.
 */
function cs_download_shortcode_item( $atts, $i ) {
	global $cs_download_shortcode_item_atts, $cs_download_shortcode_item_i;

	/**
	 * The variables are registered as part of the global scope so the template can access them.
	 */
	$cs_download_shortcode_item_atts = $atts;
	$cs_download_shortcode_item_i = $i;

	cs_get_template_part( 'shortcode', CS_POST_TYPE );
}
add_action( 'cs_download_shortcode_item', 'cs_download_shortcode_item', 10, 2 );

/**
 * Output full content for a download item in the [downloads] shortcode.
 *
 * Strips the [downloads] shortcode to avoid recursion.
 *
 * @since 3.0
 *
 * @return string
 */
function cs_download_shortcode_full_content() {
	$pattern = get_shortcode_regex( array( 'downloads' ) );
	$content = preg_replace( "/$pattern/", '', get_the_content( '' ) );

	/**
	 * Filters the full content output for an individual download in [downloads] shortcode.
	 *
	 * @since 1.2
	 *
	 * @param string $content Download content.
	 */
	return apply_filters( 'cs_downloads_content', $content );
}

/**
 * Output an excerpt for a download item in the [downloads] shortcode.
 *
 * @since 3.0
 *
 * @return string
 */
function cs_download_shortcode_excerpt() {
	// Adjust excerpt lengths.
	add_filter( 'excerpt_length', 'cs_download_shortcode_excerpt_length' );

	// Ensure we use `the_excerpt` filter (for length).
	ob_start();
	the_excerpt();
	$excerpt = ob_get_clean();

	/**
	 * Filters the excerpt output for an individual download in [downloads] shortcode.
	 *
	 * @since 1.2
	 *
	 * @param string $excerpt Download excerpt.
	 */
	$excerpt = apply_filters( 'cs_downloads_excerpt', $excerpt );

	// Let other excerpt lengths act independently again.
	remove_filter( 'excerpt_length', 'cs_download_shortcode_excerpt_length' );

	return $excerpt;
}

/**
 * Callback for the [downloads] shortcode excerpt length.
 *
 * Added as a callable function so it can be removed after the downloads are output.
 *
 * @since 3.0
 *
 * @return int
 */
function cs_download_shortcode_excerpt_length() {
	$length = 30;

	/**
	 * Filters the length of the generated excerpts in the [downloads] shortcode.
	 *
	 * @since 3.0
	 *
	 * @param int $length Length of the excerpt (in words).
	 */
	return apply_filters( 'cs_download_shortcode_excerpt_length', $length );
}

/**
 * Load the pagination for the [downloads] shortcode.
 *
 * @since 2.9.8
 *
 * @param array  $atts The [downloads] shortcode attributes.
 * @param object $downloads The WP_Query.
 * @param array  $query CS's array of attributes used to construct the main WP_Query.
 */
function cs_downloads_pagination( $atts, $downloads, $query ) {
	if ( filter_var( $atts['pagination'], FILTER_VALIDATE_BOOLEAN ) ) {
		$args = array(
			'type'    => CS_POST_TYPE,
			'format'  => '?paged=%#%',
			'current' => max( 1, $query['paged'] ),
			'total'   => $downloads->max_num_pages
		);

		if ( is_single() ) {
			$args['base'] = get_permalink() . '%#%';
		} else {
			$big          = 999999;
			$search_for   = array( $big, '#038;' );
			$replace_with = array( '%#%', '&' );
			$args['base'] = str_replace( $search_for, $replace_with, get_pagenum_link( $big ) );
		}

		$args = apply_filters( 'cs_download_pagination_args', $args, $atts, $downloads, $query );

		echo cs_pagination( $args );
	}
}
add_action( 'cs_downloads_list_after', 'cs_downloads_pagination', 10, 3 );

/**
 * Build pagination
 *
 * @since 2.9.8
 *
 * @param array $args The arguments used to build the pagination.
 */
function cs_pagination( $args = array() ) {

	$big = 999999;

	$defaults = array(
		'base'    => str_replace( $big, '%#%', esc_url( get_pagenum_link( $big ) ) ),
		'format'  => '?paged=%#%',
		'current' => max( 1, get_query_var( 'paged' ) ),
		'type'    => '',
		'total'   => '',
	);

	$args = wp_parse_args( $args, $defaults );

	/**
	 * Filter pagination args.
	 *
	 * @since 3.0
	 *
	 * @param array $args Pagination arguments.
	 */
	$args = apply_filters( 'cs_pagination_args', $args );

	$type  = $args['type'];
	$total = $args['total'];

	// Type and total must be specified.
	if ( empty( $type ) || empty( $total ) ) {
		return false;
	}

	$pagination = paginate_links(
		array(
			'base'    => $args['base'],
			'format'  => $args['format'],
			'current' => $args['current'],
			'total'   => $total
		)
	);

	if ( ! empty( $pagination ) ) : ?>
		<div id="cs_<?php echo $type; ?>_pagination" class="cs_pagination navigation">
			<?php echo $pagination; ?>
		</div>
	<?php endif;
}
