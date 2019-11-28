<?php

namespace TestHelper\View\Helper;

use Cake\Utility\Inflector;
use Cake\View\Helper;

class TestHelperHelper extends Helper {

	/**
	 * @param array $url
	 * @param bool $verbose
	 *
	 * @return string
	 */
	public function url(array $url, $verbose = false) {
		$pieces = $this->prepareUrl($url, $verbose);

		return '[' . PHP_EOL . '    ' . implode(',' . PHP_EOL . '    ', $pieces) . PHP_EOL . ']';
	}

	/**
	 * @param array $url
	 * @param bool $verbose
	 *
	 * @return array
	 */
	protected function prepareUrl(array $url, $verbose = false) {
		$output = [];
		$order = [
			'prefix' => false,
			'plugin' => false,
			'controller' => true,
			'action' => true,
			'pass' => null,
			'_ext' => null,
			'?' => null,
		];
		foreach ($order as $element => $always) {
			if ($element === 'pass') {
				if (empty($url[$element])) {
					continue;
				}
				$output[] = "'" . implode("', '", $url[$element]) . "'";

				continue;
			}
			if ($element === '?') {
				if (empty($url[$element])) {
					continue;
				}

				$query = [];
				foreach ($url[$element] as $k => $v) {
					$query[] = $this->export($k) . ' => ' . $this->export($v);
				}
				$output[] = "'?' => [" . implode(', ', $query) . ']';

				continue;
			}

			if (!isset($url[$element]) && !$always && !$verbose) {
				continue;
			}

			if (!isset($url[$element])) {
				$url[$element] = null;
			}

			if (isset($url[$element]) || $always || $always === false && $verbose) {
				$output[] = "'" . $element . "' => " . $this->export($url[$element]);
			}
		}

		return $output;
	}

	/**
	 * @param array $url
	 *
	 * @return string
	 */
	public function urlPath(array $url) {
		$defaults = [
			'prefix' => false,
			'plugin' => false,
			'controller' => true,
			'action' => true,
			'pass' => null,
			'_ext' => null,
			'?' => null,
		];
		$url += $defaults;

		$path = $url['controller'] . '::' . $url['action'];
		if ($url['prefix']) {
			$prefix = $this->normalizePrefix($url['prefix']);
			$path = $prefix . '/' . $path;
		}

		if ($url['plugin']) {
			$path = $url['plugin'] . '.' . $path;
		}

		return $path;
	}

	/**
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected function export($value) {
		$value = var_export($value, true);

		foreach (['NULL', 'FALSE', 'TRUE'] as $key) {
			if ($key === $value) {
				return strtolower($value);
			}
		}

		return $value;
	}

	/**
	 * @param string $prefix
	 *
	 * @return string
	 */
	protected function normalizePrefix($prefix) {
		if (strpos($prefix, '/') === false) {
			return Inflector::camelize($prefix);
		}

		$prefixes = array_map(
			function ($val) {
				return Inflector::camelize($val);
			},
			explode('/', $prefix)
		);

		return implode('/', $prefixes);
	}

}
