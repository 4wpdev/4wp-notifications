# 4WP Notifications

Unified in-app notifications for logged-in users. Part of the [4wp.dev](https://github.com/4wpdev) ecosystem.

## Description

A reusable WordPress plugin that aggregates notifications from multiple sources (WooCommerce orders, admin messages, and optional integrations) into one list. No dependency on a specific project; adapters load only when the related plugin is active.

## Features

- **Shortcodes**
  - `[forwp_notifications]` / `[4wp_notifications]` — full list on a page; auto-refresh via REST polling.
  - `[forwp_notifications_bell]` / `[4wp_notifications_bell]` — bell icon, unread badge, dropdown list; “View all” link configurable in settings.
- **Block** — Gutenberg block `forwp/notifications` for the notification list.
- **Admin** — Send custom notifications (one or multiple users via checkboxes); **Settings** to set the “page with all notifications” for the bell link.
- **Integrations** — WooCommerce adapter (new order, order status changed) loads only if WooCommerce is active. Mark read/unread; type icons (cart, megaphone); link icon when a notification has a URL.
- **Sync** — Marking read/unread on the notifications page updates the toolbar bell; marking in the toolbar updates the page list (custom event).
- **Translations** — English source strings; Ukrainian .po/.mo included in `languages/`.

## How it works

1. **Event** — Integration or admin action pushes an event to the queue (or creates a notification directly for immediate cases).
2. **Queue + Worker** — Action Scheduler processes events; worker inserts one row per notification.
3. **Storage** — Custom table `wp_4wp_notifications`: `id`, `user_id`, `type`, `source`, `object_id`, `payload` (JSON), `is_read`, `created_at`.
4. **API** — REST under `forwp/v1`: list, unread count, mark read, mark unread, mark all read.
5. **UI** — Shortcodes and block; front end polls REST to refresh list and badge (no WebSocket).

**Real-time:** Polling (e.g. every 30s). WebSocket/push are planned as advanced options and require a separate service.

## Installation

1. Upload the plugin to `/wp-content/plugins/4wp-notifications` or install via WordPress admin.
2. Activate the plugin.
3. In **Notifications → Settings** choose the page that will show the full list (and add `[forwp_notifications]` to that page).
4. Place `[forwp_notifications_bell]` where you want the bell (e.g. theme header or dashboard). Optionally override the icon via filter `forwp_notifications_bell_icon_url`.

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- Action Scheduler (e.g. from WooCommerce) recommended for the queue; sync fallback for immediate sends.

## License

GPL v2 or later

## Author

[4WP](https://4wp.dev)
