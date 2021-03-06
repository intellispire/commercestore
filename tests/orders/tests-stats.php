<?php
namespace CS;

/**
 * Stats Tests.
 *
 * @group cs_orders
 * @group cs_stats
 *
 * @coversDefaultClass \CS\Stats
 */
class Stats_Tests extends \CS_UnitTestCase {

	/**
	 * Stats class fixture.
	 *
	 * @var Stats
	 */
	protected static $stats;

	/**
	 * Orders test fixture.
	 *
	 * @var int[]
	 */
	protected static $orders;

	/**
	 * Refunds test fixture.
	 *
	 * @var array
	 */
	protected static $refunds;

	/**
	 * Set up fixtures once.
	 */
	public static function wpsetUpBeforeClass() : void  {
		self::$stats  = new Stats();
		self::$orders = parent::cs()->order->create_many( 5 );

		// Refund two of those orders.
		for ( $i = 0; $i < 2; $i++ ) {
			self::$refunds[] = cs_refund_order( self::$orders[ $i ] );
		}
	}

	/**
	 * @covers ::get_order_earnings
	 */
	public function test_get_order_earnings() {
		$earnings = self::$stats->get_order_earnings();

		$this->assertSame( 600.0, $earnings );
	}

	/**
	 * @covers ::get_order_earnings
	 */
	public function test_get_order_earnings_with_range_last_year_should_be_0() {
		$earnings = self::$stats->get_order_earnings( array(
			'range' => 'last_year',
		) );

		$this->assertSame( 0.00, $earnings );
	}

	/**
	 * @covers ::get_order_earnings
	 */
	public function test_get_order_earnings_with_range_last_30_days_should_be_600() {
		$earnings = self::$stats->get_order_earnings( array(
			'range' => 'last_30_days',
		) );

		$this->assertSame( 600.00, $earnings );
	}

	/**
	 * @covers ::get_order_earnings
	 */
	public function test_get_order_earnings_with_range_this_year_should_be_600() {
		$earnings = self::$stats->get_order_earnings( array(
			'range' => 'this_year',
		) );

		$this->assertSame( 600.00, $earnings );
	}

	/**
	 * @covers ::get_order_count
	 */
	public function test_get_order_count() {
		$count = self::$stats->get_order_count();

		$this->assertSame( 5, $count );
	}

	/**
	 * @covers ::get_order_count
	 */
	public function test_get_order_count_with_range_last_year_should_be_0() {
		$count = self::$stats->get_order_count( array(
			'range' => 'last_year',
		) );

		$this->assertSame( 0, $count );
	}

	/**
	 * @covers ::get_order_count
	 */
	public function test_get_order_count_with_range_last_30_days_should_be_5() {
		$count = self::$stats->get_order_count( array(
			'range' => 'last_30_days',
		) );

		$this->assertSame( 5, $count );
	}

	/**
	 * @covers ::get_order_count
	 */
	public function test_get_order_count_with_range_this_year_should_be_5() {
		$count = self::$stats->get_order_count( array(
			'range' => 'this_year',
		) );

		$this->assertSame( 5, $count );
	}

	/**
	 * @covers ::get_order_refund_count
	 */
	public function test_get_order_refund_count_with_range_last_year_should_be_0() {
		$count = self::$stats->get_order_refund_count( array(
			'range' => 'last_year',
		) );

		$this->assertSame( 0, $count );
	}

	/**
	 * @covers ::get_order_refund_count
	 */
	public function test_get_order_refund_count_with_range_this_year_should_be_2() {
		$count = self::$stats->get_order_refund_count( array(
			'range' => 'this_year',
		) );

		$this->assertSame( 2, $count );
	}

	/**
	 * @covers ::get_order_item_refund_count
	 */
	public function test_get_order_item_refund_count_with_range_last_year_should_be_0() {
		$count = self::$stats->get_order_item_refund_count( array(
			'range' => 'last_year',
		) );

		$this->assertSame( 0, $count );
	}

	/**
	 * @covers ::get_order_item_refund_count
	 */
	public function test_get_order_item_refund_count_with_range_this_year_should_be_2() {
		$count = self::$stats->get_order_item_refund_count( array(
			'range' => 'this_year',
		) );

		$this->assertSame( 2, $count );
	}

	/**
	 * @covers ::get_order_refund_amount
	 */
	public function test_get_order_refund_amount_with_range_last_year_should_be_0() {
		$earnings = self::$stats->get_order_refund_amount( array(
			'range' => 'last_year',
		) );

		$this->assertSame( 0.00, $earnings );
	}

	/**
	 * @covers ::get_order_refund_amount
	 */
	public function test_get_order_refund_amount_with_range_this_year_should_be_240() {
		$earnings = self::$stats->get_order_refund_amount( array(
			'range' => 'this_year',
		) );

		$this->assertSame( 240.00, $earnings );
	}

	/**
	 * @covers ::get_refund_rate
	 */
	public function test_get_get_refund_rate_with_range_last_year_should_be_0() {
		$refund_rate = self::$stats->get_refund_rate( array(
			'range'  => 'last_year',
			'output' => 'raw',
			'status' => array( 'complete', 'revoked', 'refunded', 'partially_refunded' ),
		) );

		$this->assertSame( 0, $refund_rate );
	}

	/**
	 * @covers ::get_refund_rate
	 */
	public function test_get_get_refund_rate_with_range_this_year_should_be_40() {
		$refund_rate = self::$stats->get_refund_rate( array(
			'range'  => 'this_year',
			'output' => 'raw',
			'status' => array( 'complete', 'revoked', 'refunded', 'partially_refunded' ),
		) );

		$this->assertSame( 40.0, $refund_rate );
	}
}
