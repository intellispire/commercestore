<?php
/**
 * Settings Review Notice
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, CommerceStore
 * @license   GPL2+
 * @since     2.11.4
 */

namespace CS\Admin\Promos\Notices;

class Five_Star_Review_Settings extends Five_Star_Review_Dashboard {

	/**
	 * Action hook for displaying the notice.
	 */
	const DISPLAY_HOOK = 'admin_notices';

	/**
	 * Type of promotional notice.
	 */
	const TYPE = 'admin-notice';

	/**
	 * The current screen.
	 *
	 * @var string
	 */
	protected $screen = 'plugin-settings-page';

	/**
	 * Display the notice.
	 * This extends the parent method because the container classes are different.
	 *
	 * @since 2.11.4
	 * @return void
	 */
	public function display() {
		?>
		<div
			id="cs-admin-notice-<?php echo esc_attr( $this->get_id() ); ?>"
			class="notice notice-info cs-admin-notice-<?php echo esc_attr( sanitize_html_class( static::TYPE ) ); ?> cs-promo-notice"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'cs-dismiss-notice-' . $this->get_id() ) ); ?>"
			data-id="<?php echo esc_attr( $this->get_id() ); ?>"
			data-lifespan="<?php echo esc_attr( static::dismiss_duration() ); ?>"
		>
			<?php
			parent::_display();
			?>
		</div>
		<?php
	}

	/**
	 * Whether the notice should display.
	 * This extends the general method as this notice should only display on CommerceStore settings screens.
	 *
	 * @since 2.11.4
	 * @return bool
	 */
	protected function _should_display() {
		if ( ! cs_is_admin_page( 'settings' ) ) {
			return false;
		}
		return parent::_should_display();
	}
}
