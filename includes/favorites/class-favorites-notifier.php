<?php
/**
 * Creates notifications when favorited content changes.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Favorites_Notifier {

	/**
	 * @var ForWP_Favorites_Repository
	 */
	private $repository;

	public function __construct() {
		$this->repository = new ForWP_Favorites_Repository();

		add_action( 'transition_post_status', array( $this, 'on_transition_post_status' ), 10, 3 );
		add_action( 'post_updated', array( $this, 'on_post_updated' ), 10, 3 );
	}

	/**
	 * Notify followers when a post is first published.
	 *
	 * @param string  $new_status New status.
	 * @param string  $old_status Old status.
	 * @param WP_Post $post       Post object.
	 */
	public function on_transition_post_status( $new_status, $old_status, $post ) {
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		if ( ! $post instanceof WP_Post || wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}

		if ( ! ForWP_Notifications_Plugin_Settings::is_favorites_new_post_enabled() ) {
			return;
		}

		$this->notify_new_post( $post );
	}

	/**
	 * Notify followers when a published post is updated.
	 *
	 * @param int     $post_id     Post ID.
	 * @param WP_Post $post_after  Post object after update.
	 * @param WP_Post $post_before Post object before update.
	 */
	public function on_post_updated( $post_id, $post_after, $post_before ) {
		unset( $post_id );

		if ( ! $post_after instanceof WP_Post || ! $post_before instanceof WP_Post ) {
			return;
		}

		if ( 'publish' !== $post_after->post_status || 'publish' !== $post_before->post_status ) {
			return;
		}

		if ( wp_is_post_revision( $post_after ) || wp_is_post_autosave( $post_after ) ) {
			return;
		}

		if ( ! ForWP_Notifications_Plugin_Settings::is_favorites_post_updated_enabled() ) {
			return;
		}

		if ( $post_after->post_modified_gmt === $post_before->post_modified_gmt ) {
			return;
		}

		$this->notify_post_updated( $post_after );
	}

	/**
	 * @param WP_Post $post Post object.
	 */
	private function notify_new_post( WP_Post $post ) {
		$user_ids = array_unique(
			array_merge(
				$this->repository->get_user_ids_for_post_type( $post->post_type ),
				$this->get_term_follower_ids( $post )
			)
		);

		if ( empty( $user_ids ) ) {
			return;
		}

		$post_type_obj = get_post_type_object( $post->post_type );
		$type_label    = $post_type_obj ? (string) $post_type_obj->labels->singular_name : $post->post_type;
		$title         = get_the_title( $post );
		$url           = get_permalink( $post );

		$payload = array(
			'title'   => sprintf(
				/* translators: %s: post type singular label */
				__( 'New %s', '4wp-notifications' ),
				$type_label
			),
			'message' => $title,
			'url'     => $url,
			'actions' => array(
				array(
					'type'  => 'view',
					'label' => __( 'View', '4wp-notifications' ),
					'url'   => $url,
				),
			),
		);

		foreach ( $user_ids as $user_id ) {
			if ( $user_id > 0 ) {
				ForWP_Notifications_Queue::push( $user_id, 'favorite_new_post', 'favorites', $payload, $post->ID );
			}
		}
	}

	/**
	 * @param WP_Post $post Post object.
	 */
	private function notify_post_updated( WP_Post $post ) {
		$user_ids = $this->repository->get_user_ids_for_post( $post->ID );
		if ( empty( $user_ids ) ) {
			return;
		}

		$title = get_the_title( $post );
		$url   = get_permalink( $post );

		$payload = array(
			'title'   => __( 'Updated favorite', '4wp-notifications' ),
			'message' => $title,
			'url'     => $url,
			'actions' => array(
				array(
					'type'  => 'view',
					'label' => __( 'View', '4wp-notifications' ),
					'url'   => $url,
				),
			),
		);

		foreach ( $user_ids as $user_id ) {
			if ( $user_id > 0 ) {
				ForWP_Notifications_Queue::push( $user_id, 'favorite_post_updated', 'favorites', $payload, $post->ID );
			}
		}
	}

	/**
	 * @param WP_Post $post Post object.
	 * @return int[]
	 */
	private function get_term_follower_ids( WP_Post $post ) {
		$taxonomies = get_object_taxonomies( $post->post_type, 'names' );
		$user_ids   = array();

		foreach ( $taxonomies as $taxonomy ) {
			$term_ids = wp_get_post_terms( $post->ID, $taxonomy, array( 'fields' => 'ids' ) );
			if ( is_wp_error( $term_ids ) || empty( $term_ids ) ) {
				continue;
			}

			foreach ( $term_ids as $term_id ) {
				$user_ids = array_merge( $user_ids, $this->repository->get_user_ids_for_term( (int) $term_id, $taxonomy ) );
			}
		}

		return array_map( 'intval', $user_ids );
	}
}
