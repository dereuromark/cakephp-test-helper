<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use TestHelper\Command\Linter\AbstractLinterTask;

class NoMixedInTemplatesTask extends AbstractLinterTask {

	/**
     * @inheritDoc
     */
	public function name(): string {
		return 'no-mixed-in-templates';
	}

	/**
     * @inheritDoc
     */
	public function description(): string {
		return 'Check for @var mixed declarations in template files - templates must have specific type annotations';
	}

	/**
     * @inheritDoc
     *
     * @return array<int, string>
     */
	public function defaultPaths(): array {
		return ['templates/'];
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

			$matches = [];
			preg_match_all('/^\s*\*?\s*@var\s+mixed\s+(\$\w+)/m', $content, $matches, PREG_OFFSET_CAPTURE);

			if (!empty($matches[0])) {
				foreach ($matches[0] as $index => $match) {
					$line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
					$varName = $matches[1][$index][0];
					$this->outputIssue(
						$io,
						$file,
						$line,
						"Template variable {$varName} must have a specific type annotation, not 'mixed'",
						trim($match[0]),
					);
					$issues++;
				}
			}
		}

		return $issues;
	}

}
