<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use TestHelper\Command\Linter\AbstractLinterTask;

class UseBaseMigrationTask extends AbstractLinterTask {

	/**
     * @inheritDoc
     */
	public function name(): string {
		return 'use-base-migration';
	}

	/**
     * @inheritDoc
     */
	public function description(): string {
		return 'Check for deprecated AbstractMigration and AbstractSeed usage - should use BaseMigration and BaseSeed';
	}

	/**
     * @inheritDoc
     *
     * @return array<int, string>
     */
	public function defaultPaths(): array {
		return ['config/Migrations/', 'config/Seeds/'];
	}

	/**
     * @inheritDoc
     *
     * @param array<string, mixed> $options
     */
	public function run(ConsoleIo $io, array $options = []): int {
		$paths = $options['paths'] ?? $this->defaultPaths();
		$files = $this->getFiles($paths, '*.php');
		$issues = 0;

		foreach ($files as $file) {
			$content = file_get_contents($file);
			if ($content === false) {
				continue;
			}

			$lines = explode("\n", $content);

			foreach ($lines as $lineNumber => $line) {
				// Check for deprecated AbstractMigration
				if (preg_match('/use\s+Migrations\\\\AbstractMigration\s*;/', $line)) {
					$this->outputIssue(
						$io,
						$file,
						$lineNumber + 1,
						'Use BaseMigration instead of deprecated AbstractMigration',
						trim($line),
					);
					$issues++;
				}

				// Check for deprecated AbstractSeed
				if (preg_match('/use\s+Migrations\\\\AbstractSeed\s*;/', $line)) {
					$this->outputIssue(
						$io,
						$file,
						$lineNumber + 1,
						'Use BaseSeed instead of deprecated AbstractSeed',
						trim($line),
					);
					$issues++;
				}

				// Check class extends for AbstractMigration
				if (preg_match('/class\s+\w+\s+extends\s+AbstractMigration/', $line)) {
					$this->outputIssue(
						$io,
						$file,
						$lineNumber + 1,
						'Class should extend BaseMigration instead of AbstractMigration',
						trim($line),
					);
					$issues++;
				}

				// Check class extends for AbstractSeed
				if (preg_match('/class\s+\w+\s+extends\s+AbstractSeed/', $line)) {
					$this->outputIssue(
						$io,
						$file,
						$lineNumber + 1,
						'Class should extend BaseSeed instead of AbstractSeed',
						trim($line),
					);
					$issues++;
				}
			}
		}

		return $issues;
	}

}
