<?php
/**
 * PHPUnit bootstrap.
 *
 * phpcs:ignoreFile
 *
 * @package ForWP_Notifications
 */

require_once __DIR__ . '/wp-stubs.php';

$root = dirname( __DIR__ );

define( 'FORWP_NOTIFICATIONS_VERSION', '1.2.0-test' );
define( 'FORWP_NOTIFICATIONS_PLUGIN_DIR', trailingslashit( $root ) );
define( 'FORWP_NOTIFICATIONS_PLUGIN_URL', 'https://example.test/wp-content/plugins/4wp-notifications/' );
define( 'FORWP_NOTIFICATIONS_PLUGIN_BASENAME', '4wp-notifications/4wp-notifications.php' );

require_once $root . '/includes/class-notification-repository.php';
require_once $root . '/includes/class-notification-manager.php';
require_once $root . '/includes/class-notification-sender.php';
require_once $root . '/includes/class-recipient-resolver.php';
require_once $root . '/includes/class-page-display.php';

if ( file_exists( $root . '/vendor/autoload.php' ) ) {
	require_once $root . '/vendor/autoload.php';
}
