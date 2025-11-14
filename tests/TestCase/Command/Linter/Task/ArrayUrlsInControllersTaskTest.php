<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use TestHelper\Command\Linter\Task\ArrayUrlsInControllersTask;
use TestHelper\Test\TestSuite\ConsoleOutput;

/**
 * @link \TestHelper\Command\Linter\Task\ArrayUrlsInControllersTask
 */
class ArrayUrlsInControllersTaskTest extends TestCase {

	protected ConsoleOutput $out;

	protected ConsoleOutput $err;

	protected ArrayUrlsInControllersTask $task;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$this->task = new ArrayUrlsInControllersTask();
	}

	/**
	 * tearDown method
	 *
	 * @return void
	 */
	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * Test task name
	 *
	 * @return void
	 */
	public function testName(): void {
		$this->assertSame('array-urls-in-controllers', $this->task->name());
	}

	/**
	 * Test task description
	 *
	 * @return void
	 */
	public function testDescription(): void {
		$description = $this->task->description();
		$this->assertStringContainsString('redirect()', $description);
	}

	/**
	 * Test default paths
	 *
	 * @return void
	 */
	public function testDefaultPaths(): void {
		$paths = $this->task->defaultPaths();
		$this->assertContains('src/Controller/', $paths);
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
	 * Test autofix converts string URL to array in redirect()
	 *
	 * @return void
	 */
	public function testAutofixConvertsRedirectUrl(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'linter_test_');
		$content = <<<'PHP'
<?php
class SomeController extends Controller {
    public function someAction() {
        return $this->redirect('/dashboard/index');
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

		// Test the complete method with proper whitespace preservation
		$expected = <<<'PHP'
<?php
class SomeController extends Controller {
    public function someAction() {
        return $this->redirect(['controller' => 'Dashboard', 'action' => 'index']);
    }
}
PHP;
		$this->assertSame($expected, $fixed);

		unlink($tempFile);
	}

	/**
	 * Test autofix converts redirect with query string
	 *
	 * @return void
	 */
	public function testAutofixConvertsRedirectUrlWithQueryString(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'linter_test_');
		$content = <<<'PHP'
<?php
class SomeController extends Controller {
    public function someAction() {
        return $this->redirect('/articles/index?status=published');
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

		// Test the complete method with proper whitespace preservation
		$expected = <<<'PHP'
<?php
class SomeController extends Controller {
    public function someAction() {
        return $this->redirect(['controller' => 'Articles', 'action' => 'index', '?' => ['status' => 'published']]);
    }
}
PHP;
		$this->assertSame($expected, $fixed);

		unlink($tempFile);
	}

	/**
	 * Test autofix skips concatenated URLs
	 *
	 * @return void
	 */
	public function testAutofixSkipsConcatenatedUrls(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'linter_test_');
		$content = <<<'PHP'
<?php
class SomeController extends Controller {
    public function someAction() {
        return $this->redirect('/users/view/' . $id);
        return $this->redirect('/path?id=' . $user->id);
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

		// Content should be unchanged - concatenated URLs are too complex to auto-fix
		$this->assertSame($content, $fixed);

		unlink($tempFile);
	}

	/**
	 * Test autofix handles redirect without return
	 *
	 * @return void
	 */
	public function testAutofixHandlesRedirectWithoutReturn(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'linter_test_');
		$content = <<<'PHP'
<?php
class SomeController extends Controller {
    public function someAction() {
        if ($condition) {
            $this->redirect('/users/index');
        }
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

		// Test the complete method with proper whitespace preservation
		$expected = <<<'PHP'
<?php
class SomeController extends Controller {
    public function someAction() {
        if ($condition) {
            $this->redirect(['controller' => 'Users', 'action' => 'index']);
        }
    }
}
PHP;
		$this->assertSame($expected, $fixed);

		unlink($tempFile);
	}

}
