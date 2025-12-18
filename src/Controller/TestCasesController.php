<?php

namespace TestHelper\Controller;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Http\Response;
use DirectoryIterator;
use ReflectionClass;
use ReflectionMethod;
use RuntimeException;
use TestHelper\Utility\ClassResolver;
use Throwable;

/**
 * @property \TestHelper\Controller\Component\TestRunnerComponent $TestRunner
 * @property \TestHelper\Controller\Component\TestGeneratorComponent $TestGenerator
 */
class TestCasesController extends TestHelperAppController {

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('TestHelper.TestRunner');
		$this->loadComponent('TestHelper.TestGenerator');
	}

	/**
	 * Browse TestCase directory structure
	 *
	 * @return void
	 */
	public function browse(): void {
		/** @var string|null $appOrPlugin */
		$appOrPlugin = $this->request->getQuery('namespace');
		$plugin = $appOrPlugin !== 'app' ? $appOrPlugin : null;

		$path = $this->request->getQuery('path') ?: '';
		$path = str_replace(['..', "\0"], '', (string)$path); // Security: prevent directory traversal

		$basePath = $plugin ? Plugin::path($plugin) . 'tests' . DS . 'TestCase' : TESTS . 'TestCase';
		$fullPath = $basePath . ($path ? DS . $path : '');

		if (!is_dir($fullPath)) {
			$this->Flash->error('Directory not found: ' . $path);
			$fullPath = $basePath;
			$path = '';
		}

		$items = $this->getDirectoryContents($fullPath);

		// Build breadcrumb
		$breadcrumb = [];
		if ($path) {
			$parts = explode(DS, $path);
			$currentPath = '';
			foreach ($parts as $part) {
				$currentPath .= ($currentPath ? DS : '') . $part;
				$breadcrumb[] = ['name' => $part, 'path' => $currentPath];
			}
		}

		$namespace = $appOrPlugin ?: 'app';
		$this->set(compact('items', 'path', 'breadcrumb', 'namespace', 'plugin'));
	}

	/**
	 * View a specific test case and its methods
	 *
	 * @return void
	 */
	public function view(): void {
		/** @var string|null $appOrPlugin */
		$appOrPlugin = $this->request->getQuery('namespace');
		$plugin = $appOrPlugin !== 'app' ? $appOrPlugin : null;

		$file = $this->request->getQuery('file');
		if (!$file) {
			$this->Flash->error('No file specified');

			return;
		}

		$file = str_replace(['..', "\0"], '', (string)$file); // Security: prevent directory traversal

		$basePath = $plugin ? Plugin::path($plugin) . 'tests' . DS . 'TestCase' : TESTS . 'TestCase';
		$fullPath = $basePath . DS . $file;

		if (!file_exists($fullPath)) {
			$this->Flash->error('Test file not found: ' . $file);

			return;
		}

		$testPath = ($plugin ? 'plugins' . DS . $plugin . DS . 'tests' : 'tests') . DS . 'TestCase' . DS . $file;
		$methods = $this->getTestMethods($fullPath, $plugin);

		// Build breadcrumb from file path
		$breadcrumb = [];
		$parts = explode(DS, dirname($file));
		$currentPath = '';
		foreach ($parts as $part) {
			if ($part === '.') {
				continue;
			}
			$currentPath .= ($currentPath ? DS : '') . $part;
			$breadcrumb[] = ['name' => $part, 'path' => $currentPath];
		}

		$namespace = $appOrPlugin ?: 'app';
		$className = basename($file, '.php');

		$this->set(compact('methods', 'file', 'testPath', 'breadcrumb', 'namespace', 'plugin', 'className'));
	}

	/**
	 * Get contents of a directory
	 *
	 * @param string $path
	 * @return array<array<string, mixed>>
	 */
	protected function getDirectoryContents(string $path): array {
		$items = [];

		if (!is_dir($path)) {
			return $items;
		}

		$iterator = new DirectoryIterator($path);
		foreach ($iterator as $fileInfo) {
			if ($fileInfo->isDot()) {
				continue;
			}

			$name = $fileInfo->getFilename();

			if ($fileInfo->isDir()) {
				$items[] = [
					'name' => $name,
					'type' => 'directory',
					'path' => $name,
				];
			} elseif ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
				$items[] = [
					'name' => $name,
					'type' => 'file',
					'path' => $name,
				];
			}
		}

		// Sort: directories first, then files, alphabetically
		usort($items, function ($a, $b) {
			if ($a['type'] !== $b['type']) {
				return $a['type'] === 'directory' ? -1 : 1;
			}

			return strcasecmp($a['name'], $b['name']);
		});

		return $items;
	}

	/**
	 * Get test methods from a test file
	 *
	 * @param string $filePath
	 * @param string|null $plugin
	 * @return array<array<string, mixed>>
	 */
	protected function getTestMethods(string $filePath, ?string $plugin): array {
		$methods = [];

		// Try to determine the class name from the file
		$content = file_get_contents($filePath);
		if (!$content) {
			return $methods;
		}

		// Extract namespace and class name
		$namespace = null;
		if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
			$namespace = $matches[1];
		}

		$className = null;
		if (preg_match('/class\s+(\w+)/', $content, $matches)) {
			$className = $matches[1];
		}

		if (!$namespace || !$className) {
			// Fallback: extract test methods via regex
			return $this->extractTestMethodsViaRegex($content);
		}

		$fullClassName = $namespace . '\\' . $className;

		// Try to load and reflect the class
		try {
			if (!class_exists($fullClassName)) {
				require_once $filePath;
			}

			if (!class_exists($fullClassName)) {
				return $this->extractTestMethodsViaRegex($content);
			}

			$reflection = new ReflectionClass($fullClassName);
			foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
				$methodName = $method->getName();

				// Check if it's a test method (starts with 'test' or has @test annotation)
				$isTest = str_starts_with($methodName, 'test');
				if (!$isTest && $method->getDocComment()) {
					$isTest = str_contains($method->getDocComment(), '@test');
				}

				if ($isTest && $method->getDeclaringClass()->getName() === $fullClassName) {
					$methods[] = [
						'name' => $methodName,
						'line' => $method->getStartLine(),
					];
				}
			}
		} catch (Throwable $e) {
			// If reflection fails, fall back to regex
			return $this->extractTestMethodsViaRegex($content);
		}

		return $methods;
	}

	/**
	 * Extract test methods using regex (fallback)
	 *
	 * @param string $content
	 * @return array<array<string, mixed>>
	 */
	protected function extractTestMethodsViaRegex(string $content): array {
		$methods = [];

		// Match public function testXxx or methods with @test annotation
		if (preg_match_all('/public\s+function\s+(test\w+)\s*\(/m', $content, $matches, PREG_OFFSET_CAPTURE)) {
			foreach ($matches[1] as $match) {
				$methodName = $match[0];
				$offset = $match[1];
				$line = substr_count(substr($content, 0, $offset), "\n") + 1;

				$methods[] = [
					'name' => $methodName,
					'line' => $line,
				];
			}
		}

		return $methods;
	}

	/**
	 * @return void
	 */
	public function run() {
		$file = $this->request->getData('test') ?: $this->request->getQuery('test');
		$filter = $this->request->getData('filter') ?: $this->request->getQuery('filter');

		$result = $this->TestRunner->run($file, $filter);

		if ($this->request->is('ajax')) {
			// For AJAX, return JSON directly
			$this->viewBuilder()->setClassName('Json');

			// Build HTML output manually
			$output = '<h1>' . h($file) . '</h1>';
			$output .= '<code>' . h($result['command']) . '</code>';
			$output .= '<h2>' . ($result['code'] === 0 ? 'OK' : 'ERROR (code ' . $result['code'] . ')') . '</h2>';
			$output .= '<pre>' . h(implode("\n", $result['content'])) . '</pre>';

			$response = [
				'output' => $output,
				'code' => $result['code'],
				'command' => $result['command'],
			];
			$this->set($response);
			$this->viewBuilder()->setOptions(['serialize' => array_keys($response)]);
		} else {
			$this->set(compact('result'));
		}
	}

	/**
	 * @throws \RuntimeException
	 * @return void
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

		if ($this->request->is('ajax')) {
			// For AJAX, return JSON directly
			$this->viewBuilder()->setClassName('Json');

			// Build HTML output manually
			$refreshUrl = $this->request->getUri()->getPath() . '?' . http_build_query(['force' => true] + $this->request->getQuery());
			$output = '<h1>' . h($file) . '</h1>';
			$output .= '<code>' . h($result['command']) . '</code><br><br>';
			$output .= '<div style="float: right">';
			$output .= '<a href="' . h($refreshUrl) . '">Refresh</a>';
			$output .= ' | ';
			$output .= '<a href="' . h($result['url']) . '" target="_blank">Open in new tab</a>';
			$output .= '</div>';
			$output .= 'Coverage-Result of ' . h($result['file']);
			$output .= '<h2>Details</h2>';
			if ($result['testFileExists']) {
				$output .= '<iframe src="' . h($result['url']) . '" style="width: 98%; height: 800px;"></iframe>';
			} else {
				$output .= '<i>Coverage file could not be created, coverage driver issues?</i>';
			}

			$response = [
				'output' => $output,
				'url' => $result['url'],
				'testFileExists' => $result['testFileExists'],
			];
			$this->set($response);
			$this->viewBuilder()->setOptions(['serialize' => array_keys($response)]);
		} else {
			$this->set(compact('result'));
		}
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
	public function command() {
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
	 * @return \Cake\Http\Response|null
	 */
	public function commandHelper() {
		return $this->handle('CommandHelper');
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function cell() {
		return $this->handle(ucfirst(__FUNCTION__));
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function form() {
		return $this->handle(ucfirst(__FUNCTION__));
	}

	/**
	 * @return \Cake\Http\Response|null
	 */
	public function mailer() {
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
	 * - Command
	 * - Task
	 * - CommandHelper
	 * - Cell
	 * - Form
	 * - Mailer
	 *
	 * @param string $type
	 *
	 * @return \Cake\Http\Response|null
	 */
	protected function handle(string $type): ?Response {
		/** @var string|null $appOrPlugin */
		$appOrPlugin = $this->request->getQuery('namespace');
		$plugin = $appOrPlugin !== 'app' ? $appOrPlugin : null;
		$classType = ClassResolver::type($type);
		$paths = App::classPath($classType, $plugin);
		$files = $this->TestGenerator->getFiles($paths);

		if ($this->request->is('post')) {
			if ($this->TestGenerator->generate($this->request->getData('name'), $type, $plugin)) {
				$this->Flash->success('Test case generated.');
			}

			return $this->redirect($this->referer([$type] + ['?' => $this->request->getQuery()]));
		}

		/** @phpstan-var string $name */
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

		return $this->render('handle');
	}

	/**
	 * @param string $name
	 * @param string $type
	 * @param string $plugin
	 * @param array<string, mixed> $options
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

		$command = 'cd ' . ROOT . ' && ' . $this->getPhpBinary() . ' bin/cake.php bake ' . $arguments;
		exec($command, $output, $return);

		if ($return !== 0) {
			$this->Flash->error('Error code ' . $return . ': ' . print_r($output, true) . ' [' . $command . ']');
		}

		$this->Flash->success((string)json_encode($output));

		return $return === 0;
	}

	/**
	 * @return string
	 */
	protected function getPhpBinary(): string {
		return Configure::read('TestHelper.php') ?: 'php';
	}

	/**
	 * @param array $folders
	 *
	 * @return array
	 */
	protected function getFiles(array $folders) {
		$names = [];
		foreach ($folders as $folder) {
			if (!is_dir($folder)) {
				continue;
			}

			// Get files in the main folder
			$iterator = new DirectoryIterator($folder);
			foreach ($iterator as $fileInfo) {
				if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
					$name = $fileInfo->getBasename('.php');
					$names[] = $name;
				}
			}

			// Get files in subdirectories
			foreach ($iterator as $fileInfo) {
				if ($fileInfo->isDir() && !$fileInfo->isDot()) {
					$subFolder = $fileInfo->getFilename();
					$subIterator = new DirectoryIterator($folder . $subFolder);
					foreach ($subIterator as $subFileInfo) {
						if ($subFileInfo->isFile() && $subFileInfo->getExtension() === 'php') {
							$name = $subFileInfo->getBasename('.php');
							$names[] = $subFolder . '.' . $name;
						}
					}
				}
			}
		}

		return $names;
	}

}
