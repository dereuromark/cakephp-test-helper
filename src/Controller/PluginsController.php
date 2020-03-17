<?php

namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;

/**
 * @property \TestHelper\Controller\Component\PluginsComponent $Plugins
 */
class PluginsController extends AppController {

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
	 * @param \Cake\Event\Event $event
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
	 * @return void
	 */
	public function index() {
		$plugins = Plugin::loaded();

		$hooks = $this->Plugins->hooks();
		$result = $this->Plugins->check($plugins);

		$this->set(compact('plugins', 'hooks', 'result'));
	}

}
