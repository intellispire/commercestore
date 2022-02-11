<li class="cart_item empty"><?php echo cs_empty_cart_message(); ?></li>
<?php if ( cs_use_taxes() ) : ?>
<li class="cart_item cs-cart-meta cs_subtotal" style="display:none;"><?php echo __( 'Subtotal:', 'commercestore' ). " <span class='subtotal'>" . cs_currency_filter( cs_format_amount( cs_get_cart_subtotal() ) ); ?></span></li>
<li class="cart_item cs-cart-meta cs_cart_tax" style="display:none;"><?php _e( 'Estimated Tax:', 'commercestore' ); ?> <span class="cart-tax"><?php echo cs_currency_filter( cs_format_amount( cs_get_cart_tax() ) ); ?></span></li>
<?php endif; ?>
<li class="cart_item cs-cart-meta cs_total" style="display:none;"><?php _e( 'Total:', 'commercestore' ); ?> <span class="cart-total"><?php echo cs_currency_filter( cs_format_amount( cs_get_cart_total() ) ); ?></span></li>
<li class="cart_item cs_checkout" style="display:none;"><a href="<?php echo cs_get_checkout_uri(); ?>"><?php _e( 'Checkout', 'commercestore' ); ?></a></li>
