<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;
use TestHelper\Command\Linter\Task\NoMixedInTemplatesTask;
use TestHelper\Command\Linter\Task\UseOrmQueryTask;
use TestHelper\Command\LinterCommand;
use TestHelper\Test\TestSuite\ConsoleOutput;

/**
 * @uses \TestHelper\Command\LinterCommand
 */
class LinterCommandTest extends TestCase {

	protected ConsoleOutput $out;

	protected ConsoleOutput $err;

	protected LinterCommand $command;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$this->command = new LinterCommand();
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		unset($this->command);
		Configure::delete('TestHelper.Linter');
		parent::tearDown();
	}

	/**
	 * Test default tasks are loaded
	 *
	 * @return void
	 */
	public function testDefaultTasks(): void {
		$io = new ConsoleIo($this->out, $this->err);
		$args = new Arguments([], ['list' => true], []);

		$this->command->execute($args, $io);

		$output = $this->out->output();
		$this->assertStringContainsString('Available linter tasks:', $output);
		$this->assertStringContainsString('no-mixed-in-templates', $output);
		$this->assertStringContainsString('use-orm-query', $output);
		$this->assertStringContainsString('use-base-migration', $output);
		$this->assertStringContainsString('single-request-per-test', $output);
	}

	/**
	 * Test disabling default tasks
	 *
	 * @return void
	 */
	public function testDisableDefaultTask(): void {
		Configure::write('TestHelper.Linter.tasks', [
			NoMixedInTemplatesTask::class => false,
		]);

		$io = new ConsoleIo($this->out, $this->err);
		$args = new Arguments([], ['list' => true], []);

		$this->command->execute($args, $io);

		$output = $this->out->output();
		$this->assertStringContainsString('use-orm-query', $output);
		$this->assertStringNotContainsString('no-mixed-in-templates', $output);
	}

	/**
	 * Test adding custom tasks via simple list
	 *
	 * @return void
	 */
	public function testAddCustomTasks(): void {
		// Simulate adding custom task via simple list
		Configure::write('TestHelper.Linter.tasks', [
			UseOrmQueryTask::class, // Re-add existing one using simple list format
		]);

		$io = new ConsoleIo($this->out, $this->err);
		$args = new Arguments([], ['list' => true], []);

		$this->command->execute($args, $io);

		$output = $this->out->output();
		// All default tasks should still be present
		$this->assertStringContainsString('use-orm-query', $output);
		$this->assertStringContainsString('no-mixed-in-templates', $output);
	}

}
