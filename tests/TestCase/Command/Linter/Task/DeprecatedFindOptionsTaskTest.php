<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use TestHelper\Command\Linter\Task\DeprecatedFindOptionsTask;
use TestHelper\Test\TestSuite\ConsoleOutput;

/**
 * @uses \TestHelper\Command\Linter\Task\DeprecatedFindOptionsTask
 */
class DeprecatedFindOptionsTaskTest extends TestCase {

	protected ConsoleOutput $out;

	protected ConsoleOutput $err;

	protected DeprecatedFindOptionsTask $task;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$this->task = new DeprecatedFindOptionsTask();
	}

	/**
	 * Test task name
	 *
	 * @return void
	 */
	public function testName(): void {
		$this->assertSame('deprecated-find-options', $this->task->name());
	}

	/**
	 * Test task description
	 *
	 * @return void
	 */
	public function testDescription(): void {
		$description = $this->task->description();
		$this->assertStringContainsString('find()', $description);
		$this->assertStringContainsString('deprecated', $description);
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
	 * Test supports auto-fix
	 *
	 * @return void
	 */
	public function testSupportsAutoFix(): void {
		$this->assertTrue($this->task->supportsAutoFix());
	}

	/**
	 * Test autofix adds spread operator to find() with variable
	 *
	 * @return void
	 */
	public function testAutofixAddsSpreadOperatorWithVariable(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'linter_test_');
		$content = <<<'PHP'
<?php
class SomeTable extends Table {
    public function findSomething($query, $options) {
        return $query->find('all', $options);
    }
}
PHP;
		file_put_contents($tempFile, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$reflection = new \ReflectionClass($this->task);
		$method = $reflection->getMethod('checkFile');

		// Run with fix enabled
		$method->invoke($this->task, $io, $tempFile, false, true);

		$fixed = file_get_contents($tempFile);
		$this->assertStringContainsString("->find('all', ...\$options)", $fixed);
		$this->assertStringNotContainsString("->find('all', \$options)", $fixed);

		unlink($tempFile);
	}

	/**
	 * Test autofix adds spread operator to find() with array
	 *
	 * @return void
	 */
	public function testAutofixAddsSpreadOperatorWithArray(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'linter_test_');
		$content = <<<'PHP'
<?php
class SomeTable extends Table {
    public function findActive($query) {
        return $query->find('list', ['conditions' => ['active' => true]]);
    }
}
PHP;
		file_put_contents($tempFile, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$reflection = new \ReflectionClass($this->task);
		$method = $reflection->getMethod('checkFile');

		// Run with fix enabled
		$method->invoke($this->task, $io, $tempFile, false, true);

		$fixed = file_get_contents($tempFile);
		$this->assertStringContainsString("->find('list', ...['conditions' => ['active' => true]])", $fixed);

		unlink($tempFile);
	}

	/**
	 * Test autofix handles different find types
	 *
	 * @return void
	 */
	public function testAutofixHandlesDifferentFindTypes(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'linter_test_');
		$content = <<<'PHP'
<?php
class SomeTable extends Table {
    public function test() {
        $a = $this->find('all', $options);
        $b = $this->find('list', $options);
        $c = $this->find('threaded', $options);
        $d = $this->find('first', $options);
    }
}
PHP;
		file_put_contents($tempFile, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$reflection = new \ReflectionClass($this->task);
		$method = $reflection->getMethod('checkFile');

		// Run with fix enabled
		$method->invoke($this->task, $io, $tempFile, false, true);

		$fixed = file_get_contents($tempFile);
		$this->assertStringContainsString("->find('all', ...\$options)", $fixed);
		$this->assertStringContainsString("->find('list', ...\$options)", $fixed);
		$this->assertStringContainsString("->find('threaded', ...\$options)", $fixed);
		$this->assertStringContainsString("->find('first', ...\$options)", $fixed);

		unlink($tempFile);
	}

}
