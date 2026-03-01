# 4wp-notifications — plan

Universal plugin of the [4wpdev](https://github.com/4wpdev) ecosystem: a single notification system for the logged-in user. Not tied to a specific project; integrations are optional.

## Logic

1. **Event** → hook or `do_action('4wp_notification_event', $user_id, $type, $source, $payload)`
2. **Storage** → one row in table `wp_4wp_notifications`
3. **Display** → list for the user (REST), mark read, CTA from payload

Sources (Woo, LMS, Favorites) do not know about the table — they only fire the event. Queue/worker is optional so as not to block requests.

---

## Structure

```
4wp-notifications/
├── 4wp-notifications.php          # bootstrap, defines, autoload
├── includes/
│   ├── class-notification-manager.php   # create(), get_for_user()
│   ├── class-notification-repository.php
│   ├── class-queue.php                  # push event (DB or Action Scheduler)
│   ├── class-worker.php                 # process queue → insert notification
│   └── interfaces/
│       └── interface-notification-source.php
├── integrations/                   # optional adapters
│   ├── class-woo-adapter.php       # if WooCommerce
│   ├── class-lms-adapter.php       # if LMS4WP
│   └── class-favorites-adapter.php
├── rest/
│   └── class-rest-controller.php  # GET list, PATCH mark read
├── assets/
│   └── blocks/                     # Interactivity API block (list + actions)
└── install/
    └── class-installer.php         # create table, uninstall
```

---

## Table

`wp_4wp_notifications`: `id`, `user_id`, `type`, `source`, `object_id`, `payload` (JSON), `is_read`, `created_at`, `scheduled_at` (nullable).  
Indexes: `(user_id, is_read)`, `(user_id, created_at DESC)`.

---

## Stages

1. **Core** — table, NotificationManager (create + repository), REST (list, mark read).
2. **Queue + Worker** — event to queue (Action Scheduler), worker writes to table.
3. **UI** — block with Interactivity API (store `4wp/notifications`, list, mark read, CTA).
4. **Adapters** — Woo (order status/new order), LMS (per available hooks), Favorites (`do_action` after add/remove).
5. **Later** — push, SMS, SSE/polling for updates.

---

## Requirements

- PHP 7.4+, WP 5.8+.
- Adapters load only when the corresponding plugin is present (`class_exists('WooCommerce')`, etc.).
- One action for all: `do_action('4wp_notification_event', $user_id, $type, $source, $payload)`; type and source are strings (e.g. `order_status_changed`, `woo`).
