<?php
/**
 * Notification types tab — integration toggles.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$forwp_notif_woo_active = ForWP_Notifications_Plugin_Settings::is_woocommerce_active();
?>
<div class="forwp-notifications-intro-card">
	<h2 class="forwp-notifications-intro-card__title"><?php esc_html_e( 'Automatic notification sources', '4wp-notifications' ); ?></h2>
	<p class="forwp-notifications-intro-card__text"><?php esc_html_e( 'Choose which integrations create in-app notifications. Direct admin messages are always available under Direct notifications.', '4wp-notifications' ); ?></p>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="forwp_notifications_types" />
	<?php wp_nonce_field( 'forwp_notifications_types' ); ?>

	<div class="forwp-notifications-source-grid">
		<div class="forwp-notifications-source-card<?php echo $forwp_notif_woo_active ? ' is-live' : ' is-unavailable'; ?>">
			<div class="forwp-notifications-source-card__head">
				<div>
					<h3 class="forwp-notifications-source-card__title"><?php esc_html_e( 'WooCommerce', '4wp-notifications' ); ?></h3>
					<p class="forwp-notifications-source-card__slug"><code>woo</code></p>
				</div>
				<?php if ( $forwp_notif_woo_active ) : ?>
					<span class="forwp-notifications-badge forwp-notifications-badge--live"><?php esc_html_e( 'Live', '4wp-notifications' ); ?></span>
				<?php else : ?>
					<span class="forwp-notifications-badge forwp-notifications-badge--off"><?php esc_html_e( 'Not installed', '4wp-notifications' ); ?></span>
				<?php endif; ?>
			</div>
			<p class="forwp-notifications-source-card__desc"><?php esc_html_e( 'Notify customers when orders are created or change status.', '4wp-notifications' ); ?></p>
			<?php if ( $forwp_notif_woo_active ) : ?>
				<fieldset class="forwp-notifications-toggle-list">
					<label class="forwp-notifications-toggle-row">
						<input type="checkbox" name="woo_order_created" value="1" <?php checked( ForWP_Notifications_Plugin_Settings::is_woo_order_created_enabled() ); ?> />
						<span><?php esc_html_e( 'New order created', '4wp-notifications' ); ?></span>
					</label>
					<label class="forwp-notifications-toggle-row">
						<input type="checkbox" name="woo_status_changed" value="1" <?php checked( ForWP_Notifications_Plugin_Settings::is_woo_status_changed_enabled() ); ?> />
						<span><?php esc_html_e( 'Order status changed', '4wp-notifications' ); ?></span>
					</label>
				</fieldset>
			<?php else : ?>
				<p class="forwp-notifications-admin-muted"><?php esc_html_e( 'Install and activate WooCommerce to enable store notifications.', '4wp-notifications' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="forwp-notifications-source-card is-live">
			<div class="forwp-notifications-source-card__head">
				<div>
					<h3 class="forwp-notifications-source-card__title"><?php esc_html_e( 'Favorites', '4wp-notifications' ); ?></h3>
					<p class="forwp-notifications-source-card__slug"><code>favorites</code></p>
				</div>
				<span class="forwp-notifications-badge forwp-notifications-badge--live"><?php esc_html_e( 'Live', '4wp-notifications' ); ?></span>
			</div>
			<p class="forwp-notifications-source-card__desc"><?php esc_html_e( 'Notify users when they follow a CPT, category, or saved post and matching content is published or updated.', '4wp-notifications' ); ?></p>
			<fieldset class="forwp-notifications-toggle-list">
				<label class="forwp-notifications-toggle-row">
					<input type="checkbox" name="fav_new_post" value="1" <?php checked( ForWP_Notifications_Plugin_Settings::is_favorites_new_post_enabled() ); ?> />
					<span><?php esc_html_e( 'New post in followed CPT or category', '4wp-notifications' ); ?></span>
				</label>
				<label class="forwp-notifications-toggle-row">
					<input type="checkbox" name="fav_post_updated" value="1" <?php checked( ForWP_Notifications_Plugin_Settings::is_favorites_post_updated_enabled() ); ?> />
					<span><?php esc_html_e( 'Followed post updated', '4wp-notifications' ); ?></span>
				</label>
			</fieldset>
		</div>

		<div class="forwp-notifications-source-card is-planned">
			<div class="forwp-notifications-source-card__head">
				<div>
					<h3 class="forwp-notifications-source-card__title"><?php esc_html_e( 'LMS4WP', '4wp-notifications' ); ?></h3>
					<p class="forwp-notifications-source-card__slug"><code>lms4wp</code></p>
				</div>
				<span class="forwp-notifications-badge forwp-notifications-badge--planned"><?php esc_html_e( 'Planned', '4wp-notifications' ); ?></span>
			</div>
			<p class="forwp-notifications-source-card__desc"><?php esc_html_e( 'Course enrollment, progress, and assignment alerts.', '4wp-notifications' ); ?></p>
		</div>
	</div>

	<p class="forwp-notifications-form-actions">
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Save notification types', '4wp-notifications' ); ?></button>
	</p>
</form>
