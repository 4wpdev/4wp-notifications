<?php
/**
 * @package ForWP_Notifications
 */

use PHPUnit\Framework\TestCase;

class PageDisplayTest extends TestCase {

	public function test_page_has_list_detects_shortcode(): void {
		$post              = new WP_Post();
		$post->post_type   = 'page';
		$post->post_content = 'Intro [forwp_notifications]';

		$this->assertTrue( ForWP_Notifications_Page_Display::page_has_list( $post ) );
	}

	public function test_page_has_list_false_for_empty_page(): void {
		$post              = new WP_Post();
		$post->post_type   = 'page';
		$post->post_content = 'Hello world';

		$this->assertFalse( ForWP_Notifications_Page_Display::page_has_list( $post ) );
	}
}
