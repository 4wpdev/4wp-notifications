<?php
/**
 * Admin menu and unified settings app.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Admin {

	const OPTION_PAGE_ID = ForWP_Notifications_Plugin_Settings::OPTION_PAGE_ID;

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'maybe_redirect_legacy_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_forwp_notifications_send', array( $this, 'handle_send' ) );
		add_action( 'admin_post_forwp_notifications_settings', array( $this, 'handle_settings' ) );
		add_action( 'admin_post_forwp_notifications_types', array( $this, 'handle_types' ) );
	}

	/**
	 * Legacy Settings submenu → unified app.
	 */
	public function maybe_redirect_legacy_settings() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only legacy URL redirect; no state change.
		if ( ! is_admin() || ! isset( $_GET['page'] ) || 'forwp-notifications-settings' !== $_GET['page'] ) {
			return;
		}
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'display';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		wp_safe_redirect( admin_url( 'admin.php?page=4wp-notifications&tab=' . $tab ) );
		exit;
	}

	/**
	 * @param string $hook_suffix Current admin screen.
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'toplevel_page_4wp-notifications' !== $hook_suffix ) {
			return;
		}
		wp_enqueue_style(
			'forwp-notifications-admin-settings',
			FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/css/admin-settings.css',
			array(),
			FORWP_NOTIFICATIONS_VERSION
		);
		wp_enqueue_script(
			'forwp-notifications-admin',
			FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/js/admin.js',
			array(),
			FORWP_NOTIFICATIONS_VERSION,
			true
		);
	}

	public function add_menu() {
		add_menu_page(
			__( '4WP Notifications', '4wp-notifications' ),
			__( '4WP Notifications', '4wp-notifications' ),
			'manage_options',
			'4wp-notifications',
			array( $this, 'render_app_page' ),
			'dashicons-bell',
			58
		);
		// Remove duplicate submenu entry WordPress adds for the top-level slug.
		remove_submenu_page( '4wp-notifications', '4wp-notifications' );
	}

	public function render_app_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		require FORWP_NOTIFICATIONS_PLUGIN_DIR . 'admin/views/app-page.php';
	}

	public function handle_send() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '', 'forwp_notifications_send' ) ) {
			wp_die( esc_html__( 'Invalid request.', '4wp-notifications' ) );
		}

		$user_ids   = isset( $_POST['user_ids'] ) && is_array( $_POST['user_ids'] ) ? array_map( 'intval', wp_unslash( $_POST['user_ids'] ) ) : array();
		$role_slugs = isset( $_POST['role_slugs'] ) && is_array( $_POST['role_slugs'] ) ? array_map( 'sanitize_key', wp_unslash( $_POST['role_slugs'] ) ) : array();
		$user_ids   = ForWP_Notifications_Recipient_Resolver::resolve_ids( $user_ids, $role_slugs );
		$title      = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
		$message    = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
		$url        = isset( $_POST['url'] ) ? esc_url_raw( wp_unslash( $_POST['url'] ) ) : '';

		if ( empty( $user_ids ) || '' === $title ) {
			wp_safe_redirect( admin_url( 'admin.php?page=4wp-notifications&tab=direct&error=1' ) );
			exit;
		}
		$payload = array(
			'title'   => $title,
			'message' => $message,
		);
		if ( $url ) {
			$payload['url']     = $url;
			$payload['actions'] = array(
				array(
					'type'  => 'view',
					'label' => __( 'View', '4wp-notifications' ),
					'url'   => $url,
				),
			);
		}

		foreach ( $user_ids as $user_id ) {
			if ( $user_id > 0 ) {
				ForWP_Notifications_Queue::push( $user_id, 'custom', 'admin', $payload, null, true );
			}
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => '4wp-notifications',
					'tab'  => 'direct',
					'sent' => count( $user_ids ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public function handle_settings() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '', 'forwp_notifications_settings' ) ) {
			wp_die( esc_html__( 'Invalid request.', '4wp-notifications' ) );
		}

		ForWP_Notifications_Plugin_Settings::set_page_id( isset( $_POST['page_id'] ) ? (int) $_POST['page_id'] : 0 );

		$page_id = ForWP_Notifications_Plugin_Settings::get_page_id();
		if ( $page_id > 0 ) {
			ForWP_Notifications_Page_Display::ensure_page_has_list_block( $page_id );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => '4wp-notifications',
					'tab'   => 'display',
					'saved' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public function handle_types() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '', 'forwp_notifications_types' ) ) {
			wp_die( esc_html__( 'Invalid request.', '4wp-notifications' ) );
		}

		if ( ForWP_Notifications_Plugin_Settings::is_woocommerce_active() ) {
			ForWP_Notifications_Plugin_Settings::set_woo_order_created_enabled( ! empty( $_POST['woo_order_created'] ) );
			ForWP_Notifications_Plugin_Settings::set_woo_status_changed_enabled( ! empty( $_POST['woo_status_changed'] ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'        => '4wp-notifications',
					'tab'         => 'types',
					'types_saved' => '1',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * @return string
	 */
	public static function get_all_notifications_page_url() {
		return ForWP_Notifications_Plugin_Settings::get_all_notifications_page_url();
	}
}
