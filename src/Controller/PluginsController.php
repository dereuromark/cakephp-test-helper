<?php

namespace TestHelper\Controller;

use Cake\Core\Plugin;

/**
 * @property \TestHelper\Controller\Component\PluginsComponent $Plugins
 */
class PluginsController extends TestHelperAppController {

	protected ?string $defaultTable = '';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('TestHelper.Plugins');
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
