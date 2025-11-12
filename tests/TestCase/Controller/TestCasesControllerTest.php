<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \TestHelper\Controller\TestCasesController
 */
class TestCasesControllerTest extends TestCase {

	use IntegrationTestTrait;

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

	/**
	 * @return void
	 */
	public function testCommand(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'command', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testTable(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'table', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testEntity(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'entity', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testBehavior(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'behavior', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testComponent(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'component', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testTask(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'TestCases', 'action' => 'task', '?' => ['namespace' => 'app']]);

		$this->assertResponseCode(200);
	}

}
