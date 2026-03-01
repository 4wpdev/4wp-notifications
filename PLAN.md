# 4wp-notifications — план

Універсальний плагін екосистеми [4wpdev](https://github.com/4wpdev): єдина система нотифікацій для авторизованого користувача. Без прив'язки до проєкту; інтеграції опційні.

## Логіка

1. **Подія** → хук або `do_action('4wp_notification_event', $user_id, $type, $source, $payload)`
2. **Запис** → один рядок у таблиці `wp_4wp_notifications`
3. **Показ** → список для користувача (REST), mark read, CTA з payload

Джерела (Woo, LMS, Favorites) не знають про таблицю — лише викликають подію. Черга/воркер — опційно, щоб не блокувати запити.

---

## Структура

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

## Таблиця

`wp_4wp_notifications`: `id`, `user_id`, `type`, `source`, `object_id`, `payload` (JSON), `is_read`, `created_at`, `scheduled_at` (nullable).  
Індекси: `(user_id, is_read)`, `(user_id, created_at DESC)`.

---

## Етапи

1. **Core** — таблиця, NotificationManager (create + repository), REST (list, mark read).
2. **Queue + Worker** — подія в чергу (Action Scheduler), воркер пише в таблицю.
3. **UI** — блок з Interactivity API (store `4wp/notifications`, список, mark read, CTA).
4. **Адаптери** — Woo (order status/new order), LMS (за наявними хуками), Favorites (`do_action` після add/remove).
5. **Пізніше** — push, SMS, SSE/polling для оновлень.

---

## Умови

- PHP 7.4+, WP 5.8+.
- Адаптери підключаються тільки за наявності відповідного плагіна (`class_exists('WooCommerce')` тощо).
- Один action для всього: `do_action('4wp_notification_event', $user_id, $type, $source, $payload)`; тип і source — рядки (наприклад `order_status_changed`, `woo`).
