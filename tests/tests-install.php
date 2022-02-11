<?php


/**
 * @group cs_activation
 */
class Tests_Activation extends CS_UnitTestCase {

	/**
	 * SetUp test class.
	 *
	 * @since 2.1.0
	 */
	public function setUp() {
		parent::setUp();
	}

	/**
	 * Test if the global settings are set and have settings pages.
	 *
	 * @since 2.1.0
	 */
	public function test_settings() {
		global $cs_options;
		$this->assertArrayHasKey( 'purchase_page', $cs_options );
		$this->assertArrayHasKey( 'success_page', $cs_options );
		$this->assertArrayHasKey( 'failure_page', $cs_options );
	}

	/**
	 * Test the install function, installing pages and setting option values.
	 *
	 * @since 2.2.4
	 */
	public function test_install() {

		global $cs_options;

		$origin_cs_options   = $cs_options;
		$origin_upgraded_from = get_option( 'cs_version_upgraded_from' );
		$origin_cs_version   = cs_get_db_version();

		// Prepare values for testing
		delete_option( 'cs_settings' ); // Needed for the install test to succeed
		update_option( 'cs_version', '2.1' );
		$cs_options = array();

		cs_install();

		// Test that function exists
		$this->assertTrue( function_exists( 'cs_create_protection_files' ) );

		// Test the cs_version_upgraded_from value
		$this->assertEquals( get_option( 'cs_version_upgraded_from' ), '2.1' );

		// Test that new pages are created, and not the same as the already created ones.
		// This is to make sure the test is giving the most accurate results.
		$new_settings = get_option( 'cs_settings' );

		$this->assertArrayHasKey( 'purchase_page', $new_settings );
		$this->assertNotEquals( $origin_cs_options['purchase_page'], $new_settings['purchase_page'] );
		$this->assertArrayHasKey( 'success_page', $new_settings );
		$this->assertNotEquals( $origin_cs_options['success_page'], $new_settings['success_page'] );
		$this->assertArrayHasKey( 'failure_page', $new_settings );
		$this->assertNotEquals( $origin_cs_options['failure_page'], $new_settings['failure_page'] );
		$this->assertArrayHasKey( 'purchase_history_page', $new_settings );
		$this->assertNotEquals( $origin_cs_options['purchase_history_page'], $new_settings['purchase_history_page'] );

		$this->assertEquals( cs_format_db_version( CS_VERSION ), get_option( 'cs_version' ) );

		$this->assertInstanceOf( 'WP_Role', get_role( 'shop_manager' ) );
		$this->assertInstanceOf( 'WP_Role', get_role( 'shop_accountant' ) );
		$this->assertInstanceOf( 'WP_Role', get_role( 'shop_worker' ) );
		$this->assertInstanceOf( 'WP_Role', get_role( 'shop_vendor' ) );

		// Reset to original data.
		wp_delete_post( $new_settings['purchase_page'], true );
		wp_delete_post( $new_settings['success_page'], true );
		wp_delete_post( $new_settings['purchase_history_page'], true );
		wp_delete_post( $new_settings['failure_page'], true );
		update_option( 'cs_version_upgraded_from', $origin_upgraded_from );
		$cs_options = $origin_cs_options;
		update_option( 'cs_settings', $cs_options );
		update_option( 'cs_version', $origin_cs_version );

	}

	/**
	 * Test that the install doesn't redirect when activating multiple plugins.
	 *
	 * @since 2.2.4
	 */
	public function test_install_bail() {

		$_GET['activate-multi'] = 1;

		cs_install();

		$this->assertFalse( get_transient( 'activate-multi' ) );

	}

	/**
	 * Test cs_after_install(). Test that the transient gets deleted.
	 *
	 * Since 2.2.4
	 */
	public function test_cs_ater_install() {

		// Prepare for test
		set_transient( '_cs_installed', $GLOBALS['cs_options'], 30 );

		// Fake admin screen
		set_current_screen( 'dashboard' );

		$this->assertNotFalse( get_transient( '_cs_installed' ) );

		cs_after_install();

		$this->assertFalse( get_transient( '_cs_installed' ) );

	}

	/**
	 * Test that when not in admin, the function bails.
	 *
	 * @since 2.2.4
	 */
	public function test_cs_after_install_bail_no_admin() {

		// Prepare for test
		set_current_screen( 'front' );
		set_transient( '_cs_installed', $GLOBALS['cs_options'], 30 );

		cs_after_install();
		$this->assertNotFalse( get_transient( '_cs_installed' ) );

	}


	/**
	 * Test that cs_after_install() bails when transient doesn't exist.
	 * Kind of a useless test, but for coverage :-)
	 *
	 * @since 2.2.4
	 */
	public function test_cs_after_install_bail_transient() {

		// Fake admin screen
		set_current_screen( 'dashboard' );

		delete_transient( '_cs_installed' );

		$this->assertNull( cs_after_install() );

		// Reset to origin
		set_transient( '_cs_installed', $GLOBALS['cs_options'], 30 );

	}

	/**
	 * Test that cs_install_roles_on_network() bails when $wp_roles is no object.
	 * Kind of a useless test, but for coverage :-)
	 *
	 * @since 2.2.4
	 */
	public function test_cs_install_roles_on_network_bail_object() {

		global $wp_roles;

		$origin_roles = $wp_roles;

		$wp_roles = null;

		$this->assertNull( cs_install_roles_on_network() );

		// Reset to origin
		$wp_roles = $origin_roles;

	}

	/**
	 * Test that cs_install_roles_on_network() creates the roles when 'shop_manager' is not defined.
	 *
	 * @since 2.2.4
	 */
	public function test_cs_install_roles_on_network() {

		global $wp_roles;

		$origin_roles = $wp_roles;

		// Prepare variables for test
		unset( $wp_roles->roles['shop_manager'] );

		cs_install_roles_on_network();

		// Test that the roles are created
		$this->assertInstanceOf( 'WP_Role', get_role( 'shop_manager' ) );
		$this->assertInstanceOf( 'WP_Role', get_role( 'shop_accountant' ) );
		$this->assertInstanceOf( 'WP_Role', get_role( 'shop_worker' ) );
		$this->assertInstanceOf( 'WP_Role', get_role( 'shop_vendor' ) );


		// Reset to origin
		$wp_roles = $origin_roles;

	}

	/**
	 * Test that cs_install_roles_on_network() creates the roles when $wp_roles->roles is initially false.
	 *
	 * @since 2.6.3
	 */
	public function test_cs_install_roles_on_network_when_roles_false() {

		global $wp_roles;

		$origin_roles = $wp_roles->roles;

		// Prepare variables for test
		$wp_roles->roles = false;

		cs_install_roles_on_network();

		// Test that the roles are created
		$this->assertInstanceOf( 'WP_Role', get_role( 'shop_manager' ) );
		$this->assertInstanceOf( 'WP_Role', get_role( 'shop_accountant' ) );
		$this->assertInstanceOf( 'WP_Role', get_role( 'shop_worker' ) );
		$this->assertInstanceOf( 'WP_Role', get_role( 'shop_vendor' ) );


		// Reset to origin
		$wp_roles->roles = $origin_roles;

	}

}
