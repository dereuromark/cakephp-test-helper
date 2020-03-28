<?php

namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;
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

			$params = Router::getRouteCollection()->parse($url);

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
