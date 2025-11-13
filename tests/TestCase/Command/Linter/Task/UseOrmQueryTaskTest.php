<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Command\Linter\Task;

use Cake\TestSuite\TestCase;
use TestHelper\Command\Linter\Task\UseOrmQueryTask;
use TestHelper\Test\TestSuite\ConsoleOutput;

/**
 * @uses \TestHelper\Command\Linter\Task\UseOrmQueryTask
 */
class UseOrmQueryTaskTest extends TestCase {

	protected ConsoleOutput $out;

	protected ConsoleOutput $err;

	protected UseOrmQueryTask $task;

	/**
     * setUp method
     *
     * @return void
     */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$this->task = new UseOrmQueryTask();
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
		$this->assertSame('use-orm-query', $this->task->name());
	}

	/**
     * Test task description
     *
     * @return void
     */
	public function testDescription(): void {
		$description = $this->task->description();
		$this->assertStringContainsString('Query', $description);
		$this->assertStringContainsString('SelectQuery', $description);
	}

	/**
     * Test default paths
     *
     * @return void
     */
	public function testDefaultPaths(): void {
		$paths = $this->task->defaultPaths();
		$this->assertContains('src/', $paths);
		$this->assertContains('tests/', $paths);
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
