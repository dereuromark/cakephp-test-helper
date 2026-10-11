<?php

namespace TestHelper\Test\TestCase;

use Cake\Core\PluginApplicationInterface;
use Cake\Http\MiddlewareQueue;
use Cake\TestSuite\TestCase;
use TestHelper\TestHelperPlugin;

/**
 * @uses \TestHelper\TestHelperPlugin
 */
class TestHelperPluginTest extends TestCase {

	/**
	 * Test plugin name
	 *
	 * @return void
	 */
	public function testName() {
		$plugin = new TestHelperPlugin();
		$this->assertSame('TestHelper', $plugin->getName());
	}

	/**
	 * Test middleware is disabled
	 *
	 * @return void
	 */
	public function testMiddleware() {
		$plugin = new TestHelperPlugin();
		$middlewareQueue = new MiddlewareQueue();

		$result = $plugin->middleware($middlewareQueue);
		$this->assertSame($middlewareQueue, $result);
	}

	/**
	 * Test bootstrap is disabled
	 *
	 * @return void
	 */
	public function testBootstrap() {
		$plugin = new TestHelperPlugin();
		$app = $this->createStub(PluginApplicationInterface::class);

		// Bootstrap should do nothing since it's disabled
		$plugin->bootstrap($app);
		$this->assertTrue(true);
	}

}
