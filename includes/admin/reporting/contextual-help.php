<?php
/**
 * Contextual Help
 *
 * @package     CS
 * @subpackage  Admin/Reports
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.4
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Reports contextual help.
 *
 * @access      private
 * @since       1.4
 * @return      void
 */
function cs_reporting_contextual_help() {
	$screen = get_current_screen();

	if ( $screen->id != 'download_page_cs-reports' )
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
		'id'	    => 'cs-reports',
		'title'	    => __( 'Reports', 'commercestore' ),
		'content'	=> '<p>' . __( 'This screen provides you with reports for your earnings, downloads, customers and taxes.', 'commercestore' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'cs-reports-export',
		'title'	    => __( 'Export', 'commercestore' ),
		'content'	=>
			'<p>' . __( 'This screen allows you to export your reports into a CSV format.', 'commercestore' ) . '</p>' .
			'<p>' . __( '<strong>Sales and Earnings</strong> - This report exports all of the sales and earnings that you have made in the current year. It includes your sales and earnings for each product as well a graphs of sales and earnings so you can compare them for each month.', 'commercestore' ) . '</p>' .
			'<p>' . __( '<strong>Payment History</strong> - This report exports all of the payments you have received on your CommerceStore store in a CSV format.  It includes the contact details of the customer, the products they have purchased as well as any discount codes they have used and the final price they have paid.', 'commercestore' ) . '</p>' .
			'<p>' . __( "<strong>Customers</strong> - This report exports all of your customers in a CSV format. It exports the customer's name and email address and the amount of products they have purchased as well as the final price of their total purchases.", 'commercestore' ) . '</p>' .
			'<p>' . __( '<strong>Download History</strong> - This report exports all of the downloads you have received in the current month into a CSV. It exports the date the file was downloaded, the customer it was downloaded by, their IP address, the name of the product and the file they downloaded.', 'commercestore' ) . '</p>'
	) );

	if( ! empty( $_GET['tab'] ) && 'logs' == $_GET['tab'] ) {
		$screen->add_help_tab( array(
			'id'	    => 'cs-reports-log-search',
			'title'	    => __( 'Search File Downloads', 'commercestore' ),
			'content'	=>
				'<p>' . __( 'The file download log can be searched in several different ways:', 'commercestore' ) . '</p>' .
				'<ul>
					<li>' . __( 'You can enter the customer\'s email address', 'commercestore' ) . '</li>
					<li>' . __( 'You can enter the customer\'s IP address', 'commercestore' ) . '</li>
					<li>' . __( 'You can enter the download file\'s name', 'commercestore' ) . '</li>
				</ul>'
		) );
	}

	do_action( 'cs_reports_contextual_help', $screen );
}
add_action( 'load-download_page_cs-reports', 'cs_reporting_contextual_help' );
