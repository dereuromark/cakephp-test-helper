<?php

namespace TestHelper\Test\TestCase\Controller;

use Shim\TestSuite\IntegrationTestCase;

/**
 * @uses \TestHelper\Controller\TestHelperController
 */
class TestFixturesControllerTest extends IntegrationTestCase {

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestFixtures', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testGenerate() {
		$this->disableErrorHandlerMiddleware();
		//$this->_retainFlashMessages = true; // Only necessary for rendering involved.

		$data = [
			'plugin' => 'Tools',
			'name' => 'MyCoolRecords',
		];

		$this->post(['plugin' => 'TestHelper', 'controller' => 'TestFixtures', 'action' => 'generate'], $data);

		$this->assertResponseCode(302);

		$flash = $this->_requestSession->read('Flash.flash');
		//FIXME - why null?
		/*
		$flash = Hash::combine($flash, '{n}.element', '{n}.message');
		$this->assertTextContains('bake fixture MyCoolRecords -q -p Tools', $flash['flash/info']);
		$this->assertSame('MyCoolRecordsFixture generated.', $flash['flash/success']);
		*/
	}

}
