<?php
namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Filesystem\Folder;
use RuntimeException;

/**
 * @property \TestHelper\Controller\Component\TestRunnerComponent $TestRunner
 */
class TestCasesController extends AppController {

	/**
	 * @var array
	 */
	public $components = [
		'TestHelper.TestRunner',
	];

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
	 */
	public function coverage() {
		$file = $this->request->getData('test') ?: $this->request->getQuery('test');
		if (!file_exists(ROOT . DS . $file)) {
			throw new RuntimeException('Invalid file: ' . $file);
		}

		$name = $this->request->getData('name') ?: $this->request->getQuery('name');
		$type = $this->request->getData('type') ?: $this->request->getQuery('type');

		$result = $this->TestRunner->coverage($file, $name, $type);

		$this->set(compact('result'));
		$this->set('_serialize', 'result');
	}

	/**
	 * @param string|null $appOrPlugin
	 * @return \Cake\Http\Response|null
	 */
	public function controllers($appOrPlugin = null) {
		$plugin = $appOrPlugin !== 'app' ? $appOrPlugin : null;
		$controller = App::path('Controller', $plugin);
		$files = $this->getFiles($controller);

		if ($this->request->is('post')) {
			if ($this->generate($this->request->getData('name'), $plugin)) {
				$this->Flash->success('Test case generated');
			}

			return $this->redirect(['action' => 'controllers', $appOrPlugin]);
		}

		foreach ($files as $key => $name) {
			if (!preg_match('/\w+Controller$/', $name)) {
				unset($files[$key]);
				continue;
			}

			list ($prefix, $class) = pluginSplit($name);
			$class .= 'Test.php';
			if ($prefix) {
				$class = $prefix . DS . $class;
			}

			$testCase = ($plugin ? Plugin::path($plugin) . 'tests' . DS : TESTS) . 'TestCase' . DS . 'Controller' . DS . $class;

			$files[$key] = [
				'type' => ($plugin ? $plugin . '.' : '') . 'Controller',
				'name' => $name,
				'testCase' => str_replace(ROOT . DS, '', $testCase),
				'hasTestCase' => file_exists($testCase),
			];
		}

		$this->set(compact('files'));
	}

	/**
	 * @param string $name
	 * @param string $plugin
	 * @param array $options
	 *
	 * @return bool
	 */
	protected function generate($name, $plugin, array $options = []) {
		$arguments = 'test Controller ' . $name . ' -t Setup -q';
		if ($plugin) {
			$arguments .= '-p ' . $plugin;
		}
		foreach ($options as $key => $option) {
			$arguments .= '--' . $key . ' ' . $option;
		}

		$command = 'cd ' . ROOT . ' && php bin/cake.php bake ' . $arguments;
		exec($command, $output, $return);

		if ($return !== 0) {
			$this->Flash->error('Error code ' . $return . ': ' . print_r($output, true));
		}

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
