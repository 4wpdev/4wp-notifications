<?php
/**
 * REST API: list notifications, mark read, mark all read.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_REST_Controller {

	const NAMESPACE = 'forwp/v1';

	/**
	 * @var ForWP_Notifications_Manager
	 */
	private $manager;

	public function __construct() {
		$this->manager = new ForWP_Notifications_Manager();
	}

	/**
	 * Register REST routes.
	 */
	public static function register() {
		$controller = new self();
		add_action( 'rest_api_init', array( $controller, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/notifications',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => array(
						'per_page' => array(
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'check_can_send' ),
					'args'                => $this->get_create_args(),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/notifications/unread-count',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_unread_count' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/notifications/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'mark_read' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'integer',
							'validate_callback' => function ( $v ) {
								return $v > 0; },
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'type'              => 'integer',
							'validate_callback' => function ( $v ) {
								return $v > 0; },
						),
					),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/notifications/mark-all-read',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'mark_all_read' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
				),
			)
		);
		register_rest_route(
			self::NAMESPACE,
			'/notifications/delete',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_all' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
				),
			)
		);
	}

	public function check_logged_in( WP_REST_Request $request ) {
		return is_user_logged_in();
	}

	/**
	 * @return bool|WP_Error
	 */
	public function check_can_send( WP_REST_Request $request ) {
		if ( ! ForWP_Notifications_Sender::user_can_send() ) {
			return new WP_Error(
				'forwp_notifications_forbidden',
				__( 'You are not allowed to send notifications.', '4wp-notifications' ),
				array( 'status' => 403 )
			);
		}

		return true;
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function get_create_args() {
		return array(
			'user_id'   => array(
				'type'              => 'integer',
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'user_ids'  => array(
				'type'  => 'array',
				'items' => array(
					'type'              => 'integer',
					'minimum'           => 1,
					'sanitize_callback' => 'absint',
				),
			),
			'type'      => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_key',
			),
			'source'    => array(
				'type'              => 'string',
				'default'           => 'core',
				'sanitize_callback' => 'sanitize_key',
			),
			'title'     => array(
				'type'              => 'string',
				'required'          => true,
				'sanitize_callback' => 'sanitize_text_field',
			),
			'message'   => array(
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
			),
			'url'       => array(
				'type'              => 'string',
				'format'            => 'uri',
				'sanitize_callback' => 'esc_url_raw',
			),
			'object_id' => array(
				'type'              => 'integer',
				'minimum'           => 1,
				'sanitize_callback' => 'absint',
			),
			'payload'   => array(
				'type'                 => 'object',
				'additionalProperties' => true,
			),
		);
	}

	public function create_item( WP_REST_Request $request ) {
		$user_ids = ForWP_Notifications_Sender::normalize_user_ids(
			(int) $request->get_param( 'user_id' ),
			(array) $request->get_param( 'user_ids' )
		);

		$payload = $request->get_param( 'payload' );
		$payload = is_array( $payload ) ? $payload : array();

		foreach ( array( 'title', 'message', 'url' ) as $key ) {
			$value = $request->get_param( $key );
			if ( null !== $value && '' !== $value && ! isset( $payload[ $key ] ) ) {
				$payload[ $key ] = $value;
			}
		}

		$result = ForWP_Notifications_Sender::send(
			$user_ids,
			(string) $request->get_param( 'type' ),
			(string) $request->get_param( 'source' ),
			$payload,
			$request->get_param( 'object_id' ) ? (int) $request->get_param( 'object_id' ) : null
		);

		if ( is_wp_error( $result ) ) {
			$data   = $result->get_error_data();
			$status = is_array( $data ) && isset( $data['status'] ) ? (int) $data['status'] : 400;

			return new WP_REST_Response(
				array(
					'code'    => $result->get_error_code(),
					'message' => $result->get_error_message(),
					'data'    => $result->get_error_data(),
				),
				$status
			);
		}

		return new WP_REST_Response( $result, 201 );
	}

	public function get_items( WP_REST_Request $request ) {
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );
		$offset   = ( $page - 1 ) * $per_page;
		$items    = $this->manager->get_for_user( null, $per_page, $offset );
		$unread   = $this->manager->count_unread( null );
		return new WP_REST_Response(
			array(
				'items'        => $items,
				'unread_count' => $unread,
			),
			200
		);
	}

	public function get_unread_count( WP_REST_Request $request ) {
		$count = $this->manager->count_unread( null );
		return new WP_REST_Response( array( 'unread_count' => $count ), 200 );
	}

	public function mark_read( WP_REST_Request $request ) {
		$id      = (int) $request->get_param( 'id' );
		$body    = $request->get_json_params();
		$is_read = isset( $body['is_read'] ) ? (bool) $body['is_read'] : true;
		$ok      = $is_read
			? $this->manager->mark_read( $id, null )
			: $this->manager->mark_unread( $id, null );
		if ( ! $ok ) {
			return new WP_REST_Response( array( 'message' => __( 'Notification not found.', '4wp-notifications' ) ), 404 );
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'is_read' => $is_read,
			),
			200
		);
	}

	public function mark_all_read( WP_REST_Request $request ) {
		$updated = $this->manager->mark_all_read( null );
		return new WP_REST_Response(
			array(
				'success' => true,
				'updated' => $updated,
			),
			200
		);
	}

	public function delete( WP_REST_Request $request ) {
		$ok      = $this->manager->delete( $id, null );
		if ( ! $ok ) {
			return new WP_REST_Response( array( 'message' => __( 'Notification not found.', '4wp-notifications' ) ), 404 );
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'removed' => true,
			),
			200
		);
	}

	public function delete_all( WP_REST_Request $request ) {
		$ok      = $this->manager->delete_all();
		if ( ! $ok ) {
			return new WP_REST_Response( array( 'message' => __( 'No notifications to delete.', '4wp-notifications' ) ), 404 );
		}
		return new WP_REST_Response(
			array(
				'success' => true,
				'removed' => true,
			),
			200
		);
	}
}
