<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use ReflectionClass;
use TestHelper\Command\Linter\Task\ArrayUrlsInTestsTask;
use TestHelper\Test\TestSuite\ConsoleOutput;

/**
 * @uses \TestHelper\Command\Linter\Task\ArrayUrlsInTestsTask
 */
class ArrayUrlsInTestsTaskTest extends TestCase {

	protected ConsoleOutput $out;

	protected ConsoleOutput $err;

	protected ArrayUrlsInTestsTask $task;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$this->task = new ArrayUrlsInTestsTask();
	}

	/**
	 * Test task name
	 *
	 * @return void
	 */
	public function testName(): void {
		$this->assertSame('array-urls-in-tests', $this->task->name());
	}

	/**
	 * Test task description
	 *
	 * @return void
	 */
	public function testDescription(): void {
		$description = $this->task->description();
		$this->assertStringContainsString('get()', $description);
		$this->assertStringContainsString('post()', $description);
		$this->assertStringContainsString('assertRedirect()', $description);
	}

	/**
	 * Test default paths
	 *
	 * @return void
	 */
	public function testDefaultPaths(): void {
		$paths = $this->task->defaultPaths();
		$this->assertContains('tests/TestCase/Controller/', $paths);
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
	 * Test autofix converts string URL to array in get()
	 *
	 * @return void
	 */
	public function testAutofixConvertsGetUrl(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'linter_test_');
		$content = <<<'PHP'
<?php
class SomeTest extends TestCase {
    public function testSomething(): void {
        $this->get('/suppliers/view/123');
    }
}
PHP;
		file_put_contents($tempFile, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$reflection = new ReflectionClass($this->task);
		$method = $reflection->getMethod('checkFile');

		// Run with fix enabled
		$method->invoke($this->task, $io, $tempFile, false, true);

		$fixed = file_get_contents($tempFile);

		// Test the complete method with proper whitespace preservation
		$expected = <<<'PHP'
<?php
class SomeTest extends TestCase {
    public function testSomething(): void {
        $this->get(['controller' => 'Suppliers', 'action' => 'view', 123]);
    }
}
PHP;
		$this->assertSame($expected, $fixed);

		unlink($tempFile);
	}

	/**
	 * Test autofix converts string URL to array in post()
	 *
	 * @return void
	 */
	public function testAutofixConvertsPostUrl(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'linter_test_');
		$content = <<<'PHP'
<?php
class SomeTest extends TestCase {
    public function testSomething(): void {
        $this->post('/users/login');
    }
}
PHP;
		file_put_contents($tempFile, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$reflection = new ReflectionClass($this->task);
		$method = $reflection->getMethod('checkFile');

		// Run with fix enabled
		$method->invoke($this->task, $io, $tempFile, false, true);

		$fixed = file_get_contents($tempFile);

		// Test the complete method with proper whitespace preservation
		$expected = <<<'PHP'
<?php
class SomeTest extends TestCase {
    public function testSomething(): void {
        $this->post(['controller' => 'Users', 'action' => 'login']);
    }
}
PHP;
		$this->assertSame($expected, $fixed);

		unlink($tempFile);
	}

	/**
	 * Test autofix converts string URL to array in assertRedirect()
	 *
	 * @return void
	 */
	public function testAutofixConvertsAssertRedirectUrl(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'linter_test_');
		$content = <<<'PHP'
<?php
class SomeTest extends TestCase {
    public function testSomething(): void {
        $this->assertRedirect('/dashboard/index');
    }
}
PHP;
		file_put_contents($tempFile, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$reflection = new ReflectionClass($this->task);
		$method = $reflection->getMethod('checkFile');

		// Run with fix enabled
		$method->invoke($this->task, $io, $tempFile, false, true);

		$fixed = file_get_contents($tempFile);

		// Test the complete method with proper whitespace preservation
		$expected = <<<'PHP'
<?php
class SomeTest extends TestCase {
    public function testSomething(): void {
        $this->assertRedirect(['controller' => 'Dashboard', 'action' => 'index']);
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
class SomeTest extends TestCase {
    public function testSomething(): void {
        $this->get('/articles/view/' . $id);
        $this->post('/users/' . $id . '/edit');
        $this->assertRedirect('/products/single/' . $product->uuid);
        $this->assertRedirect('/products/single?product_id=' . $product->id);
    }
}
PHP;
		file_put_contents($tempFile, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$reflection = new ReflectionClass($this->task);
		$method = $reflection->getMethod('checkFile');

		// Run with fix enabled
		$method->invoke($this->task, $io, $tempFile, false, true);

		$fixed = file_get_contents($tempFile);

		// Content should be unchanged - concatenated URLs are too complex to auto-fix
		$this->assertSame($content, $fixed);

		unlink($tempFile);
	}

}
