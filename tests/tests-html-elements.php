<?php

/**
 * CommerceStore HTML Elements Tests
 *
 * @group cs_html
 *
 * @coversDefaultClass CS_HTML_Elements
 */
class Test_HTML_Elements extends CS_UnitTestCase {

	/**
	 * @covers ::product_dropdown
	 */
	public function test_product_dropdown() {
		$expected = '<select name="products" id="products" class="cs-select " data-placeholder="Choose a Product" data-search-type="download" data-search-placeholder="Search Products">';
		$this->assertContains( $expected, CS()->html->product_dropdown() );
	}

	/**
	 * @covers ::cs_parse_product_dropdown_value
	 */
	public function test_product_dropdown_value_parse_should_be_123_1() {
		$expected = array(
			'download_id' => '123',
			'price_id'    => '1',
		);

		$this->assertEqualSetsWithIndex( $expected, cs_parse_product_dropdown_value( '123_1' ) );
	}

	/**
	 * @covers ::cs_parse_product_dropdown_value
	 */
	public function test_product_dropdown_value_parse_should_be_123() {
		$expected = array(
			'download_id' => '123',
			'price_id'    => false,
		);

		$this->assertEqualSetsWithIndex( $expected, cs_parse_product_dropdown_value( '123' ) );
		$this->assertEqualSetsWithIndex( $expected, cs_parse_product_dropdown_value( 123 ) );
	}

	/**
	 * @covers ::cs_parse_product_dropdown_value
	 */
	public function test_product_dropdown_array_parse() {
		$saved_values = array( 123, '155_1', '155_2', 99 );
		$expected     = array(
			array(
				'download_id' => '123',
				'price_id'    => false,
			),
			array(
				'download_id' => '155',
				'price_id'    => '1',
			),
			array(
				'download_id' => '155',
				'price_id'    => '2',
			),
			array(
				'download_id' => '99',
				'price_id'    => false,
			),
		);

		$this->assertEqualSetsWithIndex( $expected, cs_parse_product_dropdown_values( $saved_values ) );
	}

	/**
	 * @covers ::cs_parse_product_dropdown_value
	 */
	public function test_product_dropdown_string_parse() {
		$saved_values = '155';
		$expected     = array(
			array(
				'download_id' => '155',
				'price_id'    => false,
			),
		);

		$this->assertEqualSetsWithIndex( $expected, cs_parse_product_dropdown_values( $saved_values ) );

		$saved_values = '155_1';
		$expected     = array(
			array(
				'download_id' => '155',
				'price_id'    => '1',
			),
		);

		$this->assertEqualSetsWithIndex( $expected, cs_parse_product_dropdown_values( $saved_values ) );
	}

	/**
	 * @covers ::customer_dropdown
	 */
	public function test_customer_dropdown() {
		$expected = '<select name="customers" id="customers" class="cs-select  cs-customer-select cs-select-chosen" data-placeholder="Choose a Customer" data-search-type="customer" data-search-placeholder="Search Customers"><option value="0" selected=\'selected\'>No customers found</option></select>';

		$this->assertContains( $expected, CS()->html->customer_dropdown() );
	}

	/**
	 * @covers ::discount_dropdown
	 */
	public function test_discount_dropdown() {
		$meta = array(
			'name'              => '50 Percent Off',
			'type'              => 'percent',
			'amount'            => '50',
			'code'              => '50PERCENTOFF',
			'product_condition' => 'all',
		);

		cs_store_discount( $meta );

		$expected = '<select name="cs_discounts" id="discounts" class="cs-select  cs-user-select cs-select-chosen" data-placeholder="Choose a Discount"><option value="all" selected=\'selected\'>All Discounts</option><option value="' . cs_get_discount_id_by_code( '50PERCENTOFF' ) . '">50 Percent Off</option></select>';

		$this->assertSame( $expected, CS()->html->discount_dropdown() );
	}

	/**
	 * @covers ::category_dropdown
	 */
	public function test_category_dropdown() {
		$expected = '<select name="cs_categories" id="" class="cs-select " data-placeholder=""><option value="all" selected=\'selected\'>All Product Categories</option></select>';
		$this->assertEquals( $expected, CS()->html->category_dropdown() );
	}

	/**
	 * @covers ::year_dropdown
	 */
	public function test_year_dropdown() {
		$current_year = date( 'Y' );
		$expected     = '<select name="year" id="cs_year_select_year" class="cs-select " data-placeholder="">';
		$i            = 5;

		while ( $i >= 0 ) {
			$selected    = 0 === $i ? ' selected=\'selected\'' : '';
			$option_year = $current_year - $i;
			$expected   .= '<option value="' . $option_year . '"' . $selected . '>' . $option_year . '</option>';
			$i--;
		}

		$expected .= '</select>';

		$this->assertEquals( $expected, CS()->html->year_dropdown() );
	}

	/**
	 * @covers ::year_dropdown
	 */
	public function test_year_dropdown_variable() {
		$years_before = 5;
		$years_after  = 5;
		$current_year = date( 'Y' );

		$start_year = $current_year - $years_before;
		$end_year   = $current_year + $years_after;

		$expected = '<select name="year" id="cs_year_select_year" class="cs-select " data-placeholder="">';

		while ( $start_year <= $end_year ) {
			$selected = (int) $start_year === (int) $current_year
				? ' selected=\'selected\''
				: '';

			$expected .= '<option value="' . $start_year . '"' . $selected . '>' . $start_year . '</option>';
			$start_year++;
		}
		$expected .= '</select>';

		$this->assertEquals( $expected, CS()->html->year_dropdown( 'year', 0, $years_before, $years_after ) );

	}

	/**
	 * @covers ::month_dropdown
	 */
	public function test_month_dropdown() {
		$out = CS()->html->month_dropdown();

		$this->assertContains( '<select name="month" id="cs_month_select_month" class="cs-select "', $out );
		$this->assertContains( '<option value="1"', $out );
		$this->assertContains( '<option value="2"', $out );
		$this->assertContains( '<option value="3"', $out );
		$this->assertContains( '<option value="4"', $out );
		$this->assertContains( '<option value="5"', $out );
		$this->assertContains( '<option value="6"', $out );
		$this->assertContains( '<option value="7"', $out );
		$this->assertContains( '<option value="8"', $out );
		$this->assertContains( '<option value="9"', $out );
		$this->assertContains( '<option value="10"', $out );
		$this->assertContains( '<option value="11"', $out );
		$this->assertContains( '<option value="12"', $out );
	}

	/**
	 * @covers CS_HTML_Elements::select
	 */
	public function test_select_is_required() {
		$select = CS()->html->select(
			array(
				'required' => true,
				'options'  => array(
					1 => '1',
					2 => '2',
					3 => '3',
				),
			)
		);

		$this->assertContains( 'required', $select );
	}

	/**
	 * @covers CS_HTML_Elements::select
	 */
	public function test_select_is_not_required() {
		$select = CS()->html->select(
			array(
				'options' => array(
					1 => '1',
					2 => '2',
					3 => '3',
				)
			)
		);

		$this->assertNotContains( 'required', $select );
	}

	/**
	 * @covers CS_HTML_Elements::text
	 */
	public function test_text_is_required() {
		$this->assertContains( 'required', CS()->html->text( array( 'required' => true ) ) );
	}

	/**
	 * @covers CS_HTML_Elements::text
	 */
	public function test_text_is_not_required() {
		$this->assertNotContains( 'required', CS()->html->text() );
	}
}
