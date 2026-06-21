<?php
/**
 * Favorites storage.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Favorites_Repository {

	const TABLE_NAME = '4wp_favorites';

	const TYPE_POST      = 'post';
	const TYPE_POST_TYPE = 'post_type';
	const TYPE_TERM      = 'term';

	/**
	 * @return string
	 */
	public static function table_name() {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_NAME;
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $type    Favorite type.
	 * @param int    $ref_id  Post or term ID.
	 * @param string $ref_key Post type slug or taxonomy slug.
	 * @param array  $meta    Optional metadata.
	 * @return int|false Insert ID or false.
	 */
	public function add( $user_id, $type, $ref_id, $ref_key = '', $meta = array() ) {
		global $wpdb;

		$user_id = (int) $user_id;
		$type    = sanitize_key( $type );
		$ref_id  = (int) $ref_id;
		$ref_key = sanitize_key( $ref_key );

		if ( $user_id <= 0 || ! in_array( $type, self::allowed_types(), true ) ) {
			return false;
		}

		$meta_json = ! empty( $meta ) ? wp_json_encode( $meta ) : null;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert(
			self::table_name(),
			array(
				'user_id'    => $user_id,
				'fav_type'   => $type,
				'ref_id'     => $ref_id,
				'ref_key'    => $ref_key,
				'meta'       => $meta_json,
				'created_at' => current_time( 'mysql', true ),
			),
			array( '%d', '%s', '%d', '%s', '%s', '%s' )
		);

		return false !== $result ? (int) $wpdb->insert_id : false;
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $type    Favorite type.
	 * @param int    $ref_id  Reference ID.
	 * @param string $ref_key Reference key.
	 * @return bool
	 */
	public function remove( $user_id, $type, $ref_id, $ref_key = '' ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			self::table_name(),
			array(
				'user_id'  => (int) $user_id,
				'fav_type' => sanitize_key( $type ),
				'ref_id'   => (int) $ref_id,
				'ref_key'  => sanitize_key( $ref_key ),
			),
			array( '%d', '%s', '%d', '%s' )
		);

		return false !== $deleted && $deleted > 0;
	}

	/**
	 * @param int $id      Favorite row ID.
	 * @param int $user_id User ID.
	 * @return bool
	 */
	public function remove_by_id( $id, $user_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete(
			self::table_name(),
			array(
				'id'      => (int) $id,
				'user_id' => (int) $user_id,
			),
			array( '%d', '%d' )
		);

		return false !== $deleted && $deleted > 0;
	}

	/**
	 * @param int    $user_id User ID.
	 * @param string $type    Favorite type.
	 * @param int    $ref_id  Reference ID.
	 * @param string $ref_key Reference key.
	 * @return bool
	 */
	public function exists( $user_id, $type, $ref_id, $ref_key = '' ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$found = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM %i WHERE user_id = %d AND fav_type = %s AND ref_id = %d AND ref_key = %s LIMIT 1',
				self::table_name(),
				(int) $user_id,
				sanitize_key( $type ),
				(int) $ref_id,
				sanitize_key( $ref_key )
			)
		);

		return null !== $found;
	}

	/**
	 * @param int $user_id User ID.
	 * @return array<int, object>
	 */
	public function get_for_user( $user_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM %i WHERE user_id = %d ORDER BY created_at DESC',
				self::table_name(),
				(int) $user_id
			)
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * @param string $post_type Post type slug.
	 * @return int[]
	 */
	public function get_user_ids_for_post_type( $post_type ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT DISTINCT user_id FROM %i WHERE fav_type = %s AND ref_key = %s',
				self::table_name(),
				self::TYPE_POST_TYPE,
				sanitize_key( $post_type )
			)
		);

		return array_map( 'intval', is_array( $ids ) ? $ids : array() );
	}

	/**
	 * @param int $post_id Post ID.
	 * @return int[]
	 */
	public function get_user_ids_for_post( $post_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT DISTINCT user_id FROM %i WHERE fav_type = %s AND ref_id = %d',
				self::table_name(),
				self::TYPE_POST,
				(int) $post_id
			)
		);

		return array_map( 'intval', is_array( $ids ) ? $ids : array() );
	}

	/**
	 * @param int    $term_id  Term ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return int[]
	 */
	public function get_user_ids_for_term( $term_id, $taxonomy ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				'SELECT DISTINCT user_id FROM %i WHERE fav_type = %s AND ref_id = %d AND ref_key = %s',
				self::table_name(),
				self::TYPE_TERM,
				(int) $term_id,
				sanitize_key( $taxonomy )
			)
		);

		return array_map( 'intval', is_array( $ids ) ? $ids : array() );
	}

	/**
	 * @return string[]
	 */
	public static function allowed_types() {
		return array(
			self::TYPE_POST,
			self::TYPE_POST_TYPE,
			self::TYPE_TERM,
		);
	}
}
