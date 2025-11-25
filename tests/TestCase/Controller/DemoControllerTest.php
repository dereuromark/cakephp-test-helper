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

	/**
	 * @return void
	 */
	public function testFlashMessages(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Demo', 'action' => 'flashMessages']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Flash Messages Demo');
	}

	/**
	 * @return void
	 */
	public function testFlashMessagesPost(): void {
		$this->enableRetainFlashMessages();
		$this->post(['plugin' => 'TestHelper', 'controller' => 'Demo', 'action' => 'flashMessages'], [
			'type' => 'success',
			'message' => 'Test success message',
		]);

		$this->assertRedirect(['plugin' => 'TestHelper', 'controller' => 'Demo', 'action' => 'flashMessages']);
		$this->assertFlashMessage('Test success message');
	}

	/**
	 * @return void
	 */
	public function testTables(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Demo', 'action' => 'tables']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Tables Demo');
	}

	/**
	 * @return void
	 */
	public function testPagination(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Demo', 'action' => 'pagination']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Pagination Demo');
	}

	/**
	 * @return void
	 */
	public function testButtons(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Demo', 'action' => 'buttons']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Buttons');
	}

	/**
	 * @return void
	 */
	public function testTypography(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Demo', 'action' => 'typography']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Typography Demo');
	}

	/**
	 * @return void
	 */
	public function testNavigation(): void {
		$this->get(['plugin' => 'TestHelper', 'controller' => 'Demo', 'action' => 'navigation']);

		$this->assertResponseCode(200);
		$this->assertResponseContains('Navigation Demo');
	}

}
