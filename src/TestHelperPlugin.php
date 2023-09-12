<?php

namespace TestHelper;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;

class TestHelperPlugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected bool $middlewareEnabled = false;

	/**
	 * @var bool
	 */
	protected bool $bootstrapEnabled = false;

	/**
	 * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
	 * @return void
	 */
	public function routes(RouteBuilder $routes): void {
		$routes->plugin(
			'TestHelper',
			['path' => '/test-helper'],
			function (RouteBuilder $routes): void {
				$routes->connect('/', ['controller' => 'TestHelper', 'action' => 'index']);

				$routes->fallbacks();
			},
		);
	}

}
