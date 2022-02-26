<?php
/**
 * Tracking functions for reporting plugin usage to the CommerceStore site for users that
 * have opted in.
 *
 * @package     CS
 * @subpackage  Admin
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.8.2
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Usage tracking
 *
 * @since  1.8.2
 * @return void
 */
class CS_Tracking {

	/**
	 * The data to send to the CommerceStore site
	 *
	 * @access private
	 */
	private $data;

	/**
	 * Get things going
	 *
	 */
	public function __construct() {

		// WordPress core actions
		add_action( 'init',          array( $this, 'schedule_send' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice'  ) );

		// Sanitize setting
		add_action( 'cs_settings_general_sanitize', array( $this, 'check_for_settings_optin' ) );

		// Handle opting in and out
		add_action( 'cs_opt_into_tracking',   array( $this, 'check_for_optin'  ) );
		add_action( 'cs_opt_out_of_tracking', array( $this, 'check_for_optout' ) );
	}

	/**
	 * Check if the user has opted into tracking
	 *
	 * @access private
	 * @return bool
	 */
	private function tracking_allowed() {
		return (bool) cs_get_option( 'allow_tracking', false );
	}

	/**
	 * Setup the data that is going to be tracked
	 *
	 * @access private
	 * @return void
	 */
	private function setup_data() {

		// Retrieve current theme info
		$theme_data    = wp_get_theme();
		$theme         = $theme_data->Name . ' ' . $theme_data->Version;
		$checkout_page = cs_get_option( 'purchase_page', false );
		$date          = ( false !== $checkout_page )
			? get_post_field( 'post_date', $checkout_page )
			: 'not set';
		$server        = isset( $_SERVER['SERVER_SOFTWARE'] )
			? $_SERVER['SERVER_SOFTWARE']
			: '';

		// Setup data
		$data = array(
			'php_version'  => phpversion(),
			'cs_version'  => CS_VERSION,
			'wp_version'   => get_bloginfo( 'version' ),
			'server'       => $server,
			'install_date' => $date,
			'multisite'    => is_multisite(),
			'url'          => home_url(),
			'theme'        => $theme,
			'email'        => get_bloginfo( 'admin_email' )
		);

		// Retrieve current plugin information
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// Get plugins
		$plugins        = array_keys( get_plugins() );
		$active_plugins = get_option( 'active_plugins', array() );

		// Remove active plugins from list so we can show active and inactive separately.
		foreach ( $plugins as $key => $plugin ) {
			if ( in_array( $plugin, $active_plugins, true ) ) {
				unset( $plugins[ $key ] );
			}
		}

		$data['active_plugins']   = $active_plugins;
		$data['inactive_plugins'] = $plugins;
		$data['active_gateways']  = array_keys( cs_get_enabled_payment_gateways() );
		$data['products']         = wp_count_posts( CS_POST_TYPE )->publish;
		$data['download_label']   = cs_get_label_singular( true );
		$data['locale']           = get_locale();

		$this->data = $data;
	}

	/**
	 * Send the data to the CommerceStore server
	 *
	 * @access private
	 *
	 * @param  bool $override If we should override the tracking setting.
	 * @param  bool $ignore_last_checkin If we should ignore when the last check in was.
	 *
	 * @return bool
	 */
	public function send_checkin( $override = false, $ignore_last_checkin = false ) {

		$home_url = trailingslashit( home_url() );

		// Allows us to stop our own site from checking in, and a filter for our additional sites
		if ( $home_url === 'https://commercestore.com/' || apply_filters( 'cs_disable_tracking_checkin', false ) ) {
			return false;
		}

		if ( ! $this->tracking_allowed() && ! $override ) {
			return false;
		}

		// Send a maximum of once per week
		$last_send = $this->get_last_send();
		if ( is_numeric( $last_send ) && $last_send > strtotime( '-1 week' ) && ! $ignore_last_checkin ) {
			return false;
		}

		$this->setup_data();

		wp_remote_post( 'https://commercestore.com/?cs_action=checkin', array(
			'method'      => 'POST',
			'timeout'     => 8,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => false,
			'body'        => $this->data,
			'user-agent'  => 'CS/' . CS_VERSION . '; ' . get_bloginfo( 'url' )
		) );

		update_option( 'cs_tracking_last_send', time() );

		return true;
	}

	/**
	 * Check for a new opt-in on settings save
	 *
	 * This runs during the sanitation of General settings, thus the return
	 *
	 * @return array
	 */
	public function check_for_settings_optin( $input ) {

		// Send an intial check in on settings save
		if ( isset( $input['allow_tracking'] ) && $input['allow_tracking'] == 1 ) {
			$this->send_checkin( true );
		}

		return $input;
	}

	/**
	 * Check for a new opt-in via the admin notice
	 *
	 * @return void
	 */
	public function check_for_optin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		cs_update_option( 'allow_tracking', 1 );

		$this->send_checkin( true );

		update_option( 'cs_tracking_notice', '1' );
	}

	/**
	 * Check for a new opt-in via the admin notice
	 *
	 * @return void
	 */
	public function check_for_optout() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		cs_delete_option( 'allow_tracking' );
		update_option( 'cs_tracking_notice', '1' );
		cs_redirect( remove_query_arg( 'cs_action' ) );
	}

	/**
	 * Get the last time a checkin was sent
	 *
	 * @access private
	 * @return false|string
	 */
	private function get_last_send() {
		return get_option( 'cs_tracking_last_send' );
	}

	/**
	 * Schedule a weekly checkin
	 *
	 * We send once a week (while tracking is allowed) to check in, which can be
	 * used to determine active sites.
	 *
	 * @return void
	 */
	public function schedule_send() {
		if ( cs_doing_cron() ) {
			add_action( 'cs_weekly_scheduled_events', array( $this, 'send_checkin' ) );
		}
	}

	/**
	 * Display the admin notice to users that have not opted-in or out
	 *
	 * @return void
	 */
	public function admin_notice() {
		static $once = null;

		// Only 1 notice
		if ( ! is_null( $once ) ) {
			return;
		}

		// Already ran once
		$once = true;

		// Bail if already noticed
		if ( get_option( 'cs_tracking_notice' ) ) {
			return;
		}

		// Bail if already allowed
		if ( cs_get_option( 'allow_tracking', false ) ) {
			return;
		}

		// Bail if user cannot decide
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// No notices for local installs
		if ( cs_is_dev_environment() ) {
			update_option( 'cs_tracking_notice', '1' );

		// Notify the user
		} elseif ( cs_is_admin_page() && ! cs_is_admin_page( 'index.php' ) && ! cs_is_insertable_admin_page() ) {
			$optin_url      = add_query_arg( 'cs_action', 'opt_into_tracking'   );
			$optout_url     = add_query_arg( 'cs_action', 'opt_out_of_tracking' );
			$source         = substr( md5( get_bloginfo( 'name' ) ), 0, 10 );
			$extensions_url = 'https://commercestore.com/downloads/?utm_source=' . $source . '&utm_medium=admin&utm_term=notice&utm_campaign=CSUsageTracking';

			// Add the notice
			CS()->notices->add_notice( array(
				'id'      => 'cs-allow-tracking',
				'class'   => 'updated',
				'message' => array(
					'<strong>' . __( 'Allow CommerceStore to track plugin usage?', 'commercestore' ) . '</strong>',
					sprintf( __( 'Opt-in to light usage tracking and our newsletter, and immediately be emailed a discount to the CommerceStore shop, valid towards the <a href="%s" target="_blank">purchase of extensions</a>.', 'commercestore' ), $extensions_url ),
					__( 'No sensitive data is tracked.', 'commercestore' ),
					'<a href="' . esc_url( $optin_url ) . '" class="button-secondary">' . __( 'Allow', 'commercestore' ) . '</a> <a href="' . esc_url( $optout_url ) . '" class="button-secondary">' . __( 'Do not allow', 'commercestore' ) . '</a>'
				),
				'is_dismissible' => false
			) );
		}
	}
}
