<?php
/**
 * Logging Functions
 * @package     CS_Recurring
 * @subpackage  Logging
 * @copyright   Copyright (c) 2021, Sandhills Development, LLC
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.10.1
 */

add_action( 'init', 'cs_recurring_register_log_type' );
/**
 * Registers the CS subscription log post type, and
 * cs_log_type taxonomy if it does not exist.
 *
 * @since 2.10.1
 * @return void
 */
function cs_recurring_register_log_type() {
	register_post_type(
		'cs_subscription_log',
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
			'taxonomies'         => array( 'cs_log_type' ),
		)
	);

	if ( ! taxonomy_exists( 'cs_log_type' ) ) {
		register_taxonomy( 'cs_log_type', 'cs_subscription_log', array( 'public' => false ) );
	}
}
