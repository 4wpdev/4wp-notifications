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

	const TABLE_NAME            = '4wp_notifications';
	const FAVORITES_TABLE_NAME  = '4wp_favorites';

	/**
	 * Ensure tables exist (call on load if missing).
	 */
	public static function maybe_install() {
		global $wpdb;
		$table = $wpdb->prefix . self::TABLE_NAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $found !== $table ) {
			self::install();
			return;
		}

		$favorites_table = $wpdb->prefix . self::FAVORITES_TABLE_NAME;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$favorites_found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $favorites_table ) );
		if ( $favorites_found !== $favorites_table ) {
			self::install_favorites_table();
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
		self::install_favorites_table();
		update_option( '4wp_notifications_db_version', FORWP_NOTIFICATIONS_VERSION );
	}

	/**
	 * Create favorites table.
	 */
	public static function install_favorites_table() {
		global $wpdb;
		$table   = $wpdb->prefix . self::FAVORITES_TABLE_NAME;
		$charset = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			fav_type VARCHAR(20) NOT NULL,
			ref_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			ref_key VARCHAR(191) NOT NULL DEFAULT '',
			meta LONGTEXT DEFAULT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			UNIQUE KEY user_target (user_id, fav_type, ref_id, ref_key),
			KEY user_id (user_id)
		) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Drop table on uninstall.
	 */
	public static function uninstall() {
		global $wpdb;
		$table           = $wpdb->prefix . self::TABLE_NAME;
		$favorites_table = $wpdb->prefix . self::FAVORITES_TABLE_NAME;
		$sql             = $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table );
		$favorites_sql   = $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $favorites_table );

		$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Uninstall DDL; $sql from prepare() above.
		$wpdb->query( $favorites_sql ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.NotPrepared -- Uninstall DDL; $sql from prepare() above.
		delete_option( '4wp_notifications_db_version' );
	}
}
