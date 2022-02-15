<?php
/**
 * Notice content for php-56-requirement
 *
 * @package CS_Stripe
 * @since   2.6.19
 */

$future_required_version = 5.6;
$current_version         = phpversion();
?>

<p>
	<strong><?php esc_html_e( 'Easy Digital Downloads Stripe Payment Gateway is increasing its PHP version requirement.', 'commercestore' ); ?></strong>
</p>

<p>
	<?php 
	echo wp_kses(
		sprintf(
			/* translators: %1$s Future PHP version requirement. %2$s Current PHP version. %3$s Opening strong tag, do not translate. %4$s Closing strong tag, do not translate. %5$s Opening anchor tag, do not translate. %6$s Closing anchor tag, do not translate. */
			__( 'Easy Digital Downloads Stripe Payment Gateway will be increasing its PHP requirement to version %1$s or higher in an upcoming release. It looks like you\'re using version %2$s, which means you will need to %3$supgrade your version of PHP to allow the plugin to continue to function%4$s. Newer versions of PHP are both faster and more secure. The version you\'re using %5$sno longer receives security updates%6$s, which is another great reason to update.', 'commercestore' ),
			'<code>' . $future_required_version . '</code>',
			'<code>' . $current_version . '</code>',
			'<strong>',
			'</strong>',
			'<a href="http://php.net/eol.php" rel="noopener noreferrer" target="_blank">',
			'</a>'
		),
		array(
			'code'   => true,
			'strong' => true,
			'a'      => array(
				'href'   => true,
				'rel'    => true,
				'target' => true,
			)
		)
	);
	?>
</p>

<p>
	<button id="csx-php-56-read-more" class="button button-secondary button-small"><?php esc_html_e( 'Read More', 'commercestore' ); ?></button>

	<script>
	document.getElementById( 'csx-php-56-read-more' ).addEventListener( 'click', function( e ) {
		e.preventDefault();
		var wrapperEl = e.target.parentNode.nextElementSibling;
		wrapperEl.style.display = 'block' === wrapperEl.style.display ? 'none' : 'block';
	} );
	</script>
</p>

<div style="display: none;">

	<p>
		<strong><?php esc_html_e( 'Which version should I upgrade to?', 'commercestore' ); ?></strong>
	</p>

	<p>
		<?php
		echo wp_kses(
			sprintf(
				/* translators: %1$s Future PHP version requirement. */
				__( 'In order to be compatible with future versions of WP Simple Pay, you should update your PHP version to %1$s, <code>7.0</code>, <code>7.1</code>, or <code>7.2</code>. On a normal WordPress site, switching to PHP %1$s should never cause issues. We would however actually recommend you switch to PHP <code>7.1</code> or higher to receive the full speed and security benefits provided to more modern and fully supported versions of PHP. However, some plugins may not be fully compatible with PHP <code>7.x</code>, so more testing may be required.', 'commercestore' ),
				'<code>' . $future_required_version . '</code>'
			),
			array(
				'code' => true,
			)
		);
		?>
	</p>

	<p>
		<strong><?php esc_html_e( 'Need help upgrading? Ask your web host!', 'commercestore' ); ?></strong>
	</p>

	<p>
	<?php
		echo wp_kses(
			/* translators: %1$s Opening anchor tag, do not translate. %2$s Closing anchor tag, do not translate. */
			sprintf(
				__( 'Many web hosts can give you instructions on how/where to upgrade your version of PHP through their control panel, or may even be able to do it for you. %1$sRead more about updating PHP%2$s.', 'commercestore' ),
				'<a href="https://wordpress.org/support/update-php/" target="_blank" rel="noopener noreferrer">',
				'</a>'
			),
			array(
				'a'    => array(
					'href'   => true,
					'rel'    => true,
					'target' => true,
				)
			)
		);
	?>
	</p>

</div>
