<?php
/**
 * Favorites business logic.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Favorites_Manager {

	/**
	 * @var ForWP_Favorites_Repository
	 */
	private $repository;

	public function __construct() {
		$this->repository = new ForWP_Favorites_Repository();
	}

	/**
	 * @param int|null $user_id User ID or current user.
	 * @return array<string, array<string, mixed>>
	 */
	public function get_grouped_for_user( $user_id = null ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( $user_id <= 0 ) {
			return array();
		}

		$rows   = $this->repository->get_for_user( $user_id );
		$groups = array();

		foreach ( $rows as $row ) {
			$item = $this->format_row( $row );
			if ( null === $item ) {
				continue;
			}

			$group_key = (string) $item['group_key'];
			if ( ! isset( $groups[ $group_key ] ) ) {
				$groups[ $group_key ] = array(
					'key'   => $group_key,
					'label' => (string) $item['group_label'],
					'items' => array(),
				);
			}

			$groups[ $group_key ]['items'][] = $item;
		}

		uasort(
			$groups,
			static function ( array $a, array $b ): int {
				return strcasecmp( (string) $a['label'], (string) $b['label'] );
			}
		);

		return $groups;
	}

	/**
	 * Flat list of recently added favorites.
	 *
	 * @param int|null $user_id User ID or current user.
	 * @param int      $limit   Max items.
	 * @return array<int, array<string, mixed>>
	 */
	public function get_recent_for_user( $user_id = null, $limit = 5 ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( $user_id <= 0 ) {
			return array();
		}

		$limit = absint( $limit );
		$limit = $limit > 0 ? min( $limit, 50 ) : 5;

		$rows  = $this->repository->get_for_user( $user_id );
		$items = array();

		foreach ( $rows as $row ) {
			$item = $this->format_row( $row );
			if ( null !== $item ) {
				$items[] = $item;
			}
			if ( count( $items ) >= $limit ) {
				break;
			}
		}

		return $items;
	}

	/**
	 * @param int|null $user_id User ID or current user.
	 * @return int
	 */
	public function count_for_user( $user_id = null ) {
		$user_id = $user_id ? (int) $user_id : get_current_user_id();
		if ( $user_id <= 0 ) {
			return 0;
		}

		return count( $this->repository->get_for_user( $user_id ) );
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $type    Favorite type.
	 * @param int    $ref_id  Reference ID.
	 * @param string $ref_key Reference key.
	 * @return array{active:bool,id:int|null,item:array|null}
	 */
	public function toggle( $user_id, $type, $ref_id, $ref_key = '' ) {
		$user_id = (int) $user_id;
		$type    = sanitize_key( $type );
		$ref_id  = (int) $ref_id;
		$ref_key = sanitize_key( $ref_key );

		if ( $user_id <= 0 || ! $this->validate_target( $type, $ref_id, $ref_key ) ) {
			return array(
				'active' => false,
				'id'     => null,
				'item'   => null,
			);
		}

		if ( $this->repository->exists( $user_id, $type, $ref_id, $ref_key ) ) {
			$this->repository->remove( $user_id, $type, $ref_id, $ref_key );
			return array(
				'active' => false,
				'id'     => null,
				'item'   => null,
			);
		}

		$id = $this->repository->add( $user_id, $type, $ref_id, $ref_key );
		$row = (object) array(
			'id'         => $id,
			'user_id'    => $user_id,
			'fav_type'   => $type,
			'ref_id'     => $ref_id,
			'ref_key'    => $ref_key,
			'meta'       => null,
			'created_at' => current_time( 'mysql', true ),
		);

		return array(
			'active' => true,
			'id'     => $id ? (int) $id : null,
			'item'   => $this->format_row( $row ),
		);
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $type    Favorite type.
	 * @param int    $ref_id  Reference ID.
	 * @param string $ref_key Reference key.
	 * @return bool
	 */
	public function is_active( $user_id, $type, $ref_id, $ref_key = '' ) {
		return $this->repository->exists( (int) $user_id, $type, (int) $ref_id, $ref_key );
	}

	/**
	 * Resolve favorite target from block attributes and template context.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param array<string, mixed> $context    Block context.
	 * @return array{type:string,ref_id:int,ref_key:string}|null
	 */
	public function resolve_target( array $attributes, array $context = array() ) {
		$mode = isset( $attributes['targetMode'] ) ? sanitize_key( (string) $attributes['targetMode'] ) : 'auto';

		if ( 'post' === $mode || ( 'auto' === $mode && ! empty( $context['postId'] ) ) ) {
			$post_id = 'post' === $mode && ! empty( $attributes['postId'] )
				? (int) $attributes['postId']
				: (int) ( $context['postId'] ?? 0 );

			if ( $post_id > 0 && get_post( $post_id ) ) {
				return array(
					'type'    => ForWP_Favorites_Repository::TYPE_POST,
					'ref_id'  => $post_id,
					'ref_key' => '',
				);
			}
		}

		if ( 'post_type' === $mode || ( 'auto' === $mode && is_post_type_archive() ) ) {
			$post_type = 'post_type' === $mode && ! empty( $attributes['postTypeSlug'] )
				? sanitize_key( (string) $attributes['postTypeSlug'] )
				: (string) get_query_var( 'post_type' );

			if ( is_array( $post_type ) ) {
				$post_type = reset( $post_type );
			}

			if ( $post_type && post_type_exists( $post_type ) ) {
				return array(
					'type'    => ForWP_Favorites_Repository::TYPE_POST_TYPE,
					'ref_id'  => 0,
					'ref_key' => sanitize_key( $post_type ),
				);
			}
		}

		if ( 'term' === $mode || ( 'auto' === $mode && ( is_category() || is_tag() || is_tax() ) ) ) {
			$term_id  = 'term' === $mode && ! empty( $attributes['termId'] ) ? (int) $attributes['termId'] : 0;
			$taxonomy = 'term' === $mode && ! empty( $attributes['taxonomy'] )
				? sanitize_key( (string) $attributes['taxonomy'] )
				: '';

			if ( $term_id <= 0 || '' === $taxonomy ) {
				$queried = get_queried_object();
				if ( $queried instanceof WP_Term ) {
					$term_id  = (int) $queried->term_id;
					$taxonomy = (string) $queried->taxonomy;
				}
			}

			if ( $term_id > 0 && $taxonomy && taxonomy_exists( $taxonomy ) ) {
				return array(
					'type'    => ForWP_Favorites_Repository::TYPE_TERM,
					'ref_id'  => $term_id,
					'ref_key' => sanitize_key( $taxonomy ),
				);
			}
		}

		if ( 'auto' === $mode && ! empty( $context['postId'] ) ) {
			return array(
				'type'    => ForWP_Favorites_Repository::TYPE_POST,
				'ref_id'  => (int) $context['postId'],
				'ref_key' => '',
			);
		}

		return null;
	}

	/**
	 * @param string $type    Favorite type.
	 * @param int    $ref_id  Reference ID.
	 * @param string $ref_key Reference key.
	 * @return bool
	 */
	public function validate_target( $type, $ref_id, $ref_key ) {
		$type    = sanitize_key( $type );
		$ref_id  = (int) $ref_id;
		$ref_key = sanitize_key( $ref_key );

		switch ( $type ) {
			case ForWP_Favorites_Repository::TYPE_POST:
				$post = get_post( $ref_id );
				return $post instanceof WP_Post && 'publish' === $post->post_status;

			case ForWP_Favorites_Repository::TYPE_POST_TYPE:
				return '' !== $ref_key && post_type_exists( $ref_key );

			case ForWP_Favorites_Repository::TYPE_TERM:
				$term = get_term( $ref_id, $ref_key );
				return $term instanceof WP_Term && ! is_wp_error( $term );

			default:
				return false;
		}
	}

	/**
	 * @param object $row DB row.
	 * @return array<string, mixed>|null
	 */
	private function format_row( $row ) {
		$type    = isset( $row->fav_type ) ? sanitize_key( (string) $row->fav_type ) : '';
		$ref_id  = isset( $row->ref_id ) ? (int) $row->ref_id : 0;
		$ref_key = isset( $row->ref_key ) ? sanitize_key( (string) $row->ref_key ) : '';

		switch ( $type ) {
			case ForWP_Favorites_Repository::TYPE_POST:
				$post = get_post( $ref_id );
				if ( ! $post instanceof WP_Post ) {
					return null;
				}
				$post_type_obj = get_post_type_object( $post->post_type );
				return array(
					'id'          => (int) $row->id,
					'type'        => $type,
					'ref_id'      => $ref_id,
					'ref_key'     => '',
					'title'       => get_the_title( $post ),
					'url'         => get_permalink( $post ),
					'group_key'   => $post->post_type,
					'group_label' => $post_type_obj ? (string) $post_type_obj->labels->name : $post->post_type,
					'subtitle'    => __( 'Saved item', '4wp-notifications' ),
					'created_at'  => isset( $row->created_at ) ? (string) $row->created_at : '',
				);

			case ForWP_Favorites_Repository::TYPE_POST_TYPE:
				if ( ! post_type_exists( $ref_key ) ) {
					return null;
				}
				$post_type_obj = get_post_type_object( $ref_key );
				$archive_url   = get_post_type_archive_link( $ref_key );
				return array(
					'id'          => (int) $row->id,
					'type'        => $type,
					'ref_id'      => 0,
					'ref_key'     => $ref_key,
					'title'       => $post_type_obj ? (string) $post_type_obj->labels->name : $ref_key,
					'url'         => $archive_url ? $archive_url : '',
					'group_key'   => $ref_key,
					'group_label' => $post_type_obj ? (string) $post_type_obj->labels->name : $ref_key,
					'subtitle'    => __( 'All new posts', '4wp-notifications' ),
					'created_at'  => isset( $row->created_at ) ? (string) $row->created_at : '',
				);

			case ForWP_Favorites_Repository::TYPE_TERM:
				$term = get_term( $ref_id, $ref_key );
				if ( ! $term instanceof WP_Term || is_wp_error( $term ) ) {
					return null;
				}
				$tax_obj   = get_taxonomy( $ref_key );
				$group_key = $this->get_term_group_key( $term, $ref_key );
				return array(
					'id'          => (int) $row->id,
					'type'        => $type,
					'ref_id'      => $ref_id,
					'ref_key'     => $ref_key,
					'title'       => $term->name,
					'url'         => get_term_link( $term ),
					'group_key'   => $group_key,
					'group_label' => $this->get_group_label_for_key( $group_key, $tax_obj ? (string) $tax_obj->labels->name : $ref_key ),
					'subtitle'    => __( 'Category subscription', '4wp-notifications' ),
					'created_at'  => isset( $row->created_at ) ? (string) $row->created_at : '',
				);
		}

		return null;
	}

	/**
	 * @param WP_Term $term     Term object.
	 * @param string  $taxonomy Taxonomy slug.
	 * @return string
	 */
	private function get_term_group_key( WP_Term $term, $taxonomy ) {
		$tax_obj = get_taxonomy( $taxonomy );
		if ( $tax_obj && ! empty( $tax_obj->object_type ) ) {
			$post_type = reset( $tax_obj->object_type );
			if ( is_string( $post_type ) && post_type_exists( $post_type ) ) {
				return $post_type;
			}
		}

		return 'taxonomy:' . sanitize_key( $taxonomy );
	}

	/**
	 * @param string $group_key Group key.
	 * @param string $fallback  Fallback label.
	 * @return string
	 */
	private function get_group_label_for_key( $group_key, $fallback ) {
		if ( 0 === strpos( $group_key, 'taxonomy:' ) ) {
			return $fallback;
		}

		$post_type_obj = get_post_type_object( $group_key );
		return $post_type_obj ? (string) $post_type_obj->labels->name : $fallback;
	}
}
