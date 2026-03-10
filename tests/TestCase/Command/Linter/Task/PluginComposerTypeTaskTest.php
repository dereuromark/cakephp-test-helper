<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use TestHelper\Command\Linter\Task\PluginComposerTypeTask;
use TestHelper\Test\TestSuite\ConsoleOutput;

/**
 * @uses \TestHelper\Command\Linter\Task\PluginComposerTypeTask
 */
class PluginComposerTypeTaskTest extends TestCase {

	protected ConsoleOutput $out;

	protected ConsoleOutput $err;

	protected PluginComposerTypeTask $task;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$this->task = new PluginComposerTypeTask();
	}

	/**
	 * Test task name
	 *
	 * @return void
	 */
	public function testName(): void {
		$this->assertSame('plugin-composer-type', $this->task->name());
	}

	/**
	 * Test task description
	 *
	 * @return void
	 */
	public function testDescription(): void {
		$description = $this->task->description();
		$this->assertStringContainsString('cakephp-plugin', $description);
		$this->assertStringContainsString('composer.json', $description);
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
	 * Test valid composer.json with cakephp-plugin type
	 *
	 * @return void
	 */
	public function testRunWithValidComposerJson(): void {
		$tempDir = sys_get_temp_dir() . '/linter_test_' . uniqid();
		mkdir($tempDir);
		$composerPath = $tempDir . '/composer.json';
		$content = json_encode([
			'name' => 'test/plugin',
			'type' => 'cakephp-plugin',
		], JSON_PRETTY_PRINT);
		file_put_contents($composerPath, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$issues = $this->task->run($io, ['paths' => [$composerPath]]);

		$this->assertSame(0, $issues);

		unlink($composerPath);
		rmdir($tempDir);
	}

	/**
	 * Test composer.json with missing type field
	 *
	 * @return void
	 */
	public function testRunWithMissingType(): void {
		$tempDir = sys_get_temp_dir() . '/linter_test_' . uniqid();
		mkdir($tempDir);
		$composerPath = $tempDir . '/composer.json';
		$content = json_encode([
			'name' => 'test/plugin',
		], JSON_PRETTY_PRINT);
		file_put_contents($composerPath, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$issues = $this->task->run($io, ['paths' => [$composerPath]]);

		$this->assertSame(1, $issues);
		$this->assertStringContainsString('Missing "type" field', $this->out->output());

		unlink($composerPath);
		rmdir($tempDir);
	}

	/**
	 * Test composer.json with wrong type
	 *
	 * @return void
	 */
	public function testRunWithWrongType(): void {
		$tempDir = sys_get_temp_dir() . '/linter_test_' . uniqid();
		mkdir($tempDir);
		$composerPath = $tempDir . '/composer.json';
		$content = json_encode([
			'name' => 'test/plugin',
			'type' => 'library',
		], JSON_PRETTY_PRINT);
		file_put_contents($composerPath, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$issues = $this->task->run($io, ['paths' => [$composerPath]]);

		$this->assertSame(1, $issues);
		$this->assertStringContainsString('must have "type": "cakephp-plugin"', $this->out->output());

		unlink($composerPath);
		rmdir($tempDir);
	}

	/**
	 * Test invalid JSON in composer.json
	 *
	 * @return void
	 */
	public function testRunWithInvalidJson(): void {
		$tempDir = sys_get_temp_dir() . '/linter_test_' . uniqid();
		mkdir($tempDir);
		$composerPath = $tempDir . '/composer.json';
		file_put_contents($composerPath, 'not valid json {');

		$io = new ConsoleIo($this->out, $this->err);
		$issues = $this->task->run($io, ['paths' => [$composerPath]]);

		$this->assertSame(1, $issues);
		$this->assertStringContainsString('not valid JSON', $this->out->output());

		unlink($composerPath);
		rmdir($tempDir);
	}

	/**
	 * Test with non-existent file
	 *
	 * @return void
	 */
	public function testRunWithNonExistentFile(): void {
		$io = new ConsoleIo($this->out, $this->err);
		$issues = $this->task->run($io, ['paths' => ['/nonexistent/composer.json']]);

		$this->assertSame(0, $issues);
	}

}
