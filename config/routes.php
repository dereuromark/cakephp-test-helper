<?php

use Cake\Routing\RouteBuilder;
use Cake\Routing\Router;

Router::plugin(
    'TestHelper',
    ['path' => '/test-helper'],
    function (RouteBuilder $routes) {
        $routes->fallbacks();
    }
);

