<?php
/**
 * Tools
 *
 * These are functions used for displaying CommerceStore tools such as the import/export system.
 *
 * @package     CS
 * @subpackage  Admin/Tools
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Shows the tools panel which contains CS-specific tools including the built-in import/export system.
 *
 * @since 1.8
 * @author Daniel J Griffiths
 */
function cs_tools_page() {

	// Get tabs and active tab
	$tabs       = cs_get_tools_tabs();
	$active_tab = isset( $_GET['tab'] )
		? sanitize_key( $_GET['tab'] )
		: 'general';

	wp_enqueue_script( 'cs-admin-tools' );

	if ( 'import_export' === $active_tab ) {
		wp_enqueue_script( 'cs-admin-tools-import' );
		wp_enqueue_script( 'cs-admin-tools-export' );
	}
?>

	<div class="wrap">
		<h1><?php esc_html_e( 'Tools', 'commercestore' ); ?></h1>
		<hr class="wp-header-end">

		<nav class="nav-tab-wrapper cs-nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Secondary menu', 'commercestore' ); ?>">
		<?php

		foreach ( $tabs as $tab_id => $tab_name ) {

			$tab_url = cs_get_admin_url(
				array(
					'page' => 'cs-tools',
					'tab'  => $tab_id,
				)
			);

			$tab_url = remove_query_arg(
				array(
					'cs-message',
				),
				$tab_url
			);

			$active = ( $active_tab === $tab_id )
				? ' nav-tab-active'
				: '';

			echo '<a href="' . esc_url( $tab_url ) . '" class="nav-tab' . esc_attr( $active ) . '">' . esc_html( $tab_name ) . '</a>';
		}

		?>
		</nav>

		<div class="metabox-holder">
			<?php
			do_action( 'cs_tools_tab_' . $active_tab );
			?>
		</div><!-- .metabox-holder -->
	</div><!-- .wrap -->

	<?php
}

/**
 * Retrieve tools tabs.
 *
 * @since 2.0
 *
 * @return array Tabs for the 'Tools' page.
 */
function cs_get_tools_tabs() {
	static $tabs = array();

	// Set tabs if empty
	if ( empty( $tabs ) ) {

		// Define all tabs
		$tabs = array(
			'general'       => __( 'General',       'commercestore' ),
			'api_keys'      => __( 'API Keys',      'commercestore' ),
			'betas'         => __( 'Beta Versions', 'commercestore' ),
			'logs'          => __( 'Logs',          'commercestore' ),
			'system_info'   => __( 'System Info',   'commercestore' ),
			'debug_log'     => __( 'Debug Log',     'commercestore' ),
			'import_export' => __( 'Import/Export', 'commercestore' )
		);

		// Unset the betas tab if not allowed
		if ( count( cs_get_beta_enabled_extensions() ) <= 0 ) {
			unset( $tabs['betas'] );
		}
	}

	// Filter & return
	return apply_filters( 'cs_tools_tabs', $tabs );
}

/**
 * Display the recount stats.
 *
 * @since 2.5
 */
function cs_tools_recount_stats_display() {

	// Bail if the user does not have the required capabilities.
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	do_action( 'cs_tools_recount_stats_before' );
	?>

	<div class="postbox">
		<h3><span><?php esc_html_e( 'Recount Stats', 'commercestore' ); ?></span></h3>
		<div class="inside recount-stats-controls">
			<p><?php esc_html_e( 'Use these tools to recount / reset store stats.', 'commercestore' ); ?></p>
			<form method="post" id="cs-tools-recount-form" class="cs-export-form cs-import-export-form">
				<span>
					<?php wp_nonce_field( 'cs_ajax_export', 'cs_ajax_export' ); ?>

					<select name="cs-export-class" id="recount-stats-type">
						<option value="0" selected="selected"
								disabled="disabled"><?php esc_html_e( 'Please select an option', 'commercestore' ); ?></option>
						<option data-type="recount-store"
								value="CS_Tools_Recount_Store_Earnings"><?php esc_html_e( 'Recount Store Earnings and Sales', 'commercestore' ); ?></option>
						<option data-type="recount-download"
								value="CS_Tools_Recount_Download_Stats"><?php printf( __( 'Recount Earnings and Sales for a %s', 'commercestore' ), cs_get_label_singular( true ) ); ?></option>
						<option data-type="recount-all"
								value="CS_Tools_Recount_All_Stats"><?php printf( __( 'Recount Earnings and Sales for All %s', 'commercestore' ), cs_get_label_plural( true ) ); ?></option>
						<option data-type="recount-customer-stats"
								value="CS_Tools_Recount_Customer_Stats"><?php esc_html_e( 'Recount Customer Stats', 'commercestore' ); ?></option>
						<?php do_action( 'cs_recount_tool_options' ); ?>
						<option data-type="reset-stats"
								value="CS_Tools_Reset_Stats"><?php esc_html_e( 'Reset Store', 'commercestore' ); ?></option>
					</select>

					<span id="tools-product-dropdown" style="display: none">
						<?php
						$args = array(
							'name'   => 'download_id',
							'number' => - 1,
							'chosen' => true,
						);
						echo CS()->html->product_dropdown( $args );
						?>
					</span>

					<button type="submit" id="recount-stats-submit" class="button button-secondary">
						<?php esc_html_e( 'Submit', 'commercestore' ); ?>
					</button>

					<br/>

					<span class="cs-recount-stats-descriptions">
						<span id="recount-store"><?php _e( 'Recalculates the total store earnings and sales.', 'commercestore' ); ?></span>
						<span id="recount-download"><?php printf( __( 'Recalculates the earnings and sales stats for a specific %s.', 'commercestore' ), cs_get_label_singular( true ) ); ?></span>
						<span id="recount-all"><?php printf( __( 'Recalculates the earnings and sales stats for all %s.', 'commercestore' ), cs_get_label_plural( true ) ); ?></span>
						<span id="recount-customer-stats"><?php _e( 'Recalculates the lifetime value and purchase counts for all customers.', 'commercestore' ); ?></span>
						<?php do_action( 'cs_recount_tool_descriptions' ); ?>
						<span id="reset-stats"><?php _e( '<strong>Deletes</strong> all payment records, customers, and related log entries.', 'commercestore' ); ?></span>
					</span>

					<span class="spinner"></span>

				</span>
			</form>
			<?php do_action( 'cs_tools_recount_forms' ); ?>
		</div><!-- .inside -->
	</div><!-- .postbox -->

	<?php
	do_action( 'cs_tools_recount_stats_after' );
}

add_action( 'cs_tools_tab_general', 'cs_tools_recount_stats_display' );

/**
 * Display the clear upgrades tab.
 *
 * @since 2.3.5
 */
function cs_tools_clear_doing_upgrade_display() {
	if ( ! current_user_can( 'manage_shop_settings' ) || false === get_option( 'cs_doing_upgrade' ) ) {
		return;
	}

	do_action( 'cs_tools_clear_doing_upgrade_before' );
	?>
    <div class="postbox">
        <h3><span><?php _e( 'Clear Incomplete Upgrade Notice', 'commercestore' ); ?></span></h3>
        <div class="inside">
            <p><?php _e( 'Sometimes a database upgrade notice may not be cleared after an upgrade is completed due to conflicts with other extensions or other minor issues.', 'commercestore' ); ?></p>
            <p><?php _e( 'If you\'re certain these upgrades have been completed, you can clear these upgrade notices by clicking the button below. If you have any questions about this, please contact the CommerceStore support team and we\'ll be happy to help.', 'commercestore' ); ?></p>
            <form method="post"
                  action="<?php echo admin_url( 'edit.php?post_type=download&page=cs-tools&tab=general' ); ?>">
                <p>
                    <input type="hidden" name="cs_action" value="clear_doing_upgrade"/>
					<?php wp_nonce_field( 'cs_clear_upgrades_nonce', 'cs_clear_upgrades_nonce' ); ?>
					<?php submit_button( __( 'Clear Incomplete Upgrade Notice', 'commercestore' ), 'secondary', 'submit', false ); ?>
                </p>
            </form>
        </div><!-- .inside -->
    </div><!-- .postbox -->
	<?php
	do_action( 'cs_tools_clear_doing_upgrade_after' );
}

add_action( 'cs_tools_tab_general', 'cs_tools_clear_doing_upgrade_display' );

/**
 * Display the API Keys
 *
 * @since 2.0
 */
function cs_tools_api_keys_display() {
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	do_action( 'cs_tools_api_keys_before' );

	require_once CS_PLUGIN_DIR . 'includes/admin/class-api-keys-table.php';

	$api_keys_table = new CS_API_Keys_Table();
	$api_keys_table->prepare_items();
	$api_keys_table->display();
	?>
    <p>
		<?php printf(
			__( 'These API keys allow you to use the <a href="%s">CS REST API</a> to retrieve store data in JSON or XML for external applications or devices, such as the <a href="%s">CS mobile app</a>.', 'commercestore' ),
			'http://docs.commercestore.com/article/544-cs-api-reference/',
			'https://commercestore.com/downloads/ios-sales-earnings-tracker/?utm_source=plugin-tools-page&utm_medium=api_keys_tab&utm_term=ios-app&utm_campaign=CSMobileApp'
		); ?>
    </p>
	<?php

	do_action( 'cs_tools_api_keys_after' );
}

add_action( 'cs_tools_tab_api_keys', 'cs_tools_api_keys_display' );


/**
 * Display beta opt-ins
 *
 * @since 2.6.11
 */
function cs_tools_betas_display() {
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	$has_beta = cs_get_beta_enabled_extensions();

	do_action( 'cs_tools_betas_before' );
	?>

    <div class="postbox cs-beta-support">
        <h3><span><?php _e( 'Enable Beta Versions', 'commercestore' ); ?></span></h3>
        <div class="inside">
            <p><?php _e( 'Checking any of the below checkboxes will opt you in to receive pre-release update notifications. You can opt-out at any time. Pre-release updates do not install automatically, you will still have the opportunity to ignore update notifications.', 'commercestore' ); ?></p>
            <form method="post"
                  action="<?php echo admin_url( 'edit.php?post_type=download&page=cs-tools&tab=betas' ); ?>">
                <table class="form-table cs-beta-support">
                    <tbody>
					<?php foreach ( $has_beta as $slug => $product ) : ?>
                        <tr>
							<?php $checked = cs_extension_has_beta_support( $slug ); ?>
                            <th scope="row"><?php echo esc_html( $product ); ?></th>
                            <td>
                                <input type="checkbox" name="enabled_betas[<?php echo esc_attr( $slug ); ?>]"
                                       id="enabled_betas[<?php echo esc_attr( $slug ); ?>]"<?php echo checked( $checked, true, false ); ?>
                                       value="1"/>
                                <label for="enabled_betas[<?php echo esc_attr( $slug ); ?>]"><?php printf( __( 'Get updates for pre-release versions of %s', 'commercestore' ), $product ); ?></label>
                            </td>
                        </tr>
					<?php endforeach; ?>
                    </tbody>
                </table>
                <input type="hidden" name="cs_action" value="save_enabled_betas"/>
				<?php wp_nonce_field( 'cs_save_betas_nonce', 'cs_save_betas_nonce' ); ?>
				<?php submit_button( __( 'Save', 'commercestore' ), 'secondary', 'submit', false ); ?>
            </form>
        </div>
    </div>

	<?php
	do_action( 'cs_tools_betas_after' );
}

add_action( 'cs_tools_tab_betas', 'cs_tools_betas_display' );

/**
 * Return an array of all extensions with beta support.
 *
 * Extensions should be added as 'extension-slug' => 'Extension Name'
 *
 * @since 2.6.11
 *
 * @return array $extensions The array of extensions
 */
function cs_get_beta_enabled_extensions() {
	return (array) apply_filters( 'cs_beta_enabled_extensions', array() );
}

/**
 * Check if a given extensions has beta support enabled
 *
 * @since 2.6.11
 *
 * @param string $slug The slug of the extension to check
 *
 * @return bool True if enabled, false otherwise
 */
function cs_extension_has_beta_support( $slug ) {
	$enabled_betas = cs_get_option( 'enabled_betas', array() );
	$return        = false;

	if ( array_key_exists( $slug, $enabled_betas ) ) {
		$return = true;
	}

	return $return;
}

/**
 * Save enabled betas.
 *
 * @since 2.6.11
 */
function cs_tools_enabled_betas_save() {
	if ( ! wp_verify_nonce( $_POST['cs_save_betas_nonce'], 'cs_save_betas_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	if ( ! empty( $_POST['enabled_betas'] ) ) {
		$enabled_betas = array_filter( array_map( 'cs_tools_enabled_betas_sanitize_value', $_POST['enabled_betas'] ) );
		cs_update_option( 'enabled_betas', $enabled_betas );
	} else {
		cs_delete_option( 'enabled_betas' );
	}
}

add_action( 'cs_save_enabled_betas', 'cs_tools_enabled_betas_save' );

/**
 * Sanitize the supported beta values by making them booleans
 *
 * @since 2.6.11
 *
 * @param mixed $value The value being sent in, determining if beta support is enabled.
 *
 * @return bool
 */
function cs_tools_enabled_betas_sanitize_value( $value ) {
	return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
}

/**
 * Save banned emails.
 *
 * @since 2.0
 */
function cs_tools_banned_emails_save() {
	if ( ! wp_verify_nonce( $_POST['cs_banned_emails_nonce'], 'cs_banned_emails_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	if ( ! empty( $_POST['banned_emails'] ) ) {
		// Sanitize the input
		$emails = array_map( 'trim', explode( "\n", $_POST['banned_emails'] ) );
		$emails = array_unique( $emails );
		$emails = array_map( 'sanitize_text_field', $emails );

		foreach ( $emails as $id => $email ) {
			if ( ! is_email( $email ) && $email[0] != '@' && $email[0] != '.' ) {
				unset( $emails[ $id ] );
			}
		}
	} else {
		$emails = '';
	}

	cs_update_option( 'banned_emails', $emails );
}
add_action( 'cs_save_banned_emails', 'cs_tools_banned_emails_save' );

/**
 * Execute upgrade notice clear.
 *
 * @since 2.3.5
 */
function cs_tools_clear_upgrade_notice() {
	if ( ! wp_verify_nonce( $_POST['cs_clear_upgrades_nonce'], 'cs_clear_upgrades_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	delete_option( 'cs_doing_upgrade' );
}
add_action( 'cs_clear_doing_upgrade', 'cs_tools_clear_upgrade_notice' );

/**
 * Display the tools import/export tab.
 *
 * @since 2.0
 */
function cs_tools_import_export_display() {
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	do_action( 'cs_tools_import_export_before' );
	?>

	<div class="postbox cs-import-payment-history">
		<h3><span><?php esc_html_e( 'Import Orders', 'commercestore' ); ?></span></h3>
		<div class="inside">
			<p><?php esc_html_e( 'Import a CSV file of orders.', 'commercestore' ); ?></p>
            <form id="cs-import-payments" class="cs-import-form cs-import-export-form"
                  action="<?php echo esc_url( add_query_arg( 'cs_action', 'upload_import_file', admin_url() ) ); ?>"
                  method="post" enctype="multipart/form-data">

                <div class="cs-import-file-wrap">
					<?php wp_nonce_field( 'cs_ajax_import', 'cs_ajax_import' ); ?>
                    <input type="hidden" name="cs-import-class" value="CS_Batch_Payments_Import"/>
                    <p>
                        <input name="cs-import-file" id="cs-payments-import-file" type="file"/>
                    </p>
                    <span>
						<input type="submit" value="<?php _e( 'Import CSV', 'commercestore' ); ?>"
                               class="button-secondary"/>
						<span class="spinner"></span>
					</span>
                </div>

                <div class="cs-import-options" id="cs-import-payments-options" style="display:none;">

                    <p>
						<?php
						printf(
							__( 'Each column loaded from the CSV needs to be mapped to an order field. Select the column that should be mapped to each field below. Any columns not needed can be ignored. See <a href="%s" target="_blank">this guide</a> for assistance with importing payment records.', 'commercestore' ),
							'https://docs.commercestore.com/category/1337-importexport'
						);
						?>
                    </p>

                    <table class="widefat cs_repeatable_table striped" width="100%" cellpadding="0" cellspacing="0">
                        <thead>
                        <tr>
                            <th><strong><?php _e( 'Payment Field', 'commercestore' ); ?></strong></th>
                            <th><strong><?php _e( 'CSV Column', 'commercestore' ); ?></strong></th>
                            <th><strong><?php _e( 'Data Preview', 'commercestore' ); ?></strong></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><?php _e( 'Currency Code', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[currency]" class="cs-import-csv-column"
                                        data-field="Currency">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Email', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[email]" class="cs-import-csv-column" data-field="Email">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
						<tr>
							<td><?php esc_html_e( 'Name', 'commercestore' ); ?></td>
							<td>
								<select name="cs-import-field[name]" class="cs-import-csv-column"
										data-field="Name">
									<option value=""><?php esc_html_e( '- Ignore this field -', 'commercestore' ); ?></option>
								</select>
							</td>
							<td class="cs-import-preview-field"><?php esc_html_e( '- select field to preview data -', 'commercestore' ); ?></td>
						</tr>
                        <tr>
                            <td><?php _e( 'First Name', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[first_name]" class="cs-import-csv-column"
                                        data-field="First Name">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Last Name', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[last_name]" class="cs-import-csv-column"
                                        data-field="Last Name">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Customer ID', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[customer_id]" class="cs-import-csv-column"
                                        data-field="Customer ID">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Discount Code(s)', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[discounts]" class="cs-import-csv-column"
                                        data-field="Discount Code">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'IP Address', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[ip]" class="cs-import-csv-column"
                                        data-field="IP Address">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Mode (Live|Test)', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[mode]" class="cs-import-csv-column"
                                        data-field="Mode (Live|Test)">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Parent Payment ID', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[parent_payment_id]" class="cs-import-csv-column"
                                        data-field="">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Payment Method', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[gateway]" class="cs-import-csv-column"
                                        data-field="Payment Method">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Payment Number', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[number]" class="cs-import-csv-column"
                                        data-field="Payment Number">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Date', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[date]" class="cs-import-csv-column" data-field="Date">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Purchase Key', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[key]" class="cs-import-csv-column"
                                        data-field="Purchase Key">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Purchased Product(s)', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[downloads]" class="cs-import-csv-column"
                                        data-field="Products (Raw)">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Status', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[status]" class="cs-import-csv-column"
                                        data-field="Status">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Subtotal', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[subtotal]" class="cs-import-csv-column" data-field="">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Tax', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[tax]" class="cs-import-csv-column" data-field="Tax ($)">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Total', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[total]" class="cs-import-csv-column"
                                        data-field="Amount ($)">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Transaction ID', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[transaction_id]" class="cs-import-csv-column"
                                        data-field="Transaction ID">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'User', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[user_id]" class="cs-import-csv-column"
                                        data-field="User">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Address Line 1', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[line1]" class="cs-import-csv-column"
                                        data-field="Address">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Address Line 2', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[line2]" class="cs-import-csv-column"
                                        data-field="Address (Line 2)">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'City', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[city]" class="cs-import-csv-column" data-field="City">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'State / Province', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[state]" class="cs-import-csv-column" data-field="State">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Zip / Postal Code', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[zip]" class="cs-import-csv-column"
                                        data-field="Zip / Postal Code">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Country', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[country]" class="cs-import-csv-column"
                                        data-field="Country">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        </tbody>
                    </table>
                    <p class="submit">
						<button class="button cs-import-proceed button-primary"><?php esc_html_e( 'Process Import', 'commercestore' ); ?></button>
                    </p>
                </div>
            </form>
        </div><!-- .inside -->
    </div><!-- .postbox -->

    <div class="postbox cs-import-payment-history">
        <h3><span><?php _e( 'Import Download Products', 'commercestore' ); ?></span></h3>
        <div class="inside">
            <p><?php _e( 'Import a CSV file of products.', 'commercestore' ); ?></p>
            <form id="cs-import-downloads" class="cs-import-form cs-import-export-form"
                  action="<?php echo esc_url( add_query_arg( 'cs_action', 'upload_import_file', admin_url() ) ); ?>"
                  method="post" enctype="multipart/form-data">

                <div class="cs-import-file-wrap">
					<?php wp_nonce_field( 'cs_ajax_import', 'cs_ajax_import' ); ?>
                    <input type="hidden" name="cs-import-class" value="CS_Batch_Downloads_Import"/>
                    <p>
                        <input name="cs-import-file" id="cs-downloads-import-file" type="file"/>
                    </p>
                    <span>
						<input type="submit" value="<?php _e( 'Import CSV', 'commercestore' ); ?>"
                               class="button-secondary"/>
						<span class="spinner"></span>
					</span>
                </div>

                <div class="cs-import-options" id="cs-import-downloads-options" style="display:none;">

                    <p>
						<?php
						printf(
							__( 'Each column loaded from the CSV needs to be mapped to a Download product field. Select the column that should be mapped to each field below. Any columns not needed can be ignored. See <a href="%s" target="_blank">this guide</a> for assistance with importing Download products.', 'commercestore' ),
							'http://docs.commercestore.com/category/1337-importexport'
						);
						?>
                    </p>

                    <table class="widefat cs_repeatable_table striped" width="100%" cellpadding="0" cellspacing="0">
                        <thead>
                        <tr>
                            <th><strong><?php _e( 'Product Field', 'commercestore' ); ?></strong></th>
                            <th><strong><?php _e( 'CSV Column', 'commercestore' ); ?></strong></th>
                            <th><strong><?php _e( 'Data Preview', 'commercestore' ); ?></strong></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td><?php _e( 'Product Author', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[post_author]" class="cs-import-csv-column"
                                        data-field="Author">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Product Categories', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[categories]" class="cs-import-csv-column"
                                        data-field="Categories">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Product Creation Date', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[post_date]" class="cs-import-csv-column"
                                        data-field="Date Created">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Product Description', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[post_content]" class="cs-import-csv-column"
                                        data-field="Description">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Product Excerpt', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[post_excerpt]" class="cs-import-csv-column"
                                        data-field="Excerpt">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Product Image', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[featured_image]" class="cs-import-csv-column"
                                        data-field="Featured Image">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Product Notes', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[notes]" class="cs-import-csv-column" data-field="Notes">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Product Price(s)', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[price]" class="cs-import-csv-column" data-field="Price">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Product SKU', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[sku]" class="cs-import-csv-column" data-field="SKU">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Product Slug', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[post_name]" class="cs-import-csv-column"
                                        data-field="Slug">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Product Status', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[post_status]" class="cs-import-csv-column"
                                        data-field="Status">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Product Tags', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[tags]" class="cs-import-csv-column" data-field="Tags">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Product Title', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[post_title]" class="cs-import-csv-column"
                                        data-field="Name">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Download Files', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[files]" class="cs-import-csv-column" data-field="Files">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'File Download Limit', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[download_limit]" class="cs-import-csv-column"
                                        data-field="File Download Limit">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Sale Count', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[sales]" class="cs-import-csv-column" data-field="Sales">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        <tr>
                            <td><?php _e( 'Total Earnings', 'commercestore' ); ?></td>
                            <td>
                                <select name="cs-import-field[earnings]" class="cs-import-csv-column"
                                        data-field="Earnings">
                                    <option value=""><?php _e( '- Ignore this field -', 'commercestore' ); ?></option>
                                </select>
                            </td>
                            <td class="cs-import-preview-field"><?php _e( '- select field to preview data -', 'commercestore' ); ?></td>
                        </tr>
                        </tbody>
                    </table>
                    <p class="submit">
						<button class="button cs-import-proceed button-primary"><?php esc_html_e( 'Process Import', 'commercestore' ); ?></button>
                    </p>
                </div>
            </form>
        </div><!-- .inside -->
    </div><!-- .postbox -->

    <div class="postbox">
        <h3><span><?php _e( 'Export Settings', 'commercestore' ); ?></span></h3>
        <div class="inside">
            <p><?php _e( 'Export the CommerceStore settings for this site as a .json file. This allows you to easily import the configuration into another site.', 'commercestore' ); ?></p>
            <p><?php printf( __( 'To export shop data (purchases, customers, etc), visit the <a href="%s">Reports</a> page.', 'commercestore' ), admin_url( 'edit.php?post_type=download&page=cs-reports&tab=export' ) ); ?></p>
            <form method="post"
                  action="<?php echo admin_url( 'edit.php?post_type=download&page=cs-tools&tab=import_export' ); ?>">
                <p><input type="hidden" name="cs_action" value="export_settings"/></p>
                <p>
					<?php wp_nonce_field( 'cs_export_nonce', 'cs_export_nonce' ); ?>
					<?php submit_button( __( 'Export', 'commercestore' ), 'secondary', 'submit', false ); ?>
                </p>
            </form>
        </div><!-- .inside -->
    </div><!-- .postbox -->

    <div class="postbox">
        <h3><span><?php _e( 'Import Settings', 'commercestore' ); ?></span></h3>
        <div class="inside">
            <p><?php _e( 'Import the CommerceStore settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.', 'commercestore' ); ?></p>
            <form method="post" enctype="multipart/form-data"
                  action="<?php echo admin_url( 'edit.php?post_type=download&page=cs-tools&tab=import_export' ); ?>">
                <p>
                    <input type="file" name="import_file"/>
                </p>
                <p>
                    <input type="hidden" name="cs_action" value="import_settings"/>
					<?php wp_nonce_field( 'cs_import_nonce', 'cs_import_nonce' ); ?>
					<?php submit_button( __( 'Import', 'commercestore' ), 'secondary', 'submit', false ); ?>
                </p>
            </form>
        </div><!-- .inside -->
    </div><!-- .postbox -->
	<?php
	do_action( 'cs_tools_import_export_after' );
}
add_action( 'cs_tools_tab_import_export', 'cs_tools_import_export_display' );

/**
 * Process a settings export that generates a .json file of the shop settings
 *
 * @since 1.7
 */
function cs_tools_import_export_process_export() {

	// Bail if no nonce
	if ( empty( $_POST['cs_export_nonce'] ) ) {
		return;
	}

	// Bail if nonce does not verify
	if ( ! wp_verify_nonce( $_POST['cs_export_nonce'], 'cs_export_nonce' ) ) {
		return;
	}

	// Bail if user cannot manage shop
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	/**
	 * Filter the settings export filename
	 *
	 * @since 1.7
	 *
	 * @param string $filename The file name to export settings to
	 */
	$filename      = apply_filters( 'cs_settings_export_filename', 'cs-settings-export-' . date( 'm-d-Y' ) ) . '.json';
	$cs_settings  = get_option( 'cs_settings' );
	$cs_tax_rates = get_option( 'cs_tax_rates' );

	cs_set_time_limit();

	nocache_headers();

	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Expires: 0' );

	wp_send_json( array(
		'cs_settings'  => $cs_settings,
		'cs_tax_rates' => $cs_tax_rates
	) );
}
add_action( 'cs_export_settings', 'cs_tools_import_export_process_export' );

/**
 * Process a settings import from a json file
 *
 * @since 1.7
 * @return void
 */
function cs_tools_import_export_process_import() {

	if ( empty( $_POST['cs_import_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( $_POST['cs_import_nonce'], 'cs_import_nonce' ) ) {
		return;
	}

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	if ( cs_get_file_extension( $_FILES['import_file']['name'] ) != 'json' ) {
		wp_die( __( 'Please upload a valid .json file', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 400 ) );
	}

	$import_file = $_FILES['import_file']['tmp_name'];

	if ( empty( $import_file ) ) {
		wp_die( __( 'Please upload a file to import', 'commercestore' ), __( 'Error', 'commercestore' ), array( 'response' => 400 ) );
	}

	// Retrieve the settings from the file and convert the json object to an array
	$settings = cs_object_to_array( json_decode( file_get_contents( $import_file ) ) );

	if ( ! isset( $settings['cs_settings'] ) ) {

		// Process a settings export from a pre 2.8 version of CS
		update_option( 'cs_settings', $settings );

	} else {

		// Update the settings from a 2.8+ export file
		$cs_settings = $settings['cs_settings'];
		update_option( 'cs_settings', $cs_settings );

		$cs_tax_rates = $settings['cs_tax_rates'];
		update_option( 'cs_tax_rates', $cs_tax_rates );

	}

	cs_redirect( cs_get_admin_url( array(
		'page'        => 'cs-tools',
		'cs-message' => 'settings-imported',
		'tab'         => 'import_export',
	) ) );
}
add_action( 'cs_import_settings', 'cs_tools_import_export_process_import' );

/**
 * Display the debug log tab
 *
 * @since       2.8.7
 */
function cs_tools_debug_log_display() {
	$cs_logs = CS()->debug_log;

	// Setup fallback incase no file exists
	$path        = $cs_logs->get_log_file_path();
	$log         = $cs_logs->get_file_contents();
	$path_output = ! empty( $path )
		? wp_normalize_path( $path )
		: esc_html__( 'No File', 'commercestore' );
	$log_output  = ! empty( $log )
		? wp_normalize_path( $log )
		: esc_html__( 'Log is Empty', 'commercestore' ); ?>

    <div class="postbox">
        <h3><span><?php esc_html_e( 'Debug Log', 'commercestore' ); ?></span></h3>
        <div class="inside">
            <form id="cs-debug-log" method="post">
                <p><?php _e( 'When debug mode is enabled, specific information will be logged here. (<a href="https://github.com/commercestore/commercestore/blob/master/includes/class-cs-logging.php">Learn how</a> to use <code>CS_Logging</code> in your own code.)', 'commercestore' ); ?></p>
                <textarea
					readonly="readonly"
					class="cs-tools-textarea"
					rows="15"
					name="cs-debug-log-contents"><?php echo esc_textarea( $log_output ); ?></textarea>
                <p>
                    <input type="hidden" name="cs_action" value="submit_debug_log"/>
					<?php
					submit_button( __( 'Download Debug Log File', 'commercestore' ), 'primary',                     'cs-download-debug-log', false );
					submit_button( __( 'Copy to Clipboard',       'commercestore' ), 'secondary cs-inline-button', 'cs-copy-debug-log',     false, array( 'onclick' => "this.form['cs-debug-log-contents'].focus();this.form['cs-debug-log-contents'].select();document.execCommand('copy');return false;" ) );

					// Only show the "Clear Log" button if there is a log to clear
					if ( ! empty( $log ) ) {
						submit_button( __( 'Clear Log', 'commercestore' ), 'secondary cs-inline-button', 'cs-clear-debug-log', false );
					}

					?>
                </p>
				<?php wp_nonce_field( 'cs-debug-log-action' ); ?>
            </form>

            <p>
				<?php _e( 'Log file', 'commercestore' ); ?>:
                <code><?php echo esc_html( $path_output ); ?></code>
			</p>
        </div><!-- .inside -->
    </div><!-- .postbox -->

	<?php
}
add_action( 'cs_tools_tab_debug_log', 'cs_tools_debug_log_display' );

/**
 * Handles submit actions for the debug log.
 *
 * @since 2.8.7
 */
function cs_handle_submit_debug_log() {
	$cs_logs = CS()->debug_log;

	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	check_admin_referer( 'cs-debug-log-action' );

	if ( isset( $_REQUEST['cs-download-debug-log'] ) ) {
		nocache_headers();

		header( 'Content-Type: text/plain' );
		header( 'Content-Disposition: attachment; filename="cs-debug-log.txt"' );

		echo wp_strip_all_tags( $_REQUEST['cs-debug-log-contents'] );
		exit;

	} elseif ( isset( $_REQUEST['cs-clear-debug-log'] ) ) {

		// Clear the debug log.
		$cs_logs->clear_log_file();

		cs_redirect( cs_get_admin_url( array(
			'page' => 'cs-tools',
			'tab'  => 'debug_log'
		) ) );
	}
}
add_action( 'cs_submit_debug_log', 'cs_handle_submit_debug_log' );

/**
 * Display the system info tab
 *
 * @since 2.0
 */
function cs_tools_sysinfo_display() {
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	?>

	<div class="postbox">
		<h3><span><?php esc_html_e( 'System Information', 'commercestore' ); ?></span></h3>
		<div class="inside">
			<p>
				<?php esc_html_e( 'Use the system information below to help troubleshoot problems.', 'commercestore' ); ?>
			</p>

			<form id="cs-system-info" action="<?php echo esc_url( admin_url( 'edit.php?post_type=download&page=cs-tools&tab=system_info' ) ); ?>" method="post" dir="ltr">
				<textarea readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" class="cs-tools-textarea" name="cs-sysinfo"
					><?php echo cs_tools_sysinfo_get(); ?></textarea>

				<p>
					<input type="hidden" name="cs-action" value="download_sysinfo"/>
					<?php
					submit_button( __( 'Download System Info File', 'commercestore' ), 'primary', 'cs-download-sysinfo', false );
					submit_button( __( 'Copy to Clipboard',         'commercestore' ), 'secondary cs-inline-button', 'cs-copy-system-info', false, array( 'onclick' => "this.form['cs-sysinfo'].focus();this.form['cs-sysinfo'].select();document.execCommand('copy');return false;" ) );
					?>
				</p>
			</form>
		</div>
	</div>

	<?php
}
add_action( 'cs_tools_tab_system_info', 'cs_tools_sysinfo_display' );

/**
 * Get system info.
 *
 * @since 2.0
 *
 * @return string $return A string containing the info to output
 */
function cs_tools_sysinfo_get() {
	global $wpdb;

	if ( ! class_exists( 'Browser' ) ) {
		require_once CS_PLUGIN_DIR . 'includes/libraries/browser.php';
	}

	$browser = new Browser();

	// Get theme info
	$theme_data   = wp_get_theme();
	$theme        = $theme_data->Name . ' ' . $theme_data->Version;
	$parent_theme = $theme_data->Template;
	if ( ! empty( $parent_theme ) ) {
		$parent_theme_data = wp_get_theme( $parent_theme );
		$parent_theme      = $parent_theme_data->Name . ' ' . $parent_theme_data->Version;
	}

	// Try to identify the hosting provider
	$host = cs_get_host();

	$return  = '### Begin System Info (Generated ' . date( 'Y-m-d H:i:s' ) . ') ###' . "\n\n";

	// Start with the basics...
	$return .= '-- Site Info' . "\n\n";
	$return .= 'Site URL:                 ' . site_url() . "\n";
	$return .= 'Home URL:                 ' . home_url() . "\n";
	$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

	$return = apply_filters( 'cs_sysinfo_after_site_info', $return );

	// Can we determine the site's host?
	if ( $host ) {
		$return .= "\n" . '-- Hosting Provider' . "\n\n";
		$return .= 'Host:                     ' . $host . "\n";

		$return = apply_filters( 'cs_sysinfo_after_host_info', $return );
	}

	// The local users' browser information, handled by the Browser class
	$return .= "\n" . '-- User Browser' . "\n\n";
	$return .= $browser;

	$return = apply_filters( 'cs_sysinfo_after_user_browser', $return );

	$locale = get_locale();

	// WordPress configuration
	$return .= "\n" . '-- WordPress Configuration' . "\n\n";
	$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
	$return .= 'Language:                 ' . ( ! empty( $locale ) ? $locale : 'en_US' ) . "\n";
	$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
	$return .= 'Active Theme:             ' . $theme . "\n";
	if ( $parent_theme !== $theme ) {
		$return .= 'Parent Theme:             ' . $parent_theme . "\n";
	}
	$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

	// Only show page specs if frontpage is set to 'page'
	if ( get_option( 'show_on_front' ) == 'page' ) {
		$front_page_id = get_option( 'page_on_front' );
		$blog_page_id  = get_option( 'page_for_posts' );

		$return .= 'Page On Front:            ' . ( $front_page_id != 0 ? '#' . $front_page_id : 'Unset' ) . "\n";
		$return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? '#' . $blog_page_id : 'Unset' ) . "\n";
	}

	$return .= 'ABSPATH:                  ' . ABSPATH . "\n";

	// Make sure wp_remote_post() is working
	$request['cmd'] = '_notify-validate';

	$params = array(
		'sslverify'  => false,
		'timeout'    => 60,
		'user-agent' => 'CS/' . CS_VERSION,
		'body'       => $request,
	);

	$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

	if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
		$WP_REMOTE_POST = 'wp_remote_post() works';
	} else {
		$WP_REMOTE_POST = 'wp_remote_post() does not work';
	}

	$return .= 'Remote Post:              ' . $WP_REMOTE_POST . "\n";
	$return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
	// Commented out per https://github.com/commercestore/CommerceStore/issues/3475
	//$return .= 'Admin AJAX:               ' . ( cs_test_ajax_works() ? 'Accessible' : 'Inaccessible' ) . "\n";
	$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
	$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
	$return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";

	$return = apply_filters( 'cs_sysinfo_after_wordpress_config', $return );

	// CommerceStore configuration
	$return .= "\n" . '-- CommerceStore Configuration' . "\n\n";
	$return .= 'Version:                  ' . CS_VERSION . "\n";
	$return .= 'Upgraded From:            ' . get_option( 'cs_version_upgraded_from', 'None' ) . "\n";
	$return .= 'Test Mode:                ' . ( cs_is_test_mode() ? "Enabled\n" : "Disabled\n" );
	$return .= 'AJAX:                     ' . ( ! cs_is_ajax_disabled() ? "Enabled\n" : "Disabled\n" );
	$return .= 'Guest Checkout:           ' . ( cs_no_guest_checkout() ? "Disabled\n" : "Enabled\n" );
	$return .= 'Symlinks:                 ' . ( apply_filters( 'cs_symlink_file_downloads', cs_get_option( 'symlink_file_downloads', false ) ) && function_exists( 'symlink' ) ? "Enabled\n" : "Disabled\n" );
	$return .= 'Download Method:          ' . ucfirst( cs_get_file_download_method() ) . "\n";
	$return .= 'Currency Code:            ' . cs_get_currency() . "\n";
	$return .= 'Currency Position:        ' . cs_get_option( 'currency_position', 'before' ) . "\n";
	$return .= 'Decimal Separator:        ' . cs_get_option( 'decimal_separator', '.' ) . "\n";
	$return .= 'Thousands Separator:      ' . cs_get_option( 'thousands_separator', ',' ) . "\n";
	$return .= 'Upgrades Completed:       ' . implode( ',', cs_get_completed_upgrades() ) . "\n";
	$return .= 'Download Link Expiration: ' . cs_get_option( 'download_link_expiration' ) . " hour(s)\n";

	$return = apply_filters( 'cs_sysinfo_after_cs_config', $return );

	// CommerceStore Database tables
	$return .= "\n" . '-- CommerceStore Database Tables' . "\n\n";

	foreach ( CS()->components as $component ) {

		// Object
		$thing = $component->get_interface( 'table' );
		if ( ! empty( $thing ) ) {
			$return .= str_pad( $thing->name . ': ', 32, ' ' ) . $thing->get_version() . "\n";
		}

		// Meta
		$thing = $component->get_interface( 'meta' );
		if ( ! empty( $thing ) ) {
			$return .= str_pad( $thing->name . ': ', 32, ' ' ) . $thing->get_version() . "\n";
		}
	}

	$return = apply_filters( 'cs_sysinfo_after_cs_database_tables', $return );

	// CommerceStore Database tables
	$return .= "\n" . '-- CommerceStore Database Row Counts' . "\n\n";

	foreach ( CS()->components as $component ) {

		// Object
		$thing = $component->get_interface( 'table' );
		if ( ! empty( $thing ) ) {
			$return .= str_pad( $thing->name . ': ', 32, ' ' ) . $thing->count() . "\n";
		}

		// Meta
		$thing = $component->get_interface( 'meta' );
		if ( ! empty( $thing ) ) {
			$return .= str_pad( $thing->name . ': ', 32, ' ' ) . $thing->count() . "\n";
		}
	}

	$return = apply_filters( 'cs_sysinfo_after_cs_database_row_counts', $return );

	// CommerceStore pages
	$purchase_page = cs_get_option( 'purchase_page', '' );
	$success_page  = cs_get_option( 'success_page', '' );
	$failure_page  = cs_get_option( 'failure_page', '' );

	$return .= "\n" . '-- CommerceStore Page Configuration' . "\n\n";
	$return .= 'Checkout:                 ' . ( ! empty( $purchase_page ) ? "Valid\n" : "Invalid\n" );
	$return .= 'Checkout Page:            ' . ( ! empty( $purchase_page ) ? get_permalink( $purchase_page ) . "\n" : "Unset\n" );
	$return .= 'Success Page:             ' . ( ! empty( $success_page ) ? get_permalink( $success_page ) . "\n" : "Unset\n" );
	$return .= 'Failure Page:             ' . ( ! empty( $failure_page ) ? get_permalink( $failure_page ) . "\n" : "Unset\n" );
	$return .= 'Downloads Slug:           ' . ( defined( 'CS_SLUG' ) ? '/' . CS_SLUG . "\n" : CS_DEFAULT_SLUG . "\n" );

	$return = apply_filters( 'cs_sysinfo_after_cs_pages', $return );

	// CommerceStore gateways
	$return .= "\n" . '-- CommerceStore Gateway Configuration' . "\n\n";

	$active_gateways = cs_get_enabled_payment_gateways();
	if ( $active_gateways ) {
		$default_gateway_is_active = cs_is_gateway_active( cs_get_default_gateway() );
		if ( $default_gateway_is_active ) {
			$default_gateway = cs_get_default_gateway();
			$default_gateway = $active_gateways[ $default_gateway ]['admin_label'];
		} else {
			$default_gateway = 'Test Payment';
		}

		$gateways = array();
		foreach ( $active_gateways as $gateway ) {
			$gateways[] = $gateway['admin_label'];
		}

		$return .= 'Enabled Gateways:         ' . implode( ', ', $gateways ) . "\n";
		$return .= 'Default Gateway:          ' . $default_gateway . "\n";
	} else {
		$return .= 'Enabled Gateways:         None' . "\n";
	}

	$return = apply_filters( 'cs_sysinfo_after_cs_gateways', $return );


	// CommerceStore Taxes
	$return .= "\n" . '-- CommerceStore Tax Configuration' . "\n\n";
	$return .= 'Taxes:                    ' . ( cs_use_taxes() ? "Enabled\n" : "Disabled\n" );
	$return .= 'Default Rate:             ' . cs_get_formatted_tax_rate() . "\n";
	$return .= 'Display On Checkout:      ' . ( cs_get_option( 'checkout_include_tax', false ) ? "Displayed\n" : "Not Displayed\n" );
	$return .= 'Prices Include Tax:       ' . ( cs_prices_include_tax() ? "Yes\n" : "No\n" );

	$rates = cs_get_tax_rates();
	if ( ! empty( $rates ) ) {
		$return .= 'Country / State Rates:    ' . "\n";
		foreach ( $rates as $rate ) {
			$return .= '                          Country: ' . $rate['country'] . ', State: ' . $rate['state'] . ', Rate: ' . $rate['rate'] . "\n";
		}
	}

	$return = apply_filters( 'cs_sysinfo_after_cs_taxes', $return );

	// CommerceStore Templates
	$dir = get_stylesheet_directory() . '/cs_templates/*';
	if ( is_dir( $dir ) && ( count( glob( "$dir/*" ) ) !== 0 ) ) {
		$return .= "\n" . '-- CommerceStore Template Overrides' . "\n\n";

		foreach ( glob( $dir ) as $file ) {
			$return .= 'Filename:                 ' . basename( $file ) . "\n";
		}

		$return = apply_filters( 'cs_sysinfo_after_cs_templates', $return );
	}

	// Get plugins that have an update
	$updates = get_plugin_updates();

	// Must-use plugins
	// NOTE: MU plugins can't show updates!
	$muplugins = get_mu_plugins();
	if ( count( $muplugins ) > 0 ) {
		$return .= "\n" . '-- Must-Use Plugins' . "\n\n";

		foreach ( $muplugins as $plugin => $plugin_data ) {
			$return .= str_pad( $plugin_data['Name'] . ': ', 26, ' ' ) . $plugin_data['Version'] . "\n";
		}

		$return = apply_filters( 'cs_sysinfo_after_wordpress_mu_plugins', $return );
	}

	// WordPress active plugins
	$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";

	$plugins        = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( ! in_array( $plugin_path, $active_plugins ) ) {
			continue;
		}

		$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
		$plugin_url = '';
		if ( ! empty( $plugin['PluginURI'] ) ) {
			$plugin_url = $plugin['PluginURI'];
		} elseif ( ! empty( $plugin['AuthorURI'] ) ) {
			$plugin_url = $plugin['AuthorURI'];
		} elseif ( ! empty( $plugin['Author'] ) ) {
			$plugin_url = $plugin['Author'];
		}
		if ( $plugin_url ) {
			$plugin_url = "\n" . $plugin_url;
		}
		$return .= str_pad( $plugin['Name'] . ': ', 26, ' ' ) . $plugin['Version'] . $update . $plugin_url . "\n\n";
	}

	$return = apply_filters( 'cs_sysinfo_after_wordpress_plugins', $return );

	// WordPress inactive plugins
	$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

	foreach ( $plugins as $plugin_path => $plugin ) {
		if ( in_array( $plugin_path, $active_plugins ) ) {
			continue;
		}

		$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
		$plugin_url = '';
		if ( ! empty( $plugin['PluginURI'] ) ) {
			$plugin_url = $plugin['PluginURI'];
		} elseif ( ! empty( $plugin['AuthorURI'] ) ) {
			$plugin_url = $plugin['AuthorURI'];
		} elseif ( ! empty( $plugin['Author'] ) ) {
			$plugin_url = $plugin['Author'];
		}
		if ( $plugin_url ) {
			$plugin_url = "\n" . $plugin_url;
		}
		$return .= str_pad( $plugin['Name'] . ': ', 26, ' ' ) . $plugin['Version'] . $update . $plugin_url . "\n\n";
	}

	$return = apply_filters( 'cs_sysinfo_after_wordpress_plugins_inactive', $return );

	if ( is_multisite() ) {
		// WordPress Multisite active plugins
		$return .= "\n" . '-- Network Active Plugins' . "\n\n";

		$plugins        = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach ( $plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			if ( ! array_key_exists( $plugin_base, $active_plugins ) ) {
				continue;
			}

			$update = ( array_key_exists( $plugin_path, $updates ) ) ? ' (needs update - ' . $updates[ $plugin_path ]->update->new_version . ')' : '';
			$plugin = get_plugin_data( $plugin_path );
			$plugin_url = '';
			if ( ! empty( $plugin['PluginURI'] ) ) {
				$plugin_url = $plugin['PluginURI'];
			} elseif ( ! empty( $plugin['AuthorURI'] ) ) {
				$plugin_url = $plugin['AuthorURI'];
			} elseif ( ! empty( $plugin['Author'] ) ) {
				$plugin_url = $plugin['Author'];
			}
			if ( $plugin_url ) {
				$plugin_url = "\n" . $plugin_url;
			}
			$return .= str_pad( $plugin['Name'] . ': ', 26, ' ' ) . $plugin['Version'] . $update . $plugin_url . "\n\n";
		}

		$return = apply_filters( 'cs_sysinfo_after_wordpress_ms_plugins', $return );
	}

	// Server configuration (really just versioning)
	$return .= "\n" . '-- Webserver Configuration' . "\n\n";
	$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
	$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
	$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

	$return = apply_filters( 'cs_sysinfo_after_webserver_config', $return );

	// PHP configs... now we're getting to the important stuff
	$return .= "\n" . '-- PHP Configuration' . "\n\n";
	$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
	$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
	$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
	$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
	$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";
	$return .= 'PHP Arg Separator:        ' . cs_get_php_arg_separator_output() . "\n";

	$return = apply_filters( 'cs_sysinfo_after_php_config', $return );

	// PHP extensions and such
	$return .= "\n" . '-- PHP Extensions' . "\n\n";
	$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
	$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

	$return = apply_filters( 'cs_sysinfo_after_php_ext', $return );

	// Session stuff
	$return .= "\n" . '-- Session Configuration' . "\n\n";
	$return .= 'CS Use Sessions:         ' . ( defined( 'CS_USE_PHP_SESSIONS' ) && CS_USE_PHP_SESSIONS ? 'Enforced' : ( CS()->session->use_php_sessions() ? 'Enabled' : 'Disabled' ) ) . "\n";
	$return .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

	// The rest of this is only relevant is session is enabled
	if ( isset( $_SESSION ) ) {
		$return .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "\n";
		$return .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "\n";
		$return .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "\n";
		$return .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "\n";
		$return .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "\n";
	}

	$return = apply_filters( 'cs_sysinfo_after_session_config', $return );

	$return .= "\n" . '### End System Info ###';

	return $return;
}

/**
 * Generates a System Info download file
 *
 * @since 2.0
 */
function cs_tools_sysinfo_download() {
	if ( ! current_user_can( 'manage_shop_settings' ) ) {
		return;
	}

	nocache_headers();

	header( 'Content-Type: text/plain' );
	header( 'Content-Disposition: attachment; filename="cs-system-info.txt"' );

	echo wp_strip_all_tags( $_POST['cs-sysinfo'] );
	cs_die();
}
add_action( 'cs_download_sysinfo', 'cs_tools_sysinfo_download' );

/**
 * Redirects requests to the old sales log to the orders page.
 *
 * @since 3.0
 */
function cs_redirect_sales_log() {
	if ( cs_is_admin_page( 'tools', 'logs' ) && ! empty( $_GET['view'] ) && 'sales' === $_GET['view'] ) {
		$query_args = array(
			'page' => 'cs-payment-history'
		);

		$args_to_remap = array(
			CS_POST_TYPE   => 'product-id',
			'start-date' => 'start-date',
			'end-date'   => 'end-date'
		);

		foreach( $args_to_remap as $old_arg => $new_arg ) {
			if ( ! empty( $_GET[ $old_arg ] ) ) {
				$query_args[ $new_arg ] = urlencode( $_GET[ $old_arg ] );
			}
		}

		wp_safe_redirect( esc_url_raw( add_query_arg( $query_args, cs_get_admin_base_url() ) ) );
		exit;
	}
}
add_action( 'admin_init', 'cs_redirect_sales_log' );

/**
 * Renders the Logs tab in the Tools screen.
 *
 * @since 3.0
 */
function cs_tools_tab_logs() {
	if ( ! current_user_can( 'view_shop_reports' ) ) {
		return;
	}

	require_once CS_PLUGIN_DIR . 'includes/admin/tools/logs.php';

	$current_view = 'file_downloads';
	$log_views    = cs_log_default_views();

	if ( isset( $_GET['view'] ) && array_key_exists( $_GET['view'], $log_views ) ) {
		$current_view = sanitize_text_field( $_GET['view'] );
	}

	/**
	 * Fires when a given logs view should be rendered.
	 *
	 * The dynamic portion of the hook name, `$current_view`, represents the slug
	 * of the logs view to render.
	 *
	 * @since 1.4
	 */
	do_action( 'cs_logs_view_' . $current_view );
}
add_action( 'cs_tools_tab_logs', 'cs_tools_tab_logs' );
