<?php

namespace TestHelper\Utility\Association;

use Cake\Core\App;
use Cake\Core\Plugin;
use DirectoryIterator;

/**
 * Enumerates in-scope table aliases: app + first-party plugins by default, vendor opt-in.
 */
class TableResolver {

	/**
	 * @param bool $includeVendor Include vendor plugin tables (read-only info).
	 * @return array<string> Table aliases, plugin-dotted where relevant (e.g. 'Posts', 'Sandbox.Animals').
	 */
	public function tables(bool $includeVendor = false): array {
		$aliases = [];

		foreach (App::classPath('Model/Table') as $path) {
			$aliases = array_merge($aliases, $this->scan($path, null));
		}

		foreach (Plugin::loaded() as $plugin) {
			if (!$includeVendor && $this->isVendor($plugin)) {
				continue;
			}

			foreach (App::classPath('Model/Table', $plugin) as $path) {
				$aliases = array_merge($aliases, $this->scan($path, $plugin));
			}
		}

		sort($aliases);

		return array_values(array_unique($aliases));
	}

	/**
	 * @param string $plugin
	 * @return bool
	 */
	public function isVendor(string $plugin): bool {
		$path = Plugin::path($plugin);

		return str_contains($path, DS . 'vendor' . DS);
	}

	/**
	 * @param string $path
	 * @param string|null $plugin
	 * @return array<string>
	 */
	protected function scan(string $path, ?string $plugin): array {
		if (!is_dir($path)) {
			return [];
		}

		$aliases = [];
		$iterator = new DirectoryIterator($path);
		foreach ($iterator as $file) {
			if ($file->isDot() || $file->isDir() || !preg_match('/^(\w+)Table\.php$/', (string)$file, $matches)) {
				continue;
			}

			$aliases[] = ($plugin ? $plugin . '.' : '') . $matches[1];
		}

		return $aliases;
	}

}
