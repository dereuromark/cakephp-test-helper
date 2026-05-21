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

			$columns = array_map('strval', (array)($constraint['columns'] ?? []));
			$references = $constraint['references'] ?? null;
			if (!$columns || !is_array($references)) {
				continue;
			}

			[$referencedTable, $referencedColumns] = $this->normalizeReferences($references);

			$keys[] = new ForeignKey(
				connection: $connectionName,
				ownerTable: $table,
				column: $columns,
				referencedTable: $referencedTable,
				referencedColumn: $referencedColumns,
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
	 * @return array{0: string, 1: array<string>}
	 */
	protected function normalizeReferences(array $references): array {
		$referencedTable = (string)($references[0] ?? '');
		$referencedColumns = $references[1] ?? 'id';
		$referencedColumns = is_array($referencedColumns) ? array_values($referencedColumns) : [$referencedColumns];
		$referencedColumns = array_map('strval', $referencedColumns);
		if (!$referencedColumns) {
			$referencedColumns = ['id'];
		}

		return [$referencedTable, $referencedColumns];
	}

}
