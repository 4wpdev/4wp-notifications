<?php
/**
 * Plugin Name: 4WP Notifications
 * Plugin URI: https://github.com/4wpdev/4wp-notifications
 * Description: Unified in-app notifications for logged-in users. WooCommerce, admin messages, and extensible sources.
 * Version: 1.0.2
 * Author: 4wp.dev
 * Author URI: https://4wp.dev
 * Text Domain: forwp-notifications
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FORWP_NOTIFICATIONS_VERSION', '1.0.0' );
define( 'FORWP_NOTIFICATIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FORWP_NOTIFICATIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FORWP_NOTIFICATIONS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'install/class-installer.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-notification-repository.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-notification-manager.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-queue.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-worker.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-shortcode-bell.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-block.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'rest/class-rest-controller.php';

register_activation_hook( __FILE__, array( 'ForWP_Notifications_Installer', 'install' ) );
register_uninstall_hook( __FILE__, array( 'ForWP_Notifications_Installer', 'uninstall' ) );

add_action( 'plugins_loaded', 'forwp_notifications_init' );

/**
 * Bootstrap plugin.
 */
function forwp_notifications_init() {
	// Load translations from plugin's languages folder (absolute path for reliability).
	// For Ukrainian: set Site Language to "Українська" in Settings → General.
	$locale = apply_filters( 'plugin_locale', determine_locale(), 'forwp-notifications' );
	$mofile = FORWP_NOTIFICATIONS_PLUGIN_DIR . 'languages/forwp-notifications-' . $locale . '.mo';
	if ( is_readable( $mofile ) ) {
		load_textdomain( 'forwp-notifications', $mofile );
	} elseif ( $locale === 'uk' || strpos( $locale, 'uk_' ) === 0 ) {
		$mofile_uk = FORWP_NOTIFICATIONS_PLUGIN_DIR . 'languages/forwp-notifications-uk_UA.mo';
		if ( is_readable( $mofile_uk ) ) {
			load_textdomain( 'forwp-notifications', $mofile_uk );
		}
	}
	if ( ! is_textdomain_loaded( 'forwp-notifications' ) ) {
		load_plugin_textdomain( 'forwp-notifications', false, dirname( FORWP_NOTIFICATIONS_PLUGIN_BASENAME ) . '/languages' );
	}

	ForWP_Notifications_Installer::maybe_install();
	ForWP_Notifications_REST_Controller::register();
	new ForWP_Notifications_Worker();
	new ForWP_Notifications_Shortcode();
	new ForWP_Notifications_Shortcode_Bell();
	new ForWP_Notifications_Block();

	if ( is_admin() ) {
		require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'admin/class-admin.php';
		new ForWP_Notifications_Admin();
	}

	add_action( 'woocommerce_init', 'forwp_notifications_init_woo' );
}

/**
 * Підключити WooCommerce-адаптер після завантаження WC (щоб хук order_status_changed спрацьовував).
 */
function forwp_notifications_init_woo() {
	require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'integrations/class-woo-adapter.php';
	new ForWP_Notifications_Woo_Adapter();
}
