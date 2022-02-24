<?php
/**
 * Contextual Help
 *
 * @package     CS
 * @subpackage  Admin/Downloads
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2.3
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Adds the Contextual Help for the main Downloads page
 *
 * @since 1.2.3
 * @return void
 */
function cs_downloads_contextual_help() {
	$screen = get_current_screen();

	if ( $screen->id != CS_POST_TYPE )
		return;

	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'commercestore' ) . '</strong></p>' .
		'<p>' . sprintf( __( 'Visit the <a href="%s">documentation</a> on the CommerceStore website.', 'commercestore' ), esc_url( 'http://docs.commercestore.com/' ) ) . '</p>' .
		'<p>' . sprintf(
			__( 'Need more from your CommerceStore store? <a href="%s">Upgrade Now</a>!', 'commercestore' ),
			esc_url( 'https://commercestore.com/pricing/?utm_source=plugin-settings-page&utm_medium=contextual-help-sidebar&utm_term=pricing&utm_campaign=ContextualHelp' )
		) . '</p>'
	);

	$screen->add_help_tab( array(
		'id'	    => 'cs-download-configuration',
		'title'	    => sprintf( __( '%s Settings', 'commercestore' ), cs_get_label_singular() ),
		'content'	=>
			'<p>' . __( '<strong>File Download Limit</strong> - Define how many times customers are allowed to download their purchased files. Leave at 0 for unlimited. Resending the purchase receipt will permit the customer one additional download if their limit has already been reached.', 'commercestore' ) . '</p>' .

			'<p>' . __( '<strong>Accounting Options</strong> - If enabled, define an individual SKU or product number for this download.', 'commercestore' ) . '</p>' .

			'<p>' . __( '<strong>Button Options</strong> - Disable the automatic output of the purchase button. If disabled, no button will be added to the download page unless the <code>[purchase_link]</code> shortcode is used.', 'commercestore' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'cs-download-prices',
		'title'	    => sprintf( __( '%s Prices', 'commercestore' ), cs_get_label_singular() ),
		'content'	=>
			'<p>' . __( '<strong>Enable variable pricing</strong> - By enabling variable pricing, multiple download options and prices can be configured.', 'commercestore' ) . '</p>' .

			'<p>' . __( '<strong>Enable multi-option purchases</strong> - By enabling multi-option purchases customers can add multiple variable price items to their cart at once.', 'commercestore' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'cs-download-files',
		'title'	    => sprintf( __( '%s Files', 'commercestore' ), cs_get_label_singular() ),
		'content'	=>
			'<p>' . __( '<strong>Product Type Options</strong> - Choose a default product type or a bundle. Bundled products automatically include access to other download&#39;s files when purchased.', 'commercestore' ) . '</p>' .

			'<p>' . __( '<strong>File Downloads</strong> - Define download file names and their respective file URL. Multiple files can be assigned to a single price, or variable prices.', 'commercestore' ) . '</p>'
	) );


	$screen->add_help_tab( array(
		'id'	    => 'cs-product-notes',
		'title'	    => sprintf( __( '%s Instructions', 'commercestore' ), cs_get_label_singular() ),
		'content'	=> '<p>' . sprintf( __( 'Special instructions for this %s. These will be added to the sales receipt, and may be used by some extensions or themes.', 'commercestore' ), strtolower( cs_get_label_singular() ) ) . '</p>'
	) );

	$colors = array(
		'gray', 'pink', 'blue', 'green', 'teal', 'black', 'dark gray', 'orange', 'purple', 'slate'
	);

	$screen->add_help_tab( array(
		'id'	    => 'cs-purchase-shortcode',
		'title'	    => __( 'Purchase Shortcode', 'commercestore' ),
		'content'	=>
			'<p>' . __( '<strong>Purchase Shortcode</strong> - If the automatic output of the purchase button has been disabled via the Download Configuration box, a shortcode can be used to output the button or link.', 'commercestore' ) . '</p>' .
			'<p><code>[purchase_link id="#" price="1" text="Add to Cart" color="blue"]</code></p>' .
			'<ul>
				<li><strong>id</strong> - ' . __( 'The ID of a specific download to purchase.', 'commercestore' ) . '</li>
				<li><strong>price</strong> - ' . __( 'Whether to show the price on the purchase button. 1 to show the price, 0 to disable it.', 'commercestore' ) . '</li>
				<li><strong>text</strong> - ' . __( 'The text to be displayed on the button or link.', 'commercestore' ) . '</li>
				<li><strong>style</strong> - ' . __( '<em>button</em> | <em>text</em> - The style of the purchase link.', 'commercestore' ) . '</li>
				<li><strong>color</strong> - <em>' . implode( '</em> | <em>', $colors ) . '</em></li>
				<li><strong>class</strong> - ' . __( 'One or more custom CSS classes you want applied to the button.', 'commercestore' ) . '</li>
			</ul>' .
			'<p>' . sprintf( __( 'For more information, see <a href="%s">using Shortcodes</a> on the WordPress.org Codex or <a href="%s">CommerceStore Documentation</a>', 'commercestore' ), 'https://codex.wordpress.org/Shortcode', 'http://docs.commercestore.com/article/229-purchaselink' ) . '</p>'
	) );

	/**
	 * Fires off in the CommerceStore Downloads Contextual Help Screen
	 *
	 * @since 1.2.3
	 * @param object $screen The current admin screen
	 */
	do_action( 'cs_downloads_contextual_help', $screen );
}
add_action( 'load-post.php', 'cs_downloads_contextual_help' );
add_action( 'load-post-new.php', 'cs_downloads_contextual_help' );
