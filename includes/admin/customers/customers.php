<?php
/**
 * Customers - Admin Functions.
 *
 * @package     CS
 * @subpackage  Admin/Customers
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.3
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/** Navigation ****************************************************************/

/**
 * Output the primary customers page navigation
 *
 * @since 3.0
 * @param string $active_tab
 */
function cs_customers_page_primary_nav( $active_tab = '' ) {

	$add_new_url = add_query_arg( array( 'view' => 'add-customer' ), cs_get_admin_url( array( 'page' => 'cs-customers' ) ) );

	ob_start();?>

	<nav class="nav-tab-wrapper cs-nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Secondary menu', 'commercestore' ); ?>">
		<?php

		// Get the pages
		$tabs = cs_get_customer_pages();

		// Loop through pages and create tabs
		foreach ( $tabs as $tab_id => $tab_name ) {

			// Remove
			$tab_url = cs_get_admin_url(
				array(
					'page'      => 'cs-customers',
					'page_type' => urlencode( $tab_id ),
				)
			);

			$class = 'nav-tab';
			if ( $active_tab === $tab_id ) {
				$class .= ' nav-tab-active';
			}

			// Link
			echo '<a href="' . esc_url( $tab_url ) . '" class="' . esc_attr( $class ) . '">'; // WPCS: XSS ok.
				echo esc_html( $tab_name );
			echo '</a>';
		}
		?>
		<!--<a href="<?php echo esc_url( $add_new_url ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'commercestore' ); ?></a>-->
	</nav>

	<?php

	echo ob_get_clean(); // WPCS: XSS ok.
}

/**
 * Retrieve the customer pages.
 *
 * Used only by the primary tab navigation for customers.
 *
 * @since 3.0
 *
 * @return array
 */
function cs_get_customer_pages() {
	static $pages = null;

	// Filter
	if ( null === $pages ) {
		$pages = (array) apply_filters( 'cs_get_customer_pages', array(
			'customers' => esc_html__( 'Customers',          'commercestore' ),
			'emails'    => esc_html__( 'Email Addresses',    'commercestore' ),
			'physical'  => esc_html__( 'Physical Addresses', 'commercestore' )
		) );
	}

	// Return
	return $pages;
}

/**
 * Display customer sections
 *
 * Contains backwards compat code to shim tabs & views to CS_Sections()
 *
 * @since 3.0
 *
 * @param object $customer
 */
function cs_customers_sections( $customer ) {

	// Instantiate the Sections class and sections array
	$sections   = new CS\Admin\Sections();
	$c_sections = array();

	// Setup sections variables
	$sections->item            = $customer;
	$sections->use_js          = true;
	$sections->current_section = ! empty( $_GET['view'] )
		? sanitize_key( $_GET['view'] )
		: 'overview';
	$sections->base_url = cs_get_admin_url( array(
		'page' => 'cs-customers',
		'id'   => $customer->id
	) );

	// Get all registered tabs & views
	$tabs  = cs_customer_tabs();
	$views = cs_customer_views();

	// Loop through tabs & setup sections
	if ( ! empty( $tabs ) ) {
		foreach ( $tabs as $id => $tab ) {

			// Bail if no view
			if ( ! isset( $views[ $id ] ) ) {
				continue;
			}

			// Add to sections array
			$c_sections[] = array(
				'id'       => $id,
				'label'    => $tab['title'],
				'icon'     => str_replace( 'dashicons-', '', $tab['dashicon'] ),
				'callback' => $views[ $id ]
			);
		}
	}

	// Set the customer sections
	$sections->set_sections( $c_sections );

	// Display the sections
	$sections->display();
}

/**
 * Customers Page
 *
 * Renders the customers page contents.
 *
 * @since  2.3
 * @return void
 */
function cs_customers_page() {
	// Enqueue scripts.
	wp_enqueue_script( 'cs-admin-customers' );
	wp_enqueue_script( 'cs-admin-notes' );

	// Views
	$default_views  = cs_customer_views();
	$requested_view = isset( $_GET['view'] )
		? sanitize_key( $_GET['view'] )
		: 'customers';

	// Tabs
	$active_tab = ! empty( $_GET['page_type'] )
		? sanitize_key( $_GET['page_type'] )
		: 'customers';

	// Single customer view
	if ( array_key_exists( $requested_view, $default_views ) && is_callable( $default_views[ $requested_view ] ) ) {
		cs_render_customer_view( $requested_view, $default_views );

	// List table view
	} else {
		cs_customers_list( $active_tab );
	}
}

/**
 * Register the views for customer management
 *
 * @since  2.3
 * @return array Array of views and their callbacks
 */
function cs_customer_views() {
	return apply_filters( 'cs_customer_views', array() );
}

/**
 * Register the tabs for customer management
 *
 * @since  2.3
 * @return array Array of tabs for the customer
 */
function cs_customer_tabs() {
	return apply_filters( 'cs_customer_tabs', array() );
}

/**
 * List table of customers
 *
 * @since  2.3
 * @return void
 */
function cs_customers_list( $active_tab = 'customers' ) {

	// Get the possible pages
	$pages = cs_get_customer_pages();

	// Reset page if not a registered page
	if ( ! in_array( $active_tab, array_keys( $pages ), true ) ) {
		$active_tab = 'customers';
	}

	// Get the label/name from the active tab
	$name = $pages[ $active_tab ];

	// Get the action url from the active tab
	$action_url = cs_get_admin_url( array(
		'page_type' => $active_tab,
		'page'      => 'cs-' . $active_tab
	) );

	// Setup the list table class
	switch ( $active_tab ) {
		case 'customers' :
			include_once dirname( __FILE__ ) . '/class-customer-table.php';
			$list_table_class = 'CS_Customer_Reports_Table';
			break;
		case 'emails' :
			include_once dirname( __FILE__ ) . '/class-customer-email-addresses-table.php';
			$list_table_class = 'CS_Customer_Email_Addresses_Table';
			break;
		case 'physical' :
			include_once dirname( __FILE__ ) . '/class-customer-addresses-table.php';
			$list_table_class = 'CS_Customer_Addresses_Table';
			break;
	}

	// Initialize the list table
	$customers_table = new $list_table_class;
	$customers_table->prepare_items(); ?>

	<div class="wrap">
		<h1 class="wp-heading-inline"><?php echo esc_html( $name ); ?></h1>
		<hr class="wp-header-end">

		<?php cs_customers_page_primary_nav( $active_tab ); ?>
		<br>

		<?php do_action( 'cs_' . $active_tab. '_table_top' ); ?>

		<form id="cs-customers-filter" method="get" action="<?php echo esc_url( $action_url ); ?>">
			<?php
			$customers_table->views();
			$customers_table->search_box( sprintf( __( 'Search %s', 'commercestore' ), $name ), 'cs-customers' );
			$customers_table->display();
			?>
			<input type="hidden" name="post_type" value="download" />
			<input type="hidden" name="page" value="cs-customers" />
			<input type="hidden" name="view" value="customers" />
			<input type="hidden" name="page_type" value="<?php echo esc_attr( $active_tab ); ?>" />
		</form>

		<?php do_action( 'cs_customers_table_bottom' ); ?>

	</div>

	<?php
}

/**
 * Renders the customer view wrapper.
 *
 * @since 2.3
 * @since 3.0 Updated to use CS\Sections class.
 *
 * @param  string $view      The View being requested
 * @param  array $callbacks  The Registered views and their callback functions
 */
function cs_render_customer_view( $view, $callbacks ) {

	$render = true;

	$customer_view_role = apply_filters( 'cs_view_customers_role', 'view_shop_reports' );

	if ( ! current_user_can( $customer_view_role ) ) {
		cs_set_error( 'cs-no-access', __( 'You are not permitted to view this data.', 'commercestore' ) );
		$render = false;
	}

	if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
		cs_set_error( 'cs-invalid_customer', __( 'Invalid Customer ID Provided.', 'commercestore' ) );
		$render = false;
	}

	$customer_id = absint( $_GET['id'] );
	$customer    = cs_get_customer( $customer_id );

	if ( empty( $customer->id ) ) {
		cs_set_error( 'cs-invalid_customer', __( 'Invalid Customer ID Provided.', 'commercestore' ) );
		$render = false;
	} ?>

	<div class='wrap'>
		<h1>
			<?php esc_html_e( 'Customer Details', 'commercestore' ); ?>
			<?php do_action( 'cs_after_customer_details_header', $customer ); ?>
		</h1>

		<hr class="wp-header-end">

		<?php if ( cs_get_errors() ) : ?>

			<div class="error settings-error"><?php cs_print_errors(); ?></div>

		<?php endif;

		if ( $customer && $render ) : ?>

			<div id="cs-item-wrapper" class="cs-item-has-tabs cs-clearfix">
				<?php cs_customers_sections( $customer ); ?>
			</div>

		<?php endif; ?>

	</div>
	<?php

}

/**
 * View a customer profile
 *
 * @since 2.3
 * @since 3.0 Updated to use new query methods.
 *
 * @param \CS_Customer $customer Customer object.
 */
function cs_customers_view( $customer = null ) {
	$customer_edit_role = cs_get_edit_customers_role();

	$agreement_timestamps = $customer->get_meta( 'agree_to_terms_time', false );
	$show_terms           = cs_get_option( 'show_agree_to_terms' );
	$privacy_timestamps   = $customer->get_meta( 'agree_to_privacy_time', false );
	$show_privacy         = cs_get_option( 'show_agree_to_privacy_policy' );
	$last_payment_date    = '';

	if ( ( empty( $agreement_timestamps ) && $show_terms ) || ( empty( $privacy_timestamps ) && $show_privacy ) ) {
		$last_payment = cs_get_orders(
			array(
				'customer_id' => $customer->id,
				'orderby'     => 'date',
				'order'       => 'DESC',
				'number'      => 1,
			)
		);
		if ( ! empty( $last_payment ) ) {
			$last_payment      = reset( $last_payment );
			$last_payment_date = strtotime( $last_payment->date_created );
		}
	}

	if ( is_array( $agreement_timestamps ) ) {
		$agreement_timestamp = array_pop( $agreement_timestamps );
	}

	if ( is_array( $privacy_timestamps ) ) {
		$privacy_timestamp = array_pop( $privacy_timestamps );
	}

	$user_id = ( $customer->user_id > 0 )
		? absint( $customer->user_id )
		: '';

	$address_args = array(
		'address'     => '',
		'address2'    => '',
		'city'        => '',
		'region'      => '',
		'postal_code' => '',
		'country'     => '',
	);

	$data_atts = array(
		'key'     => 'user_login',
		'exclude' => $user_id
	);

	$user_args  = array(
		'name'  => 'customerinfo[user_login]',
		'class' => 'cs-user-dropdown',
		'data'  => $data_atts
	);

	// Maybe get user data
	if ( ! empty( $user_id ) ) {
		$userdata = get_userdata( $user_id );

		if ( ! empty( $userdata->user_login ) ) {
			$user_login         = $userdata->user_login;
			$user_args['value'] = $user_login;
		} else {
			$user_login = false;
		}
	}

	// Address
	$address = $customer->get_address();

	if ( ! empty( $address ) ) {
		$address = $address->to_array();
		$address = wp_parse_args( $address, $address_args );

	} else {
		$address = $address_args;
	}

	do_action( 'cs_customer_card_top', $customer );

	// Country
	$selected_country = $address['country'];
	$countries        = cs_get_country_list();

	// State
	$selected_state = cs_get_shop_state();
	$states         = cs_get_shop_states( $selected_country );
	$selected_state = isset( $address['region'] )
		? $address['region']
		: $selected_state;

	// Email addresses
	$all_emails = cs_get_customer_email_addresses( array(
		'customer_id' => $customer->id,
		'orderby'     => 'type', // to put `primary` email first
		'order'       => 'ASC'
	) );

	// Physical addresses
	$addresses = $customer->get_addresses();

	// Orders
	// Orders and refunds.
	$orders = cs_get_orders( array(
		'customer_id' => $customer->id,
		'number'      => 10,
		'type'        => 'sale',
	) );

	$refunds = cs_get_orders( array(
		'customer_id' => $customer->id,
		'number'      => 10,
		'type'        => 'refund',
	) );

	// Downloads
	$downloads = cs_get_users_purchased_products( $customer->email );

	?>

	<div class="info-wrapper customer-section">
		<form id="edit-customer-info" method="post" action="<?php echo admin_url( 'edit.php?post_type=download&page=cs-customers&view=overview&id=' . $customer->id ); ?>">
			<input type="hidden" data-key="id" name="customerinfo[id]" value="<?php echo esc_html( $customer->id ); ?>" />
			<input type="hidden" name="cs_action" value="edit-customer" />
			<?php wp_nonce_field( 'edit-customer', '_wpnonce', false, true ); ?>

			<div class="cs-item-info customer-info">
				<div class="avatar-wrap left" id="customer-avatar">
					<?php echo get_avatar( $customer->email, 150 ); ?><br />
					<?php if ( current_user_can( $customer_edit_role ) ) : ?>
						<span class="info-item editable customer-edit-link">
							<a href="#" class="button-secondary" id="edit-customer"><?php _e( 'Edit Profile', 'commercestore' ); ?></a>
						</span>
						<?php do_action( 'cs_after_customer_edit_link', $customer ); ?>
					<?php endif; ?>

					<span id="customer-edit-actions" class="edit-item">
						<a id="cs-edit-customer-cancel" href="" class="cancel"><?php _e( 'Cancel', 'commercestore' ); ?></a>
						<button id="cs-edit-customer-save" class="button button-secondary"><?php _e( 'Update', 'commercestore' ); ?></button>
					</span>
				</div>

				<div class="customer-id right">
					#<?php echo esc_html( $customer->id ); ?>
				</div>

				<div class="customer-address-wrapper right">
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Customer Address', 'commercestore' ); ?></legend>

						<span class="customer-address info-item editable">
							<span class="info-item" data-key="address"><?php echo esc_html( $address['address'] ); ?></span>
							<span class="info-item" data-key="address2"><?php echo esc_html( $address['address2'] ); ?></span>
							<span class="info-item" data-key="city"><?php echo esc_html( $address['city'] ); ?></span>
							<span class="info-item" data-key="region"><?php echo cs_get_state_name( $address['country'], $address['region'] ); ?></span>
							<span class="info-item" data-key="postal_code"><?php echo esc_html( $address['postal_code'] ); ?></span>
							<span class="info-item" data-key="country"><?php echo cs_get_country_name( $address['country'] ); ?></span>
						</span>

						<span class="customer-address info-item edit-item">
							<input class="info-item" type="text" data-key="address" name="customerinfo[address]" placeholder="<?php _e( 'Address 1', 'commercestore' ); ?>" value="<?php echo esc_attr( $address['address'] ); ?>" />
							<input class="info-item" type="text" data-key="address2" name="customerinfo[address2]" placeholder="<?php _e( 'Address 2', 'commercestore' ); ?>" value="<?php echo esc_attr( $address['address2'] ); ?>" />
							<input class="info-item" type="text" data-key="city"  name="customerinfo[city]"  placeholder="<?php _e( 'City', 'commercestore' ); ?>" value="<?php echo esc_attr( $address['city'] ); ?>" />
							<select data-key="country" name="customerinfo[country]" id="billing_country" class="billing_country cs-select edit-item" data-nonce="<?php echo wp_create_nonce( 'cs-country-field-nonce' ); ?>">
								<?php
								foreach ( $countries as $country_code => $country ) {
									echo '<option value="' . esc_attr( $country_code ) . '"' . selected( $country_code, $selected_country, false ) . '>' . esc_html( $country ) . '</option>';
								}
								?>
							</select>

							<?php

							if ( ! empty( $states ) ) : ?>

								<select data-key="state" name="customerinfo[region]" id="card_state" class="card_state cs-select info-item">
									<?php
									foreach( $states as $state_code => $state ) {
										echo '<option value="' . $state_code . '"' . selected( $state_code, $selected_state, false ) . '>' . esc_html( $state ) . '</option>';
									}
									?>
								</select>

							<?php else : ?>

								<input type="text" size="6" data-key="region" name="customerinfo[region]" id="card_state" class="card_state cs-input info-item" placeholder="<?php _e( 'State / Province', 'commercestore' ); ?>"/>

							<?php endif; ?>

							<input class="info-item" type="text" data-key="postal_code" name="customerinfo[postal_code]" placeholder="<?php _e( 'Postal Code', 'commercestore' ); ?>" value="<?php echo esc_attr( $address['postal_code'] ); ?>" />
						</span>
					</fieldset>
				</div>

				<div class="customer-main-wrapper left">
					<span class="customer-name info-item edit-item">
						<input size="15" data-key="name" name="customerinfo[name]" type="text" value="<?php echo esc_attr( $customer->name ); ?>" placeholder="<?php _e( 'Customer Name', 'commercestore' ); ?>" />
					</span>
					<span class="customer-name info-item editable" data-key="name">
						<?php echo esc_html( $customer->name ); ?>
					</span>

					<span class="customer-email info-item edit-item">
						<input size="20" data-key="email" name="customerinfo[email]" type="text" value="<?php echo esc_attr( $customer->email ); ?>" placeholder="<?php _e( 'Customer Email', 'commercestore' ); ?>" />
					</span>
					<span class="customer-email info-item editable" data-key="email">
						<?php echo esc_html( $customer->email ); ?>
					</span>
					<span class="customer-date-created info-item edit-item">
						<input size="" data-key="date_created" name="customerinfo[date_created]" type="text" value="<?php echo esc_attr( $customer->date_created ); ?>" placeholder="<?php _e( 'Customer Since', 'commercestore' ); ?>" class="cs_datepicker" />
					</span>
					<span class="customer-since info-item editable">
						<?php
						printf(
							/* translators: The date. */
							esc_html__( 'Customer since %s', 'commercestore' ),
							esc_html( cs_date_i18n( $customer->date_created ) )
						);
						?>
					</span>
					<span class="customer-user-id info-item edit-item">
						<?php echo CS()->html->ajax_user_search( $user_args ); ?>
						<input type="hidden" name="customerinfo[user_id]" data-key="user_id" value="<?php echo esc_attr( $user_id ); ?>" />
					</span>
					<span class="customer-user-id info-item editable">
						<?php if ( ! empty( $user_id ) ) : ?>

							<span data-key="user_id">
								<?php if ( empty( $user_login ) ) :
									printf( __( 'User %s missing', 'commercestore' ), '<code>' . $user_id . '</code>');
								endif; ?>
								<a href="<?php echo admin_url( 'user-edit.php?user_id=' . $user_id ); ?>"><?php echo esc_html( $user_login ); ?></a>
							</span>

						<?php else : ?>

							<span data-key="user_id">
								<?php _e( 'Not a registered user', 'commercestore' ); ?>
							</span>

						<?php endif; ?>

						<?php if ( current_user_can( $customer_edit_role ) && intval( $user_id ) > 0 ) : ?>

							<span class="disconnect-user">
								<a id="disconnect-customer" href="#disconnect" class="dashicons dashicons-editor-unlink"></a>
							</span>

						<?php endif; ?>
					</span>
				</div>
			</div>
		</form>
		<div class="cs-clearfix"></div>
	</div>

	<?php do_action( 'cs_customer_before_stats', $customer ); ?>

	<div id="cs-item-stats-wrapper" class="customer-stats-wrapper customer-section">
		<ul>
			<li>
				<a href="<?php echo admin_url( 'edit.php?post_type=download&page=cs-payment-history&customer=' . $customer->id ); ?>">
					<span class="dashicons dashicons-cart"></span>
					<?php
					printf(
						/* translators: the customer's lifetime number of sales */
						_n(
							'%s Completed Sale',
							'%s Completed Sales',
							$customer->purchase_count,
							'commercestore'
						),
						(
							'<span class="cs_purchase_count">' .
							esc_html( number_format_i18n( $customer->purchase_count, 0 ) ) .
							'</span>'
						)
					);
					?>
				</a>
			</li>
			<li>
				<span class="dashicons dashicons-chart-area"></span>
				<?php
				printf(
					/* translators: the customer's lifetime value */
					esc_html__(
						'%s Lifetime Value',
						'commercestore'
					),
					(
						'<span class="cs_purchase_value">' .
						esc_html( cs_currency_filter( cs_format_amount( $customer->purchase_value ) ) ) .
						'</span>'
					)
				);
				?>
			</li>
			<?php do_action( 'cs_customer_stats_list', $customer ); ?>
		</ul>
	</div>

	<?php do_action( 'cs_customer_before_agreements', $customer ); ?>

	<div id="cs-item-agreements-wrapper" class="customer-agreements-wrapper customer-section">
		<h3><?php esc_html_e( 'Agreements', 'commercestore' ); ?></h3>
		<p class="customer-terms-agreement-date info-item">
			<?php
			if ( ! empty( $agreement_timestamp ) ) {
				echo esc_html( cs_date_i18n( $agreement_timestamp, get_option( 'date_format' ) . ' H:i:s' ) . ' ' . cs_get_timezone_abbr() );

				esc_html_e( ' &mdash; Agreed to Terms', 'commercestore' );

				if ( ! empty( $agreement_timestamps ) ) : ?>

					<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<strong><?php esc_html_e( 'Previous Agreement Dates', 'commercestore' ); ?></strong><br /><?php foreach ( $agreement_timestamps as $timestamp ) { echo date_i18n( get_option( 'date_format' ) . ' H:i:s', $timestamp ); } ?>"></span>

				<?php endif;

			} elseif ( ! empty( $last_payment_date && $show_terms ) ) {
				echo esc_html( cs_date_i18n( $last_payment_date, get_option( 'date_format' ) . ' H:i:s' ) . ' ' . cs_get_timezone_abbr() );

				esc_html_e( ' &mdash; Agreed to Terms', 'commercestore' );
				?>

				<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<strong><?php esc_html_e( 'Estimated Privacy Policy Date', 'commercestore' ); ?></strong><br /><?php esc_html_e( 'This customer made a purchase prior to agreement dates being logged, this is the date of their last purchase. If your site was displaying the agreement checkbox at that time, this is our best estimate as to when they last agreed to your terms.', 'commercestore' ); ?>"></span>

				<?php
			} else {
				esc_html_e( 'No terms agreement found.', 'commercestore' );
			}
			?>
		</p>

		<p class="customer-privacy-policy-date info-item">
			<?php
			if ( ! empty( $privacy_timestamp ) ) {
				echo esc_html( cs_date_i18n( $privacy_timestamp, get_option( 'date_format' ) . ' H:i:s' ) . ' ' . cs_get_timezone_abbr() );

				esc_html_e( ' &mdash; Agreed to Privacy Policy', 'commercestore' );

				if ( ! empty( $privacy_timestamps ) ) : ?>

					<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<strong><?php esc_html_e( 'Previous Agreement Dates', 'commercestore' ); ?></strong><br /><?php foreach ( $privacy_timestamps as $timestamp ) { echo esc_html( date_i18n( get_option( 'date_format' ) . ' H:i:s', $timestamp ) ); } ?>"></span>

				<?php endif;

			} elseif ( ! empty( $last_payment_date ) && $show_privacy ) {

				echo esc_html( cs_date_i18n( $last_payment_date, get_option( 'date_format' ) . ' H:i:s' ) . ' ' . cs_get_timezone_abbr() );

				esc_html_e( ' &mdash; Agreed to Privacy Policy', 'commercestore' );
				?>

				<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<strong><?php esc_html_e( 'Estimated Privacy Policy Date', 'commercestore' ); ?></strong><br /><?php esc_html_e( 'This customer made a purchase prior to privacy policy dates being logged, this is the date of their last purchase. If your site was displaying the privacy policy checkbox at that time, this is our best estimate as to when they last agreed to your privacy policy.', 'commercestore' ); ?>"></span>

				<?php
			} else {
				esc_html_e( 'No privacy policy agreement found.', 'commercestore' );
			}
			?>
		</p>
	</div>

	<?php do_action( 'cs_customer_before_tables_wrapper', $customer ); ?>

	<div id="cs-item-tables-wrapper" class="customer-tables-wrapper customer-section">

		<?php do_action( 'cs_customer_before_tables', $customer ); ?>

		<h3><?php esc_html_e( 'Customer Addresses', 'commercestore' ); ?></h3>

		<div class="notice-wrap"></div>

		<table class="wp-list-table widefat striped addresses">
			<thead>
				<tr>
					<th class="column-primary"><?php esc_html_e( 'Address',     'commercestore' ); ?></th>
					<th><?php esc_html_e( 'City',        'commercestore' ); ?></th>
					<th><?php esc_html_e( 'Region',      'commercestore' ); ?></th>
					<th><?php esc_html_e( 'Postal Code', 'commercestore' ); ?></th>
					<th><?php esc_html_e( 'Country',     'commercestore' ); ?></th>
					<th><?php esc_html_e( 'First Used',  'commercestore' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( ! empty( $addresses ) ) :

				foreach ( $addresses as $address ) :
					$delete_url = wp_nonce_url( cs_get_admin_url( array(
							'page'       => 'cs-customers',
							'view'       => 'overview',
							'id'         => urlencode( $address->id ),
							'cs_action' => 'customer-remove-address'
					) ), 'cs-remove-customer-address' );
					?>

					<tr data-id="<?php echo esc_attr( $address->id ); ?>">
						<td data-colname="<?php esc_attr_e( 'Address', 'commercestore' ); ?>">
							<?php
							echo ! empty( $address->address )
								? esc_html( $address->address )
								: '&mdash;';

							echo ! empty( $address->address2 )
								? esc_html( $address->address2 )
								: '';
							?>
						</td>
						<td data-colname="<?php esc_attr_e( 'City', 'commercestore' ); ?>">
							<?php
							echo ! empty( $address->city )
								? esc_html( $address->city )
								: '&mdash;';
							?>
						</td>
						<td data-colname="<?php esc_attr_e( 'Region', 'commercestore' ); ?>">
							<?php
							echo ! empty( $address->region )
								? esc_html( cs_get_state_name( $address->country, $address->region ) )
								: '&mdash;';
							?>
						</td>
						<td data-colname="<?php esc_attr_e( 'Postal Code', 'commercestore' ); ?>">
							<?php
							echo ! empty( $address->postal_code )
								? esc_html( $address->postal_code )
								: '&mdash;';
							?>
						</td>
						<td data-colname="<?php esc_attr_e( 'Country', 'commercestore' ); ?>">
							<?php
							echo ! empty( $address->country )
								? esc_html( cs_get_country_name( $address->country ) )
								: '&mdash;';
							?>
						</td>
						<td class="has-row-actions" data-colname="<?php esc_attr_e( 'First Used', 'commercestore' ); ?>">
							<time datetime="<?php echo esc_attr( CS()->utils->date( $address->date_created, null, true )->toDateTimeString() ); ?>"><?php echo cs_date_i18n( CS()->utils->date( $address->date_created, null, true )->toDateTimeString(), 'M. d, Y' ) . '<br>' . cs_date_i18n( strtotime( $address->date_created ), 'H:i' ) . ' ' . cs_get_timezone_abbr(); ?></time>
							<?php if ( ! empty( $address->is_primary ) ) : ?>
								<span class="cs-chip"><?php esc_html_e( 'Primary', 'commercestore' ); ?></span>
							<?php endif; ?>
							<div class="row-actions">
								<span class="delete"><a href="<?php echo esc_url( $delete_url ); ?>"><?php esc_html_e( 'Delete', 'commercestore' ); ?></a></span>
							</div>
						</td>
					</tr>

				<?php endforeach; ?>

			<?php else : ?>

				<tr>
					<td class="no-items" colspan="6"><?php esc_html_e( 'No addresses found.', 'commercestore' ); ?></td>
				</tr>

			<?php endif; ?>
			</tbody>
		</table>

		<h3>
			<?php esc_html_e( 'Customer Emails', 'commercestore' ); ?>
			<span alt="f223" class="cs-help-tip dashicons dashicons-editor-help" title="<?php esc_html_e( 'This customer can use any of the emails listed here when making new purchases.', 'commercestore' ); ?>"></span>
		</h3>

		<div class="notice-wrap"></div>

		<table class="wp-list-table widefat striped emails">
			<thead>
				<tr>
					<th class="column-primary"><?php esc_html_e( 'Email', 'commercestore' ); ?></th>
					<th><?php esc_html_e( 'Date Added', 'commercestore' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php if ( ! empty( $all_emails ) ) :

				foreach ( $all_emails as $key => $email ) : ?>

					<tr data-key="<?php echo esc_attr( $key ); ?>">
						<td class="column-primary">
							<span><?php echo esc_html( $email->email ); ?></span>

							<?php if ( 'primary' === $email->type ) : ?>
								<span class="cs-chip"><?php esc_html_e( 'Primary', 'commercestore' ); ?></span>
							<?php else : ?>
								<div class="row-actions">
									<?php
									$base_url     = cs_get_admin_url(
										array(
											'page' => 'cs-customers',
											'view' => 'overview',
											'id'   => urlencode( $customer->id ),
										)
									);
									$actions      = array(
										'promote' => array(
											'url'   => wp_nonce_url( add_query_arg( array( 'email' => rawurlencode( $email->email ), 'cs_action' => 'customer-primary-email' ), $base_url ), 'cs-set-customer-primary-email' ),
											'label' => __( 'Make Primary', 'commercestore' ),
										),
										'delete'  => array(
											'url'   => wp_nonce_url( add_query_arg( array( 'email' => rawurlencode( $email->email ), 'cs_action' => 'customer-remove-email'  ), $base_url ), 'cs-remove-customer-email' ),
											'label' => __( 'Delete', 'commercestore' ),
										),
									);
									$action_links = array();
									foreach ( $actions as $action => $args ) {
										$action_links[] = sprintf(
											'<span class="%s"><a href="%s">%s</a></span>',
											esc_attr( $action ),
											esc_url( $args['url'] ),
											esc_html( $args['label'] )
										);
									}
									echo wp_kses( implode( ' | ', $action_links ), cs_get_allowed_tags() );
									?>
								</div>
							<?php endif; ?>
						</td>
						<td class="column-type">
							<time datetime="<?php echo esc_attr( CS()->utils->date( $email->date_created, null, true )->toDateTimeString() ); ?>"><?php echo cs_date_i18n( CS()->utils->date( $email->date_created, null, true )->toDateTimeString(), 'M. d, Y' ) . '<br>' . cs_date_i18n( strtotime( $email->date_created ), 'H:i' ) . ' ' . cs_get_timezone_abbr(); ?></time>
						</td>
					</tr>

				<?php endforeach; ?>

				<tr class="add-customer-email-row">
					<td colspan="2">
						<div class="add-customer-email-wrapper">
							<input type="hidden" name="customer-id" value="<?php echo esc_attr( $customer->id ); ?>" />
							<?php wp_nonce_field( 'cs-add-customer-email', 'add_email_nonce', false, true ); ?>
							<div class="cs-form-group">
								<label class="cs-form-group__label screen-reader-text" for="cs-additional-email"><?php esc_html_e( 'Email Address', 'commercestore' ); ?></label>
								<div class="cs-form-group__control">
									<input type="email" name="additional-email" id="cs-additional-email" class="cs-form-group__input regular-text" value="" placeholder="<?php esc_attr_e( 'Email Address', 'commercestore' ); ?>" />
								</div>
							</div>
							<div class="cs-form-group cs-make-email-primary">
								<div class="cs-form-group__control">
									<input type="checkbox" name="make-additional-primary" value="1" id="make-additional-primary" />
									<label for="make-additional-primary"><?php esc_html_e( 'Make Primary', 'commercestore' ); ?></label>
								</div>
							</div>
							<span class="spinner"></span>
							<button class="button-secondary cs-add-customer-email" id="add-customer-email"><?php esc_html_e( 'Add Email', 'commercestore' ); ?></button>
						</div>
					</td>
				</tr>

			<?php else : ?>

				<tr><td colspan="2"><?php esc_html_e( 'No emails found.', 'commercestore' ); ?></td></tr>

			<?php endif; ?>
			</tbody>
		</table>

		<h3><?php _e( 'Recent Orders', 'commercestore' ); ?></h3>
		<table class="wp-list-table widefat striped customer-payments">
			<thead>
			<tr>
				<th class="column-primary"><?php _e( 'Number', 'commercestore' ); ?></th>
				<th><?php _e( 'Gateway', 'commercestore' ); ?></th>
				<th><?php _e( 'Total', 'commercestore' ); ?></th>
				<th><?php _e( 'Date', 'commercestore' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if ( ! empty( $orders ) ) :
				foreach ( $orders as $order ) :
					$state  = '';

					// State
					if ( 'complete' !== $order->status ) {
						$state = ' &mdash; ' . cs_get_payment_status_label( $order->status );
					}

					// View URL
					$view_url = cs_get_admin_url( array(
						'page' => 'cs-payment-history',
						'view' => 'view-order-details',
						'id'   => $order->id,
					) );

					$link = '<strong><a class="row-title" href="' . esc_url( $view_url ) . '">' . esc_html( $order->get_number() ) . '</a>' . esc_html( $state ) . '</strong>'; ?>

					<tr>
						<td class="column-primary"><strong><?php echo $link; ?></strong></td>
						<td><?php echo cs_get_gateway_admin_label( $order->gateway ); ?></td>
						<td><?php echo cs_currency_filter( cs_format_amount( $order->total ), $order->currency ); ?></td>
						<td><time datetime="<?php echo esc_attr( CS()->utils->date( $order->date_created, null, true )->toDateTimeString() ); ?>"><?php echo cs_date_i18n( CS()->utils->date( $order->date_created, null, true )->toDateTimeString(), 'M. d, Y' ) . '<br>' . cs_date_i18n( strtotime( $order->date_created ), 'H:i' ) . ' ' . cs_get_timezone_abbr(); ?></time></td>
					</tr>

				<?php endforeach;
			else: ?>
				<tr><td colspan="5" class="no-items"><?php esc_html_e( 'No orders found', 'commercestore' ); ?></td></tr>
			<?php endif; ?>
			</tbody>
		</table>

		<h3><?php _e( 'Recent Refunds', 'commercestore' ); ?></h3>
		<table class="wp-list-table widefat striped customer-payments">
			<thead>
			<tr>
				<th class="column-primary"><?php _e( 'Number', 'commercestore' ); ?></th>
				<th><?php _e( 'Gateway', 'commercestore' ); ?></th>
				<th><?php _e( 'Total', 'commercestore' ); ?></th>
				<th><?php _e( 'Date', 'commercestore' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php if ( ! empty( $refunds ) ) :
				foreach ( $refunds as $refund ) :
					// View URL
					$view_url = cs_get_admin_url( array(
						'page' => 'cs-payment-history',
						'view' => 'view-refund-details',
						'id'   => $refund->id,
					) );

					$link = '<a class="row-title" href="' . esc_url( $view_url ) . '">' . esc_html( $refund->order_number ) . '</a>'; ?>

					<tr>
						<td class="column-primary"><strong><?php echo $link; ?></strong></td>
						<td><?php echo cs_get_gateway_admin_label( $refund->gateway ); ?></td>
						<td><?php echo cs_currency_filter( cs_format_amount( $refund->total ), $refund->currency ); ?></td>
						<td><time datetime="<?php echo esc_attr( CS()->utils->date( $refund->date_created, null, true )->toDateTimeString() ); ?>"><?php echo cs_date_i18n( CS()->utils->date( $refund->date_created, null, true )->toDateTimeString(), 'M. d, Y' ) . '<br>' . cs_date_i18n( CS()->utils->date( $refund->date_created, null, true )->toDateTimeString(), 'H:i' ); ?> <?php echo esc_html( cs_get_timezone_abbr() ); ?></time></td>
					</tr>

				<?php endforeach;
			else: ?>
				<tr><td colspan="5" class="no-items"><?php esc_html_e( 'No refunds found', 'commercestore' ); ?></td></tr>
			<?php endif; ?>
			</tbody>
		</table>

		<h3><?php printf( __( 'Purchased %s', 'commercestore' ), cs_get_label_plural() ); ?></h3>
		<table class="wp-list-table widefat striped customer-downloads">
			<thead>
				<tr>
					<th class="column-primary"><?php echo cs_get_label_singular(); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $downloads ) ) : ?>

					<?php foreach ( $downloads as $download ) : ?>

						<tr>
							<td class="column-primary"><strong><a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' . $download->ID ) ); ?>"><?php echo esc_html( $download->post_title ); ?></a></strong></td>
						</tr>

					<?php endforeach; ?>

				<?php else: ?>

					<tr><td class="no-items"><?php printf( __( 'No %s Found', 'commercestore' ), cs_get_label_plural() ); ?></td></tr>

				<?php endif; ?>
			</tbody>
		</table>

		<?php do_action( 'cs_customer_after_tables', $customer ); ?>

	</div>

	<?php do_action( 'cs_customer_card_bottom', $customer ); ?>

	<?php
}

/**
 * View the notes section of a customer.
 *
 * @since 2.3
 *
 * @param $customer CS_Customer Customer profile being viewed.
 */
function cs_customer_notes_view( $customer ) {
	$paged = ! empty( $_GET['paged'] ) && is_numeric( $_GET['paged'] )
		? absint( $_GET['paged'] )
		: 1;

	$per_page   = apply_filters( 'cs_customer_notes_per_page', 20 );
	$notes      = $customer->get_notes( $per_page, $paged );
	$note_count = $customer->get_notes_count();
	$args       = array(
		'total'        => $note_count,
		'add_fragment' => '#cs_general_notes'
	); ?>

	<div id="cs-item-notes-wrapper">
		<div class="cs-item-header-small">
			<?php echo get_avatar( $customer->email, 30 ); ?> <span><?php echo esc_html( $customer->name ); ?></span>
		</div>
		<h3><?php esc_html_e( 'Notes', 'commercestore' ); ?></h3>

		<?php echo cs_admin_get_notes_pagination( $args ); ?>

		<div id="cs-customer-notes">
			<?php echo cs_admin_get_notes_html( $notes ); ?>
			<?php echo cs_admin_get_new_note_form( $customer->id, 'customer' ); ?>
		</div>

		<?php echo cs_admin_get_notes_pagination( $args ); ?>
	</div>

	<?php
}

/**
 * View the delete section of a customer
 *
 * @since  2.3
 * @param  $customer The Customer being displayed
 * @return void
 */
function cs_customers_delete_view( $customer ) {

	do_action( 'cs_customer_delete_top', $customer ); ?>

	<div class="info-wrapper customer-section">

		<form id="delete-customer" method="post" action="<?php echo admin_url( 'edit.php?post_type=download&page=cs-customers&view=delete&id=' . $customer->id ); ?>">

			<div class="cs-item-header-small">
				<?php echo get_avatar( $customer->email, 30 ); ?> <span><?php echo $customer->name; ?></span>
			</div>

			<h3><?php esc_html_e( 'Delete', 'commercestore' ); ?></h3>

			<div class="delete-customer">
				<span class="delete-customer-options">
					<p>
						<?php echo CS()->html->checkbox( array( 'name' => 'cs-customer-delete-confirm' ) ); ?>
						<label for="cs-customer-delete-confirm"><?php esc_html_e( 'Are you sure you want to delete this customer?', 'commercestore' ); ?></label>
					</p>

					<p>
						<?php echo CS()->html->checkbox( array( 'name' => 'cs-customer-delete-records', 'options' => array( 'disabled' => true ) ) ); ?>
						<label for="cs-customer-delete-records"><?php esc_html_e( 'Delete all associated payments and records?', 'commercestore' ); ?></label>
					</p>

					<?php do_action( 'cs_customer_delete_inputs', $customer ); ?>
				</span>

				<span id="customer-edit-actions">
					<input type="hidden" name="customer_id" value="<?php echo $customer->id; ?>" />
					<?php wp_nonce_field( 'delete-customer', '_wpnonce', false, true ); ?>
					<input type="hidden" name="cs_action" value="delete-customer" />
					<input type="submit" disabled="disabled" id="cs-delete-customer" class="button-primary" value="<?php _e( 'Delete Customer', 'commercestore' ); ?>" />
				</span>
			</div>
		</form>
	</div>

	<?php

	do_action( 'cs_customer_delete_bottom', $customer );
}

/**
 * View the tools section of a customer
 *
 * @since  2.3
 * @param  $customer The Customer being displayed
 * @return void
 */
function cs_customer_tools_view( $customer ) {

	do_action( 'cs_customer_tools_top', $customer ); ?>

	<div id="cs-item-tools-wrapper">
		<div class="cs-item-header-small">
			<?php echo get_avatar( $customer->email, 30 ); ?> <span><?php echo $customer->name; ?></span>
		</div>

		<h3><?php _e( 'Tools', 'commercestore' ); ?></h3>

		<div class="cs-item-info">
			<h4><?php _e( 'Recount Customer Stats', 'commercestore' ); ?></h4>
			<p class="cs-item-description"><?php _e( 'Use this tool to recalculate the purchase count and total value of the customer.', 'commercestore' ); ?></p>
			<form method="post" id="cs-tools-recount-form" class="cs-export-form cs-import-export-form">
				<span>
					<?php wp_nonce_field( 'cs_ajax_export', 'cs_ajax_export' ); ?>

					<input type="hidden" name="cs-export-class" data-type="recount-single-customer-stats" value="CS_Tools_Recount_Single_Customer_Stats" />
					<input type="hidden" name="customer_id" value="<?php echo $customer->id; ?>" />
					<input type="submit" id="recount-stats-submit" value="<?php _e( 'Recount Stats', 'commercestore' ); ?>" class="button-secondary"/>
				</span>
			</form>
		</div>
	</div>

	<?php

	do_action( 'cs_customer_tools_bottom', $customer );
}

/**
 * Display a notice on customer account if they are pending verification
 *
 * @since  2.4.8
 * @return void
 */
function cs_verify_customer_notice( $customer ) {

	if ( ! cs_user_pending_verification( $customer->user_id ) ) {
		return;
	}

	$url = wp_nonce_url( admin_url( 'edit.php?post_type=download&page=cs-customers&view=overview&cs_action=verify_user_admin&id=' . $customer->id ), 'cs-verify-user' );

	echo '<div class="update error"><p>';
	_e( 'This customer\'s user account is pending verification.', 'commercestore' );
	echo ' ';
	echo '<a href="' . $url . '">' . __( 'Verify account.', 'commercestore' ) . '</a>';
	echo "\n\n";

	echo '</p></div>';
}
add_action( 'cs_customer_card_top', 'cs_verify_customer_notice', 10, 1 );
