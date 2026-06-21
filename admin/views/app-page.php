<?php
/**
 * Unified admin app — 4WP ecosystem shell (aligned with Drive / Weather).
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only admin tab/notice query args.

$forwp_notif_tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'display';
$forwp_notif_tabs = array( 'display', 'direct', 'types', 'documentation' );
if ( ! in_array( $forwp_notif_tab, $forwp_notif_tabs, true ) ) {
	$forwp_notif_tab = 'display';
}

$forwp_notif_saved       = isset( $_GET['saved'] ) && '1' === $_GET['saved'];
$forwp_notif_sent        = isset( $_GET['sent'] ) ? (int) $_GET['sent'] : 0;
$forwp_notif_types_saved = isset( $_GET['types_saved'] ) && '1' === $_GET['types_saved'];
$forwp_notif_error       = isset( $_GET['error'] ) && '1' === $_GET['error'];
$forwp_notif_page_id           = ForWP_Notifications_Plugin_Settings::get_page_id();
$forwp_notif_favorites_page_id = ForWP_Notifications_Plugin_Settings::get_favorites_page_id();

// phpcs:enable WordPress.Security.NonceVerification.Recommended

$forwp_notif_heading_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="28" height="28" fill="none" focusable="false" aria-hidden="true"><path d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6V11a7 7 0 1 0-14 0v5l-2 2v1h18v-1l-2-2Z" fill="currentColor"/></svg>';
$forwp_notif_svg_allowed = array(
	'svg'  => array(
		'xmlns'       => true,
		'viewbox'     => true,
		'width'       => true,
		'height'      => true,
		'fill'        => true,
		'focusable'   => true,
		'aria-hidden' => true,
	),
	'path' => array(
		'd'    => true,
		'fill' => true,
	),
);
?>
<div class="wrap forwp-notifications-admin-shell">
	<?php if ( $forwp_notif_sent > 0 ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php
		/* translators: %d: number of recipients */
		echo esc_html( sprintf( __( 'Notification sent to %d user(s).', '4wp-notifications' ), $forwp_notif_sent ) );
		?></p></div>
	<?php endif; ?>
	<?php if ( $forwp_notif_saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Display settings saved.', '4wp-notifications' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $forwp_notif_types_saved ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Notification types saved.', '4wp-notifications' ); ?></p></div>
	<?php endif; ?>
	<?php if ( $forwp_notif_error ) : ?>
		<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'Could not send notification. Enter a title and select at least one role or user.', '4wp-notifications' ); ?></p></div>
	<?php endif; ?>

	<h1 class="forwp-notifications-admin-heading">
		<span class="forwp-notifications-admin-heading__icon" aria-hidden="true"><?php echo wp_kses( $forwp_notif_heading_svg, $forwp_notif_svg_allowed ); ?></span>
		<span class="forwp-notifications-admin-heading__text"><?php esc_html_e( '4WP Notifications', '4wp-notifications' ); ?></span>
	</h1>
	<p class="forwp-notifications-admin-lead"><?php esc_html_e( 'In-app notifications for logged-in users — blocks for the Site Editor, shortcodes for classic content.', '4wp-notifications' ); ?></p>

	<div class="forwp-notifications-admin-app">
		<div class="forwp-notifications-tab-panel components-tab-panel">
			<div class="components-tab-panel__tabs" role="tablist" aria-label="<?php esc_attr_e( '4WP Notifications', '4wp-notifications' ); ?>">
				<button type="button" role="tab" class="components-button components-tab-panel__tabs-item forwp-notifications-tab<?php echo 'display' === $forwp_notif_tab ? ' is-active' : ''; ?>" data-tab="display" aria-selected="<?php echo 'display' === $forwp_notif_tab ? 'true' : 'false'; ?>" aria-controls="forwp-notifications-panel-display" tabindex="<?php echo 'display' === $forwp_notif_tab ? '0' : '-1'; ?>">
					<?php esc_html_e( 'Display', '4wp-notifications' ); ?>
				</button>
				<button type="button" role="tab" class="components-button components-tab-panel__tabs-item forwp-notifications-tab<?php echo 'direct' === $forwp_notif_tab ? ' is-active' : ''; ?>" data-tab="direct" aria-selected="<?php echo 'direct' === $forwp_notif_tab ? 'true' : 'false'; ?>" aria-controls="forwp-notifications-panel-direct" tabindex="<?php echo 'direct' === $forwp_notif_tab ? '0' : '-1'; ?>">
					<?php esc_html_e( 'Direct notifications', '4wp-notifications' ); ?>
				</button>
				<button type="button" role="tab" class="components-button components-tab-panel__tabs-item forwp-notifications-tab<?php echo 'types' === $forwp_notif_tab ? ' is-active' : ''; ?>" data-tab="types" aria-selected="<?php echo 'types' === $forwp_notif_tab ? 'true' : 'false'; ?>" aria-controls="forwp-notifications-panel-types" tabindex="<?php echo 'types' === $forwp_notif_tab ? '0' : '-1'; ?>">
					<?php esc_html_e( 'Notification types', '4wp-notifications' ); ?>
				</button>
				<button type="button" role="tab" class="components-button components-tab-panel__tabs-item forwp-notifications-tab<?php echo 'documentation' === $forwp_notif_tab ? ' is-active' : ''; ?>" data-tab="documentation" aria-selected="<?php echo 'documentation' === $forwp_notif_tab ? 'true' : 'false'; ?>" aria-controls="forwp-notifications-panel-documentation" tabindex="<?php echo 'documentation' === $forwp_notif_tab ? '0' : '-1'; ?>">
					<?php esc_html_e( 'Documentation', '4wp-notifications' ); ?>
				</button>
			</div>

			<div id="forwp-notifications-panel-display" role="tabpanel" class="components-tab-panel__tab-content" aria-labelledby="forwp-notifications-tab-display" <?php echo 'display' !== $forwp_notif_tab ? 'hidden' : ''; ?>>
				<?php require FORWP_NOTIFICATIONS_PLUGIN_DIR . 'admin/views/partials/tab-display.php'; ?>
			</div>
			<div id="forwp-notifications-panel-direct" role="tabpanel" class="components-tab-panel__tab-content" aria-labelledby="forwp-notifications-tab-direct" <?php echo 'direct' !== $forwp_notif_tab ? 'hidden' : ''; ?>>
				<?php require FORWP_NOTIFICATIONS_PLUGIN_DIR . 'admin/views/partials/tab-direct.php'; ?>
			</div>
			<div id="forwp-notifications-panel-types" role="tabpanel" class="components-tab-panel__tab-content" aria-labelledby="forwp-notifications-tab-types" <?php echo 'types' !== $forwp_notif_tab ? 'hidden' : ''; ?>>
				<?php require FORWP_NOTIFICATIONS_PLUGIN_DIR . 'admin/views/partials/tab-types.php'; ?>
			</div>
			<div id="forwp-notifications-panel-documentation" role="tabpanel" class="components-tab-panel__tab-content" aria-labelledby="forwp-notifications-tab-documentation" <?php echo 'documentation' !== $forwp_notif_tab ? 'hidden' : ''; ?>>
				<?php require FORWP_NOTIFICATIONS_PLUGIN_DIR . 'admin/views/partials/tab-documentation.php'; ?>
			</div>
		</div>
	</div>
</div>
