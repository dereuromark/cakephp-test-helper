<?php

return [
	'TestHelper' => [
		'php' => null, // Custom PHP binary path, e.g. '/usr/bin/php8.2'
		'command' => null,
		'coverage' => null,

		// Set to true to bypass authorization checks for TestHelper routes
		// when using the CakePHP Authorization plugin
		'ignoreAuthorization' => true,

		// Optional "back" link shown in the TestHelper navbar, for returning to your app's
		// admin area. Accepts anything Router::url() takes (URL array, path string, full URL).
		// Use 'plugin' => false to anchor the URL to the host app rather than TestHelper.
		// 'adminBackUrl' => ['plugin' => false, 'prefix' => 'Admin', 'controller' => 'Overview', 'action' => 'index'],
		// 'adminBackLabel' => 'Back to admin', // Optional. Defaults to "Back to App".

		// Custom migration order for drift-check (like Migrator's runMany format).
		// Use [] for app migrations. Order matters for foreign key constraints.
		// If not set, auto-detects: plugins first (alphabetically), then app.
		// 'migrations' => [
		//     ['plugin' => 'Queue'],
		//     ['plugin' => 'Users'],
		//     ['plugin' => 'Blog'],
		//     [], // App migrations
		// ],

		// Fixture/test collector (drives /test-helper/test-fixtures and the comparison tool).
		// Options merge over the component defaults; the connection is taken from the request.
		// 'Collector' => [
		//     'blacklist' => ['DebugKit'], // Plugins to skip when collecting models/fixtures.
		//     'ignoredTables' => [], // Table classes to ignore.
		//     'ignoredEntities' => [], // Entity classes to ignore.
		//     'ignoredDbTables' => ['i18n', 'cake_sessions', 'sessions', '/phinxlog/'], // DB tables to ignore (regex allowed via /.../).
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
			// Run the index-presence layer, which flags foreign-key-style columns that are
			// not the leading column of any index (joins/lookups on them table-scan). Most
			// valuable on PostgreSQL, where a foreign-key constraint does not auto-create an
			// index on the referencing column. Set to false to silence it on apps where the
			// heuristic is more noise than value (e.g. write-heavy or denormalized schemas).
			'checkIndexes' => true,
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
