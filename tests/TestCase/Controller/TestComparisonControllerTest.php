<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;
use Cake\Utility\Hash;
use TestApp\TestSuite\TestSession;

/**
 * @uses \TestHelper\Controller\TestComparisonController
 */
class TestComparisonControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestComparison', 'action' => 'index']);

		$this->assertResponseCode(200);
	}

}
