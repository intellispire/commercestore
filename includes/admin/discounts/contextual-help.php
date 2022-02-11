<?php
/**
 * Contextual Help
 *
 * @package     CS
 * @subpackage  Admin/Discounts
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2.3
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Adds the Contextual Help for the Discount Codes Page
 *
 * @since 1.3
 * @return void
 */
function cs_discounts_contextual_help() {
	$screen = get_current_screen();

	$screen->set_help_sidebar(
		'<p><strong>' . __( 'For more information:', 'commercestore' ) . '</strong></p>' .
		'<p>' . sprintf( __( 'Visit the <a href="%s">documentation</a> on the CommerceStore website.', 'commercestore' ), esc_url( 'http://docs.commercestore.com/' ) ) . '</p>' .
		'<p>' . sprintf(
			__( 'Need more from your CommerceStore store? <a href="%s">Upgrade Now</a>!', 'commercestore' ),
			esc_url( 'https://commercestore.com/pricing/?utm_source=plugin-settings-page&utm_medium=contextual-help-sidebar&utm_term=pricing&utm_campaign=ContextualHelp' )
		) . '</p>'
	);

	$screen->add_help_tab( array(
		'id'	    => 'cs-discount-general',
		'title'	    => __( 'General', 'commercestore' ),
		'content'	=>
			'<p>' . __( 'Discount codes allow you to offer buyers special discounts by having them enter predefined codes during checkout.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Discount codes that are set to "inactive" cannot be redeemed.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Discount codes can be setup to only be used only one time by each customer. If a customer attempts to use a code a second time, they will be given an error.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Discount codes that have already been used cannot be deleted for data integrity and reporting purposes.', 'commercestore' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'cs-discount-add',
		'title'	    => __( 'Adding Discounts', 'commercestore' ),
		'content'	=>
			'<p>' . __( 'You can create any number of discount codes easily from this page.', 'commercestore' ) . '</p>' .
			'<p>' . __( 'Discount codes have several options:', 'commercestore' ) . '</p>' .
			'<ul>'.
				'<li>' . __( '<strong>Name</strong> - this is the name given to the discount. Used primarily for administrative purposes.', 'commercestore' ) . '</li>' .
				'<li>' . __( '<strong>Code</strong> - this is the unique code that customers will enter during checkout to redeem the code.', 'commercestore' ) . '</li>' .
				'<li>' . __( '<strong>Type</strong> - this is the type of discount this code awards.', 'commercestore' ) . '</li>' .
				'<li>' . __( '<strong>Amount</strong> - this is the discount amount provided by this code. For percentage based discounts, enter a number such as 70 for 70%. Do not enter a percent sign.', 'commercestore' ) . '</li>' .
				'<li>' . __( '<strong>Requirements</strong> - this allows you to select the product(s) that are required to be purchased in order for a discount to be applied.', 'commercestore' ) . '</li>' .
				'<li>' . __( '<strong>Condition</strong> - this lets you set whether all selected products must be in the cart, or just a minimum of one.', 'commercestore' ) . '</li>' .
				'<li>' . __( '<strong>Apply discount only to selected Downloads?</strong> - If this box is checked, only the prices of the required products will be discounted. If left unchecked, the discount will apply to all products in the cart.', 'commercestore' ) . '</li>' .
				'<li>' . __( '<strong>Start Date</strong> - this is the date that this code becomes available. If a customer attempts to redeem the code prior to this date, they will be given an error. This is optional.', 'commercestore' ) . '</li>' .
				'<li>' . __( '<strong>Expiration Date</strong> - this is the end date for the discount. After this date, the code will no longer be able to be used. This is optional.', 'commercestore' ) . '</li>' .
				'<li>' . __( '<strong>Minimum Amount</strong> - this is the minimum purchase amount required to use this code. If a customer has less than this amount in their cart, they will be given an error. This is optional.', 'commercestore' ) . '</li>' .
				'<li>' . __( '<strong>Max Uses</strong> - this is the maximum number of times this discount can be redeemed. Once this number is reached, no more customers will be allowed to use it.', 'commercestore' ) . '</li>' .
			'</ul>'
	) );

	do_action( 'cs_discounts_contextual_help', $screen );
}
add_action( 'load-download_page_cs-discounts', 'cs_discounts_contextual_help' );
