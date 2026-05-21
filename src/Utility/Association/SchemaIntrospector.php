<?php

namespace TestHelper\Utility\Association;

use Cake\Database\Connection;
use Cake\Database\Schema\TableSchema;

/**
 * Reads the DB side: real FK constraints and FK-looking columns, per table.
 */
class SchemaIntrospector {

	/**
	 * Real foreign-key constraints on the given table.
	 *
	 * @param \Cake\Database\Connection $connection
	 * @param string $table Physical table name.
	 * @return array<\TestHelper\Utility\Association\ForeignKey>
	 */
	public function foreignKeys(Connection $connection, string $table): array {
		$schema = $connection->getSchemaCollection()->describe($table);
		$connectionName = $connection->configName();

		$keys = [];
		foreach ($schema->constraints() as $name) {
			$constraint = $schema->getConstraint($name);
			if (!$constraint || ($constraint['type'] ?? null) !== TableSchema::CONSTRAINT_FOREIGN) {
				continue;
			}

			$columns = (array)($constraint['columns'] ?? []);
			$references = $constraint['references'] ?? null;
			if (count($columns) !== 1 || !is_array($references)) {
				// Composite FKs are not deep-validated in v1.
				continue;
			}

			[$referencedTable, $referencedColumn] = $this->normalizeReferences($references);

			$keys[] = new ForeignKey(
				connection: $connectionName,
				ownerTable: $table,
				column: (string)$columns[0],
				referencedTable: $referencedTable,
				referencedColumn: $referencedColumn,
				source: ForeignKey::SOURCE_DB,
			);
		}

		return $keys;
	}

	/**
	 * FK-looking (`*_id`) columns that have no real FK constraint.
	 *
	 * @param \Cake\Database\Connection $connection
	 * @param string $table Physical table name.
	 * @return array<\TestHelper\Utility\Association\LooseColumn>
	 */
	public function looseColumns(Connection $connection, string $table): array {
		$schema = $connection->getSchemaCollection()->describe($table);
		$connectionName = $connection->configName();

		$constrained = [];
		foreach ($schema->constraints() as $name) {
			$constraint = $schema->getConstraint($name);
			if (!$constraint || ($constraint['type'] ?? null) !== TableSchema::CONSTRAINT_FOREIGN) {
				continue;
			}
			foreach ((array)($constraint['columns'] ?? []) as $column) {
				$constrained[$column] = true;
			}
		}

		$loose = [];
		foreach ($schema->columns() as $column) {
			if (!str_ends_with($column, '_id')) {
				continue;
			}
			if (isset($constrained[$column])) {
				continue;
			}

			$loose[] = new LooseColumn(
				connection: $connectionName,
				table: $table,
				column: $column,
			);
		}

		return $loose;
	}

	/**
	 * @param array<int|string, mixed> $references
	 * @return array{0: string, 1: string}
	 */
	protected function normalizeReferences(array $references): array {
		$referencedTable = (string)($references[0] ?? '');
		$referencedColumn = $references[1] ?? 'id';
		if (is_array($referencedColumn)) {
			$referencedColumn = (string)(reset($referencedColumn) ?: 'id');
		}

		return [$referencedTable, (string)$referencedColumn];
	}

}
