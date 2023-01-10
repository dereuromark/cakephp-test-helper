<?php

namespace TestHelper\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use TestHelper\View\Helper\TestHelperHelper;

class TestHelperHelperTest extends TestCase {

	protected TestHelperHelper $testHelperHelper;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$view = new View();
		$this->testHelperHelper = new TestHelperHelper($view);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		unset($this->testHelperHelper);

		parent::tearDown();
	}

	/**
	 * @return void
	 */
	public function testPrepareUrl() {
		$array = [
			'plugin' => 'My/Plugin',
			'controller' => 'MyController',
			'prefix' => 'My/NestedPrefix',
			'action' => 'myAction',
		];
		$result = $this->testHelperHelper->url($array);
		$expected = <<<TXT
[
    'prefix' => 'My/NestedPrefix',
    'plugin' => 'My/Plugin',
    'controller' => 'MyController',
    'action' => 'myAction'
]
TXT;
		$this->assertTextEquals($expected, $result);

		$array = [
			'controller' => 'MyController',
			'action' => 'myAction',
		];
		$result = $this->testHelperHelper->url($array, true);
		$expected = <<<TXT
[
    'prefix' => null,
    'plugin' => null,
    'controller' => 'MyController',
    'action' => 'myAction'
]
TXT;
		$this->assertTextEquals($expected, $result);
	}

	/**
	 * @return void
	 */
	public function testUrlPath() {
		$array = [
			'plugin' => 'My/Plugin',
			'controller' => 'MyController',
			'prefix' => 'My/NestedPrefix',
			'action' => 'myAction',
		];
		$result = $this->testHelperHelper->urlPath($array);

		$this->assertSame('My/Plugin.My/NestedPrefix/MyController::myAction', $result);
	}

}
