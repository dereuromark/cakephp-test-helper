<?php

namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;

/**
 * @property \TestHelper\Controller\Component\PluginsComponent $Plugins
 */
class PluginsController extends AppController {

	protected ?string $defaultTable = '';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('TestHelper.Plugins');

		$this->viewBuilder()->setHelpers([
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
			$this->components()->get('Authentication')->addUnauthenticatedActions(['index', 'recommended']);
		}
	}

	/**
	 * @return void
	 */
	public function index() {
		$plugins = Plugin::loaded();

		$hooks = $this->Plugins->hooks();
		$result = $this->Plugins->check($plugins);

		$this->set(compact('plugins', 'hooks', 'result'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function recommended() {
		/** @var string $plugin */
		$plugin = $this->request->getQuery('plugin');

		$hooks = $this->Plugins->hooks();
		$result = $this->Plugins->check([$plugin]);
		$result = $result[$plugin];

		$class = $result['pluginClassExists'] ? $result['pluginClass'] : null;
		$classContent = $class ? (string)file_get_contents($class) : null;
		$classContentAfter = $this->Plugins->adjustPluginClass($plugin, $classContent, $result);

		if ($this->request->is('post')) {
			file_put_contents($result['pluginClass'], $classContentAfter);

			$this->Flash->success($result['pluginClass'] . ' adjusted.');

			return $this->redirect(['action' => 'recommended', '?' => $this->request->getQuery()]);
		}

		$this->set(compact('hooks', 'result', 'plugin', 'classContent', 'classContentAfter'));
	}

}
