<?php
/**
 * Shared frontend markup for the full notifications list.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_List_Renderer {

	/**
	 * Register list assets.
	 */
	public static function register_assets() {
		wp_register_style(
			'forwp-notifications-shortcode',
			FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/css/shortcode.css',
			array(),
			FORWP_NOTIFICATIONS_VERSION
		);
		wp_register_script(
			'forwp-notifications-shortcode-poll',
			FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/js/shortcode-poll.js',
			array(),
			FORWP_NOTIFICATIONS_VERSION,
			true
		);
	}

	/**
	 * Enqueue list assets.
	 */
	public static function enqueue_assets() {
		self::register_assets();
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'forwp-notifications-shortcode' );
		wp_enqueue_script( 'forwp-notifications-shortcode-poll' );
	}

	/**
	 * Render notifications list with REST polling.
	 *
	 * @param int $limit Max items.
	 * @return string
	 */
	public static function render( $limit = 20 ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$limit = absint( $limit );
		$limit = $limit > 0 ? min( $limit, 100 ) : 20;

		self::enqueue_assets();

		$manager       = new ForWP_Notifications_Manager();
		$items         = $manager->get_for_user( null, $limit, 0 );
		$rest_url      = rest_url( ForWP_Notifications_REST_Controller::NAMESPACE );
		$nonce         = wp_create_nonce( 'wp_rest' );
		$poll_interval = 30000;
		$empty_text    = __( 'No notifications.', '4wp-notifications' );

		ob_start();
		?>
		<div class="4wp-notifications" data-forwp-poll="1" data-forwp-rest-url="<?php echo esc_url( $rest_url ); ?>" data-forwp-nonce="<?php echo esc_attr( $nonce ); ?>" data-forwp-poll-interval="<?php echo esc_attr( (string) $poll_interval ); ?>" data-forwp-empty-text="<?php echo esc_attr( $empty_text ); ?>">
			<?php if ( ! empty( $items ) ) : ?>
				<ul class="4wp-notifications__list">
					<?php
					foreach ( $items as $item ) {
						self::render_item( $item );
					}
					?>
				</ul>
			<?php else : ?>
				<p class="4wp-notifications__empty"><?php echo esc_html( $empty_text ); ?></p>
			<?php endif; ?>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param array<string, mixed> $item Notification row.
	 */
	public static function render_item( $item ) {
		$is_read         = ( (int) $item['is_read'] ) === 1;
		$source          = isset( $item['source'] ) ? $item['source'] : '';
		$item_icon_class = ForWP_Notifications_Bell_Renderer::get_item_icon_class( $source );
		$toggle_label    = $is_read ? __( 'Mark as unread', '4wp-notifications' ) : __( 'Mark as read', '4wp-notifications' );
		$toggle_icon     = $is_read ? 'dashicons-hidden' : 'dashicons-visibility';
		$toggle_class    = '4wp-notifications__toggle' . ( $is_read ? ' 4wp-notifications__toggle--read' : '' );
		?>
		<li class="4wp-notifications__item <?php echo $is_read ? 'is-read' : ''; ?>" data-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
			<span class="4wp-notifications__item-icon" aria-hidden="true"><span class="dashicons <?php echo esc_attr( $item_icon_class ); ?>"></span></span>
			<div class="4wp-notifications__content">
				<?php if ( ! empty( $item['payload']['title'] ) ) : ?>
					<span class="4wp-notifications__title"><?php echo esc_html( $item['payload']['title'] ); ?></span>
				<?php endif; ?>
				<?php if ( ! empty( $item['payload']['message'] ) ) : ?>
					<p class="4wp-notifications__message"><?php echo esc_html( $item['payload']['message'] ); ?></p>
				<?php endif; ?>
				<span class="4wp-notifications__date"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['created_at'] ) ) ); ?></span>
				<?php if ( ! empty( $item['payload']['url'] ) ) : ?>
					<a class="4wp-notifications__link" href="<?php echo esc_url( $item['payload']['url'] ); ?>" aria-label="<?php esc_attr_e( 'Go to page', '4wp-notifications' ); ?>"><span class="4wp-notifications__link-icon dashicons dashicons-external" aria-hidden="true"></span></a>
				<?php endif; ?>
			</div>
			<button type="button" class="<?php echo esc_attr( $toggle_class ); ?> forwp-js-toggle" data-id="<?php echo esc_attr( (string) $item['id'] ); ?>" data-is-read="<?php echo $is_read ? '1' : '0'; ?>" aria-label="<?php echo esc_attr( $toggle_label ); ?>"><span class="dashicons <?php echo esc_attr( $toggle_icon ); ?>" aria-hidden="true"></span></button>
		</li>
		<?php
	}
}
