<?php
/**
 * Worker: обробляє події з черги (Action Scheduler) і створює нотифікації.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Worker {

	public function __construct() {
		add_action( ForWP_Notifications_Queue::HOOK, array( $this, 'process' ), 10, 5 );
	}

	/**
	 * Обробити одну подію. Action Scheduler передає array_values($args) окремими аргументами.
	 *
	 * @param int    $user_id   User ID.
	 * @param string $type      Type.
	 * @param string $source    Source.
	 * @param array  $payload   Payload.
	 * @param int|null $object_id Object ID.
	 */
	public function process( $user_id, $type, $source, $payload, $object_id = null ) {
		$user_id = (int) $user_id;
		if ( $user_id <= 0 || empty( $type ) ) {
			return;
		}
		$manager = new ForWP_Notifications_Manager();
		$manager->create(
			$user_id,
			sanitize_text_field( $type ),
			! empty( $source ) ? sanitize_text_field( $source ) : 'core',
			is_array( $payload ) ? $payload : array(),
			$object_id ? (int) $object_id : null
		);
	}
}
