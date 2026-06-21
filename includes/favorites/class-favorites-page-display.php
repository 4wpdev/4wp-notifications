<?php
/**
 * Ensures the configured favorites page shows the list block.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Favorites_Page_Display {

	const SEED_OPTION = 'forwp_favorites_page_seeded_v1';

	public function __construct() {
		add_filter( 'the_content', array( $this, 'maybe_inject_list' ), 9 );
		add_action( 'init', array( $this, 'maybe_seed_configured_page' ), 20 );
	}

	/**
	 * @param string $content Post content.
	 * @return string
	 */
	public function maybe_inject_list( $content ) {
		if ( ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$page_id = ForWP_Notifications_Plugin_Settings::get_favorites_page_id();
		if ( $page_id <= 0 || get_the_ID() !== $page_id ) {
			return $content;
		}

		$post = get_post();
		if ( self::page_has_list( $post ) ) {
			return $content;
		}

		$list = ForWP_Favorites_List_Renderer::render();
		if ( '' === $list ) {
			return $content;
		}

		if ( '' === trim( $content ) ) {
			return $list;
		}

		return $content . $list;
	}

	public function maybe_seed_configured_page() {
		if ( get_option( self::SEED_OPTION ) ) {
			return;
		}

		$page_id = ForWP_Notifications_Plugin_Settings::get_favorites_page_id();
		if ( $page_id > 0 ) {
			self::ensure_page_has_list_block( $page_id );
		}

		update_option( self::SEED_OPTION, 1 );
	}

	/**
	 * @param int $page_id Page ID.
	 * @return bool
	 */
	public static function ensure_page_has_list_block( $page_id ) {
		$page_id = (int) $page_id;
		if ( $page_id <= 0 ) {
			return false;
		}

		$post = get_post( $page_id );
		if ( ! $post || 'page' !== $post->post_type ) {
			return false;
		}

		if ( self::page_has_list( $post ) ) {
			return false;
		}

		$block       = '<!-- wp:forwp/favorites-list /-->';
		$content     = trim( (string) $post->post_content );
		$new_content = '' !== $content ? $content . "\n\n" . $block : $block;

		wp_update_post(
			array(
				'ID'           => $page_id,
				'post_content' => $new_content,
			)
		);

		return true;
	}

	/**
	 * @param WP_Post|null $post Post object.
	 * @return bool
	 */
	public static function page_has_list( $post ) {
		if ( ! $post instanceof WP_Post ) {
			return false;
		}

		if ( has_block( 'forwp/favorites-list', $post ) ) {
			return true;
		}

		return has_shortcode( (string) $post->post_content, 'forwp_favorites' );
	}
}
