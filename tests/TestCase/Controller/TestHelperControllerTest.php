<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestCase;

/**
 * @uses \TestHelper\Controller\TestHelperController
 */
class TestHelperControllerTest extends IntegrationTestCase {

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestHelper', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

}
