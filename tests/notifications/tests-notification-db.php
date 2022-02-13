<?php
/**
 * Notification Database Tests
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, CommerceStore
 * @license   GPL2+
 */

namespace CS\Tests\Notifications;

use CS\Models\Notification;

/**
 * @coversDefaultClass \CS\Database\NotificationsDB
 * @group cs_notifications
 */
class NotificationDBTests extends \CS_UnitTestCase {

	/**
	 * @covers \CS\Database\NotificationsDB::getActiveNotifications
	 */
	public function test_notification_included_in_active() {
		CS()->notifications->insert( array(
			'title'     => 'Notification',
			'content'   => 'Notification',
			'type'      => 'success',
			'dismissed' => 0,
		), 'notification' );

		$notifications = CS()->notifications->getActiveNotifications();

		$this->assertSame( 1, count( $notifications ) );
		$this->assertTrue( $notifications[0] instanceof Notification );
		$this->assertSame( 'Notification', $notifications[0]->title );
	}

	/**
	 * @covers \CS\Database\NotificationsDB::getActiveNotifications
	 */
	public function test_dismissed_notification_not_included_in_active() {
		CS()->notifications->insert( array(
			'title'     => 'Notification',
			'content'   => 'Notification',
			'type'      => 'success',
			'dismissed' => 1,
		), 'notification' );

		$this->assertEmpty( CS()->notifications->getActiveNotifications() );
	}

	/**
	 * @covers \CS\Database\NotificationsDB::getActiveNotifications
	 */
	public function test_start_date_in_future_not_included_in_active() {
		CS()->notifications->insert( array(
			'title'   => 'Notification',
			'content' => 'Notification',
			'type'    => 'success',
			'start'   => date( 'Y-m-d H:i:s', strtotime( '+1 week' ) ),
		), 'notification' );

		$this->assertEmpty( CS()->notifications->getActiveNotifications() );
	}

	/**
	 * @covers \CS\Database\NotificationsDB::getActiveNotifications
	 */
	public function test_end_date_in_past_not_included_in_active() {
		CS()->notifications->insert( array(
			'title'   => 'Notification',
			'content' => 'Notification',
			'type'    => 'success',
			'end'     => date( 'Y-m-d H:i:s', strtotime( '-1 week' ) ),
		), 'notification' );

		$this->assertEmpty( CS()->notifications->getActiveNotifications() );
	}

	/**
	 * @covers \CS\Database\NotificationsDB::getActiveNotifications
	 */
	public function test_end_date_in_future_included_in_active() {
		// Ends in 1 week - still valid.
		CS()->notifications->insert( array(
			'title'   => 'Notification',
			'content' => 'Notification',
			'type'    => 'success',
			'end'     => date( 'Y-m-d H:i:s', strtotime( '+1 week' ) ),
		), 'notification' );

		// Ended 1 week ago - should not be in results.
		CS()->notifications->insert( array(
			'title'   => 'Notification',
			'content' => 'Notification',
			'type'    => 'success',
			'end'     => date( 'Y-m-d H:i:s', strtotime( '-1 week' ) ),
		), 'notification' );

		$notifications = CS()->notifications->getActiveNotifications();

		$this->assertSame( 1, count( $notifications ) );
	}

}
