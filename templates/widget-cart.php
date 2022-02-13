<?php
/**
 * This template is used to display the Downloads cart widget.
 */
$cart_items    = cs_get_cart_contents();
$cart_quantity = cs_get_cart_quantity();
$display       = $cart_quantity > 0 ? '' : ' style="display:none;"';
?>
<p class="cs-cart-number-of-items"<?php echo $display; ?>><?php _e( 'Number of items in cart', 'commercestore' ); ?>: <span class="cs-cart-quantity"><?php echo $cart_quantity; ?></span></p>
<ul class="cs-cart">
<?php if( $cart_items ) : ?>

	<?php foreach( $cart_items as $key => $item ) : ?>

		<?php echo cs_get_cart_item_template( $key, $item, false ); ?>

	<?php endforeach; ?>

	<?php cs_get_template_part( 'widget', 'cart-checkout' ); ?>

<?php else : ?>

	<?php cs_get_template_part( 'widget', 'cart-empty' ); ?>

<?php endif; ?>
</ul>
