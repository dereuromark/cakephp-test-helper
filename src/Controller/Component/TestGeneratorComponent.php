<?php

namespace TestHelper\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Plugin;

/**
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class TestGeneratorComponent extends Component {

	/**
	 * @var array
	 */
	public $components = [
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
		if (strpos($name, '/') !== false) {
			[$prefix, $name] = explode('/', $name, 2);
		}

		if (preg_match("#$type$#", $name, $matches)) {
			$name = substr($name, 0, -strlen($type));
		}

		$arguments = 'test ' . $type . ' ' . $name . ' -q';
		if (Plugin::isLoaded('Setup')) {
			$arguments .= ' -t Setup';
		}
		if ($plugin) {
			$arguments .= ' -p ' . $plugin;
		}
		if ($prefix) {
			$arguments .= ' --prefix=' . $prefix;
		}
		foreach ($options as $key => $option) {
			$arguments .= '--' . $key . ' ' . $option;
		}

		$command = 'cd ' . ROOT . ' && php bin/cake.php bake ' . $arguments;
		exec($command, $output, $return);

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
		$arguments = 'fixture ' . $name . ' -q';
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
		if (PHP_SAPI !== 'cli') {
			exec($command, $output, $return);
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
			$folderContent = $this->read($folder);

			foreach ($folderContent[1] as $file) {
				$name = pathinfo($file, PATHINFO_FILENAME);
				$names[] = $name;
			}

			foreach ($folderContent[0] as $subFolder) {
				$folderContent = $this->read($folder . $subFolder);

				foreach ($folderContent[1] as $file) {
					$name = pathinfo($file, PATHINFO_FILENAME);
					$names[] = $subFolder . '/' . $name;
				}
			}
		}

		return $names;
	}

	/**
	 * Lists the visible sub folders and files of a directory, sorted by name.
	 *
	 * @param string $path
	 *
	 * @return array<array<string>> Folders first, files second.
	 */
	protected function read(string $path): array {
		$folders = [];
		$files = [];
		if (!is_dir($path)) {
			return [$folders, $files];
		}

		$path = rtrim($path, DS) . DS;
		foreach ((array)scandir($path) as $name) {
			$name = (string)$name;
			if ($name === '' || $name[0] === '.') {
				continue;
			}
			if (is_dir($path . $name)) {
				$folders[] = $name;

				continue;
			}
			$files[] = $name;
		}

		return [$folders, $files];
	}

}
