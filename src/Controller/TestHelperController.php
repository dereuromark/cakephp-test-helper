<?php

namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Routing\Router;

class TestHelperController extends AppController {

	/**
	 * @var array
	 */
	public $components = [
		'Flash',
	];

	/**
	 * @var array
	 */
	public $helpers = [
		'TestHelper.TestHelper',
		'Tools.Format',
	];

	/**
	 * @param \Cake\Event\Event $event
	 * @return \Cake\Http\Response|null|void
	 */
	public function beforeFilter(Event $event) {
		parent::beforeFilter($event);

		if (isset($this->Security)) {
			$this->Security->setConfig('validatePost', false);
		}

		if (isset($this->Auth)) {
			$this->Auth->allow();
		}
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		if ($this->request->is('post')) {
			$url = $this->request->getData('url');

			$url = str_replace(env('HTTP_ORIGIN'), '', $url);
			$url = Router::parse($url);
			/*
			//TODO add new 3.6 way
			$request = new ServerRequest($url);
			$middleware = new RoutingMiddleware();
			$result = $middleware->__invoke($request, new Response(), function() {});
			$url = Router::parseRequest($request);
			*/

			$this->set(compact('url'));
		}

		$plugins = Plugin::loaded();

		$namespace = $this->request->getQuery('plugin');
		if ($namespace && !in_array($namespace, $plugins)) {
			$this->Flash->error('Invalid plugin');
			return $this->redirect([]);
		}

		$this->set(compact('plugins', 'namespace'));
	}

}
