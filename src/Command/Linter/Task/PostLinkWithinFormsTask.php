<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use TestHelper\Command\Linter\AbstractLinterTask;

class PostLinkWithinFormsTask extends AbstractLinterTask {

	/**
     * @inheritDoc
     */
	public function name(): string {
		return 'post-link-within-forms';
	}

	/**
     * @inheritDoc
     */
	public function description(): string {
		return 'Detect postLink() calls within <form> tags - ensure block => true parameter is set';
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
		$fix = $options['fix'] ?? false;
		$issues = 0;

		foreach ($files as $file) {
			$issues += $this->checkFile($io, $file, $verbose, $fix);
		}

		return $issues;
	}

	/**
     * Check a single file for postLink() within forms without block => true.
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
		$insideForm = false;
		$formDepth = 0;
		$modified = false;

		foreach ($lines as $lineNum => $line) {
			// Track when we enter/exit forms
			if (preg_match('/<form\b/i', $line) || preg_match('/\$this->Form->create\(/', $line)) {
				$insideForm = true;
				$formDepth++;
			}

			if (preg_match('/<\/form>/i', $line) || preg_match('/\$this->Form->end\(/', $line)) {
				$formDepth--;
				if ($formDepth <= 0) {
					$insideForm = false;
					$formDepth = 0;
				}
			}

			// Check for postLink() within forms
			if ($insideForm && preg_match('/\$this->Form->postLink\(/', $line)) {
				// Check if this line or nearby lines have 'block' => true
				$hasBlockTrue = false;

				// Check current line and next few lines for 'block' => true
				for ($i = 0; $i < 5 && ($lineNum + $i) < count($lines); $i++) {
					$checkLine = $lines[$lineNum + $i];
					if (preg_match('/[\'"]block[\'"]\s*=>\s*true/', $checkLine)) {
						$hasBlockTrue = true;

						break;
					}
				}

				if (!$hasBlockTrue) {
					$this->outputIssue(
						$io,
						$file,
						$lineNum + 1,
						'postLink() within <form> must have "block" => true to prevent nested forms',
						trim($line),
						$verbose,
					);
					$issues++;

					if ($fix) {
						$lines[$lineNum] = $this->fixPostLink($line);
						$modified = true;
						$io->verbose('  Fixed: ' . trim($lines[$lineNum]));
					}
				}
			}
		}

		if ($modified) {
			file_put_contents($file, implode($eol, $lines));
		}

		return $issues;
	}

	/**
     * Fix a postLink() call by adding 'block' => true parameter.
     *
     * @param string $line The line containing postLink()
     *
     * @return string The fixed line
     */
	protected function fixPostLink(string $line): string {
		// We need to carefully parse postLink() calls which have this signature:
		// postLink(title, url, options)

		// Find the postLink method call
		$pattern = '/(\$this->Form->postLink\s*\()/';
		if (!preg_match($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
			return $line;
		}

		$start = $matches[1][1] + strlen($matches[1][0]);
		$rest = substr($line, $start);

		// Count parameters by tracking brackets and commas
		$depth = 1; // We're inside postLink(
		$commas = 0;
		$paramStarts = [];
		$len = strlen($rest);

		for ($i = 0; $i < $len; $i++) {
			$char = $rest[$i];

			if ($char === '(' || $char === '[') {
				$depth++;
			} elseif ($char === ')' || $char === ']') {
				$depth--;
				if ($depth === 0) {
					// Found the closing paren of postLink
					$beforeClose = substr($line, 0, $start + $i);
					$afterClose = substr($line, $start + $i);

					if ($commas === 1) {
						// Only 2 params, add third
						return $beforeClose . ", ['block' => true]" . $afterClose;
					}
					if ($commas === 2 && isset($paramStarts[2])) {
						// Has 3rd param, add block => true to it
						$thirdParamStart = $paramStarts[2];
						// Check if third param starts with [
						$thirdParam = trim(substr($rest, $thirdParamStart, $i - $thirdParamStart));
						if (strpos($thirdParam, '[') === 0) {
							// It's an array, add our option at the start
							$beforeArray = substr($line, 0, $start + $thirdParamStart);
							$arrayContent = substr($line, $start + $thirdParamStart);

							return $beforeArray . str_replace('[', "['block' => true, ", $arrayContent);
						}
					}

					break;
				}
			} elseif ($char === ',' && $depth === 1) {
				$commas++;
				$paramStarts[$commas] = $i + 1; // Position after comma
			}
		}

		return $line;
	}

}
