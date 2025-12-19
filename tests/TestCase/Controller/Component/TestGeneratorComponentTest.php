<?php

namespace TestHelper\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use TestHelper\Controller\Component\TestGeneratorComponent;

class TestGeneratorComponentTest extends TestCase {

	protected TestGeneratorComponent $component;

	protected string $tempDir;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->component = new TestGeneratorComponent(new ComponentRegistry(new Controller(new ServerRequest())));
		$this->tempDir = TMP . 'test_generator_test' . DS;
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();

		// Clean up temp directory
		$this->removeDirectory($this->tempDir);

		Configure::delete('TestHelper.php');
	}

	/**
	 * @return void
	 */
	public function testGetFilesEmpty(): void {
		$result = $this->component->getFiles(['/non/existent/path']);

		$this->assertSame([], $result);
	}

	/**
	 * @return void
	 */
	public function testGetFilesWithFiles(): void {
		$this->createTempStructure([
			'FooController.php',
			'BarController.php',
		]);

		$result = $this->component->getFiles([$this->tempDir]);

		sort($result);
		$this->assertSame(['BarController', 'FooController'], $result);
	}

	/**
	 * @return void
	 */
	public function testGetFilesWithSubdirectories(): void {
		$this->createTempStructure([
			'FooController.php',
			'Admin/BarController.php',
			'Admin/BazController.php',
		]);

		$result = $this->component->getFiles([$this->tempDir]);

		sort($result);
		$this->assertSame(['Admin/BarController', 'Admin/BazController', 'FooController'], $result);
	}

	/**
	 * @return void
	 */
	public function testGetFilesIgnoresNonPhp(): void {
		$this->createTempStructure([
			'FooController.php',
			'README.md',
			'.gitkeep',
		]);

		$result = $this->component->getFiles([$this->tempDir]);

		$this->assertSame(['FooController'], $result);
	}

	/**
	 * @return void
	 */
	public function testGetFilesMultipleFolders(): void {
		$secondDir = TMP . 'test_generator_test2' . DS;
		$this->createTempStructure(['FooController.php']);
		$this->createTempStructure(['BarController.php'], $secondDir);

		$result = $this->component->getFiles([$this->tempDir, $secondDir]);

		sort($result);
		$this->assertSame(['BarController', 'FooController'], $result);

		$this->removeDirectory($secondDir);
	}

	/**
	 * @return void
	 */
	public function testGetPhpBinaryDefault(): void {
		Configure::delete('TestHelper.php');

		$result = $this->invokeMethod($this->component, 'getPhpBinary');

		$this->assertSame('php', $result);
	}

	/**
	 * @return void
	 */
	public function testGetPhpBinaryConfigured(): void {
		Configure::write('TestHelper.php', '/usr/bin/php8.1');

		$result = $this->invokeMethod($this->component, 'getPhpBinary');

		$this->assertSame('/usr/bin/php8.1', $result);
	}

	/**
	 * Create temp directory structure with files.
	 *
	 * @param array<string> $files
	 * @param string|null $baseDir
	 * @return void
	 */
	protected function createTempStructure(array $files, ?string $baseDir = null): void {
		$baseDir = $baseDir ?? $this->tempDir;

		if (!is_dir($baseDir)) {
			mkdir($baseDir, 0777, true);
		}

		foreach ($files as $file) {
			$filePath = $baseDir . $file;
			$dir = dirname($filePath);
			if (!is_dir($dir)) {
				mkdir($dir, 0777, true);
			}
			file_put_contents($filePath, '<?php // test file');
		}
	}

	/**
	 * Remove directory recursively.
	 *
	 * @param string $dir
	 * @return void
	 */
	protected function removeDirectory(string $dir): void {
		if (!is_dir($dir)) {
			return;
		}

		$files = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::CHILD_FIRST,
		);

		foreach ($files as $file) {
			if ($file->isDir()) {
				rmdir($file->getRealPath());
			} else {
				unlink($file->getRealPath());
			}
		}

		rmdir($dir);
	}

	/**
	 * Helper to invoke protected/private methods.
	 *
	 * @param object $object
	 * @param string $methodName
	 * @param array $parameters
	 * @return mixed
	 */
	protected function invokeMethod(object $object, string $methodName, array $parameters = []): mixed {
		$reflection = new ReflectionClass($object::class);
		$method = $reflection->getMethod($methodName);

		return $method->invokeArgs($object, $parameters);
	}

}
