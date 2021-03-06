<?php
/**
 * Import Actions
 *
 * These are actions related to import data from CommerceStore.
 *
 * @package     CS
 * @subpackage  Admin/Import
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Add a hook allowing extensions to register a hook on the batch export process
 *
 * @since  2.6
 * @return void
 */
function cs_register_batch_importers() {
	if ( is_admin() ) {
		do_action( 'cs_register_batch_importer' );
	}
}
add_action( 'plugins_loaded', 'cs_register_batch_importers' );

/**
 * Register the payments batch importer
 *
 * @since  2.6
 */
function cs_register_payments_batch_import() {
	add_action( 'cs_batch_import_class_include', 'cs_include_payments_batch_import_processer', 10 );
}
add_action( 'cs_register_batch_importer', 'cs_register_payments_batch_import', 10 );

/**
 * Loads the payments batch process if needed
 *
 * @since  2.6
 * @param  string $class The class being requested to run for the batch import
 * @return void
 */
function cs_include_payments_batch_import_processer( $class ) {

	if ( 'CS_Batch_Payments_Import' === $class ) {
		require_once CS_PLUGIN_DIR . 'includes/admin/import/class-batch-import-payments.php';
	}

}

/**
 * Register the downloads batch importer
 *
 * @since  2.6
 */
function cs_register_downloads_batch_import() {
	add_action( 'cs_batch_import_class_include', 'cs_include_downloads_batch_import_processer', 10 );
}
add_action( 'cs_register_batch_importer', 'cs_register_downloads_batch_import', 10 );

/**
 * Loads the downloads batch process if needed
 *
 * @since  2.6
 * @param  string $class The class being requested to run for the batch import
 * @return void
 */
function cs_include_downloads_batch_import_processer( $class ) {

	if ( 'CS_Batch_Downloads_Import' === $class ) {
		require_once CS_PLUGIN_DIR . 'includes/admin/import/class-batch-import-downloads.php';
	}

}