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

		$this->assertSame('vendor/bin/phpunit', $result);
	}

	/**
	 * @return void
	 */
	public function testGetCommandConfigured(): void {
		Configure::write('TestHelper.command', 'custom/phpunit');

		$result = $this->invokeMethod($this->component, 'getCommand');

		$this->assertSame('custom/phpunit', $result);
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
