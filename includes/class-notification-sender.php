<?php
/**
 * Shared API for creating notifications (REST, hooks, integrations).
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Sender {

	/**
	 * Capability required to send via REST.
	 *
	 * @return string
	 */
	public static function get_send_capability() {
		return apply_filters( 'forwp_notifications_send_capability', 'manage_options' );
	}

	/**
	 * @return bool
	 */
	public static function user_can_send() {
		return current_user_can( self::get_send_capability() );
	}

	/**
	 * @param int   $user_id  Single user ID.
	 * @param array $user_ids Multiple user IDs.
	 * @return int[]
	 */
	public static function normalize_user_ids( $user_id, $user_ids = array() ) {
		$ids = array();

		if ( is_array( $user_ids ) ) {
			$ids = array_map( 'intval', $user_ids );
		}

		if ( (int) $user_id > 0 ) {
			$ids[] = (int) $user_id;
		}

		$ids = array_values( array_unique( array_filter( $ids ) ) );

		return apply_filters( 'forwp_notifications_send_user_ids', $ids, $user_id, $user_ids );
	}

	/**
	 * @param mixed $payload Raw payload.
	 * @return array<string, mixed>
	 */
	public static function sanitize_payload( $payload ) {
		if ( ! is_array( $payload ) ) {
			return array();
		}

		$out = array();

		if ( isset( $payload['title'] ) ) {
			$out['title'] = sanitize_text_field( (string) $payload['title'] );
		}
		if ( isset( $payload['message'] ) ) {
			$out['message'] = sanitize_textarea_field( (string) $payload['message'] );
		}
		if ( ! empty( $payload['url'] ) ) {
			$out['url'] = esc_url_raw( (string) $payload['url'] );
		}

		return apply_filters( 'forwp_notifications_sanitize_payload', $out, $payload );
	}

	/**
	 * Create one or more notifications.
	 *
	 * @param int[]    $user_ids  Recipient user IDs.
	 * @param string   $type      Notification type slug.
	 * @param string   $source    Source slug (plugin/module name).
	 * @param array    $payload   title, message, url.
	 * @param int|null $object_id Related object ID.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function send( $user_ids, $type, $source = 'core', $payload = array(), $object_id = null ) {
		$user_ids  = array_filter( array_map( 'intval', (array) $user_ids ) );
		$type      = sanitize_key( (string) $type );
		$source    = sanitize_key( (string) $source );
		$source    = '' !== $source ? $source : 'core';
		$payload   = self::sanitize_payload( $payload );
		$object_id = null !== $object_id ? (int) $object_id : null;

		if ( '' === $type ) {
			return new WP_Error(
				'forwp_notifications_invalid_type',
				__( 'Notification type is required.', '4wp-notifications' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $payload['title'] ) ) {
			return new WP_Error(
				'forwp_notifications_missing_title',
				__( 'Notification title is required.', '4wp-notifications' ),
				array( 'status' => 400 )
			);
		}

		if ( empty( $user_ids ) ) {
			return new WP_Error(
				'forwp_notifications_missing_users',
				__( 'At least one user ID is required.', '4wp-notifications' ),
				array( 'status' => 400 )
			);
		}

		if ( ! empty( $payload['url'] ) ) {
			$payload['actions'] = array(
				array(
					'type'  => 'view',
					'label' => __( 'View', '4wp-notifications' ),
					'url'   => $payload['url'],
				),
			);
		}

		$payload = apply_filters( 'forwp_notifications_before_send', $payload, $user_ids, $type, $source, $object_id );

		$manager = new ForWP_Notifications_Manager();
		$created = array();
		$skipped = array();

		foreach ( $user_ids as $user_id ) {
			if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
				$skipped[] = $user_id;
				continue;
			}

			$notification_id = $manager->create( $user_id, $type, $source, $payload, $object_id );
			if ( $notification_id ) {
				$created[] = array(
					'user_id'         => $user_id,
					'notification_id' => (int) $notification_id,
				);
			}
		}

		if ( empty( $created ) ) {
			return new WP_Error(
				'forwp_notifications_send_failed',
				__( 'Could not create notifications for the given users.', '4wp-notifications' ),
				array(
					'status'           => 400,
					'skipped_user_ids' => $skipped,
				)
			);
		}

		return array(
			'success'          => true,
			'created'          => $created,
			'skipped_user_ids' => $skipped,
		);
	}
}
