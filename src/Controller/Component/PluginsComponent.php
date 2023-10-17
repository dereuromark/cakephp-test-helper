<?php

namespace TestHelper\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Plugin;
use Cake\Core\PluginInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use RuntimeException;

class PluginsComponent extends Component {

	/**
	 * @var array<string>
	 */
	protected array $irrelevant = [
		'middleware',
		'services',
	];

	/**
	 * @return array<string>
	 */
	public function hooks(): array {
		$hooks = PluginInterface::VALID_HOOKS;
		sort($hooks);

		return $hooks;
	}

	/**
	 * @param array<string> $pluginNames
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

		$classPath = Plugin::classPath($pluginName);
		$result['consoleExists'] = $this->consoleExists($classPath);

		$result['routesExists'] = $this->routesExists($configPath);
		$result['middlewareExists'] = null;
		$result['servicesExists'] = null;

		$pluginClassPath = $classPath . 'Plugin.php';
		$pluginClassExists = file_exists($pluginClassPath);
		$result['pluginClass'] = $pluginClassPath;
		$result['pluginClassExists'] = $pluginClassExists;

		$result += $this->addPluginConfig($pluginClassPath, $pluginClassExists);

		foreach ($this->hooks() as $hook) {
			$existing = [];
			if (!empty($result[$hook . 'Exists'])) {
				$existing[] = 'file';
			}
			if (!empty($result[$hook . 'Hook'])) {
				$existing[] = 'callback';
			}
			$result[$hook] = $existing ?: null;
		}

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

		$parts = $this->hooks();
		if (!$pluginClassExists) {
			foreach ($parts as $part) {
				$result[$part . 'Enabled'] = null;
			}

			return $result;
		}

		$pluginContent = file_get_contents($pluginClassPath);
		if ($pluginContent === false) {
			throw new RuntimeException('Cannot read file: ' . $pluginClassPath);
		}

		foreach ($parts as $part) {
			preg_match('#protected \$' . $part . 'Enabled\s*=\s*(\w+);#', $pluginContent, $matches);
			$enabled = null;
			if ($matches) {
				$enabled = trim($matches[1]) === 'false' ? false : true;
			}

			$result[$part . 'Enabled'] = $enabled;
		}

		foreach ($parts as $part) {
			$exists = (bool)preg_match('#public function ' . $part . '\(#', $pluginContent, $matches);
			$result[$part . 'Hook'] = $exists;
		}

		return $result;
	}

	/**
	 * Only check file.
	 *
	 * @param string $configPath
	 *
	 * @return bool
	 */
	protected function bootstrapExists(string $configPath): bool {
		$filePath = $configPath . 'bootstrap.php';
		if (!file_exists($filePath)) {
			return false;
		}

		$fileContent = file_get_contents($filePath);
		if ($fileContent === false) {
			throw new RuntimeException('Cannot read file: ' . $filePath);
		}

		return trim($fileContent) !== '<?php';
	}

	/**
	 * Only check file.
	 *
	 * @param string $configPath
	 *
	 * @return bool
	 */
	protected function routesExists(string $configPath): bool {
		$fileExists = file_exists($configPath . 'routes.php');
		if (!$fileExists) {
			return false;
		}

		$fileContent = file_get_contents($configPath . 'routes.php');

		if ($fileContent && trim($fileContent) !== '<?php') {
			return true;
		}

		return false;
	}

	/**
	 * Only check files.
	 *
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
			$regexIterator = new RegexIterator($recursiveIterator, '/\.php$/i', RegexIterator::GET_MATCH);

			foreach ($regexIterator as $match) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $plugin
	 * @param string|null $content
	 * @param array $result
	 *
	 * @return string
	 */
	public function adjustPluginClass(string $plugin, ?string $content, array $result): string {
		if ($content === null) {
			$content = <<<TXT
<?php

namespace $plugin;

use Cake\Core\BasePlugin;

class Plugin extends BasePlugin {
}

TXT;
		}

		$parts = $this->hooks();
		rsort($parts);

		foreach ($parts as $part) {
			if ($result[$part . 'Exists'] && $result[$part . 'Enabled'] === false) {
				$content = (string)preg_replace('#protected \$' . $part . 'Enabled = false;#', 'protected $' . $part . 'Enabled = true;', $content);
			}
			if (empty($result[$part]) && $result[$part . 'Enabled'] === null && !in_array($part, $this->irrelevant, true)) {
				$content = $this->addProperty($content, $part, $result);
			}
		}

		return $content;
	}

	/**
	 * @param string $content
	 * @param string $part
	 * @param array $result
	 *
	 * @return string
	 */
	protected function addProperty(string $content, string $part, array $result): string {
		$pieces = explode(PHP_EOL, $content);
		$indentation = $this->detectIndentation($pieces);

		$pos = null;
		$count = 0;
		foreach ($pieces as $i => $piece) {
			if (strpos($piece, 'class Plugin extends BasePlugin') === false) {
				continue;
			}

			$pos = $i;
		}

		if ($pos) {
			if (trim($pieces[$pos + 1]) === '{') {
				$pos++;
			}

			// Now set pointer to after this class start
			$pos++;

			$add = [
				$indentation . '/**',
				$indentation . ' * @var bool',
				$indentation . ' */',
				$indentation . 'protected $' . $part . 'Enabled = ' . ($result[$part . 'Exists'] ? 'true' : 'false') . ';',
			];
			if ($count === 0 && trim($pieces[$pos + 1]) !== '{') {
				array_unshift($add, '');
			}

			array_splice($pieces, $pos, 0, $add);
			$count++;
		}

		return implode(PHP_EOL, $pieces);
	}

	/**
	 * @param array $pieces
	 *
	 * @return string
	 */
	protected function detectIndentation(array $pieces): string {
		$indentation = '    ';

		foreach ($pieces as $piece) {
			if (!$piece || !preg_match('/^(\s+)/', $piece, $matches)) {
				continue;
			}

			if (substr($matches[1], 0, 1) !== "\t") {
				continue;
			}

			return "\t";
		}

		return $indentation;
	}

}
