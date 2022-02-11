<div id="cs-payment-processing">
	<p><?php printf( __( 'Your purchase is processing. This page will reload automatically in 8 seconds. If it does not, click <a href="%s">here</a>.', 'commercestore' ), cs_get_success_page_uri() ); ?>
	<span class="cs-cart-ajax"><span class="cs-icon-spinner cs-icon-spin"></span></span>
	<script type="text/javascript">setTimeout(function(){ window.location = '<?php echo cs_get_success_page_uri(); ?>'; }, 8000);</script>
</div>
