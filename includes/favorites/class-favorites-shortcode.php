<?php
/**
 * Shortcodes for favorites list and button.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Favorites_Shortcode {

	public function __construct() {
		add_shortcode( 'forwp_favorites', array( $this, 'render_list' ) );
		add_shortcode( 'forwp_favorite_button', array( $this, 'render_button' ) );
		add_shortcode( 'forwp_favorites_menu', array( $this, 'render_menu' ) );
		add_shortcode( '4wp_favorites_menu', array( $this, 'render_menu' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets() {
		ForWP_Favorites_List_Renderer::register_assets();
		ForWP_Favorites_Menu_Renderer::register_assets();
	}

	/**
	 * @return string
	 */
	public function render_list() {
		return ForWP_Favorites_List_Renderer::render();
	}

	/**
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_button( $atts ) {
		$atts = shortcode_atts(
			array(
				'target_mode'   => 'auto',
				'post_id'       => '0',
				'post_type'     => '',
				'term_id'       => '0',
				'taxonomy'      => '',
				'show_label'    => '0',
				'label_add'     => '',
				'label_remove'  => '',
			),
			$atts,
			'forwp_favorite_button'
		);

		return ForWP_Favorites_Button_Renderer::render(
			array(
				'targetMode'   => sanitize_key( $atts['target_mode'] ),
				'postId'       => (int) $atts['post_id'],
				'postTypeSlug' => sanitize_key( $atts['post_type'] ),
				'termId'       => (int) $atts['term_id'],
				'taxonomy'     => sanitize_key( $atts['taxonomy'] ),
				'showLabel'    => ! empty( $atts['show_label'] ),
				'labelAdd'     => sanitize_text_field( $atts['label_add'] ),
				'labelRemove'  => sanitize_text_field( $atts['label_remove'] ),
			)
		);
	}

	/**
	 * @param array<string, string> $atts Shortcode attributes.
	 * @return string
	 */
	public function render_menu( $atts ) {
		$atts = shortcode_atts(
			array(
				'all_url' => '',
				'limit'   => '5',
			),
			$atts,
			'forwp_favorites_menu'
		);

		return ForWP_Favorites_Menu_Renderer::render(
			array(
				'all_url' => (string) $atts['all_url'],
				'limit'   => (int) $atts['limit'],
			)
		);
	}
}
