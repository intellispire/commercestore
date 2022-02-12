<?php
/**
 * Thickbox
 *
 * @package     CS
 * @subpackage  Admin
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Adds an "Insert Download" button above the TinyMCE Editor on add/edit screens.
 *
 * @since 1.0
 * @return string "Insert Download" Button
 */
function cs_media_button() {

	// Bail if not a post new/edit screen
	if ( ! cs_is_insertable_admin_page() ) {
		return;
	}

	// Setup the icon
	$icon = '<span class="wp-media-buttons-icon dashicons dashicons-download" id="cs-media-button"></span>';
	$text = sprintf( __( 'Insert %s', 'commercestore' ), cs_get_label_singular() );

	// Output the thickbox button
	echo '<a href="#TB_inline?&width=600&height=300&inlineId=choose-download" name="' . esc_attr( $text ) . '" class="thickbox button cs-thickbox">' . $icon . esc_html( $text ) . '</a>';
}
add_action( 'media_buttons', 'cs_media_button', 11 );

/**
 * Admin Footer For Thickbox
 *
 * Prints the footer code needed for the Insert Download
 * TinyMCE button.
 *
 * @since 1.0
 * @global $pagenow
 * @global $typenow
 * @return void
 */
function cs_admin_footer_for_thickbox() {

	// Bail if not a post new/edit screen
	if ( ! cs_is_insertable_admin_page() ) {
		return;
	}

	// Styles
	$styles = array(
		'text link' => esc_html__( 'Link',   'commercestore' ),
		'button'    => esc_html__( 'Button', 'commercestore' )
	);

	// Colors
	$colors = cs_get_button_colors();

	?>

	<script type="text/javascript">

		/**
		 * Used to insert the download shortcode with attributes
		 */
		function insertDownload() {
			var id     = jQuery('#products').val(),
				direct = jQuery('#select-cs-direct').val(),
				style  = jQuery('#select-cs-style').val(),
				color  = jQuery('#select-cs-color').is(':visible') ? jQuery( '#select-cs-color').val() : '',
				text   = jQuery('#cs-text').val() || '<?php _e( 'Purchase', 'commercestore' ); ?>';

			// Return early if no download is selected
			if ( '' === id ) {
				alert('<?php _e( 'You must choose a download', 'commercestore' ); ?>');
				return;
			}

			if ( '2' === direct ) {
				direct = ' direct="true"';
			} else {
				direct = '';
			}

			// Send the shortcode to the editor
			window.send_to_editor('[purchase_link id="' + id + '" style="' + style + '" color="' + color + '" text="' + text + '"' + direct +']');
		}

		jQuery(document).ready(function ($) {
			$('#select-cs-style').change(function () {
				( $(this).val() === 'button' )
					? $('#cs-color-choice').show()
					: $('#cs-color-choice').hide();
			});
		});
	</script>

	<div id="choose-download" style="display: none;">
		<div id="choose-download-wrapper">
			<div class="wrap">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row" valign="top">
								<?php echo cs_get_label_singular(); ?>
							</th>
							<td>
								<?php echo CS()->html->product_dropdown( array( 'chosen' => true ) ); ?>
								<p class="description"><?php esc_html_e( 'Choose an existing product', 'commercestore' ); ?></p>
							</td>
						</tr>

						<?php if ( cs_shop_supports_buy_now() ) : ?>
							<tr>
								<th scope="row" valign="top">
									<?php esc_html_e( 'Behavior', 'commercestore' ); ?>
								</th>
								<td>
									<select id="select-cs-direct">
										<option value="1"><?php _e( 'Add to Cart', 'commercestore' ); ?></option>
										<option value="2"><?php _e( 'Direct Link', 'commercestore' ); ?></option>
									</select>
									<p class="description"><?php esc_html_e( 'How do you want this to work?', 'commercestore' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>

						<tr>
							<th scope="row" valign="top">
								<?php esc_html_e( 'Style', 'commercestore' ); ?>
							</th>
							<td>
								<select id="select-cs-style">
									<?php
										foreach ( $styles as $style => $label ) {
											echo '<option value="' . esc_attr( $style ) . '">' . esc_html( $label ) . '</option>';
										}
									?>
								</select>
								<p class="description"><?php esc_html_e( 'Choose between a Button or a Link', 'commercestore' ); ?></p>
							</td>
						</tr>

						<?php if ( ! empty( $colors ) ) : ?>
							<tr id="cs-color-choice" style="display: none;">
								<th scope="row" valign="top">
									<?php esc_html_e( 'Color', 'commercestore' ); ?>
								</th>
								<td>
									<select id="select-cs-color">
										<?php
											foreach ( $colors as $key => $color ) {
												echo '<option value="' . str_replace( ' ', '_', $key ) . '">' . $color['label'] . '</option>';
											}
										?>
									</select>
									<p class="description"><?php esc_html_e( 'Choose the button color', 'commercestore' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>

						<tr>
							<th scope="row" valign="top">
								<?php esc_html_e( 'Text', 'commercestore' ); ?>
							</th>
							<td>
								<input type="text" class="regular-text" id="cs-text" value="" placeholder="<?php _e( 'View Product', 'commercestore' ); ?>"/>
								<p class="description"><?php esc_html_e( 'This is the text inside the button or link', 'commercestore' ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			<div class="submit-wrapper">
				<div>
					<a id="cs-cancel-download-insert" class="button" onclick="tb_remove();"><?php _e( 'Cancel', 'commercestore' ); ?></a>
					<input type="button" id="cs-insert-download" class="button-primary" value="<?php echo sprintf( __( 'Insert %s', 'commercestore' ), cs_get_label_singular() ); ?>" onclick="insertDownload();" />
				</div>
			</div>
		</div>
	</div>

<?php
}
add_action( 'admin_footer', 'cs_admin_footer_for_thickbox' );
