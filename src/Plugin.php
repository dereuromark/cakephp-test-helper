<?php

namespace TestHelper;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;

class Plugin extends BasePlugin {

	/**
	 * @var bool
	 */
	protected $middlewareEnabled = false;

	/**
	 * @var bool
	 */
	protected $bootstrapEnabled = false;

	/**
	 * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
	 * @return void
	 */
	public function routes(RouteBuilder $routes): void {
		$routes->plugin(
			'TestHelper',
			['path' => '/test-helper'],
			function (RouteBuilder $routes) {
				$routes->connect('/', ['controller' => 'TestHelper', 'action' => 'index']);

				$routes->fallbacks();
			}
		);
	}

}
