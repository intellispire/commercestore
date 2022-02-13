<?php
/**
 * Reviews
 *
 * Manages automatic activation for Reviews.
 *
 * @package     CS
 * @subpackage  Reviews
 * @copyright   Copyright (c) 2021, CommerceStore
 * @license     https://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       2.11.4
 */
namespace CS\Admin\Settings;

use \CS\Admin\Extensions\Extension;

class Reviews extends Extension {

	/**
	 * The product ID on CS.
	 *
	 * @var integer
	 */
	protected $item_id = 37976;

	/**
	 * The CommerceStore settings tab where this extension should show.
	 *
	 * @since 2.11.4
	 * @var string
	 */
	protected $settings_tab = 'marketing';

	/**
	 * The settings section for this item.
	 *
	 * @since 2.11.5
	 * @var string
	 */
	protected $settings_section = 'reviews';

	/**
	 * The pass level required to access this extension.
	 */
	const PASS_LEVEL = \CS\Admin\Pass_Manager::EXTENDED_PASS_ID;

	public function __construct() {
		add_filter( 'cs_settings_sections_marketing', array( $this, 'add_section' ) );
		add_action( 'cs_settings_tab_top_marketing_reviews', array( $this, 'settings_field' ) );
		add_action( 'cs_settings_tab_top_marketing_reviews', array( $this, 'hide_submit_button' ) );
		add_action( 'add_meta_boxes', array( $this, 'maybe_do_metabox' ) );

		parent::__construct();
	}

	/**
	 * Gets the custom configuration for Reviews.
	 *
	 * @since 2.11.4
	 * @param \CS\Admin\Extensions\ProductData $product_data The product data object.
	 * @return array
	 */
	protected function get_configuration( \CS\Admin\Extensions\ProductData $product_data ) {
		$configuration          = array(
			'title' => 'Build Trust With Real Customer Reviews',
		);
		$settings_configuration = array(
			'style'       => 'detailed-2col',
			'description' => $this->get_custom_description(),
			'features'    => array(
				'Request Reviews',
				'Incentivize Reviewers',
				'Full Schema.org Support',
				'Embed Reviews Via Blocks',
				'Limit Reviews to Customers',
				'Vendor Reviews (with Frontend Submissions)',
			),
		);
		return $this->is_cs_settings_screen() ? array_merge( $configuration, $settings_configuration ) : $configuration;
	}

	/**
	 * Gets a custom description for the Reviews extension card.
	 *
	 * @since 2.11.4
	 * @return string
	 */
	private function get_custom_description() {
		$description = array(
			'Increase sales on your site with social proof. 70% of online shoppers don\'t purchase before reading reviews.',
			'Easily collect, manage, and beautifully display reviews all from your WordPress dashboard.',
		);

		return $this->format_description( $description );
	}

	/**
	 * Adds the Reviews section to the settings.
	 *
	 * @param array $sections
	 * @return array
	 */
	public function add_section( $sections ) {
		if ( ! $this->can_show_product_section() ) {
			return $sections;
		}

		$sections[ $this->settings_section ] = __( 'Reviews', 'commercestore' );

		return $sections;
	}

	/**
	 * If Reviews is not active, registers a metabox on individual download edit screen.
	 *
	 * @since 2.11.4
	 * @return void
	 */
	public function maybe_do_metabox() {
		if ( ! $this->is_download_edit_screen() ) {
			return;
		}
		if ( $this->is_activated() ) {
			return;
		}
		add_meta_box(
			'cs-reviews-status',
			__( 'Product Reviews', 'commercestore' ),
			array( $this, 'settings_field' ),
			'download',
			'side',
			'low'
		);
	}

	/**
	 * Whether CommerceStore Reviews active or not.
	 *
	 * @since 2.11.4
	 *
	 * @return bool True if Reviews is active.
	 */
	protected function is_activated() {
		if ( $this->manager->is_plugin_active( $this->get_product_data() ) ) {
			return true;
		}

		return function_exists( 'cs_reviews' );
	}
}

new Reviews();
