<?php

return [
	'TestHelper' => [
		'php' => null, // Custom PHP binary path, e.g. '/usr/bin/php8.2'
		'command' => null,
		'coverage' => null,

		// Set to true to bypass authorization checks for TestHelper routes
		// when using the CakePHP Authorization plugin
		'ignoreAuthorization' => true,

		'Linter' => [
			'tasks' => [
				// ...
			],
			// Allow specific string URLs that should not trigger warnings
			'ArrayUrlsInTests' => [
				'allowedStringUrls' => [],
			],
			'ArrayUrlsInControllers' => [
				'allowedStringUrls' => [],
			],
		],
	],
];
