<?php
/**
 * Shortcode [forwp_notifications] — вивід списку повідомлень для поточного користувача.
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
		add_shortcode( '4wp_notifications', array( $this, 'render' ) ); // alias for backward compatibility
		add_action( 'template_redirect', array( $this, 'handle_mark_read' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_styles' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_poll_script' ) );
	}

	public function register_styles() {
		wp_register_style(
			'4wp-notifications-shortcode',
			FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/css/shortcode.css',
			array(),
			FORWP_NOTIFICATIONS_VERSION
		);
	}

	public function register_poll_script() {
		wp_register_script(
			'forwp-notifications-shortcode-poll',
			FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/js/shortcode-poll.js',
			array(),
			FORWP_NOTIFICATIONS_VERSION,
			true
		);
	}

	/**
	 * Handle ?forwp_mark_read=ID&_wpnonce=... — позначити прочитаним і редірект назад.
	 */
	public function handle_mark_read() {
		$id = isset( $_GET['forwp_mark_read'] ) ? (int) $_GET['forwp_mark_read'] : 0;
		if ( $id <= 0 || ! is_user_logged_in() ) {
			return;
		}
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( $_GET['_wpnonce'] ), 'forwp_notification_read_' . $id ) ) {
			return;
		}
		$this->manager->mark_read( $id, null );
		$redirect = remove_query_arg( array( 'forwp_mark_read', '_wpnonce' ), wp_get_referer() ?: wp_get_current_url() );
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Shortcode callback — вивід списку + полінг через окремий JS (гарантовано оновлюється).
	 *
	 * @param array $atts Shortcode attributes (limit).
	 * @return string
	 */
	public function render( $atts ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		$atts  = shortcode_atts( array( 'limit' => 20 ), $atts, self::SHORTCODE );
		$limit = absint( $atts['limit'] );
		$limit = $limit > 0 ? min( $limit, 100 ) : 20;

		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( '4wp-notifications-shortcode' );
		wp_enqueue_script( 'forwp-notifications-shortcode-poll' );

		$manager = new ForWP_Notifications_Manager();
		$items   = $manager->get_for_user( null, $limit, 0 );
		$rest_url = rest_url( 'forwp/v1' );
		$nonce    = wp_create_nonce( 'wp_rest' );
		$poll_interval = 30000;

		ob_start();
		?>
		<div class="forwp-notifications" data-forwp-poll="1" data-forwp-rest-url="<?php echo esc_url( $rest_url ); ?>" data-forwp-nonce="<?php echo esc_attr( $nonce ); ?>" data-forwp-poll-interval="<?php echo esc_attr( (string) $poll_interval ); ?>">
			<?php if ( ! empty( $items ) ) : ?>
				<ul class="forwp-notifications__list">
					<?php foreach ( $items as $item ) : ?>
						<?php
						$is_read = ( (int) $item['is_read'] ) === 1;
						$source  = isset( $item['source'] ) ? $item['source'] : '';
						$item_icon_class = ForWP_Notifications_Shortcode_Bell::get_item_icon_class( $source );
						$toggle_label = $is_read ? __( 'Mark as unread', 'forwp-notifications' ) : __( 'Mark as read', 'forwp-notifications' );
						$toggle_icon  = $is_read ? 'dashicons-hidden' : 'dashicons-visibility';
						$toggle_class = 'forwp-notifications__toggle' . ( $is_read ? ' forwp-notifications__toggle--read' : '' );
						?>
						<li class="forwp-notifications__item <?php echo $is_read ? 'is-read' : ''; ?>" data-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
							<span class="forwp-notifications__item-icon" aria-hidden="true"><span class="dashicons <?php echo esc_attr( $item_icon_class ); ?>"></span></span>
							<div class="forwp-notifications__content">
								<?php if ( ! empty( $item['payload']['title'] ) ) : ?>
									<span class="forwp-notifications__title"><?php echo esc_html( $item['payload']['title'] ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $item['payload']['message'] ) ) : ?>
									<p class="forwp-notifications__message"><?php echo esc_html( $item['payload']['message'] ); ?></p>
								<?php endif; ?>
								<span class="forwp-notifications__date"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['created_at'] ) ) ); ?></span>
								<?php if ( ! empty( $item['payload']['url'] ) ) : ?>
									<a class="forwp-notifications__link" href="<?php echo esc_url( $item['payload']['url'] ); ?>" aria-label="<?php esc_attr_e( 'Go to page', 'forwp-notifications' ); ?>"><span class="forwp-notifications__link-icon dashicons dashicons-external" aria-hidden="true"></span></a>
								<?php endif; ?>
							</div>
							<button type="button" class="<?php echo esc_attr( $toggle_class ); ?> forwp-js-toggle" data-id="<?php echo esc_attr( (string) $item['id'] ); ?>" data-is-read="<?php echo $is_read ? '1' : '0'; ?>" aria-label="<?php echo esc_attr( $toggle_label ); ?>"><span class="dashicons <?php echo esc_attr( $toggle_icon ); ?>" aria-hidden="true"></span></button>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="forwp-notifications__empty"><?php esc_html_e( 'No notifications.', 'forwp-notifications' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Shared render: список повідомлень (для шорткоду та блоку).
	 *
	 * @param int $limit Max кількість записів.
	 * @return string HTML.
	 */
	public static function render_notifications( $limit = 20 ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}
		$manager = new ForWP_Notifications_Manager();
		$limit   = $limit > 0 ? min( (int) $limit, 100 ) : 20;
		$items   = $manager->get_for_user( null, $limit, 0 );
		$unread  = $manager->count_unread( null );

		ob_start();
		?>
		<div class="forwp-notifications" data-unread="<?php echo esc_attr( (string) $unread ); ?>">
			<?php if ( ! empty( $items ) ) : ?>
				<ul class="forwp-notifications__list">
					<?php foreach ( $items as $item ) : ?>
						<li class="forwp-notifications__item <?php echo ( (int) $item['is_read'] ) === 1 ? 'is-read' : ''; ?>" data-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
							<div class="forwp-notifications__content">
								<?php if ( ! empty( $item['payload']['title'] ) ) : ?>
									<span class="forwp-notifications__title"><?php echo esc_html( $item['payload']['title'] ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $item['payload']['message'] ) ) : ?>
									<p class="forwp-notifications__message"><?php echo esc_html( $item['payload']['message'] ); ?></p>
								<?php endif; ?>
								<span class="forwp-notifications__date"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['created_at'] ) ) ); ?></span>
								<?php if ( ! empty( $item['payload']['url'] ) ) : ?>
									<a class="forwp-notifications__link" href="<?php echo esc_url( $item['payload']['url'] ); ?>" aria-label="<?php esc_attr_e( 'Go to page', 'forwp-notifications' ); ?>"><span class="forwp-notifications__link-icon dashicons dashicons-external" aria-hidden="true"></span></a>
								<?php endif; ?>
								<?php if ( ( (int) $item['is_read'] ) !== 1 ) : ?>
									<a class="forwp-notifications__mark-read" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'forwp_mark_read', $item['id'] ), 'forwp_notification_read_' . $item['id'] ) ); ?>"><?php esc_html_e( 'Mark as read', 'forwp-notifications' ); ?></a>
								<?php endif; ?>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			<?php else : ?>
				<p class="forwp-notifications__empty"><?php esc_html_e( 'No notifications.', 'forwp-notifications' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}
}
