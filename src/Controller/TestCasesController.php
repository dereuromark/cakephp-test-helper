<?php

namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Event\EventInterface;
use Cake\Filesystem\Folder;
use RuntimeException;
use TestHelper\Utility\ClassResolver;

/**
 * @property \TestHelper\Controller\Component\TestRunnerComponent $TestRunner
 * @property \TestHelper\Controller\Component\TestGeneratorComponent $TestGenerator
 */
class TestCasesController extends AppController {

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('Flash');
		$this->loadComponent('TestHelper.TestRunner');
		$this->loadComponent('TestHelper.TestGenerator');

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
	public function run() {
		$file = $this->request->getData('test') ?: $this->request->getQuery('test');

		$result = $this->TestRunner->run($file);

		$this->set(compact('result'));
		$this->set('_serialize', 'result');
	}

	/**
	 * @return void
	 * @throws \RuntimeException
	 */
	public function coverage() {
		$file = $this->request->getData('test') ?: $this->request->getQuery('test');
		if (!file_exists(ROOT . DS . $file)) {
			throw new RuntimeException('Invalid file: ' . $file);
		}

		$name = $this->request->getData('name') ?: $this->request->getQuery('name');
		$type = $this->request->getData('type') ?: $this->request->getQuery('type');
		$force = $this->request->getData('force') ?: $this->request->getQuery('force');

		$result = $this->TestRunner->coverage($file, $name, $type, (bool)$force);

		$this->set(compact('result'));
		$this->set('_serialize', 'result');
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function controller() {
		return $this->handle(ucfirst(__FUNCTION__));
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function shell() {
		return $this->handle(ucfirst(__FUNCTION__));
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function table() {
		return $this->handle(ucfirst(__FUNCTION__));
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function entity() {
		return $this->handle(ucfirst(__FUNCTION__));
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function behavior() {
		return $this->handle(ucfirst(__FUNCTION__));
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function component() {
		return $this->handle(ucfirst(__FUNCTION__));
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function helper() {
		return $this->handle(ucfirst(__FUNCTION__));
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function task() {
		return $this->handle(ucfirst(__FUNCTION__));
	}

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

			[$prefix, $class] = pluginSplit($name);
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

	/**
	 * @param string $name
	 * @param string $type
	 * @param string $plugin
	 * @param array $options
	 *
	 * @return bool
	 */
	protected function generate($name, $type, $plugin, array $options = []) {
		$arguments = 'test ' . $type . ' ' . $name . ' -q';
		if (Plugin::isLoaded('Setup')) {
			$arguments .= ' -t Setup';
		}
		if ($plugin) {
			$arguments .= ' -p ' . $plugin;
		}
		foreach ($options as $key => $option) {
			$arguments .= '--' . $key . ' ' . $option;
		}

		$command = 'cd ' . ROOT . ' && php bin/cake.php bake ' . $arguments;
		exec($command, $output, $return);

		if ($return !== 0) {
			$this->Flash->error('Error code ' . $return . ': ' . print_r($output, true) . ' [' . $command . ']');
		}

		$this->Flash->success(json_encode($output));

		return $return === 0;
	}

	/**
	 * @param array $folders
	 *
	 * @return array
	 */
	protected function getFiles(array $folders) {
		$names = [];
		foreach ($folders as $folder) {
			$folderContent = (new Folder($folder))->read(Folder::SORT_NAME, true);

			foreach ($folderContent[1] as $file) {
				$name = pathinfo($file, PATHINFO_FILENAME);
				$names[] = $name;
			}

			foreach ($folderContent[0] as $subFolder) {
				$folderContent = (new Folder($folder . $subFolder))->read(Folder::SORT_NAME, true);

				foreach ($folderContent[1] as $file) {
					$name = pathinfo($file, PATHINFO_FILENAME);
					$names[] = $subFolder . '.' . $name;
				}
			}
		}

		return $names;
	}

}
