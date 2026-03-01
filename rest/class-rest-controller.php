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
		register_rest_route( self::NAMESPACE, '/notifications', array(
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
		) );
		register_rest_route( self::NAMESPACE, '/notifications/unread-count', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_unread_count' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			),
		) );
		register_rest_route( self::NAMESPACE, '/notifications/(?P<id>\d+)', array(
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'mark_read' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
				'args'                => array(
					'id' => array(
						'required'          => true,
						'type'              => 'integer',
						'validate_callback' => function ( $v ) { return $v > 0; },
					),
				),
			),
		) );
		register_rest_route( self::NAMESPACE, '/notifications/mark-all-read', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'mark_all_read' ),
				'permission_callback' => array( $this, 'check_logged_in' ),
			),
		) );
	}

	public function check_logged_in( WP_REST_Request $request ) {
		return is_user_logged_in();
	}

	public function get_items( WP_REST_Request $request ) {
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );
		$offset   = ( $page - 1 ) * $per_page;
		$items  = $this->manager->get_for_user( null, $per_page, $offset );
		$unread = $this->manager->count_unread( null );
		return new WP_REST_Response( array(
			'items'        => $items,
			'unread_count' => $unread,
		), 200 );
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
			return new WP_REST_Response( array( 'message' => __( 'Notification not found.', 'forwp-notifications' ) ), 404 );
		}
		return new WP_REST_Response( array( 'success' => true, 'is_read' => $is_read ), 200 );
	}

	public function mark_all_read( WP_REST_Request $request ) {
		$updated = $this->manager->mark_all_read( null );
		return new WP_REST_Response( array( 'success' => true, 'updated' => $updated ), 200 );
	}
}
