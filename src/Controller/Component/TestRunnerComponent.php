<?php
namespace TestHelper\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use RuntimeException;

class TestRunnerComponent extends Component {

	/**
	 * @param string $file
	 * @return array
	 */
	public function run($file) {
		if (!file_exists(ROOT . DS . $file)) {
			throw new RuntimeException('Invalid file: ' . $file);
		}

		$command = $this->getCommand();
		$command .= ' ' . $file;
		exec('cd ' . ROOT . ' && ' . $command, $output, $res);

		$result = [
			'command' => $command,
			'content' => $output,
			'output' => implode('<br>', $output),
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
	public function coverage($file, $name, $type, $force = false) {
		$command = $this->getCommand();

		$testFile = ROOT . DS . 'webroot/coverage/src/' . $type . '/' . $name . '.php.html';

		$command .= ' ' . $file;
		$command .= ' --log-junit webroot/coverage/unitreport.xml --coverage-html webroot/coverage --coverage-clover webroot/coverage/coverage.xml --whitelist src/' . $type . '/' . $name . '.php';
		if (!file_exists($testFile) || $force) {
			exec('cd ' . ROOT . ' && ' . $command, $output, $res);
		}

		$url = str_replace('\\', '/', '/coverage/src/' . $type . '/' . $name . '.php.html');

		$output = <<<HTML
<h2>Coverage-Result</h2>
<a href="$url" target="_blank">$file</a>
HTML;

		$result = [
			'command' => $command,
			'file' => $file,
			'url' => $url,
			'content' => file_get_contents($testFile),
			'output' => $output,
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
