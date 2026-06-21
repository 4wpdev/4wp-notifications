<?php
/**
 * Shared frontend markup for the favorites menu widget (header dropdown).
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Favorites_Menu_Renderer {

	/**
	 * Register menu widget assets.
	 */
	public static function register_assets() {
		wp_register_style(
			'forwp-favorites-menu-widget',
			FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/css/favorites-menu.css',
			array(),
			FORWP_NOTIFICATIONS_VERSION
		);
		wp_register_script(
			'forwp-favorites-menu-widget',
			FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/js/favorites-menu.js',
			array(),
			FORWP_NOTIFICATIONS_VERSION,
			true
		);
	}

	/**
	 * Enqueue menu widget assets.
	 */
	public static function enqueue_assets() {
		self::register_assets();
		wp_enqueue_style( 'dashicons' );
		wp_enqueue_style( 'forwp-favorites-menu-widget' );
		wp_enqueue_script( 'forwp-favorites-menu-widget' );
	}

	/**
	 * Resolve the "View all" URL.
	 *
	 * @param string $all_url Optional override URL.
	 * @return string
	 */
	public static function resolve_all_url( $all_url = '' ) {
		$all_url = apply_filters( 'forwp_favorites_menu_all_url', $all_url );
		if ( '' !== $all_url ) {
			return $all_url;
		}

		return ForWP_Notifications_Plugin_Settings::get_favorites_page_url();
	}

	/**
	 * Icon class for a favorite item by type.
	 *
	 * @param string $type Favorite type slug.
	 * @return string Dashicons class name.
	 */
	public static function get_item_icon_class( $type ) {
		$map = array(
			ForWP_Favorites_Repository::TYPE_POST      => 'dashicons-admin-post',
			ForWP_Favorites_Repository::TYPE_POST_TYPE => 'dashicons-archive',
			ForWP_Favorites_Repository::TYPE_TERM      => 'dashicons-category',
		);

		$class = isset( $map[ $type ] ) ? $map[ $type ] : 'dashicons-heart';
		return apply_filters( 'forwp_favorites_menu_item_icon_class', $class, $type );
	}

	/**
	 * Render favorites menu widget HTML.
	 *
	 * @param array<string, mixed> $args all_url, limit.
	 * @return string
	 */
	public static function render( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'all_url' => '',
				'limit'   => 5,
			)
		);

		if ( ! is_user_logged_in() ) {
			return self::render_guest();
		}

		$all_url = self::resolve_all_url( (string) $args['all_url'] );
		$limit   = absint( $args['limit'] );
		$limit   = $limit > 0 ? min( $limit, 20 ) : 5;

		self::enqueue_assets();

		$manager  = new ForWP_Favorites_Manager();
		$items    = $manager->get_recent_for_user( null, $limit );
		$total    = $manager->count_for_user( null );
		$rest_url = rest_url( ForWP_Notifications_REST_Controller::NAMESPACE );
		$nonce    = wp_create_nonce( 'wp_rest' );

		$i18n = array(
			'empty'    => __( 'No favorites yet', '4wp-notifications' ),
			'goToPage' => __( 'Go to page', '4wp-notifications' ),
			'justNow'  => __( 'just now', '4wp-notifications' ),
			'minAgo'   => __( 'min ago', '4wp-notifications' ),
			'hrAgo'    => __( 'hr ago', '4wp-notifications' ),
			'dAgo'     => __( 'd ago', '4wp-notifications' ),
		);
		wp_localize_script( 'forwp-favorites-menu-widget', 'forwpFavoritesMenuI18n', $i18n );

		ob_start();
		?>
		<div class="forwp-favorites-menu" data-forwp-favorites-menu="1" data-forwp-rest-url="<?php echo esc_url( $rest_url ); ?>" data-forwp-nonce="<?php echo esc_attr( $nonce ); ?>" data-forwp-limit="<?php echo esc_attr( (string) $limit ); ?>" data-forwp-i18n="<?php echo esc_attr( wp_json_encode( $i18n ) ); ?>">
			<button type="button" class="forwp-favorites-menu__button" aria-label="<?php esc_attr_e( 'Favorites', '4wp-notifications' ); ?>" aria-expanded="false" aria-haspopup="true">
				<span class="forwp-favorites-menu__icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" focusable="false">
						<path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
					</svg>
				</span>
				<span class="forwp-favorites-menu__badge" <?php echo $total > 0 ? '' : 'style="display: none;"'; ?>><?php echo $total > 99 ? '99+' : (int) $total; ?></span>
			</button>
			<div class="forwp-favorites-menu__dropdown">
				<div class="forwp-favorites-menu__dropdown-header">
					<h3 class="forwp-favorites-menu__dropdown-title"><?php esc_html_e( 'Favorites', '4wp-notifications' ); ?></h3>
				</div>
				<div class="forwp-favorites-menu__list">
					<?php
					foreach ( $items as $item ) {
						self::render_item( $item );
					}
					?>
					<div class="forwp-favorites-menu__list-empty" <?php echo ! empty( $items ) ? 'style="display: none;"' : ''; ?>><p><?php esc_html_e( 'No favorites yet', '4wp-notifications' ); ?></p></div>
				</div>
				<div class="forwp-favorites-menu__footer">
					<a href="<?php echo esc_url( $all_url ? $all_url : '#' ); ?>"><?php esc_html_e( 'View all favorites', '4wp-notifications' ); ?></a>
				</div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Guest widget: heart icon links to 4WP Account sign-in.
	 *
	 * @return string
	 */
	private static function render_guest() {
		self::enqueue_assets();

		$login_url = ForWP_Favorites_Auth::get_login_url(
			is_singular() ? (string) get_permalink() : ''
		);

		ob_start();
		?>
		<div class="forwp-favorites-menu forwp-favorites-menu--guest" data-forwp-favorites-menu-guest="1" data-forwp-login-url="<?php echo esc_url( $login_url ); ?>">
			<button type="button" class="forwp-favorites-menu__button" aria-label="<?php esc_attr_e( 'Sign in to view favorites', '4wp-notifications' ); ?>">
				<span class="forwp-favorites-menu__icon" aria-hidden="true">
					<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" focusable="false">
						<path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
					</svg>
				</span>
			</button>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Output a single dropdown item.
	 *
	 * @param array<string, mixed> $item Favorite row.
	 */
	public static function render_item( $item ) {
		$type     = isset( $item['type'] ) ? (string) $item['type'] : '';
		$title    = isset( $item['title'] ) ? (string) $item['title'] : '';
		$subtitle = isset( $item['subtitle'] ) ? (string) $item['subtitle'] : '';
		$url      = isset( $item['url'] ) ? (string) $item['url'] : '#';
		$created  = isset( $item['created_at'] ) ? (string) $item['created_at'] : '';
		$time_ago = $created ? human_time_diff( strtotime( $created ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', '4wp-notifications' ) : '';
		$icon     = self::get_item_icon_class( $type );
		$has_link = $url && '#' !== $url;
		?>
		<a href="<?php echo esc_url( $url ); ?>" class="forwp-favorites-menu__item" data-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
			<span class="forwp-favorites-menu__item-icon"><span class="dashicons <?php echo esc_attr( $icon ); ?>" style="font-size:20px;width:20px;height:20px;" aria-hidden="true"></span></span>
			<div class="forwp-favorites-menu__item-content">
				<h4 class="forwp-favorites-menu__item-title"><?php echo esc_html( $title ); ?></h4>
				<?php if ( $subtitle ) : ?>
					<p class="forwp-favorites-menu__item-text"><?php echo esc_html( $subtitle ); ?></p>
				<?php endif; ?>
				<?php if ( $time_ago ) : ?>
					<time class="forwp-favorites-menu__item-time"><?php echo esc_html( $time_ago ); ?></time>
				<?php endif; ?>
				<?php if ( $has_link ) : ?>
					<span class="forwp-favorites-menu__item-link-icon" aria-label="<?php esc_attr_e( 'Go to page', '4wp-notifications' ); ?>"><span class="dashicons dashicons-external"></span></span>
				<?php endif; ?>
			</div>
		</a>
		<?php
	}
}
