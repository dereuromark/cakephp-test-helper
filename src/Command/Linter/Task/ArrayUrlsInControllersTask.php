<?php

declare(strict_types=1);

namespace TestHelper\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use Cake\Http\ServerRequest;
use Cake\Http\UriFactory;
use Cake\Routing\Router;
use Exception;
use TestHelper\Command\Linter\AbstractLinterTask;

class ArrayUrlsInControllersTask extends AbstractLinterTask {

	/**
     * @inheritDoc
     */
	public function name(): string {
		return 'array-urls-in-controllers';
	}

	/**
     * @inheritDoc
     */
	public function description(): string {
		return 'Check controller files for string URLs in redirect() - enforce array format';
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
		return ['src/Controller/'];
	}

	/**
     * @inheritDoc
     *
     * @param array<string, mixed> $options
     */
	public function run(ConsoleIo $io, array $options = []): int {
		$paths = $options['paths'] ?? $this->defaultPaths();
		$files = $this->getFiles($paths, '*Controller.php');
		$verbose = $options['verbose'] ?? false;
		$fix = $options['fix'] ?? false;
		$issues = 0;

		foreach ($files as $file) {
			$issues += $this->checkFile($io, $file, $verbose, $fix);
		}

		return $issues;
	}

	/**
     * Check a single file for string URLs in redirect() calls.
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
			// Check for $this->redirect('/some/url') or return $this->redirect('/some/url')
			// Skip concatenated strings (e.g., '/path/' . $var) as they're too complex to auto-fix
			if (preg_match('/\$this->redirect\(\s*([\'"])\/[^\'"]*\1\s*\./', $line)) {
				// Skip concatenated URLs
				continue;
			}

			if (preg_match('/\$this->redirect\(\s*([\'"])\//', $line, $matches)) {
				$this->outputIssue(
					$io,
					$file,
					$lineNum + 1,
					'Use array format for redirect() URL instead of string',
					trim($line),
					$verbose,
				);
				$issues++;

				if ($fix) {
					$lines[$lineNum] = $this->fixUrlToArray($line);
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
     * Convert a string URL to array format.
     *
     * @param string $line The line containing the URL
     *
     * @return string The fixed line
     */
	protected function fixUrlToArray(string $line): string {
		// Extract the URL string - match the full quoted string including both quotes
		// Capture leading whitespace to preserve indentation
		$pattern = '/(.*?)(\$this->redirect\(\s*)([\'"])([^\'"]*)\3/';
		if (!preg_match($pattern, $line, $matches, PREG_OFFSET_CAPTURE)) {
			return $line;
		}

		$leadingWhitespace = $matches[1][0];
		$methodCall = $matches[2][0];
		$url = $matches[4][0];

		// Calculate positions
		$matchEnd = $matches[0][1] + strlen($matches[0][0]);
		$after = substr($line, $matchEnd);

		// Parse the URL into parts
		$urlArray = $this->parseUrl($url);

		return $leadingWhitespace . $methodCall . $urlArray . $after;
	}

	/**
     * Parse a CakePHP URL string into array format using Router.
     *
     * @param string $url The URL string (e.g., '/controller/action/param')
     *
     * @return string The array representation as a string
     */
	protected function parseUrl(string $url): string {
		// Extract query string if present
		$queryString = null;
		if (strpos($url, '?') !== false) {
			[$url, $queryString] = explode('?', $url, 2);
		}

		// Use Router to parse the URL properly
		try {
			$request = (new ServerRequest())->withUri((new UriFactory())->createUri($url));
			$params = Router::getRouteCollection()->parseRequest($request);

			// Build array from parsed parameters
			$arrayParts = [];

			// Add controller if present
			if (isset($params['controller'])) {
				$arrayParts[] = "'controller' => '" . $params['controller'] . "'";
			}

			// Add action if present
			if (isset($params['action'])) {
				$arrayParts[] = "'action' => '" . $params['action'] . "'";
			}

			// Add plugin if present
			if (isset($params['plugin']) && $params['plugin']) {
				$arrayParts[] = "'plugin' => '" . $params['plugin'] . "'";
			}

			// Add prefix if present
			if (isset($params['prefix']) && $params['prefix']) {
				$arrayParts[] = "'prefix' => '" . $params['prefix'] . "'";
			}

			// Add pass parameters (unnamed params)
			if (isset($params['pass']) && is_array($params['pass'])) {
				foreach ($params['pass'] as $pass) {
					if (is_numeric($pass)) {
						$arrayParts[] = $pass;
					} else {
						$arrayParts[] = "'{$pass}'";
					}
				}
			}

			// Add query string parameters
			if ($queryString) {
				parse_str($queryString, $queryParams);
				if (!empty($queryParams)) {
					$queryParts = [];
					foreach ($queryParams as $key => $value) {
						if (is_array($value)) {
							continue; // Skip array values for simplicity
						}
						$queryParts[] = "'{$key}' => '" . (string)$value . "'";
					}
					$arrayParts[] = "'?' => [" . implode(', ', $queryParts) . ']';
				}
			}

			return '[' . implode(', ', $arrayParts) . ']';
		} catch (Exception $e) {
			// Fallback: If routing fails, build basic array manually
			$url = ltrim($url, '/');
			$parts = array_filter(explode('/', explode('?', $url)[0]));
			$parts = array_values($parts);

			if (empty($parts)) {
				return "['controller' => 'Pages', 'action' => 'home']";
			}

			$arrayParts = [];
			$arrayParts[] = "'controller' => '" . ucfirst($parts[0]) . "'";
			$arrayParts[] = "'action' => '" . ($parts[1] ?? 'index') . "'";

			// Add remaining parts as pass parameters
			for ($i = 2; $i < count($parts); $i++) {
				if (is_numeric($parts[$i])) {
					$arrayParts[] = $parts[$i];
				} else {
					$arrayParts[] = "'{$parts[$i]}'";
				}
			}

			// Add query string if present
			if ($queryString) {
				parse_str($queryString, $queryParams);
				if (!empty($queryParams)) {
					$queryParts = [];
					foreach ($queryParams as $key => $value) {
						if (is_array($value)) {
							continue; // Skip array values for simplicity
						}
						$queryParts[] = "'{$key}' => '" . (string)$value . "'";
					}
					$arrayParts[] = "'?' => [" . implode(', ', $queryParts) . ']';
				}
			}

			return '[' . implode(', ', $arrayParts) . ']';
		}
	}

}
