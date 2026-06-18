<?php
/**
 * @package ForWP_Notifications
 */

use PHPUnit\Framework\TestCase;

class NotificationSenderTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		forwp_notifications_tests_reset();
	}

	public function test_sanitize_payload(): void {
		$payload = ForWP_Notifications_Sender::sanitize_payload(
			array(
				'title'   => '  Hello <b>world</b> ',
				'message' => 'Line one<script>alert(1)</script>',
				'url'     => 'https://example.com/post/',
			)
		);

		$this->assertSame( 'Hello world', $payload['title'] );
		$this->assertSame( 'Line onealert(1)', $payload['message'] );
		$this->assertSame( 'https://example.com/post/', $payload['url'] );
	}

	public function test_normalize_user_ids_deduplicates(): void {
		$ids = ForWP_Notifications_Sender::normalize_user_ids( 5, array( 5, 12, 12, 0 ) );
		$this->assertSame( array( 5, 12 ), $ids );
	}

	public function test_send_requires_type(): void {
		$result = ForWP_Notifications_Sender::send(
			array( 1 ),
			'',
			'core',
			array( 'title' => 'Test' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'forwp_notifications_invalid_type', $result->get_error_code() );
	}

	public function test_send_requires_title(): void {
		$result = ForWP_Notifications_Sender::send(
			array( 1 ),
			'custom',
			'core',
			array( 'message' => 'No title' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'forwp_notifications_missing_title', $result->get_error_code() );
	}

	public function test_send_requires_user_ids(): void {
		$result = ForWP_Notifications_Sender::send(
			array(),
			'custom',
			'core',
			array( 'title' => 'Test' )
		);

		$this->assertInstanceOf( WP_Error::class, $result );
		$this->assertSame( 'forwp_notifications_missing_users', $result->get_error_code() );
	}

	public function test_user_can_send_respects_capability(): void {
		$GLOBALS['forwp_notifications_test_caps']['manage_options'] = true;
		$this->assertTrue( ForWP_Notifications_Sender::user_can_send() );

		$GLOBALS['forwp_notifications_test_caps']['manage_options'] = false;
		$this->assertFalse( ForWP_Notifications_Sender::user_can_send() );
	}
}
