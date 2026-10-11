<?php

namespace TestHelper\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use DirectoryIterator;
use InvalidArgumentException;

/**
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class TestGeneratorComponent extends Component {

	protected array $components = [
		'Flash',
	];

	/**
	 * @param string $name
	 * @param string $type
	 * @param string|null $plugin
	 * @param array<string, mixed> $options
	 *
	 * @return bool
	 */
	public function generate(string $name, string $type, ?string $plugin, array $options = []) {
		$prefix = null;
		if (str_contains($name, '/')) {
			[$prefix, $name] = explode('/', $name, 2);
		}

		if ($type !== '' && str_ends_with($name, $type)) {
			$name = substr($name, 0, -strlen($type));
		}

		$arguments = 'test ' . escapeshellarg($type) . ' ' . escapeshellarg($name) . ' -q';
		if (Plugin::isLoaded('Setup')) {
			$arguments .= ' -t Setup';
		}
		if ($plugin) {
			$arguments .= ' -p ' . escapeshellarg($plugin);
		}
		if ($prefix) {
			$arguments .= ' --prefix=' . escapeshellarg($prefix);
		}
		$arguments .= $this->buildOptions($options);

		$command = 'cd ' . escapeshellarg(ROOT) . ' && ' . escapeshellarg($this->getPhpBinary()) . ' bin/cake.php bake ' . $arguments;
		[$output, $return] = $this->executeCommand($command);

		if ($return !== 0) {
			$this->Flash->error('Error code ' . $return . ': ' . print_r($output, true) . ' [' . $command . ']');

			return false;
		}

		$this->Flash->info((string)json_encode($output));

		return true;
	}

	/**
	 * @param string $name
	 * @param string $plugin
	 * @param array<string, mixed> $options
	 *
	 * @return bool
	 */
	public function generateFixture($name, $plugin, array $options = []) {
		$arguments = 'fixture ' . escapeshellarg($name) . ' -q';
		if (Plugin::isLoaded('Setup')) {
			$arguments .= ' -t Setup';
		}
		if ($plugin) {
			$arguments .= ' -p ' . escapeshellarg($plugin);
		}
		$arguments .= $this->buildOptions($options);

		$command = 'cd ' . escapeshellarg(ROOT) . ' && ' . escapeshellarg($this->getPhpBinary()) . ' bin/cake.php bake ' . $arguments;
		if (PHP_SAPI !== 'cli') {
			[$output, $return] = $this->executeCommand($command);
		} else {
			$return = 0;
			$output = 'CLI dry-run: `' . $command . '`';
		}

		if ($return !== 0) {
			$this->Flash->error('Error code ' . $return . ': ' . print_r($output, true) . ' [' . $command . ']');

			return false;
		}

		$this->Flash->info((string)json_encode($output));

		return true;
	}

	/**
	 * @param array<string> $folders
	 *
	 * @return array<string>
	 */
	public function getFiles(array $folders) {
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
					$subIterator = new DirectoryIterator(rtrim($folder, DS) . DS . $subFolder);
					foreach ($subIterator as $subFileInfo) {
						if ($subFileInfo->isFile() && $subFileInfo->getExtension() === 'php') {
							$name = $subFileInfo->getBasename('.php');
							$names[] = $subFolder . '/' . $name;
						}
					}
				}
			}
		}

		return $names;
	}

	/**
	 * @return string
	 */
	protected function getPhpBinary(): string {
		return Configure::read('TestHelper.php') ?: 'php';
	}

	/**
	 * @param array<string, mixed> $options
	 * @return string
	 */
	protected function buildOptions(array $options): string {
		$arguments = '';
		foreach ($options as $key => $value) {
			if (!preg_match('/^[a-z][a-z0-9-]*$/i', $key) || (!is_scalar($value) && $value !== null)) {
				throw new InvalidArgumentException('Invalid bake option: ' . $key);
			}
			if ($value === false || $value === null) {
				continue;
			}
			$arguments .= ' --' . $key;
			if ($value !== true) {
				$arguments .= ' ' . escapeshellarg((string)$value);
			}
		}

		return $arguments;
	}

	/**
	 * @param string $command
	 * @return array{list<string>, int}
	 */
	protected function executeCommand(string $command): array {
		exec($command . ' 2>&1', $output, $code);

		return [$output, $code];
	}

}
