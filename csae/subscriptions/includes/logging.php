<?php
/**
 * Logging Functions
 * @package     EDD_Recurring
 * @subpackage  Logging
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.10.1
 */

add_action( 'init', 'edd_recurring_register_log_type' );
/**
 * Registers the EDD subscription log post type, and
 * edd_log_type taxonomy if it does not exist.
 *
 * @since 2.10.1
 * @return void
 */
function edd_recurring_register_log_type() {
	register_post_type(
		'edd_subscription_log',
		array(
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'query_var'          => false,
			'rewrite'            => false,
			'capability_type'    => 'page',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => array( 'title', 'editor' ),
			'taxonomies'         => array( 'edd_log_type' ),
		)
	);

	if ( ! taxonomy_exists( 'edd_log_type' ) ) {
		register_taxonomy( 'edd_log_type', 'edd_subscription_log', array( 'public' => false ) );
	}
}
