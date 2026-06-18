<?php
/**
 * Block forwp/notifications-bell — bell widget for headers and navigation.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Bell_Block {

	const BLOCK_NAME = 'forwp/notifications-bell';

	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_filter( 'allowed_block_types_all', array( $this, 'ensure_block_allowed' ), 10, 2 );
	}

	/**
	 * @param bool|string[]           $allowed_block_types Allowed blocks.
	 * @param WP_Block_Editor_Context $block_editor_context Editor context.
	 * @return bool|string[]
	 */
	public function ensure_block_allowed( $allowed_block_types, $block_editor_context ) {
		if ( is_array( $allowed_block_types ) && ! in_array( self::BLOCK_NAME, $allowed_block_types, true ) ) {
			$allowed_block_types[] = self::BLOCK_NAME;
		}
		return $allowed_block_types;
	}

	public function register_block() {
		register_block_type(
			FORWP_NOTIFICATIONS_PLUGIN_DIR . 'assets/blocks/notifications-bell',
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * @param array<string, mixed> $attributes Block attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		return ForWP_Notifications_Bell_Renderer::render(
			array(
				'all_url' => isset( $attributes['allUrl'] ) ? (string) $attributes['allUrl'] : '',
				'limit'   => isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 20,
			)
		);
	}
}
