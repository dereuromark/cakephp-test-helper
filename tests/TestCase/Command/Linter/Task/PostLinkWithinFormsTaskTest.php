<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use Cake\TestSuite\TestCase;
use TestHelper\Command\Linter\Task\PostLinkWithinFormsTask;
use TestHelper\Test\TestSuite\ConsoleOutput;

/**
 * @uses \TestHelper\Command\Linter\Task\PostLinkWithinFormsTask
 */
class PostLinkWithinFormsTaskTest extends TestCase {

	protected ConsoleOutput $out;

	protected ConsoleOutput $err;

	protected PostLinkWithinFormsTask $task;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		$this->out = new ConsoleOutput();
		$this->err = new ConsoleOutput();
		$this->task = new PostLinkWithinFormsTask();
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
		$this->assertSame('post-link-within-forms', $this->task->name());
	}

	/**
	 * Test task description
	 *
	 * @return void
	 */
	public function testDescription(): void {
		$description = $this->task->description();
		$this->assertStringContainsString('postLink()', $description);
		$this->assertStringContainsString('block', $description);
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
	 * Test supports auto-fix
	 *
	 * @return void
	 */
	public function testSupportsAutoFix(): void {
		$this->assertTrue($this->task->supportsAutoFix());
	}

	/**
	 * Test autofix adds block => true to postLink without third parameter
	 *
	 * @return void
	 */
	public function testAutofixAddsBlockParameter(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'linter_test_');
		$content = <<<'PHP'
<?= $this->Form->create($entity) ?>
<?= $this->Form->postLink('Delete', ['action' => 'delete', $entity->id]) ?>
<?= $this->Form->end() ?>
PHP;
		file_put_contents($tempFile, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$reflection = new \ReflectionClass($this->task);
		$method = $reflection->getMethod('checkFile');
		$method->setAccessible(true);

		// Run with fix enabled
		$method->invoke($this->task, $io, $tempFile, false, true);

		$fixed = file_get_contents($tempFile);
		$this->assertStringContainsString("['block' => true]", $fixed);

		unlink($tempFile);
	}

	/**
	 * Test autofix adds block => true to postLink with existing third parameter
	 *
	 * @return void
	 */
	public function testAutofixAddsBlockToExistingOptions(): void {
		$tempFile = tempnam(sys_get_temp_dir(), 'linter_test_');
		$content = <<<'PHP'
<?= $this->Form->create($entity) ?>
<?= $this->Form->postLink('Delete', ['action' => 'delete', $entity->id], ['confirm' => 'Are you sure?']) ?>
<?= $this->Form->end() ?>
PHP;
		file_put_contents($tempFile, $content);

		$io = new ConsoleIo($this->out, $this->err);
		$reflection = new \ReflectionClass($this->task);
		$method = $reflection->getMethod('checkFile');
		$method->setAccessible(true);

		// Run with fix enabled
		$method->invoke($this->task, $io, $tempFile, false, true);

		$fixed = file_get_contents($tempFile);
		$this->assertStringContainsString("'block' => true,", $fixed);
		$this->assertStringContainsString("'confirm' => 'Are you sure?'", $fixed);

		unlink($tempFile);
	}

}
