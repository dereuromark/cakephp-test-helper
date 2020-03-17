<?php

namespace TestHelper\Test\TestCase\Controller;

use Shim\TestSuite\IntegrationTestCase;

class PluginsControllerTest extends IntegrationTestCase {

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Plugins', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testRecommended() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Plugins', 'action' => 'recommended', '?' => ['plugin' => 'Tools']]);

		$this->assertResponseCode(200);
	}

}
