<?php
/**
 * Email Template
 *
 * @package     CS
 * @subpackage  Emails
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Gets all the email templates that have been registerd. The list is extendable
 * and more templates can be added.
 *
 * As of 2.0, this is simply a wrapper to CS_Email_Templates->get_templates()
 *
 * @since 1.0.8.2
 * @return array $templates All the registered email templates
 */
function cs_get_email_templates() {
	$templates = new CS_Emails;
	return $templates->get_templates();
}

/**
 * Email Template Tags
 *
 * @since 1.0
 *
 * @param string $message Message with the template tags
 * @param array $payment_data Payment Data
 * @param int $payment_id Payment ID
 * @param bool $admin_notice Whether or not this is a notification email
 *
 * @return string $message Fully formatted message
 */
function cs_email_template_tags( $message, $payment_data, $payment_id, $admin_notice = false ) {
	return cs_do_email_tags( $message, $payment_id );
}

/**
 * Email Preview Template Tags
 *
 * @since 1.0
 * @param string $message Email message with template tags
 * @return string $message Fully formatted message
 */
function cs_email_preview_template_tags( $message ) {
	$download_list = '<ul>';
	$download_list .= '<li>' . __( 'Sample Product Title', 'commercestore' ) . '<br />';
	$download_list .= '<div>';
	$download_list .= '<a href="#">' . __( 'Sample Download File Name', 'commercestore' ) . '</a> - <small>' . __( 'Optional notes about this download.', 'commercestore' ) . '</small>';
	$download_list .= '</div>';
	$download_list .= '</li>';
	$download_list .= '</ul>';

	$file_urls = esc_html( trailingslashit( get_site_url() ) . 'test.zip?test=key&key=123' );

	$price = cs_currency_filter( cs_format_amount( 10.50 ) );

	$gateway = cs_get_gateway_admin_label( cs_get_default_gateway() );

	$receipt_id = strtolower( md5( uniqid() ) );

	$notes = __( 'These are some sample notes added to a product.', 'commercestore' );

	$tax = cs_currency_filter( cs_format_amount( 1.00 ) );

	$sub_total = cs_currency_filter( cs_format_amount( 9.50 ) );

	$payment_id = rand(1, 100);

	$user = wp_get_current_user();

	$message = str_replace( '{download_list}', $download_list, $message );
	$message = str_replace( '{file_urls}', $file_urls, $message );
	$message = str_replace( '{name}', $user->display_name, $message );
	$message = str_replace( '{fullname}', $user->display_name, $message );
 	$message = str_replace( '{username}', $user->user_login, $message );
	$message = str_replace( '{date}', cs_date_i18n( current_time( 'timestamp' ) ), $message );
	$message = str_replace( '{subtotal}', $sub_total, $message );
	$message = str_replace( '{tax}', $tax, $message );
	$message = str_replace( '{price}', $price, $message );
	$message = str_replace( '{receipt_id}', $receipt_id, $message );
	$message = str_replace( '{payment_method}', $gateway, $message );
	$message = str_replace( '{sitename}', get_bloginfo( 'name' ), $message );
	$message = str_replace( '{product_notes}', $notes, $message );
	$message = str_replace( '{payment_id}', $payment_id, $message );
	$message = str_replace( '{receipt_link}', cs_email_tag_receipt_link( $payment_id ), $message );

	$message = apply_filters( 'cs_email_preview_template_tags', $message );

	return apply_filters( 'cs_email_template_wpautop', true ) ? wpautop( $message ) : $message;
}

/**
 * Email Template Preview
 *
 * @access private
 * @since 1.0.8.2
 */
function cs_email_template_preview() {
	if( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	ob_start();
	?>
	<a href="<?php echo esc_url( add_query_arg( array( 'cs_action' => 'preview_email' ), home_url() ) ); ?>" class="button-secondary" target="_blank"><?php _e( 'Preview Purchase Receipt', 'commercestore' ); ?></a>
	<a href="<?php echo wp_nonce_url( add_query_arg( array( 'cs_action' => 'send_test_email' ) ), 'cs-test-email' ); ?>" class="button-secondary"><?php _e( 'Send Test Email', 'commercestore' ); ?></a>
	<?php
	echo ob_get_clean();
}
add_action( 'cs_purchase_receipt_email_settings', 'cs_email_template_preview' );

/**
 * Displays the email preview
 *
 * @since 2.1
 * @return void
 */
function cs_display_email_template_preview() {

	if( empty( $_GET['cs_action'] ) ) {
		return;
	}

	if( 'preview_email' !== $_GET['cs_action'] ) {
		return;
	}

	if( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}


	CS()->emails->heading = cs_email_preview_template_tags( cs_get_option( 'purchase_heading', __( 'Purchase Receipt', 'commercestore' ) ) );

	echo CS()->emails->build_email( cs_email_preview_template_tags( cs_get_email_body_content( 0, array() ) ) );

	exit;

}
add_action( 'template_redirect', 'cs_display_email_template_preview' );

/**
 * Email Template Body
 *
 * @since 1.0.8.2
 * @param int $payment_id Payment ID
 * @param array $payment_data Payment Data
 * @return string $email_body Body of the email
 */
function cs_get_email_body_content( $payment_id = 0, $payment_data = array() ) {
	$default_email_body = __( "Dear", "commercestore" ) . " {name},\n\n";
	$default_email_body .= __( "Thank you for your purchase. Please click on the link(s) below to download your files.", "commercestore" ) . "\n\n";
	$default_email_body .= "{download_list}\n\n";
	$default_email_body .= "{sitename}";

	$email = cs_get_option( 'purchase_receipt', false );
	$email = $email ? stripslashes( $email ) : $default_email_body;

	$email_body = apply_filters( 'cs_email_template_wpautop', true ) ? wpautop( $email ) : $email;

	$email_body = apply_filters( 'cs_purchase_receipt_' . CS()->emails->get_template(), $email_body, $payment_id, $payment_data );

	return apply_filters( 'cs_purchase_receipt', $email_body, $payment_id, $payment_data );
}

/**
 * Sale Notification Template Body
 *
 * @since 1.7
 * @author Daniel J Griffiths
 * @param int $payment_id Payment ID
 * @param array $payment_data Payment Data
 * @return string $email_body Body of the email
 */
function cs_get_sale_notification_body_content( $payment_id = 0, $payment_data = array() ) {
	$payment = cs_get_payment( $payment_id );
	$order   = cs_get_order( $payment_id );

	$name = $payment->email;
	if ( $payment->user_id > 0 ) {
		$user_data = get_userdata( $payment->user_id );
		if ( ! empty( $user_data->display_name ) ) {
			$name = $user_data->display_name;
		}
	} elseif ( ! empty( $payment->first_name ) && ! empty( $payment->last_name ) ) {
		$name = $payment->first_name . ' ' . $payment->last_name;
	}

	$download_list = '';

	$order_items = $order->get_items();
	if( ! empty( $order_items ) ) {
		foreach( $order_items as $item ) {
			$download_list .= html_entity_decode( $item->product_name, ENT_COMPAT, 'UTF-8' ) . "\n";
		}
	}

	$gateway = cs_get_gateway_checkout_label( $payment->gateway );

	$default_email_body = __( 'Hello', 'commercestore' ) . "\n\n" . sprintf( __( 'A %s purchase has been made', 'commercestore' ), cs_get_label_plural() ) . ".\n\n";
	$default_email_body .= sprintf( __( '%s sold:', 'commercestore' ), cs_get_label_plural() ) . "\n\n";
	$default_email_body .= $download_list . "\n\n";
	$default_email_body .= __( 'Purchased by: ', 'commercestore' ) . " " . html_entity_decode( $name, ENT_COMPAT, 'UTF-8' ) . "\n";
	$default_email_body .= __( 'Amount: ', 'commercestore' ) . " " . html_entity_decode( cs_currency_filter( cs_format_amount( $payment->total ) ), ENT_COMPAT, 'UTF-8' ) . "\n";
	$default_email_body .= __( 'Payment Method: ', 'commercestore' ) . " " . $gateway . "\n\n";
	$default_email_body .= __( 'Thank you', 'commercestore' );

	$message = cs_get_option( 'sale_notification', false );
	$message   = $message ? stripslashes( $message ) : $default_email_body;

	//$email_body = cs_email_template_tags( $email, $payment_data, $payment_id, true );
	$email_body = cs_do_email_tags( $message, $payment_id );

	$email_body = apply_filters( 'cs_email_template_wpautop', true ) ? wpautop( $email_body ) : $email_body;

	return apply_filters( 'cs_sale_notification', $email_body, $payment_id, $payment_data );
}

/**
 * Render Receipt in the Browser
 *
 * A link is added to the Purchase Receipt to view the email in the browser and
 * this function renders the Purchase Receipt in the browser. It overrides the
 * Purchase Receipt template and provides its only styling.
 *
 * @since 1.5
 * @author Sunny Ratilal
 */
function cs_render_receipt_in_browser() {
	if ( ! isset( $_GET['payment_key'] ) ) {
		wp_die( __( 'Missing purchase key.', 'commercestore' ), __( 'Error', 'commercestore' ) );
	}

	$key = urlencode( $_GET['payment_key'] );

	ob_start();

	// Disallows caching of the page
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate"); // HTTP/1.1
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache"); // HTTP/1.0
	header("Expires: Sat, 23 Oct 1977 05:00:00 PST"); // Date in the past
?>
<!DOCTYPE html>
<html lang="en">
	<head>
		<title><?php _e( 'Receipt', 'commercestore' ); ?></title>
		<meta charset="utf-8" />
		<meta name="robots" content="noindex, nofollow" />
		<?php wp_head(); ?>
	</head>
<body class="<?php echo apply_filters('cs_receipt_page_body_class', 'cs_receipt_page' ); ?>">
	<div id="cs_receipt_wrapper">
		<?php do_action( 'cs_render_receipt_in_browser_before' ); ?>
		<?php echo do_shortcode('[cs_receipt payment_key='. $key .']'); ?>
		<?php do_action( 'cs_render_receipt_in_browser_after' ); ?>
	</div>
<?php wp_footer(); ?>
</body>
</html>
<?php
	echo ob_get_clean();
	die();
}
add_action( 'cs_view_receipt', 'cs_render_receipt_in_browser' );
