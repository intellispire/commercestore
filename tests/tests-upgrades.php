<?php


/**
 * @group cs_upgrades
 */
class Tests_Upgrades extends CS_UnitTestCase {

	public function set_up() {
		parent::set_up();
		require_once CS_PLUGIN_DIR . 'includes/admin/upgrades/upgrade-functions.php';
	}

	public function tear_down() {
		parent::tear_down();
	}

	public function test_upgrade_completion() {

		$current_upgrades = cs_get_completed_upgrades();
		// Since we mark previous upgrades as complete upon install
		$this->assertTrue( ! empty( $current_upgrades ) );
		$this->assertInternalType( 'array', $current_upgrades );

		$this->assertTrue( cs_set_upgrade_complete( 'test-upgrade-action' ) );
		$this->assertTrue( cs_has_upgrade_completed( 'test-upgrade-action' ) );
		$this->assertFalse( cs_has_upgrade_completed( 'test-upgrade-action-false' ) );

	}

}
