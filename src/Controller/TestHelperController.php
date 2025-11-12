<?php

namespace TestHelper\Controller;

use Cake\Core\Plugin;
use Cake\Http\ServerRequest;
use Cake\Http\UriFactory;
use Cake\Routing\Router;

class TestHelperController extends TestHelperAppController {

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
