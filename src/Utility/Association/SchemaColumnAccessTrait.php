<?php

namespace TestHelper\Utility\Association;

use Cake\ORM\Table;
use Throwable;

/**
 * Schema lookups that never throw: a single misconfigured or non-introspectable table
 * must not abort the audit. Shared by the code-side readers that build foreign keys from
 * live ORM tables.
 */
trait SchemaColumnAccessTrait {

	/**
	 * @param \Cake\ORM\Table $table
	 * @return array<string>|null Null when the schema cannot be described.
	 */
	protected function safeColumns(Table $table): ?array {
		try {
			return $table->getSchema()->columns();
		} catch (Throwable) {
			return null;
		}
	}

	/**
	 * Abstract DB type of a column, or null if the schema/column cannot be resolved.
	 *
	 * @param \Cake\ORM\Table $table
	 * @param string $column
	 * @return string|null
	 */
	protected function safeColumnType(Table $table, string $column): ?string {
		try {
			return $table->getSchema()->getColumnType($column);
		} catch (Throwable) {
			return null;
		}
	}

}
