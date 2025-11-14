<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter;

use Cake\Console\ConsoleIo;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

abstract class AbstractLinterTask implements LinterTaskInterface {

	/**
     * @inheritDoc
     */
	public function supportsAutoFix(): bool {
		return false;
	}

	/**
     * @inheritDoc
     */
	public function supportsPluginMode(): bool {
		return true;
	}

	/**
     * Get files matching pattern in given paths
     *
     * @param array<string> $paths Paths to scan
     * @param string $pattern File pattern to match
     *
     * @return array<string>
     */
	protected function getFiles(array $paths, string $pattern = '*.php'): array {
		$files = [];

		foreach ($paths as $path) {
			$fullPath = $this->resolvePath($path);
			if (!is_dir($fullPath)) {
				continue;
			}

			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($fullPath),
			);

			foreach ($iterator as $file) {
				assert($file instanceof SplFileInfo);
				if (!$file->isFile() || !fnmatch($pattern, $file->getFilename())) {
					continue;
				}
				$files[] = $file->getPathname();
			}
		}

		return $files;
	}

	/**
     * Resolve a path relative to ROOT
     *
     * @param string $path Path to resolve
     *
     * @return string
     */
	protected function resolvePath(string $path): string {
		if (strpos($path, '/') === 0) {
			return $path;
		}

		return ROOT . DS . $path;
	}

	/**
     * Get relative path from ROOT
     *
     * @param string $path Full path
     *
     * @return string
     */
	protected function getRelativePath(string $path): string {
		return str_replace(ROOT . DS, '', $path);
	}

	/**
     * Output an issue found
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param string $file File path
     * @param int $line Line number
     * @param string $issue Issue description
     * @param string|null $context Optional context
     * @param bool $verbose Whether to show verbose output with full paths
     *
     * @return void
     */
	protected function outputIssue(
		ConsoleIo $io,
		string $file,
		int $line,
		string $issue,
		?string $context = null,
		bool $verbose = false,
	): void {
		// Use full path in verbose mode for better terminal clickability
		// Relative path otherwise for cleaner output in normal usage
		$path = $verbose ? $file : $this->getRelativePath($file);
		$location = $line ? "{$path}:{$line}" : $path;

		$io->out('');
		$io->out("<comment>{$location}</comment>");
		$io->out("  {$issue}");
		if ($context !== null) {
			$io->out("  Found: {$context}");
		}
	}

}
