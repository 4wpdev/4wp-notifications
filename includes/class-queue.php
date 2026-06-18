<?php
/**
 * Queue: enqueue events (Action Scheduler) or create notifications synchronously.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Queue {

	const HOOK = 'forwp_notifications_process_event';

	/**
	 * Enqueue an event or create a notification immediately.
	 *
	 * Action Scheduler may delay execution by up to a minute. When $sync is true, write to the DB immediately.
	 *
	 * @param int    $user_id   User ID.
	 * @param string $type      Type.
	 * @param string $source    Source.
	 * @param array  $payload   Payload.
	 * @param int    $object_id Optional object ID.
	 * @param bool   $sync      True = write to DB immediately (skip queue). Use for admin broadcasts.
	 */
	public static function push( $user_id, $type, $source = 'core', $payload = array(), $object_id = null, $sync = false ) {
		if ( $sync ) {
			$manager = new ForWP_Notifications_Manager();
			$manager->create( $user_id, $type, $source, $payload, $object_id );
			return;
		}
		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action(
				time(),
				self::HOOK,
				array(
					(int) $user_id,
					$type,
					$source,
					$payload,
					$object_id ? (int) $object_id : null,
				),
				'forwp_notifications'
			);
			return;
		}
		$manager = new ForWP_Notifications_Manager();
		$manager->create( $user_id, $type, $source, $payload, $object_id );
	}
}
