<?php
/**
 * Notification Importer Tests
 *
 * @package   commercestore
 * @copyright Copyright (c) 2021, CommerceStore
 * @license   GPL2+
 */

namespace CS\Tests\Notifications;

use CS\Utils\NotificationImporter;

/**
 * @coversDefaultClass \CS\Utils\NotificationImporter
 * @group cs_notifications
 */
class NotificationImporterTests extends \CS_UnitTestCase {

	/**
	 * Truncates the notification table before each test.
	 */
	public function setUp() {
		parent::setUp();

		global $wpdb;
		$tableName = CS()->notifications->table_name;

		$wpdb->query( "TRUNCATE TABLE {$tableName}" );
	}

	/**
	 * Builds a mock of the NotificationImporter class, overriding the return
	 * value of fetchNotifications().
	 *
	 * @return NotificationImporter
	 */
	protected function getMockImporter( $returnValue = array() ) {
		$mock = $this->getMockBuilder( '\\CS\\Utils\\NotificationImporter' )
		             ->setMethods( array( 'fetchNotifications' ) )
		             ->getMock();

		// Doing a json_decode / encode here so that we end up with an array of objects.
		$mock->method( 'fetchNotifications' )
		     ->willReturn( json_decode( json_encode( $returnValue ) ) );

		return $mock;
	}

	/**
	 * Returns all notifications in the database.
	 *
	 * @return object[]
	 */
	protected function getNotifications() {
		global $wpdb;
		$tableName = CS()->notifications->table_name;

		return $wpdb->get_results( "SELECT * FROM {$tableName}" );
	}

	/**
	 * @covers \CS\Utils\NotificationImporter::insertNewNotification
	 */
	public function test_valid_notification_is_imported() {
		$importer = $this->getMockImporter( array(
			array(
				'title'             => 'Announcing New CommerceStore Feature',
				'content'           => 'This is an exciting new CommerceStore feature.',
				'id'                => 90,
				'start'             => null,
				'notification_type' => 'success',
			)
		) );

		$importer->run();

		$notifications = $this->getNotifications();

		$this->assertSame( 1, count( $notifications ) );

		$this->assertSame( 'Announcing New CommerceStore Feature', $notifications[0]->title );
		$this->assertSame( 'This is an exciting new CommerceStore feature.', $notifications[0]->content );
		$this->assertEquals( 90, $notifications[0]->remote_id );
		$this->assertSame( null, $notifications[0]->start );
		$this->assertSame( 'success', $notifications[0]->type );
	}

	/**
	 * @covers \CS\Utils\NotificationImporter::validateNotification
	 */
	public function test_notification_with_no_title_not_imported() {
		$importer = $this->getMockImporter( array(
			array(
				// title is missing
				'content'           => 'This is an exciting new CommerceStore feature.',
				'id'                => 90,
				'start'             => null,
				'notification_type' => 'success',
			)
		) );

		$importer->run();

		$notifications = $this->getNotifications();

		$this->assertSame( 0, count( $notifications ) );
	}

	/**
	 * @covers \CS\Utils\NotificationImporter::updateExistingNotification
	 */
	public function test_existing_notification_updated_with_new_content() {
		$importer = $this->getMockImporter( array(
			array(
				'title'             => 'Announcing New CommerceStore Feature',
				'content'           => 'This is an exciting new CommerceStore feature.',
				'id'                => 90,
				'start'             => null,
				'notification_type' => 'success',
			)
		) );

		$importer->run();

		$notifications = $this->getNotifications();
		$this->assertSame( 'This is an exciting new CommerceStore feature.', $notifications[0]->content );

		$importer = $this->getMockImporter( array(
			array(
				'title'             => 'Announcing New CommerceStore Feature',
				'content'           => 'This is an exciting new CommerceStore feature with updated content.',
				'id'                => 90,
				'start'             => null,
				'notification_type' => 'success',
			)
		) );

		$importer->run();

		$notifications = $this->getNotifications();
		$this->assertSame( 'This is an exciting new CommerceStore feature with updated content.', $notifications[0]->content );
	}

	/**
	 * Notification has an end date of 2 days ago, so it should not be imported.
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Notification has expired.
	 * @throws \Exception
	 */
	public function test_ended_notification_doesnt_validate() {
		$importer = new NotificationImporter();

		if ( method_exists( $this, 'setExpectedException' ) ) {
			$this->setExpectedException( 'Exception', 'Notification has expired.' );
		}

		$notification                    = new \stdClass();
		$notification->title             = 'Announcing New CommerceStore Feature';
		$notification->content           = 'This is an exciting new CommerceStore feature.';
		$notification->id                = 90;
		$notification->end               = date( 'Y-m-d H:i:s', strtotime( '-2 days' ) );
		$notification->notification_type = 'success';

		$importer->validateNotification( $notification );
	}

	/**
	 * CommerceStore was installed today, but notification was created 2 days ago. It should not
	 * validate because we only accept notifications created _after_ CommerceStore was installed.
	 *
	 * @covers \CS\Utils\NotificationImporter::validateNotification
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Notification created prior to CommerceStore activation.
	 * @throws \Exception
	 */
	public function test_notification_started_before_installation_date_doesnt_validate() {
		$importer = new NotificationImporter();

		if ( method_exists( $this, 'setExpectedException' ) ) {
			$this->setExpectedException( 'Exception', 'Notification created prior to CommerceStore activation.' );
		}

		$notification                    = new \stdClass();
		$notification->title             = 'Announcing New CommerceStore Feature';
		$notification->content           = 'This is an exciting new CommerceStore feature.';
		$notification->id                = 90;
		$notification->start               = date( 'Y-m-d H:i:s', strtotime( '-2 days' ) );
		$notification->notification_type = 'success';

		$importer->validateNotification( $notification );
	}

	/**
	 * @covers \CS\Utils\NotificationImporter::validateNotification
	 *
	 * @expectedException \Exception
	 * @throws \Exception
	 */
	public function test_notification_missing_properties_doesnt_validate() {
		$importer = new NotificationImporter();

		if ( method_exists( $this, 'setExpectedException' ) ) {
			$this->setExpectedException( 'Exception', 'Missing required properties: ["title"]' );
		}

		$notification                    = new \stdClass();
		$notification->content           = 'This is an exciting new CommerceStore feature.';
		$notification->id                = 90;
		$notification->notification_type = 'success';

		$importer->validateNotification( $notification );
	}

	/**
	 * @covers \CS\Utils\NotificationImporter::validateNotification
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Condition(s) not met.
	 * @throws \Exception
	 */
	public function test_notification_for_pass_holders_not_validated_for_free_install() {
		$importer = new NotificationImporter();

		if ( method_exists( $this, 'setExpectedException' ) ) {
			$this->setExpectedException( 'Exception', 'Condition(s) not met.' );
		}

		$notification                    = new \stdClass();
		$notification->title             = 'Announcing New CommerceStore Feature for Pass Holders';
		$notification->content           = 'This is an exciting new CommerceStore feature.';
		$notification->id                = 90;
		$notification->notification_type = 'success';
		$notification->type              = array( 'pass-any' );

		$importer->validateNotification( $notification );
	}

	/**
	 * @covers \CS\Utils\NotificationImporter::validateNotification
	 *
	 * @expectedException \Exception
	 * @expectedExceptionMessage Condition(s) not met.
	 * @throws \Exception
	 */
	public function test_notification_for_version_1x_not_validated() {
		$importer = new NotificationImporter();

		if ( method_exists( $this, 'setExpectedException' ) ) {
			$this->setExpectedException( 'Exception', 'Condition(s) not met.' );
		}

		$notification                    = new \stdClass();
		$notification->title             = 'Announcing New CommerceStore Feature for Pass Holders';
		$notification->content           = 'This is an exciting new CommerceStore feature.';
		$notification->id                = 90;
		$notification->notification_type = 'success';
		$notification->type              = array( '1-x' );

		$importer->validateNotification( $notification );
	}

}
