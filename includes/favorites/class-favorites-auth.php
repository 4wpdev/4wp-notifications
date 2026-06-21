<?php
/**
 * Resolves login URL — prefers 4WP Account over wp-login.php.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Favorites_Auth {

	/**
	 * Login URL for favorites flows.
	 *
	 * @param string $redirect Optional URL to return to after sign-in.
	 * @return string
	 */
	public static function get_login_url( $redirect = '' ) {
		if ( '' === $redirect && is_singular() ) {
			$redirect = get_permalink();
		}

		if ( class_exists( '\ForWP\Auth\Account\AccountMenu' ) ) {
			$url = self::get_account_login_url( (string) $redirect );
			if ( '' !== $url ) {
				/**
				 * Filter favorites login URL when 4WP Account is active.
				 *
				 * @param string $url      Login URL.
				 * @param string $redirect Redirect target after sign-in.
				 */
				return (string) apply_filters( 'forwp_favorites_login_url', $url, $redirect );
			}
		}

		return wp_login_url( $redirect ? $redirect : '' );
	}

	/**
	 * Resolve account sign-in URL (compatible with older 4wp-account releases).
	 *
	 * @param string $redirect Optional redirect target after sign-in.
	 * @return string
	 */
	private static function get_account_login_url( $redirect = '' ) {
		if ( method_exists( '\ForWP\Auth\Account\AccountMenu', 'get_login_url' ) ) {
			return (string) \ForWP\Auth\Account\AccountMenu::get_login_url( (string) $redirect );
		}

		if ( ! method_exists( '\ForWP\Auth\Account\AccountMenu', 'get_account_page_url' ) ) {
			return '';
		}

		$url = (string) \ForWP\Auth\Account\AccountMenu::get_account_page_url();
		if ( '' === $url ) {
			return '';
		}

		if ( '' !== $redirect ) {
			$url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $url );
		}

		return $url;
	}
}
