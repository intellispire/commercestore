<?php
/**
 * Component Functions
 *
 * This file includes functions for interacting with CommerceStore components. An CS
 * component is comprised of:
 *
 * - Database table/schema/query
 * - Object interface
 * - Optional meta-data
 *
 * Some examples of CommerceStore components are:
 *
 * - Customer
 * - Adjustment
 * - Order
 * - Order Item
 * - Note
 * - Log
 *
 * Add-ons and third party plugins are welcome to register their own component
 * in exactly the same way that CommerceStore does internally.
 *
 * @package     CS
 * @subpackage  Functions/Components
 * @since       3.0
*/

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Register a new CommerceStore component (customer, adjustment, order, etc...)
 *
 * @since 3.0
 *
 * @param string $name
 * @param array  $args
 */
function cs_register_component( $name = '', $args = array() ) {

	// Sanitize the component name
	$name = sanitize_key( $name );

	// Bail if name or args are empty
	if ( empty( $name ) || empty( $args ) ) {
		return;
	}

	// Parse arguments
	$r = wp_parse_args( $args, array(
		'name'   => $name,
		'schema' => '\\CS\\Database\\Schema',
		'table'  => '\\CS\\Database\\Table',
		'query'  => '\\CS\\Database\\Query',
		'object' => '\\CS\\Database\\Row',
		'meta'   => false
	) );

	// Setup the component
	CS()->components[ $name ] = new CS\Component( $r );

	// Component registered
	do_action( 'cs_registered_component', $name, $r, $args );
}

/**
 * Get an CommerceStore Component object
 *
 * @since 3.0
 * @param string $name
 *
 * @return CS\Component|false False if not exists, CS\Component if exists
 */
function cs_get_component( $name = '' ) {
	$name = sanitize_key( $name );

	// Return component if exists, or false
	return isset( CS()->components[ $name ] )
		? CS()->components[ $name ]
		: false;
}

/**
 * Get an CommerceStore Component interface
 *
 * @since 3.0
 * @param string $component
 * @param string $interface
 *
 * @return mixed False if not exists, CommerceStore Component interface if exists
 */
function cs_get_component_interface( $component = '', $interface = '' ) {

	// Get component
	$c = cs_get_component( $component );

	// Bail if no component
	if ( empty( $c ) ) {
		return $c;
	}

	// Return interface, or false if not exists
	return $c->get_interface( $interface );
}

/**
 * Setup all CommerceStore components
 *
 * @since 3.0
 */
function cs_setup_components() {
	static $setup = false;

	// Never register components more than 1 time per request
	if ( false !== $setup ) {
		return;
	}

	// Register customer.
	cs_register_component( 'customer', array(
		'schema' => '\\CS\\Database\\Schemas\\Customers',
		'table'  => '\\CS\\Database\\Tables\\Customers',
		'meta'   => '\\CS\\Database\\Tables\\Customer_Meta',
		'query'  => '\\CS\\Database\\Queries\\Customer',
		'object' => 'CS_Customer'
	) );

	// Register customer address.
	cs_register_component( 'customer_address', array(
		'schema' => '\\CS\\Database\\Schemas\\Customer_Addresses',
		'table'  => '\\CS\\Database\\Tables\\Customer_Addresses',
		'query'  => '\\CS\\Database\\Queries\\Customer_Address',
		'object' => '\\CS\\Customers\\Customer_Address',
		'meta'   => false
	) );

	// Register customer email address.
	cs_register_component( 'customer_email_address', array(
		'schema' => '\\CS\\Database\\Schemas\\Customer_Email_Addresses',
		'table'  => '\\CS\\Database\\Tables\\Customer_Email_Addresses',
		'query'  => '\\CS\\Database\\Queries\\Customer_Email_Address',
		'object' => '\\CS\\Customers\\Customer_Email_Address',
		'meta'   => false
	) );

	// Register adjustment.
	cs_register_component( 'adjustment', array(
		'schema' => '\\CS\\Database\\Schemas\\Adjustments',
		'table'  => '\\CS\\Database\\Tables\\Adjustments',
		'meta'   => '\\CS\\Database\\Tables\\Adjustment_Meta',
		'query'  => '\\CS\\Database\\Queries\\Adjustment',
		'object' => '\\CS\\Adjustments\\Adjustment'
	) );

	// Register note.
	cs_register_component( 'note', array(
		'schema' => '\\CS\\Database\\Schemas\\Notes',
		'table'  => '\\CS\\Database\\Tables\\Notes',
		'meta'   => '\\CS\\Database\\Tables\\Note_Meta',
		'query'  => '\\CS\\Database\\Queries\\Note',
		'object' => '\\CS\\Notes\\Note'
	) );

	// Register order.
	cs_register_component( 'order', array(
		'schema' => '\\CS\\Database\\Schemas\\Orders',
		'table'  => '\\CS\\Database\\Tables\\Orders',
		'meta'   => '\\CS\\Database\\Tables\\Order_Meta',
		'query'  => '\\CS\\Database\\Queries\\Order',
		'object' => '\\CS\\Orders\\Order'
	) );

	// Register order item.
	cs_register_component( 'order_item', array(
		'schema' => '\\CS\\Database\\Schemas\\Order_Items',
		'table'  => '\\CS\\Database\\Tables\\Order_Items',
		'meta'   => '\\CS\\Database\\Tables\\Order_Item_Meta',
		'query'  => '\\CS\\Database\\Queries\\Order_Item',
		'object' => '\\CS\\Orders\\Order_Item'
	) );

	// Register order adjustment.
	cs_register_component( 'order_adjustment', array(
		'schema' => '\\CS\\Database\\Schemas\\Order_Adjustments',
		'table'  => '\\CS\\Database\\Tables\\Order_Adjustments',
		'meta'   => '\\CS\\Database\\Tables\\Order_Adjustment_Meta',
		'query'  => '\\CS\\Database\\Queries\\Order_Adjustment',
		'object' => '\\CS\\Orders\\Order_Adjustment',
	) );

	// Register order address.
	cs_register_component( 'order_address', array(
		'schema' => '\\CS\\Database\\Schemas\\Order_Addresses',
		'table'  => '\\CS\\Database\\Tables\\Order_Addresses',
		'query'  => '\\CS\\Database\\Queries\\Order_Address',
		'object' => '\\CS\\Orders\\Order_Address',
		'meta'   => false
	) );

	// Register order transaction.
	cs_register_component( 'order_transaction', array(
		'schema' => '\\CS\\Database\\Schemas\\Order_Transactions',
		'table'  => '\\CS\\Database\\Tables\\Order_Transactions',
		'query'  => '\\CS\\Database\\Queries\\Order_Transaction',
		'object' => '\\CS\\Orders\\Order_Transaction',
		'meta'   => false
	) );

	// Register log.
	cs_register_component( 'log', array(
		'schema' => '\\CS\\Database\\Schemas\\Logs',
		'table'  => '\\CS\\Database\\Tables\\Logs',
		'meta'   => '\\CS\\Database\\Tables\\Log_Meta',
		'query'  => '\\CS\\Database\\Queries\\Log',
		'object' => '\\CS\\Logs\\Log'
	) );

	// Register log API request.
	cs_register_component( 'log_api_request', array(
		'schema' => '\\CS\\Database\\Schemas\\Logs_Api_Requests',
		'table'  => '\\CS\\Database\\Tables\\Logs_Api_Requests',
		'meta'   => '\\CS\\Database\\Tables\\Logs_Api_Request_Meta',
		'query'  => '\\CS\\Database\\Queries\\Log_Api_Request',
		'object' => '\\CS\\Logs\\Api_Request_Log',
	) );

	// Register log file download.
	cs_register_component( 'log_file_download', array(
		'schema' => '\\CS\\Database\\Schemas\\Logs_File_Downloads',
		'table'  => '\\CS\\Database\\Tables\\Logs_File_Downloads',
		'meta'   => '\\CS\\Database\\Tables\\Logs_File_Download_Meta',
		'query'  => '\\CS\\Database\\Queries\\Log_File_Download',
		'object' => '\\CS\\Logs\\File_Download_Log',
	) );

	// Set the locally static setup var.
	$setup = true;

	// Action to allow third party components to be setup.
	do_action( 'cs_setup_components' );
}

/**
 * Install all component database tables
 *
 * This function installs all database tables used by all components (including
 * third-party and add-ons that use the Component API)
 *
 * This is used by unit tests and tools.
 *
 * @since 3.0
 */
function cs_install_component_database_tables() {

	// Get the components
	$components = CS()->components;

	// Bail if no components setup yet
	if ( empty( $components ) ) {
		return;
	}

	// Drop all component tables
	foreach ( $components as $component ) {

		// Objects
		$object = $component->get_interface( 'table' );
		if ( $object instanceof \CS\Database\Table && ! $object->exists() ) {
			$object->install();
		}

		// Meta
		$meta = $component->get_interface( 'meta' );
		if ( $meta instanceof \CS\Database\Table && ! $meta->exists() ) {
			$meta->install();
		}
	}
}

/**
 * Uninstall all component database tables
 *
 * This function is destructive and disastrous, so do not call it directly
 * unless you fully intend to destroy all data (including third-party add-ons
 * that use the Component API)
 *
 * This is used by unit tests and tools.
 *
 * @since 3.0
 */
function cs_uninstall_component_database_tables() {

	// Get the components
	$components = CS()->components;

	// Bail if no components setup yet
	if ( empty( $components ) ) {
		return;
	}

	// Drop all component tables
	foreach ( $components as $component ) {

		// Objects
		$object = $component->get_interface( 'table' );
		if ( $object instanceof \CS\Database\Table && $object->exists() ) {
			$object->uninstall();
		}

		// Meta
		$meta = $component->get_interface( 'meta' );
		if ( $meta instanceof \CS\Database\Table && $meta->exists() ) {
			$meta->uninstall();
		}
	}
}
