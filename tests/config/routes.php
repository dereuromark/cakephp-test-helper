<?php

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;
use Cake\Routing\Route\DashedRoute;

Router::defaultRouteClass(DashedRoute::class);
Router::scope('/', function(RouteBuilder $routes) {
	$routes->fallbacks();
});

Router::plugin(
	'TestHelper',
	['path' => '/test-helper'],
	function (RouteBuilder $routes) {
		$routes->connect('/', ['controller' => 'TestHelper', 'action' => 'index']);

		$routes->fallbacks();
	}
);
