=== 4WP Notifications ===
Contributors: 4wpdev, anatolikkk
Tags: notifications, woocommerce, bell, gutenberg, inbox
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

In-app notifications for logged-in users — bell block, inbox list, WooCommerce events, admin broadcasts, and a developer REST send API.

== Description ==

**4WP Notifications** delivers a unified in-app notification inbox for logged-in WordPress users: a header bell with unread badge, a full notifications page, optional WooCommerce order alerts, and tools for developers to fire notifications from custom code.

A plugin by [4wp.dev](https://4wp.dev/). **4WP** is our project brand; the letters "WP" appear only as part of that brand name, not as a reference to WordPress. This plugin is not affiliated with, endorsed, or sponsored by WordPress.

Source code: [github.com/4wpdev/4wp-notifications](https://github.com/4wpdev/4wp-notifications)

= Key features =

* **Notifications Bell block** — dropdown with unread count; add inside a Navigation block (header menu)
* **Notifications List block** — full inbox page with mark read/unread and live polling
* **Shortcodes** — `[forwp_notifications_bell]` and `[forwp_notifications]` for classic themes
* **Direct notifications** — admins broadcast to roles and/or individual users
* **WooCommerce** — optional alerts for new orders and status changes
* **Developer hooks** — `forwp_notification_event` / `4wp_notification_event` from PHP
* **REST API** — list, unread count, mark read/unread, and **send** notifications (authenticated)

= Privacy =

Notifications are stored in your WordPress database and shown only to the logged-in recipient. Read endpoints require an authenticated user session. Send endpoints require administrator capability (default: `manage_options`). No data is sent to 4wp.dev.

= Development =

Human-readable PHP source is in the public GitHub repository above. No npm build step — block editor scripts ship as plain JS in `assets/blocks/`.

Run tests: `composer install && composer test && composer run lint`

== Blocks ==

This plugin provides 2 blocks.

* **4WP Notifications Bell** (`forwp/notifications-bell`) — bell icon and recent notifications dropdown (Navigation child block).
* **4WP Notifications List** (`forwp/notifications`) — full notification list for a page.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/4wp-notifications/` or install from the Plugins screen.
2. Activate **4WP Notifications**.
3. Open **4WP Notifications → Display** and choose the page for “View all notifications”.
4. Add the **Notifications List** block to that page (or save Display settings to auto-insert it).
5. Add the **Notifications Bell** block inside your header **Navigation** block.
6. Optional: enable WooCommerce notification types under **Notification types**.

== Frequently Asked Questions ==

= Do visitors see notifications? =

No. The bell and list render only for logged-in users.

= Can I send a message to all customers? =

Yes. Use **Direct notifications**, select the **Customer** role (or any role), and send.

= Can developers send notifications from custom code? =

Yes. Use the PHP hook `forwp_notification_event` or POST to `/wp-json/forwp/v1/notifications` (requires `manage_options` by default). See **Documentation** in the admin app.

= Does WooCommerce require extra setup? =

Install WooCommerce, then enable event types under **Notification types**.

= Are shortcodes still supported? =

Yes. Blocks are recommended for block themes; shortcodes remain for classic content and menus.

== Screenshots ==

1. Admin — Display tab with blocks vs shortcodes reference.
2. Admin — Direct notifications with role-based recipients.
3. Admin — Notification types (WooCommerce toggles).
4. Frontend — bell dropdown with unread badge.

== Changelog ==

= 1.2.0 =
* REST API POST — send notifications to `user_id` or `user_ids` (default capability: `manage_options`).
* Developer hooks: `forwp_notification_event` and legacy `4wp_notification_event`.
* Block editor assets for Bell and List blocks (inserter visibility and previews).
* Auto-insert Notifications List block on the configured page.
* Bell dropdown list refreshes on poll and when opening the menu.

= 1.1.0 =
* Unified admin app (Display, Direct, Types, Documentation) aligned with 4WP Drive / Weather.
* Notifications Bell block (`forwp/notifications-bell`) for headers and navigation.
* Shared frontend renderers for blocks and shortcodes.
* Role-based recipient targeting for direct admin notifications.
* WooCommerce notification type toggles in settings.

= 1.0.4 =
* Tabbed settings shell and notification type cards.

= 1.0.0 =
* Initial release: shortcodes, list block, WooCommerce adapter, REST read API.

== Upgrade Notice ==

= 1.2.0 =
Developer send API, PHP hooks, block editor fixes, and bell list sync improvements.

= 1.1.0 =
Unified admin UI, bell block, role-based broadcasts, and WooCommerce toggles.

= 1.0.0 =
First release.
