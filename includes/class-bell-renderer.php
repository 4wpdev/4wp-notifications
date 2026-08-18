<?php
/**
 * Shared frontend markup for the notifications bell widget.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Bell_Renderer {

	/**
	 * Register bell widget assets.
	 */
	public static function register_assets() {
		wp_register_style(
			'forwp-notifications-bell-widget',
			FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/css/bell-widget.css',
			array(),
			FORWP_NOTIFICATIONS_VERSION
		);
		wp_register_script(
			'forwp-notifications-bell-widget',
			FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/js/bell-widget.js',
			array(),
			FORWP_NOTIFICATIONS_VERSION,
			true
		);
	}

	/**
	 * Enqueue bell widget assets.
	 */
	public static function enqueue_assets() {
		self::register_assets();
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'forwp-notifications-bell-widget' );
		wp_enqueue_script( 'forwp-notifications-bell-widget' );
	}

	/**
	 * Icon class for a notification item by source.
	 *
	 * @param string $source Notification source slug.
	 * @return string Dashicons class name.
	 */
	public static function get_item_icon_class( $source ) {
		$map   = array(
			'woo'   => 'dashicons-cart',
			'admin' => 'dashicons-megaphone',
		);
		$class = isset( $map[ $source ] ) ? $map[ $source ] : 'dashicons-bell';
		return apply_filters( 'forwp_notifications_item_icon_class', $class, $source );
	}

	/**
	 * Resolve the "View all" URL.
	 *
	 * @param string $all_url Optional override URL.
	 * @return string
	 */
	public static function resolve_all_url( $all_url = '' ) {
		$all_url = apply_filters( 'forwp_notifications_bell_all_url', $all_url );
		if ( '' !== $all_url ) {
			return $all_url;
		}
		$page_id = (int) get_option( 'forwp_notifications_page_id', 0 );
		if ( $page_id <= 0 ) {
			return '';
		}
		$url = get_permalink( $page_id );
		return $url ? $url : '';
	}

	/**
	 * Render bell widget HTML.
	 *
	 * @param array<string, mixed> $args all_url, limit.
	 * @return string
	 */
	public static function render( $args = array() ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		$args = wp_parse_args(
			$args,
			array(
				'all_url' => '',
				'limit'   => 20,
			)
		);

		$all_url = self::resolve_all_url( (string) $args['all_url'] );
		$limit   = absint( $args['limit'] );
		$limit   = $limit > 0 ? min( $limit, 50 ) : 20;

		self::enqueue_assets();

		$manager  = new ForWP_Notifications_Manager();
		$items    = $manager->get_for_user( null, $limit, 0 );
		$unread   = $manager->count_unread( null );
		$rest_url = rest_url( ForWP_Notifications_REST_Controller::NAMESPACE );
		$nonce    = wp_create_nonce( 'wp_rest' );
		$icon_url = apply_filters( 'forwp_notifications_bell_icon_url', '' );

		$i18n = array(
			'empty'        => __( 'No new notifications', '4wp-notifications' ),
			'markRead'     => __( 'Mark as read', '4wp-notifications' ),
			'markUnread'   => __( 'Mark as unread', '4wp-notifications' ),
			'notification' => __( 'Notification', '4wp-notifications' ),
			'goToPage'     => __( 'Go to page', '4wp-notifications' ),
			'justNow'      => __( 'just now', '4wp-notifications' ),
			'minAgo'       => __( 'min ago', '4wp-notifications' ),
			'hrAgo'        => __( 'hr ago', '4wp-notifications' ),
			'dAgo'         => __( 'd ago', '4wp-notifications' ),
		);
		wp_localize_script( 'forwp-notifications-bell-widget', 'forwpNotificationsBellI18n', $i18n );

		ob_start();
		?>
		<div class="forwp-notifications-bell" data-forwp-bell="1" data-forwp-rest-url="<?php echo esc_url( $rest_url ); ?>" data-forwp-nonce="<?php echo esc_attr( $nonce ); ?>" data-forwp-i18n="<?php echo esc_attr( wp_json_encode( $i18n ) ); ?>">
			<button type="button" class="forwp-notifications-bell__button" aria-label="<?php esc_attr_e( 'Notifications', '4wp-notifications' ); ?>" aria-expanded="false" aria-haspopup="true">
				<?php echo self::get_icon_html( $icon_url ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in helper. ?>
				<span class="forwp-notifications-bell__badge" <?php echo $unread > 0 ? '' : 'style="display: none;"'; ?>><?php echo $unread > 99 ? '99+' : (int) $unread; ?></span>
			</button>
			<div class="forwp-notifications-bell__dropdown">
				<div class="forwp-notifications-bell__dropdown-header">
					<h3 class="forwp-notifications-bell__dropdown-title"><?php esc_html_e( 'Notifications', '4wp-notifications' ); ?></h3>
					<button type="button" class="forwp-notifications-bell__mark-all"><?php esc_html_e( 'Mark all as read', '4wp-notifications' ); ?></button>
				</div>
				<div class="forwp-notifications-bell__list">
					<?php
					foreach ( $items as $item ) {
						self::render_item( $item );
					}
					?>
					<div class="forwp-notifications-bell__list-empty" <?php echo ! empty( $items ) ? 'style="display: none;"' : ''; ?>><p><?php esc_html_e( 'No new notifications', '4wp-notifications' ); ?></p></div>
				</div>
				<div class="forwp-notifications-bell__footer">
					<button type="button" class="forwp-notifications-bell__delete-all"><?php esc_html_e( 'Delete All', '4wp-notifications' ); ?></button>
					<a href="<?php echo esc_url( $all_url ? $all_url : '#' ); ?>"><?php esc_html_e( 'View all notifications', '4wp-notifications' ); ?></a>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * @param string $icon_url Optional custom icon URL.
	 * @return string
	 */
	private static function get_icon_html( $icon_url ) {
		if ( $icon_url ) {
			return '<img width="20" height="20" src="' . esc_url( $icon_url ) . '" class="style-svg" alt="">';
		}
		return '<span class="dashicons dashicons-bell" style="font-size: 20px; width: 20px; height: 20px;"></span>';
	}

	/**
	 * Output a single dropdown item.
	 *
	 * @param array<string, mixed> $item Notification row.
	 */
	public static function render_item( $item ) {
		$payload      = isset( $item['payload'] ) && is_array( $item['payload'] ) ? $item['payload'] : array();
		$source       = isset( $item['source'] ) ? $item['source'] : '';
		$title        = isset( $payload['title'] ) ? $payload['title'] : __( 'Notification', '4wp-notifications' );
		$message      = isset( $payload['message'] ) ? $payload['message'] : '';
		$url          = isset( $payload['url'] ) ? $payload['url'] : '#';
		$is_read      = ( (int) $item['is_read'] ) === 1;
		$created      = isset( $item['created_at'] ) ? $item['created_at'] : '';
		$time_ago     = $created ? human_time_diff( strtotime( $created ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', '4wp-notifications' ) : '';
		$class        = 'forwp-notifications-bell__item';
		$toggle_label = $is_read ? __( 'Mark as unread', '4wp-notifications' ) : __( 'Mark as read', '4wp-notifications' );
		$toggle_icon  = $is_read ? 'dashicons-hidden' : 'dashicons-visibility';
		$toggle_class = 'forwp-notifications-bell__item-toggle' . ( $is_read ? ' forwp-notifications-bell__item-toggle--read' : '' );
		$icon_class   = self::get_item_icon_class( $source );
		$has_link     = $url && '#' !== $url;

		if ( ! $is_read ) {
			$class .= ' forwp-notifications-bell__item--unread';
		}
		?>
		<a href="<?php echo esc_url( $url ); ?>" class="<?php echo esc_attr( $class ); ?>" data-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
			<span class="forwp-notifications-bell__item-icon"><span class="dashicons <?php echo esc_attr( $icon_class ); ?>" style="font-size:20px;width:20px;height:20px;" aria-hidden="true"></span></span>
			<div class="forwp-notifications-bell__item-content">
				<h4 class="forwp-notifications-bell__item-title"><?php echo esc_html( $title ); ?></h4>
				<?php
				if ( $message ) :
					?>
					<p class="forwp-notifications-bell__item-text"><?php echo esc_html( $message ); ?></p><?php endif; ?>
				<?php
				if ( $time_ago ) :
					?>
					<time class="forwp-notifications-bell__item-time"><?php echo esc_html( $time_ago ); ?></time><?php endif; ?>
				<?php
				if ( $has_link ) :
					?>
					<span class="forwp-notifications-bell__item-link-icon" aria-label="<?php esc_attr_e( 'Go to page', '4wp-notifications' ); ?>"><span class="dashicons dashicons-external"></span></span><?php endif; ?>
			</div>
			<button type="button" class="<?php echo esc_attr( $toggle_class ); ?>" data-id="<?php echo esc_attr( (string) $item['id'] ); ?>" data-is-read="<?php echo $is_read ? '1' : '0'; ?>" aria-label="<?php echo esc_attr( $toggle_label ); ?>"><span class="dashicons <?php echo esc_attr( $toggle_icon ); ?>" aria-hidden="true"></span></button>
		</a>
		<?php
	}
}
