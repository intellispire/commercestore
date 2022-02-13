<?php if ( ! cs_has_variable_prices( get_the_ID() ) ) : ?>
	<div>
		<div class="cs_price">
			<?php cs_price( get_the_ID() ); ?>
		</div>
	</div>
<?php endif; ?>
