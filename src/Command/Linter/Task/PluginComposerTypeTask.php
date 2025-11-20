<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use TestHelper\Command\Linter\AbstractLinterTask;

class PluginComposerTypeTask extends AbstractLinterTask {

	/**
	 * @inheritDoc
	 */
	public function name(): string {
		return 'plugin-composer-type';
	}

	/**
	 * @inheritDoc
	 */
	public function description(): string {
		return 'Check that composer.json has "type": "cakephp-plugin" (plugins only)';
	}

	/**
	 * @inheritDoc
	 *
	 * @return array<int, string>
	 */
	public function defaultPaths(): array {
		return ['composer.json'];
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

		foreach ($paths as $path) {
			$composerJsonPath = $this->resolvePath($path);
			if (!file_exists($composerJsonPath)) {
				continue;
			}

			$content = file_get_contents($composerJsonPath);
			if ($content === false) {
				continue;
			}

			$composerData = json_decode($content, true);
			if (!is_array($composerData)) {
				$this->outputIssue(
					$io,
					$composerJsonPath,
					0,
					'composer.json is not valid JSON',
					null,
					$verbose,
				);
				$issues++;

				continue;
			}

			// Check for type field
			if (!isset($composerData['type'])) {
				$this->outputIssue(
					$io,
					$composerJsonPath,
					0,
					'Missing "type" field in composer.json. Plugins must specify "type": "cakephp-plugin"',
					null,
					$verbose,
				);
				$issues++;

				continue;
			}

			// Check that type is cakephp-plugin
			if ($composerData['type'] !== 'cakephp-plugin') {
				$this->outputIssue(
					$io,
					$composerJsonPath,
					0,
					'Plugin composer.json must have "type": "cakephp-plugin", found: "' . $composerData['type'] . '"',
					'"type": "' . $composerData['type'] . '"',
					$verbose,
				);
				$issues++;
			}
		}

		return $issues;
	}

}
