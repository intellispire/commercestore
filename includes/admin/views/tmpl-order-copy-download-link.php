<?php
/**
 * Order Overview: Copy Download Links
 *
 * @package     CS
 * @subpackage  Admin/Views
 * @copyright   Copyright (c) 2020, Sandhills Development, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */
?>

<div class="cs-order-overview-modal">
	<form class="cs-order-copy-download-link">

		<p>
			<label for="link">
				<?php echo esc_html( sprintf( __( '%s Links', 'commercestore' ), cs_get_label_singular() ) ); ?>
			</label>

			<# if ( false === data.link ) { #>
				<span class="spinner is-active" style="float: none; margin: 0;"></span>
			<# } else if ( '' === data.link ) { #>
				<?php esc_html_e( 'No file links available', 'commercestore' ); ?>
			<# } else { #>
				<textarea rows="10" id="link">{{ data.link }}</textarea>
			<# } #>
		</p>

		<p class="submit">
			<input
				id="close"
				type="submit"
				class="button button-primary cs-ml-auto"
				value="<?php esc_html_e( 'Close', 'commercestore' ); ?>"
			/>
		</p>
	</form>
</div>
