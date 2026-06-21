<?php
/**
 * Frontend markup for the favorites list.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Favorites_List_Renderer {

	/**
	 * Register assets.
	 */
	public static function register_assets() {
		wp_register_style(
			'forwp-favorites',
			FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/css/favorites.css',
			array(),
			FORWP_NOTIFICATIONS_VERSION
		);
		wp_register_script(
			'forwp-favorites',
			FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/js/favorites.js',
			array(),
			FORWP_NOTIFICATIONS_VERSION,
			true
		);
	}

	/**
	 * Enqueue assets.
	 */
	public static function enqueue_assets() {
		self::register_assets();
		wp_enqueue_style( 'forwp-favorites' );
		wp_enqueue_script( 'forwp-favorites' );
		wp_localize_script(
			'forwp-favorites',
			'forwpFavorites',
			array(
				'restUrl'  => rest_url( ForWP_Notifications_REST_Controller::NAMESPACE ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'loginUrl' => ForWP_Favorites_Auth::get_login_url( is_singular() ? get_permalink() : '' ),
				'i18n'     => array(
					'add'     => __( 'Add to favorites', '4wp-notifications' ),
					'remove'  => __( 'Remove from favorites', '4wp-notifications' ),
					'empty'   => __( 'No favorites yet.', '4wp-notifications' ),
					'error'   => __( 'Could not update favorites.', '4wp-notifications' ),
					'login'   => __( 'Sign in to save favorites.', '4wp-notifications' ),
				),
			)
		);
	}

	/**
	 * Render grouped favorites list.
	 *
	 * @return string
	 */
	public static function render() {
		if ( ! is_user_logged_in() ) {
			self::enqueue_assets();
			ob_start();
			?>
			<div class="forwp-favorites forwp-favorites--guest">
				<p class="forwp-favorites__empty"><?php esc_html_e( 'Sign in to view your favorites.', '4wp-notifications' ); ?></p>
				<p><a class="forwp-favorites__login" href="<?php echo esc_url( ForWP_Favorites_Auth::get_login_url( get_permalink() ) ); ?>"><?php esc_html_e( 'Sign in', '4wp-notifications' ); ?></a></p>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		self::enqueue_assets();

		$manager = new ForWP_Favorites_Manager();
		$groups  = $manager->get_grouped_for_user( get_current_user_id() );

		ob_start();
		?>
		<div class="forwp-favorites" data-forwp-favorites-list="1">
			<?php if ( empty( $groups ) ) : ?>
				<p class="forwp-favorites__empty"><?php esc_html_e( 'No favorites yet.', '4wp-notifications' ); ?></p>
			<?php else : ?>
				<?php foreach ( $groups as $group ) : ?>
					<section class="forwp-favorites__group">
						<h3 class="forwp-favorites__group-title"><?php echo esc_html( (string) $group['label'] ); ?></h3>
						<ul class="forwp-favorites__items">
							<?php foreach ( $group['items'] as $item ) : ?>
								<li class="forwp-favorites__item" data-forwp-fav-id="<?php echo esc_attr( (string) $item['id'] ); ?>">
									<div class="forwp-favorites__item-main">
										<?php if ( ! empty( $item['url'] ) ) : ?>
											<a class="forwp-favorites__item-link" href="<?php echo esc_url( (string) $item['url'] ); ?>"><?php echo esc_html( (string) $item['title'] ); ?></a>
										<?php else : ?>
											<span class="forwp-favorites__item-title"><?php echo esc_html( (string) $item['title'] ); ?></span>
										<?php endif; ?>
										<?php if ( ! empty( $item['subtitle'] ) ) : ?>
											<span class="forwp-favorites__item-subtitle"><?php echo esc_html( (string) $item['subtitle'] ); ?></span>
										<?php endif; ?>
									</div>
									<button type="button" class="forwp-favorites__remove" data-forwp-fav-remove="<?php echo esc_attr( (string) $item['id'] ); ?>" aria-label="<?php esc_attr_e( 'Remove from favorites', '4wp-notifications' ); ?>">&times;</button>
								</li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
