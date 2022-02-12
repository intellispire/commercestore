<?php
/**
 * Order Type Functions
 *
 * @package     CS
 * @subpackage  Orders
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Retrieve the registered order types.
 *
 * @since 3.0
 *
 * @return array
 */
function cs_get_order_types() {

	// Get the
	$component = cs_get_component( 'order' );

	// Setup an empty types array
	if ( ! isset( $component->types ) ) {
		$component->types = array();
	}

	// Return types
	return (array) $component->types;
}

/**
 * Register an Order Type.
 *
 * @since 3.0
 *
 * @param array $args
 */
function cs_register_order_type( $type = '', $args = array() ) {

	// Sanitize the type
	$type = sanitize_key( $type );

	// Parse args
	$r = wp_parse_args( $args, array(
		'show_ui' => true,
		'labels'  => array(
			'singular' => '',
			'plural'   => ''
		)
	) );

	// Get the
	$component = cs_get_component( 'order' );

	// Setup an empty types array
	if ( ! isset( $component->types ) ) {
		$component->types = array();
	}

	// Add the order type to the `types` array
	$component->types[ $type ] = $r;
}

/**
 * Register the default Order Types.
 *
 * @since 3.0
 */
function cs_register_default_order_types( $name = '' ) {

	// Bail if not the `order` name
	if ( 'order' !== $name ) {
		return;
	}

	// Sales
	cs_register_order_type( 'sale', array(
		'labels' => array(
			'singular' => __( 'Order',  'commercestore' ),
			'plural'   => __( 'Orders', 'commercestore' )
		)
	) );

	// Refunds
	cs_register_order_type( 'refund', array(
		'labels' => array(
			'singular' => __( 'Refund',  'commercestore' ),
			'plural'   => __( 'Refunds', 'commercestore' )
		)
	) );

	// Invoices
	cs_register_order_type( 'invoice', array(
		'show_ui' => false,
		'labels'  => array(
			'singular' => __( 'Invoice',  'commercestore' ),
			'plural'   => __( 'Invoices', 'commercestore' )
		)
	) );
}
add_action( 'cs_registered_component', 'cs_register_default_order_types' );
