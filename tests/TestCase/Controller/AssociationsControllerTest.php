<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \TestHelper\Controller\AssociationsController
 */
class AssociationsControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testIndex() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Associations', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Association');
	}

	/**
	 * @return void
	 */
	public function testIndexIncludeVendor() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Associations', 'action' => 'index', '?' => ['vendor' => 1]]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testScan() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Associations', 'action' => 'scan']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testViewWithoutModel() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'Associations', 'action' => 'view']);

		$this->assertResponseCode(200);
	}

}
