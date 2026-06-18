<?php
/**
 * @package ForWP_Notifications
 */

use PHPUnit\Framework\TestCase;

class RecipientResolverTest extends TestCase {

	public function test_resolve_ids_deduplicates_explicit_users(): void {
		$ids = ForWP_Notifications_Recipient_Resolver::resolve_ids( array( 3, 3, 7, 0 ), array() );
		$this->assertSame( array( 3, 7 ), $ids );
	}
}
