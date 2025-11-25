<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\Http\Response;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TestHelper\Controller\TestHelperAppController;

/**
 * @uses \TestHelper\Controller\TestHelperAppController
 */
class TestHelperAppControllerTest extends TestCase {

	/**
	 * Test subject
	 *
	 * @var \TestHelper\Controller\TestHelperAppController
	 */
	protected $TestHelperAppController;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$request = new ServerRequest();
		$response = new Response();
		$this->TestHelperAppController = new TestHelperAppController($request, $response);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset($this->TestHelperAppController);

		parent::tearDown();
	}

	/**
	 * Test initialize method
	 *
	 * @return void
	 */
	public function testInitialize(): void {
		$this->TestHelperAppController->initialize();

		// Test that Flash component is loaded
		$this->assertTrue($this->TestHelperAppController->components()->has('Flash'));

		// Test that the layout is set to TestHelper.test_helper
		$layout = $this->TestHelperAppController->viewBuilder()->getLayout();
		$this->assertSame('TestHelper.test_helper', $layout);
	}

}
