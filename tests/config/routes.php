<?php

use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

/**
 * @var \Cake\Routing\RouteBuilder $routes
 */
$routes->setRouteClass(DashedRoute::class);
$routes->scope('/', function(RouteBuilder $routes) {
	$routes->fallbacks();
});

$routes->plugin(
	'TestHelper',
	function (RouteBuilder $routes) {
		$routes->connect('/', ['controller' => 'TestHelper', 'action' => 'index']);

		$routes->fallbacks();
	},
	['path' => '/test-helper'],
);
