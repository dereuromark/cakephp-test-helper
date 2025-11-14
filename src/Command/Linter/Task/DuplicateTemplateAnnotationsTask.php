<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use TestHelper\Command\Linter\AbstractLinterTask;

class DuplicateTemplateAnnotationsTask extends AbstractLinterTask {

	/**
     * @inheritDoc
     */
	public function name(): string {
		return 'duplicate-template-annotations';
	}

	/**
     * @inheritDoc
     */
	public function description(): string {
		return 'Check for duplicate @var annotations for the same variable in template files';
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
		$verbose = $options['verbose'] ?? false;
		$issues = 0;

		foreach ($files as $file) {
			$content = file_get_contents($file);
			if ($content === false) {
				continue;
			}

			// Find all @var annotations with their variable names
			$matches = [];
			preg_match_all('/^\s*\*?\s*@var\s+([^\s]+)\s+(\$\w+)/m', $content, $matches, PREG_OFFSET_CAPTURE);

			if (empty($matches[0])) {
				continue;
			}

			// Track variable annotations: varName => [['type' => ..., 'line' => ..., 'full' => ...], ...]
			$varAnnotations = [];

			foreach ($matches[0] as $index => $match) {
				$line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
				$type = $matches[1][$index][0];
				$varName = $matches[2][$index][0];
				$fullMatch = trim($match[0]);

				if (!isset($varAnnotations[$varName])) {
					$varAnnotations[$varName] = [];
				}

				$varAnnotations[$varName][] = [
					'type' => $type,
					'line' => $line,
					'full' => $fullMatch,
				];
			}

			// Report duplicates
			foreach ($varAnnotations as $varName => $annotations) {
				if (count($annotations) > 1) {
					$lines = array_column($annotations, 'line');
					$types = array_column($annotations, 'type');
					$uniqueTypes = array_unique($types);

					$typeInfo = count($uniqueTypes) > 1
						? 'with different types: ' . implode(', ', $uniqueTypes)
						: 'with same type: ' . $types[0];

					$this->outputIssue(
						$io,
						$file,
						$lines[0],
						"Variable {$varName} has " . count($annotations) . ' @var annotations ' . $typeInfo,
						'Lines: ' . implode(', ', $lines),
						$verbose,
					);
					$issues++;
				}
			}
		}

		return $issues;
	}

}
