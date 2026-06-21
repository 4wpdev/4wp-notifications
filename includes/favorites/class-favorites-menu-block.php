<?php
/**
 * Block forwp/favorites-menu — favorites dropdown for headers and navigation.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Favorites_Menu_Block {

	const BLOCK_NAME = 'forwp/favorites-menu';

	public function __construct() {
		add_action( 'init', array( $this, 'register_block' ) );
		add_filter( 'allowed_block_types_all', array( $this, 'ensure_block_allowed' ), 99, 2 );
		add_filter( 'block_core_navigation_listable_blocks', array( $this, 'allow_in_navigation' ) );
	}

	/**
	 * @param bool|string[]           $allowed_block_types Allowed blocks.
	 * @param WP_Block_Editor_Context $block_editor_context Editor context.
	 * @return bool|string[]
	 */
	public function ensure_block_allowed( $allowed_block_types, $block_editor_context ) {
		unset( $block_editor_context );

		if ( true === $allowed_block_types ) {
			return $allowed_block_types;
		}

		if ( ! is_array( $allowed_block_types ) ) {
			$allowed_block_types = array();
		}

		if ( ! in_array( self::BLOCK_NAME, $allowed_block_types, true ) ) {
			$allowed_block_types[] = self::BLOCK_NAME;
		}

		return $allowed_block_types;
	}

	/**
	 * Keep menu widget usable inside core/navigation menus.
	 *
	 * @param string[] $blocks Blocks that need a list-item wrapper in navigation.
	 * @return string[]
	 */
	public function allow_in_navigation( array $blocks ) {
		if ( ! in_array( self::BLOCK_NAME, $blocks, true ) ) {
			$blocks[] = self::BLOCK_NAME;
		}

		return $blocks;
	}

	public function register_block() {
		register_block_type(
			FORWP_NOTIFICATIONS_PLUGIN_DIR . 'assets/blocks/favorites-menu',
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
		return ForWP_Favorites_Menu_Renderer::render(
			array(
				'all_url' => isset( $attributes['allUrl'] ) ? (string) $attributes['allUrl'] : '',
				'limit'   => isset( $attributes['limit'] ) ? (int) $attributes['limit'] : 5,
			)
		);
	}
}
