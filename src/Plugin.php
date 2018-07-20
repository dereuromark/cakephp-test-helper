<?php

namespace TestHelper;

use Cake\Core\BasePlugin;
use Cake\Routing\RouteBuilder;
use Cake\Routing\Route\DashedRoute;

/**
 * Backend Plugin
 *
 * @author Flavius
 * @version 1.0
 */
class Plugin extends BasePlugin
{
    /**
     * Plugin name
     * @var string
     */
    protected $name = 'TestHelper';

    /**
     * {@inheritDoc}
     * @see \Cake\Core\BasePlugin::routes()
     */
    public function routes($routes)
    {
        $routes->plugin(
            'TestHelper',
            ['path' => '/test-helper'],
            function (RouteBuilder $routes) {
                $routes->connect('/', ['controller' => 'TestHelper', 'action' => 'index']);

                $routes->fallbacks(DashedRoute::class);
            }
        );
    }
}
