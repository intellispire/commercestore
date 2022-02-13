<?php
namespace CS\Tests\Factory;

/**
 * Unit test factory for customer email addresses.
 *
 * Note: The below method notations are defined solely for the benefit of IDEs,
 * as a way to indicate expected return values from the given factory methods.
 *
 * @method int create( $args = array(), $generation_definitions = null )
 * @method \CS\Customers\Customer_Email_Address create_and_get( $args = array(), $generation_definitions = null )
 * @method int[] create_many( $count, $args = array(), $generation_definitions = null )
 *
 * @package CS\Tests\Factory
 */
class Customer_Email_Address extends \WP_UnitTest_Factory_For_Thing {

	public function __construct( $factory = null ) {
		parent::__construct( $factory );

		$this->default_generation_definitions = array(
			'customer_id' => new \WP_UnitTest_Generator_Sequence( '%d' ),
			'type'        => new \WP_UnitTest_Generator_Sequence( 'type%d' ),
			'status'      => new \WP_UnitTest_Generator_Sequence( 'status%d' ),
			'email'       => new \WP_UnitTest_Generator_Sequence( 'user%d@cs.test' ),
		);
	}

	public function create_object( $args ) {
		return cs_add_customer_email_address( $args );
	}

	public function update_object( $customer_id, $fields ) {
		return cs_update_customer_email_address( $customer_id, $fields );
	}

	public function delete( $customer_email_address_id ) {
		cs_delete_customer_email_address( $customer_email_address_id );
	}

	public function delete_many( $customers ) {
		foreach ( $customers as $customer ) {
			$this->delete( $customer );
		}
	}

	/**
	 * Stub out copy of parent method for IDE type hinting purposes.
	 *
	 * @param $customer_email_address_id Customer email address ID.
	 *
	 * @return \CS\Customers\Customer_Email_Address|false
	 */
	public function get_object_by_id( $customer_email_address_id ) {
		return cs_get_customer( $customer_email_address_id );
	}
}
