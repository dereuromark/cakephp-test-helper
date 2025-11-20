<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use TestHelper\Command\Linter\AbstractLinterTask;

class PluginInstallerTask extends AbstractLinterTask {

	/**
	 * @inheritDoc
	 */
	public function name(): string {
		return 'plugin-installer';
	}

	/**
	 * @inheritDoc
	 */
	public function description(): string {
		return 'Check that cakephp/plugin-installer is installed in composer.json (application only)';
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
	public function supportsPluginMode(): bool {
		return false;
	}

	/**
	 * @inheritDoc
	 *
	 * @param array<string, mixed> $options
	 */
	public function run(ConsoleIo $io, array $options = []): int {
		$composerJsonPath = $this->resolvePath('composer.json');
		$verbose = $options['verbose'] ?? false;

		if (!file_exists($composerJsonPath)) {
			$this->outputIssue(
				$io,
				$composerJsonPath,
				0,
				'composer.json not found',
				null,
				$verbose,
			);

			return 1;
		}

		$content = file_get_contents($composerJsonPath);
		if ($content === false) {
			$this->outputIssue(
				$io,
				$composerJsonPath,
				0,
				'Could not read composer.json',
				null,
				$verbose,
			);

			return 1;
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

			return 1;
		}

		// Check in both require and require-dev
		$hasPluginInstaller = false;
		if (isset($composerData['require']['cakephp/plugin-installer'])) {
			$hasPluginInstaller = true;
		}
		if (isset($composerData['require-dev']['cakephp/plugin-installer'])) {
			$hasPluginInstaller = true;
		}

		if (!$hasPluginInstaller) {
			$this->outputIssue(
				$io,
				$composerJsonPath,
				0,
				'cakephp/plugin-installer is not installed. Run: composer require cakephp/plugin-installer',
				null,
				$verbose,
			);

			return 1;
		}

		return 0;
	}

}
