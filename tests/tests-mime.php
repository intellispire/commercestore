<?php


/**
 * @group cs_mime
 */
class Tests_Mime extends CS_UnitTestCase {

	public function setUp() {
		parent::setUp();
	}

	public function tear_down() {
		parent::tear_down();
	}

	public function testAllowedMimeTypes() {
		$mime = get_allowed_mime_types();

		$this->assertArrayHasKey( 'zip', $mime );
		$this->assertArrayHasKey( 'epub', $mime );
		$this->assertArrayHasKey( 'mobi', $mime );
		$this->assertArrayHasKey( 'aiff', $mime );
		$this->assertArrayHasKey( 'aif', $mime );
		$this->assertArrayHasKey( 'psd', $mime );
		$this->assertArrayHasKey( 'exe', $mime );
		$this->assertArrayHasKey( 'apk', $mime );
		$this->assertArrayHasKey( 'msi', $mime );
	}
}
