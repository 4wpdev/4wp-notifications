<?php
/**
 * REST API for favorites.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Favorites_REST_Controller {

	/**
	 * @var ForWP_Favorites_Manager
	 */
	private $manager;

	public function __construct() {
		$this->manager = new ForWP_Favorites_Manager();
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
			ForWP_Notifications_REST_Controller::NAMESPACE,
			'/favorites',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => array(
						'view'  => array(
							'type'              => 'string',
							'default'           => 'grouped',
							'enum'              => array( 'grouped', 'recent' ),
							'sanitize_callback' => 'sanitize_key',
						),
						'limit' => array(
							'type'              => 'integer',
							'default'           => 5,
							'minimum'           => 1,
							'maximum'           => 50,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			ForWP_Notifications_REST_Controller::NAMESPACE,
			'/favorites/status',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => $this->get_target_args(),
				),
			)
		);

		register_rest_route(
			ForWP_Notifications_REST_Controller::NAMESPACE,
			'/favorites/toggle',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'toggle' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => $this->get_target_args(),
				),
			)
		);

		register_rest_route(
			ForWP_Notifications_REST_Controller::NAMESPACE,
			'/favorites/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'check_logged_in' ),
					'args'                => array(
						'id' => array(
							'required' => true,
							'type'     => 'integer',
						),
					),
				),
			)
		);
	}

	/**
	 * @return bool
	 */
	public function check_logged_in() {
		return is_user_logged_in();
	}

	/**
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_items( WP_REST_Request $request ) {
		$user_id = get_current_user_id();
		$view    = sanitize_key( (string) $request->get_param( 'view' ) );
		$limit   = (int) $request->get_param( 'limit' );

		if ( 'recent' === $view ) {
			return rest_ensure_response(
				array(
					'items' => $this->manager->get_recent_for_user( $user_id, $limit ),
					'total' => $this->manager->count_for_user( $user_id ),
				)
			);
		}

		$groups = $this->manager->get_grouped_for_user( $user_id );

		return rest_ensure_response(
			array(
				'groups' => array_values( $groups ),
				'total'  => $this->manager->count_for_user( $user_id ),
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_status( WP_REST_Request $request ) {
		$target = $this->parse_target( $request );
		if ( is_wp_error( $target ) ) {
			return $target;
		}

		$active = $this->manager->is_active(
			get_current_user_id(),
			$target['type'],
			$target['ref_id'],
			$target['ref_key']
		);

		return rest_ensure_response(
			array(
				'active'  => $active,
				'type'    => $target['type'],
				'ref_id'  => $target['ref_id'],
				'ref_key' => $target['ref_key'],
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function toggle( WP_REST_Request $request ) {
		$target = $this->parse_target( $request );
		if ( is_wp_error( $target ) ) {
			return $target;
		}

		$result = $this->manager->toggle(
			get_current_user_id(),
			$target['type'],
			$target['ref_id'],
			$target['ref_key']
		);

		return rest_ensure_response(
			array_merge(
				$result,
				array(
					'type'    => $target['type'],
					'ref_id'  => $target['ref_id'],
					'ref_key' => $target['ref_key'],
				)
			)
		);
	}

	/**
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_item( WP_REST_Request $request ) {
		$id      = (int) $request->get_param( 'id' );
		$user_id = get_current_user_id();
		$repo    = new ForWP_Favorites_Repository();

		if ( ! $repo->remove_by_id( $id, $user_id ) ) {
			return new WP_Error(
				'forwp_favorites_not_found',
				__( 'Favorite not found.', '4wp-notifications' ),
				array( 'status' => 404 )
			);
		}

		return rest_ensure_response(
			array(
				'deleted' => true,
				'id'      => $id,
			)
		);
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	private function get_target_args() {
		return array(
			'type'    => array(
				'required'          => true,
				'type'              => 'string',
				'enum'              => ForWP_Favorites_Repository::allowed_types(),
				'sanitize_callback' => 'sanitize_key',
			),
			'ref_id'  => array(
				'type'              => 'integer',
				'default'           => 0,
				'sanitize_callback' => 'absint',
			),
			'ref_key' => array(
				'type'              => 'string',
				'default'           => '',
				'sanitize_callback' => 'sanitize_key',
			),
		);
	}

	/**
	 * @param WP_REST_Request $request Request object.
	 * @return array{type:string,ref_id:int,ref_key:string}|WP_Error
	 */
	private function parse_target( WP_REST_Request $request ) {
		$type    = sanitize_key( (string) $request->get_param( 'type' ) );
		$ref_id  = (int) $request->get_param( 'ref_id' );
		$ref_key = sanitize_key( (string) $request->get_param( 'ref_key' ) );

		if ( ! $this->manager->validate_target( $type, $ref_id, $ref_key ) ) {
			return new WP_Error(
				'forwp_favorites_invalid_target',
				__( 'Invalid favorite target.', '4wp-notifications' ),
				array( 'status' => 400 )
			);
		}

		return array(
			'type'    => $type,
			'ref_id'  => $ref_id,
			'ref_key' => $ref_key,
		);
	}
}
