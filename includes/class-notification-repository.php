<?php
/**
 * Notification repository: DB access for notifications.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Repository {

	const TABLE_NAME = '4wp_notifications';

	/**
	 * @var wpdb
	 */
	private $wpdb;

	/**
	 * @var string
	 */
	private $table;

	public function __construct() {
		global $wpdb;
		$this->wpdb  = $wpdb;
		$this->table = $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * Insert a notification.
	 *
	 * @param int    $user_id   User ID.
	 * @param string $type      Type (e.g. order_created, order_status_changed, custom).
	 * @param string $source    Source (e.g. woo, admin).
	 * @param array  $payload   Optional. Title, message, url, etc.
	 * @param int    $object_id Optional. Related object ID.
	 * @return int|false Insert ID or false.
	 */
	public function insert( $user_id, $type, $source = 'core', $payload = array(), $object_id = null ) {
		$payload_json = $payload ? wp_json_encode( $payload ) : null;
		$object_id    = $object_id ? (int) $object_id : null;
		$result       = $this->wpdb->insert(
			$this->table,
			array(
				'user_id'   => (int) $user_id,
				'type'      => sanitize_text_field( $type ),
				'source'    => sanitize_text_field( $source ),
				'object_id' => $object_id,
				'payload'   => $payload_json,
				'is_read'   => 0,
			),
			array( '%d', '%s', '%s', $object_id === null ? null : '%d', '%s', '%d' )
		);
		if ( $result === false && $this->wpdb->last_error ) {
			// Log so we can debug: error_log( '4wp_notifications insert: ' . $this->wpdb->last_error );
		}
		return $result ? (int) $this->wpdb->insert_id : false;
	}

	/**
	 * Get notifications for user.
	 *
	 * @param int $user_id User ID.
	 * @param int $limit   Max rows.
	 * @param int $offset  Offset.
	 * @return array Rows with id, user_id, type, source, object_id, payload, is_read, created_at.
	 */
	public function get_for_user( $user_id, $limit = 50, $offset = 0 ) {
		$user_id = (int) $user_id;
		$limit   = absint( $limit );
		$offset  = absint( $offset );
		$rows    = $this->wpdb->get_results(
			$this->wpdb->prepare(
				"SELECT id, user_id, type, source, object_id, payload, is_read, created_at FROM {$this->table} WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
				$user_id,
				$limit,
				$offset
			),
			ARRAY_A
		);
		foreach ( $rows as &$row ) {
			$row['payload'] = $row['payload'] ? json_decode( $row['payload'], true ) : array();
		}
		return $rows;
	}

	/**
	 * Count unread for user.
	 *
	 * @param int $user_id User ID.
	 * @return int
	 */
	public function count_unread( $user_id ) {
		return (int) $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT COUNT(*) FROM {$this->table} WHERE user_id = %d AND is_read = 0",
				(int) $user_id
			)
		);
	}

	/**
	 * Mark one notification as read.
	 *
	 * @param int $id      Notification ID.
	 * @param int $user_id User ID (must own the notification).
	 * @return bool
	 */
	public function mark_read( $id, $user_id ) {
		return (bool) $this->wpdb->update(
			$this->table,
			array( 'is_read' => 1 ),
			array( 'id' => (int) $id, 'user_id' => (int) $user_id ),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}

	/**
	 * Mark one notification as unread.
	 *
	 * @param int $id      Notification ID.
	 * @param int $user_id User ID (must own the notification).
	 * @return bool
	 */
	public function mark_unread( $id, $user_id ) {
		return (bool) $this->wpdb->update(
			$this->table,
			array( 'is_read' => 0 ),
			array( 'id' => (int) $id, 'user_id' => (int) $user_id ),
			array( '%d' ),
			array( '%d', '%d' )
		);
	}

	/**
	 * Mark all notifications for user as read.
	 *
	 * @param int $user_id User ID.
	 * @return int|false Number of rows updated or false.
	 */
	public function mark_all_read( $user_id ) {
		return $this->wpdb->update(
			$this->table,
			array( 'is_read' => 1 ),
			array( 'user_id' => (int) $user_id ),
			array( '%d' ),
			array( '%d' )
		);
	}
}
