<?php
/**
 * Downgrades
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 * @since     2.11
 */

/**
 * Checks if the current site has downgraded, and if so, performs any necessary actions.
 *
 * @since 2.11
 * @return bool Whether or not a downgrade was performed.
 */
function cs_do_downgrade() {
	$did_downgrade   = false;
	$cs_version     = preg_replace( '/[^0-9.].*/', '', get_option( 'cs_version' ) );
	$downgraded_from = get_option( 'cs_version_downgraded_from' );

	/**
	 * Check for downgrade from 3.0 to 2.11.
	 */
	if ( version_compare( CS_VERSION, '3.0-beta1', '<' ) ) {
		if (
			version_compare( $cs_version, '3.0-beta1', '>=' ) ||
			( $downgraded_from && version_compare( $downgraded_from, '3.0-beta1', '>=' ) )
		) {
			/*
			 * This site probably just downgraded from CommerceStore 3.0. Let's perform a downgrade.
			 */
			$did_downgrade = cs_maybe_downgrade_from_v3();
		}
	}

	if ( $did_downgrade ) {
		update_option( 'cs_version', preg_replace( '/[^0-9.].*/', '', CS_VERSION ) );
		delete_option( 'cs_version_downgraded_from' );
	}

	return $did_downgrade;
}
add_action( 'admin_init', 'cs_do_downgrade' );

/**
 * Performs a database downgrade from CommerceStore 3.0 to 2.11 if one is needed.
 * The main operation here is changing the customer meta column from `cs_customer_id` (v3.0 version)
 * back to `customer_id` for v2.x.
 *
 * @since 2.11
 * @return bool Whether the downgrade was performed.
 */
function cs_maybe_downgrade_from_v3() {
	global $wpdb;
	$customer_meta_table = CS()->customer_meta->table_name;

	// If there is no column called `cs_customer_id`, then we don't need to downgrade.
	$columns = $wpdb->query( "SHOW COLUMNS FROM {$customer_meta_table} LIKE 'cs_customer_id'");
	if ( empty( $columns ) ) {
		return false;
	}

	$wpdb->query( "ALTER TABLE {$customer_meta_table} CHANGE `cs_customer_id` `customer_id` bigint(20) unsigned NOT NULL default '0'" );
	$wpdb->query( "ALTER TABLE {$customer_meta_table} DROP INDEX cs_customer_id" );
	$wpdb->query( "ALTER TABLE {$customer_meta_table} ADD INDEX customer_id (customer_id)" );

	// These two calls re-add the table version numbers for us.
	CS()->customer_meta->create_table();
	CS()->customers->create_table();

	cs_debug_log( 'Completed downgrade from CommerceStore 3.0.', true );

	return true;
}
