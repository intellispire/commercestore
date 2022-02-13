<?php
/**
 * Displays a list of notifications.
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, CommerceStore
 * @license   GPL2+
 * @since     2.11.4
 */
?>
<div
	id="cs-notifications"
	class="cs-hidden"
	x-data
	x-init="function() { $el.classList.remove( 'cs-hidden' ) }"
>
	<div
		class="cs-overlay"
		x-show="$store.csNotifications.isPanelOpen"
		x-on:click="$store.csNotifications.closePanel()"
	></div>

	<div
		id="cs-notifications-panel"
		x-show="$store.csNotifications.isPanelOpen"
		x-transition:enter-start="cs-slide-in"
		x-transition:leave-end="cs-slide-in"
	>
		<div id="cs-notifications-header" tabindex="-1">
			<h3>
				<?php
				echo wp_kses(
					sprintf(
					/* Translators: %s - number of notifications */
						__( '(%s) New Notifications', 'commercestore' ),
						'<span x-text="$store.csNotifications.numberActiveNotifications"></span>'
					),
					array( 'span' => array( 'x-text' => true ) )
				);
				?>
			</h3>

			<button
				type="button"
				class="cs-close"
				x-on:click="$store.csNotifications.closePanel()"
			>
				<span class="dashicons dashicons-no-alt"></span>
				<span class="screen-reader-text"><?php esc_html_e( 'Close panel', 'commercestore' ); ?></span>
			</button>
		</div>

		<div id="cs-notifications-body">
			<template x-if="$store.csNotifications.notificationsLoaded && $store.csNotifications.activeNotifications.length">
				<template x-for="(notification, index) in $store.csNotifications.activeNotifications" :key="notification.id">
					<div class="cs-notification">
						<div class="cs-notification--icon" :class="'cs-notification--icon-' + notification.type">
							<span class="dashicons" :class="'dashicons-' + notification.icon_name"></span>
						</div>

						<div class="cs-notification--body">
							<div class="cs-notification--header">
								<h4 class="cs-notification--title" x-text="notification.title"></h4>

								<div class="cs-notification--date" x-text="notification.relative_date"></div>
							</div>

							<div class="cs-notification--content" x-html="notification.content"></div>

							<div class="cs-notification--actions">
								<template x-for="button in notification.buttons">
									<a
										:href="button.url"
										:class="button.type === 'primary' ? 'button button-primary' : 'button button-secondary'"
										target="_blank"
										x-text="button.text"
									></a>
								</template>

								<button
									type="button"
									class="cs-notification--dismiss"
									x-on:click="$store.csNotifications.dismiss( $event, index )"
								>
									<?php esc_html_e( 'Dismiss', 'commercestore' ); ?>
								</button>
							</div>
						</div>
					</div>
				</template>
			</template>

			<template x-if="$store.csNotifications.notificationsLoaded && ! $store.csNotifications.activeNotifications.length">
				<div id="cs-notifications-none">
					<?php esc_html_e( 'You have no new notifications.', 'commercestore' ); ?>
				</div>
			</template>

			<template x-if="! $store.csNotifications.notificationsLoaded">
				<div>
					<?php esc_html_e( 'Loading notifications...', 'commercestore' ); ?>
				</div>
			</template>
		</div>
	</div>
</div>
