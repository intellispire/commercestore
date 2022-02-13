<?php
/**
 * Dashboard Review Notice
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, CommerceStore
 * @license   GPL2+
 * @since     2.11.4
 */

namespace CS\Admin\Promos\Notices;

class Five_Star_Review_Dashboard extends Notice {

	/**
	 * Action hook for displaying the notice.
	 */
	const DISPLAY_HOOK = 'cs_dashboard_sales_widget';

	/**
	 * Type of promotional notice.
	 */
	const TYPE = 'dashboard';

	/**
	 * Capability required to view or dismiss the notice.
	 */
	const CAPABILITY = 'manage_shop_settings';

	/**
	 * The current screen.
	 *
	 * @var string
	 */
	protected $screen = 'dashboard';

	/**
	 * The ID of the notice. Defined specifically here as we intend to use it twice.
	 *
	 * @since 2.11.4
	 * @return string
	 */
	public function get_id() {
		return 'five-star-review';
	}

	/**
	 * Display the notice.
	 *
	 * @since 2.11.4
	 * @return void
	 */
	public function display() {
		?>
		<div
			id="cs-admin-notice-<?php echo esc_attr( $this->get_id() ); ?>"
			class="cs-admin-notice-<?php echo esc_attr( sanitize_html_class( static::TYPE ) ); ?> cs-promo-notice"
			data-nonce="<?php echo esc_attr( wp_create_nonce( 'cs-dismiss-notice-' . $this->get_id() ) ); ?>"
			data-id="<?php echo esc_attr( $this->get_id() ); ?>"
			data-lifespan="<?php echo esc_attr( static::dismiss_duration() ); ?>"
		>
			<?php
			$this->_display();
			?>
		</div>
		<?php
	}

	/**
	 * The promo notice content.
	 *
	 * @since 2.11.4
	 * @return void
	 */
	public function _display() {
		?>
		<div class="cs-review-step cs-review-step-1">
			<p><?php esc_html_e( 'Hey, I noticed you\'ve made quite a few sales with CommerceStore! Are you enjoying CommerceStore?', 'commercestore' ); ?></p>
			<div class="cs-review-actions">
				<button class="button-primary cs-review-switch-step" data-step="3"><?php esc_html_e( 'Yes', 'commercestore' ); ?></button><br />
				<button class="button-link cs-review-switch-step" data-step="2"><?php esc_html_e( 'Not Really', 'commercestore' ); ?></button>
			</div>
		</div>
		<div class="cs-review-step cs-review-step-2" style="display:none;">
			<p><?php esc_html_e( 'We\'re sorry to hear you aren\'t enjoying CommerceStore. We would love a chance to improve. Could you take a minute and let us know what we can do better?', 'commercestore' ); ?></p>
			<div class="cs-review-actions">
				<a href="<?php echo esc_url( $this->url() ); ?>" class="button button-secondary cs-promo-notice-dismiss" target="_blank"><?php esc_html_e( 'Give Feedback', 'commercestore' ); ?></a><br>
				<button class="button-link cs-promo-notice-dismiss"><?php esc_html_e( 'No thanks', 'commercestore' ); ?></button>
			</div>
		</div>
		<div class="cs-review-step cs-review-step-3" style="display:none;">
			<p><?php esc_html_e( 'That\'s awesome! Could you please do me a BIG favor and give it a 5-star rating on WordPress to help us spread the word and boost our motivation?', 'commercestore' ); ?></p>
			<p><strong><?php echo wp_kses( __( '~ Chris Klosowski<br>President of CommerceStore', 'commercestore' ), array( 'br' => array() ) ); ?></strong></p>
			<div class="cs-review-actions">
				<a href="https://wordpress.org/support/plugin/commercestore/reviews/?filter=5#new-post" class="button button-primary cs-promo-notice-dismiss" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Ok, you deserve it!', 'commercestore' ); ?></a><br>
				<button class="button-link cs-promo-notice-dismiss"><?php esc_html_e( 'No thanks', 'commercestore' ); ?></button>
			</div>
		</div>
		<img alt="" class="cs-peeking" src="<?php echo esc_url( CS_PLUGIN_URL . 'assets/images/cs-peeking.png' ); ?>" />
		<script type="text/javascript">
			document.addEventListener( 'DOMContentLoaded', function() {
				var steps = document.querySelectorAll( '.cs-review-switch-step' );
				steps.forEach( function(step) {
					step.addEventListener( 'click', function ( e ) {
						e.preventDefault();
						var target = this.getAttribute( 'data-step' );
						if ( target ) {
							var notice = this.closest( '.cs-promo-notice' );
							var review_step = notice.querySelector( '.cs-review-step-' + target );
							if ( review_step ) {
								var thisStep = this.closest( '.cs-review-step' );
								csFadeOut( thisStep );
								csFadeIn( review_step );
							}
						}
					} )
				} )

				function csFadeIn( element ) {
					var op = 0;
					element.style.opacity = op;
					element.style.display = 'block';
					var timer = setInterval( function () {
						if ( op >= 1 ) {
							clearInterval( timer );
						}
						element.style.opacity = op;
						element.style.filter = 'alpha(opacity=' + op * 100 + ')';
						op = op + 0.1;
					}, 80 );
				}

				function csFadeOut( element ) {
					var op = 1;
					var timer = setInterval( function () {
						if ( op <= 0 ) {
							element.style.display = 'none';
							clearInterval( timer );
						}
						element.style.opacity = op;
						element.style.filter = 'alpha(opacity=' + op * 100 + ')';
						op = op - 0.1;
					}, 80 );
				}
			} );
		</script>
		<?php
	}

	/**
	 * Whether the notice should display.
	 *
	 * @since 2.11.4
	 * @return bool
	 */
	protected function _should_display() {

		$activated = cs_get_activation_date();
		// Do not show if CommerceStore was activated less than 30 days ago.
		if ( ! is_numeric( $activated ) || ( $activated + ( DAY_IN_SECONDS * 30 ) ) > time() ) {
			return false;
		}
		// @todo Change this to cs_count_orders in 3.0
		$payments = cs_count_payments();

		return isset( $payments->publish ) && $payments->publish >= 15;
	}

	/**
	 * Builds the UTM parameters for the URLs.
	 *
	 * @since 2.11.4
	 *
	 * @return string
	 */
	private function url() {
		$args = array(
			'utm_source'   => urlencode( $this->screen ),
			'utm_medium'   => urlencode( static::TYPE ),
			'utm_campaign' => 'Feedback',
			'utm_content'  => 'give-feedback',
		);

		return add_query_arg(
			$args,
			'https://commercestore.com/plugin-feedback'
		);
	}
}
