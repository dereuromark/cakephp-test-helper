<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use TestApp\TestSuite\TestSession;

/**
 * @uses \TestHelper\Controller\TestFixturesController
 */
class TestFixturesControllerTest extends TestCase {

	use IntegrationTestTrait;

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

		$data = [
			'plugin' => 'Tools',
			'name' => 'MyCoolRecords',
		];

		$this->post(['plugin' => 'TestHelper', 'controller' => 'TestFixtures', 'action' => 'generate'], $data);

		$this->assertResponseCode(302);

		$flash = (new TestSession($_SESSION))->readOrFail('Flash.flash');
		$flash = Hash::combine($flash, '{n}.element', '{n}.message');
		$this->assertTextContains('bake fixture MyCoolRecords -q -p Tools', $flash['flash/info']);
		$this->assertSame('MyCoolRecordsFixture generated.', $flash['flash/success']);
	}

}
