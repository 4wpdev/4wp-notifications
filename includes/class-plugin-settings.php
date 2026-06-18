<?php
/**
 * Plugin options and defaults.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Plugin_Settings {

	const OPTION_PAGE_ID           = 'forwp_notifications_page_id';
	const OPTION_WOO_ORDER_CREATED = 'forwp_notifications_woo_order_created';
	const OPTION_WOO_STATUS        = 'forwp_notifications_woo_status_changed';

	/**
	 * @return int
	 */
	public static function get_page_id() {
		return (int) get_option( self::OPTION_PAGE_ID, 0 );
	}

	/**
	 * @param int $page_id Page ID.
	 */
	public static function set_page_id( $page_id ) {
		update_option( self::OPTION_PAGE_ID, max( 0, (int) $page_id ) );
	}

	/**
	 * @return string
	 */
	public static function get_all_notifications_page_url() {
		$page_id = self::get_page_id();
		if ( $page_id <= 0 ) {
			return '';
		}
		$url = get_permalink( $page_id );
		return $url ? $url : '';
	}

	/**
	 * @return bool
	 */
	public static function is_woo_order_created_enabled() {
		return (bool) get_option( self::OPTION_WOO_ORDER_CREATED, true );
	}

	/**
	 * @return bool
	 */
	public static function is_woo_status_changed_enabled() {
		return (bool) get_option( self::OPTION_WOO_STATUS, true );
	}

	/**
	 * @param bool $enabled Enabled state.
	 */
	public static function set_woo_order_created_enabled( $enabled ) {
		update_option( self::OPTION_WOO_ORDER_CREATED, $enabled ? 1 : 0 );
	}

	/**
	 * @param bool $enabled Enabled state.
	 */
	public static function set_woo_status_changed_enabled( $enabled ) {
		update_option( self::OPTION_WOO_STATUS, $enabled ? 1 : 0 );
	}

	/**
	 * @return bool
	 */
	public static function is_woocommerce_active() {
		return class_exists( 'WooCommerce' );
	}
}
