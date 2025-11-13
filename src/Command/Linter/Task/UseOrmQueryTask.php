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
		return 'Check for incorrect Query imports - use specific query types like SelectQuery instead';
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
				// Check for generic Query imports (Database\Query or ORM\Query)
				if (preg_match('/^\s*use\s+Cake\\\\(Database|ORM)\\\\Query\s*;/', $line)) {
					$this->outputIssue(
						$io,
						$file,
						$lineNumber + 1,
						'Use specific query type like "use Cake\ORM\Query\SelectQuery;" instead',
						trim($line),
					);
					$issues++;
				}
			}
		}

		return $issues;
	}

}
