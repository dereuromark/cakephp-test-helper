<?php

namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\Plugin;
use Cake\Event\Event;

/**
 * @property \TestHelper\Controller\Component\TestGeneratorComponent $TestGenerator
 * @property \TestHelper\Controller\Component\TestFixturesComponent $TestFixtures
 */
class TestFixturesController extends AppController {

	/**
	 * @var array
	 */
	public $components = [
		'Flash',
		'TestHelper.TestGenerator',
		'TestHelper.TestFixtures',
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
	 * @return void
	 */
	public function index() {
		if ($this->request->getQuery('plugin')) {
			$plugins = [$this->request->getQuery('plugin')];
		} else {
			$plugins = Plugin::loaded();
		}

		$result = $this->TestFixtures->all($plugins);

		$this->set(compact('result'));
	}

	/**
	 * @return void
	 * @throws \RuntimeException
	 */

	/**
	 * Currently supports types:
	 * - Fixture
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function generate() {
		$this->request->allowMethod('post');

		$appOrPlugin = $this->request->getData('plugin');
		$plugin = $appOrPlugin !== 'app' ? $appOrPlugin : null;
		$name = $this->request->getData('name');

		if ($this->TestGenerator->generate($name, $plugin)) {
			$this->Flash->success($name . 'Fixture generated.');
		}

		return $this->redirect($this->referer(['action' => 'index'] + ['?' => $this->request->getQuery()], true));
	}

}
