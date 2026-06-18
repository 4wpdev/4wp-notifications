<?php
/**
 * Documentation tab.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$forwp_notif_rest_send_url = rest_url( ForWP_Notifications_REST_Controller::NAMESPACE . '/notifications' );
?>
<div class="forwp-notifications-intro-card">
	<h2 class="forwp-notifications-intro-card__title"><?php esc_html_e( 'Quick setup', '4wp-notifications' ); ?></h2>
	<ol class="forwp-notifications-admin-list forwp-notifications-admin-list--ordered">
		<li><?php esc_html_e( 'Display → choose the page that shows the full list.', '4wp-notifications' ); ?></li>
		<li><?php esc_html_e( 'Add Notifications List block to that page (or shortcode).', '4wp-notifications' ); ?></li>
		<li><?php esc_html_e( 'Add Notifications Bell block to your header template or Navigation block.', '4wp-notifications' ); ?></li>
		<li><?php esc_html_e( 'Direct notifications → send a test message.', '4wp-notifications' ); ?></li>
		<li><?php esc_html_e( 'Notification types → enable WooCommerce events when needed.', '4wp-notifications' ); ?></li>
	</ol>
</div>

<section class="forwp-notifications-card">
	<h3 class="forwp-notifications-admin-section-title"><?php esc_html_e( 'Developer hook (PHP)', '4wp-notifications' ); ?></h3>
	<p class="forwp-notifications-admin-muted"><?php esc_html_e( 'Fire a notification from custom code — e.g. after a CPT post is created:', '4wp-notifications' ); ?></p>
	<pre class="forwp-notifications-code"><code>do_action(
	'forwp_notification_event',
	$user_id,          // recipient user ID
	'post_published',  // type slug
	'my-plugin',       // source slug
	array(
		'title'   => 'New entry published',
		'message' => 'Your CPT item is live.',
		'url'     => get_permalink( $post_id ),
	),
	$post_id           // optional object ID
);</code></pre>
	<p class="forwp-notifications-admin-muted"><?php esc_html_e( 'Legacy alias: 4wp_notification_event (same arguments).', '4wp-notifications' ); ?></p>
</section>

<section class="forwp-notifications-card">
	<h3 class="forwp-notifications-admin-section-title"><?php esc_html_e( 'REST API — send notification', '4wp-notifications' ); ?></h3>
	<p class="forwp-notifications-admin-muted">
		<?php
		printf(
			/* translators: %s: REST route path */
			esc_html__( 'POST %s — requires a user with permission to send notifications (default: manage_options). Use an Application Password for external calls.', '4wp-notifications' ),
			'<code>' . esc_html( $forwp_notif_rest_send_url ) . '</code>'
		);
		?>
	</p>
	<pre class="forwp-notifications-code"><code>POST <?php echo esc_html( $forwp_notif_rest_send_url ); ?>

{
	"user_id": 1,
	"type": "post_published",
	"source": "my-plugin",
	"title": "New entry published",
	"message": "Your CPT item is live.",
	"url": "https://example.com/my-post/",
	"object_id": 123
}</code></pre>
	<p class="forwp-notifications-admin-muted"><?php esc_html_e( 'Multiple recipients: use user_ids instead of user_id.', '4wp-notifications' ); ?></p>
	<pre class="forwp-notifications-code"><code>{
	"user_ids": [1, 5, 12],
	"type": "custom",
	"source": "my-plugin",
	"title": "Batch alert",
	"message": "Something happened."
}</code></pre>
</section>

<section class="forwp-notifications-card">
	<h3 class="forwp-notifications-admin-section-title"><?php esc_html_e( 'REST API — read & mark read', '4wp-notifications' ); ?></h3>
	<ul class="forwp-notifications-admin-list">
		<li><code>GET <?php echo esc_html( rest_url( ForWP_Notifications_REST_Controller::NAMESPACE . '/notifications' ) ); ?></code></li>
		<li><code>GET …/notifications/unread-count</code></li>
		<li><code>PATCH …/notifications/{id}</code> — body: <code>{"is_read": true}</code></li>
		<li><code>POST …/notifications/mark-all-read</code></li>
	</ul>
</section>
