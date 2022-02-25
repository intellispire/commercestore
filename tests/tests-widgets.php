<?php


/**
 * @group cs_widgets
 */
class Tests_Widgets extends CS_UnitTestCase {

	/**
	 * Test that the hooks in the file are good.
	 *
	 * @since 2.4.3
	 */
	public function test_file_hooks() {
		$this->assertNotFalse( has_action( 'widgets_init', 'cs_register_widgets' ) );
	}

	/**
	 * Test that the widgets are registered properly.
	 *
	 * @since 2.4.3
	 */
	public function test_register_widget() {

		cs_register_widgets();

		$widgets = array_keys( $GLOBALS['wp_widget_factory']->widgets );
		$this->assertStringContainsString( 'cs_cart_widget', $widgets );
		$this->assertStringContainsString( 'cs_categories_tags_widget', $widgets );
		$this->assertStringContainsString( 'cs_product_details_widget', $widgets );

	}

	/**
	 * Test that the cart widget exists with the right properties.
	 *
	 * @since 2.4.3
	 */
	public function test_cart_widget() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$cart_widget = $widgets['cs_cart_widget'];

		$this->assertInstanceOf( 'cs_cart_widget', $cart_widget );
		$this->assertEquals( 'cs_cart_widget', $cart_widget->id_base );
		$this->assertEquals( 'Downloads Cart', $cart_widget->name );

	}

	/**
	 * Test that the widget() method outputs HTML.
	 *
	 * @since 2.4.3
	 */
	public function test_cart_widget_function_bail_checkout() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$cart_widget = $widgets['cs_cart_widget'];

		$this->go_to( get_permalink( cs_get_option( 'purchase_page' ) ) );

		ob_start();
			$cart_widget->widget( array(
				'before_title'  => '',
				'after_title'   => '',
				'before_widget' => '',
				'after_widget'  => '',
			), array(
				'title'            => 'Cart',
				'hide_on_checkout' => true,
				'hide_on_empty'    => false,
			) );
		$output = ob_get_clean();

		$this->assertEmpty( $output );

	}

	/**
	 * Test that the widget() method outputs HTML.
	 *
	 * @since 2.4.3
	 */
	public function test_cart_widget_function() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$cart_widget = $widgets['cs_cart_widget'];

		ob_start();
			$cart_widget->widget( array(
				'before_title'  => '',
				'after_title'   => '',
				'before_widget' => '',
				'after_widget'  => '',
			), array(
				'title'            => 'Cart',
				'hide_on_checkout' => true,
				'hide_on_empty'    => false,
			) );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Number of items in cart:', $output );
		$this->assertStringContainsString( '<li class="cart_item empty">', $output );
		$this->assertStringContainsString( '<li class="cart_item cs-cart-meta cs_total"', $output );
		$this->assertStringContainsString( '<li class="cart_item cs_checkout"', $output );

	}

	public function test_cart_widget_function_hide_on_empty() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$cart_widget = $widgets['cs_cart_widget'];

		ob_start();
			$cart_widget->widget( array(
				'before_title'  => '',
				'after_title'   => '',
				'before_widget' => '',
				'after_widget'  => '',
			), array(
				'title'            => 'Cart',
				'hide_on_checkout' => true,
				'hide_on_empty'    => true,
			) );
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Number of items in cart:', $output );
		$this->assertStringContainsString( '<li class="cart_item empty">', $output );
		$this->assertStringContainsString( '<li class="cart_item cs-cart-meta cs_total"', $output );
		$this->assertStringContainsString( '<li class="cart_item cs_checkout"', $output );

	}

	/**
	 * Test that the cart widget update method returns the correct values.
	 *
	 * @since 2.4.3
	 */
	public function test_cart_widget_update() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$cart_widget = $widgets['cs_cart_widget'];

		$new_instance = array( 'title' => 'Your Cart', 'hide_on_checkout' => true, 'hide_on_empty' => true );
		$old_instance = array( 'title' => 'Cart', 'hide_on_checkout' => false, 'hide_on_empty' => false );
		$updated      = $cart_widget->update( $new_instance, $old_instance );

		$this->assertEquals( $updated, array( 'title' => 'Your Cart', 'hide_on_checkout' => true, 'hide_on_empty' => true ) );

	}

	/**
	 * Test that the cart widget form method outputs HTML.
	 *
	 * @since 2.4.3
	 */
	public function test_cart_widget_form() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$cart_widget = $widgets['cs_cart_widget'];

		ob_start();
			$cart_widget->form( array() );
		$output = ob_get_clean();

		$this->assertMatchesRegularExpression( '/<label for="(.*)">(.*)<\/label>/', $output );
		$this->assertMatchesRegularExpression( '/<input class="widefat" id="(.*)" name="(.*)" type="text" value="(.*)"\/>/', $output );
		$this->assertMatchesRegularExpression( '/<input (.*) id="(.*)" name="(.*)" type="checkbox" \/>/', $output );
		$this->assertMatchesRegularExpression( '/<label for="(.*)">(.*)<\/label>/', $output );

	}

	/** Categories tags widget */

	/**
	 * Test that the categories_widget widget exists with the right properties.
	 *
	 * @since 2.4.3
	 */
	public function test_categories_tags_widget() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$categories_widget = $widgets['cs_categories_tags_widget'];

		$this->assertInstanceOf( 'cs_categories_tags_widget', $categories_widget );
		$this->assertEquals( 'cs_categories_tags_widget', $categories_widget->id_base );
		$this->assertEquals( 'Downloads Categories / Tags', $categories_widget->name );

	}

	/**
	 * Test that the widget() method outputs HTML.
	 *
	 * @since 2.4.3
	 */
	public function test_categories_tags_widget_function() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$categories_widget = $widgets['cs_categories_tags_widget'];
		$download = CS_Helper_Download::create_simple_download();
		$terms = wp_set_object_terms( $download->ID, array( 'test1', 'test2' ), 'download_category', false );

		$this->go_to( $download->ID );


		ob_start();
			$categories_widget->widget( array(
				'before_title'  => '',
				'after_title'   => '',
				'before_widget' => '',
				'after_widget'  => '',
			), array(
				'title'      => 'Cart',
				'taxonomy'   => 'download_category',
				'count'      => true,
				'hide_empty' => true,
			) );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<ul class="cs-taxonomy-widget">', $output );
		$this->assertStringContainsString( '<li class="cat-item cat-item-' . reset( $terms ), $output );
		$this->assertStringContainsString( '<li class="cat-item cat-item-' . end( $terms ), $output );

		CS_Helper_Download::delete_download( $download->ID );

	}

	/**
	 * Test that the categories widget update method returns the correct values.
	 *
	 * @since 2.4.3
	 */
	public function test_categories_tags_widget_update() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$categories_widget = $widgets['cs_categories_tags_widget'];

		$updated = $categories_widget->update(
			array( 'title' => 'Categories', 'taxonomy' => 'download_category', 'count' => true, 'hide_empty' => true ),
			array( 'title' => 'Tags', 'taxonomy' => 'download_tag', 'count' => true, 'hide_empty' => true )
		);

		$this->assertEquals( $updated, array( 'title' => 'Categories', 'taxonomy' => 'download_category', 'count' => true, 'hide_empty' => true ) );

	}

	/**
	 * Test that the cart widget form method outputs HTML.
	 *
	 * @since 2.4.3
	 */
	public function test_categories_tags_widget_form() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$categories_widget = $widgets['cs_categories_tags_widget'];

		ob_start();
			$categories_widget->form( array() );
		$output = ob_get_clean();

		$this->assertMatchesRegularExpression( '/<label for="(.*)">Title:<\/label>/', $output );
		$this->assertMatchesRegularExpression( '/<input class="widefat" id="(.*)" name="(.*)" type="text" value="(.*)"\/>/', $output );
		$this->assertMatchesRegularExpression( '/<label for="(.*)">Taxonomy:<\/label>/', $output );
		$this->assertMatchesRegularExpression( '/<option value="download_category" (.*)>(.*)<\/option>/', $output );
		$this->assertMatchesRegularExpression( '/<option value="download_tag" (.*)>(.*)<\/option>/', $output );
		$this->assertMatchesRegularExpression( '/<label for="(.*)">Show Count:<\/label>/', $output );
		$this->assertMatchesRegularExpression( '/<label for="(.*)">Hide Empty Categories:<\/label>/', $output );

	}

	/** Product details widget */

	/**
	 * Test that the cs_product_details widget exists with the right properties.
	 *
	 * @since 2.4.3
	 */
	public function test_cs_product_details_widget() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$categories_widget = $widgets['cs_product_details_widget'];

		$this->assertInstanceOf( 'CS_Product_Details_Widget', $categories_widget );
		$this->assertEquals( 'cs_product_details', $categories_widget->id_base );
		$this->assertEquals( 'Product Details', $categories_widget->name );

	}

	/**
	 * Test that the widget() method returns when the visiting page is invalid.
	 *
	 * @since 2.4.3
	 */
	public function test_cs_product_details_widget_function_bail_no_download() {

		$this->go_to( '/' );
		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$details_widget = $widgets['cs_product_details_widget'];

		$this->assertNull( $details_widget->widget( array(), array( 'download_id' => 'current' ) ) );

	}

	/**
	 * Test that the widget() method uses the current post when 'download_id' is set to 'current'.
	 *
	 * @since 2.4.3
	 */
	public function test_cs_product_details_widget_function_bail_download() {

		$download = CS_Helper_Download::create_simple_download();
		$this->go_to( get_permalink( $download->ID ) );
		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$details_widget = $widgets['cs_product_details_widget'];

		ob_start();
			$details_widget->widget( array(
				'before_title'  => '',
				'after_title'   => '',
				'before_widget' => '',
				'after_widget'  => '',
			), array(
				'title'           => 'Cart',
				'download_id'     => 'current',
				'download_title'  => 'download_category',
				'purchase_button' => true,
				'categories'      => true,
				'tags'            => true,
			) );
		$output = ob_get_clean();

		$this->assertNotEmpty( $output );

		CS_Helper_Download::delete_download( $download->ID );

	}

	/**
	 * Test that the widget() method outputs HTML.
	 *
	 * @since 2.4.3
	 */
	public function test_cs_product_details_widget_function() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$details_widget = $widgets['cs_product_details_widget'];
		$download = CS_Helper_Download::create_simple_download();
		$terms = wp_set_object_terms( $download->ID, array( 'test1' ), 'download_category', false );

		$this->go_to( $download->ID );

		ob_start();
			$details_widget->widget( array(
				'before_title'  => '',
				'after_title'   => '',
				'before_widget' => '',
				'after_widget'  => '',
			), array(
				'title'           => 'Cart',
				'download_id'     => $download->ID,
				'download_title'  => 'download_category',
				'purchase_button' => true,
				'categories'      => true,
				'tags'            => true,
			) );
		$output = ob_get_clean();

		$this->assertStringContainsString( '<h3>' . $download->post_title . '</h3>', $output );
		$this->assertMatchesRegularExpression( '/<form id="cs_purchase_[0-9]+" class="cs_download_purchase_form cs_purchase_[0-9]+" method="post">/', $output );
		$this->assertStringContainsString( '<input type="hidden" name="cs_action" class="cs_action_input" value="add_to_cart">', $output );
		$this->assertStringContainsString( '<input type="hidden" name="download_id" value="' . $download->ID . '">', $output );
		$this->assertStringContainsString( '<p class="cs-meta">', $output );
		$this->assertStringContainsString( '<span class="categories">Product Category: ', $output );

		CS_Helper_Download::delete_download( $download->ID );

	}

	/**
	 * Test that the cart widget form method outputs HTML.
	 *
	 * @since 2.4.3
	 */
	public function test_cs_product_details_widget_form() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$categories_widget = $widgets['cs_product_details_widget'];

		ob_start();
			$categories_widget->form( array() );
		$output = ob_get_clean();

		$this->assertMatchesRegularExpression( '/<label for="widget-cs_product_details--title">Title:<\/label>/', $output );
		$this->assertMatchesRegularExpression( '/<input class="widefat" id="widget-cs_product_details--title" name="widget-cs_product_details\[\]\[title\]" type="text" value="(.*)" \/>/', $output );
		$this->assertMatchesRegularExpression( '/Display Type:/', $output );
		$this->assertMatchesRegularExpression( '/<label for="widget-cs_product_details--download_id">Product:<\/label>/', $output );
		$this->assertMatchesRegularExpression( '/<select class="widefat" name="widget-cs_product_details\[\]\[download_id\]" id="widget-cs_product_details--download_id">/', $output );
		$this->assertMatchesRegularExpression( '/<input  checked=\'checked\' id="widget-cs_product_details--download_title" name="widget-cs_product_details\[\]\[download_title\]" type="checkbox" \/>/', $output );
		$this->assertMatchesRegularExpression( '/<label for="widget-cs_product_details--download_title">Show Product Title<\/label>/', $output );
		$this->assertMatchesRegularExpression( '/<input  checked=\'checked\' id="widget-cs_product_details--purchase_button" name="widget-cs_product_details\[\]\[purchase_button\]" type="checkbox" \/>/', $output );
		$this->assertMatchesRegularExpression( '/<label for="widget-cs_product_details--purchase_button">Show Purchase Button<\/label>/', $output );
		$this->assertMatchesRegularExpression( '/<input  checked=\'checked\' id="widget-cs_product_details--categories" name="widget-cs_product_details\[\]\[categories\]" type="checkbox" \/>/', $output );
		$this->assertMatchesRegularExpression( '/<label for="widget-cs_product_details--categories">Show Product Categories<\/label>/', $output );
		$this->assertMatchesRegularExpression( '/<input  checked=\'checked\' id="widget-cs_product_details--tags" name="widget-cs_product_details\[\]\[tags\]" type="checkbox" \/>/', $output );
		$this->assertMatchesRegularExpression( '/<label for="widget-cs_product_details--tags">Show Product Tags<\/label>/', $output );

	}

	/**
	 * Test that the categories widget update method returns the correct values.
	 *
	 * @since 2.4.3
	 */
	public function test_cs_product_details_widget_update() {

		$widgets = $GLOBALS['wp_widget_factory']->widgets;
		$details_widget = $widgets['cs_product_details_widget'];

		$updated = $details_widget->update(
			array( 'title' => 'Details', 'download_id' => 123, 'display_type' => 'specific', 'download_title' => true, 'purchase_button' => true, 'categories' => true, 'tags' => true ),
			array( 'title' => 'OLD Details', 'display_type' => 'specific', 'download_id' => 123, 'download_title' => false, 'purchase_button' => false, 'categories' => false, 'tags' => false )
		);

		$this->assertEquals( $updated, array( 'title' => 'Details', 'display_type' => 'specific', 'download_id' => 123, 'download_title' => true, 'purchase_button' => true, 'categories' => true, 'tags' => true ) );

	}

}
