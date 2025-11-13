<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use TestHelper\Command\Linter\AbstractLinterTask;

class UseOrmQueryTask extends AbstractLinterTask {

	/**
     * @inheritDoc
     */
	public function name(): string {
		return 'use-orm-query';
	}

	/**
     * @inheritDoc
     */
	public function description(): string {
		return 'Check for incorrect "use Cake\Database\Query;" which should be "use Cake\ORM\Query\SelectQuery;"';
	}

	/**
     * @inheritDoc
     *
     * @return array<int, string>
     */
	public function defaultPaths(): array {
		return ['src/', 'tests/', 'plugins/'];
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
				// Check for the incorrect import
				if (preg_match('/^\s*use\s+Cake\\\\Database\\\\Query\s*;/', $line)) {
					$this->outputIssue(
						$io,
						$file,
						$lineNumber + 1,
						'Use "use Cake\ORM\Query\SelectQuery;" instead of "use Cake\Database\Query;"',
						trim($line),
					);
					$issues++;
				}
			}
		}

		return $issues;
	}

}
