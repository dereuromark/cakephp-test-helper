<?php

namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Event\Event;
use RuntimeException;
use TestHelper\Utility\ClassResolver;

/**
 * @property \TestHelper\Controller\Component\TestGeneratorComponent $TestGenerator
 */
class TestFixturesController extends AppController {

	/**
	 * @var array
	 */
	public $components = [
		'Flash',
		'TestHelper.TestGenerator',
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
		$plugins = Plugin::loaded();

		$result = $this->TestFixtures->all($plugins);

		$this->set(compact('result'));
	}

	/**
	 * @return void
	 * @throws \RuntimeException
	 */

	/**
	 * Bake currently supports types:
	 * - Entity
	 * - Table
	 * - Controller
	 * - Component
	 * - Behavior
	 * - Helper
	 * - Shell
	 * - Task
	 * - ShellHelper
	 * - Cell
	 * - Form
	 * - Mailer
	 *
	 * @param string $type
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	protected function handle($type) {
		$appOrPlugin = $this->request->getQuery('namespace');
		$plugin = $appOrPlugin !== 'app' ? $appOrPlugin : null;
		$classType = ClassResolver::type($type);
		$paths = App::path($classType, $plugin);
		$files = $this->TestGenerator->getFiles($paths);

		if ($this->request->is('post')) {
			if ($this->TestGenerator->generate($this->request->getData('name'), $type, $plugin)) {
				$this->Flash->success('Test case generated.');
			}

			return $this->redirect($this->referer([$type] + ['?' => $this->request->getQuery()], true));
		}

		foreach ($files as $key => $name) {
			$suffix = ClassResolver::suffix($type);
			if ($suffix && !preg_match('/\w+' . $suffix . '$/', $name)) {
				unset($files[$key]);
				continue;
			}

			list ($prefix, $class) = pluginSplit($name);
			$class .= 'Test.php';
			if ($prefix) {
				$class = $prefix . DS . $class;
			}

			$folder = str_replace('/', DS, $classType);
			$testCase = ($plugin ? Plugin::path($plugin) . 'tests' . DS : TESTS) . 'TestCase' . DS . $folder . DS . $class;

			$files[$key] = [
				'type' => ($plugin ? $plugin . '.' : '') . $classType,
				'name' => $name,
				'testCase' => str_replace(ROOT . DS, '', $testCase),
				'hasTestCase' => file_exists($testCase),
			];
		}

		$this->set(compact('files', 'type'));
		$this->render('handle');
	}

}
