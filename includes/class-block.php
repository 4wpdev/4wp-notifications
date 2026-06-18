<?php
/**
 * Block forwp/notifications — full notifications list.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Block {

	const BLOCK_NAME = 'forwp/notifications';

	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_assets' ) );
		add_filter( 'allowed_block_types_all', array( $this, 'ensure_block_allowed' ), 10, 2 );
	}

	public function enqueue_editor_assets() {
		wp_enqueue_style( 'dashicons' );
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
			FORWP_NOTIFICATIONS_PLUGIN_DIR . 'assets/blocks/notifications',
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
		$limit = isset( $attributes['limit'] ) ? absint( $attributes['limit'] ) : 20;
		return ForWP_Notifications_List_Renderer::render( $limit );
	}
}
