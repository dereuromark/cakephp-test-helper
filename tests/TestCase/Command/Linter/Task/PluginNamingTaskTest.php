<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use TestHelper\Command\Linter\Task\PluginNamingTask;
use TestHelper\Test\TestSuite\ConsoleOutput;

/**
 * @uses \TestHelper\Command\Linter\Task\PluginNamingTask
 */
class PluginNamingTaskTest extends TestCase {

	protected ConsoleOutput $out;

	protected ConsoleOutput $err;

	protected PluginNamingTask $task;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$this->task = new PluginNamingTask();
	}

	/**
	 * Test task name
	 *
	 * @return void
	 */
	public function testName(): void {
		$this->assertSame('plugin-naming', $this->task->name());
	}

	/**
	 * Test task description
	 *
	 * @return void
	 */
	public function testDescription(): void {
		$description = $this->task->description();
		$this->assertStringContainsString('Plugin class', $description);
		$this->assertStringContainsString('PluginName', $description);
	}

	/**
	 * Test default paths
	 *
	 * @return void
	 */
	public function testDefaultPaths(): void {
		$paths = $this->task->defaultPaths();
		$this->assertContains('src/', $paths);
	}

	/**
	 * Test requires plugin mode
	 *
	 * @return void
	 */
	public function testRequiresPluginMode(): void {
		$this->assertTrue($this->task->requiresPluginMode());
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
	 * Test properly named plugin class
	 *
	 * @return void
	 */
	public function testRunWithProperlyNamedPlugin(): void {
		$tempDir = sys_get_temp_dir() . '/linter_test_' . uniqid() . '/src';
		mkdir($tempDir, 0777, true);
		$pluginFile = $tempDir . '/MyAwesomePlugin.php';
		$content = <<<'PHP'
<?php

namespace MyAwesome;

use Cake\Core\BasePlugin;

class MyAwesomePlugin extends BasePlugin {
}
PHP;
		file_put_contents($pluginFile, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$issues = $this->task->run($io, ['paths' => [$tempDir]]);

		$this->assertSame(0, $issues);

		unlink($pluginFile);
		rmdir($tempDir);
		rmdir(dirname($tempDir));
	}

	/**
	 * Test incorrectly named plugin class (just "Plugin")
	 *
	 * @return void
	 */
	public function testRunWithIncorrectlyNamedPlugin(): void {
		$tempDir = sys_get_temp_dir() . '/linter_test_' . uniqid() . '/src';
		mkdir($tempDir, 0777, true);
		$pluginFile = $tempDir . '/Plugin.php';
		$content = <<<'PHP'
<?php

namespace MyAwesome;

use Cake\Core\BasePlugin;

class Plugin extends BasePlugin {
}
PHP;
		file_put_contents($pluginFile, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$issues = $this->task->run($io, ['paths' => [$tempDir]]);

		$this->assertSame(1, $issues);
		$this->assertStringContainsString("'MyAwesomePlugin'", $this->out->output());
		$this->assertStringContainsString("not 'Plugin'", $this->out->output());

		unlink($pluginFile);
		rmdir($tempDir);
		rmdir(dirname($tempDir));
	}

	/**
	 * Test with no Plugin.php file
	 *
	 * @return void
	 */
	public function testRunWithNoPluginFile(): void {
		$tempDir = sys_get_temp_dir() . '/linter_test_' . uniqid() . '/src';
		mkdir($tempDir, 0777, true);

		$io = new ConsoleIo($this->out, $this->err);
		$issues = $this->task->run($io, ['paths' => [$tempDir]]);

		$this->assertSame(0, $issues);

		rmdir($tempDir);
		rmdir(dirname($tempDir));
	}

	/**
	 * Test with non-existent directory
	 *
	 * @return void
	 */
	public function testRunWithNonExistentDirectory(): void {
		$io = new ConsoleIo($this->out, $this->err);
		$issues = $this->task->run($io, ['paths' => ['/nonexistent/src']]);

		$this->assertSame(0, $issues);
	}

}
