<?php

namespace TestHelper\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use ReflectionClass;
use TestHelper\Controller\Component\TestRunnerComponent;

class TestRunnerComponentTest extends TestCase {

	protected TestRunnerComponent $component;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->component = new TestRunnerComponent(new ComponentRegistry(new Controller(new ServerRequest())));
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		parent::tearDown();

		Configure::delete('TestHelper.php');
		Configure::delete('TestHelper.command');
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
		Configure::write('TestHelper.php', '/usr/bin/php8.2');

		$result = $this->invokeMethod($this->component, 'getPhpBinary');

		$this->assertSame('/usr/bin/php8.2', $result);
	}

	/**
	 * @return void
	 */
	public function testGetCommandDefault(): void {
		Configure::delete('TestHelper.command');

		$result = $this->invokeMethod($this->component, 'getCommand');

		$this->assertSame(escapeshellarg('php') . ' vendor/bin/phpunit', $result);
	}

	/**
	 * @return void
	 */
	public function testGetCommandConfigured(): void {
		Configure::write('TestHelper.command', 'custom/phpunit');

		$result = $this->invokeMethod($this->component, 'getCommand');

		$this->assertSame('custom/phpunit', $result);
	}

	public function testRunPreservesArgumentsOutputAndWorkingDirectory(): void {
		$file = 'tests/runner ; literal.php';
		$script = tempnam(TMP, 'test-helper-runner-');
		file_put_contents(ROOT . DS . $file, '<?php');
		file_put_contents($script, '<?php echo "<script>output</script>\\n"; fwrite(STDERR, "diagnostic\\n"); echo json_encode(array_slice($argv, 1));');
		Configure::write('TestHelper.command', escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script));
		$directory = getcwd();
		try {
			chdir(TMP);
			$result = $this->component->run($file, 'testName with spaces');
			$this->assertSame(0, $result['code']);
			$this->assertSame(realpath(TMP), getcwd());
			$this->assertContains('diagnostic', $result['content']);
			$this->assertStringContainsString('&lt;script&gt;', $result['output']);
			$this->assertSame([$file, '--filter', 'testName with spaces'], json_decode(end($result['content']), true));
		} finally {
			if ($directory !== false) {
				chdir($directory);
			}
			unlink(ROOT . DS . $file);
			unlink($script);
		}
	}

	public function testCoverageUsesSupportedPhpunitOptions(): void {
		$component = $this->getMockBuilder(TestRunnerComponent::class)
			->setConstructorArgs([new ComponentRegistry(new Controller(new ServerRequest()))])
			->onlyMethods(['executeCommand'])->getMock();
		$component->expects($this->once())->method('executeCommand')
			->with($this->callback(function (string $command): bool {
				$this->assertStringContainsString('XDEBUG_MODE=coverage', $command);
				$this->assertStringContainsString('--coverage-filter ' . escapeshellarg('src/Controller/'), $command);
				$this->assertStringNotContainsString(' --include ', $command);

				return true;
			}))->willReturn([[], 0]);
		$component->coverage('tests/TestCase/TestHelperPluginTest.php', 'MissingReport', 'Controller', true);
	}

	public function testCoverageReportsProcessFailureDiagnostics(): void {
		$component = $this->getMockBuilder(TestRunnerComponent::class)
			->setConstructorArgs([new ComponentRegistry(new Controller(new ServerRequest()))])
			->onlyMethods(['executeCommand'])->getMock();
		$component->expects($this->once())->method('executeCommand')->willReturn([['<error>driver missing'], 2]);
		$result = $component->coverage('tests/TestCase/TestHelperPluginTest.php', 'MissingReport', 'Controller', true);
		$this->assertFalse($result['testFileExists']);
		$this->assertSame(2, $result['code']);
		$this->assertStringContainsString('&lt;error&gt;driver missing', $result['output']);
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
