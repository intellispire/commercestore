<?php
/**
 * Email template tag helpers.
 *
 * Email tags are wrapped in { }
 *
 * A few examples:
 *
 * {download_list}
 * {name}
 * {sitename}
 *
 *
 * To replace tags in content, use: cs_do_email_tags( $content, payment_id );
 *
 * To add tags, use: cs_add_email_tag( $tag, $description, $func, $label ). Be sure to wrap cs_add_email_tag()
 * in a function hooked to the 'cs_add_email_tags' action
 *
 * @package     CS
 * @subpackage  Email
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Add an email tag.
 *
 * @since 1.9
 * @since 3.0 Added $label parameter.
 *
 * @param string $tag         Email tag to be replace in email.
 * @param string $description Tag description.
 * @param string $func        Callback function to run when email tag is found.
 * @param string $label       Tag label.
 */
function cs_add_email_tag( $tag = '', $description = '', $func = '', $label = '' ) {
	CS()->email_tags->add( $tag, $description, $func, $label );
}

/**
 * Remove an email tag
 *
 * @since 1.9
 *
 * @param string $tag Email tag to remove hook from
 */
function cs_remove_email_tag( $tag ) {
	CS()->email_tags->remove( $tag );
}

/**
 * Check if $tag is a registered email tag
 *
 * @since 1.9
 *
 * @param string $tag Email tag that will be searched
 *
 * @return bool
 */
function cs_email_tag_exists( $tag ) {
	return CS()->email_tags->email_tag_exists( $tag );
}

/**
 * Get all email tags
 *
 * @since 1.9
 *
 * @return array
 */
function cs_get_email_tags() {
	return CS()->email_tags->get_tags();
}

/**
 * Get a formatted HTML list of all available email tags.
 *
 * @since 1.9
 *
 * @return string
 */
function cs_get_emails_tags_list() {

	// Begin with empty list.
	$list = '';

	// Get all tags.
	$email_tags = (array) cs_get_email_tags();

	// Check.
	if ( count( $email_tags ) > 0 ) {

		// Loop.
		foreach ( $email_tags as $email_tag ) {

			// Add email tag to list.
			$list .= '<code>{' . $email_tag['tag'] . '}</code> - ' . $email_tag['description'] . '<br/>';
		}
	}

	// Return the list.
	return $list;
}

/**
 * Search content for email tags and filter email tags through their hooks.
 *
 * @since 1.9
 * @since 3.0 Renamed `$payment_id` parameter to `$order_id`.
 *            Set default value of `$order_id` to 0.
 *            Set default value of `$content` to empty string.
 *
 * @param string $content  Content to search for email tags.
 * @param int    $order_id Order ID.
 *
 * @return string Content with email tags filtered out.
 */
function cs_do_email_tags( $content = '', $order_id = 0 ) {

	// Replace all tags.
	$content = CS()->email_tags->do_tags( $content, $order_id );

	// Maintaining backwards compatibility.
	$content = apply_filters( 'cs_email_template_tags', $content, cs_get_payment_meta( $order_id ), $order_id );

	// Return content.
	return $content;
}

/**
 * Load email tags.
 *
 * @since 1.9
 */
function cs_load_email_tags() {
	do_action( 'cs_add_email_tags' );
}
add_action( 'init', 'cs_load_email_tags', -999 );

/**
 * Add default CommerceStore email template tags.
 *
 * @since 1.9
 */
function cs_setup_email_tags() {
	$email_tags = array(
		array(
			'tag'         => 'download_list',
			'label'       => __( 'Download List', 'commercestore' ),
			'description' => __( 'A list of download links for each download purchased.', 'commercestore' ),
			'function'    => 'text/html' === CS()->emails->get_content_type()
				? 'cs_email_tag_download_list'
				: 'cs_email_tag_download_list_plain',
		),
		array(
			'tag'         => 'file_urls',
			'label'       => __( 'File URLs', 'commercestore' ),
			'description' => __( 'A plain-text list of download URLs for each download purchased.', 'commercestore' ),
			'function'    => 'cs_email_tag_file_urls',
		),
		array(
			'tag'         => 'name',
			'label'       => __( 'First Name', 'commercestore' ),
			'description' => __( "The buyer's first name.", 'commercestore' ),
			'function'    => 'cs_email_tag_first_name',
		),
		array(
			'tag'         => 'fullname',
			'label'       => __( 'Full Name', 'commercestore' ),
			'description' => __( "The buyer's full name: first and last.", 'commercestore' ),
			'function'    => 'cs_email_tag_fullname',
		),
		array(
			'tag'         => 'username',
			'label'       => __( 'Username', 'commercestore' ),
			'description' => __( "The buyer's user name on the site, if they registered an account.", 'commercestore' ),
			'function'    => 'cs_email_tag_username',
		),
		array(
			'tag'         => 'user_email',
			'label'       => __( 'Email', 'commercestore' ),
			'description' => __( "The buyer's email address.", 'commercestore' ),
			'function'    => 'cs_email_tag_user_email',
		),
		array(
			'tag'         => 'billing_address',
			'label'       => __( 'Billing Address', 'commercestore' ),
			'description' => __( "The buyer's billing address.", 'commercestore' ),
			'function'    => 'cs_email_tag_billing_address',
		),
		array(
			'tag'         => 'date',
			'label'       => __( 'Purchase Date', 'commercestore' ),
			'description' => __( 'The date of the purchase.', 'commercestore' ),
			'function'    => 'cs_email_tag_date',
		),
		array(
			'tag'         => 'subtotal',
			'label'       => __( 'Subtotal', 'commercestore' ),
			'description' => __( 'The price of the purchase before taxes.', 'commercestore' ),
			'function'    => 'cs_email_tag_subtotal',
		),
		array(
			'tag'         => 'tax',
			'label'       => __( 'Tax', 'commercestore' ),
			'description' => __( 'The taxed amount of the purchase', 'commercestore' ),
			'function'    => 'cs_email_tag_tax',
		),
		array(
			'tag'         => 'price',
			'label'       => __( 'Price', 'commercestore' ),
			'description' => __( 'The total price of the purchase', 'commercestore' ),
			'function'    => 'cs_email_tag_price',
		),
		array(
			'tag'         => 'payment_id',
			'label'       => __( 'Payment ID', 'commercestore' ),
			'description' => __( 'The unique identifier for this purchase.', 'commercestore' ),
			'function'    => 'cs_email_tag_payment_id',
		),
		array(
			'tag'         => 'receipt_id',
			'label'       => __( 'Receipt ID', 'commercestore' ),
			'description' => __( 'The unique identifier for the receipt of this purchase.', 'commercestore' ),
			'function'    => 'cs_email_tag_receipt_id',
		),
		array(
			'tag'         => 'payment_method',
			'label'       => __( 'Payment Method', 'commercestore' ),
			'description' => __( 'The method of payment used for this purchase.', 'commercestore' ),
			'function'    => 'cs_email_tag_payment_method',
		),
		array(
			'tag'         => 'sitename',
			'label'       => __( 'Site Name', 'commercestore' ),
			'description' => __( 'Your site name.', 'commercestore' ),
			'function'    => 'cs_email_tag_sitename',
		),
		array(
			'tag'         => 'receipt_link',
			'label'       => __( 'Receipt Link', 'commercestore' ),
			'description' => __( 'Adds a link so users can view their receipt directly on your website if they are unable to view it in the browser correctly.', 'commercestore' ),
			'function'    => 'cs_email_tag_receipt_link',
		),
		array(
			'tag'         => 'discount_codes',
			'label'       => __( 'Discount Codes', 'commercestore' ),
			'description' => __( 'Adds a list of any discount codes applied to this purchase.', 'commercestore' ),
			'function'    => 'cs_email_tag_discount_codes',
		),
		array(
			'tag'         => 'ip_address',
			'label'       => __( 'IP Address', 'commercestore' ),
			'description' => __( "The buyer's IP Address.", 'commercestore' ),
			'function'    => 'cs_email_tag_ip_address',
		),
	);

	// Apply cs_email_tags filter
	$email_tags = apply_filters( 'cs_email_tags', $email_tags );

	// Add email tags
	foreach ( $email_tags as $email_tag ) {
		$label = isset( $email_tag['label'] ) ? $email_tag['label'] : '';
		cs_add_email_tag( $email_tag['tag'], $email_tag['description'], $email_tag['function'], $label );
	}

}
add_action( 'cs_add_email_tags', 'cs_setup_email_tags' );

/**
 * Email template tag: download_list
 * A list of download links for each download purchased
 *
 * @param int $payment_id
 *
 * @return string download_list
 */
function cs_email_tag_download_list( $payment_id ) {
	$payment = new CS_Payment( $payment_id );
	$order   = cs_get_order( $payment_id );

	if ( ! $order ) {
		return '';
	}

	$payment_data  = $payment->get_meta();
	$download_list = '<ul>';
	$cart_items    = $payment->cart_details;
	$email         = $payment->email;
	$needs_notes   = array();

	if ( $order->get_items() ) {
		$show_names = apply_filters( 'cs_email_show_names', true );
		$show_links = apply_filters( 'cs_email_show_links', true );

		foreach ( $order->get_items() as $item ) {

			if ( cs_use_skus() ) {
				$sku = cs_get_download_sku( $item->product_id );
			}

			if ( cs_item_quantities_enabled() ) {
				$quantity = $item->quantity;
			}

			if ( $show_names ) {

				$title = '<strong>' . $item->product_name . '</strong>';

				if ( ! empty( $quantity ) && $quantity > 1 ) {
					$title .= "&nbsp;&ndash;&nbsp;" . __( 'Quantity', 'commercestore' ) . ': ' . $quantity;
				}

				if ( ! empty( $sku ) ) {
					$title .= "&nbsp;&ndash;&nbsp;" . __( 'SKU', 'commercestore' ) . ': ' . $sku;
				}

				$download_list .= '<li>' . apply_filters( 'cs_email_receipt_download_title', $title, $cart_items[ $item->cart_index ], $item->price_id, $payment_id ) . '<br/>';
			}

			$files = cs_get_download_files( $item->product_id, $item->price_id );

			if ( ! empty( $files ) ) {

				foreach ( $files as $filekey => $file ) {

					if ( $show_links ) {
						$download_list .= '<div>';
							$file_url = cs_get_download_file_url( $payment_data['key'], $email, $filekey, $item->product_id, $item->price_id );
							$download_list .= '<a href="' . esc_url_raw( $file_url ) . '">' . cs_get_file_name( $file ) . '</a>';
							$download_list .= '</div>';
					} else {
						$download_list .= '<div>';
							$download_list .= cs_get_file_name( $file );
						$download_list .= '</div>';
					}

				}

			} elseif ( cs_is_bundled_product( $item->product_id ) ) {

				$bundled_products = apply_filters( 'cs_email_tag_bundled_products', cs_get_bundled_products( $item->product_id, $item->price_id ), $item, $payment_id, 'download_list' );

				foreach ( $bundled_products as $bundle_item ) {

					$download_list .= '<div class="cs_bundled_product"><strong>' . get_the_title( $bundle_item ) . '</strong></div>';

					$download_files = cs_get_download_files( cs_get_bundle_item_id( $bundle_item ), cs_get_bundle_item_price_id( $bundle_item ) );

					foreach ( $download_files as $filekey => $file ) {
						if ( $show_links ) {
							$download_list .= '<div>';
							$file_url = cs_get_download_file_url( $payment_data['key'], $email, $filekey, $bundle_item, $item->price_id );
							$download_list .= '<a href="' . esc_url( $file_url ) . '">' . cs_get_file_name( $file ) . '</a>';
							$download_list .= '</div>';
						} else {
							$download_list .= '<div>';
							$download_list .= cs_get_file_name( $file );
							$download_list .= '</div>';
						}
					}
				}

			} else {

				$no_downloads_message = apply_filters( 'cs_receipt_no_files_found_text', __( 'No downloadable files found.', 'commercestore' ), $item->product_id );
				$no_downloads_message = apply_filters( 'cs_email_receipt_no_downloads_message', $no_downloads_message, $item->product_id, $item->price_id, $payment_id );

				if ( ! empty( $no_downloads_message ) ){
					$download_list .= '<div>';
						$download_list .= $no_downloads_message;
					$download_list .= '</div>';
				}
			}

			if ( ! array_key_exists( $item->product_id, $needs_notes ) ) {
				$item_notes = cs_get_product_notes( $item->product_id );
				if ( $item_notes ) {
					$needs_notes[ $item->product_id ] = array(
						'item_name'  => get_the_title( $item->product_id ),
						'item_notes' => $item_notes,
					);
				}
			}

			if ( $show_names ) {
				$download_list .= '</li>';
			}
		}
	}
	$download_list .= '</ul>';

	// Remove any empty values.
	$needs_notes = array_filter( $needs_notes );
	if ( ! empty( $needs_notes ) ) {
		$download_list .= __( 'Additional information about your purchase:', 'commercestore' );

		$download_list .= '<ul>';
		foreach ( $needs_notes as $note ) {
			$download_list .= '<li>' . $note['item_name'] . "\n" . '<small>' . $note['item_notes'] . '</small></li>';
		}
		$download_list .= '</ul>';
	}

	return $download_list;
}

/**
 * Email template tag: download_list
 * A list of download links for each download purchased in plaintext
 *
 * @since 2.1.1
 * @param int $payment_id
 *
 * @return string download_list
 */
function cs_email_tag_download_list_plain( $payment_id ) {
	$payment = new CS_Payment( $payment_id );
	$order   = cs_get_order( $payment_id );

	$payment_data  = $payment->get_meta();
	$cart_items    = $payment->cart_details;
	$email         = $payment->email;
	$download_list = '';

	if ( $order->get_items() ) {
		$show_names = apply_filters( 'cs_email_show_names', true );
		$show_links = apply_filters( 'cs_email_show_links', true );

		foreach ( $order->get_items() as $item ) {

			if ( cs_use_skus() ) {
				$sku = cs_get_download_sku( $item->product_id );
			}

			if ( cs_item_quantities_enabled() ) {
				$quantity = $item->quantity;
			}

			if ( $show_names ) {

				$title = $item->product_name;

				if ( ! empty( $quantity ) && $quantity > 1 ) {
					$title .= __( 'Quantity', 'commercestore' ) . ': ' . $quantity;
				}

				if ( ! empty( $sku ) ) {
					$title .= __( 'SKU', 'commercestore' ) . ': ' . $sku;
				}

				$download_list .= "\n";

				$download_list .= apply_filters( 'cs_email_receipt_download_title', $title, $cart_items[ $item->cart_index ], $item->price_id, $payment_id )  . "\n";
			}

			$files = cs_get_download_files( $item->product_id, $item->price_id );

			if ( ! empty( $files ) ) {

				foreach ( $files as $filekey => $file ) {
					if( $show_links ) {
						$download_list .= "\n";
						$file_url = cs_get_download_file_url( $payment_data['key'], $email, $filekey, $item->product_id, $item->price_id );
						$download_list .= cs_get_file_name( $file ) . ': ' . $file_url . "\n";
					} else {
						$download_list .= "\n";
						$download_list .= cs_get_file_name( $file ) . "\n";
					}
				}

			} elseif ( cs_is_bundled_product( $item->product_id ) ) {

				$bundled_products = apply_filters( 'cs_email_tag_bundled_products', cs_get_bundled_products( $item->product_id ), $cart_items[ $item->cart_index ], $payment_id, 'download_list' );

				foreach ( $bundled_products as $bundle_item ) {

					$download_list .= '<div class="cs_bundled_product"><strong>' . get_the_title( $bundle_item ) . '</strong></div>';

					$files = cs_get_download_files( $bundle_item );

					foreach ( $files as $filekey => $file ) {
						if( $show_links ) {
							$file_url = cs_get_download_file_url( $payment_data['key'], $email, $filekey, $bundle_item, $item->price_id );
							$download_list .= cs_get_file_name( $file ) . ': ' . $file_url . "\n";
						} else {
							$download_list .= cs_get_file_name( $file ) . "\n";
						}
					}
				}
			}


			if ( '' != cs_get_product_notes( $item->product_id ) ) {
				$download_list .= "\n";
				$download_list .= cs_get_product_notes( $item->product_id ) . "\n";
			}
		}
	}

	return $download_list;
}

/**
 * Email template tag: file_urls
 * A plain-text list of download URLs for each download purchased
 *
 * @param int $payment_id
 *
 * @return string $file_urls
 */
function cs_email_tag_file_urls( $payment_id ) {
	$payment = new CS_Payment( $payment_id );

	$payment_data = $payment->get_meta();
	$file_urls    = '';
	$cart_items   = $payment->cart_details;
	$email        = $payment->email;

	foreach ( $cart_items as $item ) {

		$price_id = cs_get_cart_item_price_id( $item );
		$files    = cs_get_download_files( $item['id'], $price_id );

		if ( $files ) {
			foreach ( $files as $filekey => $file ) {
				$file_url = cs_get_download_file_url( $payment_data['key'], $email, $filekey, $item['id'], $price_id );

				$file_urls .= esc_html( $file_url ) . '<br/>';
			}
		}
		elseif ( cs_is_bundled_product( $item['id'] ) ) {

			$bundled_products = apply_filters( 'cs_email_tag_bundled_products', cs_get_bundled_products( $item['id'] ), $item, $payment_id, 'file_urls' );

			foreach ( $bundled_products as $bundle_item ) {

				$files = cs_get_download_files( $bundle_item );
				foreach ( $files as $filekey => $file ) {
					$file_url = cs_get_download_file_url( $payment_data['key'], $email, $filekey, $bundle_item, $price_id );
					$file_urls .= esc_html( $file_url ) . '<br/>';
				}

			}
		}

	}

	return $file_urls;
}

/**
 * Email template tag: name
 * The buyer's first name
 *
 * @param int $payment_id
 *
 * @return string name
 */
function cs_email_tag_first_name( $payment_id ) {
	$payment   = new CS_Payment( $payment_id );
	$user_info = $payment->user_info;

	if( empty( $user_info) ) {
		return '';
	}

	$email_name   = cs_get_email_names( $user_info, $payment );

	return $email_name['name'];
}

/**
 * Email template tag: fullname
 * The buyer's full name, first and last
 *
 * @param int $payment_id
 *
 * @return string fullname
 */
function cs_email_tag_fullname( $payment_id ) {
	$payment   = new CS_Payment( $payment_id );
	$user_info = $payment->user_info;

	if( empty( $user_info ) ) {
		return '';
	}

	$email_name   = cs_get_email_names( $user_info, $payment );
	return $email_name['fullname'];
}

/**
 * Email template tag: username
 * The buyer's user name on the site, if they registered an account
 *
 * @param int $payment_id
 *
 * @return string username
 */
function cs_email_tag_username( $payment_id ) {
	$payment   = new CS_Payment( $payment_id );
	$user_info = $payment->user_info;

	if( empty( $user_info ) ) {
		return '';
	}

	$email_name   = cs_get_email_names( $user_info, $payment );
	return $email_name['username'];
}

/**
 * Email template tag: user_email
 * The buyer's email address
 *
 * @param int $payment_id
 *
 * @return string user_email
 */
function cs_email_tag_user_email( $payment_id ) {
	$payment = new CS_Payment( $payment_id );

	return $payment->email;
}

/**
 * Email template tag: billing_address
 * The buyer's billing address
 *
 * @param int $payment_id
 *
 * @return string billing_address
 */
function cs_email_tag_billing_address( $payment_id ) {

	$user_info    = cs_get_payment_meta_user_info( $payment_id );
	$user_address = ! empty( $user_info['address'] ) ? $user_info['address'] : array( 'line1' => '', 'line2' => '', 'city' => '', 'country' => '', 'state' => '', 'zip' => '' );

	$return = $user_address['line1'] . "\n";
	if( ! empty( $user_address['line2'] ) ) {
		$return .= $user_address['line2'] . "\n";
	}
	$return .= $user_address['city'] . ' ' . $user_address['zip'] . ' ' . $user_address['state'] . "\n";
	$return .= $user_address['country'];

	return $return;
}

/**
 * Email template tag: date
 * Date of purchase
 *
 * @param int $payment_id
 *
 * @return string date
 */
function cs_email_tag_date( $payment_id ) {
	$payment = new CS_Payment( $payment_id );
	return date_i18n( get_option( 'date_format' ), strtotime( $payment->date ) );
}

/**
 * Email template tag: subtotal
 * Price of purchase before taxes
 *
 * @param int $payment_id
 *
 * @return string subtotal
 */
function cs_email_tag_subtotal( $payment_id ) {
	$payment  = new CS_Payment( $payment_id );
	$subtotal = cs_currency_filter( cs_format_amount( $payment->subtotal ), $payment->currency );
	return html_entity_decode( $subtotal, ENT_COMPAT, 'UTF-8' );
}

/**
 * Email template tag: tax
 * The taxed amount of the purchase
 *
 * @param int $payment_id
 *
 * @return string tax
 */
function cs_email_tag_tax( $payment_id ) {
	$payment = new CS_Payment( $payment_id );
	$tax     = cs_currency_filter( cs_format_amount( $payment->tax ), $payment->currency );
	return html_entity_decode( $tax, ENT_COMPAT, 'UTF-8' );
}

/**
 * Email template tag: price
 * The total price of the purchase
 *
 * @param int $payment_id
 *
 * @return string price
 */
function cs_email_tag_price( $payment_id ) {
	$payment = new CS_Payment( $payment_id );
	$price   = cs_currency_filter( cs_format_amount( $payment->total ), $payment->currency );
	return html_entity_decode( $price, ENT_COMPAT, 'UTF-8' );
}

/**
 * Email template tag: payment_id
 * The unique ID number for this purchase
 *
 * @param int $payment_id
 *
 * @return int payment_id
 */
function cs_email_tag_payment_id( $payment_id ) {
	$payment = new CS_Payment( $payment_id );
	return $payment->number;
}

/**
 * Email template tag: receipt_id
 * The unique ID number for this purchase receipt
 *
 * @param int $payment_id
 *
 * @return string receipt_id
 */
function cs_email_tag_receipt_id( $payment_id ) {
	$payment = new CS_Payment( $payment_id );
	return $payment->key;
}

/**
 * Email template tag: payment_method
 * The method of payment used for this purchase
 *
 * @param int $payment_id
 *
 * @return string gateway
 */
function cs_email_tag_payment_method( $payment_id ) {
	$payment = new CS_Payment( $payment_id );
	return cs_get_gateway_checkout_label( $payment->gateway );
}

/**
 * Email template tag: sitename
 * Your site name
 *
 * @param int $payment_id
 *
 * @return string sitename
 */
function cs_email_tag_sitename( $payment_id ) {
	return wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
}

/**
 * Email template tag: receipt_link
 * Adds a link so users can view their receipt directly on your website if they are unable to view it in the browser correctly
 *
 * @param $payment_id int
 *
 * @return string receipt_link
 */
function cs_email_tag_receipt_link( $payment_id ) {
	$receipt_url = esc_url( add_query_arg( array(
		'payment_key' => cs_get_payment_key( $payment_id ),
		'cs_action'  => 'view_receipt'
	), home_url() ) );
	$formatted   = sprintf( __( '%1$sView it in your browser %2$s', 'commercestore' ), '<a href="' . $receipt_url . '">', '&raquo;</a>' );

	if ( cs_get_option( 'email_template' ) !== 'none' ) {
		return $formatted;
	} else {
		return $receipt_url;
	}
}

/**
 * Email template tag: discount_codes
 * Adds a list of any discount codes applied to this purchase
 *
 * @since  2.0
 * @param int $payment_id
 * @return string $discount_codes
 */
function cs_email_tag_discount_codes( $payment_id ) {
	$user_info = cs_get_payment_meta_user_info( $payment_id );

	$discount_codes = '';

	if( isset( $user_info['discount'] ) && $user_info['discount'] !== 'none' ) {
		$discount_codes = $user_info['discount'];
	}

	return $discount_codes;
}

/**
 * Email template tag: IP address
 * IP address of the customer
 *
 * @since  2.3
 * @param int $payment_id
 * @return string IP address
 */
function cs_email_tag_ip_address( $payment_id ) {
	$payment = new CS_Payment( $payment_id );
	return $payment->ip;
}
