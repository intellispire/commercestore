<?php


/**
 * @group cs_cpt
 */
class Tests_Post_Type_Labels extends CS_UnitTestCase {

	public function set_up() {
		parent::set_up();
	}

	public function tear_down() {
		parent::tear_down();
	}

	public function test_get_default_labels() {
		$out = cs_get_default_labels();
		$this->assertArrayHasKey( 'singular', $out );
		$this->assertArrayHasKey( 'plural', $out );

		$this->assertEquals( 'Product', $out['singular'] );
		$this->assertEquals( 'Products', $out['plural'] );
	}

	public function test_singular_label() {
		$this->assertEquals( 'Product', cs_get_label_singular() );
		$this->assertEquals( 'product', cs_get_label_singular( true ) );
	}

	public function test_plural_label() {
		$this->assertEquals( 'Products', cs_get_label_plural() );
		$this->assertEquals( 'products', cs_get_label_plural( true ) );
	}

	public function test_taxonomy_labels() {

		$category_labels = cs_get_taxonomy_labels();
		$this->assertInternalType( 'array', $category_labels );
		$this->assertArrayHasKey( 'name', $category_labels );
		$this->assertArrayHasKey( 'singular_name', $category_labels );
		$this->assertTrue( in_array( 'Product Category', $category_labels ) );
		$this->assertTrue( in_array( 'Product Categories', $category_labels ) );
		// Negative test for our change to exclude singular post type label in #3212
		$this->assertTrue( in_array( 'Categories', $category_labels ) );

		$this->assertInternalType( 'array', $category_labels );
		$this->assertArrayHasKey( 'name', $category_labels );
		$this->assertArrayHasKey( 'singular_name', $category_labels );
		$this->assertTrue( in_array( 'Product Category', $category_labels ) );
		$this->assertTrue( in_array( 'Product Categories', $category_labels ) );
		// Negative test for our change to exclude singular post type label in #3212
		$this->assertTrue( in_array( 'Categories', $category_labels ) );

		$tag_labels = cs_get_taxonomy_labels( CS_TAG_TYPE );
		$this->assertInternalType( 'array', $tag_labels );
		$this->assertArrayHasKey( 'name', $tag_labels );
		$this->assertArrayHasKey( 'singular_name', $tag_labels );
		$this->assertTrue( in_array( 'Product Tag', $tag_labels ) );
		$this->assertTrue( in_array( 'Product Tags', $tag_labels ) );
		// Negative test for our change to exclude singular post type label in #3212
		$this->assertTrue( in_array( 'Tags', $tag_labels ) );

	}
}
