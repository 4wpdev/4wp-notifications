<?php
/**
 * Shortcode [forwp_notifications_bell] — bell icon and dropdown.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Shortcode_Bell {

	const SHORTCODE = 'forwp_notifications_bell';

	public function __construct() {
		add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
		add_shortcode( '4wp_notifications_bell', array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets() {
		ForWP_Notifications_Bell_Renderer::register_assets();
	}

	/**
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'all_url' => '',
				'limit'   => 20,
			),
			$atts,
			self::SHORTCODE
		);

		return ForWP_Notifications_Bell_Renderer::render(
			array(
				'all_url' => (string) $atts['all_url'],
				'limit'   => (int) $atts['limit'],
			)
		);
	}

	/**
	 * Backward-compatible alias for list item icons.
	 *
	 * @param string $source Notification source slug.
	 * @return string
	 */
	public static function get_item_icon_class( $source ) {
		return ForWP_Notifications_Bell_Renderer::get_item_icon_class( $source );
	}
}
