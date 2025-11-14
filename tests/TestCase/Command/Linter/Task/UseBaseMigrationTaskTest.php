<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Command\Linter\Task;

use Cake\TestSuite\TestCase;
use TestHelper\Command\Linter\Task\UseBaseMigrationTask;
use TestHelper\Test\TestSuite\ConsoleOutput;

/**
 * @uses \TestHelper\Command\Linter\Task\UseBaseMigrationTask
 */
class UseBaseMigrationTaskTest extends TestCase {

	protected ConsoleOutput $out;

	protected ConsoleOutput $err;

	protected UseBaseMigrationTask $task;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$this->task = new UseBaseMigrationTask();
	}

	/**
	 * Test task name
	 *
	 * @return void
	 */
	public function testName(): void {
		$this->assertSame('use-base-migration', $this->task->name());
	}

	/**
	 * Test task description
	 *
	 * @return void
	 */
	public function testDescription(): void {
		$description = $this->task->description();
		$this->assertStringContainsString('BaseMigration', $description);
		$this->assertStringContainsString('AbstractMigration', $description);
	}

	/**
	 * Test default paths
	 *
	 * @return void
	 */
	public function testDefaultPaths(): void {
		$paths = $this->task->defaultPaths();
		$this->assertContains('config/Migrations/', $paths);
		$this->assertContains('config/Seeds/', $paths);
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
