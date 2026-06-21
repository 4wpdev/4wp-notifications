<?php
/**
 * Plugin Name:       4WP Notifications
 * Plugin URI:        https://github.com/4wpdev/4wp-notifications
 * Description:       In-app notifications for logged-in users — bell block, inbox list, WooCommerce alerts, and admin broadcasts.
 * Version:           1.3.2
 * Requires at least: 6.4
 * Tested up to:      7.0
 * Requires PHP:      7.4
 * Author:            4wpdev
 * Author URI:        https://4wp.dev
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       4wp-notifications
 * Domain Path:       /languages
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FORWP_NOTIFICATIONS_VERSION', '1.3.2' );
define( 'FORWP_NOTIFICATIONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FORWP_NOTIFICATIONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'FORWP_NOTIFICATIONS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'install/class-installer.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-plugin-settings.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-recipient-resolver.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-notification-repository.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-notification-manager.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-notification-sender.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-event-dispatcher.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-queue.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-worker.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-shortcode.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-shortcode-bell.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-bell-renderer.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-list-renderer.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-page-display.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-block.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-bell-block.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-repository.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-manager.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-notifier.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-auth.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-list-renderer.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-button-renderer.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-menu-renderer.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-rest-controller.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-list-block.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-button-block.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-menu-block.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-page-display.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/favorites/class-favorites-shortcode.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'includes/class-notifications-account-bridge.php';
require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'rest/class-rest-controller.php';

register_activation_hook( __FILE__, array( 'ForWP_Notifications_Installer', 'install' ) );
register_uninstall_hook( __FILE__, array( 'ForWP_Notifications_Installer', 'uninstall' ) );

add_action( 'plugins_loaded', 'forwp_notifications_init' );

/**
 * Bootstrap plugin.
 */
function forwp_notifications_init() {
	// Optional bundled uk locale (wp.org loads translations automatically).
	$forwp_notif_locale = determine_locale();
	if ( 'uk' === $forwp_notif_locale || 0 === strpos( $forwp_notif_locale, 'uk_' ) ) {
		$mofile_uk = FORWP_NOTIFICATIONS_PLUGIN_DIR . 'languages/4wp-notifications-uk_UA.mo';
		if ( is_readable( $mofile_uk ) ) {
			load_textdomain( '4wp-notifications', $mofile_uk );
		}
	}

	ForWP_Notifications_Installer::maybe_install();
	ForWP_Notifications_REST_Controller::register();
	ForWP_Favorites_REST_Controller::register();
	new ForWP_Notifications_Event_Dispatcher();
	new ForWP_Notifications_Worker();
	new ForWP_Notifications_Shortcode();
	new ForWP_Notifications_Shortcode_Bell();
	new ForWP_Notifications_Page_Display();
	new ForWP_Notifications_Block();
	new ForWP_Notifications_Bell_Block();
	new ForWP_Favorites_Notifier();
	new ForWP_Favorites_Page_Display();
	new ForWP_Favorites_Shortcode();
	new ForWP_Favorites_List_Block();
	new ForWP_Favorites_Button_Block();
	new ForWP_Favorites_Menu_Block();
	new ForWP_Notifications_Account_Bridge();

	if ( is_admin() ) {
		require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'admin/class-admin.php';
		new ForWP_Notifications_Admin();
	}

	add_action( 'woocommerce_init', 'forwp_notifications_init_woo' );
}

/**
 * Load WooCommerce adapter after WooCommerce is available.
 */
function forwp_notifications_init_woo() {
	require_once FORWP_NOTIFICATIONS_PLUGIN_DIR . 'integrations/class-woo-adapter.php';
	new ForWP_Notifications_Woo_Adapter();
}
