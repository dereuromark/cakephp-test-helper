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

	/**
	 * Test yesNo method with badge display
	 *
	 * @return void
	 */
	public function testYesNoBadge(): void {
		$result = $this->testHelperHelper->yesNo(true);
		$this->assertStringContainsString('badge bg-success', $result);
		$this->assertStringContainsString('Yes', $result);

		$result = $this->testHelperHelper->yesNo(false);
		$this->assertStringContainsString('badge bg-danger', $result);
		$this->assertStringContainsString('No', $result);
	}

	/**
	 * Test yesNo method with icon display
	 *
	 * @return void
	 */
	public function testYesNoIcon(): void {
		$result = $this->testHelperHelper->yesNo(true, ['icon' => true]);
		$this->assertStringContainsString('fas fa-check text-success', $result);
		$this->assertStringContainsString('title="Yes"', $result);

		$result = $this->testHelperHelper->yesNo(false, ['icon' => true]);
		$this->assertStringContainsString('fas fa-times text-danger', $result);
		$this->assertStringContainsString('title="No"', $result);
	}

}
