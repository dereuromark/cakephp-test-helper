<?php

namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Templating\View\Helper\IconHelper;

/**
 * @property \TestHelper\Controller\Component\TestGeneratorComponent $TestGenerator
 * @property \TestHelper\Controller\Component\CollectorComponent $Collector
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
		$this->loadComponent('TestHelper.Collector', [
			'connection' => $this->request->getQuery('connection', 'default'),
		] + (array)Configure::read('TestHelper.Collector'));

		$this->viewBuilder()->setHelpers([
			'TestHelper.TestHelper',
			'Tools.Format',
			class_exists(IconHelper::class) ? 'Templating.Icon' : 'Tools.Icon',
		]);
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);

		if ($this->components()->has('Security')) {
			$this->components()->get('Security')->setConfig('validatePost', false);
		}

		if ($this->components()->has('Auth') && method_exists($this->components()->get('Auth'), 'allow')) {
			$this->components()->get('Auth')->allow();
		} elseif ($this->components()->has('Authentication') && method_exists($this->components()->get('Authentication'), 'addUnauthenticatedActions')) {
			$this->components()->get('Authentication')->addUnauthenticatedActions(['index', 'generate']);
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
