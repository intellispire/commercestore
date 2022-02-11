<?php

/**
 * @group cs_scripts
 */
class Tests_Scripts extends CS_UnitTestCase {

	/**
	 * Test if all the file hooks are working.
	 *
	 * @since 2.3.6
	 */
	public function test_file_hooks() {
		$this->assertNotFalse( has_action( 'init',                  'cs_register_scripts'       ) );
		$this->assertNotFalse( has_action( 'init',                  'cs_register_styles'        ) );
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts',    'cs_load_scripts'           ) );
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts',    'cs_enqueue_styles'         ) );
		$this->assertNotFalse( has_action( 'admin_init',            'cs_register_admin_scripts' ) );
		$this->assertNotFalse( has_action( 'admin_init',            'cs_register_admin_styles'  ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', 'cs_enqueue_admin_scripts'  ) );
		$this->assertNotFalse( has_action( 'admin_enqueue_scripts', 'cs_enqueue_admin_styles'   ) );
		$this->assertNotFalse( has_action( 'admin_head',            'cs_admin_downloads_icon'   ) );
	}

	/**
	 * Test that all the scripts are loaded at the checkout page.
	 *
	 * @since 2.3.6
	 */
	public function test_load_scripts_checkout() {
		global $cs_options;

		// Prepare test
		$this->go_to( get_permalink( $cs_options['purchase_page'] ) );
		cs_load_scripts();

		$this->assertTrue( wp_script_is( 'creditCardValidator', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'cs-checkout-global', 'enqueued' ) );
		$this->assertTrue( wp_script_is( 'cs-ajax', 'enqueued' ) );

		$this->go_to( '/' );
	}

	/**
	 * Test that the cs_register_styles() function will bail when the 'disable_styles'
	 * option is set to true.
	 *
	 * @since 2.3.6
	 */
	public function test_register_styles_bail_option() {

		// Prepare test
		$origin_disable_styles = cs_get_option( 'disable_styles', false );
		cs_update_option( 'disable_styles', true );

		// Assert
		$this->assertNull( cs_register_styles() );

		// Reset to origin
		cs_update_option( 'disable_styles', $origin_disable_styles );
	}

	/**
	 * Test that the cs_register_styles() function will enqueue the styles.
	 *
	 * @since 2.3.6
	 */
	public function test_register_styles() {

		cs_update_option( 'disable_styles', false );
		cs_register_styles();

		$this->assertTrue( wp_style_is( 'cs-styles', 'registered' ) );
	}

	/**
	 * Test that the test_enqueue_styles() function will enqueue the styles.
	 *
	 * @since 2.3.6
	 */
	public function test_enqueue_styles() {

		cs_update_option( 'disable_styles', false );
		cs_enqueue_styles();

		$this->assertTrue( wp_style_is( 'cs-styles', 'enqueued' ) );
	}

	/**
	 * Test that the cs_register_styles() function will enqueue the proper styles
	 * when page is checkout + ssl.
	 *
	 * @since 2.3.6
	 */
	public function test_register_styles_checkout_ssl() {

		// Prepare test
		$_SERVER['HTTPS'] = 'ON'; // Fake SSL
		$this->go_to( get_permalink( cs_get_option( 'purchase_page' ) ) );
		cs_update_option( 'disable_styles', false );
		cs_register_styles();

		$this->go_to( '/' );

		$this->assertTrue( wp_style_is( 'cs-styles', 'registered' ) );

		unset( $_SERVER['HTTPS'] );
	}

	/**
	 * Test that the cs_load_admin_scripts() function will bail when not a CommerceStore admin page.
	 *
	 * @since 2.3.6
	 */
	public function test_load_admin_scripts_bail() {

		// Prepare test
		global $pagenow;
		$origin_pagenow = $pagenow;
		$pagenow = 'dashboard';

		if ( ! function_exists( 'cs_is_admin_page' ) ) {
			include CS_PLUGIN_DIR . 'includes/admin/admin-pages.php';
		}

		// Assert
		$this->assertNull( cs_load_admin_scripts( 'dashboard' ) );

		// Reset to origin
		$pagenow = $origin_pagenow;
	}

	/**
	 * @dataProvider _admin_scripts_dp
	 * @covers ::cs_load_admin_scripts()
	 *
	 * @param string $script    Registered script handle.
	 * @param string $script_is Status of the script to check.
	 */
	public function test_load_admin_scripts_should_enqueue_expected_scripts( $script, $script_is ) {
		$this->load_admin_scripts();

		$this->assertTrue( wp_script_is( $script, $script_is ) );
	}

	/**
	 * Data provider for test_load_admin_scripts_should_enqueue_expected_scripts().
	 */
	public function _admin_scripts_dp() {
		return array(
			array( 'jquery-chosen',        'enqueued' ),
			array( 'cs-admin-scripts',    'enqueued' ),
			array( 'jquery-ui-datepicker', 'enqueued' ),
			array( 'jquery-ui-dialog',     'enqueued' ),
			array( 'media-upload',         'enqueued' ),
			array( 'thickbox',             'enqueued' ),
		);
	}

	/**
	 * @dataProvider _admin_styles_dp
	 * @covers ::cs_load_admin_scripts()
	 *
	 * @param string $style    Registered stylesheet handle.
	 * @param string $style_is Status of the stylesheet to check.
	 */
	public function test_load_admin_scripts_should_enqueue_expected_stylesheets( $style, $style_is ) {
		$this->load_admin_scripts();

		$this->assertTrue( wp_style_is( $style, $style_is ) );
	}

	/**
	 * Data provider for test_load_admin_scripts_should_enqueue_expected_stylesheets().
	 */
	public function _admin_styles_dp() {
		return array(
			array( 'jquery-chosen',   'enqueued' ),
			array( 'wp-color-picker', 'enqueued' ),
			array( 'thickbox',        'enqueued' ),
			array( 'cs-admin',       'enqueued' ),
		);
	}

	/**
	 * Helper to load admin scripts.
	 */
	protected function load_admin_scripts() {
		if ( ! function_exists( 'cs_is_admin_page' ) ) {
			include CS_PLUGIN_DIR . 'includes/admin/admin-pages.php';
		}

		cs_load_admin_scripts( 'settings.php' );
	}

}
