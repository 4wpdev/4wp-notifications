<?php
/**
 * Developer hooks for firing notifications from custom code.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Event_Dispatcher {

	public function __construct() {
		add_action( 'forwp_notification_event', array( $this, 'handle_event' ), 10, 5 );
		add_action( '4wp_notification_event', array( $this, 'handle_event' ), 10, 5 );
	}

	/**
	 * @param int      $user_id   Recipient user ID.
	 * @param string   $type      Notification type.
	 * @param string   $source    Source slug.
	 * @param array    $payload   title, message, url.
	 * @param int|null $object_id Related object ID.
	 */
	public function handle_event( $user_id, $type, $source = 'core', $payload = array(), $object_id = null ) {
		ForWP_Notifications_Sender::send(
			array( (int) $user_id ),
			(string) $type,
			(string) $source,
			is_array( $payload ) ? $payload : array(),
			$object_id
		);
	}
}
