<?php
/**
 * License Upgrade Notice
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, Sandhills Development, LLC
 * @license   GPL2+
 * @since     2.10.6
 */

namespace CS\Admin\Promos\Notices;

use CS\Admin\Pass_Manager;

class License_Upgrade_Notice extends Notice {

	const DISPLAY_HOOK = 'admin_notices';

	/**
	 * Number of CommerceStore license keys that have been entered.
	 * Not validated to make sure they're actually active; this is
	 * just an indicator if any licenses exist at all.
	 *
	 * @var array
	 */
	private $number_license_keys;

	/**
	 * @var Pass_Manager
	 */
	private $pass_manager;

	/**
	 * License_Upgrade_Notice constructor.
	 */
	public function __construct() {
		global $cs_licensed_products;

		$this->number_license_keys = is_array( $cs_licensed_products ) ? count( $cs_licensed_products ) : 0;
		$this->pass_manager        = new Pass_Manager();
	}

	/**
	 * This notice lasts 90 days.
	 *
	 * @return int
	 */
	public static function dismiss_duration() {
		return 3 * MONTH_IN_SECONDS;
	}

	/**
	 * Determines if the current page is an CommerceStore admin page.
	 *
	 * @return bool
	 */
	private function is_cs_admin_page() {
		if ( defined( 'CS_DOING_TESTS' ) && CS_DOING_TESTS ) {
			return true;
		}

		$screen = get_current_screen();

		if ( ! $screen instanceof \WP_Screen || 'dashboard' === $screen->id || ! cs_is_admin_page( '', '', false ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @return bool
	 */
	protected function _should_display() {

		if ( ! $this->is_cs_admin_page() ) {
			return false;
		}

		// Someone with no license keys entered always sees a notice.
		if ( 0 === $this->number_license_keys ) {
			return true;
		}

		// If we have no pass data yet, don't show the notice because we don't yet know what it should say.
		if ( ! $this->pass_manager->has_pass_data ) {
			return false;
		}

		// If someone has an extended pass or higher, and has an active AffiliateWP license, don't show.
		try {
			if (
				$this->pass_manager->has_pass() &&
				Pass_Manager::pass_compare( $this->pass_manager->highest_pass_id, Pass_Manager::EXTENDED_PASS_ID, '>=' ) &&
				$this->has_affiliate_wp_license() &&
				$this->has_mi_license()
			) {
				return false;
			}
		} catch ( \Exception $e ) {
			return true;
		}

		return true;
	}

	/**
	 * Determines whether or not AffiliateWP is installed and has a license key.
	 *
	 * @since 2.10.6
	 *
	 * @return bool
	 */
	private function has_affiliate_wp_license() {
		if ( ! function_exists( 'affiliate_wp' ) ) {
			return false;
		}

		return (bool) affiliate_wp()->settings->get( 'license_key' );
	}

	/**
	 * Determines whether or not MonsterInsights is installed and has a license key.
	 *
	 * @since 2.11.6
	 *
	 * @return bool
	 */
	private function has_mi_license() {
		if ( ! class_exists( 'MonsterInsights' ) ) {
			return false;
		}

		$mi_license = \MonsterInsights::$instance->license->get_license_key();
		return ! empty( $mi_license );
	}

	/**
	 * @inheritDoc
	 */
	protected function _display() {
		$screen = get_current_screen();
		$source = 'settings';
		if ( $screen instanceof \WP_Screen ) {
			switch ( $screen->base ) {
				case 'edit' :
					$source = 'list-downloads';
					break;
				case 'post' :
					$source = sprintf(
						'%s-download',
						'add' === $screen->action ? 'add' : 'edit'
					);
					break;
				default :
					$source = str_replace( 'download_page_cs-', '', $screen->base );
			}
		}

		try {
			if ( 0 === $this->number_license_keys ) {

				// No license keys active at all.
				printf(
				/* Translators: %1$s opening anchor tag; %2$s closing anchor tag */
					__( 'You are using the free version of CommerceStore. %1$sPurchase a pass%2$s to get email marketing tools and recurring payments.', 'commercestore' ),
					'<a href="' . esc_url( add_query_arg( $this->query_args( 'core', $source ), 'https://commercestore.com/pricing/' ) ) . '" target="_blank">',
					'</a>'
				);

			} elseif ( ! $this->pass_manager->highest_pass_id ) {

				// Individual product license active, but no pass.
				printf(
				/* Translators: %1$s opening anchor tag; %2$s closing anchor tag */
					__( 'For access to additional CommerceStore extensions to grow your store, consider %1$spurchasing a pass%2$s.', 'commercestore' ),
					'<a href="' . esc_url( add_query_arg( $this->query_args( 'extension-license', $source ), 'https://commercestore.com/pricing/' ) ) . '" target="_blank">',
					'</a>'
				);

			} elseif ( Pass_Manager::pass_compare( $this->pass_manager->highest_pass_id, Pass_Manager::PERSONAL_PASS_ID, '=' ) ) {

				// Personal pass active.
				printf(
				/* Translators: %1$s opening anchor tag; %2$s closing anchor tag */
					__( 'You are using CommerceStore with a Personal Pass. Consider %1$supgrading%2$s to get recurring payments and more.', 'commercestore' ),
					'<a href="' . esc_url( add_query_arg( $this->query_args( 'personal-pass', $source ), 'https://commercestore.com/your-account/license-keys/' ) ) . '" target="_blank">',
					'</a>'
				);

			} elseif ( Pass_Manager::pass_compare( $this->pass_manager->highest_pass_id, Pass_Manager::EXTENDED_PASS_ID, '>=' ) ) {

				if ( ! $this->has_affiliate_wp_license() ) {
				// Extended pass or higher.
				printf(
				/* Translators: %1$s opening anchor tag; %2$s closing anchor tag */
					__( 'Grow your business and make more money with affiliate marketing. %1$sGet AffiliateWP%2$s', 'commercestore' ),
					'<a href="' . esc_url( add_query_arg( $this->query_args( 'extended-pass', $source ), 'https://affiliatewp.com/?ref=743' ) ) . '" target="_blank">',
					'</a>'
				);
				} elseif( ! $this->has_mi_license() ) {
					printf(
					/* Translators: %1$s opening anchor tag; %2$s closing anchor tag */
						__( 'Gain access to powerful insights to grow your traffic and revenue. %1$sGet MonsterInsights%2$s', 'commercestore' ),
						'<a href="' . esc_url( 'https://monsterinsights.com?utm_campaign=xsell&utm_source=eddplugin&utm_content=top-promo' ) . '" target="_blank">',
						'</a>'
					);
				}
			}
		} catch ( \Exception $e ) {
			// If we're in here, that means we have an invalid pass ID... what should we do? :thinking:
		}
	}

	/**
	 * Builds the UTM parameters for the URLs.
	 *
	 * @since 2.10.6
	 *
	 * @param string $upgrade_from License type upgraded from.
	 * @param string $source       Current page.
	 *
	 * @return string[]
	 */
	private function query_args( $upgrade_from, $source = 'settings' ) {
		return array(
			'utm_source'   => urlencode( $source ),
			'utm_medium'   => 'upgrade-from-' . urlencode( $upgrade_from ),
			'utm_campaign' => 'admin',
			'utm_content'  => 'top-promo'
		);
	}
}
