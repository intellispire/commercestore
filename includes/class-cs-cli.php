<?php
/**
 * CommerceStore WP-CLI
 *
 * This class provides an integration point with the WP-CLI plugin allowing
 * access to CommerceStore from the command line.
 *
 * @package    CS
 * @subpackage Classes/CLI
 * @copyright  Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license    http://opensource.org/license/gpl-2.0.php GNU Public License
 * @since      2.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

WP_CLI::add_command( 'cs', 'CS_CLI' );

/**
 * Work with CommerceStore through WP-CLI
 *
 * CS_CLI Class
 *
 * Adds CLI support to CommerceStore through WP-CLI
 *
 * @since 2.0
 */
class CS_CLI extends WP_CLI_Command {

	private $api;


	public function __construct() {
		$this->api = new CS_API();
	}


	/**
	 * Get CommerceStore details
	 *
	 * ## OPTIONS
	 *
	 * None. Returns basic info regarding your CommerceStore instance.
	 *
	 * ## EXAMPLES
	 *
	 * wp cs details
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function details( $args, $assoc_args ) {
		$symlink_file_downloads = cs_get_option( 'symlink_file_downloads', false );
		$purchase_page          = cs_get_option( 'purchase_page', '' );
		$success_page           = cs_get_option( 'success_page', '' );
		$failure_page           = cs_get_option( 'failure_page', '' );

		WP_CLI::line( sprintf( __( 'You are running CommerceStore version: %s', 'commercestore' ), CS_VERSION ) );
		WP_CLI::line( "\n" . sprintf( __( 'Test mode is: %s', 'commercestore' ), ( cs_is_test_mode() ? __( 'Enabled', 'commercestore' ) : __( 'Disabled', 'commercestore' ) ) ) );
		WP_CLI::line( sprintf( __( 'AJAX is: %s', 'commercestore' ), ( cs_is_ajax_enabled() ? __( 'Enabled', 'commercestore' ) : __( 'Disabled', 'commercestore' ) ) ) );
		WP_CLI::line( sprintf( __( 'Guest checkouts are: %s', 'commercestore' ), ( cs_no_guest_checkout() ? __( 'Disabled', 'commercestore' ) : __( 'Enabled', 'commercestore' ) ) ) );
		WP_CLI::line( sprintf( __( 'Symlinks are: %s', 'commercestore' ), ( apply_filters( 'cs_symlink_file_downloads', isset( $symlink_file_downloads ) ) && function_exists( 'symlink' ) ? __( 'Enabled', 'commercestore' ) : __( 'Disabled', 'commercestore' ) ) ) );
		WP_CLI::line( "\n" . sprintf( __( 'Checkout page is: %s', 'commercestore' ), ( ! cs_get_option( 'purchase_page', false ) ) ? __( 'Valid', 'commercestore' ) : __( 'Invalid', 'commercestore' ) ) );
		WP_CLI::line( sprintf( __( 'Checkout URL is: %s', 'commercestore' ), ( ! empty( $purchase_page ) ? get_permalink( $purchase_page ) : __( 'Undefined', 'commercestore' ) ) ) );
		WP_CLI::line( sprintf( __( 'Success URL is: %s', 'commercestore' ), ( ! empty( $success_page ) ? get_permalink( $success_page ) : __( 'Undefined', 'commercestore' ) ) ) );
		WP_CLI::line( sprintf( __( 'Failure URL is: %s', 'commercestore' ), ( ! empty( $failure_page ) ? get_permalink( $failure_page ) : __( 'Undefined', 'commercestore' ) ) ) );
		WP_CLI::line( sprintf( __( 'Downloads slug is: %s', 'commercestore' ), ( defined( 'CS_SLUG' ) ? '/' . CS_SLUG : '/' . CS_DEFAULT_SLUG ) ) );
		WP_CLI::line( "\n" . sprintf( __( 'Taxes are: %s', 'commercestore' ), ( cs_use_taxes() ? __( 'Enabled', 'commercestore' ) : __( 'Disabled', 'commercestore' ) ) ) );
		WP_CLI::line( sprintf( __( 'Tax rate is: %s', 'commercestore' ), cs_get_formatted_tax_rate() ) );

		$rates = cs_get_tax_rates();
		if ( ! empty( $rates ) ) {
			foreach ( $rates as $rate ) {
				WP_CLI::line( sprintf( __( 'Country: %s, State: %s, Rate: %s', 'commercestore' ), $rate['country'], $rate['state'], $rate['rate'] ) );
			}
		}
	}


	/**
	 * Get stats for your CommerceStore site
	 *
	 * ## OPTIONS
	 *
	 * --product=<product_id>: The ID of a specific product to retrieve stats for, or all
	 * --date=[range|this_month|last_month|today|yesterday|this_quarter|last_quarter|this_year|last_year]: A specific
	 * date range to retrieve stats for
	 * --startdate=<date>: The start date of a date range to retrieve stats for
	 * --enddate=<date>: The end date of a date range to retrieve stats for
	 *
	 * ## EXAMPLES
	 *
	 * wp cs stats --date=this_month
	 * wp cs stats --start-date=01/02/2014 --end-date=02/23/2014
	 * wp cs stats --date=last_year
	 * wp cs stats --date=last_year --product=15
	 */
	public function stats( $args, $assoc_args ) {

		$stats      = new CS_Payment_Stats();
		$date       = isset( $assoc_args ) && array_key_exists( 'date', $assoc_args ) ? $assoc_args['date'] : false;
		$start_date = isset( $assoc_args ) && array_key_exists( 'startdate', $assoc_args ) ? $assoc_args['startdate'] : false;
		$end_date   = isset( $assoc_args ) && array_key_exists( 'enddate', $assoc_args ) ? $assoc_args['enddate'] : false;
		$download   = isset( $assoc_args ) && array_key_exists( 'product', $assoc_args ) ? $assoc_args['product'] : 0;

		if ( ! empty( $date ) ) {
			$start_date = $date;
			$end_date   = false;
		} elseif ( empty( $date ) && empty( $start_date ) ) {
			$start_date = 'this_month';
			$end_date   = false;
		}

		$earnings = $stats->get_earnings( $download, $start_date, $end_date );
		$sales    = $stats->get_sales( $download, $start_date, $end_date );

		WP_CLI::line( sprintf( __( 'Earnings: %s', 'commercestore' ), $earnings ) );
		WP_CLI::line( sprintf( __( 'Sales: %s', 'commercestore' ), $sales ) );

	}


	/**
	 * Get the products currently posted on your CommerceStore site
	 *
	 * ## OPTIONS
	 *
	 * --id=<product_id>: A specific product ID to retrieve
	 *
	 *
	 * ## EXAMPLES
	 *
	 * wp cs products --id=103
	 */
	public function products( $args, $assoc_args ) {
		$product_id = isset( $assoc_args ) && array_key_exists( 'id', $assoc_args ) ? absint( $assoc_args['id'] ) : false;
		$products   = $this->api->get_products( $product_id );

		if ( isset( $products['error'] ) ) {
			WP_CLI::error( $products['error'] );
		}

		if ( empty( $products ) ) {
			WP_CLI::error( __( 'No Downloads found', 'commercestore' ) );

			return;
		}

		foreach ( $products['products'] as $product ) {
			$categories = '';
			$tags       = '';
			$pricing    = array();

			if ( is_array( $product['info']['category'] ) ) {
				$categories = array();
				foreach ( $product['info']['category'] as $category ) {
					$categories[] = $category->name;
				}

				$categories = implode( ', ', $categories );
			}

			if ( is_array( $product['info']['tags'] ) ) {
				$tags = array();
				foreach ( $product['info']['tags'] as $tag ) {
					$tags[] = $tag->name;
				}

				$tags = implode( ', ', $tags );
			}

			foreach ( $product['pricing'] as $price => $value ) {
				if ( 'amount' !== $price ) {
					$price = $price . ' - ';
				}

				$pricing[] = $price . ': ' . cs_format_amount( $value ) . ' ' . cs_get_currency();
			}

			$pricing = implode( ', ', $pricing );

			WP_CLI::line( WP_CLI::colorize( '%G' . $product['info']['title'] . '%N' ) );
			WP_CLI::line( sprintf( __( 'ID: %d', 'commercestore' ), $product['info']['id'] ) );
			WP_CLI::line( sprintf( __( 'Status: %s', 'commercestore' ), $product['info']['status'] ) );
			WP_CLI::line( sprintf( __( 'Posted: %s', 'commercestore' ), $product['info']['create_date'] ) );
			WP_CLI::line( sprintf( __( 'Categories: %s', 'commercestore' ), $categories ) );
			WP_CLI::line( sprintf( __( 'Tags: %s', 'commercestore' ), ( is_array( $tags ) ? '' : $tags ) ) );
			WP_CLI::line( sprintf( __( 'Pricing: %s', 'commercestore' ), $pricing ) );
			WP_CLI::line( sprintf( __( 'Sales: %s', 'commercestore' ), $product['stats']['total']['sales'] ) );
			WP_CLI::line( sprintf( __( 'Earnings: %s', 'commercestore' ), cs_format_amount( $product['stats']['total']['earnings'] ) ) ) . ' ' . cs_get_currency();
			WP_CLI::line( '' );
			WP_CLI::line( sprintf( __( 'Slug: %s', 'commercestore' ), $product['info']['slug'] ) );
			WP_CLI::line( sprintf( __( 'Permalink: %s', 'commercestore' ), $product['info']['link'] ) );

			if ( array_key_exists( 'files', $product ) ) {
				WP_CLI::line( '' );
				WP_CLI::line( __( 'Download Files:', 'commercestore' ) );

				foreach ( $product['files'] as $file ) {
					WP_CLI::line( '  ' . sprintf( __( 'File: %s (%s)', 'commercestore' ), $file['name'], $file['file'] ) );

					if ( isset( $file['condition'] ) && 'all' !== $file['condition'] ) {
						WP_CLI::line( '  ' . sprintf( __( 'Price Assignment: %s', 'commercestore' ), $file['condition'] ) );
					}
				}
			}

			WP_CLI::line( '' );
		}

	}


	/**
	 * Get the customers currently on your CommerceStore site. Can also be used to create customers records
	 *
	 * ## OPTIONS
	 *
	 * --id=<customer_id>: A specific customer ID to retrieve
	 * --email=<customer_email>: The email address of the customer to retrieve
	 * --create=<number>: The number of arbitrary customers to create. Leave as 1 or blank to create a customer with a
	 * speciific email
	 *
	 * ## EXAMPLES
	 *
	 * wp cs customers --id=103
	 * wp cs customers --email=john@test.com
	 * wp cs customers --create=1 --email=john@test.com
	 * wp cs customers --create=1 --email=john@test.com --name="John Doe"
	 * wp cs customers --create=1 --email=john@test.com --name="John Doe" user_id=1
	 * wp cs customers --create=1000
	 */
	public function customers( $args, $assoc_args ) {
		$customer_id = isset( $assoc_args ) && array_key_exists( 'id', $assoc_args ) ? absint( $assoc_args['id'] ) : false;
		$email       = isset( $assoc_args ) && array_key_exists( 'email', $assoc_args ) ? $assoc_args['email'] : false;
		$name        = isset( $assoc_args ) && array_key_exists( 'name', $assoc_args ) ? $assoc_args['name'] : null;
		$user_id     = isset( $assoc_args ) && array_key_exists( 'user_id', $assoc_args ) ? $assoc_args['user_id'] : null;
		$create      = isset( $assoc_args ) && array_key_exists( 'create', $assoc_args ) ? $assoc_args['create'] : false;
		$start       = time();

		if ( $create ) {
			$number = 1;

			// Create one or more customers
			if ( ! $email ) {

				// If no email is specified, look to see if we are generating arbitrary customer accounts
				$number = is_numeric( $create ) ? absint( $create ) : 1;
			}

			for ( $i = 0; $i < $number; $i ++ ) {
				if ( ! $email ) {

					// Generate fake email
					$email = 'customer-' . uniqid() . '@test.com';
				}

				$args = array(
					'email'   => $email,
					'name'    => $name,
					'user_id' => $user_id,
				);

				$customer_id = cs_add_customer( $args );

				if ( $customer_id ) {
					WP_CLI::line( sprintf( __( 'Customer %d created successfully', 'commercestore' ), $customer_id ) );
				} else {
					WP_CLI::error( __( 'Failed to create customer', 'commercestore' ) );
				}

				// Reset email to false so it is generated on the next loop (if creating customers)
				$email = false;

			}

			WP_CLI::line( WP_CLI::colorize( '%G' . sprintf( __( '%d customers created in %d seconds', 'commercestore' ), $create, time() - $start ) . '%N' ) );
		} else {
			// Search for customers
			$search = false;

			// Checking if search is being done by id, email or user_id fields.
			if ( $customer_id || $email || ( 'null' !== $user_id ) ) {
				$search           = array();
				$customer_details = array();

				if ( $customer_id ) {
					$customer_details['id'] = $customer_id;
				} elseif ( $email ) {
					$customer_details['email'] = $email;
				} elseif ( null !== $user_id ) {
					$customer_details['user_id'] = $user_id;
				}

				$search['customer'] = $customer_details;
			}

			$customers = $this->api->get_customers( $search );

			if ( isset( $customers['error'] ) ) {
				WP_CLI::error( $customers['error'] );
			}

			if ( empty( $customers ) ) {
				WP_CLI::error( __( 'No customers found', 'commercestore' ) );

				return;
			}

			foreach ( $customers['customers'] as $customer ) {
				WP_CLI::line( WP_CLI::colorize( '%G' . $customer['info']['email'] . '%N' ) );
				WP_CLI::line( sprintf( __( 'Customer User ID: %s', 'commercestore' ), $customer['info']['id'] ) );
				WP_CLI::line( sprintf( __( 'Username: %s', 'commercestore' ), $customer['info']['username'] ) );
				WP_CLI::line( sprintf( __( 'Display Name: %s', 'commercestore' ), $customer['info']['display_name'] ) );

				if ( array_key_exists( 'first_name', $customer ) ) {
					WP_CLI::line( sprintf( __( 'First Name: %s', 'commercestore' ), $customer['info']['first_name'] ) );
				}

				if ( array_key_exists( 'last_name', $customer ) ) {
					WP_CLI::line( sprintf( __( 'Last Name: %s', 'commercestore' ), $customer['info']['last_name'] ) );
				}

				WP_CLI::line( sprintf( __( 'Email: %s', 'commercestore' ), $customer['info']['email'] ) );

				WP_CLI::line( '' );
				WP_CLI::line( sprintf( __( 'Purchases: %s', 'commercestore' ), $customer['stats']['total_purchases'] ) );
				WP_CLI::line( sprintf( __( 'Total Spent: %s', 'commercestore' ), cs_format_amount( $customer['stats']['total_spent'] ) . ' ' . cs_get_currency() ) );
				WP_CLI::line( sprintf( __( 'Total Downloads: %s', 'commercestore' ), $customer['stats']['total_downloads'] ) );

				WP_CLI::line( '' );
			}

		}

	}


	/**
	 * Get the recent sales for your CommerceStore site
	 *
	 * ## OPTIONS
	 *
	 *     --email=<customer_email>: The email address of the customer to retrieve
	 *
	 * ## EXAMPLES
	 *
	 * wp cs sales
	 * wp cs sales --email=john@test.com
	 */
	public function sales( $args, $assoc_args ) {
		$email = isset( $assoc_args ) && array_key_exists( 'email', $assoc_args ) ? $assoc_args['email'] : '';

		global $wp_query;

		$wp_query->query_vars['email'] = $email;

		$sales = $this->api->get_recent_sales();

		if ( empty( $sales ) ) {
			WP_CLI::error( __( 'No sales found', 'commercestore' ) );

			return;
		}

		foreach ( $sales['sales'] as $sale ) {
			WP_CLI::line( WP_CLI::colorize( '%G' . $sale['ID'] . '%N' ) );
			WP_CLI::line( sprintf( __( 'Purchase Key: %s', 'commercestore' ), $sale['key'] ) );
			WP_CLI::line( sprintf( __( 'Email: %s', 'commercestore' ), $sale['email'] ) );
			WP_CLI::line( sprintf( __( 'Date: %s', 'commercestore' ), $sale['date'] ) );
			WP_CLI::line( sprintf( __( 'Subtotal: %s', 'commercestore' ), cs_format_amount( $sale['subtotal'] ) . ' ' . cs_get_currency() ) );
			WP_CLI::line( sprintf( __( 'Tax: %s', 'commercestore' ), cs_format_amount( $sale['tax'] ) . ' ' . cs_get_currency() ) );

			if ( array_key_exists( 0, $sale['fees'] ) ) {
				WP_CLI::line( __( 'Fees:', 'commercestore' ) );

				foreach ( $sale['fees'] as $fee ) {
					WP_CLI::line( sprintf( __( '  Fee: %s - %s', 'commercestore' ), cs_format_amount( $fee['amount'] ), cs_get_currency() ) );
				}
			}

			WP_CLI::line( sprintf( __( 'Total: %s', 'commercestore' ), cs_format_amount( $sale['total'] ) . ' ' . cs_get_currency() ) );
			WP_CLI::line( '' );
			WP_CLI::line( sprintf( __( 'Gateway: %s', 'commercestore' ), $sale['gateway'] ) );

			if ( array_key_exists( 0, $sale['products'] ) ) {
				WP_CLI::line( __( 'Products:', 'commercestore' ) );

				foreach ( $sale['products'] as $product ) {
					$price_name = ! empty( $product['price_name'] ) ? ' (' . $product['price_name'] . ')' : '';
					WP_CLI::line( sprintf( __( '  Product: %s - %s', 'commercestore' ), $product['name'], cs_format_amount( $product['price'] ) . ' ' . cs_get_currency() . $price_name ) );
				}
			}

			WP_CLI::line( '' );
		}
	}


	/**
	 * Get discount details for on your CommerceStore site
	 *
	 * ## OPTIONS
	 *
	 * --id=<discount_id>: A specific discount ID to retrieve
	 *
	 * ## EXAMPLES
	 *
	 * wp cs discounts --id=103
	 */
	public function discounts( $args, $assoc_args ) {

		$discount_id = isset( $assoc_args ) && array_key_exists( 'id', $assoc_args ) ? absint( $assoc_args['id'] ) : false;

		$discounts = $this->api->get_discounts( $discount_id );

		if ( isset( $discounts['error'] ) ) {
			WP_CLI::error( $discounts['error'] );
		}

		if ( empty( $discounts ) ) {
			WP_CLI::error( __( 'No discounts found', 'commercestore' ) );

			return;
		}

		foreach ( $discounts['discounts'] as $discount ) {
			WP_CLI::line( WP_CLI::colorize( '%G' . $discount['ID'] . '%N' ) );
			WP_CLI::line( sprintf( __( 'Name: %s', 'commercestore' ), $discount['name'] ) );
			WP_CLI::line( sprintf( __( 'Code: %s', 'commercestore' ), $discount['code'] ) );

			if ( $discount['type'] == 'percent' ) {
				$amount = $discount['amount'] . '%';
			} else {
				$amount = cs_format_amount( $discount['amount'] ) . ' ' . cs_get_currency();
			}

			WP_CLI::line( sprintf( __( 'Amount: %s', 'commercestore' ), $amount ) );
			WP_CLI::line( sprintf( __( 'Uses: %s', 'commercestore' ), $discount['uses'] ) );
			WP_CLI::line( sprintf( __( 'Max Uses: %s', 'commercestore' ), ( $discount['max_uses'] == '0' ? __( 'Unlimited', 'commercestore' ) : $discount['max_uses'] ) ) );
			WP_CLI::line( sprintf( __( 'Start Date: %s', 'commercestore' ), ( empty( $discount['start_date'] ) ? __( 'No Start Date', 'commercestore' ) : $discount['start_date'] ) ) );
			WP_CLI::line( sprintf( __( 'Expiration Date: %s', 'commercestore' ), ( empty( $discount['exp_date'] ) ? __( 'No Expiration', 'commercestore' ) : $discount['exp_date'] ) ) );
			WP_CLI::line( sprintf( __( 'Status: %s', 'commercestore' ), ucwords( $discount['status'] ) ) );

			WP_CLI::line( '' );

			if ( array_key_exists( 0, $discount['product_requirements'] ) ) {
				WP_CLI::line( __( 'Product Requirements:', 'commercestore' ) );

				foreach ( $discount['product_requirements'] as $req => $req_id ) {
					WP_CLI::line( sprintf( __( '  Product: %s', 'commercestore' ), $req_id ) );
				}
			}

			WP_CLI::line( '' );

			WP_CLI::line( sprintf( __( 'Global Discount: %s', 'commercestore' ), ( empty( $discount['global_discount'] ) ? 'False' : 'True' ) ) );
			WP_CLI::line( sprintf( __( 'Single Use: %s', 'commercestore' ), ( empty( $discount['single_use'] ) ? 'False' : 'True' ) ) );

			WP_CLI::line( '' );
		}
	}

	/**
	 * Create sample purchase data for your CommerceStore site
	 *
	 * ## OPTIONS
	 *
	 * --number: The number of purchases to create
	 * --status=<status>: The status to create purchases as
	 * --id=<product_id>: A specific product to create purchase data for
	 * --price_id=<price_id>: A price ID of the specified product
	 *
	 * ## EXAMPLES
	 *
	 * wp cs payments create --number=10 --status=complete
	 * wp cs payments create --number=10 --id=103
	 */
	public function payments( $args, $assoc_args ) {

		$error = false;

		// At some point we'll likely add another action for payments
		if ( ! isset( $args ) || 0 === count( $args ) ) {
			$error = __( 'No action specified, did you mean', 'commercestore' );
		} elseif ( isset( $args ) && ! in_array( 'create', $args, true ) ) {
			$error = __( 'Invalid action specified, did you mean', 'commercestore' );
		}

		if ( $error ) {
			$query = '';
			foreach ( $assoc_args as $key => $value ) {
				$query .= ' --' . $key . '=' . $value;
			}

			WP_CLI::error(
				sprintf( $error . ' %s?', 'wp cs payments create' . $query )
			);

			return;
		}


		// Setup some defaults
		$number   = 1;
		$status   = 'complete';
		$id       = false;
		$price_id = false;
		$tax      = 0;
		$email    = 'guest@cs.local';
		$fname    = 'Pippin';
		$lname    = 'Williamson';
		$date     = false;
		$range    = 30;

		$generate_users = false;

		if ( count( $assoc_args ) > 0 ) {
			$number   = ( array_key_exists( 'number', $assoc_args ) ) ? absint( $assoc_args['number'] ) : $number;
			$id       = ( array_key_exists( 'id', $assoc_args ) ) ? absint( $assoc_args['id'] ) : $id;
			$price_id = ( array_key_exists( 'price_id', $assoc_args ) ) ? absint( $assoc_args['price_id'] ) : $price_id;
			$tax      = ( array_key_exists( 'tax', $assoc_args ) ) ? floatval( $assoc_args['tax'] ) : $tax;
			$email    = ( array_key_exists( 'email', $assoc_args ) ) ? sanitize_email( $assoc_args['email'] ) : $email;
			$fname    = ( array_key_exists( 'fname', $assoc_args ) ) ? sanitize_text_field( $assoc_args['fname'] ) : $fname;
			$lname    = ( array_key_exists( 'lname', $assoc_args ) ) ? sanitize_text_field( $assoc_args['lname'] ) : $lname;
			$date     = ( array_key_exists( 'date', $assoc_args ) ) ? sanitize_text_field( $assoc_args['date'] ) : $date;
			$range    = ( array_key_exists( 'range', $assoc_args ) ) ? absint( $assoc_args['range'] ) : $range;

			$generate_users = ( array_key_exists( 'generate_users', $assoc_args ) ) ? (bool) absint( $assoc_args['generate_users'] ) : $generate_users;

			// Status requires a bit more validation.
			if ( array_key_exists( 'status', $assoc_args ) ) {
				$statuses = array_keys( cs_get_payment_statuses() );

				if ( in_array( $assoc_args['status'], $statuses, true ) ) {
					$status = ( 'publish' === $assoc_args['status'] )
						? 'complete'
						: $assoc_args['status'];
				} else {
					WP_CLI::warning( sprintf(
						__( "Invalid status '%s', defaulting to 'complete'", 'commercestore' ),
						$assoc_args['status']
					) );
				}
			}
		}

		// Build the user info array.
		$user_info = array(
			'id'         => 0,
			'email'      => $email,
			'first_name' => $fname,
			'last_name'  => $lname,
			'discount'   => 'none',
		);

		$progress = \WP_CLI\Utils\make_progress_bar( 'Creating Orders', $number );

		for ( $i = 0; $i < $number; $i ++ ) {
			$products = array();
			$total    = 0;

			// No specified product
			if ( ! $id ) {
				$products = get_posts( array(
					'post_type'      => 'download',
					'orderby'        => 'rand',
					'order'          => 'ASC',
					'posts_per_page' => rand( 1, 3 ),
				) );
			} else {
				$product = get_post( $id );

				if ( 'download' !== $product->post_type ) {
					WP_CLI::error( __( 'Specified ID is not a product', 'commercestore' ) );

					return;
				}

				$products[] = $product;
			}

			$cart_details = array();

			// Add each download to the order.
			foreach ( $products as $key => $download ) {
				if ( ! $download instanceof WP_Post ) {
					continue;
				}

				$options         = array();
				$final_downloads = array();

				// Variable price.
				if ( cs_has_variable_prices( $download->ID ) ) {
					$prices = cs_get_variable_prices( $download->ID );

					if ( false === $price_id || ! array_key_exists( $price_id, (array) $prices ) ) {
						$item_price_id = array_rand( $prices );
					} else {
						$item_price_id = $price_id;
					}

					$item_price          = $prices[ $item_price_id ]['amount'];
					$options['price_id'] = $item_price_id;

				// Flat price.
				} else {
					$item_price = cs_get_download_price( $download->ID );
				}

				$item_number = array(
					'id'       => $download->ID,
					'quantity' => 1,
					'options'  => $options,
				);

				$cart_details[ $key ] = array(
					'name'        => $download->post_title,
					'id'          => $download->ID,
					'item_number' => $item_number,
					'item_price'  => cs_sanitize_amount( $item_price ),
					'subtotal'    => cs_sanitize_amount( $item_price ),
					'price'       => cs_sanitize_amount( $item_price ),
					'quantity'    => 1,
					'discount'    => 0,
					'tax'         => cs_calculate_tax( $item_price ),
				);

				$final_downloads[ $key ] = $item_number;

				$total += $item_price;
			}

			// Generate random date.
			if ( 'random' === $date ) {
				// Randomly grab a date from the current past 30 days
				$oldest_time = strtotime( '-' . $range . ' days', current_time( 'timestamp' ) );
				$newest_time = current_time( 'timestamp' );

				$timestamp  = rand( $oldest_time, $newest_time );
				$timestring = date( "Y-m-d H:i:s", $timestamp );
			} elseif ( empty( $date ) ) {
				$timestring = false;
			} else {
				if ( is_numeric( $date ) ) {
					$timestring = date( "Y-m-d H:i:s", $date );
				} else {
					$parsed_time = strtotime( $date );
					$timestring  = date( "Y-m-d H:i:s", $parsed_time );
				}
			}

			// Maybe generate users.
			if ( $generate_users ) {
				$fname  = $this->get_fname();
				$lname  = $this->get_lname();
				$domain = $this->get_domain();
				$tld    = $this->get_tld();

				$email = $fname . '.' . $lname . '@' . $domain . '.' . $tld;

				$user_info = array(
					'id'         => 0,
					'email'      => $email,
					'first_name' => $fname,
					'last_name'  => $lname,
					'discount'   => 'none',
				);
			}

			// Build purchase data.
			$purchase_data = array(
				'price'        => cs_sanitize_amount( $total ),
				'tax'          => cs_calculate_tax( $total ),
				'purchase_key' => strtolower( md5( uniqid() ) ),
				'user_email'   => $email,
				'user_info'    => $user_info,
				'currency'     => cs_get_currency(),
				'downloads'    => $final_downloads,
				'cart_details' => $cart_details,
				'status'       => 'pending',
			);

			if ( ! empty( $timestring ) ) {
				$purchase_data['date_created'] = $timestring;
			}

			$order_id = cs_build_order( $purchase_data );

			// Ensure purchase receipts do not get sent.
			remove_action( 'cs_complete_purchase', 'cs_trigger_purchase_receipt', 999 );

			// Trigger payment status actions.
			if ( 'pending' !== $status ) {
				cs_update_order_status( $order_id, $status );
			}

			if ( ! empty( $timestring ) ) {
				$payment                 = new CS_Payment( $order_id );
				$payment->completed_date = $timestring;
				$payment->save();
			}

			$progress->tick();
		}

		$progress->finish();

		WP_CLI::success( sprintf( __( 'Created %s orders', 'commercestore' ), $number ) );

		return;
	}

	/**
	 * Create discount codes for your CommerceStore site
	 *
	 * ## OPTIONS
	 *
	 * --legacy: Create legacy discount codes using pre-3.0 schema
	 * --number: The number of discounts to create
	 *
	 * ## EXAMPLES
	 *
	 * wp cs create_discounts --number=100
	 * wp cs create_discounts --number=50 --legacy
	 */
	public function create_discounts( $args, $assoc_args ) {
		$number = array_key_exists( 'number', $assoc_args ) ? absint( $assoc_args['number'] ) : 1;
		$legacy = array_key_exists( 'legacy', $assoc_args ) ? true : false;

		$progress = \WP_CLI\Utils\make_progress_bar( 'Creating Discount Codes', $number );

		for ( $i = 0; $i < $number; $i ++ ) {
			if ( $legacy ) {
				$discount_id = wp_insert_post( array(
					'post_type'   => 'cs_discount',
					'post_title'  => 'Auto-Generated Legacy Discount #' . $i,
					'post_status' => 'active',
				) );

				$download_ids = get_posts( array(
					'post_type'      => 'download',
					'posts_per_page' => 2,
					'fields'         => 'ids',
					'orderby'        => 'rand',
				) );

				$meta = array(
					'code'              => 'LEGACY' . $i,
					'status'            => 'active',
					'uses'              => 10,
					'max_uses'          => 20,
					'name'              => 'Auto-Generated Legacy Discount #' . $i,
					'amount'            => 20,
					'start'             => '01/01/2000 00:00:00',
					'expiration'        => '12/31/2050 23:59:59',
					'type'              => 'percent',
					'min_price'         => '10.50',
					'product_reqs'      => array( $download_ids[0] ),
					'product_condition' => 'all',
					'excluded_products' => array( $download_ids[1] ),
					'is_not_global'     => true,
					'is_single_use'     => true,
				);

				remove_action( 'pre_get_posts', '_cs_discount_get_post_doing_it_wrong', 99, 1 );
				remove_filter( 'add_post_metadata', '_cs_discount_update_meta_backcompat', 99 );

				foreach ( $meta as $key => $value ) {
					add_post_meta( $discount_id, '_cs_discount_' . $key, $value );
				}

				add_filter( 'add_post_metadata', '_cs_discount_update_meta_backcompat', 99, 5 );
				add_action( 'pre_get_posts', '_cs_discount_get_post_doing_it_wrong', 99, 1 );
			} else {
				$type              = array( 'flat', 'percent' );
				$status            = array( 'active', 'inactive' );
				$product_condition = array( 'any', 'all' );

				$type_index              = array_rand( $type, 1 );
				$status_index            = array_rand( $status, 1 );
				$product_condition_index = array_rand( $product_condition, 1 );

				$post = array(
					'code'              => md5( time() ),
					'uses'              => mt_rand( 0, 100 ),
					'max'               => mt_rand( 0, 100 ),
					'name'              => 'Auto-Generated Discount #' . $i,
					'type'              => $type[ $type_index ],
					'amount'            => mt_rand( 10, 95 ),
					'start'             => '12/12/2010 00:00:00',
					'expiration'        => '12/31/2050 23:59:59',
					'min_price'         => mt_rand( 30, 255 ),
					'status'            => $status[ $status_index ],
					'product_condition' => $product_condition[ $product_condition_index ],
				);

				cs_store_discount( $post );

				$progress->tick();
			}
		}

		$progress->finish();

		WP_CLI::success( sprintf( __( 'Created %s discounts', 'commercestore' ), $number ) );

		return;
	}

	/**
	 * Run the CommerceStore 3.0 Migration via WP-CLI
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function v30_migration( $args, $assoc_args ) {

		// Suspend the cache addition while we're migrating.
		wp_suspend_cache_addition( true );

		$this->maybe_install_v3_tables();
		$this->migrate_tax_rates( $args, $assoc_args );
		$this->migrate_discounts( $args, $assoc_args );
		$this->migrate_payments( $args, $assoc_args );
		$this->migrate_customer_data( $args, $assoc_args );
		$this->migrate_logs( $args, $assoc_args );
		$this->migrate_order_notes( $args, $assoc_args );
		$this->migrate_customer_notes( $args, $assoc_args );
		cs_v30_is_migration_complete();
		$this->remove_legacy_data( $args, $assoc_args );
	}

	/**
	 * Installs any new 3.0 database tables that haven't yet been installed
	 *
	 * @access private
	 * @since 3.0
	 */
	private function maybe_install_v3_tables() {
		static $installed = false;

		if ( $installed ) {
			return;
		}

		foreach ( CS()->components as $component ) {
			// Install the main component table.
			$table = $component->get_interface( 'table' );
			if ( $table instanceof CS\Database\Table && ! $table->exists() ) {
				$table->install();
			}

			// Install the associated meta table, if there is one.
			$meta = $component->get_interface( 'meta' );
			if ( $meta instanceof CS\Database\Table && ! $meta->exists() ) {
				$meta->install();
			}
		}

		// Only need to do this once.
		$installed = true;
	}

	/**
	 * Migrate Discounts to the custom tables
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * ## EXAMPLES
	 *
	 * wp cs migrate_discounts
	 * wp cs migrate_discounts --force
	 */
	public function migrate_discounts( $args, $assoc_args ) {
		global $wpdb;

		$this->maybe_install_v3_tables();

		require_once CS_PLUGIN_DIR . 'includes/admin/upgrades/v3/class-data-migrator.php';

		$force = isset( $assoc_args['force'] )
			? true
			: false;

		$upgrade_completed = cs_has_upgrade_completed( 'migrate_discounts' );

		if ( ! $force && $upgrade_completed ) {
			WP_CLI::error( __( 'The discounts custom database migration has already been run. To do this anyway, use the --force argument.', 'commercestore' ) );
		}

		$sql     = "SELECT * FROM {$wpdb->posts} WHERE post_type = 'cs_discount'";
		$results = $wpdb->get_results( $sql );
		$total   = count( $results );

		if ( ! empty( $total ) ) {

			$progress = new \cli\progress\Bar( 'Migrating Discounts', $total );

			foreach ( $results as $result ) {
				\CS\Admin\Upgrades\v3\Data_Migrator::discounts( $result );

				$progress->tick();
			}

			$progress->finish();

			WP_CLI::line( __( 'Migration complete: Discounts', 'commercestore' ) );
			$new_count = cs_get_discount_count();
			$old_count = $wpdb->get_col( "SELECT count(ID) FROM $wpdb->posts WHERE post_type ='cs_discount'", 0 );
			WP_CLI::line( __( 'Old Records: ', 'commercestore' ) . $old_count[0] );
			WP_CLI::line( __( 'New Records: ', 'commercestore' ) . $new_count );

			cs_update_db_version();
			cs_set_upgrade_complete( 'migrate_discounts' );

		} else {

			WP_CLI::line( __( 'No discount records found.', 'commercestore' ) );
			cs_set_upgrade_complete( 'migrate_discounts' );

		}
	}

	/**
	 * Migrate logs to the custom tables.
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * ## EXAMPLES
	 *
	 * wp cs migrate_logs
	 * wp cs migrate_logs --force
	 */
	public function migrate_logs( $args, $assoc_args ) {
		global $wpdb;

		$this->maybe_install_v3_tables();

		require_once CS_PLUGIN_DIR . 'includes/admin/upgrades/v3/class-data-migrator.php';

		$force = isset( $assoc_args['force'] )
			? true
			: false;

		$upgrade_completed = cs_has_upgrade_completed( 'migrate_logs' );

		if ( ! $force && $upgrade_completed ) {
			WP_CLI::error( __( 'The logs custom table migration has already been run. To do this anyway, use the --force argument.', 'commercestore' ) );
		}

		$sql = "
			SELECT p.*, t.slug
			FROM {$wpdb->posts} AS p
			LEFT JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
			LEFT JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
			LEFT JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id)
			WHERE p.post_type = 'cs_log' AND t.slug != 'sale'
		";

		$results = $wpdb->get_results( $sql );
		$total   = count( $results );

		if ( ! empty( $total ) ) {
			$progress = new \cli\progress\Bar( 'Migrating Logs', $total );

			foreach ( $results as $result ) {
				\CS\Admin\Upgrades\v3\Data_Migrator::logs( $result );

				$progress->tick();
			}

			$progress->finish();

			WP_CLI::line( __( 'Migration complete: Logs', 'commercestore' ) );
			$new_count = cs_count_logs() + cs_count_file_download_logs() + cs_count_api_request_logs();
			WP_CLI::line( __( 'Old Records: ', 'commercestore' ) . $total );
			WP_CLI::line( __( 'New Records: ', 'commercestore' ) . $new_count );

			cs_update_db_version();
			cs_set_upgrade_complete( 'migrate_logs' );
		} else {
			WP_CLI::line( __( 'No log records found.', 'commercestore' ) );
			cs_set_upgrade_complete( 'migrate_logs' );
		}
	}

	/**
	 * Migrate order notes to the custom tables.
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * ## EXAMPLES
	 *
	 * wp cs migrate_notes
	 * wp cs migrate_notes --force
	 */
	public function migrate_order_notes( $args, $assoc_args ) {
		global $wpdb;

		$this->maybe_install_v3_tables();

		require_once CS_PLUGIN_DIR . 'includes/admin/upgrades/v3/class-data-migrator.php';

		$force = isset( $assoc_args['force'] )
			? true
			: false;

		$upgrade_completed = cs_has_upgrade_completed( 'migrate_order_notes' );

		if ( ! $force && $upgrade_completed ) {
			WP_CLI::error( __( 'The order notes custom table migration has already been run. To do this anyway, use the --force argument.', 'commercestore' ) );
		}

		$sql     = "SELECT * FROM {$wpdb->comments} WHERE comment_type = 'cs_payment_note' ORDER BY comment_ID ASC";
		$results = $wpdb->get_results( $sql );
		$total   = count( $results );

		if ( ! empty( $total ) ) {
			$progress = new \cli\progress\Bar( 'Migrating Notes', $total );

			foreach ( $results as $result ) {
				$result->object_id = $result->comment_post_ID;
				\CS\Admin\Upgrades\v3\Data_Migrator::order_notes( $result );

				$progress->tick();
			}

			$progress->finish();

			WP_CLI::line( __( 'Migration complete: Order Notes', 'commercestore' ) );
			$new_count = cs_count_notes();
			$old_count = $wpdb->get_col( "SELECT count(comment_ID) FROM {$wpdb->comments} WHERE comment_type = 'cs_payment_note'", 0 );
			WP_CLI::line( __( 'Old Records: ', 'commercestore' ) . $old_count[0] );
			WP_CLI::line( __( 'New Records: ', 'commercestore' ) . $new_count );

			cs_update_db_version();
			cs_set_upgrade_complete( 'migrate_order_notes' );
		} else {
			WP_CLI::line( __( 'No note records found.', 'commercestore' ) );
			cs_set_upgrade_complete( 'migrate_order_notes' );
		}
	}

	/**
	 * Migrate customer notes to the custom tables.
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * ## EXAMPLES
	 *
	 * wp cs migrate_notes
	 * wp cs migrate_notes --force
	 */
	public function migrate_customer_notes( $args, $assoc_args ) {
		global $wpdb;

		$this->maybe_install_v3_tables();

		require_once CS_PLUGIN_DIR . 'includes/admin/upgrades/v3/class-data-migrator.php';

		$force = isset( $assoc_args['force'] )
			? true
			: false;

		$upgrade_completed = cs_has_upgrade_completed( 'migrate_customer_notes' );

		if ( ! $force && $upgrade_completed ) {
			WP_CLI::error( __( 'The customer notes custom table migration has already been run. To do this anyway, use the --force argument.', 'commercestore' ) );
		}

		$sql     = "SELECT * FROM {$wpdb->cs_customers}";
		$results = $wpdb->get_results( $sql );
		$total   = count( $results );

		if ( ! empty( $total ) ) {
			$progress = new \cli\progress\Bar( 'Migrating Customer Notes', $total );

			foreach ( $results as $result ) {
				\CS\Admin\Upgrades\v3\Data_Migrator::customer_notes( $result );

				$progress->tick();
			}

			$progress->finish();

			WP_CLI::line( __( 'Migration complete: Customer Notes', 'commercestore' ) );

			cs_update_db_version();
			cs_set_upgrade_complete( 'migrate_customer_notes' );
		} else {
			WP_CLI::line( __( 'No customer note records found.', 'commercestore' ) );
			cs_set_upgrade_complete( 'migrate_customer_notes' );
		}
	}

	/**
	 * Migrate customer data to the custom tables.
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * ## EXAMPLES
	 *
	 * wp cs migrate_customer_data
	 * wp cs migrate_customer_data --force
	 */
	public function migrate_customer_data( $args, $assoc_args ) {
		global $wpdb;

		$this->maybe_install_v3_tables();

		require_once CS_PLUGIN_DIR . 'includes/admin/upgrades/v3/class-data-migrator.php';

		$force = isset( $assoc_args['force'] )
			? true
			: false;

		$customer_addresses_complete = cs_has_upgrade_completed( 'migrate_customer_addresses' );

		if ( ! $force && $customer_addresses_complete ) {
			WP_CLI::warning( __( 'The user addresses custom table migration has already been run. To do this anyway, use the --force argument.', 'commercestore' ) );
		} else {

			// Create the tables if they do not exist.
			$components = array(
				array( 'order', 'table' ),
				array( 'order', 'meta' ),
				array( 'customer', 'table' ),
				array( 'customer', 'meta' ),
				array( 'customer_address', 'table' ),
				array( 'customer_email_address', 'table' ),
			);

			foreach ( $components as $component ) {
				/** @var CS\Database\Tables\Base $table */
				$table = cs_get_component_interface( $component[0], $component[1] );

				if ( $table instanceof CS\Database\Tables\Base && ! $table->exists() ) {
					@$table->create();
				}
			}

			// Migrate user addresses first.
			$sql = "
				SELECT *
				FROM {$wpdb->usermeta}
				WHERE meta_key = '_cs_user_address'
			";
			$results = $wpdb->get_results( $sql );
			$total   = count( $results );

			if ( ! empty( $total ) ) {
				$progress = new \cli\progress\Bar( 'Migrating User Addresses', $total );

				foreach ( $results as $result ) {
					\CS\Admin\Upgrades\v3\Data_Migrator::customer_addresses( $result, 'billing' );

					$progress->tick();
				}

				$progress->finish();
			}

			// Now update the most recent billing address entries for customers as the primary address.
			$sql = "
				UPDATE {$wpdb->cs_customer_addresses} ca
				SET ca.is_primary = 1
				WHERE ca.id IN (
					SELECT MAX(ca2.id)
					FROM ( SELECT * FROM {$wpdb->cs_customer_addresses} ) ca2
					WHERE ca2.type = 'billing'
					GROUP BY ca2.customer_id
				)
			";

			@$wpdb->query( $sql );

			cs_set_upgrade_complete( 'migrate_customer_addresses' );
		}

		$customer_email_addresses_complete = cs_has_upgrade_completed( 'migrate_customer_email_addresses' );

		if ( ! $force && $customer_email_addresses_complete ) {
			WP_CLI::warning( __( 'The user email addresses custom table migration has already been run. To do this anyway, use the --force argument.', 'commercestore' ) );
		} else {
			// Migrate email addresses next.
			$sql = "
				SELECT *
				FROM {$wpdb->cs_customermeta}
				WHERE meta_key = 'additional_email'
			";
			$results = $wpdb->get_results( $sql );
			$total   = count( $results );

			if ( ! empty( $total ) ) {
				$progress = new \cli\progress\Bar( 'Migrating Email Addresses', $total );

				foreach ( $results as $result ) {
					\CS\Admin\Upgrades\v3\Data_Migrator::customer_email_addresses( $result );

					$progress->tick();
				}

				$progress->finish();
			}
			cs_set_upgrade_complete( 'migrate_customer_email_addresses' );
		}

		WP_CLI::line( __( 'Migration complete: Email Addresses', 'commercestore' ) );

		cs_update_db_version();
	}

	/**
	 * Migrate tax rates.
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * ## EXAMPLES
	 *
	 * wp cs migrate_tax_rates
	 * wp cs migrate_tax_rates --force
	 */
	public function migrate_tax_rates( $args, $assoc_args ) {
		global $wpdb;

		$this->maybe_install_v3_tables();

		require_once CS_PLUGIN_DIR . 'includes/admin/upgrades/v3/class-data-migrator.php';

		$force = isset( $assoc_args['force'] )
			? true
			: false;

		$upgrade_completed = cs_has_upgrade_completed( 'migrate_tax_rates' );

		if ( ! $force && $upgrade_completed ) {
			WP_CLI::error( __( 'The tax rates custom table migration has already been run. To do this anyway, use the --force argument.', 'commercestore' ) );
		}

		// Migrate user addresses first.
		$tax_rates = get_option( 'cs_tax_rates', array() );

		if ( ! empty( $tax_rates ) ) {
			$progress = new \cli\progress\Bar( 'Migrating Tax Rates', count( $tax_rates ) );

			foreach ( $tax_rates as $result ) {
				\CS\Admin\Upgrades\v3\Data_Migrator::tax_rates( $result );

				$progress->tick();
			}

			$progress->finish();
		}

		WP_CLI::line( __( 'Migration complete: Tax Rates', 'commercestore' ) );
		$new_count = cs_count_adjustments( array( 'type' => 'tax_rate' ) );
		WP_CLI::line( __( 'Old Records: ', 'commercestore' ) . count( $tax_rates ) );
		WP_CLI::line( __( 'New Records: ', 'commercestore' ) . $new_count );

		cs_update_db_version();
		cs_set_upgrade_complete( 'migrate_tax_rates' );
	}

	/**
	 * Migrate payments to the custom tables.
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * ## EXAMPLES
	 *
	 * wp cs migrate_payments
	 * wp cs migrate_payments --force
	 */
	public function migrate_payments( $args, $assoc_args ) {
		global $wpdb;

		$this->maybe_install_v3_tables();

		require_once CS_PLUGIN_DIR . 'includes/admin/upgrades/v3/class-data-migrator.php';

		$force = isset( $assoc_args['force'] )
			? true
			: false;

		$upgrade_completed = cs_has_upgrade_completed( 'migrate_orders' );

		if ( ! $force && $upgrade_completed ) {
			WP_CLI::error( __( 'The payments custom table migration has already been run. To do this anyway, use the --force argument.', 'commercestore' ) );
		}

		$sql = "
			SELECT *
			FROM {$wpdb->posts}
			WHERE post_type = 'cs_payment'
			ORDER BY ID ASC
		";
		$results = $wpdb->get_results( $sql );
		$total   = count( $results );

		if ( ! empty( $total ) ) {
			$progress = new \cli\progress\Bar( 'Migrating Payments', $total );
			$orders   = new \CS\Database\Queries\Order();
			foreach ( $results as $result ) {

				// Check if order has already been migrated.
				$migrated = $orders->get_item( $result->ID );
				if ( $migrated ) {
					continue;
				}

				\CS\Admin\Upgrades\v3\Data_Migrator::orders( $result );

				$progress->tick();
			}

			$progress->finish();

			WP_CLI::line( __( 'Migration complete: Orders', 'commercestore' ) );
			$new_count = cs_count_orders( array( 'type' => 'sale' ) );
			$old_count = $wpdb->get_col( "SELECT count(ID) FROM {$wpdb->posts} WHERE post_type = 'cs_payment'", 0 );
			WP_CLI::line( __( 'Old Records: ', 'commercestore' ) . $old_count[0] );
			WP_CLI::line( __( 'New Records: ', 'commercestore' ) . $new_count );

			$refund_count = cs_count_orders( array( 'type' => 'refund' ) );
			WP_CLI::line( __( 'Refund Records Created: ', 'commercestore' ) . $refund_count );

			cs_update_db_version();
			cs_set_upgrade_complete( 'migrate_orders' );

			$this->recalculate_download_sales_earnings();
		} else {
			WP_CLI::line( __( 'No payment records found.', 'commercestore' ) );
			cs_set_upgrade_complete( 'migrate_orders' );
			cs_set_upgrade_complete( 'remove_legacy_payments' );
		}
	}

	/**
	 * Recalculates the sales and earnings for all downloads.
	 *
	 * @since 3.0
	 * @return void
	 *
	 * wp cs recalculate_download_sales_earnings
	 */
	public function recalculate_download_sales_earnings() {
		global $wpdb;

		$downloads = $wpdb->get_results(
			"SELECT ID
			FROM {$wpdb->posts}
			WHERE post_type = 'download'
			ORDER BY ID ASC"
		);
		$total     = count( $downloads );
		if ( ! empty( $total ) ) {
			$progress = new \cli\progress\Bar( 'Recalculating Download Sales and Earnings', $total );
			foreach ( $downloads as $download ) {
				cs_recalculate_download_sales_earnings( $download->ID );
				$progress->tick();
			}
			$progress->finish();
		}
		WP_CLI::line( __( 'Sales and Earnings successfully recalculated for all downloads.', 'commercestore' ) );
		WP_CLI::line( __( 'Downloads Updated: ', 'commercestore' ) . $total );
	}

	/**
	 * Removes legacy data from 2.9 and earlier that has been migrated to 3.0.
	 *
	 * ## OPTIONS
	 *
	 * --force=<boolean>: If the routine should be run even if the upgrade routine has been run already
	 *
	 * ## EXAMPLES
	 *
	 * wp cs remove_legacy_data
	 * wp cs remove_legacy_data --force
	 */
	public function remove_legacy_data( $args, $assoc_args ) {
		global $wpdb;

		WP_CLI::confirm( __( 'Do you want to remove legacy data? This will permanently remove legacy discounts, logs, and order notes.', 'commercestore' ) );

		$force = isset( $assoc_args['force'] ) ? true : false;

		/**
		 * Discounts
		 */
		if ( ! $force && cs_has_upgrade_completed( 'remove_legacy_discounts' ) ) {
			WP_CLI::warning( __( 'Legacy discounts have already been removed. To run this anyway, use the --force argument.', 'commercestore' ) );
		} else {
			WP_CLI::line( __( 'Removing old discount data.', 'commercestore' ) );

			$discount_ids = $wpdb->get_results( "SELECT ID FROM $wpdb->posts WHERE post_type = 'cs_discount'" );
			$discount_ids = wp_list_pluck( $discount_ids, 'ID' );
			$discount_ids = implode( ', ', $discount_ids );

			if ( ! empty( $discount_ids ) ) {
				$delete_posts_query = "DELETE FROM $wpdb->posts WHERE ID IN ({$discount_ids})";
				$wpdb->query( $delete_posts_query );

				$delete_postmeta_query = "DELETE FROM $wpdb->postmeta WHERE post_id IN ({$discount_ids})";
				$wpdb->query( $delete_postmeta_query );
			}

			cs_set_upgrade_complete( 'remove_legacy_discounts' );
		}

		/**
		 * Logs
		 */
		if ( ! $force && cs_has_upgrade_completed( 'remove_legacy_logs' ) ) {
			WP_CLI::warning( __( 'Legacy logs have already been removed. To run this anyway, use the --force argument.', 'commercestore' ) );
		} else {
			WP_CLI::line( __( 'Removing old logs.', 'commercestore' ) );

			$log_ids = $wpdb->get_results( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'cs_log'" );
			$log_ids = wp_list_pluck( $log_ids, 'ID' );
			$log_ids = implode( ', ', $log_ids );

			if ( ! empty( $log_ids ) ) {
				$delete_query = "DELETE FROM {$wpdb->posts} WHERE post_type = 'cs_log'";
				$wpdb->query( $delete_query );

				$delete_postmeta_query = "DELETE FROM {$wpdb->posts} WHERE ID IN ({$log_ids})";
				$wpdb->query( $delete_postmeta_query );
			}

			cs_set_upgrade_complete( 'remove_legacy_logs' );
		}

		/**
		 * Order notes
		 */
		if ( ! $force && cs_has_upgrade_completed( 'remove_legacy_order_notes' ) ) {
			WP_CLI::warning( __( 'Legacy order notes have already been removed. To run this anyway, use the --force argument.', 'commercestore' ) );
		} else {
			WP_CLI::line( __( 'Removing old order notes.', 'commercestore' ) );

			$note_ids = $wpdb->get_results( "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_type = 'cs_payment_note'" );
			$note_ids = wp_list_pluck( $note_ids, 'comment_ID' );
			$note_ids = implode( ', ', $note_ids );

			if ( ! empty( $note_ids ) ) {
				$delete_query = "DELETE FROM {$wpdb->comments} WHERE comment_type = 'cs_payment_note'";
				$wpdb->query( $delete_query );

				$delete_postmeta_query = "DELETE FROM {$wpdb->commentmeta} WHERE comment_id IN ({$note_ids})";
				$wpdb->query( $delete_postmeta_query );
			}

			cs_set_upgrade_complete( 'remove_legacy_order_notes' );
		}

		/**
		 * Customers
		 *
		 * @var \CS\Database\Tables\Customers|false $customer_table
		 */
		$customer_table = cs_get_component_interface( 'customer', 'table' );
		if ( $customer_table instanceof \CS\Database\Tables\Customers && $customer_table->column_exists( 'payment_ids' ) ) {
			WP_CLI::line( __( 'Updating customers database table.', 'commercestore' ) );

			$wpdb->query( "ALTER TABLE {$wpdb->cs_customers} DROP `payment_ids`" );
		}

		/**
		 * Customer emails
		 */
		if ( ! $force && cs_has_upgrade_completed( 'remove_legacy_customer_emails' ) ) {
			WP_CLI::warning( __( 'Legacy customer emails have already been removed. To run this anyway, use the --force argument.', 'commercestore' ) );
		} else {
			WP_CLI::line( __( 'Removing old customer emails.', 'commercestore' ) );

			$wpdb->query( "DELETE FROM {$wpdb->cs_customermeta} WHERE meta_key = 'additional_email'" );

			cs_set_upgrade_complete( 'remove_legacy_customer_emails' );
		}

		/**
		 * Customer addresses
		 */
		if ( ! $force && cs_has_upgrade_completed( 'remove_legacy_customer_addresses' ) ) {
			WP_CLI::warning( __( 'Legacy customer addresses have already been removed. To run this anyway, use the --force argument.', 'commercestore' ) );
		} else {
			WP_CLI::line( __( 'Removing old customer addresses.', 'commercestore' ) );

			$wpdb->query( "DELETE FROM {$wpdb->usermeta} WHERE meta_key = '_cs_user_address'" );

			cs_set_upgrade_complete( 'remove_legacy_customer_addresses' );
		}

		/**
		 * Orders
		 */
		if ( ! $force && cs_has_upgrade_completed( 'remove_legacy_orders' ) ) {
			WP_CLI::warning( __( 'Legacy orders have already been removed. To run this anyway, use the --force argument.', 'commercestore' ) );
		} else {
			WP_CLI::line( __( 'Removing old orders.', 'commercestore' ) );

			$wpdb->query(
				"DELETE orders, order_meta FROM {$wpdb->posts} orders
				LEFT JOIN {$wpdb->postmeta} order_meta ON( orders.ID = order_meta.post_id )
				WHERE orders.post_type = 'cs_payment'"
			);

			cs_set_upgrade_complete( 'remove_legacy_orders' );
		}
	}

	/*
	 * Create sample file download log data for your CommerceStore site
	 *
	 * ## OPTIONS
	 *
	 * --number: The number of download logs to create
	 *
	 * ## EXAMPLES
	 *
	 * wp cs download_logs create --number=10
	 */
	public function download_logs( $args, $assoc_args ) {
		global $wpdb, $cs_logs;

		$error = false;

		// At some point we'll likely add another action for payments
		if ( ! isset( $args ) || count( $args ) == 0 ) {
			$error = __( 'No action specified, did you mean', 'commercestore' );
		} elseif ( isset( $args ) && ! in_array( 'create', $args ) ) {
			$error = __( 'Invalid action specified, did you mean', 'commercestore' );
		}

		if ( $error ) {
			$query = '';
			foreach ( $assoc_args as $key => $value ) {
				$query .= ' --' . $key . '=' . $value;
			}

			WP_CLI::error(
				sprintf( $error . ' %s?', 'wp cs download_logs create' . $query )
			);

			return;
		}

		// Setup some defaults
		$number = 1;

		if ( count( $assoc_args ) > 0 ) {
			$number = ( array_key_exists( 'number', $assoc_args ) ) ? absint( $assoc_args['number'] ) : $number;
		}


		// First we need to find all downloads that have files associated.
		$download_ids_with_file_meta = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = 'cs_download_files'" );
		$download_ids_with_files     = array();
		foreach ( $download_ids_with_file_meta as $meta_item ) {
			if ( empty( $meta_item->meta_value ) ) {
				continue;
			}
			$files = maybe_unserialize( $meta_item->meta_value );

			// We have an empty array;
			if ( empty( $files ) ) {
				continue;
			}

			$download_ids_with_files[ $meta_item->post_id ] = array_keys( $files );
		}

		global $wpdb;
		$product_ids = implode('","', array_keys( $download_ids_with_files ) );
		$table       = $wpdb->prefix . 'cs_order_items';
		$sql         = 'SELECT order_id, product_id, price_id, uuid FROM ' . $table . ' WHERE product_id IN ( "' . $product_ids . '")';
		$results     = $wpdb->get_results( $sql );

		// Now generate some download logs for the files.
		$progress = \WP_CLI\Utils\make_progress_bar( 'Creating File Download Logs', $number );
		$i        = 1;
		while ( $i <= $number ) {
			$found_item = array_rand( $results, 1 );
			$item       = $results[ $found_item ];

			$order_id    = (int) $item->order_id;
			$order       = cs_get_order( $order_id );
			$product_id  = (int) $item->product_id;

			if ( cs_has_variable_prices( $product_id ) ) {
				$price_id = (int) $item->price_id;
			} else {
				$price_id = false;
			}

			$customer = new CS_Customer( $order->customer_id );

			$user_info = array(
				'email' => $order->email,
				'id'    => $order->user_id,
				'name'  => $order->name,
			);

			if ( empty( $download_ids_with_files[ $product_id ] ) ) {
				continue;
			}

			$file_id_key = array_rand( $download_ids_with_files[ $product_id ], 1 );
			$file_key    = $download_ids_with_files[ $product_id ][ $file_id_key ];
			cs_add_file_download_log( array(
				'product_id'   => absint( $product_id ),
				'file_id'      => absint( $file_key ),
				'order_id'     => absint( $order_id ),
				'price_id'     => absint( $price_id ),
				'customer_id'  => $order->customer_id,
				'ip'           => cs_get_ip(),
				'user_agent'   => 'CS; WPCLI; download_logs;',
				'date_created' => $order->date_completed,
			) );

			$progress->tick();
			$i ++;
		}
		$progress->finish();
	}

	protected function get_fname() {
		$names = array(
			'Ilse',
			'Emelda',
			'Aurelio',
			'Chiquita',
			'Cheryl',
			'Norbert',
			'Neville',
			'Wendie',
			'Clint',
			'Synthia',
			'Tobi',
			'Nakita',
			'Marisa',
			'Maybelle',
			'Onie',
			'Donnette',
			'Henry',
			'Sheryll',
			'Leighann',
			'Wilson',
		);

		return $names[ rand( 0, ( count( $names ) - 1 ) ) ];
	}

	protected function get_lname() {
		$names = array(
			'Warner',
			'Roush',
			'Lenahan',
			'Theiss',
			'Sack',
			'Troutt',
			'Vanderburg',
			'Lisi',
			'Lemons',
			'Christon',
			'Kogut',
			'Broad',
			'Wernick',
			'Horstmann',
			'Schoenfeld',
			'Dolloff',
			'Murph',
			'Shipp',
			'Hursey',
			'Jacobi',
		);

		return $names[ rand( 0, ( count( $names ) - 1 ) ) ];
	}

	protected function get_domain() {
		$domains = array(
			'example',
			'cs',
			'rcp',
			'affwp',
		);

		return $domains[ rand( 0, ( count( $domains ) - 1 ) ) ];
	}

	protected function get_tld() {
		$tlds = array(
			'local',
			'test',
			'example',
			'localhost',
			'invalid',
		);

		return $tlds[ rand( 0, ( count( $tlds ) - 1 ) ) ];
	}
}
