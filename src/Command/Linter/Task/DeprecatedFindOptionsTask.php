<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use TestHelper\Command\Linter\AbstractLinterTask;

class DeprecatedFindOptionsTask extends AbstractLinterTask {

	/**
     * @inheritDoc
     */
	public function name(): string {
		return 'deprecated-find-options';
	}

	/**
     * @inheritDoc
     */
	public function description(): string {
		return 'Detect deprecated $options array in find() calls - use named parameters instead';
	}

	/**
     * @inheritDoc
     */
	public function supportsAutoFix(): bool {
		return true;
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
		$verbose = $options['verbose'] ?? false;
		$fix = $options['fix'] ?? false;
		$issues = 0;

		foreach ($files as $file) {
			$issues += $this->checkFile($io, $file, $verbose, $fix);
		}

		return $issues;
	}

	/**
     * Check a single file for deprecated find() options usage.
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param string $file File path
     * @param bool $verbose Whether to show verbose output
     * @param bool $fix Whether to auto-fix issues
     *
     * @return int Number of issues found
     */
	protected function checkFile(ConsoleIo $io, string $file, bool $verbose, bool $fix): int {
		$content = file_get_contents($file);
		if ($content === false) {
			return 0;
		}

		// Detect line ending style
		$eol = "\n";
		if (strpos($content, "\r\n") !== false) {
			$eol = "\r\n";
		} elseif (strpos($content, "\r") !== false) {
			$eol = "\r";
		}

		$lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
		$issues = 0;
		$modified = false;

		foreach ($lines as $lineNum => $line) {
			// Check for ->find('all', $options) or ->find('list', $options) patterns
			// This matches: ->find('type', $var) or ->find('type', [...])
			// But excludes: ->find('all') with no second parameter
			if (preg_match('/->find\(\s*[\'"](?:all|list|threaded|first)\s*[\'"],\s*(\$\w+|\[)/', $line)) {
				$this->outputIssue(
					$io,
					$file,
					$lineNum + 1,
					'Use named parameters like ...["conditions" => ...] instead of deprecated $options array in find()',
					trim($line),
					$verbose,
				);
				$issues++;

				if ($fix) {
					$lines[$lineNum] = $this->fixFindOptions($line);
					$modified = true;
					$io->verbose('  Fixed: ' . trim($lines[$lineNum]));
				}
			}
		}

		if ($modified) {
			file_put_contents($file, implode($eol, $lines));
		}

		return $issues;
	}

	/**
     * Fix a find() call to use spread operator for options.
     *
     * @param string $line The line containing find()
     *
     * @return string The fixed line
     */
	protected function fixFindOptions(string $line): string {
		// Pattern: ->find('type', $options) => ->find('type', ...$options)
		// Pattern: ->find('type', [...]) => ->find('type', ...[...])

		// Match ->find('type', SECOND_PARAM)
		if (preg_match('/(->find\(\s*[\'"](?:all|list|threaded|first)\s*[\'"],\s*)(\$\w+|\[)/', $line, $matches, PREG_OFFSET_CAPTURE)) {
			$before = $matches[1][0];
			$beforeOffset = $matches[1][1];
			$secondParamOffset = $matches[2][1];

			// Insert '...' right before the second parameter
			$result = substr($line, 0, $secondParamOffset) . '...' . substr($line, $secondParamOffset);

			return $result;
		}

		return $line;
	}

}
