<?php
/**
 * Block forwp/notifications — вивід списку повідомлень.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Block {

	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ), 1 );
		add_filter( 'allowed_block_types_all', array( $this, 'ensure_block_allowed' ), 10, 2 );
	}

	/**
	 * Ensure forwp/notifications is in the allowed list (themes can filter it out).
	 *
	 * @param bool|string[] $allowed_block_types Array of block type names, or boolean to enable/disable all.
	 * @param object       $block_editor_context The current block editor context.
	 * @return bool|string[]
	 */
	public function ensure_block_allowed( $allowed_block_types, $block_editor_context ) {
		if ( is_array( $allowed_block_types ) && ! in_array( 'forwp/notifications', $allowed_block_types, true ) ) {
			$allowed_block_types[] = 'forwp/notifications';
		}
		return $allowed_block_types;
	}

	public function register_block() {
		$view_script_module_ids = array();
		if ( function_exists( 'wp_register_script_module' ) ) {
			wp_register_script_module(
				'forwp-notifications-view',
				FORWP_NOTIFICATIONS_PLUGIN_URL . 'assets/blocks/notifications/view.js',
				array( '@wordpress/interactivity' )
			);
			$view_script_module_ids[] = 'forwp-notifications-view';
		}

		register_block_type(
			'forwp/notifications',
			array(
				'api_version'               => 2,
				'title'                     => __( '4WP Notifications', 'forwp-notifications' ),
				'category'                  => 'widgets',
				'description'               => __( 'Display in-app notifications for the logged-in user.', 'forwp-notifications' ),
				'icon'                      => 'bell',
				'attributes'                => array(
					'limit' => array(
						'type'    => 'number',
						'default' => 20,
					),
				),
				'supports'                  => array(
					'html'         => false,
					'align'        => true,
					'interactivity' => true,
				),
				'render_callback'           => array( $this, 'render' ),
				'view_script_module_ids'    => $view_script_module_ids,
			)
		);
	}

	/**
	 * Block render (Interactivity API: state + directives).
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		wp_enqueue_style( '4wp-notifications-shortcode' );
		$limit = isset( $attributes['limit'] ) ? absint( $attributes['limit'] ) : 20;
		$limit = $limit > 0 ? min( $limit, 100 ) : 20;

		if ( ! is_user_logged_in() ) {
			return '';
		}
		$manager = new ForWP_Notifications_Manager();
		$items   = $manager->get_for_user( null, $limit, 0 );
		$unread  = $manager->count_unread( null );

		$state = array(
			'items'       => $items,
			'unreadCount' => $unread,
			'restUrl'     => rest_url( 'forwp/v1' ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'pollInterval' => 30000,
		);
		wp_interactivity_state( 'forwp/notifications', $state );

		ob_start();
		?>
		<div class="forwp-notifications" data-wp-interactive="forwp/notifications" data-wp-init="callbacks.startPolling">
			<?php if ( ! empty( $items ) ) : ?>
				<ul class="forwp-notifications__list">
					<?php foreach ( $items as $item ) : ?>
						<li class="forwp-notifications__item" data-wp-context="<?php echo esc_attr( wp_json_encode( array( 'id' => (int) $item['id'], 'is_read' => (int) $item['is_read'] ) ) ); ?>" data-wp-class--is-read="context.is_read">
							<div class="forwp-notifications__content">
								<?php if ( ! empty( $item['payload']['title'] ) ) : ?>
									<span class="forwp-notifications__title"><?php echo esc_html( $item['payload']['title'] ); ?></span>
								<?php endif; ?>
								<?php if ( ! empty( $item['payload']['message'] ) ) : ?>
									<p class="forwp-notifications__message"><?php echo esc_html( $item['payload']['message'] ); ?></p>
								<?php endif; ?>
								<span class="forwp-notifications__date"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['created_at'] ) ) ); ?></span>
								<?php if ( ! empty( $item['payload']['url'] ) ) : ?>
									<a class="forwp-notifications__link" href="<?php echo esc_url( $item['payload']['url'] ); ?>"><?php esc_html_e( 'View', 'forwp-notifications' ); ?></a>
								<?php endif; ?>
								<button type="button" class="forwp-notifications__mark-read" data-wp-on--click="actions.markRead" data-wp-bind--hidden="context.is_read"><?php esc_html_e( 'Mark as read', 'forwp-notifications' ); ?></button>
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
