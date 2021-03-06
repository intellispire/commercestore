<?php
/**
 * Adjustments
 *
 * These are functions used for displaying discounts, credits, fees, and more.
 *
 * @package     CS
 * @subpackage  Admin/Tools
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Shows the adjustments page, containing of all registered & visible adjustment
 * types (Discounts|Credits|Fees)
 *
 * @since 3.0
 * @author Daniel J Griffiths
 */
function cs_adjustments_page() {

	// Get all tabs
	$all_tabs = cs_get_adjustments_tabs();

	// Current tab
	$active_tab = isset( $_GET['tab'] )
		? sanitize_key( $_GET['tab'] )
		: 'discount';

	// Add new URL
	$add_new_url = cs_get_admin_url( array(
		'page'       => 'cs-discounts',
		'cs-action' => 'add_' . $active_tab
	) );

	// Start the output buffer
	ob_start(); ?>

	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Discounts', 'commercestore' ); ?></h1>
		<a href="<?php echo esc_url( $add_new_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'commercestore' ); ?></a>

		<hr class="wp-header-end">
		<?php if ( 1 < count( $all_tabs ) ) : ?>

		<nav class="nav-tab-wrapper cs-nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Secondary menu', 'commercestore' ); ?>">
			<?php

			// Loop through all tabs
			foreach ( $all_tabs as $tab_id => $tab_name ) :

				// Add the tab ID
				$tab_url = cs_get_admin_url( array(
					'page' => 'cs-discounts',
					'tab'  => $tab_id
				) );

				// Remove messages
				$tab_url = remove_query_arg( array(
					'cs-message',
				), $tab_url );

				// Setup the selected class
				$active = ( $active_tab === $tab_id )
					? ' nav-tab-active'
					: ''; ?>

				<a href="<?php echo esc_url( $tab_url ); ?>" class="nav-tab<?php echo $active; ?>"><?php echo esc_html( $tab_name ); ?></a>

			<?php endforeach; ?>

			</nav>
		<br>
		<?php endif; ?>

		<?php do_action( 'cs_adjustments_page_' . $active_tab ); ?>
	</div><!-- .wrap -->

	<?php

	// Output the current buffer
	echo ob_get_clean();
}

/**
 * Retrieve adjustments tabs.
 *
 * @since 3.0
 *
 * @return array Tabs for the 'Adjustments' page.
 */
function cs_get_adjustments_tabs() {

	// Tabs
	$tabs = array(
		'discount' => __( 'Discounts', 'commercestore' ),
//		'credit'   => __( 'Credits',   'commercestore' ),
//		'fee'      => __( 'Fees',      'commercestore' )
	);

	// Filter & return
	return apply_filters( 'cs_adjustments_tabs', $tabs );
}
