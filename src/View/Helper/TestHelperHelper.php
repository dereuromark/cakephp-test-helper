<?php
namespace TestHelper\View\Helper;

use Cake\View\Helper;

class TestHelperHelper extends Helper {

	/**
	 * @param array $url
	 * @param bool $verbose
	 *
	 * @return array
	 */
	public function prepareUrl(array $url, $verbose = false) {
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

}
