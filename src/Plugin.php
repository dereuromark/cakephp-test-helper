<?php
namespace TestHelper;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;

class Plugin extends BasePlugin {
	/**
	 * @param \Cake\Routing\RouteBuilder $routes The route builder to update.
	 * @return void
	 */
	public function routes($routes) {
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
