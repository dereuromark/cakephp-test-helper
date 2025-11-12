<?php

namespace TestHelper\View\Helper;

use Cake\Utility\Inflector;
use Cake\View\Helper;

class TestHelperHelper extends Helper {

	/**
	 * Displays a yes/no icon or text for boolean values
	 *
	 * @param bool $value The boolean value to display
	 * @param array<string, mixed> $options Options for rendering
	 * @return string HTML output
	 */
	public function yesNo(bool $value, array $options = []): string {
		$defaults = [
			'icon' => false,
		];
		$options += $defaults;

		if ($options['icon']) {
			return $value
				? '<i class="fas fa-check text-success" title="Yes"></i>'
				: '<i class="fas fa-times text-danger" title="No"></i>';
		}

		return $value
			? '<span class="badge bg-success">Yes</span>'
			: '<span class="badge bg-danger">No</span>';
	}

	/**
	 * @param array<string, mixed> $params
	 * @param bool $verbose
	 *
	 * @return string
	 */
	public function url(array $params, bool $verbose = false) {
		$pieces = $this->prepareUrl($params, $verbose);

		return '[' . PHP_EOL . '    ' . implode(',' . PHP_EOL . '    ', $pieces) . PHP_EOL . ']';
	}

	/**
	 * @param array<string, mixed> $params
	 * @param bool $verbose
	 *
	 * @return array<string>
	 */
	protected function prepareUrl(array $params, bool $verbose = false): array {
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
				if (empty($params[$element])) {
					continue;
				}
				$output[] = "'" . implode("', '", $params[$element]) . "'";

				continue;
			}
			if ($element === '?') {
				if (empty($params[$element])) {
					continue;
				}

				$query = [];
				foreach ($params[$element] as $k => $v) {
					$query[] = $this->export($k) . ' => ' . $this->export($v);
				}
				$output[] = "'?' => [" . implode(', ', $query) . ']';

				continue;
			}

			if (!isset($params[$element]) && !$always && !$verbose) {
				continue;
			}

			if (!isset($params[$element])) {
				$params[$element] = null;
			}

			if (isset($params[$element]) || $always || $always === false && $verbose) {
				$output[] = "'" . $element . "' => " . $this->export($params[$element]);
			}
		}

		return $output;
	}

	/**
	 * @param array<string, mixed> $params
	 *
	 * @return string
	 */
	public function urlPath(array $params): string {
		$defaults = [
			'prefix' => false,
			'plugin' => false,
			'controller' => true,
			'action' => true,
			'pass' => null,
			'_ext' => null,
			'?' => null,
		];
		$params += $defaults;

		$path = $params['controller'] . '::' . $params['action'];
		if ($params['prefix']) {
			$prefix = $this->normalizePrefix($params['prefix']);
			$path = $prefix . '/' . $path;
		}

		if ($params['plugin']) {
			$path = $params['plugin'] . '.' . $path;
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
	protected function normalizePrefix(string $prefix): string {
		if (!str_contains($prefix, '/')) {
			return Inflector::camelize($prefix);
		}

		$prefixes = array_map(
			function ($val) {
				return Inflector::camelize($val);
			},
			explode('/', $prefix),
		);

		return implode('/', $prefixes);
	}

}
