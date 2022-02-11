<li class="cs-cart-item">
	<span class="cs-cart-item-title">{item_title}</span>
	<span class="cs-cart-item-separator">-</span>&nbsp;<?php echo cs_item_quantities_enabled() ? '<span class="cs-cart-item-quantity">{item_quantity}&nbsp;@&nbsp;</span>' : ''; ?><span class="cs-cart-item-price">{item_amount}</span>&nbsp;<span class="cs-cart-item-separator">-</span>
	<a href="{remove_url}" data-nonce="<?php echo wp_create_nonce( 'cs-remove-cart-widget-item' ); ?>" data-cart-item="{cart_item_id}" data-download-id="{item_id}" data-action="cs_remove_from_cart" class="cs-remove-from-cart"><?php _e( 'remove', 'commercestore' ); ?></a>
</li>
