<?php

namespace TestHelper\Utility;

class ClassResolver {

	protected static array $typeMap = [
		'Table' => 'Model/Table',
		'Entity' => 'Model/Entity',
		'Behavior' => 'Model/Behavior',
		'Task' => 'Command/Task',
		'Component' => 'Controller/Component',
		'Helper' => 'View/Helper',
		'CommandHelper' => 'Command/Helper',
		'Cell' => 'View/Cell',
		'Form' => 'Form',
		'Mailer' => 'Mailer',
	];

	protected static array $suffixMap = [
		'Entity' => '',
		'CommandHelper' => 'Helper',
		'Cell' => 'Cell',
		'Form' => 'Form',
		'Mailer' => 'Mailer',
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
