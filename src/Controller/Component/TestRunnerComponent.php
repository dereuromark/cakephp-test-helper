<?php

namespace TestHelper\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use RuntimeException;

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
		$command .= ' ' . $file;

		if ($filter) {
			// Escape filter for shell and add --filter option
			$command .= ' --filter ' . escapeshellarg($filter);
		}

		$command = str_replace(['/', '\\'], DS, $command);
		chdir(ROOT);
		exec($command, $output, $res);

		$result = [
			'command' => $command,
			'content' => $output,
			'output' => '<h2>Result</h2>' . implode('<br>', $output),
			'code' => $res,
		];

		return $result;
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

		$testFile = ROOT . DS . 'webroot/coverage/src/' . $type . '/' . $name . '.php.html';
		$testFile = str_replace(['/', '\\'], DS, $testFile);

		if (Configure::read('TestHelper.coverage') !== 'xdebug') {
			$command = 'XDEBUG_MODE=coverage php -d ' . ROOT . ' ' . $command;
		}

		$command .= ' ' . $file;
		$command .= ' --log-junit webroot/coverage/unitreport.xml --coverage-html webroot/coverage --coverage-clover webroot/coverage/coverage.xml --include src/' . $type . '/';
		$command = str_replace(['/', '\\'], DS, $command);

		if (!file_exists($testFile) || $force) {
			chdir(ROOT);
			exec($command, $output, $res);
		}

		$url = str_replace('\\', '/', '/coverage/src/' . $type . '/' . $name . '.php.html');

		$fileExists = file_exists($testFile);
		if ($fileExists) {
			$output = <<<HTML
<a href="$url" target="_blank">$file</a>
HTML;
		} else {
			$output = '<i>Coverage file could not be created, coverage driver issues?</i>';
		}

		$result = [
			'command' => $command,
			'file' => $file,
			'url' => $url,
			'testFileExists' => $fileExists,
			'content' => $fileExists ? file_get_contents($testFile) : null,
			'output' => '<h2>Coverage-Result</h2>' . $output,
		];

		return $result;
	}

	/**
	 * @return string
	 */
	protected function getCommand() {
		if (Configure::read('TestHelper.command')) {
			return Configure::read('TestHelper.command');
		}

		if (file_exists(ROOT . DS . 'phpunit.phar')) {
			return 'php phpunit.phar';
		}

		return 'vendor/bin/phpunit';
	}

}
