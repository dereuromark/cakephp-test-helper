<?php

namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use Cake\Http\UriFactory;
use Cake\Routing\Router;

class TestHelperController extends AppController {

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Flash');

		$this->viewBuilder()->setHelpers([
			'TestHelper.TestHelper',
			'Tools.Format',
		]);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return \Cake\Http\Response|null|void
	 */
	public function beforeFilter(EventInterface $event) {
		parent::beforeFilter($event);

		if ($this->components()->has('Security')) {
			$this->components()->get('Security')->setConfig('validatePost', false);
		}

		if ($this->components()->has('Auth') && method_exists($this->components()->get('Auth'), 'allow')) {
			$this->components()->get('Auth')->allow();
		} elseif ($this->components()->has('Authentication') && method_exists($this->components()->get('Authentication'), 'addUnauthenticatedActions')) {
			$this->components()->get('Authentication')->addUnauthenticatedActions(['index']);
		}
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		if ($this->request->is('post')) {
			$url = (string)$this->request->getData('url');

			$origin = (string)env('HTTP_ORIGIN');
			if ($origin) {
				$url = str_replace($origin, '', $url);
			}

			$request = (new ServerRequest())->withUri((new UriFactory())->createUri($url));
			$params = Router::getRouteCollection()->parseRequest($request);

			$this->set(compact('params'));
		}

		$plugins = Plugin::loaded();

		$namespace = $this->request->getQuery('plugin');
		if ($namespace && !in_array($namespace, $plugins, true)) {
			$this->Flash->error('Invalid plugin');

			return $this->redirect([]);
		}

		$this->set(compact('plugins', 'namespace'));
	}

}
