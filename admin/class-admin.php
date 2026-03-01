<?php
/**
 * Admin: menu, send form, and settings (page for all notifications).
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Admin {

	const OPTION_PAGE_ID = 'forwp_notifications_page_id';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_forwp_notifications_send', array( $this, 'handle_send' ) );
		add_action( 'admin_post_forwp_notifications_settings', array( $this, 'handle_settings' ) );
	}

	public function add_menu() {
		add_menu_page(
			__( 'Notifications', 'forwp-notifications' ),
			__( 'Notifications', 'forwp-notifications' ),
			'manage_options',
			'forwp-notifications',
			array( $this, 'render_page' ),
			'dashicons-bell',
			58
		);
		add_submenu_page(
			'forwp-notifications',
			__( 'Settings', 'forwp-notifications' ),
			__( 'Settings', 'forwp-notifications' ),
			'manage_options',
			'forwp-notifications-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$sent = isset( $_GET['sent'] ) ? (int) $_GET['sent'] : 0;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Send notification', 'forwp-notifications' ); ?></h1>
			<?php if ( $sent > 0 ) : ?>
				<div class="notice notice-success"><p><?php echo esc_html( sprintf( __( 'Notification sent to %d user(s).', 'forwp-notifications' ), $sent ) ); ?></p></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="forwp_notifications_send" />
				<?php wp_nonce_field( 'forwp_notifications_send' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="forwp_notif_user"><?php esc_html_e( 'User', 'forwp-notifications' ); ?></label></th>
						<td>
							<?php
							wp_dropdown_users( array(
								'name'             => 'user_id',
								'id'               => 'forwp_notif_user',
								'show_option_none' => __( 'Select user', 'forwp-notifications' ),
								'capability'       => 'read',
							) );
							?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="forwp_notif_title"><?php esc_html_e( 'Title', 'forwp-notifications' ); ?></label></th>
						<td><input type="text" name="title" id="forwp_notif_title" class="regular-text" required /></td>
					</tr>
					<tr>
						<th scope="row"><label for="forwp_notif_message"><?php esc_html_e( 'Message', 'forwp-notifications' ); ?></label></th>
						<td><textarea name="message" id="forwp_notif_message" class="large-text" rows="3"></textarea></td>
					</tr>
					<tr>
						<th scope="row"><label for="forwp_notif_url"><?php esc_html_e( 'Link URL (optional)', 'forwp-notifications' ); ?></label></th>
						<td><input type="url" name="url" id="forwp_notif_url" class="regular-text" placeholder="https://" /></td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Send notification', 'forwp-notifications' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}

	public function handle_send() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '', 'forwp_notifications_send' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'forwp-notifications' ) );
		}
		$user_id = isset( $_POST['user_id'] ) ? (int) $_POST['user_id'] : 0;
		$title   = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( $_POST['message'] ) : '';
		$url     = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';

		if ( $user_id <= 0 || $title === '' ) {
			wp_safe_redirect( add_query_arg( 'error', 1, admin_url( 'admin.php?page=forwp-notifications' ) ) );
			exit;
		}

		$payload = array( 'title' => $title, 'message' => $message );
		if ( $url ) {
			$payload['url'] = $url;
			$payload['actions'] = array( array( 'type' => 'view', 'label' => __( 'View', 'forwp-notifications' ), 'url' => $url ) );
		}
		ForWP_Notifications_Queue::push( $user_id, 'custom', 'admin', $payload, null, true );
		wp_safe_redirect( add_query_arg( 'sent', 1, admin_url( 'admin.php?page=forwp-notifications' ) ) );
		exit;
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$saved = isset( $_GET['saved'] ) && $_GET['saved'] === '1';
		$page_id = (int) get_option( self::OPTION_PAGE_ID, 0 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Notification settings', 'forwp-notifications' ); ?></h1>
			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'forwp-notifications' ); ?></p></div>
			<?php endif; ?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="forwp_notifications_settings" />
				<?php wp_nonce_field( 'forwp_notifications_settings' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="forwp_notifications_page_id"><?php esc_html_e( 'Page with all notifications', 'forwp-notifications' ); ?></label></th>
						<td>
							<?php
							wp_dropdown_pages( array(
								'name'             => 'page_id',
								'id'               => 'forwp_notifications_page_id',
								'selected'         => $page_id,
								'show_option_none' => __( '— Select —', 'forwp-notifications' ),
								'post_status'      => 'publish,draft',
							) );
							?>
							<p class="description"><?php esc_html_e( 'Link "View all notifications" in the bell widget will point to this page. Add the shortcode', 'forwp-notifications' ); ?> <code>[forwp_notifications]</code> <?php esc_html_e( 'or', 'forwp-notifications' ); ?> <code>[4wp_notifications]</code> <?php esc_html_e( 'to this page to display the full list.', 'forwp-notifications' ); ?></p>
						</td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save changes', 'forwp-notifications' ); ?></button>
				</p>
			</form>
		</div>
		<?php
	}

	public function handle_settings() {
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : '', 'forwp_notifications_settings' ) ) {
			wp_die( esc_html__( 'Invalid request.', 'forwp-notifications' ) );
		}
		$page_id = isset( $_POST['page_id'] ) ? (int) $_POST['page_id'] : 0;
		update_option( self::OPTION_PAGE_ID, $page_id );
		wp_safe_redirect( add_query_arg( 'saved', '1', admin_url( 'admin.php?page=forwp-notifications-settings' ) ) );
		exit;
	}

	/**
	 * Get the configured "all notifications" page URL (for use in bell widget).
	 *
	 * @return string URL or empty.
	 */
	public static function get_all_notifications_page_url() {
		$page_id = (int) get_option( self::OPTION_PAGE_ID, 0 );
		if ( $page_id <= 0 ) {
			return '';
		}
		$url = get_permalink( $page_id );
		return $url ? $url : '';
	}
}
