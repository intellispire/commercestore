<?php
/**
 * A single download inside of the [downloads] shortcode.
 *
 * @since 2.8.0
 *
 * @package CS
 * @category Template
 * @author CommerceStore
 * @version 1.0.0
 */

global $cs_download_shortcode_item_atts, $cs_download_shortcode_item_i;
?>

<div class="<?php echo esc_attr( apply_filters( 'cs_download_class', 'cs_download', get_the_ID(), $cs_download_shortcode_item_atts, $cs_download_shortcode_item_i ) ); ?>" id="cs_download_<?php the_ID(); ?>">

	<div class="<?php echo esc_attr( apply_filters( 'cs_download_inner_class', 'cs_download_inner', get_the_ID(), $cs_download_shortcode_item_atts, $cs_download_shortcode_item_i ) ); ?>">

		<?php
			do_action( 'cs_download_before' );

			if ( 'false' !== $cs_download_shortcode_item_atts['thumbnails'] ) :
				cs_get_template_part( 'shortcode', 'content-image' );
				do_action( 'cs_download_after_thumbnail' );
			endif;

			cs_get_template_part( 'shortcode', 'content-title' );

			do_action( 'cs_download_after_title' );

			if ( 'yes' === $cs_download_shortcode_item_atts['excerpt'] && 'yes' !== $cs_download_shortcode_item_atts['full_content'] ) :
				cs_get_template_part( 'shortcode', 'content-excerpt' );
				do_action( 'cs_download_after_content' );
			elseif ( 'yes' === $cs_download_shortcode_item_atts['full_content'] ) :
				cs_get_template_part( 'shortcode', 'content-full' );
				do_action( 'cs_download_after_content' );
			endif;

			if ( 'yes' === $cs_download_shortcode_item_atts['price'] ) :
				cs_get_template_part( 'shortcode', 'content-price' );
				do_action( 'cs_download_after_price' );
			endif;

			if ( 'yes' === $cs_download_shortcode_item_atts['buy_button'] ) :
				cs_get_template_part( 'shortcode', 'content-cart-button' );
			endif;

			do_action( 'cs_download_after' );
		?>

	</div>

</div>
