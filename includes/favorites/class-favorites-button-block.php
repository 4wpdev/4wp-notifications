<?php
/**
 * Block forwp/favorite-button — toggle favorite in Query Loop or archives.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Favorites_Button_Block {

	const BLOCK_NAME = 'forwp/favorite-button';

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
		unset( $block_editor_context );

		if ( is_array( $allowed_block_types ) && ! in_array( self::BLOCK_NAME, $allowed_block_types, true ) ) {
			$allowed_block_types[] = self::BLOCK_NAME;
		}

		return $allowed_block_types;
	}

	public function register_block() {
		register_block_type(
			FORWP_NOTIFICATIONS_PLUGIN_DIR . 'assets/blocks/favorite-button',
			array(
				'render_callback' => array( $this, 'render' ),
			)
		);
	}

	/**
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content    Block content.
	 * @param WP_Block             $block      Block instance.
	 * @return string
	 */
	public function render( $attributes, $content, $block ) {
		unset( $content );

		$context = array();
		if ( $block instanceof WP_Block && ! empty( $block->context ) ) {
			$context = $block->context;
		}

		return ForWP_Favorites_Button_Renderer::render( is_array( $attributes ) ? $attributes : array(), $context );
	}
}
