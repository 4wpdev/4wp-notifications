# 4WP Notifications

Reusable WordPress plugin for unified in-app notifications for logged-in users. Part of the [4wpdev](https://github.com/4wpdev) ecosystem. No dependency on a specific project; integrations (WooCommerce, LMS, etc.) are optional.

---

## How it works

1. **Event** — A hook or integration fires (e.g. order status changed, admin sends a message). The adapter pushes an event to the queue (or creates a notification directly for immediate cases).
2. **Queue + Worker** — Events are processed asynchronously via Action Scheduler. The worker creates one row per notification in the database.
3. **Storage** — One custom table `wp_4wp_notifications`: `id`, `user_id`, `type`, `source`, `object_id`, `payload` (JSON), `is_read`, `created_at`.
4. **API** — REST endpoints under `forwp/v1`: list notifications, unread count, mark read, mark all read.
5. **UI** — Data is shown via shortcodes and a block; the front end polls the REST API to refresh the list and badge (no persistent connection).

**Real-time updates today:** Polling (e.g. every 30 seconds) for unread count and list. No WebSocket or push — that would require a separate long-lived service.

---

## Features

- **Shortcodes**
  - `[forwp_notifications]` / `[4wp_notifications]` — full list on a page (e.g. “All notifications”); auto-refresh via polling.
  - `[forwp_notifications_bell]` / `[4wp_notifications_bell]` — bell icon + dropdown (badge + list); same polling for updates.
- **Block** — Gutenberg block `forwp/notifications` for the notification list.
- **Admin** — Send custom notifications to a user; **Settings** to choose the “page with all notifications” (link in the bell widget points there).
- **Integrations** — WooCommerce adapter (new order, order status changed) is loaded only if WooCommerce is active. More adapters can be added the same way.
- **Table** — `wp_4wp_notifications`. Indexes on `(user_id, is_read)` and `(user_id, created_at DESC)`.

---

## WebSocket / Push — advanced (future)

Real-time delivery (instant badge/list update without polling) would require:

- **WebSocket** — A separate process (e.g. Node.js or PHP with Ratchet/Swoole) keeping connections open. Not provided by WordPress or typical shared hosting.
- **Push** — Browser or mobile push (e.g. Web Push API + Service Worker, or FCM) for out-of-tab alerts; in-app “live” updates still need either WebSockets/SSE or very frequent polling.

These are planned as **advanced / stage two** features for production setups that can run an extra service or use a third-party push provider. The current plugin is built to work everywhere with **polling only**.

---

## Requirements

- PHP 7.4+
- WordPress 5.8+
- Action Scheduler (e.g. from WooCommerce) recommended for the queue; optional, with fallback to synchronous creation.

---

## Usage (theme-agnostic)

1. Install and activate. The table is created on activation.
2. Use `[forwp_notifications]` on a page and set that page in **Notifications → Settings** so the bell widget “View all” link works.
3. Use `[forwp_notifications_bell]` where you want the bell + dropdown (e.g. in theme header or breadcrumbs). Theme can override the bell icon via filter `forwp_notifications_bell_icon_url`.
4. Optional: add the block “4WP Notifications” in the editor for the full list.

This project is one possible testing ground; the plugin itself is intended as a reusable tool for any WordPress site.
