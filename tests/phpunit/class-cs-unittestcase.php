<?php

use PHPUnit\Util\Test;

require_once dirname( __FILE__ ) . '/factory.php';

/**
 * Defines a basic fixture to run multiple tests.
 *
 * Resets the state of the WordPress installation before and after every test.
 *
 * Includes utility functions and assertions useful for testing WordPress.
 *
 * All WordPress unit tests should inherit from this class.
 */
class CS_UnitTestCase extends WP_UnitTestCase {

	/**
	 * Holds the original GMT offset for restoration during class tear down.
	 *
	 * @var string
	 */
	public static $original_gmt_offset;

	public static function setUpBeforeClass() : void  {
		parent::setUpBeforeClass() ;

		cs_install();

		global $current_user;

		$current_user = new WP_User(1);
		$current_user->set_role('administrator');
		wp_update_user( array( 'ID' => 1, 'first_name' => 'Admin', 'last_name' => 'User' ) );
		add_filter( 'cs_log_email_errors', '__return_false' );
	}

	public static function tearDownAfterClass() : void {
		self::_delete_all_cs_data();

		delete_option( 'gmt_offset' );

		CS()->utils->get_gmt_offset( true );

		parent::tearDownAfterClass();
	}

	/**
	 * Runs before each test method.
	 */
	public function set_up() {
		parent::set_up();

		$this->expectDeprecatedCS();
	}

	/**
	 * Sets up logic for the @expectCSDeprecated annotation for deprecated elements in CS.
	 */
	function expectDeprecatedCS() {

		return;

		$annotations = $this->getAnnotations();
		foreach ( array( 'class', 'method' ) as $depth ) {
			if ( ! empty( $annotations[ $depth ]['expectCSDeprecated'] ) ) {
				$this->expected_deprecated = array_merge( $this->expected_deprecated, $annotations[ $depth ]['expectCSDeprecated'] );
			}
		}
		add_action( 'cs_deprecated_function_run', array( $this, 'deprecated_function_run' ) );
		add_action( 'cs_deprecated_argument_run', array( $this, 'deprecated_function_run' ) );
		add_action( 'cs_deprecated_hook_run', array( $this, 'deprecated_function_run' ) );
		add_action( 'cs_deprecated_function_trigger_error', '__return_false' );
		add_action( 'cs_deprecated_argument_trigger_error', '__return_false' );
		add_action( 'cs_deprecated_hook_trigger_error', '__return_false' );
	}

	protected static function cs() {
		static $factory = null;
		if ( ! $factory ) {
			$factory = new CS\Tests\Factory();
		}
		return $factory;
	}

	protected static function _delete_all_cs_data() {
		$components = CS()->components;

		foreach ( $components as $component ) {
			$thing = $component->get_interface( 'table' );

			if ( $thing instanceof \CS\Database\Table ) {
				$thing->truncate();
			}

			$thing = $component->get_interface( 'meta' );

			if ( $thing instanceof \CS\Database\Table ) {
				$thing->truncate();
			}
		}
	}

	/**
	 * Checks if all items in the array are of the given type.
	 *
	 * @param string $type   Type to check against.
	 * @param array  $actual Supplied array to check.
	 */
	public function assertStringContainsStringOnlyType( $type, $actual ) {
		$standard_types = array(
			'numeric', 'integer', 'int', 'float', 'string', 'boolean', 'bool',
			'null', 'array', 'object', 'resource', 'scalar'
		);


		if ( in_array( $type, $standard_types, true ) ) {
				$constraint = new \PHPUnit\Framework\Constraint\isType( $type );
		} else {
				$constraint = new \PHPUnit\Framework\Constraint\IsInstanceOf( $type );
		}

		foreach ( $actual as $item ) {
			if ( class_exists( '\PHPUnit\Framework\Assert' ) ) {
				\PHPUnit\Framework\Assert::assertThat( $item, $constraint );
			} else {
				\PHPUnit_Framework_Assert::assertThat( $item, $constraint );
			}
		}
	}

	/**
	 * Polyfill getAnnotations()
	 *
	 * @return array
	 */
	public function getAnnotations() : array {
		 $annotations = Test::parseTestMethodAnnotations(
			 static::class,
		   ''
		 );
		 return $annotations;
	}

	/**
	 * Polyfill assertInternalTypes
	 */

	public function assertInternalType($type, $var, $message = '') {
		switch ($type) {
			case 'int': return $this->assertIsInt($var, $message);
			case 'bool': return $this->assertIsBool($var, $message);
			case 'array': return $this->assertIsArray($var, $message);
			case 'float': return $this->assertIsFloat($var, $message);
			case 'object': return $this->assertIsObject($var, $message);
			case 'string': return $this->assertIsString($var, $message);
			case 'numeric': return $this->assertIsNumeric($var, $message);
			default:
				$this->assertFalse(true, 'Missing assertInternalTypeTest for: '. $type);
				return false;
		}
	}



}
