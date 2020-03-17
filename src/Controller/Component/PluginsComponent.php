<?php

namespace TestHelper\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Plugin;
use Cake\Core\PluginInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

class PluginsComponent extends Component {

	/**
	 * @return string[]
	 */
	public function hooks(): array {
		return PluginInterface::VALID_HOOKS;
	}

	/**
	 * @param string[] $pluginNames
	 * @return array
	 */
	public function check(array $pluginNames): array {
		$result = [
		];

		foreach ($pluginNames as $pluginName) {
			$result[$pluginName] = $this->checkPlugin($pluginName);
		}

		return $result;
	}

	/**
	 * @param string $pluginName
	 *
	 * @return array
	 */
	protected function checkPlugin(string $pluginName): array {
		$result = [];

		$configPath = Plugin::configPath($pluginName);
		$result['bootstrapExists'] = $this->bootstrapExists($configPath);
		$result['routesExists'] = $this->routesExists($configPath);

		$classPath = Plugin::classPath($pluginName);
		$result['consoleExists'] = $this->consoleExists($classPath);

		$pluginClassPath = $classPath . 'Plugin.php';
		$pluginClassExists = file_exists($pluginClassPath);

		$result['middlewareExists'] = $pluginClassExists && $this->middlewareExists($pluginClassPath);

		$result['pluginClass'] = $pluginClassExists;
		$result += $this->addPluginConfig($pluginClassPath, $pluginClassExists);

		return $result;
	}

	/**
	 * @param string $pluginClassPath
	 * @param bool $pluginClassExists
	 *
	 * @return array
	 */
	protected function addPluginConfig(string $pluginClassPath, bool $pluginClassExists): array {
		$result = [];

		if (!$pluginClassExists) {
			return $result;
		}

		$pluginContent = file_get_contents($pluginClassPath);

		$parts = $this->hooks();
		foreach ($parts as $part) {
			preg_match('#protected \$' . $part . 'Enabled\s*=\s*(\w+);#', $pluginContent, $matches);
			$enabled = null;
			if ($matches) {
				$enabled = trim($matches[1]) === 'false' ? false : true;
			}

			$result[$part . 'Enabled'] = $enabled;
		}

		return $result;
	}

	/**
	 * @param string $configPath
	 *
	 * @return bool
	 */
	protected function bootstrapExists(string $configPath): bool {
		if (!file_exists($configPath . 'bootstrap.php')) {
			return false;
		}

		$fileContent = file_get_contents($configPath . 'bootstrap.php');

		return trim($fileContent) !== '<?php';
	}

	/**
	 * @param string $configPath
	 *
	 * @return bool
	 */
	protected function routesExists(string $configPath): bool {
		if (!file_exists($configPath . 'routes.php')) {
			return false;
		}

		$fileContent = file_get_contents($configPath . 'routes.php');

		return trim($fileContent) !== '<?php';
	}

	/**
	 * @param string $classPath
	 *
	 * @return bool
	 */
	protected function consoleExists(string $classPath): bool {
		$dirs = [
			'Command',
			'Shell',
		];
		foreach ($dirs as $dir) {
			if (!is_dir($classPath . $dir)) {
				continue;
			}

			$directoryIterator = new RecursiveDirectoryIterator($classPath . $dir);
			$recursiveIterator = new RecursiveIteratorIterator($directoryIterator);
			$regexIterator = new RegexIterator($recursiveIterator, '/\.php$/i', RecursiveRegexIterator::GET_MATCH);

			foreach ($regexIterator as $match) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $pluginClassPath
	 *
	 * @return bool
	 */
	protected function middlewareExists(string $pluginClassPath): bool {
		$pluginContent = file_get_contents($pluginClassPath);

		return (bool)preg_match('#public function middleware\(MiddlewareQueue \$middleware#', $pluginContent);
	}

}
