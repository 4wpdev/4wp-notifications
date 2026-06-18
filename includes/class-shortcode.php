<?php
/**
 * Shortcode [forwp_notifications] — full notifications list for the logged-in user.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Shortcode {

	const SHORTCODE = 'forwp_notifications';

	/**
	 * @var ForWP_Notifications_Manager
	 */
	private $manager;

	public function __construct() {
		$this->manager = new ForWP_Notifications_Manager();
		add_shortcode( self::SHORTCODE, array( $this, 'render' ) );
		add_shortcode( '4wp_notifications', array( $this, 'render' ) );
		add_action( 'template_redirect', array( $this, 'handle_mark_read' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets() {
		ForWP_Notifications_List_Renderer::register_assets();
	}

	/**
	 * Handle ?forwp_mark_read=ID&_wpnonce=... redirect flow.
	 */
	public function handle_mark_read() {
		$id = isset( $_GET['forwp_mark_read'] ) ? (int) $_GET['forwp_mark_read'] : 0;
		if ( $id <= 0 || ! is_user_logged_in() ) {
			return;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'forwp_notification_read_' . $id ) ) {
			return;
		}
		$this->manager->mark_read( $id, null );
		$redirect = remove_query_arg( array( 'forwp_mark_read', '_wpnonce' ), wp_get_referer() ?: wp_get_current_url() );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * @param array<string, mixed>|string $atts Shortcode attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts  = shortcode_atts( array( 'limit' => 20 ), $atts, self::SHORTCODE );
		$limit = absint( $atts['limit'] );
		return ForWP_Notifications_List_Renderer::render( $limit );
	}
}
