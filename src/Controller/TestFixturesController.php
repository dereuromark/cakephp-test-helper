<?php

namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;

/**
 * @property \TestHelper\Controller\Component\TestGeneratorComponent $TestGenerator
 * @property \TestHelper\Controller\Component\TestFixturesComponent $TestFixtures
 */
class TestFixturesController extends AppController {

	protected ?string $defaultTable = '';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Flash');
		$this->loadComponent('TestHelper.TestGenerator');
		$this->loadComponent('TestHelper.TestFixtures');

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
	 * @return void
	 */
	public function index() {
		if ($this->request->getQuery('plugin')) {
			/** @var array<string> $plugins */
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
	 * @return \Cake\Http\Response|null
	 */
	public function generate() {
		$this->request->allowMethod('post');

		$appOrPlugin = $this->request->getData('plugin');
		$plugin = $appOrPlugin !== 'app' ? $appOrPlugin : null;
		$name = $this->request->getData('name');

		if ($this->TestGenerator->generateFixture($name, $plugin)) {
			$this->Flash->success($name . 'Fixture generated.');
		}

		return $this->redirect($this->referer(['action' => 'index'] + ['?' => $this->request->getQuery()], true));
	}

}
