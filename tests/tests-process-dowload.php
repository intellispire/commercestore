<?php
/**
 * Download Process Tests
 *
 * Tests for file downloads and downloading permissions.
 *
 * @group cs_downloads
 */
class Tests_Process_Download extends CS_UnitTestCase {

	/**
	 * Order fixture
	 *
	 * @var CS\Orders\Order
	 */
	protected static $order;

	/**
	 * Sets up fixtures once
	 */
	public static function setUpBeforeClass() : void  {
		parent::setUpBeforeClass() ;

		$order_id = cs_add_order( array(
			'status'          => 'complete',
			'type'            => 'sale',
			'date_completed'  => CS()->utils->date( 'now' )->toDateTimeString(),
			'date_refundable' => CS()->utils->date( 'now' )->addDays( 30 )->toDateTimeString(),
			'ip'              => '10.1.1.1',
			'gateway'         => 'manual',
			'mode'            => 'live',
			'currency'        => 'USD',
			'payment_key'     => md5( 'cs' ),
			'subtotal'        => 20,
			'total'           => 20,
		) );

		cs_add_order_item( array(
			'order_id'     => $order_id,
			'product_id'   => 1,
			'product_name' => 'Simple Download',
			'price_id'     => 1,
			'status'       => 'complete',
			'amount'       => 10,
			'subtotal'     => 10,
			'total'        => 10,
			'quantity'     => 1,
		) );

		cs_add_order_item( array(
			'order_id'     => $order_id,
			'product_id'   => 2,
			'product_name' => 'Simple Download',
			'status'       => 'complete',
			'amount'       => 10,
			'subtotal'     => 10,
			'total'        => 10,
			'quantity'     => 1,
		) );

		self::$order = cs_get_order( $order_id );
	}

	/**
	 * @covers ::cs_set_requested_file_scheme
	 */
	public function test_set_scheme() {
		$home_url = get_home_url();
		$file       = trailingslashit( $home_url ) . 'test-file.jpg';
		$https_file = str_replace( 'http://', 'https://', $file );

		$this->assertEquals( $file, cs_set_requested_file_scheme( $https_file, array(), '' ) );
	}

	/**
	 * If specifying a product ID that doesn't exist in the order, files cannot be downloaded
	 *
	 * @covers ::cs_order_grants_access_to_download_files
	 */
	public function test_order_with_non_existent_product_id_should_return_false() {
		$this->assertFalse( cs_order_grants_access_to_download_files( array(
			'order_id'   => self::$order->id,
			'product_id' => 500
		) ) );
	}

	/**
	 * Fully `complete` order should be able to download files
	 *
	 * @covers ::cs_order_grants_access_to_download_files
	 */
	public function test_order_with_complete_item_should_return_true() {
		// Not specifying price ID
		$this->assertTrue( cs_order_grants_access_to_download_files( array(
			'order_id'   => self::$order->id,
			'product_id' => 1
		) ) );

		// Specifying price ID
		$this->assertTrue( cs_order_grants_access_to_download_files( array(
			'order_id'   => self::$order->id,
			'product_id' => 1,
			'price_id'   => 1
		) ) );
	}

	/**
	 * If specifying a price ID that was not purchased in this order, files cannot be downloaded
	 *
	 * @covers ::cs_order_grants_access_to_download_files
	 */
	public function test_order_with_wrong_price_id_should_return_false() {
		$this->assertFalse( cs_order_grants_access_to_download_files( array(
			'order_id'   => self::$order->id,
			'product_id' => 1,
			'price_id'   => 2
		) ) );
	}

	/**
	 * If an order item has been refunded, the associated files can no longer be downloaded.
	 *
	 * @covers ::cs_order_grants_access_to_download_files
	 */
	public function test_refunded_item_in_partially_refunded_order_should_return_false() {
		// Refund the order item for product ID 2.
		foreach ( self::$order->get_items() as $item ) {
			if ( 2 == $item->product_id ) {
				cs_refund_order( $item->order_id, array(
					array(
						'order_item_id' => $item->id,
						'subtotal'      => $item->subtotal,
						'tax'           => $item->tax
					)
				) );
			}
		}

		// Fetch original order.
		$order = cs_get_order( self::$order->id );

		$this->assertSame( 'partially_refunded', $order->status );

		$this->assertFalse( cs_order_grants_access_to_download_files( array(
			'order_id'   => self::$order->id,
			'product_id' => 2
		) ) );
	}

	/**
	 * If an order has been partially refunded, the item that's still `complete` can still be downloaded.
	 *
	 * @covers ::cs_order_grants_access_to_download_files
	 */
	public function test_complete_item_in_partially_refunded_order_should_return_true() {
		$this->assertTrue( cs_order_grants_access_to_download_files( array(
			'order_id'   => self::$order->id,
			'product_id' => 1
		) ) );
	}

	/**
	 * If an order has been fully refunded, files cannot be downloaded for any of the items
	 *
	 * @covers ::cs_order_grants_access_to_download_files
	 */
	public function test_fully_refunded_order_should_return_false() {
		// Refund all items.
		cs_refund_order( self::$order->id );

		// Fetch original order.
		$order = cs_get_order( self::$order->id );

		$this->assertSame( 'refunded', $order->status );

		$this->assertFalse( cs_order_grants_access_to_download_files( array(
			'order_id'   => self::$order->id,
			'product_id' => 1
		) ) );

		$this->assertFalse( cs_order_grants_access_to_download_files( array(
			'order_id'   => self::$order->id,
			'product_id' => 2
		) ) );
	}

	/**
	 * If no order exists with the provided ID, files cannot be downloaded
	 *
	 * @covers ::cs_order_grants_access_to_download_files
	 */
	public function test_non_existent_order_number_should_return_false() {
		// Generate a random order ID until we get one that doesn't exist.
		$order_id = 12345;
		do {
			$order_id++;
			$order = cs_get_order( $order_id );
		} while ( $order instanceof \CS\Orders\Order );

		$this->assertFalse( cs_order_grants_access_to_download_files( array(
			'order_id'   => $order_id,
			'product_id' => 1
		) ) );
	}

	public function test_file_download_token() {
		$csfile = '1:2:3:4';
		$ttl     = current_time( 'timestamp' ) + HOUR_IN_SECONDS;
		$file    = 4;

		$args = array(
			'csfile' => $csfile,
			'ttl'     => $ttl,
			'file'    => $file,
		);

		$token         = cs_get_download_token( add_query_arg( $args, site_url() ) );
		$args['token'] = $token;

		$url = add_query_arg( $args, site_url() );

		$this->assertTrue( cs_validate_url_token( $url ) );
	}

	public function test_file_download_token_out_of_order() {
		$csfile = '1:2:3:4';
		$ttl     = current_time( 'timestamp' ) + HOUR_IN_SECONDS;
		$file    = 4;

		$args = array(
			'csfile' => $csfile,
			'ttl'     => $ttl,
			'file'    => $file,
		);

		$token         = cs_get_download_token( add_query_arg( $args, site_url() ) );

		// Re-order the arguments to verify for #8851.
		$new_args = array(
			'file'    => $file,
			'ttl'     => $ttl,
			'token'   => $token,
			'csfile' => $csfile,
		);

		$url = add_query_arg( $new_args, site_url() );

		$this->assertTrue( cs_validate_url_token( $url ) );
	}

	public function test_custom_parameters() {

		$payment_id = CS_Helper_Payment::create_simple_payment();
		$payment    = cs_get_payment( $payment_id );
		$download   = CS_Helper_Download::create_simple_download();

		add_filter( 'cs_get_download_file_url_args', function ( $args, $payment_id, $params ) {
			$args['beta'] = 1;

			return $args;
		}, 10, 3 );

		add_filter( 'cs_url_token_allowed_params', function ( $args ) {
			$args[] = 'beta';

			return $args;
		} );

		$parts = parse_url( add_query_arg( array(), cs_get_download_file_url( $payment->key, $payment->email, '', $download->ID ) ) );
		wp_parse_str( $parts['query'], $query_args );
		$url = add_query_arg( $query_args, site_url() );

		$this->assertTrue( cs_validate_url_token( $url ) );
	}
}
