<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CS_Recurring_Admin_Notices {

	public function __construct() {
		$this->init();
	}

	public function init() {
		add_action( 'admin_notices', array( $this, 'notices' ) );
	}

	public function notices() {

		if( ! cs_is_admin_page( 'cs-subscriptions' ) ) {
			return;
		}

		if( empty( $_GET['cs-message'] ) ) {
			return;
		}

		$type    = 'updated';
		$message = '';

		switch( strtolower( $_GET['cs-message'] ) ) {

			case 'updated' :

				$message = __( 'Subscription updated successfully', 'commercestore' );

				break;

			case 'deleted' :

				$message = __( 'Subscription deleted successfully', 'commercestore' );

				break;

			case 'cancelled' :

				$message = __( 'Subscription cancelled successfully', 'commercestore' );

				break;

			case 'subscription-note-added' :

				$message = __( 'Subscription note added successfully', 'commercestore' );

				break;

			case 'subscription-note-not-added' :

				$message = __( 'Subscription note could not be added', 'commercestore' );
				$type    = 'error';
				break;

			case 'renewal-added' :

				$message = __( 'Renewal payment recorded successfully', 'commercestore' );

				break;

			case 'renewal-not-added' :

				$message = __( 'Renewal payment could not be recorded', 'commercestore' );
				$type    = 'error';

				break;

			case 'retry-success' :

				$message = __( 'Retry succeeded! The subscription has been renewed successfully.', 'commercestore' );

				break;

			case 'retry-failed' :

				$message = sprintf( __( 'Retry failed. %s', 'commercestore' ), sanitize_text_field( urldecode( $_GET['error-message'] ) ) );
				$type    = 'error';

				break;


		}

		if ( ! empty( $message ) ) {
			echo '<div class="' . esc_attr( $type ) . '"><p>' . $message . '</p></div>';
		}

	}

}
$cs_recurring_admin_notices = new CS_Recurring_Admin_Notices;
