<?php


/**
 * @group cs_cpt
 */
class Tests_Post_Types extends CS_UnitTestCase {

	public function set_up() {
		parent::set_up();
	}

	public function tear_down() {
		parent::tear_down();
	}

	/**
	 * @covers ::cs_setup_cs_post_types
	 */
	public function test_downloads_post_type() {
		global $wp_post_types;
		$this->assertArrayHasKey( 'download', $wp_post_types );
	}

	public function test_downloads_post_type_labels() {
		global $wp_post_types;
		$this->assertEquals( 'Products', $wp_post_types['download']->labels->name );
		$this->assertEquals( 'Product', $wp_post_types['download']->labels->singular_name );
		$this->assertEquals( 'Add New', $wp_post_types['download']->labels->add_new );
		$this->assertEquals( 'Add New Product', $wp_post_types['download']->labels->add_new_item );
		$this->assertEquals( 'Edit Product', $wp_post_types['download']->labels->edit_item );
		$this->assertEquals( 'View Product', $wp_post_types['download']->labels->view_item );
		$this->assertEquals( 'Search Products', $wp_post_types['download']->labels->search_items );
		$this->assertEquals( 'No Products found', $wp_post_types['download']->labels->not_found );
		$this->assertEquals( 'No Products found in Trash', $wp_post_types['download']->labels->not_found_in_trash );
		$this->assertEquals( 'Products', $wp_post_types['download']->labels->all_items );
		$this->assertEquals( 'Products', $wp_post_types['download']->labels->menu_name );
		$this->assertEquals( 'Product', $wp_post_types['download']->labels->name_admin_bar );
		$this->assertEquals( 1, $wp_post_types['download']->publicly_queryable );
		$this->assertEquals( 'product', $wp_post_types['download']->capability_type );
		$this->assertEquals( 1, $wp_post_types['download']->map_meta_cap );
		$this->assertEquals( 'products', $wp_post_types['download']->rewrite['slug'] );
		$this->assertEquals( 1, $wp_post_types['download']->has_archive );
		$this->assertEquals( 'download', $wp_post_types['download']->query_var );
		$this->assertEquals( 'Products', $wp_post_types['download']->label );
	}

	public function test_payment_post_type() {
		global $wp_post_types;
		$this->assertArrayHasKey( 'cs_payment', $wp_post_types );
	}

	public function test_payment_post_type_labels() {
		global $wp_post_types;
		$this->assertEquals( 'Payments', $wp_post_types['cs_payment']->labels->name );
		$this->assertEquals( 'Payment', $wp_post_types['cs_payment']->labels->singular_name );
		$this->assertEquals( 'Add New', $wp_post_types['cs_payment']->labels->add_new );
		$this->assertEquals( 'Add New Payment', $wp_post_types['cs_payment']->labels->add_new_item );
		$this->assertEquals( 'Edit Payment', $wp_post_types['cs_payment']->labels->edit_item );
		$this->assertEquals( 'View Payment', $wp_post_types['cs_payment']->labels->view_item );
		$this->assertEquals( 'Search Payments', $wp_post_types['cs_payment']->labels->search_items );
		$this->assertEquals( 'No Payments found', $wp_post_types['cs_payment']->labels->not_found );
		$this->assertEquals( 'No Payments found in Trash', $wp_post_types['cs_payment']->labels->not_found_in_trash );
		$this->assertEquals( 'All Payments', $wp_post_types['cs_payment']->labels->all_items );
		$this->assertEquals( 'Payment History', $wp_post_types['cs_payment']->labels->menu_name );
		$this->assertEquals( 'Payment', $wp_post_types['cs_payment']->labels->name_admin_bar );
		$this->assertEquals( '', $wp_post_types['cs_payment']->publicly_queryable );
		$this->assertEquals( 'shop_payment', $wp_post_types['cs_payment']->capability_type );
		$this->assertEquals( 1, $wp_post_types['cs_payment']->exclude_from_search );
		$this->assertEquals( 1, $wp_post_types['cs_payment']->map_meta_cap );
		$this->assertEquals( 'Payments', $wp_post_types['cs_payment']->label );
	}

	public function test_discount_post_type() {
		global $wp_post_types;
		$this->assertArrayHasKey( 'cs_discount', $wp_post_types );
	}

	public function test_discount_post_type_labels() {
		global $wp_post_types;
		$this->assertEquals( 'Discounts', $wp_post_types['cs_discount']->labels->name );
		$this->assertEquals( 'Discount', $wp_post_types['cs_discount']->labels->singular_name );
		$this->assertEquals( 'Add New', $wp_post_types['cs_discount']->labels->add_new );
		$this->assertEquals( 'Add New Discount', $wp_post_types['cs_discount']->labels->add_new_item );
		$this->assertEquals( 'Edit Discount', $wp_post_types['cs_discount']->labels->edit_item );
		$this->assertEquals( 'View Discount', $wp_post_types['cs_discount']->labels->view_item );
		$this->assertEquals( 'Search Discounts', $wp_post_types['cs_discount']->labels->search_items );
		$this->assertEquals( 'No Discounts found', $wp_post_types['cs_discount']->labels->not_found );
		$this->assertEquals( 'No Discounts found in Trash', $wp_post_types['cs_discount']->labels->not_found_in_trash );
		$this->assertEquals( 'All Discounts', $wp_post_types['cs_discount']->labels->all_items );
		$this->assertEquals( 'Discounts', $wp_post_types['cs_discount']->labels->menu_name );
		$this->assertEquals( 'Discount', $wp_post_types['cs_discount']->labels->name_admin_bar );
		$this->assertEquals( '', $wp_post_types['cs_discount']->publicly_queryable );
		$this->assertEquals( 'shop_discount', $wp_post_types['cs_discount']->capability_type );
		$this->assertEquals( 1, $wp_post_types['cs_discount']->exclude_from_search );
		$this->assertEquals( 1, $wp_post_types['cs_discount']->map_meta_cap );
		$this->assertEquals( 'Discounts', $wp_post_types['cs_discount']->label );
	}

	public function test_register_post_statuses() {
		cs_register_post_type_statuses();

		global $wp_post_statuses;

		$this->assertInternalType( 'object', $wp_post_statuses['refunded'] );
		$this->assertInternalType( 'object', $wp_post_statuses['revoked'] );
		$this->assertInternalType( 'object', $wp_post_statuses['active'] );
		$this->assertInternalType( 'object', $wp_post_statuses['inactive'] );
	}
}
