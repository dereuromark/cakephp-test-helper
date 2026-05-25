<?php

namespace TestHelper\Controller;

use Cake\Core\Configure;
use Cake\Core\Plugin;

/**
 * @property \TestHelper\Controller\Component\TestGeneratorComponent $TestGenerator
 * @property \TestHelper\Controller\Component\CollectorComponent $Collector
 */
class TestFixturesController extends TestHelperAppController {

	protected ?string $defaultTable = '';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('TestHelper.TestGenerator');
		$this->loadComponent('TestHelper.Collector', [
			'connection' => $this->request->getQuery('connection', 'default'),
		] + (array)Configure::read('TestHelper.Collector'));
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

		$result = $this->Collector->fixtureComparison($plugins);

		$this->set(compact('result'));
	}

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

		return $this->redirect($this->referer(['action' => 'index'] + ['?' => $this->request->getQuery()]));
	}

}
