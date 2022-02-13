<?php
namespace CS\Tests\Factory;

class Order extends \WP_UnitTest_Factory_For_Thing {

	public function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'status'          => 'complete',
			'type'            => 'sale',
			'date_completed'  => CS()->utils->date( 'now' )->toDateTimeString(),
			'date_refundable' => CS()->utils->date( 'now' )->addDays( 30 )->toDateTimeString(),
			'user_id'         => new \WP_UnitTest_Generator_Sequence( '%d' ),
			'customer_id'     => new \WP_UnitTest_Generator_Sequence( '%d' ),
			'email'           => new \WP_UnitTest_Generator_Sequence( 'user%d@cs.test' ),
			'ip'              => '10.1.1.1',
			'gateway'         => 'manual',
			'mode'            => 'live',
			'currency'        => 'USD',
			'payment_key'     => md5( 'cs' ),
			'subtotal'        => 100,
			'tax'             => 25,
			'discount'        => 5,
			'total'           => 120,
		);
	}

	/**
	 * Stub out copy of parent method for IDE type hinting purposes.
	 *
	 * @param array $args
	 * @param null  $generation_definitions
	 *
	 * @return \CS\Orders\Order|false
	 */
	public function create_and_get( $args = array(), $generation_definitions = null ) {
		return parent::create_and_get( $args, $generation_definitions );
	}

	public function create_object( $args ) {
		$order_id = cs_add_order( $args );

		$oid = cs_add_order_item( array(
			'order_id'     => $order_id,
			'product_id'   => 1,
			'product_name' => 'Simple Download',
			'status'       => 'inherit',
			'amount'       => 100,
			'subtotal'     => 100,
			'discount'     => 5,
			'tax'          => 25,
			'total'        => 120,
			'quantity'     => 1,
		) );

		cs_add_order_adjustment( array(
			'object_type' => 'order',
			'object_id'   => $order_id,
			'type'        => 'tax_rate',
			'total'       => '0.25',
		) );

		cs_add_order_address( array(
			'order_id'   => $order_id,
			'first_name' => 'John',
			'last_name'  => 'Smith',
			'country'    => 'US',
		) );

		return $order_id;
	}

	public function update_object( $order_id, $fields ) {
		return cs_update_order( $order_id, $fields );
	}

	public function delete( $order_id ) {
		cs_destroy_order( $order_id );
	}

	public function delete_many( $orders ) {
		foreach ( $orders as $order ) {
			$this->delete( $order );
		}
	}

	/**
	 * Stub out copy of parent method for IDE type hinting purposes.
	 *
	 * @param $order_id Order ID.
	 *
	 * @return \CS\Orders\Order|false
	 */
	public function get_object_by_id( $order_id ) {
		return cs_get_order( $order_id );
	}
}