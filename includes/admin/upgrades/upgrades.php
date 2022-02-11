<?php
/**
 * Upgrade Screen
 *
 * @package     CS
 * @subpackage  Admin/Upgrades
 * @copyright   Copyright (c) 2018, CommerceStore, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.3.1
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Render Upgrades Screen
 *
 * @since 1.3.1
 * @return void
*/
function cs_upgrades_screen() {

	// Get the upgrade being performed
	$action = isset( $_GET['cs-upgrade'] )
		? sanitize_text_field( $_GET['cs-upgrade'] )
		: ''; ?>

	<div class="wrap">
	<h1><?php _e( 'Upgrades', 'commercestore' ); ?></h1>
	<hr class="wp-header-end">

	<?php if ( is_callable( 'cs_upgrade_render_' . $action ) ) {

		// Until we have fully migrated all upgrade scripts to this new system,
		// we will selectively enqueue the necessary scripts.
		add_filter( 'cs_load_admin_scripts', '__return_true' );
		cs_load_admin_scripts( 'cs-admin-upgrades' );

		// This is the new method to register an upgrade routine, so we can use
		// an ajax and progress bar to display any needed upgrades.
		call_user_func( 'cs_upgrade_render_' . $action );

		// Remove the above filter
		remove_filter( 'cs_load_admin_scripts', '__return_true' );

	} else {

		// This is the legacy upgrade method, which requires a page refresh
		// at each step.
		$step   = isset( $_GET['step']   ) ? absint( $_GET['step']   ) : 1;
		$total  = isset( $_GET['total']  ) ? absint( $_GET['total']  ) : false;
		$custom = isset( $_GET['custom'] ) ? absint( $_GET['custom'] ) : 0;
		$number = isset( $_GET['number'] ) ? absint( $_GET['number'] ) : 100;
		$steps  = round( ( $total / $number ), 0 );

		// Bump step
		if ( ( $steps * $number ) < $total ) {
			$steps++;
		}

		// Update step option
		update_option( 'cs_doing_upgrade', array(
			'page'        => 'cs-upgrades',
			'cs-upgrade' => $action,
			'step'        => $step,
			'total'       => $total,
			'custom'      => $custom,
			'steps'       => $steps
		) );

		// Prevent step estimate from going over
		if ( $step > $steps ) {
			$steps = $step;
		}

		if ( ! empty( $action ) ) :

			// Redirect URL
			$redirect = add_query_arg( array(
				'cs_action' => $action,
				'step'       => $step,
				'total'      => $total,
				'custom'     => $custom
			), admin_url( 'index.php' ) ); ?>

			<div id="cs-upgrade-status">
				<p><?php _e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'commercestore' ); ?></p>

				<?php if ( ! empty( $total ) ) : ?>
					<p><strong><?php printf( __( 'Step %d of approximately %d running', 'commercestore' ), $step, $steps ); ?></strong></p>
				<?php endif; ?>
			</div>
			<script type="text/javascript">
				setTimeout( function() {
					document.location.href = '<?php echo esc_url_raw( $redirect ); ?>';
				}, 250 );
			</script>

		<?php else :

			// Redirect URL
			$redirect = admin_url( 'index.php' ); ?>

			<div id="cs-upgrade-status">
				<p>
					<?php _e( 'The upgrade process has started, please be patient. This could take several minutes. You will be automatically redirected when the upgrade is finished.', 'commercestore' ); ?>
					<img src="<?php echo CS_PLUGIN_URL . 'assets/images/loading.gif'; ?>" id="cs-upgrade-loader"/>
				</p>
			</div>

			<script type="text/javascript">
				jQuery( document ).ready( function() {

					// Trigger upgrades on page load
					var data = {
						action: 'cs_trigger_upgrades'
					};

					jQuery.post( ajaxurl, data, function (response) {
						if ( 'complete' !== response ) {
							return;
						}

						jQuery( '#cs-upgrade-loader' ).hide();

						setTimeout( function() {
							document.location.href = '<?php echo esc_url_raw( $redirect ); ?>';
						}, 250 );
					});
				});
			</script>

		<?php endif;
	} ?>

	</div>

	<?php
}
