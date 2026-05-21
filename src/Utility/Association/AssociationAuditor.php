<?php

namespace TestHelper\Utility\Association;

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

/**
 * Orchestrates the association/DB audit.
 *
 * The diff methods are pure (foreign keys in -> findings out) and carry the bulk of
 * the logic; the audit() entry point wires in the live introspectors.
 */
class AssociationAuditor {

	use LocatorAwareTrait;

	protected FixSuggester $fixSuggester;

	protected SchemaIntrospector $schema;

	protected AssociationReader $reader;

	/**
	 * Built-in column suffixes that look like FKs but commonly are not (polymorphic, etc.).
	 *
	 * @var array<string>
	 */
	protected array $defaultIgnoreColumns = [
		'foreign_id',
		'parent_id',
		'related_id',
	];

	public function __construct() {
		$this->fixSuggester = new FixSuggester();
		$this->schema = new SchemaIntrospector();
		$this->reader = new AssociationReader();
	}

	/**
	 * Audit a set of table aliases (plugin-dotted where relevant).
	 *
	 * @param array<string> $tableAliases e.g. ['Posts', 'Sandbox.Animals'].
	 * @return array<\TestHelper\Utility\Association\Finding>
	 */
	public function audit(array $tableAliases): array {
		$codeKeys = [];
		$dbKeys = [];
		$looseColumns = [];
		$claimedColumns = [];
		$findings = [];
		$ownerTables = [];
		// Maps `connection|physical_table` -> registry alias so DB-sourced findings
		// (which only know the physical table) display under the same alias the matrix
		// and detail routes use.
		$aliasByPhysical = [];

		foreach ($tableAliases as $alias) {
			try {
				$table = $this->getTableLocator()->get($alias);
			} catch (Throwable $e) {
				$findings[] = new Finding(
					table: $alias,
					direction: Finding::DIRECTION_UNSUPPORTED,
					associationType: 'table',
					severity: Finding::SEVERITY_INFO,
					message: 'Table not introspectable: ' . $e->getMessage(),
				);

				continue;
			}

			$physicalKey = $table->getConnection()->configName() . '|' . $table->getTable();
			$ownerTables[$physicalKey] = [
				'connection' => $table->getConnection()->configName(),
				'table' => $table->getTable(),
			];
			$aliasByPhysical[$physicalKey] ??= $table->getRegistryAlias();

			[$tableCodeKeys, $unsupported] = $this->reader->read($table);
			$codeKeys = array_merge($codeKeys, $tableCodeKeys);
			$findings = array_merge($findings, $unsupported);
			$claimedColumns = array_merge($claimedColumns, $this->reader->claimedColumns($table));
		}

		// DB side: also introspect any owner table referenced only by code (e.g. junctions).
		foreach ($codeKeys as $fk) {
			$ownerTables[$fk->connection . '|' . $fk->ownerTable] = [
				'connection' => $fk->connection,
				'table' => $fk->ownerTable,
			];
		}

		foreach ($ownerTables as $owner) {
			try {
				$connection = ConnectionManager::get($owner['connection']);
				if (!$connection instanceof Connection) {
					continue;
				}
				$dbKeys = array_merge($dbKeys, $this->schema->foreignKeys($connection, $owner['table']));
				$looseColumns = array_merge($looseColumns, $this->schema->looseColumns($connection, $owner['table']));
			} catch (Throwable $e) {
				// Surface the failure rather than letting the table look "clean": it was
				// never actually compared against the DB.
				$findings[] = new Finding(
					table: $this->aliasFor($owner['connection'], $owner['table'], $aliasByPhysical),
					direction: Finding::DIRECTION_UNSUPPORTED,
					associationType: 'table',
					severity: Finding::SEVERITY_WARNING,
					message: sprintf('DB schema for `%s` could not be inspected: %s', $owner['table'], $e->getMessage()),
				);
			}
		}

		$findings = array_merge($findings, $this->diffForeignKeys($codeKeys, $dbKeys, $aliasByPhysical));
		$findings = array_merge($findings, $this->looseColumnFindings($looseColumns, $codeKeys, $this->ignoreColumns(), $aliasByPhysical, $claimedColumns));

		return $findings;
	}

	/**
	 * Resolve the display alias for a physical (connection, table) pair, keeping the
	 * connection dimension so same-named tables on different connections stay distinct.
	 *
	 * @param string $connection
	 * @param string $table
	 * @param array<string, string> $aliasByPhysical `connection|physical_table` => alias.
	 * @return string
	 */
	protected function aliasFor(string $connection, string $table, array $aliasByPhysical): string {
		return $aliasByPhysical[$connection . '|' . $table] ?? $table;
	}

	/**
	 * Pure symmetric diff between declared (code) and actual (DB) foreign keys.
	 *
	 * @param array<\TestHelper\Utility\Association\ForeignKey> $codeKeys
	 * @param array<\TestHelper\Utility\Association\ForeignKey> $dbKeys
	 * @param array<string, string> $aliasByPhysical `connection|physical_table` => alias, for DB-sourced findings.
	 * @return array<\TestHelper\Utility\Association\Finding>
	 */
	public function diffForeignKeys(array $codeKeys, array $dbKeys, array $aliasByPhysical = []): array {
		$findings = [];

		$dbByKey = [];
		$dbByOwner = [];
		foreach ($dbKeys as $fk) {
			$dbByKey[$fk->key()] = $fk;
			$dbByOwner[$fk->ownerKey()] = $fk;
		}

		$codeByKey = [];
		$codeByOwner = [];
		foreach ($codeKeys as $fk) {
			$codeByKey[$fk->key()] = $fk;
			$codeByOwner[$fk->ownerKey()] = $fk;
		}

		// Iterate the deduped set: reciprocal belongsTo/hasMany (and both ends of a
		// belongsToMany) normalize to the same key, so a single underlying FK is
		// reported once rather than once per declaration.
		foreach ($codeByKey as $fk) {
			if (isset($dbByKey[$fk->key()])) {
				continue;
			}

			if (isset($dbByOwner[$fk->ownerKey()])) {
				$dbFk = $dbByOwner[$fk->ownerKey()];
				$findings[] = new Finding(
					table: $fk->declaringTable ?? $fk->ownerTable,
					direction: Finding::DIRECTION_MISMATCH,
					associationType: $fk->associationType ?? 'belongsTo',
					severity: Finding::SEVERITY_ERROR,
					message: sprintf(
						'Association `%s` expects `%s.%s` -> `%s.%s`, but the DB FK points to `%s.%s`.',
						$fk->alias ?? $fk->referencedTable,
						$fk->ownerTable,
						$fk->column,
						$fk->referencedTable,
						$fk->referencedColumn,
						$dbFk->referencedTable,
						$dbFk->referencedColumn,
					),
					column: $fk->column,
					target: $fk->referencedTable,
					layer: Finding::LAYER_CONSTRAINT,
				);

				continue;
			}

			$severity = $fk->columnExists ? Finding::SEVERITY_WARNING : Finding::SEVERITY_ERROR;
			$message = $fk->columnExists
				? sprintf('Association `%s` has no DB foreign-key constraint on `%s.%s`.', $fk->alias ?? $fk->referencedTable, $fk->ownerTable, $fk->column)
				: sprintf('Association `%s` references column `%s.%s` which does not exist.', $fk->alias ?? $fk->referencedTable, $fk->ownerTable, $fk->column);

			$findings[] = new Finding(
				table: $fk->declaringTable ?? $fk->ownerTable,
				direction: Finding::DIRECTION_DB_MISSING,
				associationType: $fk->associationType ?? 'belongsTo',
				severity: $severity,
				message: $message,
				column: $fk->column,
				target: $fk->referencedTable,
				fixSnippet: $this->fixSuggester->migrationLine($fk),
				layer: Finding::LAYER_CONSTRAINT,
			);
		}

		// DB has it, no association declared (and not already reported as a mismatch).
		foreach ($dbKeys as $fk) {
			if (isset($codeByKey[$fk->key()]) || isset($codeByOwner[$fk->ownerKey()])) {
				continue;
			}

			$findings[] = new Finding(
				table: $this->aliasFor($fk->connection, $fk->ownerTable, $aliasByPhysical),
				direction: Finding::DIRECTION_CODE_MISSING,
				associationType: 'belongsTo',
				severity: Finding::SEVERITY_WARNING,
				message: sprintf(
					'DB foreign key `%s.%s` -> `%s.%s` has no matching association.',
					$fk->ownerTable,
					$fk->column,
					$fk->referencedTable,
					$fk->referencedColumn,
				),
				column: $fk->column,
				target: $fk->referencedTable,
				fixSnippet: $this->fixSuggester->associationCall($fk),
				layer: Finding::LAYER_CONSTRAINT,
			);
		}

		return $findings;
	}

	/**
	 * Pure secondary layer: `*_id` columns with no FK constraint and no association.
	 *
	 * @param array<\TestHelper\Utility\Association\LooseColumn> $looseColumns
	 * @param array<\TestHelper\Utility\Association\ForeignKey> $codeKeys
	 * @param array<string> $ignoreColumns
	 * @param array<string, string> $aliasByPhysical `connection|physical_table` => alias.
	 * @param array<string> $claimedColumns Extra `connection|table|column` ids claimed by unsupported associations.
	 * @return array<\TestHelper\Utility\Association\Finding>
	 */
	public function looseColumnFindings(array $looseColumns, array $codeKeys, array $ignoreColumns, array $aliasByPhysical = [], array $claimedColumns = []): array {
		$claimed = [];
		foreach ($codeKeys as $fk) {
			$claimed[$fk->connection . '|' . $fk->ownerTable . '|' . $fk->column] = true;
		}
		foreach ($claimedColumns as $id) {
			$claimed[$id] = true;
		}

		$findings = [];
		foreach ($looseColumns as $column) {
			if (in_array($column->column, $ignoreColumns, true)) {
				continue;
			}
			if (isset($claimed[$column->connection . '|' . $column->table . '|' . $column->column])) {
				continue;
			}

			$findings[] = new Finding(
				table: $this->aliasFor($column->connection, $column->table, $aliasByPhysical),
				direction: Finding::DIRECTION_CODE_MISSING,
				associationType: 'looseColumn',
				severity: Finding::SEVERITY_INFO,
				message: sprintf(
					'Column `%s.%s` looks like a foreign key but has no DB constraint and no association.',
					$column->table,
					$column->column,
				),
				column: $column->column,
				layer: Finding::LAYER_COLUMN,
			);
		}

		return $findings;
	}

	/**
	 * @return array<string>
	 */
	protected function ignoreColumns(): array {
		$configured = (array)Configure::read('TestHelper.associationAudit.ignoreColumns', []);

		return array_merge($this->defaultIgnoreColumns, $configured);
	}

}
