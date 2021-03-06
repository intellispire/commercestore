<?php

/**
 * @group query_filters
 */
class Tests_Query_Filters extends CS_UnitTestCase {

	/**
	 * Test that the actions exists for the cs_block_attachments() function.
	 *
	 * @since 2.2.4
	 */
	public function test_cs_block_attachments_filter() {

		$this->assertNotFalse( has_action( 'template_redirect', 'cs_block_attachments' ) );

	}

	/**
	 * Test that the function bails when not on a attachment page.
	 *
	 * @since 2.2.4
	 */
	public function test_cs_block_attachments_no_attachment_bail() {

		// Nothing to prepare, already not on a 'is_attachment' page

		$this->assertNull( cs_block_attachments() );

	}

	/**
	 * Test that the cs_block_attachments() function bails when the file has no parent.
	 *
	 * @since 2.2.4
	 */
	public function test_cs_block_attachments_no_parent_bail() {

		// Prepare test
		$filename 		= CS_PLUGIN_DIR . 'assets/images/loading.gif';
		$filetype 		= wp_check_filetype( basename( $filename ), null );
		$wp_upload_dir 	= wp_upload_dir();

		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, basename( $filename ), 0 );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		$this->go_to( get_permalink( $attach_id ) );

		$this->assertNull( cs_block_attachments() );

		// Reset to origin
		wp_delete_attachment( $attach_id, true );
		$this->go_to( '' );

	}

	/**
	 * Test that the cs_block_attachments() function bails when the parent is not a download.
	 *
	 * @since 2.2.4
	 */
	public function test_cs_block_attachments_no_download_bail() {

		// Prepare test
		$parent_post_id = $this->factory->post->create( array(
			'post_title'  => 'Hello World',
			'post_name'   => 'hello-world',
			'post_type'   => 'post',
			'post_status' => 'publish'
		) );

		$filename       = CS_PLUGIN_DIR . 'assets/images/loading.gif';
		$parent_post_id = $parent_post_id;
		$filetype       = wp_check_filetype( basename( $filename ), null );
		$wp_upload_dir  = wp_upload_dir();

		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, basename( $filename ), $parent_post_id );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		$this->go_to( get_permalink( $attach_id ) );

		$this->assertNull( cs_block_attachments() );

		// Reset to origin
		wp_delete_post( $parent_post_id, true );
		wp_delete_attachment( $attach_id, true );
		$this->go_to( '' );

	}

	/**
	 * Test that the cs_block_attachments() function will retrun when the content is not restricted.
	 *
	 * @since 2.2.4
	 */
	public function test_cs_block_attachments_not_restricted_bail() {

		// Prepare test
		$parent_post_id = $this->factory->post->create( array(
			'post_title'	=> 'Test Download Product',
			'post_name'		=> 'test-download-product',
			'post_type'		=> 'download',
			'post_status'	=> 'publish'
		) );

		$meta = array(
			'cs_price'                         => '0.00',
			'_variable_pricing'                 => 1,
			'_cs_price_options_mode'           => 'on',
			'cs_variable_prices'               => array(),
			'cs_download_files'                => array(),
			'_cs_download_limit'               => 20,
			'_cs_hide_purchase_link'           => 1,
			'cs_product_notes'                 => 'Purchase Notes',
			'_cs_product_type'                 => 'default',
			'_cs_download_earnings'            => 129.43,
			'_cs_download_sales'               => 59,
			'_cs_download_limit_override_1'    => 1,
			'cs_sku'                           => 'sku_0012'
		);
		foreach( $meta as $key => $value ) {
			update_post_meta( $parent_post_id, $key, $value );
		}

		$filename 			= CS_PLUGIN_DIR . 'assets/images/loading.gif';
		$parent_post_id 	= $parent_post_id;
		$filetype 			= wp_check_filetype( basename( $filename ), null );
		$wp_upload_dir 		= wp_upload_dir();

		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, $filename, $parent_post_id );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		$this->go_to( get_permalink( $attach_id ) );

		$this->assertNull( cs_block_attachments() );

		// Reset to origin
		wp_delete_post( $parent_post_id, true );
		wp_delete_attachment( $attach_id, true );
		$this->go_to( '' );

	}

	/**
	 * Test that the cs_block_attachments() function dies when the file is restricted.
	 *
	 * @since 2.2.4
	 */
	public function test_cs_block_attachments_die() {

		// Prepare test
		$parent_post_id = $this->factory->post->create( array(
			'post_title'  => 'Test Download Product',
			'post_name'   => 'test-download-product',
			'post_type'   => 'download',
			'post_status' => 'publish'
		) );

		$meta = array(
			'cs_price'                         => '0.00',
			'_variable_pricing'                 => 1,
			'_cs_price_options_mode'           => 'on',
			'cs_variable_prices'               => array(),
			'cs_download_files'                => array(),
			'_cs_download_limit'               => 20,
			'_cs_hide_purchase_link'           => 1,
			'cs_product_notes'                 => 'Purchase Notes',
			'_cs_product_type'                 => 'default',
			'_cs_download_earnings'            => 129.43,
			'_cs_download_sales'               => 59,
			'_cs_download_limit_override_1'    => 1,
			'cs_sku'                           => 'sku_0012'
		);
		foreach( $meta as $key => $value ) {
			update_post_meta( $parent_post_id, $key, $value );
		}

		$filename 			= CS_PLUGIN_DIR . 'assets/images/loading.gif';
		$parent_post_id 	= $parent_post_id;
		$filetype 			= wp_check_filetype( basename( $filename ), null );
		$wp_upload_dir 		= wp_upload_dir();

		$attachment = array(
			'guid'           => $wp_upload_dir['url'] . '/' . basename( $filename ),
			'post_mime_type' => $filetype['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', basename( $filename ) ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);
		$attach_id = wp_insert_attachment( $attachment, basename( $filename ), $parent_post_id );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
		wp_update_attachment_metadata( $attach_id, $attach_data );

		// Add attachment to the download product files
		update_post_meta( $parent_post_id, 'cs_download_files', array(
			array(
				'name'      => 'Restricted file',
				'file'      => wp_get_attachment_url( $attach_id ),
				'condition' => 0,
			) )
		);

		$this->go_to( get_permalink( $attach_id ) );

		add_filter( 'wp_die_handler', function() { return 'Tests_Query_Filters::some_useless_function'; } );
		ob_start();
			cs_block_attachments();
		$return = ob_get_clean();
		$this->assertEquals( 'wp_die', $return );

		// Reset to origin
		remove_all_filters( 'wp_die_handler' );
		add_filter( 'wp_die_handler', '_default_wp_die_handler' );
		wp_delete_post( $parent_post_id, true );
		wp_delete_attachment( $attach_id, true );
		$this->go_to( '' );

	}

	/**
	 * This method has been brought to live to catch the wp_die() function callback.
	 * This way it will allow us to test function that normally would die(), but now are returning 'wp_die'.
	 * When testing a function that calls wp_die(), one would normally get a 'E' error in PHPUnit.
	 *
	 * @since 2.2.4
	 */
	public static function some_useless_function( $message = '', $title = '', $args = array() ) {
		echo 'wp_die';
	}


}
