<?php
/**
 * Notices registry.
 *
 * @package CS_Stripe
 * @since   2.6.19
 */

/**
 * Implements a registry for notices.
 *
 * @since 2.6.19
 */
class CS_Stripe_Admin_Notices_Registry extends CS_Stripe_Utils_Registry implements CS_Stripe_Utils_Static_Registry {

	/**
	 * Item error label.
	 *
	 * @since 2.6.19
	 * @var   string
	 */
	public static $item_error_label = 'admin notice';

	/**
	 * The one true Notices_Registry instance.
	 *
	 * @since 2.6.19
	 * @var   CS_Stripe_Notices_Registry
	 */
	public static $instance;

	/**
	 * Retrieves the one true Admin Notices registry instance.
	 *
	 * @since 2.6.19
	 *
	 * @return CS_Stripe_Admin_Notices_Registry Report registry instance.
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new CS_Stripe_Admin_Notices_Registry();
		}

		return self::$instance;
	}

	/**
	 * Initializes the notices registry.
	 *
	 * @since 2.6.19
	 */
	public function init() {
		/**
		 * Fires during instantiation of the notices registry.
		 *
		 * @since 2.6.19
		 *
		 * @param CS_Stripe_Notices_Registry $this Registry instance.
		 */
		do_action( 'csx_admin_notices_registry_init', $this );
	}

	/**
	 * Adds a new notice.
	 *
	 * @since 2.6.19
	 *
	 * @throws Exception
	 *
	 * @param string $notice_id   Unique notice ID.
	 * @param array  $notice_args {
	 *     Arguments for adding a new notice.
	 *
	 *     @type string|callable $message     Notice message or a callback to retrieve it.
	 *     @type string          $type        Notice type. Accepts 'success', 'info', 'warning', 'error'.
	 *                                        Default 'success'.
	 *     @type bool            $dismissible Detrmines if the notice can be hidden for the current install.
	 *                                        Default true
	 * }
	 * @return true
	 * @throws Exception
	 */
	public function add( $notice_id, $notice_args ) {
		$defaults = array(
			'type'        => 'success',
			'dismissible' => true,
		);

		$notice_args = array_merge( $defaults, $notice_args );

		if ( empty( $notice_args['message'] ) ) {
			throw new Exception( esc_html__( 'A message must be specified for each notice.', 'commercestore' ) );
		}

		if ( ! in_array( $notice_args['type'], array( 'success', 'info', 'warning', 'error' ), true ) ) {
			$notice_args['type'] = 'success';
		}

		return $this->add_item( $notice_id, $notice_args );
	}

}
