<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;
use Cake\Utility\Hash;

/**
 * @uses \TestHelper\Controller\TestHelperController
 */
class TestFixturesControllerTest extends IntegrationTestCase {

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestFixtures', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testGenerate() {
		$data = [
			'plugin' => 'Tools',
			'name' => 'MyCoolRecords',
		];

		$this->post(['plugin' => 'TestHelper', 'controller' => 'TestFixtures', 'action' => 'generate'], $data);

		$this->assertResponseCode(302);

		$flash = $this->_requestSession->read('Flash.flash');
		$flash = Hash::combine($flash, '{n}.element', '{n}.message');
		$this->assertTextContains('bake fixture MyCoolRecords -q -p Tools', $flash['Flash/info']);
		$this->assertSame('MyCoolRecordsFixture generated.', $flash['Flash/success']);
	}

}
