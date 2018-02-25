<?php
namespace TestHelper\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

class TestCasesControllerTest extends IntegrationTestCase {

	/**
	 * @return void
	 */
	public function testControllers() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'controllers', 'app']);

		$this->assertResponseCode(200);
	}

}
