<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \TestHelper\Controller\DemoController
 */
class DemoControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * @return void
	 */
	public function testIndex(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Demo', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Demo');
	}

	/**
	 * @return void
	 */
	public function testFormElements(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Demo', 'action' => 'formElements']);

		$this->assertResponseCode(200);
	}

	/**
	 * @return void
	 */
	public function testHtml5Elements(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Demo', 'action' => 'html5Elements']);

		$this->assertResponseCode(200);
	}

}
