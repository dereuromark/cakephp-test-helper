<?php

namespace TestHelper\Test\TestCase\Controller;

use Shim\TestSuite\IntegrationTestCase;

class TestCasesControllerTest extends IntegrationTestCase {

	/**
	 * @return void
	 */
	public function testController() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'controller', 'app']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testHelper() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'helper', 'app']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testHelperPlugin() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'helper', 'Tools']);

		$this->assertResponseCode(200);
	}

}
