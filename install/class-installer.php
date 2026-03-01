<?php
/**
 * Installer: creates and drops notification table.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Installer {

	const TABLE_NAME = '4wp_notifications';

	/**
	 * Ensure table exists (call on load if missing).
	 */
	public static function maybe_install() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_NAME;
		if ( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->esc_like( $table ) . "'" ) !== $table ) {
			self::install();
		}
	}

	/**
	 * Create notification table on activation.
	 */
	public static function install() {
		global $wpdb;
		$table   = $wpdb->prefix . self::TABLE_NAME;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			type VARCHAR(64) NOT NULL,
			source VARCHAR(64) NOT NULL DEFAULT 'core',
			object_id BIGINT UNSIGNED DEFAULT NULL,
			payload LONGTEXT DEFAULT NULL,
			is_read TINYINT(1) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			scheduled_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY user_read (user_id, is_read),
			KEY user_created (user_id, created_at)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
		update_option( '4wp_notifications_db_version', FORWP_NOTIFICATIONS_VERSION );
	}

	/**
	 * Drop table on uninstall.
	 */
	public static function uninstall() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_NAME;
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		delete_option( '4wp_notifications_db_version' );
	}
}
