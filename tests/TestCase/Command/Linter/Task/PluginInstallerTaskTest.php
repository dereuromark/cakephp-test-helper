<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use TestHelper\Command\Linter\Task\PluginInstallerTask;
use TestHelper\Test\TestSuite\ConsoleOutput;

/**
 * @uses \TestHelper\Command\Linter\Task\PluginInstallerTask
 */
class PluginInstallerTaskTest extends TestCase {

	protected ConsoleOutput $out;

	protected ConsoleOutput $err;

	protected PluginInstallerTask $task;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$this->task = new PluginInstallerTask();
	}

	/**
	 * Test task name
	 *
	 * @return void
	 */
	public function testName(): void {
		$this->assertSame('plugin-installer', $this->task->name());
	}

	/**
	 * Test task description
	 *
	 * @return void
	 */
	public function testDescription(): void {
		$description = $this->task->description();
		$this->assertStringContainsString('cakephp/plugin-installer', $description);
		$this->assertStringContainsString('application only', $description);
	}

	/**
	 * Test default paths
	 *
	 * @return void
	 */
	public function testDefaultPaths(): void {
		$paths = $this->task->defaultPaths();
		$this->assertContains('composer.json', $paths);
	}

	/**
	 * Test does not support plugin mode
	 *
	 * @return void
	 */
	public function testSupportsPluginMode(): void {
		$this->assertFalse($this->task->supportsPluginMode());
	}

	/**
	 * Test does not support auto-fix
	 *
	 * @return void
	 */
	public function testSupportsAutoFix(): void {
		$this->assertFalse($this->task->supportsAutoFix());
	}

	/**
	 * Test running on the current project (a plugin - no plugin-installer needed)
	 *
	 * The actual test helper project is a plugin, so it doesn't have plugin-installer.
	 * This task is designed for applications only, so when supportsPluginMode() returns false,
	 * it should be skipped for plugins.
	 *
	 * @return void
	 */
	public function testRunOnCurrentProject(): void {
		$io = new ConsoleIo($this->out, $this->err);
		$issues = $this->task->run($io, []);

		// Current project is a plugin and doesn't have plugin-installer
		// The task will report 1 issue (missing plugin-installer)
		$this->assertSame(1, $issues);
		$this->assertStringContainsString('cakephp/plugin-installer', $this->out->output());
	}

}
