<?php
/**
 * Notification manager: create, get, mark read. Single entry point for notifications.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Manager {

	/**
	 * @var ForWP_Notifications_Repository
	 */
	private $repository;

	public function __construct() {
		$this->repository = new ForWP_Notifications_Repository();
	}

	/**
	 * Create a notification (and fire action for third parties).
	 *
	 * @param int    $user_id   User ID.
	 * @param string $type      Type.
	 * @param string $source    Source.
	 * @param array  $payload   Optional. title, message, url, actions, etc.
	 * @param int    $object_id Optional.
	 * @return int|false Notification ID or false.
	 */
	public function create( $user_id, $type, $source = 'core', $payload = array(), $object_id = null ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}
		$id = $this->repository->insert( $user_id, $type, $source, $payload, $object_id );
		if ( $id ) {
			do_action( 'forwp_notification_created', $id, $user_id, $type, $source, $payload );
		}
		return $id;
	}

	/**
	 * Get notifications for current or given user.
	 *
	 * @param int|null $user_id User ID or null for current.
	 * @param int     $limit   Limit.
	 * @param int     $offset  Offset.
	 * @return array
	 */
	public function get_for_user( $user_id = null, $limit = 50, $offset = 0 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( $user_id <= 0 ) {
			return array();
		}
		return $this->repository->get_for_user( $user_id, $limit, $offset );
	}

	/**
	 * Count unread for user.
	 *
	 * @param int|null $user_id User ID or null for current.
	 * @return int
	 */
	public function count_unread( $user_id = null ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( $user_id <= 0 ) {
			return 0;
		}
		return $this->repository->count_unread( $user_id );
	}

	/**
	 * Mark one as read.
	 *
	 * @param int     $id     Notification ID.
	 * @param int|null $user_id User ID or null for current.
	 * @return bool
	 */
	public function mark_read( $id, $user_id = null ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( $user_id <= 0 ) {
			return false;
		}
		return $this->repository->mark_read( (int) $id, $user_id );
	}

	/**
	 * Mark one as unread.
	 *
	 * @param int     $id     Notification ID.
	 * @param int|null $user_id User ID or null for current.
	 * @return bool
	 */
	public function mark_unread( $id, $user_id = null ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( $user_id <= 0 ) {
			return false;
		}
		return $this->repository->mark_unread( (int) $id, $user_id );
	}

	/**
	 * Mark all as read for user.
	 *
	 * @param int|null $user_id User ID or null for current.
	 * @return int|false
	 */
	public function mark_all_read( $user_id = null ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( $user_id <= 0 ) {
			return false;
		}
		return $this->repository->mark_all_read( $user_id );
	}
}
