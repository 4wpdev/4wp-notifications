<?php
/**
 * Integrates 4WP Notifications with 4WP Account cabinet.
 *
 * @package ForWP_Notifications
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ForWP_Notifications_Account_Bridge {

	public function __construct() {
		add_filter( 'forwp_account_section_content', array( $this, 'render_account_section' ), 10, 2 );
		add_filter( 'forwp_account_section_url', array( $this, 'filter_section_url' ), 10, 2 );
		add_filter( 'forwp_account_menu_sections', array( $this, 'ensure_menu_sections' ), 20 );
	}

	/**
	 * Ensure notifications sections exist when this plugin is active.
	 *
	 * @param array<string, array<string, mixed>> $sections Menu sections.
	 * @return array<string, array<string, mixed>>
	 */
	public function ensure_menu_sections( array $sections ) {
		if ( ! isset( $sections['favorites'] ) ) {
			$sections['favorites'] = array(
				'label'       => __( 'Favorites', '4wp-notifications' ),
				'description' => __( 'Saved CPT subscriptions, categories, and posts.', '4wp-notifications' ),
				'group'       => '4wp-notifications',
				'plugin'      => '4wp-notifications/4wp-notifications.php',
				'enabled'     => true,
				'order'       => 20,
			);
		}

		if ( ! isset( $sections['notifications'] ) ) {
			$sections['notifications'] = array(
				'label'       => __( 'Notifications', '4wp-notifications' ),
				'description' => __( 'In-app notification inbox.', '4wp-notifications' ),
				'group'       => '4wp-notifications',
				'plugin'      => '4wp-notifications/4wp-notifications.php',
				'enabled'     => true,
				'order'       => 25,
			);
		}

		return $sections;
	}

	/**
	 * @param string|null $content Current section content.
	 * @param string      $section Section slug.
	 * @return string|null
	 */
	public function render_account_section( $content, $section ) {
		if ( 'favorites' === $section ) {
			return ForWP_Favorites_List_Renderer::render();
		}

		if ( 'notifications' === $section ) {
			return ForWP_Notifications_List_Renderer::render( 20 );
		}

		return $content;
	}

	/**
	 * @param string $url     Section URL.
	 * @param string $section Section slug.
	 * @return string
	 */
	public function filter_section_url( $url, $section ) {
		if ( 'favorites' === $section ) {
			$page_id = ForWP_Notifications_Plugin_Settings::get_favorites_page_id();
			if ( $page_id > 0 ) {
				$page_url = get_permalink( $page_id );
				if ( $page_url ) {
					return $page_url;
				}
			}
		}

		if ( 'notifications' === $section ) {
			$page_id = ForWP_Notifications_Plugin_Settings::get_page_id();
			if ( $page_id > 0 ) {
				$page_url = get_permalink( $page_id );
				if ( $page_url ) {
					return $page_url;
				}
			}
		}

		return $url;
	}
}
