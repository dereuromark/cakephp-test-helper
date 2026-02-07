<?php

return [
	'TestHelper' => [
		'php' => null, // Custom PHP binary path, e.g. '/usr/bin/php8.2'
		'command' => null,
		'coverage' => null,

		// Set to true to bypass authorization checks for TestHelper routes
		// when using the CakePHP Authorization plugin
		'ignoreAuthorization' => true,

		// Custom migration order for drift-check (like Migrator's runMany format).
		// Use [] for app migrations. Order matters for foreign key constraints.
		// If not set, auto-detects: plugins first (alphabetically), then app.
		// 'migrations' => [
		//     ['plugin' => 'Queue'],
		//     ['plugin' => 'Users'],
		//     ['plugin' => 'Blog'],
		//     [], // App migrations
		// ],

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
