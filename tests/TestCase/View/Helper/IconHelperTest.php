<?php

namespace TestHelper\Test\TestCase\View\Helper;

use Cake\TestSuite\TestCase;
use Cake\View\View;
use TestHelper\View\Helper\IconHelper;

/**
 * @covers \TestHelper\View\Helper\IconHelper
 */
class IconHelperTest extends TestCase {

	/**
	 * @var \TestHelper\View\Helper\IconHelper
	 */
	protected IconHelper $Icon;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$view = new View();
		$this->Icon = new IconHelper($view);
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		unset($this->Icon);

		parent::tearDown();
	}

	/**
	 * Test render method with basic icon
	 *
	 * @return void
	 */
	public function testRenderBasic(): void {
		$result = $this->Icon->render('plus');

		$this->assertStringContainsString('<i', $result);
		$this->assertStringContainsString('fas', $result);
		$this->assertStringContainsString('fa-plus', $result);
		$this->assertStringContainsString('</i>', $result);
	}

	/**
	 * Test render method with attributes
	 *
	 * @return void
	 */
	public function testRenderWithAttributes(): void {
		$result = $this->Icon->render('warning', ['title' => 'Warning!']);

		$this->assertStringContainsString('fa-triangle-exclamation', $result);
		$this->assertStringContainsString('title="Warning!"', $result);
	}

	/**
	 * Test render method with custom class
	 *
	 * @return void
	 */
	public function testRenderWithClass(): void {
		$result = $this->Icon->render('check', ['class' => 'text-success']);

		$this->assertStringContainsString('fas fa-check text-success', $result);
	}

	/**
	 * Test render method with unmapped icon
	 *
	 * @return void
	 */
	public function testRenderUnmappedIcon(): void {
		$result = $this->Icon->render('custom-icon');

		$this->assertStringContainsString('fa-custom-icon', $result);
	}

}
