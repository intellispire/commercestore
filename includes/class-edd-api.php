<?php
/**
 * EDD API
 *
 * This class provides a front-facing JSON/XML API that makes it possible to query data from the shop.
 *
 * The primary purpose of this class is for external sales / earnings tracking systems, such as mobile
 *
 * @package  Easy Digital Downloads
 * @subpackage EDD API
 * @copyright Copyright (c) 2013, Pippin Williamson
 * @license  http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since  1.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * EDD API Class
 *
 * Renders API returns as a JSON array
 *
 * @access  private
 * @since  1.5
 */

class EDD_API {


	/**
	 * Pretty Print?
	 *
	 * @access  private
	 * @since  1.5
	 */

	private $pretty_print = false;


	/**
	 * Setup the API
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function __construct() {

		add_action( 'init', array( $this, 'add_endpoint' ) );
      	add_action( 'template_redirect', array( $this, 'process_endpoint' ), -1 );
		add_filter( 'query_vars', array( $this, 'query_vars' ) );
		add_action( 'show_user_profile', array( $this, 'user_key_field' ) );
		add_action( 'personal_options_update', array( $this, 'update_key' ) );

		// Determine if JSON_PRETTY_PRINT is available
		$this->pretty_print = version_compare( PHP_VERSION, '5.4', '>=' ) ? JSON_PRETTY_PRINT : define( 'JSON_PRETTY_PRINT', '' );
	}


	/**
	 * Register URL endpoint
	 *
	 * Registers a new endpoint for accessing the API
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function add_endpoint( $rewrite_rules ) {
		add_rewrite_endpoint( 'edd-api', EP_ALL );
	}


	/**
	 * Register query vars
	 *
	 * Registers query vars for API access
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function query_vars( $vars ) {
		$vars[] = 'user';
		$vars[] = 'key';
		$vars[] = 'query';
		$vars[] = 'type';
		$vars[] = 'product';
		$vars[] = 'date';
		$vars[] = 'startdate';
		$vars[] = 'enddate';
		$vars[] = 'customer';
		$vars[] = 'format';
		return $vars;
	}


	/**
	 * Process API requests
	 *
	 * Listens for api calls
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function process_endpoint() {
		global $wp_query;

		// Check for edd-api var
		if ( ! isset( $wp_query->query_vars['edd-api'] ) )
			return;

		// Make sure we have both user and api key
		if ( ! isset( $wp_query->query_vars['user'] ) || ! isset( $wp_query->query_vars['key'] ) || $wp_query->query_vars['user'] == '' || $wp_query->query_vars['key'] == '' )
			$this->missing_auth();

		// Make sure username (email) exists
		if ( ! email_exists( $wp_query->query_vars['user'] ) )
			$this->invalid_email();

		// Check email/key combination
		$user = get_user_by( 'email', $wp_query->query_vars['user'] );
		if ( $user->edd_user_api_key != $wp_query->query_vars['key'] )
			$this->invalid_key( $wp_query->query_vars['user'] );

		// Determine the kind of query
		$query_mode = $this->get_query_mode();

		switch( $query_mode ) :

			case 'stats' :

				$type      = $wp_query->query_vars['type'];
				$product   = isset( $wp_query->query_vars['product'] )   ? $wp_query->query_vars['product']   : null;
				$date      = isset( $wp_query->query_vars['date'] )      ? $wp_query->query_vars['date']      : null;
				$startdate = isset( $wp_query->query_vars['startdate'] ) ? $wp_query->query_vars['startdate'] : null;
				$enddate   = isset( $wp_query->query_vars['enddate'] )   ? $wp_query->query_vars['enddate']   : null;

				$this->get_stats( array(
					'type'      => $type,
					'product'   => $product,
					'date'      => $date,
					'startdate' => $startdate,
					'enddate'   => $enddate
				) );

				break;

			case 'products' :

				$product   = isset( $wp_query->query_vars['product'] )   ? $wp_query->query_vars['product']   : null;

				$this->get_products( $product );

				break;

			case 'customers' :

				$customer  = isset( $wp_query->query_vars['customer'] ) ? $wp_query->query_vars['customer']  : null;

				$this->get_customers( $customer );

				break;

		endswitch;

	}


	/**
	 * Retrieve the query mode
	 *
	 * Determines the kind of query requested and also ensure it is a valid query
	 *
	 * @access  private
	 * @since  1.5
	 */

	private function get_query_mode() {

		global $wp_query;

		// Whitelist our query options
		$accepted = apply_filters( 'edd_api_valid_query_modes', array(
			'stats',
			'products',
			'customers',
		) );

		$query = isset( $wp_query->query_vars['query'] ) ? $wp_query->query_vars['query'] : null;

		// Make sure our query is valid
		if( ! in_array( $query, $accepted ) || ( $query == 'stats' && ! isset( $wp_query->query_vars['type'] )  ) ) {

			$error['error'] = __( 'Invalid query!', 'edd' );

			$this->output( $error );

		}

		return $query;
	}


	/**
	 * Missing authentication error
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function missing_auth() {
		$error['error'] = __( 'You must specify both user and API key!', 'edd' );

		$this->output( $error );
	}


	/**
	 * Invalid email address error
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function invalid_email() {
		$error['error'] = __( 'The email address specified is not registered!', 'edd' );

		$this->output( $error );
	}

	/**
	 * Invalid key error
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function invalid_key( $email ) {
		$error['error'] = sprintf( __( 'Invalid API key for %s!', 'edd' ), $email );

		$this->output( $error );
	}


	/**
	 * Get customers
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function get_customers( $customer ) {
		if ( $customer == null ) {

			global $wpdb;

			$customer_list_query = $wpdb->get_col( "SELECT $wpdb->users.ID FROM $wpdb->users" );
			$customer_count = 0;

			foreach ( $customer_list_query as $customer_id ) {

				if ( edd_has_purchases( $customer_id ) ) {

					$customer_info = get_userdata( $customer_id );

					$customers['customers'][$customer_id]['info']['id'] = $customer_info->ID;
					$customers['customers'][$customer_id]['info']['username'] = $customer_info->user_login;
					$customers['customers'][$customer_id]['info']['display_name'] = $customer_info->display_name;
					$customers['customers'][$customer_id]['info']['first_name'] = $customer_info->user_firstname;
					$customers['customers'][$customer_id]['info']['last_name'] = $customer_info->user_lastname;
					$customers['customers'][$customer_id]['info']['email'] = $customer_info->user_email;
					$customers['customers'][$customer_id]['info']['url'] = $customer_info->user_url;
					$customers['customers'][$customer_id]['info']['registered'] = $customer_info->user_registered;

					$customers['customers'][$customer_id]['stats']['total_purchases'] = edd_count_purchases_of_customer( $customer_id );
					$customers['customers'][$customer_id]['stats']['total_spent'] = edd_purchase_total_of_user( $customer_id );
					$customers['customers'][$customer_id]['stats']['total_downloads'] = edd_count_file_downloads_of_user( $customer_id );

					$customer_count++;
				}

			}

			$customers['customers']['stats']['total_customers'] = $customer_count;

		} else {

			if ( !is_numeric( $customer ) ) {

				$customer = get_user_by( 'email', $customer )->ID;

			}

			if ( edd_has_purchases( $customer ) ) {

				$customer_info = get_userdata( $customer );

				$customers[$customer]['info']['id'] = $customer_info->ID;
				$customers[$customer]['info']['username'] = $customer_info->user_login;
				$customers[$customer]['info']['display_name'] = $customer_info->display_name;
				$customers[$customer]['info']['first_name'] = $customer_info->user_firstname;
				$customers[$customer]['info']['last_name'] = $customer_info->user_lastname;
				$customers[$customer]['info']['email'] = $customer_info->user_email;
				$customers[$customer]['info']['url'] = $customer_info->user_url;
				$customers[$customer]['info']['registered'] = $customer_info->user_registered;

				$customers[$customer]['stats']['total_purchases'] = edd_count_purchases_of_customer( $customer );
				$customers[$customer]['stats']['total_spent'] = edd_purchase_total_of_user( $customer );
				$customers[$customer]['stats']['total_downloads'] = edd_count_file_downloads_of_user( $customer );

			} else {

				$error['error'] = sprintf( __( 'Customer %s not found!', 'edd' ), $customer );

				$this->output( $error );

			}
		}

		$this->output( $customers );
	}


	/**
	 * Get products
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function get_products( $product ) {

		if ( $product == null ) {

			$product_list = get_posts( array( 'post_type' => 'download' ) );

			foreach ( $product_list as $product_info ) {

				$products['products'][$product_info->ID]['info']['id']                           = $product_info->ID;
				$products['products'][$product_info->ID]['info']['slug']                         = $product_info->post_name;
				$products['products'][$product_info->ID]['info']['title']                        = $product_info->post_title;
				$products['products'][$product_info->ID]['info']['create_date']                  = $product_info->post_date;
				$products['products'][$product_info->ID]['info']['modified_date']                = $product_info->post_modified;
				$products['products'][$product_info->ID]['info']['status']                       = $product_info->post_status;
				$products['products'][$product_info->ID]['info']['link']                         = html_entity_decode( $product_info->guid );
				$products['products'][$product_info->ID]['info']['content']                      = $product_info->post_content;
				$products['products'][$product_info->ID]['info']['thumbnail']                    = wp_get_attachment_url( get_post_thumbnail_id( $product_info->ID ) );

				$products['products'][$product_info->ID]['stats']['total']['sales']              = edd_get_download_sales_stats( $product_info->ID );
				$products['products'][$product_info->ID]['stats']['total']['earnings']           = edd_get_download_earnings_stats( $product_info->ID );
				$products['products'][$product_info->ID]['stats']['monthly_average']['sales']    = edd_get_average_monthly_download_sales( $product_info->ID );
				$products['products'][$product_info->ID]['stats']['monthly_average']['earnings'] = edd_get_average_monthly_download_earnings( $product_info->ID );

				if ( edd_has_variable_prices( $product_info->ID ) ) {

					foreach ( edd_get_variable_prices( $product_info->ID ) as $price ) {

						$products['products'][$product_info->ID]['pricing'][$price['name']] = $price['amount'];

					}

				} else {

					$products['products'][$product_info->ID]['pricing']['amount'] = edd_get_download_price( $product_info->ID );

				}

				foreach ( edd_get_download_files( $product_info->ID ) as $file ) {

					$products['products'][$product_info->ID]['files'][] = $file;

				}

				$products['products'][$product_info->ID]['notes'] = edd_get_product_notes( $product_info->ID );

			}

		} else {

			if ( get_post_type( $product ) == 'download' ) {

				$product_info = get_post( $product );

				$products[$product_info->ID]['info']['id']                           = $product_info->ID;
				$products[$product_info->ID]['info']['slug']                         = $product_info->post_name;
				$products[$product_info->ID]['info']['title']                        = $product_info->post_title;
				$products[$product_info->ID]['info']['create_date']                  = $product_info->post_date;
				$products[$product_info->ID]['info']['modified_date']                = $product_info->post_modified;
				$products[$product_info->ID]['info']['status']                       = $product_info->post_status;
				$products[$product_info->ID]['info']['link']                         = html_entity_decode( $product_info->guid );
				$products[$product_info->ID]['info']['content']                      = $product_info->post_content;
				$products[$product_info->ID]['info']['thumbnail']                    = wp_get_attachment_url( get_post_thumbnail_id( $product_info->ID ) );

				$products[$product_info->ID]['stats']['total']['sales']              = edd_get_download_sales_stats( $product_info->ID );
				$products[$product_info->ID]['stats']['total']['earnings']           = edd_get_download_earnings_stats( $product_info->ID );
				$products[$product_info->ID]['stats']['monthly_average']['sales']    = edd_get_average_monthly_download_sales( $product_info->ID );
				$products[$product_info->ID]['stats']['monthly_average']['earnings'] = edd_get_average_monthly_download_earnings( $product_info->ID );

				if ( edd_has_variable_prices( $product_info->ID ) ) {

					foreach ( edd_get_variable_prices( $product_info->ID ) as $price ) {

						$products[$product_info->ID]['pricing'][$price['name']] = $price['amount'];

					}

				} else {

					$products[$product_info->ID]['pricing']['amount'] = edd_get_download_price( $product_info->ID );

				}

				foreach ( edd_get_download_files( $product_info->ID ) as $file ) {

					$products[$product_info->ID]['files'][] = $file;

				}

				$products[$product_info->ID]['notes'] = edd_get_product_notes( $product_info->ID );

			} else {

				$error['error'] = sprintf( __( 'Product %s not found!', 'edd' ), $product );

				$this->output( $error );

			}
		}

		$this->output( $products );
	}


	/**
	 * Get stats
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function get_stats( $args = array() ) {

		$defaults = array(
			'type'      => '',
			'product'   => null,
			'date'      => null,
			'startdate' => null,
			'enddate'   => null
		);

		$args = wp_parse_args( $args, $defaults );

		$previous_month = date( 'n' ) == 1 ? 12 : date( 'n' ) - 1;
		$previous_year = $previous_month == 12 ? date( 'Y' ) - 1 : date( 'Y' );

		if ( date( 'j' ) == 1 ) {

			if ( date( 'n' ) == 3 ) {

				$yesterday = 28;

			} elseif ( date( 'n' ) == 5 || date( 'n' ) == 6 || date( 'n' ) == 10 || date( 'n' ) == 12 ) {

				$yesterday = 30;

			} else {

				$yesterday = 31;

			}

		} else {

			$yesterday = date( 'j' ) - 1;

		}

		if ( $args['type'] == 'sales' ) {

			if ( $args['product'] == null ) {

				if ( $args['date'] == null ) {

					$sales['sales']['current_month'] = edd_get_sales_by_date( null, date( 'n' ), date( 'Y' ) );
					$sales['sales']['last_month'] = edd_get_sales_by_date( null, $previous_month, $previous_year );
					$sales['sales']['totals'] = edd_get_total_sales();

				} elseif ( $args['date'] == 'today' ) {

					$sales['sales']['today'] = edd_get_sales_by_date( date( 'j' ), date( 'n' ), date( 'Y' ) );

				} elseif ( $args['date'] == 'yesterday' ) {

					$sales['sales']['yesterday'] = edd_get_sales_by_date( $yesterday, date( 'n' ), date( 'Y' ) );

				} elseif ( $args['date'] == 'range' ) {

					if ( isset( $args['startdate'] ) && isset( $args['enddate'] ) ) {

						global $wp_query;

						$args['startdate'] = DateTime::createFromFormat( 'Ymd', $args['startdate'] )->format( 'Y-m-d' );
						$args['enddate'] = DateTime::createFromFormat( 'Ymd', $args['enddate'] )->format( 'Y-m-d' );
						$daterange = new DatePeriod(
							new DateTime( $args['startdate'] ),
							new DateInterval( 'P1D' ),
							new DateTime( $args['enddate'] + 1 )
						);

						foreach ( $daterange as $day ) {

							$tag = ( $wp_query->query_vars['format'] == 'xml' ? $day->format( 'MdY' ) : $day->format( 'Ymd' ) );
							$sales['sales'][$tag] = edd_get_sales_by_date( $day->format( 'j' ), $day->format( 'n' ), $day->format( 'Y' ) );

						}

					} else {

						$error['error'] = __( 'Invalid or no date range specified!', 'edd' );
						$this->output( $error );

					}
				} else {

					$error['error'] = __( 'Invalid option for argument \'date\'!', 'edd' );
					$this->output( $error );

				}

				$this->output( $sales );

			} elseif ( $args['product'] == 'all' ) {

				$products = get_posts( array( 'post_type' => 'download' ) );
				foreach ( $products as $product_info ) {
					$sales['sales'][$product_info->ID] = array( $product_info->post_name => edd_get_download_sales_stats( $product_info->ID ) );
				}

				$this->output( $sales );

			} else {

				if ( get_post_type( $args['product'] ) == 'download' ) {

					$product_info = get_post( $args['product'] );
					$sales['sales'][$product_info->ID] = array( $product_info->post_name => edd_get_download_sales_stats( $args['product'] ) );

					$this->output( $sales );

				} else {

					$error['error'] = sprintf( __( 'Product %s not found!', 'edd' ), $args['product'] );

					$this->output( $error );

				}

			}

		} elseif ( $args['type'] == 'earnings' ) {

			if ( $args['product'] == null ) {

				if ( $args['date'] == null ) {

					$earnings['earnings']['current_month'] = edd_get_earnings_by_date( null, date( 'n' ), date( 'Y' ) );
					$earnings['earnings']['last_month'] = edd_get_earnings_by_date( null, $previous_month, $previous_year );
					$earnings['earnings']['totals'] = edd_get_total_earnings();

				} elseif ( $args['date'] == 'today' ) {

					$earnings['earnings']['today'] = edd_get_earnings_by_date( date( 'j' ), date( 'n' ), date( 'Y' ) );

				} elseif ( $args['date'] == 'yesterday' ) {

					$earnings['earnings']['yesterday'] = edd_get_earnings_by_date( $yesterday, date( 'n' ), date( 'Y' ) );

				} elseif ( $args['date'] == 'range' ) {

					if ( isset( $args['startdate'] ) && isset( $args['enddate'] ) ) {

						global $wp_query;

						$args['startdate'] = DateTime::createFromFormat( 'Ymd', $args['startdate'] )->format( 'Y-m-d' );
						$args['enddate'] = DateTime::createFromFormat( 'Ymd', $args['enddate'] )->format( 'Y-m-d' );

						$daterange = new DatePeriod(
							new DateTime( $args['startdate'] ),
							new DateInterval( 'P1D' ),
							new DateTime( $args['enddate'] + 1 )
						);

						foreach ( $daterange as $day ) {

							$tag = ( $wp_query->query_vars['format'] == 'xml' ? $day->format( 'MdY' ) : $day->format( 'Ymd' ) );
							$earnings['earnings'][$tag] = edd_get_earnings_by_date( $day->format( 'j' ), $day->format( 'n' ), $day->format( 'Y' ) );

						}

					} else {

						$error['error'] = __( 'Invalid or no date range specified!', 'edd' );

						$this->output( $error );

					}

				} else {

					$error['error'] = __( 'Invalid option for argument \'date\'!', 'edd' );

					$this->output( $error );

				}

				$this->output( $earnings );

			} elseif ( $args['product'] == 'all' ) {

				$products = get_posts( array( 'post_type' => 'download' ) );

				foreach ( $products as $product_info ) {

					$earnings['earnings'][$product_info->ID] = array( $product_info->post_name => edd_get_download_earnings_stats( $product_info->ID ) );

				}

				$this->output( $earnings );

			} else {

				if ( get_post_type( $args['product'] ) == 'download' ) {

					$product_info = get_post( $args['product'] );
					$earnings['earnings'][$product_info->ID] = array( $product_info->post_name => edd_get_download_earnings_stats( $args['product'] ) );

					$this->output( $earnings );

				} else {

					$error['error'] = sprintf( __( 'Product %s not found!', 'edd' ), $args['product'] );

					$this->output( $error );

				}

			}

		}

	}


	/**
	 * Output query return
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function output( $array ) {
		global $wp_query;

		if ( isset( $wp_query->query_vars['format'] ) && $wp_query->query_vars['format'] == 'xml' ) {

			require_once EDD_PLUGIN_DIR . 'includes/libraries/array2xml.php';

			$xml = Array2XML::createXML( 'edd', $array );
			echo $xml->saveXML();

		} else {

			header( 'Content-Type: application/json' );
			echo json_encode( $array, $this->pretty_print );

		}

		exit;
	}


	/**
	 * Modify user profile
	 *
	 * Modifies the output of profile.php to add key generation/revocation
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function user_key_field( $user ) {
		if ( isset( $edd_options['api_allow_user_keys'] ) || current_user_can( 'manage_shop_settings' ) ) {
			$user = get_userdata( $user->ID );
			?>
			<table class="form-table">
				<tbody>
					<tr>
						<th>
							<label for="edd_set_api_key"><?php _e( 'Easy Digital Downloads API Key', 'edd' ); ?></label>
						</th>
						<td>
							<?php if ( empty( $user->edd_user_api_key ) ) { ?>
							<input name="edd_set_api_key" type="checkbox" id="edd_set_api_key" value="0" />
							<span class="description"><?php _e( 'Generate API Key', 'edd' ); ?></span>
							<?php } else { ?>
								<span id="key"><?php echo $user->edd_user_api_key; ?></span><br/>
								<input name="edd_set_api_key" type="checkbox" id="edd_set_api_key" value="0" />
								<span class="description"><?php _e( 'Revoke API Key', 'edd' ); ?></span>
							<?php } ?>
						</td>
					</tr>
				</tbody>
			</table>
		<?php }
	}


	/**
	 * Generate and save API key
	 *
	 * Generates the key requested by user_key_field and stores it to the database
	 *
	 * @access  private
	 * @author  Daniel J Griffiths
	 * @since  1.5
	 */

	function update_key( $user_id ) {

		if ( current_user_can( 'edit_user', $user_id ) && isset( $_POST['edd_set_api_key'] ) ) {

			$user = get_userdata( $user_id );

			if ( empty( $user->edd_user_api_key ) ) {

				$hash = hash( 'md5', $user->user_email . date( 'U' ) );
				update_user_meta( $user_id, 'edd_user_api_key', $hash );

			} else {

				delete_user_meta( $user_id, 'edd_user_api_key' );

			}

		}

	}

}