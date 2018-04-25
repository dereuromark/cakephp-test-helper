<?php

namespace TestHelper\Utility;

class ClassResolver {

	/**
	 * @var array
	 */
	protected static $typeMap = [
		'Table' => 'Model/Table',
		'Entity' => 'Model/Entity',
		'Behavior' => 'Model/Behavior',
		'Task' => 'Shell/Task',
		'Component' => 'Controller/Component',
		'Helper' => 'View/Helper',
		'ShellHelper' => 'Shell/Helper',
	];

	/**
	 * @var array
	 */
	protected static $suffixMap = [
		'Entity' => '',
		'ShellHelper' => 'Helper',
	];

	/**
	 * @param string $type
	 *
	 * @return string
	 */
	public static function type($type) {
		if (!isset(static::$typeMap[$type])) {
			return $type;
		}

		return static::$typeMap[$type];
	}

	/**
	 * @param string $type
	 *
	 * @return string
	 */
	public static function suffix($type) {
		if (!isset(static::$suffixMap[$type])) {
			return $type;
		}

		$suffix = static::$suffixMap[$type];

		return $suffix;
	}

}
