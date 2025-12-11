<?php

namespace TestHelper\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;

class MigrationsComponent extends Component {

	/**
	 * Tables to ignore when comparing schemas.
	 *
	 * @var array<string>
	 */
	protected array $ignoredTables = ['phinxlog', 'cake_migrations', 'cake_seeds'];

	/**
	 * @param string $database
	 * @return string
	 */
	public function getSchema(string $database): string {
		$dbConfig = ConnectionManager::getConfig('default');
		$command = 'cd ' . ROOT . ' && mysqldump --host=' . ($dbConfig['host'] ?? 'localhost') . ' --user="' . $dbConfig['username'] . '" --password="' . $dbConfig['password'] . '" --no-data ' . $database;
		exec($command, $output, $code);
		if ($code !== 0) {
			$this->getController()->Flash->error(print_r($output, true));
		}
		array_pop($output);
		$content = trim(implode(PHP_EOL, $output));

		return $content;
	}

	/**
	 * Get structured schema information from a database connection.
	 *
	 * @param \Cake\Database\Connection $connection
	 * @return array<string, array<string, mixed>>
	 */
	public function getStructuredSchema(Connection $connection): array {
		$schema = [];

		/** @var \Cake\Database\Schema\Collection $schemaCollection */
		$schemaCollection = $connection->getSchemaCollection();
		$tables = $schemaCollection->listTables();

		foreach ($tables as $tableName) {
			if ($this->isIgnoredTable($tableName)) {
				continue;
			}

			$tableSchema = $schemaCollection->describe($tableName);

			$columns = [];
			foreach ($tableSchema->columns() as $columnName) {
				$columnData = $tableSchema->getColumn($columnName);
				$columns[$columnName] = $this->normalizeColumn($columnData);
			}

			$indexes = [];
			foreach ($tableSchema->indexes() as $indexName) {
				$indexes[$indexName] = $tableSchema->getIndex($indexName);
			}

			$constraints = [];
			foreach ($tableSchema->constraints() as $constraintName) {
				$constraints[$constraintName] = $tableSchema->getConstraint($constraintName);
			}

			$schema[$tableName] = [
				'columns' => $columns,
				'indexes' => $indexes,
				'constraints' => $constraints,
			];
		}

		ksort($schema);

		return $schema;
	}

	/**
	 * Check if a table should be ignored in schema comparison.
	 *
	 * @param string $tableName
	 * @return bool
	 */
	protected function isIgnoredTable(string $tableName): bool {
		if (in_array($tableName, $this->ignoredTables, true)) {
			return true;
		}

		// Ignore plugin phinxlog tables (e.g., blog_phinxlog, users_phinxlog)
		if (str_ends_with($tableName, '_phinxlog')) {
			return true;
		}

		return false;
	}

	/**
	 * Normalize column data for comparison (remove volatile attributes).
	 *
	 * @param array<string, mixed>|null $columnData
	 * @return array<string, mixed>
	 */
	protected function normalizeColumn(?array $columnData): array {
		if ($columnData === null) {
			return [];
		}

		// Remove attributes that may vary but don't affect functionality
		unset($columnData['comment']);

		return $columnData;
	}

	/**
	 * Compare two schemas and return drift information.
	 *
	 * @param array<string, array<string, mixed>> $expected Schema from migrations (shadow DB)
	 * @param array<string, array<string, mixed>> $actual Schema from actual database
	 * @return array<string, array<int, array<string, mixed>>>
	 */
	public function compareSchemas(array $expected, array $actual): array {
		$drift = [
			'extra_tables' => [],
			'missing_tables' => [],
			'column_diffs' => [],
			'index_diffs' => [],
			'constraint_diffs' => [],
		];

		$expectedTables = array_keys($expected);
		$actualTables = array_keys($actual);

		// Tables in actual but not in expected (extra)
		$extraTables = array_diff($actualTables, $expectedTables);
		foreach ($extraTables as $table) {
			$drift['extra_tables'][] = [
				'table' => $table,
				'columns' => array_keys($actual[$table]['columns']),
			];
		}

		// Tables in expected but not in actual (missing)
		$missingTables = array_diff($expectedTables, $actualTables);
		foreach ($missingTables as $table) {
			$drift['missing_tables'][] = [
				'table' => $table,
				'columns' => array_keys($expected[$table]['columns']),
			];
		}

		// Compare tables that exist in both
		$commonTables = array_intersect($expectedTables, $actualTables);
		foreach ($commonTables as $table) {
			$this->compareTableColumns($table, $expected[$table], $actual[$table], $drift);
			$this->compareTableIndexes($table, $expected[$table], $actual[$table], $drift);
			$this->compareTableConstraints($table, $expected[$table], $actual[$table], $drift);
		}

		return $drift;
	}

	/**
	 * Compare columns between expected and actual table schemas.
	 *
	 * @param string $table
	 * @param array<string, mixed> $expected
	 * @param array<string, mixed> $actual
	 * @param array<string, array<int, array<string, mixed>>> $drift
	 * @return void
	 */
	protected function compareTableColumns(string $table, array $expected, array $actual, array &$drift): void {
		$expectedColumns = $expected['columns'];
		$actualColumns = $actual['columns'];

		$expectedColumnNames = array_keys($expectedColumns);
		$actualColumnNames = array_keys($actualColumns);

		// Extra columns (in actual but not expected)
		$extraColumns = array_diff($actualColumnNames, $expectedColumnNames);
		foreach ($extraColumns as $column) {
			$drift['column_diffs'][] = [
				'type' => 'extra',
				'table' => $table,
				'column' => $column,
				'actual' => $actualColumns[$column],
			];
		}

		// Missing columns (in expected but not actual)
		$missingColumns = array_diff($expectedColumnNames, $actualColumnNames);
		foreach ($missingColumns as $column) {
			$drift['column_diffs'][] = [
				'type' => 'missing',
				'table' => $table,
				'column' => $column,
				'expected' => $expectedColumns[$column],
			];
		}

		// Type mismatches for columns that exist in both
		$commonColumns = array_intersect($expectedColumnNames, $actualColumnNames);
		foreach ($commonColumns as $column) {
			$expectedCol = $expectedColumns[$column];
			$actualCol = $actualColumns[$column];

			$differences = $this->getColumnDifferences($expectedCol, $actualCol);
			if ($differences) {
				$drift['column_diffs'][] = [
					'type' => 'mismatch',
					'table' => $table,
					'column' => $column,
					'expected' => $expectedCol,
					'actual' => $actualCol,
					'differences' => $differences,
				];
			}
		}
	}

	/**
	 * Get differences between two column definitions.
	 *
	 * @param array<string, mixed> $expected
	 * @param array<string, mixed> $actual
	 * @return array<string, array<string, mixed>>
	 */
	protected function getColumnDifferences(array $expected, array $actual): array {
		$differences = [];
		$keysToCompare = ['type', 'length', 'precision', 'scale', 'null', 'default', 'unsigned', 'autoIncrement'];

		foreach ($keysToCompare as $key) {
			$expectedVal = $expected[$key] ?? null;
			$actualVal = $actual[$key] ?? null;

			if ($expectedVal !== $actualVal) {
				$differences[$key] = [
					'expected' => $expectedVal,
					'actual' => $actualVal,
				];
			}
		}

		return $differences;
	}

	/**
	 * Compare indexes between expected and actual table schemas.
	 *
	 * @param string $table
	 * @param array<string, mixed> $expected
	 * @param array<string, mixed> $actual
	 * @param array<string, array<int, array<string, mixed>>> $drift
	 * @return void
	 */
	protected function compareTableIndexes(string $table, array $expected, array $actual, array &$drift): void {
		$expectedIndexes = $expected['indexes'];
		$actualIndexes = $actual['indexes'];

		$expectedIndexNames = array_keys($expectedIndexes);
		$actualIndexNames = array_keys($actualIndexes);

		// Extra indexes
		$extraIndexes = array_diff($actualIndexNames, $expectedIndexNames);
		foreach ($extraIndexes as $index) {
			$drift['index_diffs'][] = [
				'type' => 'extra',
				'table' => $table,
				'index' => $index,
				'actual' => $actualIndexes[$index],
			];
		}

		// Missing indexes
		$missingIndexes = array_diff($expectedIndexNames, $actualIndexNames);
		foreach ($missingIndexes as $index) {
			$drift['index_diffs'][] = [
				'type' => 'missing',
				'table' => $table,
				'index' => $index,
				'expected' => $expectedIndexes[$index],
			];
		}

		// Index definition mismatches
		$commonIndexes = array_intersect($expectedIndexNames, $actualIndexNames);
		foreach ($commonIndexes as $index) {
			if ($expectedIndexes[$index] !== $actualIndexes[$index]) {
				$drift['index_diffs'][] = [
					'type' => 'mismatch',
					'table' => $table,
					'index' => $index,
					'expected' => $expectedIndexes[$index],
					'actual' => $actualIndexes[$index],
				];
			}
		}
	}

	/**
	 * Compare constraints between expected and actual table schemas.
	 *
	 * @param string $table
	 * @param array<string, mixed> $expected
	 * @param array<string, mixed> $actual
	 * @param array<string, array<int, array<string, mixed>>> $drift
	 * @return void
	 */
	protected function compareTableConstraints(string $table, array $expected, array $actual, array &$drift): void {
		$expectedConstraints = $expected['constraints'];
		$actualConstraints = $actual['constraints'];

		$expectedConstraintNames = array_keys($expectedConstraints);
		$actualConstraintNames = array_keys($actualConstraints);

		// Extra constraints
		$extraConstraints = array_diff($actualConstraintNames, $expectedConstraintNames);
		foreach ($extraConstraints as $constraint) {
			$drift['constraint_diffs'][] = [
				'type' => 'extra',
				'table' => $table,
				'constraint' => $constraint,
				'actual' => $actualConstraints[$constraint],
			];
		}

		// Missing constraints
		$missingConstraints = array_diff($expectedConstraintNames, $actualConstraintNames);
		foreach ($missingConstraints as $constraint) {
			$drift['constraint_diffs'][] = [
				'type' => 'missing',
				'table' => $table,
				'constraint' => $constraint,
				'expected' => $expectedConstraints[$constraint],
			];
		}

		// Constraint definition mismatches
		$commonConstraints = array_intersect($expectedConstraintNames, $actualConstraintNames);
		foreach ($commonConstraints as $constraint) {
			if ($expectedConstraints[$constraint] !== $actualConstraints[$constraint]) {
				$drift['constraint_diffs'][] = [
					'type' => 'mismatch',
					'table' => $table,
					'constraint' => $constraint,
					'expected' => $expectedConstraints[$constraint],
					'actual' => $actualConstraints[$constraint],
				];
			}
		}
	}

	/**
	 * Check if there is any drift.
	 *
	 * @param array<string, array<int, array<string, mixed>>> $drift
	 * @return bool
	 */
	public function hasDrift(array $drift): bool {
		foreach ($drift as $driftType) {
			if ($driftType) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get the migration table name based on configuration or auto-detection.
	 *
	 * @param string $connectionName Connection name to check for table existence.
	 * @return string
	 */
	public function getMigrationTableName(string $connectionName = 'default'): string {
		$useLegacy = Configure::read('Migrations.legacyTables');
		if ($useLegacy === null) {
			// Auto-detect: check if cake_migrations table exists
			/** @var \Cake\Database\Connection $connection */
			$connection = ConnectionManager::get($connectionName);
			/** @var \Cake\Database\Schema\Collection $schemaCollection */
			$schemaCollection = $connection->getSchemaCollection();
			$tables = $schemaCollection->listTables();
			$useLegacy = !in_array('cake_migrations', $tables, true);
		}

		return $useLegacy ? 'phinxlog' : 'cake_migrations';
	}

}
