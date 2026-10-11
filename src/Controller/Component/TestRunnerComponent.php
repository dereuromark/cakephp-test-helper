<?php

namespace TestHelper\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use RuntimeException;
use function Cake\Core\h;

class TestRunnerComponent extends Component {

	/**
	 * @param string $file
	 * @param string|null $filter Optional filter for specific test method
	 * @throws \RuntimeException
	 * @return array
	 */
	public function run($file, ?string $filter = null) {
		if (!file_exists(ROOT . DS . $file)) {
			throw new RuntimeException('Invalid file: ' . $file);
		}

		$command = $this->getCommand();
		$command .= ' ' . escapeshellarg($file);

		if ($filter) {
			// Escape filter for shell and add --filter option
			$command .= ' --filter ' . escapeshellarg($filter);
		}

		[$output, $res] = $this->executeCommand($command);

		return [
			'command' => $command,
			'content' => $output,
			'output' => '<h2>Result</h2>' . implode('<br>', array_map(h(...), $output)),
			'code' => $res,
		];
	}

	/**
	 * @param string $file
	 * @param string $name
	 * @param string $type
	 * @param bool $force
	 *
	 * @return array
	 */
	public function coverage(string $file, string $name, string $type, bool $force = false): array {
		$command = $this->getCommand();

		[$plugin, $classType] = pluginSplit($type);
		$sourceRoot = $plugin ? Plugin::path($plugin) . 'src/' : ROOT . DS . 'src/';
		$sourceDirectory = $sourceRoot . $classType . '/';
		$relativeSource = str_replace('\\', '/', str_replace(ROOT . DS, '', $sourceRoot));
		$reportName = str_replace(['.', '\\'], '/', $name);
		$reportPaths = [
			$relativeSource . $classType . '/' . $reportName . '.php.html',
			$classType . '/' . $reportName . '.php.html',
		];
		$testFile = ROOT . DS . 'webroot/coverage/' . $reportPaths[0];
		foreach ($reportPaths as $reportPath) {
			$candidate = ROOT . DS . 'webroot/coverage/' . $reportPath;
			if (file_exists($candidate)) {
				$testFile = $candidate;

				break;
			}
		}

		$command .= ' ' . escapeshellarg($file);
		$command .= ' --log-junit webroot/coverage/unitreport.xml --coverage-html webroot/coverage --coverage-clover webroot/coverage/coverage.xml --coverage-filter ' . escapeshellarg($sourceDirectory);

		$commandOutput = [];
		$code = null;
		if (!file_exists($testFile) || $force) {
			$previousMode = getenv('XDEBUG_MODE');
			putenv('XDEBUG_MODE=coverage');
			try {
				[$commandOutput, $code] = $this->executeCommand($command);
			} finally {
				putenv($previousMode === false ? 'XDEBUG_MODE' : 'XDEBUG_MODE=' . $previousMode);
			}
		}

		foreach ($reportPaths as $reportPath) {
			$candidate = ROOT . DS . 'webroot/coverage/' . $reportPath;
			if (file_exists($candidate)) {
				$testFile = $candidate;

				break;
			}
		}
		$url = '/coverage/' . str_replace('\\', '/', substr($testFile, strlen(ROOT . DS . 'webroot/coverage/')));

		$fileExists = file_exists($testFile) && ($code === null || $code === 0);
		if ($fileExists) {
			$output = '<a href="' . h($url) . '" target="_blank">' . h($file) . '</a>';
		} else {
			$output = '<i>Coverage file could not be created, coverage driver issues?</i>';
			if ($commandOutput) {
				$output .= '<pre>' . h(implode(PHP_EOL, $commandOutput)) . '</pre>';
			}
		}

		return [
			'command' => $command,
			'file' => $file,
			'url' => $url,
			'testFileExists' => $fileExists,
			'content' => $fileExists ? file_get_contents($testFile) : null,
			'output' => '<h2>Coverage-Result</h2>' . $output,
			'code' => $code,
			'processOutput' => $commandOutput,
		];
	}

	/**
	 * @return string
	 */
	protected function getCommand() {
		if (Configure::read('TestHelper.command')) {
			return Configure::read('TestHelper.command');
		}

		if (file_exists(ROOT . DS . 'phpunit.phar')) {
			return escapeshellarg($this->getPhpBinary()) . ' phpunit.phar';
		}

		return escapeshellarg($this->getPhpBinary()) . ' vendor/bin/phpunit';
	}

	/**
	 * @return string
	 */
	protected function getPhpBinary(): string {
		return Configure::read('TestHelper.php') ?: 'php';
	}

	/**
	 * @param string $command
	 * @return array{list<string>, int}
	 */
	protected function executeCommand(string $command): array {
		$directory = getcwd();
		try {
			chdir(ROOT);
			exec($command . ' 2>&1', $output, $code);

			return [$output, $code];
		} finally {
			if ($directory !== false) {
				chdir($directory);
			}
		}
	}

}
