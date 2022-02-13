<?php
/**
 * Add a button to wp_editor() instances to allow easier tag insertion.
 *
 * @package     CS
 * @subpackage  Email
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Get registered emails.
 *
 * This assumes emails are "registered" by using a section in the Emails tab.
 *
 * @since 3.0
 *
 * @return array $emails Registered emails.
 */
function cs_email_tags_inserter_get_registered_emails() {
	$settings = cs_get_registered_settings();
	$emails   = $settings['emails'];

	unset( $emails['main'] );

	return array_keys( $emails );
}

/**
 * Wait until the admin has loaded (so cs_get_registered_settings() works)
 * and hook in to WordPress for each registered email.
 *
 * @since 3.0
 */
function cs_email_tags_inserter_register() {
	foreach ( cs_email_tags_inserter_get_registered_emails() as $email ) {

		// Add Thickbox button.
		add_action( 'cs_settings_tab_top_emails_' . $email, 'cs_email_tags_inserter_media_button' );

		// Output Thickbox content.
		add_action( 'cs_settings_tab_top_emails_' . $email, 'cs_email_tags_inserter_thickbox_content' );

		// Enqueue scripts.
		add_action( 'cs_settings_tab_top_emails_' . $email, 'cs_email_tags_inserter_enqueue_scripts' );
	}
}
add_action( 'admin_menu', 'cs_email_tags_inserter_register' );

/**
 * Wait until `media_buttons` action is called.
 *
 * @see cs_email_tags_inserter_media_button_output()
 *
 * @since 3.0
 */
function cs_email_tags_inserter_media_button() {
	add_action( 'media_buttons', 'cs_email_tags_inserter_media_button_output' );
}

/**
 * Adds an 'Insert Marker' button above the TinyMCE Editor on email-related
 * `wp_editor()` instances.
 *
 * @since 3.0
 */
function cs_email_tags_inserter_media_button_output() {
	?>
	<a href="#TB_inline?width=640&inlineId=cs-insert-email-tag" class="cs-email-tags-inserter thickbox button cs-thickbox" style="padding-left: 0.4em;">
		<span class="wp-media-buttons-icon dashicons dashicons-editor-code"></span>
		<?php esc_html_e( 'Insert Marker', 'commercestore' ); ?>
	</a>
	<?php
}

/**
 * Enqueue scripts for clicking a tag inside of Thickbox.
 *
 * @since 3.0
 */
function cs_email_tags_inserter_enqueue_scripts() {

	wp_enqueue_style( 'cs-admin-email-tags' );
	wp_enqueue_script( 'cs-admin-email-tags' ) ;

	// Send information about tags to script.
	$items = array();
	$tags  = cs_get_email_tags();

	foreach ( $tags as $tag ) {
		$items[] = array(
			'title'    => $tag['label'] ? $tag['label'] : $tag['tag'],
			'tag'      => $tag['tag'],
			'keywords' => array_merge(
				explode( ' ', $tag['description'] ),
				array( $tag['tag'] )
			),
		);
	}

	wp_localize_script(
		'cs-admin-email-tags',
		'csEmailTagsInserter',
		array(
			'items' => $items,
		)
	);
}

/**
 * Output Thickbox content.
 *
 * @since 3.0
 */
function cs_email_tags_inserter_thickbox_content() {
	$tags = cs_get_email_tags();
	?>
	<div id="cs-insert-email-tag" style="display: none;">
		<div class="cs-email-tags-filter">
			<input type="search" class="cs-email-tags-filter-search" placeholder="<?php echo esc_attr( __( 'Find a tag...', 'commercestore' ) ); ?>" />
		</div>

		<ul class="cs-email-tags-list">
			<?php foreach ( $tags as $tag ) : ?>
			<li id="<?php echo esc_attr( $tag['tag'] ); ?>" data-tag="<?php echo esc_attr( $tag['tag'] ); ?>" class="cs-email-tags-list-item">
				<button class="cs-email-tags-list-button" data-to_insert="{<?php echo esc_attr( $tag['tag'] ); ?>}">
					<strong><?php echo esc_html( $tag['label'] ); ?></strong><code><?php echo '{' . esc_html( $tag['tag'] ) . '}'; ?></code>
					<span><?php echo esc_html( $tag['description'] ); ?></span>
				</button>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php
}
