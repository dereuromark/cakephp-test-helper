<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class PluginsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Plugins', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testRecommended() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Plugins', 'action' => 'recommended', '?' => ['plugin' => 'Tools']]);

		$this->assertResponseCode(200);
	}

}
