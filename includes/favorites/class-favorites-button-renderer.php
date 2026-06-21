<?php
/**
 * Favorite toggle button markup.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Favorites_Button_Renderer {

	/**
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param array<string, mixed> $context    Block context.
	 * @return string
	 */
	public static function render( array $attributes, array $context = array() ) {
		ForWP_Favorites_List_Renderer::enqueue_assets();

		$manager = new ForWP_Favorites_Manager();
		$target  = $manager->resolve_target( $attributes, $context );

		if ( null === $target ) {
			if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
				return '<div class="forwp-favorite-button forwp-favorite-button--placeholder">' . esc_html__( 'Favorite button (no target on this template).', '4wp-notifications' ) . '</div>';
			}
			return '';
		}

		$show_label = ! empty( $attributes['showLabel'] );
		$label_add  = isset( $attributes['labelAdd'] ) && (string) $attributes['labelAdd'] !== ''
			? (string) $attributes['labelAdd']
			: __( 'Add to favorites', '4wp-notifications' );
		$label_remove = isset( $attributes['labelRemove'] ) && (string) $attributes['labelRemove'] !== ''
			? (string) $attributes['labelRemove']
			: __( 'In favorites', '4wp-notifications' );

		$active = is_user_logged_in()
			? $manager->is_active( get_current_user_id(), $target['type'], $target['ref_id'], $target['ref_key'] )
			: false;

		$classes = array( 'forwp-favorite-button' );
		if ( $active ) {
			$classes[] = 'is-active';
		}
		if ( ! is_user_logged_in() ) {
			$classes[] = 'forwp-favorite-button--guest';
		}

		ob_start();
		?>
		<button
			type="button"
			class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
			data-forwp-favorite-button="1"
			data-forwp-fav-type="<?php echo esc_attr( $target['type'] ); ?>"
			data-forwp-fav-ref-id="<?php echo esc_attr( (string) $target['ref_id'] ); ?>"
			data-forwp-fav-ref-key="<?php echo esc_attr( $target['ref_key'] ); ?>"
			data-forwp-label-add="<?php echo esc_attr( $label_add ); ?>"
			data-forwp-label-remove="<?php echo esc_attr( $label_remove ); ?>"
			aria-pressed="<?php echo $active ? 'true' : 'false'; ?>"
			<?php echo ! is_user_logged_in() ? 'data-forwp-login="1"' : ''; ?>
		>
			<span class="forwp-favorite-button__icon" aria-hidden="true">
				<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20" focusable="false">
					<path fill="currentColor" d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
				</svg>
			</span>
			<?php if ( $show_label ) : ?>
				<span class="forwp-favorite-button__label"><?php echo esc_html( $active ? $label_remove : $label_add ); ?></span>
			<?php else : ?>
				<span class="screen-reader-text forwp-favorite-button__label"><?php echo esc_html( $active ? $label_remove : $label_add ); ?></span>
			<?php endif; ?>
		</button>
		<?php
		return (string) ob_get_clean();
	}
}
