<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Command\Linter\Task;

use Cake\TestSuite\TestCase;
use TestHelper\Command\Linter\Task\DuplicateTemplateAnnotationsTask;
use TestHelper\Test\TestSuite\ConsoleOutput;

/**
 * @link \TestHelper\Command\Linter\Task\DuplicateTemplateAnnotationsTask
 */
class DuplicateTemplateAnnotationsTaskTest extends TestCase {

	protected ConsoleOutput $out;

	protected ConsoleOutput $err;

	protected DuplicateTemplateAnnotationsTask $task;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$this->task = new DuplicateTemplateAnnotationsTask();
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		unset($this->task);
		parent::tearDown();
	}

	/**
	 * Test task name
	 *
	 * @return void
	 */
	public function testName(): void {
		$this->assertSame('duplicate-template-annotations', $this->task->name());
	}

	/**
	 * Test task description
	 *
	 * @return void
	 */
	public function testDescription(): void {
		$description = $this->task->description();
		$this->assertStringContainsString('duplicate', $description);
		$this->assertStringContainsString('annotation', $description);
		$this->assertStringContainsString('template', $description);
	}

	/**
	 * Test default paths
	 *
	 * @return void
	 */
	public function testDefaultPaths(): void {
		$paths = $this->task->defaultPaths();
		$this->assertContains('templates/', $paths);
	}

	/**
	 * Test does not support auto-fix
	 *
	 * @return void
	 */
	public function testSupportsAutoFix(): void {
		$this->assertFalse($this->task->supportsAutoFix());
	}

}
