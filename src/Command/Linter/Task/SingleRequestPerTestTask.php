<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use TestHelper\Command\Linter\AbstractLinterTask;

class SingleRequestPerTestTask extends AbstractLinterTask {

	/**
     * @inheritDoc
     */
	public function name(): string {
		return 'single-request-per-test';
	}

	/**
     * @inheritDoc
     */
	public function description(): string {
		return 'Check that controller test methods only have one get() or post() call';
	}

	/**
     * @inheritDoc
     *
     * @return array<int, string>
     */
	public function defaultPaths(): array {
		return ['tests/TestCase/Controller/'];
	}

	/**
     * @inheritDoc
     *
     * @param array<string, mixed> $options
     */
	public function run(ConsoleIo $io, array $options = []): int {
		$paths = $options['paths'] ?? $this->defaultPaths();
		$files = $this->getFiles($paths, '*Test.php');
		$verbose = $options['verbose'] ?? false;
		$issues = 0;

		foreach ($files as $file) {
			$issues += $this->checkFile($io, $file, $verbose);
		}

		return $issues;
	}

	/**
     * Check a single file for multiple get()/post() calls in test methods.
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param string $file File path
     * @param bool $verbose Whether to show verbose output
     *
     * @return int Number of issues found
     */
	protected function checkFile(ConsoleIo $io, string $file, bool $verbose): int {
		$content = file_get_contents($file);
		if ($content === false) {
			return 0;
		}

		$lines = explode("\n", $content);
		$inTest = false;
		$getCount = 0;
		$postCount = 0;
		$lineStart = 0;
		$issues = 0;

		foreach ($lines as $lineNum => $line) {
			if (preg_match('/^\s*public function test/', $line)) {
				$inTest = true;
				$getCount = 0;
				$postCount = 0;
				$lineStart = $lineNum + 1;
			}

			if ($inTest && preg_match('/^\s*public function [^t]/', $line)) {
				$inTest = false;
			}

			if ($inTest && preg_match('/^\s*}\s*$/', $line) && $lineNum > $lineStart) {
				$total = $getCount + $postCount;
				if ($total > 1) {
					$this->outputIssue(
						$io,
						$file,
						$lineStart,
						"Test method has {$total} request calls (get/post) - only 1 allowed per test",
						"Found {$getCount} get() and {$postCount} post() calls",
						$verbose,
					);
					$issues++;
				}
				$inTest = false;
			}

			if ($inTest && preg_match('/\$this->get\(/', $line)) {
				$getCount++;
			}

			if ($inTest && preg_match('/\$this->post\(/', $line)) {
				$postCount++;
			}
		}

		return $issues;
	}

}
