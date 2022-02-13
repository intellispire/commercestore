<?php

/**
 * @group plugin_compatibility
 */
class Tests_Plugin_Compatibility extends CS_UnitTestCase {

	/**
	 * Test that the filter exists of the function.
	 *
	 * @since 2.3
	 */
	public function test_file_hooks() {

		$this->assertNotFalse( has_action( 'load-edit.php', 'cs_remove_post_types_order' ) );
		$this->assertNotFalse( has_action( 'template_redirect', 'cs_disable_jetpack_og_on_checkout' ) );
		$this->assertNotFalse( has_filter( 'cs_settings_misc', 'cs_append_no_cache_param' ) );
		$this->assertNotFalse( has_filter( 'cs_downloads_content', 'cs_qtranslate_content' ) );
		$this->assertNotFalse( has_filter( 'cs_downloads_excerpt', 'cs_qtranslate_content' ) );
		$this->assertNotFalse( has_action( 'template_redirect', 'cs_disable_woo_ssl_on_checkout' ) );
		$this->assertNotFalse( has_action( 'cs_email_send_before', 'cs_disable_mandrill_nl2br' ) );
		$this->assertNotFalse( has_action( 'template_redirect', 'cs_disable_404_redirected_redirect' ) );

	}

	/**
	 * Test that the 'CPTOrderPosts' filter is removed.
	 *
	 * @since 2.3
	 */
	public function test_remove_post_types_order() {

		cs_remove_post_types_order();
		$this->assertFalse( has_filter( 'posts_orderby', 'CPTOrderPosts' ) );

	}

	/**
	 * Test that the JetPack og tags are removed.
	 *
	 * @since 2.3
	 */
	public function test_disable_jetpack_og_on_checkout() {

		$this->go_to( get_permalink( cs_get_option( 'purchase_page' ) ) );
		cs_disable_jetpack_og_on_checkout();
		$this->assertFalse( has_action( 'wp_head', 'jetpack_og_tags' ) );

	}

	/**
	 * Test that the cs_is_caching_plugin_active() return false when no caching is installed.
	 *
	 * @since 2.3
	 */
	public function test_is_caching_plugin_active_false() {

		$this->assertFalse( cs_is_caching_plugin_active() );

	}

	/**
	 * Test that cs_is_chaching_plugin_active() return true when W3TC is active.
	 *
	 * @since 2.3
	 */
	public function test_is_caching_plugin_active_true() {

		define( 'W3TC', true );
		$this->assertTrue( cs_is_caching_plugin_active() );

	}

	/**
	 * Test that a extra setting is added when W3TC is activated.
	 *
	 * @since 2.3
	 */
	public function test_append_no_cache_param() {

		$settings = cs_append_no_cache_param( $settings = array() );

		$this->assertEquals( $settings, array( array(
			'id'   => 'no_cache_checkout',
			'name' => __('No Caching on Checkout?','commercestore' ),
			'desc' => __('Check this box in order to append a ?nocache parameter to the checkout URL to prevent caching plugins from caching the page.','commercestore' ),
			'type' => 'checkbox'
		) ) );

	}

	/**
	 * Test the qTranslate function.
	 *
	 * @since 2.3
	 */
	public function test_qtranslate_content() {

		define( 'QT_LANGUAGE', true );
		$content = cs_qtranslate_content( $content = 'This is some test content' );
		$this->assertEquals( $content, 'This is some test content' );

	}

	/**
	 * Test that the Woo SSL action is removed from the template_redirect hook.
	 *
	 * @since 2.3
	 */
	public function test_disable_woo_ssl_on_checkout() {

		$this->go_to( get_permalink( cs_get_option( 'purchase_page' ) ) );
		add_filter( 'cs_is_ssl_enforced', '__return_true' );

		cs_disable_woo_ssl_on_checkout();
		$this->assertFalse( has_action( 'template_redirect', array( 'WC_HTTPS', 'unforce_https_template_redirect' ) ) );

	}

	/**
	 * Test the Mandrill compatibility function.
	 *
	 * @since 2.3
	 */
	public function test_disable_mandrill_nl2br() {

		cs_disable_mandrill_nl2br();
		$this->assertNotFalse( has_action( 'mandrill_nl2br', '__return_false' ) );

	}

	/**
	 * Test that the cs_disable_404_redirected_redirect() functions returns when WBZ404_VERSION is not defined.
	 *
	 * @since 2.3
	 */
	public function test_disable_404_redirected_redirect_return() {

		$this->assertNull( cs_disable_404_redirected_redirect() );

	}

	/**
	 * Test the cs_disable_404_redirected_redirect function.
	 *
	 * @since 2.3
	 */
	public function test_disable_404_redirected_redirect() {

		$this->go_to( get_permalink( cs_get_option( 'success_page' ) ) );
		define( 'WBZ404_VERSION', '1.3.2' );
		cs_disable_404_redirected_redirect();

		$this->assertFalse( has_action( 'template_redirect', 'wbz404_process404' ) );

	}

	public function test_say_what_aliases() {

		global $wp_filter;
		$this->assertarrayHasKey( 'cs_say_what_domain_aliases', $wp_filter['say_what_domain_aliases'][10] );

		$say_what_aliases = apply_filters( 'say_what_domain_aliases', array() );
		$this->assertarrayHasKey( 'commercestore', $say_what_aliases );
		$this->assertTrue( in_array( 'cs', $say_what_aliases['commercestore'] ) );

	}


}

/**
 * Function required to test the qTranslate compatibility function.
 *
 * @since 2.3
 */
if ( ! function_exists( 'qtrans_useCurrentLanguageIfNotFoundShowAvailable' ) ) {
	function qtrans_useCurrentLanguageIfNotFoundShowAvailable( $content ) {
		return $content;
	}
}
