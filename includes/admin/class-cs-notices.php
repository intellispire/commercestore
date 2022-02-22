<?php
/**
 * Admin Notices Class
 *
 * @package     CS
 * @subpackage  Admin/Notices
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.3
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * CS_Notices Class
 *
 * @since 2.3
 */
class CS_Notices {

	/**
	 * @var array Array of notices to output to the current user
	 */
	private $notices = array();

	/**
	 * Get things started
	 *
	 * @since 2.3
	 */
	public function __construct() {
		add_action( 'cs_dismiss_notices', array( $this, 'dismiss_notices' )     );
		add_action( 'admin_init',          array( $this, 'add_notices'     ), 20 );
		add_action( 'admin_notices',       array( $this, 'display_notices' ), 30 );
		add_action( 'wp_ajax_cs_disable_debugging', array( $this, 'cs_disable_debugging' ) );
	}

	/**
	 * Add a notice to the notices array
	 *
	 * @since 3.0
	 *
	 * @param string          $id             Unique ID for each message
	 * @param string|WP_Error $message        A message to be displayed or {@link WP_Error}
	 * @param string          $class          Optional. A class to be added to the message div
	 * @param bool            $is_dismissible Optional. True to dismiss, false to persist
	 *
	 * @return void
	 */
	public function add_notice( $args = array() ) {

		// Parse args
		$r = wp_parse_args( $args, array(
			'id'             => '',
			'message'        => '',
			'class'          => false,
			'is_dismissible' => true
		) );

		$default_class ='updated';

		// One message as string
		if ( is_string( $r['message'] ) ) {
			$message       = '<p>' . $this->esc_notice( $r['message'] ) . '</p>';

		} elseif ( is_array( $r['message'] ) ) {
			$message       = '<p>' . implode( '</p><p>', array_map( array( $this, 'esc_notice' ), $r['message'] ) ) . '</p>';

			// Messages as objects
		} elseif ( is_wp_error( $r['message'] ) ) {
			$default_class = 'is-error';
			$errors        = $r['message']->get_error_messages();

			switch ( count( $errors ) ) {
				case 0:
					return false;

				case 1:
					$message = '<p>' . $this->esc_notice( $errors[0] ) . '</p>';
					break;

				default:
					$escaped = array_map( array( $this, 'esc_notice' ), $errors );
					$message = '<ul>' . "\n\t" . '<li>' . implode( '</li>' . "\n\t" . '<li>', $escaped ) . '</li>' . "\n" . '</ul>';
					break;
			}

			// Message is an unknown format, so bail
		} else {
			return false;
		}

		// CSS Classes
		$classes = ! empty( $r['class'] )
			? array( $r['class'] )
			: array( $default_class );

		// Add dismissible class
		if ( ! empty( $r['is_dismissible'] ) ) {
			array_push( $classes, 'is-dismissible' );
		}

		// Assemble the message
		$message = '<div class="notice ' . implode( ' ', array_map( 'sanitize_html_class', $classes ) ) . '">' . $message . '</div>';
		$message = str_replace( "'", "\'", $message );

		// Avoid malformed notices variable
		if ( ! is_array( $this->notices ) ) {
			$this->notices = array();
		}

		// Add notice to notices array
		$this->notices[] = $message;
	}

	/**
	 * Add all admin area notices
	 *
	 * @since 3.0
	 */
	public function add_notices() {

		// User can edit pages
		if ( current_user_can( 'edit_pages' ) ) {
			$this->add_page_notices();
		}

		// User can view shop reports
		if ( current_user_can( 'view_shop_reports' ) ) {
			$this->add_reports_notices();
		}

		// User can manage the entire shop
		if ( current_user_can( 'manage_shop_settings' ) ) {
			$this->add_system_notices();
			$this->add_data_notices();
			$this->add_tax_rate_notice();
			$this->add_settings_notices();
		}

		// Generic notices
		if ( ! empty( $_REQUEST['cs-message'] ) ) {
			$this->add_user_action_notices( $_REQUEST['cs-message'] );
		}
	}

	/**
	 * Dismiss admin notices when dismiss links are clicked
	 *
	 * @since 2.3
	 */
	public function dismiss_notices() {

		// Bail if no notices to dismiss
		if ( empty( $_GET['cs_notice'] ) || empty( $_GET['_wpnonce'] ) ) {
			return;
		}

		// Construct key we are dismissing
		$key = sanitize_key( $_GET['cs_notice'] );

		// Bail if sanitized notice is empty
		if ( empty( $key ) ) {
			return;
		}

		// Bail if nonce does not verify
		if ( ! wp_verify_nonce( $_GET['_wpnonce'], 'cs_notice_nonce' ) ) {
			return;
		}

		// Dismiss notice
		update_user_meta( get_current_user_id(), "_cs_{$key}_dismissed", 1 );
		cs_redirect( remove_query_arg( array( 'cs_action', 'cs_notice', '_wpnonce' ) ) );
		exit;
	}

	/**
	 * Output all admin area notices
	 *
	 * @since 2.6.0 bbPress (r6771)
	 */
	public function display_notices() {

		$this->show_debugging_notice();

		// Bail if no notices
		if ( empty( $this->notices ) || ! is_array( $this->notices ) ) {
			return;
		}

		// Start an output buffer
		ob_start();

		// Loop through notices, and add them to buffer
		foreach ( $this->notices as $notice ) {
			echo $notice;
		}

		// Output the current buffer
		$notices = ob_get_clean();

		// Only echo if not empty
		if ( ! empty( $notices ) ) {
			echo $notices;
		}
	}

	/** Private Methods *******************************************************/

	/**
	 * Notices about missing pages
	 *
	 * @since 3.0
	 */
	private function add_page_notices() {

		// Checkout page is missing
		$purchase_page = cs_get_option( 'purchase_page', '' );
		if ( empty( $purchase_page ) || ( 'trash' === get_post_status( $purchase_page ) ) ) {
			$this->add_notice( array(
				'id'             => 'cs-no-purchase-page',
				'message'        => sprintf( __( 'No checkout page is configured. Set one in <a href="%s">Settings</a>.', 'commercestore' ), admin_url( 'edit.php?post_type=download&page=cs-settings&tab=general&section=pages' ) ),
				'class'          => 'error',
				'is_dismissible' => false
			) );
		}
	}

	/**
	 * Notices about reports
	 *
	 * @since 3.0
	 */
	private function add_reports_notices() {

	}

	/**
	 * Notices for the entire shop
	 *
	 * @since 3.0
	 */
	private function add_system_notices() {

		// Bail if not an CommerceStore admin page
		if ( ! cs_is_admin_page() || cs_is_dev_environment() || cs_is_admin_page( 'index.php' ) ) {
			return;
		}

		// Bail if user cannot manage options
		if ( ! current_user_can( 'manage_shop_settings' ) ) {
			return;
		}

		// Bail if uploads directory is protected
		if ( cs_is_uploads_url_protected() ) {
			return;
		}

		// Get the upload directory
		$upload_directory   = cs_get_upload_dir();

		// Running NGINX
		$show_nginx_notice = apply_filters( 'cs_show_nginx_redirect_notice', true );
		if ( $show_nginx_notice && ! empty( $GLOBALS['is_nginx'] ) && ! get_user_meta( get_current_user_id(), '_cs_nginx_redirect_dismissed', true ) ) {
			$dismiss_notice_url = wp_nonce_url( add_query_arg( array(
				'cs_action' => 'dismiss_notices',
				'cs_notice' => 'nginx_redirect'
			) ), 'cs_notice_nonce' );

			$this->add_notice( array(
				'id'             => 'cs-nginx',
				'class'          => 'error',
				'is_dismissible' => false,
				'message'        => array(
					sprintf( __( 'The files in %s are not currently protected.', 'commercestore' ), '<code>' . $upload_directory . '</code>' ),
					__( 'To protect them, you must add this <a href="http://docs.commercestore.com/article/682-protected-download-files-on-nginx">NGINX redirect rule</a>.', 'commercestore' ),
					sprintf( __( 'If you have already done this, or it does not apply to your site, you may permenently %s.', 'commercestore' ), '<a href="' . esc_url( $dismiss_notice_url ) . '">' . __( 'dismiss this notice', 'commercestore' ) . '</a>' )
				)
			) );
		}

		// Running Apache
		if ( ! empty( $GLOBALS['is_apache'] ) && ! cs_htaccess_exists() && ! get_user_meta( get_current_user_id(), '_cs_htaccess_missing_dismissed', true ) ) {
			$dismiss_notice_url = wp_nonce_url( add_query_arg( array(
				'cs_action' => 'dismiss_notices',
				'cs_notice' => 'htaccess_missing'
			) ), 'cs_notice_nonce' );

			$this->add_notice( array(
				'id'             => 'cs-apache',
				'class'          => 'error',
				'is_dismissible' => false,
				'message'        => array(
					sprintf( __( 'The .htaccess file is missing from: %s', 'commercestore' ), '<strong>' . $upload_directory . '</strong>' ),
					sprintf( __( 'First, please resave the Misc settings tab a few times. If this warning continues to appear, create a file called ".htaccess" in the %s directory, and copy the following into it:', 'commercestore' ), '<strong>' . $upload_directory . '</strong>' ),
					sprintf( __( 'If you have already done this, or it does not apply to your site, you may permenently %s.', 'commercestore' ), '<a href="' . esc_url( $dismiss_notice_url ) . '">' . __( 'dismiss this notice', 'commercestore' ) . '</a>' ),
					'<pre>' . cs_get_htaccess_rules() . '</pre>'
				)
			) );
		}
	}

	/**
	 * Notices about data (migrations, etc...)
	 *
	 * @since 3.0
	 */
	private function add_data_notices() {

		// Recount earnings
		if ( class_exists( 'CS_Recount_Earnings' ) ) {
			$this->add_notice( array(
				'id'             => 'cs-recount-earnings',
				'class'          => 'error',
				'is_dismissible' => false,
				'message'        => sprintf( __( 'CommerceStore 2.5 contains a <a href="%s">built in recount tool</a>. Please <a href="%s">deactivate the CommerceStore - Recount Earnings plugin</a>', 'commercestore' ), admin_url( 'edit.php?post_type=download&page=cs-tools&tab=general' ), admin_url( 'plugins.php' ) )
			) );
		}
	}

	/**
	 * Adds a notice about the deprecated Default Rate for Taxes.
	 *
	 * @since 3.0
	 */
	private function add_tax_rate_notice() {

		// Default tax rate not detected.
		if ( false === cs_get_option( 'tax_rate' ) ) {
			return;
		}

		// On Rates page, settings notice is shown.
		if ( ! empty( $_GET['page'] ) && 'cs-settings' === $_GET['page'] && ! empty( $_GET['section'] ) && 'rates' === $_GET['section'] ) {
			return;
		}

		// URL to fix this
		$url = cs_get_admin_url( array(
			'page'      => 'cs-settings',
			'tab'       => 'taxes',
			'section'   => 'rates'
		) );

		// Link
		$link = '<a href="' . esc_url( $url ) . '" class="button button-secondary">' . __( 'Review Tax Rates', 'commercestore' ) . '</a>';

		// Add the notice
		$this->add_notice( array(
			'id'             => 'cs-default-tax-rate',
			'class'          => 'error',
			/* translators: Link to review existing tax rates. */
			'message'        => '<strong>' . __( 'A default tax rate was detected.', 'commercestore' ) . '</strong></p><p>' . __( 'This setting is no longer used in this version of CommerceStore. Please confirm your regional tax rates are properly configured and update tax settings to remove this notice.', 'commercestore' ) . '</p><p>' . $link,
			'is_dismissible' => false
		) );
	}

	/**
	 * Notices about settings (updating, etc...)
	 *
	 * @since 3.0
	 */
	private function add_settings_notices() {

		// Settings area
		if ( ! empty( $_GET['page'] ) && ( 'cs-settings' === $_GET['page'] ) ) {

			// Settings updated
			if ( ! empty( $_GET['settings-updated'] ) ) {
				$this->add_notice( array(
					'id'      => 'cs-notices',
					'message' => __( 'Settings updated.', 'commercestore' )
				) );
			}

			// No payment gateways are enabled
			if ( ! cs_get_option( 'gateways' ) && cs_is_test_mode() ) {

				// URL to fix this
				$url = add_query_arg( array(
					'post_type' => CS_POST_TYPE,
					'page'      => 'cs-settings',
					'tab'       => 'gateways'
				) );

				// Link
				$link = '<a href="' . esc_url( $url ) . '">' . __( 'Fix this', 'commercestore' ) . '</a>';

				// Add the notice
				$this->add_notice( array(
					'id'             => 'cs-gateways',
					'class'          => 'error',
					'message'        => sprintf( __( 'No payment gateways are enabled. %s.', 'commercestore' ), $link ),
					'is_dismissible' => false
				) );
			}
		}
	}

	/**
	 * Notices about actions that the user has taken
	 *
	 * @since 3.0
	 *
	 * @param string $notice
	 */
	private function add_user_action_notices( $notice = '' ) {

		// Sanitize notice key
		$notice = sanitize_key( $notice );

		// Bail if notice is empty
		if ( empty( $notice ) ) {
			return;
		}

		// Shop discounts errors
		if ( current_user_can( 'manage_shop_discounts' ) ) {
			switch ( $notice ) {
				case 'discount_added' :
					$this->add_notice( array(
						'id'      => 'cs-discount-added',
						'message' => __( 'Discount code added.', 'commercestore' )
					) );
					break;
				case 'discount_add_failed' :
					$this->add_notice( array(
						'id'      => 'cs-discount-add-fail',
						'message' => __( 'There was a problem adding that discount code, please try again.', 'commercestore' ),
						'class'   => 'error'
					) );
					break;
				case 'discount_exists' :
					$this->add_notice( array(
						'id'      => 'cs-discount-exists',
						'message' => __( 'A discount with that code already exists, please use a different code.', 'commercestore' ),
						'class'   => 'error'
					) );
					break;
				case 'discount_updated' :
					$this->add_notice( array(
						'id'      => 'cs-discount-updated',
						'message' => __( 'Discount code updated.', 'commercestore' )
					) );
					break;
				case 'discount_not_changed' :
					$this->add_notice( array(
						'id'      => 'cs-discount-not-changed',
						'message' => __( 'No changes were made to that discount code.', 'commercestore' )
					) );
					break;
				case 'discount_update_failed' :
					$this->add_notice( array(
						'id'      => 'cs-discount-updated-fail',
						'message' => __( 'There was a problem updating that discount code, please try again.', 'commercestore' ),
						'class'   => 'error'
					) );
					break;
				case 'discount_validation_failed' :
					$this->add_notice( array(
						'id'      => 'cs-discount-validation-fail',
						'message' => __( 'The discount code could not be added because one or more of the required fields was empty, please try again.', 'commercestore' ),
						'class'   => 'error'
					) );
					break;
				case 'discount_invalid_code':
					$this->add_notice( array(
						'id'      => 'cs-discount-invalid-code',
						'message' => __( 'The discount code entered is invalid; only alphanumeric characters are allowed, please try again.', 'commercestore' ),
						'class'   => 'error'
					) );
					break;
				case 'discount_invalid_amount' :
					$this->add_notice( array(
						'id'      => 'cs-discount-invalid-amount',
						'message' => __( 'The discount amount must be a valid percentage or numeric flat amount. Please try again.', 'commercestore' ),
						'class'   => 'error'
					) );
					break;
				case 'discount_deleted':
					$this->add_notice( array(
						'id'      => 'cs-discount-deleted',
						'message' => __( 'Discount code deleted.', 'commercestore' )
					) );
					break;
				case 'discount_delete_failed':
					$this->add_notice( array(
						'id'      => 'cs-discount-delete-fail',
						'message' => __( 'There was a problem deleting that discount code, please try again.', 'commercestore' ),
						'class'   => 'error'
					) );
					break;
				case 'discount_activated':
					$this->add_notice( array(
						'id'      => 'cs-discount-activated',
						'message' => __( 'Discount code activated.', 'commercestore' )
					) );
					break;
				case 'discount_activation_failed':
					$this->add_notice( array(
						'id'      => 'cs-discount-activation-fail',
						'message' => __( 'There was a problem activating that discount code, please try again.', 'commercestore' ),
						'class'   => 'error'
					) );
					break;
				case 'discount_deactivated':
					$this->add_notice( array(
						'id'      => 'cs-discount-deactivated',
						'message' => __( 'Discount code deactivated.', 'commercestore' )
					) );
					break;
				case 'discount_deactivation_failed':
					$this->add_notice( array(
						'id'      => 'cs-discount-deactivation-fail',
						'message' => __( 'There was a problem deactivating that discount code, please try again.', 'commercestore' ),
						'class'   => 'error'
					) );
					break;
			}
		}

		// Shop reports errors
		if ( current_user_can( 'view_shop_reports' ) ) {
			switch( $notice ) {
				case 'refreshed-reports' :
					$this->add_notice( array(
						'id'      => 'cs-refreshed-reports',
						'message' => __( 'The reports have been refreshed.', 'commercestore' )
					) );
					break;
			}
		}

		// Shop settings errors
		if ( current_user_can( 'manage_shop_settings' ) ) {
			switch( $notice ) {
				case 'settings-imported' :
					$this->add_notice( array(
						'id'      => 'cs-settings-imported',
						'message' => __( 'The settings have been imported.', 'commercestore' )
					) );
					break;
				case 'api-key-generated' :
					$this->add_notice( array(
						'id'      => 'cs-api-key-generated',
						'message' => __( 'API keys successfully generated.', 'commercestore' )
					) );
					break;
				case 'api-key-exists' :
					$this->add_notice( array(
						'id'      => 'cs-api-key-exists',
						'message' => __( 'The specified user already has API keys.', 'commercestore' ),
						'class'   => 'error'
					) );
					break;
				case 'api-key-regenerated' :
					$this->add_notice( array(
						'id'      => 'cs-api-key-regenerated',
						'message' => __( 'API keys successfully regenerated.', 'commercestore' )
					) );
					break;
				case 'api-key-revoked' :
					$this->add_notice( array(
						'id'      => 'cs-api-key-revoked',
						'message' => __( 'API keys successfully revoked.', 'commercestore' )
					) );
					break;
				case 'test-purchase-email-sent':
					$this->add_notice(
						array(
							'id'      => 'cs-test-purchase-receipt-sent',
							'message' => __( 'The test email was sent successfully.', 'commercestore' )
						)
					);
					break;
			}
		}

		// Shop payments errors
		if ( current_user_can( 'edit_shop_payments' ) ) {
			switch( $notice ) {
				case 'note-added' :
					$this->add_notice( array(
						'id'      => 'cs-note-added',
						'message' => __( 'The note has been added successfully.', 'commercestore' )
					) );
					break;
				case 'payment-updated' :
					$this->add_notice( array(
						'id'      => 'cs-payment-updated',
						'message' => __( 'The order has been updated successfully.', 'commercestore' )
					) );
					break;
				case 'order_added' :
					$this->add_notice( array(
						'id'      => 'cs-order-added',
						'message' => __( 'Order successfully created.', 'commercestore' )
					) );
					break;
				case 'order_trashed' :
					$this->add_notice( array(
						'id'      => 'cs-order-trashed',
						'message' => __( 'The order has been moved to the trash.', 'commercestore' )
					) );
					break;
				case 'order_restored' :
					$this->add_notice( array(
						'id'      => 'cs-order-restored',
						'message' => __( 'The order has been restored.', 'commercestore' )
					) );
					break;
				case 'payment_deleted' :
					$this->add_notice( array(
						'id'      => 'cs-payment-deleted',
						'message' => __( 'The order has been deleted.', 'commercestore' )
					) );
					break;
				case 'email_sent' :
					$this->add_notice( array(
						'id'      => 'cs-payment-sent',
						'message' => __( 'The purchase receipt has been resent.', 'commercestore' )
					) );
					break;
				case 'email_send_failed':
					$this->add_notice( array(
						'id'      => 'cs-payment-sent',
						'message' => __( 'Failed to send purchase receipt.', 'commercestore' )
					) );
					break;
				case 'payment-note-deleted' :
					$this->add_notice( array(
						'id'      => 'cs-note-deleted',
						'message' => __( 'The order note has been deleted.', 'commercestore' )
					) );
					break;
			}
		}

		// Customer Notices
		if ( current_user_can( 'edit_shop_payments' ) ) {
			switch( $notice ) {
				case 'customer-deleted' :
					$this->add_notice( array(
						'id'      => 'cs-customer-deleted',
						'message' => __( 'Customer successfully deleted.', 'commercestore' ),
					) );
					break;
				case 'user-verified' :
					$this->add_notice( array(
						'id'      => 'cs-user-verified',
						'message' => __( 'User successfully verified.', 'commercestore' ),
					) );
					break;
				case 'email-added' :
					$this->add_notice( array(
						'id'      => 'cs-customer-email-added',
						'message' => __( 'Customer email added.', 'commercestore' ),
					) );
					break;
				case 'email-removed' :
					$this->add_notice( array(
						'id'      => 'cs-customer-email-removed',
						'message' => __( 'Customer email deleted.', 'commercestore' ),
					) );
					break;
				case 'email-remove-failed' :
					$this->add_notice( array(
						'id'      => 'cs-customer-email-remove-failed',
						'message' => __( 'Failed to delete customer email.', 'commercestore' ),
						'class'   => 'error',
					) );
					break;
				case 'primary-email-updated' :
					$this->add_notice( array(
						'id'      => 'cscs-customer-primary-email-updated',
						'message' => __( 'Primary email updated for customer.', 'commercestore' )
					) );
					break;
				case 'primary-email-failed' :
					$this->add_notice( array(
						'id'      => 'cs-customer-primary-email-failed',
						'message' => __( 'Failed to set primary email.', 'commercestore' ),
						'class'   => 'error',
					) );
					break;
				case 'address-removed' :
					$this->add_notice( array(
						'id'      => 'cs-customer-address-removed',
						'message' => __( 'Customer address deleted.', 'commercestore' )
					) );
					break;
				case 'address-remove-failed' :
					$this->add_notice( array(
						'id'      => 'cs-customer-address-remove-failed',
						'message' => __( 'Failed to delete customer address.', 'commercestore' ),
						'class'   => 'error',
					) );
					break;
			}
		}
	}

	/**
	 * Show a notice if debugging is enabled in the CommerceStore settings.
	 * Does not show if only the `CS_DEBUG_MODE` constant is defined.
	 *
	 * @since 2.11.5
	 * @return void
	 */
	private function show_debugging_notice() {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			return;
		}
		if ( ! current_user_can( 'manage_shop_settings' ) ) {
			return;
		}
		if ( ! cs_get_option( 'debug_mode', false ) ) {
			return;
		}

		/**
		 * The notices JS needs to be output wherever the notice is displayed, not just CommerceStore screens.
		 * If more notices add to the script then this enqueue will need to be moved.
		 *
		 * @since 3.0
		 */
		wp_enqueue_script( 'cs-admin-notices', CS_PLUGIN_URL . 'assets/js/cs-admin-notices.js', array( 'jquery' ), CS_VERSION, true );
		$view_url = add_query_arg(
			array(
				'post_type' => CS_POST_TYPE,
				'page'      => 'cs-tools',
				'tab'       => 'debug_log',
			),
			admin_url( 'edit.php' )
		);
		?>
		<div id="cs-debug-log-notice" class="notice notice-warning">
			<p>
				<?php esc_html_e( 'CommerceStore debug logging is enabled. Please only leave it enabled for as long as it is needed for troubleshooting.', 'commercestore' ); ?>
			</p>
			<p>
				<a class="button button-secondary" href="<?php echo esc_url( $view_url ); ?>"><?php esc_html_e( 'View Debug Log', 'commercestore' ); ?></a>
				<button class="button button-primary" id="cs-disable-debug-log"><?php esc_html_e( 'Delete Log File and Disable Logging', 'commercestore' ); ?></button>
				<?php wp_nonce_field( 'cs_debug_log_delete', 'cs_debug_log_delete' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Disables the debug log setting and deletes the existing log file.
	 *
	 * @since 2.11.5
	 * @return void
	 */
	public function cs_disable_debugging() {
		$validate_nonce = ! empty( $_GET['nonce'] ) && wp_verify_nonce( $_GET['nonce'], 'cs_debug_log_delete' );
		if ( ! current_user_can( 'manage_shop_settings' ) || ! $validate_nonce ) {
			wp_send_json_error( wpautop( __( 'You do not have permission to perform this action.', 'commercestore' ) ), 403 );
		}
		cs_update_option( 'debug_mode', false );
		global $cs_logs;
		$cs_logs->clear_log_file();
		wp_send_json_success( wpautop( __( 'The debug log has been cleared and logging has been disabled.', 'commercestore' ) ) );
	}

	/**
	 * Escape message string output
	 *
	 * @since 2.6.0 bbPress (r6775)
	 *
	 * @param string $message
	 *
	 * @return string
	 */
	private function esc_notice( $message = '' ) {
		$tags = wp_kses_allowed_html( 'post' );
		$text = wp_kses( $message, $tags );

		return $text;
	}
}
