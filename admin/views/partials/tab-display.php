<?php
/**
 * Display tab — pages, blocks, and shortcodes.
 *
 * @package ForWP_Notifications
 *
 * @var int $forwp_notif_page_id Configured notifications page ID.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="forwp-notifications-intro-card">
	<h2 class="forwp-notifications-intro-card__title"><?php esc_html_e( 'Frontend display', '4wp-notifications' ); ?></h2>
	<p class="forwp-notifications-intro-card__text"><?php esc_html_e( 'Configure where users see notifications. Prefer blocks in block themes; shortcodes remain for classic menus and legacy pages.', '4wp-notifications' ); ?></p>
</div>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="forwp-notifications-card">
	<input type="hidden" name="action" value="forwp_notifications_settings" />
	<?php wp_nonce_field( 'forwp_notifications_settings' ); ?>

	<div class="forwp-notifications-field">
		<label class="forwp-notifications-field__label" for="forwp_notifications_page_id"><?php esc_html_e( 'All notifications page', '4wp-notifications' ); ?></label>
		<?php
		wp_dropdown_pages(
			array(
				'name'             => 'page_id',
				'id'               => 'forwp_notifications_page_id',
				'selected'         => absint( $forwp_notif_page_id ),
				'show_option_none' => esc_html__( '— Select —', '4wp-notifications' ),
				'post_status'      => 'publish,draft',
			)
		);
		?>
		<p class="forwp-notifications-field__help"><?php esc_html_e( 'The bell widget links here via “View all notifications”. Add the Notifications List block or shortcode to this page.', '4wp-notifications' ); ?></p>
		<?php if ( $forwp_notif_page_id > 0 ) : ?>
			<p class="forwp-notifications-field__links">
				<a href="<?php echo esc_url( get_edit_post_link( $forwp_notif_page_id, 'raw' ) ); ?>"><?php esc_html_e( 'Edit page', '4wp-notifications' ); ?></a>
				<?php if ( get_permalink( $forwp_notif_page_id ) ) : ?>
					· <a href="<?php echo esc_url( get_permalink( $forwp_notif_page_id ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View page', '4wp-notifications' ); ?></a>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>

	<div class="forwp-notifications-field">
		<label class="forwp-notifications-field__label" for="forwp_favorites_page_id"><?php esc_html_e( 'Favorites page', '4wp-notifications' ); ?></label>
		<?php
		wp_dropdown_pages(
			array(
				'name'             => 'favorites_page_id',
				'id'               => 'forwp_favorites_page_id',
				'selected'         => absint( $forwp_notif_favorites_page_id ),
				'show_option_none' => esc_html__( '— Select —', '4wp-notifications' ),
				'post_status'      => 'publish,draft',
			)
		);
		?>
		<p class="forwp-notifications-field__help"><?php esc_html_e( 'Dedicated page for saved favorites grouped by content type. The favorites menu widget links here via “View all favorites”. Add the Favorites List block or shortcode.', '4wp-notifications' ); ?></p>
		<?php if ( $forwp_notif_favorites_page_id > 0 ) : ?>
			<p class="forwp-notifications-field__links">
				<a href="<?php echo esc_url( get_edit_post_link( $forwp_notif_favorites_page_id, 'raw' ) ); ?>"><?php esc_html_e( 'Edit page', '4wp-notifications' ); ?></a>
				<?php if ( get_permalink( $forwp_notif_favorites_page_id ) ) : ?>
					· <a href="<?php echo esc_url( get_permalink( $forwp_notif_favorites_page_id ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View page', '4wp-notifications' ); ?></a>
				<?php endif; ?>
			</p>
		<?php endif; ?>
	</div>

	<p class="forwp-notifications-form-actions">
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Save display settings', '4wp-notifications' ); ?></button>
	</p>
</form>

<section class="forwp-notifications-card forwp-notifications-reference">
	<h3 class="forwp-notifications-admin-section-title"><?php esc_html_e( 'Blocks and shortcodes', '4wp-notifications' ); ?></h3>
	<table class="widefat striped forwp-notifications-ref-table">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Use case', '4wp-notifications' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Block (recommended)', '4wp-notifications' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Shortcode (legacy)', '4wp-notifications' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td><?php esc_html_e( 'Bell in header or navigation', '4wp-notifications' ); ?></td>
				<td><?php esc_html_e( 'Bell icon + dropdown. Insert anywhere (header row, sidebar, page) or inside Navigation.', '4wp-notifications' ); ?><br /><code>forwp/notifications-bell</code></td>
				<td><code>[forwp_notifications_bell]</code></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Full list on a page', '4wp-notifications' ); ?></td>
				<td><strong>4WP Notifications List</strong><br /><code>forwp/notifications</code></td>
				<td><code>[forwp_notifications]</code></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Favorites list page', '4wp-notifications' ); ?></td>
				<td><strong>4WP Favorites List</strong><br /><code>forwp/favorites-list</code></td>
				<td><code>[forwp_favorites]</code></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Favorite heart in Query Loop or archives', '4wp-notifications' ); ?></td>
				<td><strong>4WP Favorite Button</strong><br /><code>forwp/favorite-button</code></td>
				<td><code>[forwp_favorite_button]</code></td>
			</tr>
			<tr>
				<td><?php esc_html_e( 'Favorites menu in header or navigation', '4wp-notifications' ); ?></td>
				<td><?php esc_html_e( 'Heart icon + dropdown of recent favorites. Insert anywhere or inside Navigation.', '4wp-notifications' ); ?><br /><code>forwp/favorites-menu</code></td>
				<td><code>[forwp_favorites_menu]</code></td>
			</tr>
		</tbody>
	</table>
	<ul class="forwp-notifications-admin-list">
		<li><code>[forwp_notifications limit="20"]</code></li>
		<li><code>[forwp_notifications_bell limit="10" all_url="https://…"]</code></li>
		<li><code>[forwp_favorites_menu limit="5" all_url="https://…"]</code></li>
	</ul>
</section>
