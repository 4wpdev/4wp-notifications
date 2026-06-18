<?php
/**
 * Resolve admin notification recipients from roles and explicit user picks.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Recipient_Resolver {

	/**
	 * Merge selected user IDs with all users in selected roles.
	 *
	 * @param int[]    $user_ids   Explicit user IDs from checkboxes.
	 * @param string[] $role_slugs Role slugs from role checkboxes.
	 * @return int[]
	 */
	public static function resolve_ids( array $user_ids, array $role_slugs ) {
		$ids = array_map( 'intval', $user_ids );
		$ids = array_filter( $ids );

		foreach ( $role_slugs as $role ) {
			$role = sanitize_key( (string) $role );
			if ( '' === $role ) {
				continue;
			}
			$role_users = get_users(
				array(
					'role'   => $role,
					'fields' => 'ID',
				)
			);
			if ( is_array( $role_users ) ) {
				$ids = array_merge( $ids, array_map( 'intval', $role_users ) );
			}
		}

		$ids = array_values( array_unique( array_filter( $ids ) ) );
		sort( $ids, SORT_NUMERIC );

		return $ids;
	}

	/**
	 * Users grouped by primary role for the admin recipient UI.
	 *
	 * @return array<string, WP_User[]>
	 */
	public static function get_users_by_role() {
		$users_by_role = array();
		foreach ( get_users( array( 'orderby' => 'display_name' ) ) as $user ) {
			if ( ! $user instanceof WP_User ) {
				continue;
			}
			$roles = $user->roles;
			$role  = ! empty( $roles ) ? (string) $roles[0] : 'subscriber';
			if ( ! isset( $users_by_role[ $role ] ) ) {
				$users_by_role[ $role ] = array();
			}
			$users_by_role[ $role ][] = $user;
		}
		ksort( $users_by_role );
		return $users_by_role;
	}
}
