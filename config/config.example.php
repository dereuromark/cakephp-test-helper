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

		// Association vs DB foreign-key audit (/test-helper/associations)
		'associationAudit' => [
			// Extra `*_id` columns the loose-column layer should ignore (added to the
			// built-in foreign_id/parent_id/related_id), e.g. polymorphic columns.
			'ignoreColumns' => [
				// 'commentable_id',
			],
			// Emit the info-level "integer keys are preferred" finding for relations that
			// use matching non-integer keys (e.g. uuid <-> uuid). Set to false to silence
			// it on apps that deliberately standardize on uuid/other keys.
			'preferIntegerKeys' => true,
		],

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
