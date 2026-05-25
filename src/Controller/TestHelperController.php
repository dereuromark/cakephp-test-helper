<?php

namespace TestHelper\Controller;

use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Http\ServerRequest;
use Cake\Http\UriFactory;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Router;
use DirectoryIterator;
use TestHelper\Utility\ClassResolver;

class TestHelperController extends TestHelperAppController {

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		if ($this->request->is('post')) {
			$url = (string)$this->request->getData('url');

			$origin = (string)env('HTTP_ORIGIN');
			if ($origin) {
				$url = str_replace($origin, '', $url);
			}

			$request = (new ServerRequest())->withUri((new UriFactory())->createUri($url));
			$params = null;
			try {
				$params = Router::getRouteCollection()->parseRequest($request);
			} catch (MissingRouteException $e) {
				$this->Flash->error($e->getMessage());
			}

			$this->set(compact('params'));
		}

		$plugins = Plugin::loaded();

		$namespace = (string)$this->request->getQuery('plugin') ?: null;
		if ($namespace && !in_array($namespace, $plugins, true)) {
			$this->Flash->error('Invalid plugin');

			return $this->redirect([]);
		}

		$testTypes = $this->getTestTypes($namespace);

		$this->set(compact('plugins', 'namespace', 'testTypes'));
	}

	/**
	 * Get test types with file counts
	 *
	 * @param string|null $namespace
	 * @return array<array<string, mixed>>
	 */
	protected function getTestTypes(?string $namespace): array {
		$plugin = $namespace !== 'app' && $namespace ? $namespace : null;

		$testTypes = [
			// Controller Layer
			['icon' => 'controllers', 'label' => 'Controllers', 'action' => 'controller', 'type' => 'Controller'],
			['icon' => 'components', 'label' => 'Components', 'action' => 'component', 'type' => 'Component'],
			// Model Layer
			['icon' => 'tables', 'label' => 'Tables', 'action' => 'table', 'type' => 'Table'],
			['icon' => 'entities', 'label' => 'Entities', 'action' => 'entity', 'type' => 'Entity'],
			['icon' => 'behaviors', 'label' => 'Behaviors', 'action' => 'behavior', 'type' => 'Behavior'],
			// View Layer
			['icon' => 'helpers', 'label' => 'Helpers', 'action' => 'helper', 'type' => 'Helper'],
			['icon' => 'cells', 'label' => 'Cells', 'action' => 'cell', 'type' => 'Cell'],
			// Console/Command Layer
			['icon' => 'commands', 'label' => 'Commands', 'action' => 'command', 'type' => 'Command'],
			['icon' => 'tasks', 'label' => 'Tasks', 'action' => 'task', 'type' => 'Task'],
			['icon' => 'command-helpers', 'label' => 'Command Helpers', 'action' => 'commandHelper', 'type' => 'CommandHelper'],
			// Other
			['icon' => 'forms', 'label' => 'Forms', 'action' => 'form', 'type' => 'Form'],
			['icon' => 'mailers', 'label' => 'Mailers', 'action' => 'mailer', 'type' => 'Mailer'],
		];

		return $this->addFileCountsToTypes($testTypes, $plugin);
	}

	/**
	 * Add file counts to test types
	 *
	 * @param array<array<string, mixed>> $testTypes
	 * @param string|null $plugin
	 * @return array<array<string, mixed>>
	 */
	protected function addFileCountsToTypes(array $testTypes, ?string $plugin): array {
		foreach ($testTypes as &$typeData) {
			$typeData['count'] = $this->countFilesForType($typeData['type'], $plugin);
		}
		unset($typeData);

		return $testTypes;
	}

	/**
	 * Count PHP files for a given type
	 *
	 * @param string $type
	 * @param string|null $plugin
	 * @return int
	 */
	protected function countFilesForType(string $type, ?string $plugin): int {
		$classType = ClassResolver::type($type);
		$paths = App::classPath($classType, $plugin);
		$count = 0;

		foreach ($paths as $path) {
			if (!is_dir($path)) {
				continue;
			}

			// Count files in the main folder
			$iterator = new DirectoryIterator($path);
			foreach ($iterator as $fileInfo) {
				if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
					// Exclude base Controller.php for Controller type
					if ($type === 'Controller' && $fileInfo->getFilename() === 'Controller.php') {
						continue;
					}
					$count++;
				}
			}

			// For Controllers, also count files in subdirectories that have Controller suffix
			if ($type === 'Controller') {
				foreach ($iterator as $fileInfo) {
					if ($fileInfo->isDir() && !$fileInfo->isDot()) {
						$subPath = $path . DS . $fileInfo->getFilename();
						$subIterator = new DirectoryIterator($subPath);
						foreach ($subIterator as $subFileInfo) {
							if ($subFileInfo->isFile() && $subFileInfo->getExtension() === 'php') {
								$filename = $subFileInfo->getFilename();
								if (str_ends_with($filename, 'Controller.php') && $filename !== 'Controller.php') {
									$count++;
								}
							}
						}
					}
				}
			}
		}

		return $count;
	}

}
