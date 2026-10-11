<?php

namespace TestHelper\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\BasePlugin;
use Cake\Core\Configure;
use Cake\Core\Plugin;
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
				$this->assertSame('coverage', getenv('XDEBUG_MODE'));
				$this->assertStringNotContainsString('XDEBUG_MODE=', $command);
				$this->assertStringContainsString('--coverage-filter ' . escapeshellarg(ROOT . DS . 'src/Controller/'), $command);
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

	public function testPluginCoverageUsesPluginSourceDirectoryAndReport(): void {
		$path = ROOT . DS . 'plugins/CoverageFixture/';
		Plugin::getCollection()->add(new BasePlugin(['name' => 'CoverageFixture', 'path' => $path]));
		$directory = ROOT . DS . 'webroot/coverage/plugins/CoverageFixture/src/Controller/Admin';
		if (!is_dir($directory)) {
			mkdir($directory, 0770, true);
		}
		$report = $directory . '/ReportsController.php.html';
		file_put_contents($report, 'Plugin coverage');
		$component = $this->getMockBuilder(TestRunnerComponent::class)
			->setConstructorArgs([new ComponentRegistry(new Controller(new ServerRequest()))])
			->onlyMethods(['executeCommand'])->getMock();
		$component->expects($this->once())->method('executeCommand')
			->with($this->callback(function (string $command) use ($path): bool {
				$this->assertStringContainsString('--coverage-filter ' . escapeshellarg($path . 'src/Controller/'), $command);

				return true;
			}))->willReturn([[], 0]);
		try {
			$result = $component->coverage('tests/TestCase/TestHelperPluginTest.php', 'Admin.ReportsController', 'CoverageFixture.Controller', true);
			$this->assertTrue($result['testFileExists']);
			$this->assertSame('/coverage/plugins/CoverageFixture/src/Controller/Admin/ReportsController.php.html', $result['url']);
		} finally {
			unlink($report);
			Plugin::getCollection()->remove('CoverageFixture');
		}
	}

	public function testFailedForcedCoverageDoesNotReuseOldReport(): void {
		$directory = ROOT . DS . 'webroot/coverage/src/Controller';
		if (!is_dir($directory)) {
			mkdir($directory, 0770, true);
		}
		$report = $directory . '/StaleReport.php.html';
		file_put_contents($report, 'Old coverage report');
		$previousMode = getenv('XDEBUG_MODE');
		$component = $this->getMockBuilder(TestRunnerComponent::class)
			->setConstructorArgs([new ComponentRegistry(new Controller(new ServerRequest()))])
			->onlyMethods(['executeCommand'])->getMock();
		$component->expects($this->once())->method('executeCommand')->willReturn([['Failed coverage'], 2]);
		try {
			$result = $component->coverage('tests/TestCase/TestHelperPluginTest.php', 'StaleReport', 'Controller', true);
			$this->assertFalse($result['testFileExists']);
			$this->assertNull($result['content']);
			$this->assertSame(2, $result['code']);
			$this->assertStringContainsString('Failed coverage', $result['output']);
			$this->assertSame($previousMode, getenv('XDEBUG_MODE'));
		} finally {
			unlink($report);
		}
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
