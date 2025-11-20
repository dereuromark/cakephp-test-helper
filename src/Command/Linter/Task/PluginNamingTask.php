<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use TestHelper\Command\Linter\AbstractLinterTask;

class PluginNamingTask extends AbstractLinterTask {

	/**
	 * @inheritDoc
	 */
	public function name(): string {
		return 'plugin-naming';
	}

	/**
	 * @inheritDoc
	 */
	public function description(): string {
		return 'Check that Plugin class is named {PluginName}Plugin, not just Plugin (plugins only)';
	}

	/**
	 * @inheritDoc
	 *
	 * @return array<int, string>
	 */
	public function defaultPaths(): array {
		return ['src/'];
	}

	/**
	 * @inheritDoc
	 */
	public function requiresPluginMode(): bool {
		return true;
	}

	/**
	 * @inheritDoc
	 *
	 * @param array<string, mixed> $options
	 */
	public function run(ConsoleIo $io, array $options = []): int {
		$paths = $options['paths'] ?? $this->defaultPaths();
		$verbose = $options['verbose'] ?? false;
		$issues = 0;

		// Look for Plugin.php files in the src/ directory
		foreach ($paths as $path) {
			$fullPath = $this->resolvePath($path);
			if (!is_dir($fullPath)) {
				continue;
			}

			$pluginFile = $fullPath . DS . 'Plugin.php';
			if (!file_exists($pluginFile)) {
				continue;
			}

			$content = file_get_contents($pluginFile);
			if ($content === false) {
				continue;
			}

			// Check if the class is named just "Plugin"
			if (preg_match('/^class\s+Plugin\s+/m', $content)) {
				// Extract the namespace to determine expected plugin name
				$namespace = '';
				if (preg_match('/^namespace\s+([^;]+);/m', $content, $matches)) {
					$namespace = $matches[1];
				}

				// Expected class name should be {PluginName}Plugin
				$expectedClassName = $namespace ? basename($namespace) . 'Plugin' : '{PluginName}Plugin';

				$line = $this->getLineNumber($content, '/^class\s+Plugin\s+/m');
				$this->outputIssue(
					$io,
					$pluginFile,
					$line,
					"Plugin class must be named '{$expectedClassName}', not 'Plugin'",
					'class Plugin',
					$verbose,
				);
				$issues++;
			}
		}

		return $issues;
	}

	/**
	 * Get line number for a pattern match
	 *
	 * @param string $content File content
	 * @param string $pattern Regex pattern
	 *
	 * @return int
	 */
	protected function getLineNumber(string $content, string $pattern): int {
		$matches = [];
		if (preg_match($pattern, $content, $matches, PREG_OFFSET_CAPTURE)) {
			return substr_count(substr($content, 0, $matches[0][1]), "\n") + 1;
		}

		return 0;
	}

}
