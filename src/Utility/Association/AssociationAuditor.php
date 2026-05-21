<?php

namespace TestHelper\Utility\Association;

use Cake\Core\Configure;
use Cake\Database\Connection;
use Cake\Database\Schema\TableSchema;
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

	/**
	 * Abstract integer types, narrowest to widest. Treated as one family for key-type
	 * comparison: same-family widths agree, but an owner narrower than the referenced key
	 * cannot hold every value, so that direction is flagged.
	 *
	 * @var array<string>
	 */
	protected const INTEGER_TYPES = ['tinyinteger', 'smallinteger', 'integer', 'biginteger'];

	/**
	 * Bucket name the integer family collapses to.
	 *
	 * @var string
	 */
	protected const BUCKET_INTEGER = 'integer';

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
		$findings = array_merge($findings, $this->typeFindings($codeKeys, $this->preferIntegerKeys()));
		$findings = array_merge($findings, $this->ruleFindings($codeKeys, $dbKeys));

		return $findings;
	}

	/**
	 * Order findings worst-first for flat/grouped display: severity (error -> warning ->
	 * info), then table, then column, then message. audit() emits in phase order, so this
	 * is what turns the raw list into a stable, triage-friendly one. The message tiebreak
	 * keeps order content-determined (independent of emission order) when several findings
	 * share severity/table/column, e.g. multiple unsupported associations on one table.
	 *
	 * @param array<\TestHelper\Utility\Association\Finding> $findings
	 * @return array<\TestHelper\Utility\Association\Finding>
	 */
	public function sortFindings(array $findings): array {
		usort($findings, function (Finding $a, Finding $b): int {
			return (Finding::SEVERITY_RANK[$b->severity] ?? 0) <=> (Finding::SEVERITY_RANK[$a->severity] ?? 0)
				?: strcmp($a->table, $b->table)
				?: strcmp($a->column ?? '', $b->column ?? '')
				?: strcmp($a->message, $b->message);
		});

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

			// A missing column is a different (worse) problem than a missing constraint:
			// you cannot add a foreign key to a column that does not exist, so it gets its
			// own direction and a column-adding fix rather than the addForeignKey snippet.
			if (!$fk->columnExists) {
				$findings[] = new Finding(
					table: $fk->declaringTable ?? $fk->ownerTable,
					direction: Finding::DIRECTION_COLUMN_MISSING,
					associationType: $fk->associationType ?? 'belongsTo',
					severity: Finding::SEVERITY_ERROR,
					message: sprintf('Association `%s` references column `%s.%s` which does not exist.', $fk->alias ?? $fk->referencedTable, $fk->ownerTable, $fk->column),
					column: $fk->column,
					target: $fk->referencedTable,
					fixSnippet: $this->fixSuggester->columnLine($fk),
					layer: Finding::LAYER_COLUMN,
				);

				continue;
			}

			$findings[] = new Finding(
				table: $fk->declaringTable ?? $fk->ownerTable,
				direction: Finding::DIRECTION_DB_MISSING,
				associationType: $fk->associationType ?? 'belongsTo',
				severity: Finding::SEVERITY_WARNING,
				message: sprintf('Association `%s` has no DB foreign-key constraint on `%s.%s`.', $fk->alias ?? $fk->referencedTable, $fk->ownerTable, $fk->column),
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
			foreach ($fk->columns as $column) {
				$claimed[$fk->connection . '|' . $fk->ownerTable . '|' . $column] = true;
			}
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
	 * Pure key-type layer: compares each declared FK column's type against its referenced
	 * column's type:
	 *
	 * - different type families (e.g. integer referencing uuid) are an error;
	 * - within the integer family, an owner narrower than the referenced key (e.g. integer
	 *   referencing biginteger) is a warning, since it cannot hold every referenced value
	 *   (a wider or equal owner is fine);
	 * - matching non-integer families (e.g. both uuid) are info, since integer keys are the
	 *   ideal - silenced via `TestHelper.associationAudit.preferIntegerKeys`.
	 *
	 * @param array<\TestHelper\Utility\Association\ForeignKey> $codeKeys
	 * @param bool|null $preferIntegerKeys When false, the non-integer info advisory is suppressed
	 *   (errors remain); null falls back to the `TestHelper.associationAudit.preferIntegerKeys` config.
	 * @return array<\TestHelper\Utility\Association\Finding>
	 */
	public function typeFindings(array $codeKeys, ?bool $preferIntegerKeys = null): array {
		$preferIntegerKeys ??= $this->preferIntegerKeys();
		$seen = [];
		$findings = [];

		foreach ($codeKeys as $fk) {
			if ($fk->ownerColumnType === null || $fk->referencedColumnType === null) {
				continue;
			}
			// Dedupe reciprocal declarations of the same physical FK.
			if (isset($seen[$fk->key()])) {
				continue;
			}
			$seen[$fk->key()] = true;

			$ownerBucket = $this->typeBucket($fk->ownerColumnType);
			$referencedBucket = $this->typeBucket($fk->referencedColumnType);

			if ($ownerBucket !== $referencedBucket) {
				$findings[] = new Finding(
					table: $fk->declaringTable ?? $fk->ownerTable,
					direction: Finding::DIRECTION_TYPE,
					associationType: $fk->associationType ?? 'belongsTo',
					severity: Finding::SEVERITY_ERROR,
					message: sprintf(
						'Key type mismatch: `%s.%s` (%s) references `%s.%s` (%s).',
						$fk->ownerTable,
						$fk->column,
						$fk->ownerColumnType,
						$fk->referencedTable,
						$fk->referencedColumn,
						$fk->referencedColumnType,
					),
					column: $fk->column,
					target: $fk->referencedTable,
					fixSnippet: $this->fixSuggester->typeAlignmentLine($fk),
					layer: Finding::LAYER_TYPE,
				);

				continue;
			}

			if ($ownerBucket === static::BUCKET_INTEGER) {
				$ownerRank = array_search($fk->ownerColumnType, static::INTEGER_TYPES, true);
				$referencedRank = array_search($fk->referencedColumnType, static::INTEGER_TYPES, true);
				// Equal or wider owner (or an unknown integer subtype) holds every value.
				if ($ownerRank === false || $referencedRank === false || $ownerRank >= $referencedRank) {
					continue;
				}

				$findings[] = new Finding(
					table: $fk->declaringTable ?? $fk->ownerTable,
					direction: Finding::DIRECTION_TYPE,
					associationType: $fk->associationType ?? 'belongsTo',
					severity: Finding::SEVERITY_WARNING,
					message: sprintf(
						'Key `%s.%s` (%s) is narrower than the referenced key `%s.%s` (%s); it cannot hold every referenced value.',
						$fk->ownerTable,
						$fk->column,
						$fk->ownerColumnType,
						$fk->referencedTable,
						$fk->referencedColumn,
						$fk->referencedColumnType,
					),
					column: $fk->column,
					target: $fk->referencedTable,
					// A narrower integer FK is widened to its referenced key by the same changeColumn fix.
					fixSnippet: $this->fixSuggester->typeAlignmentLine($fk),
					layer: Finding::LAYER_TYPE,
				);

				continue;
			}

			// Matching non-integer keys: integer keys are the ideal, so note it as info
			// unless the app deliberately standardizes on another key type.
			if (!$preferIntegerKeys) {
				continue;
			}

			$findings[] = new Finding(
				table: $fk->declaringTable ?? $fk->ownerTable,
				direction: Finding::DIRECTION_TYPE,
				associationType: $fk->associationType ?? 'belongsTo',
				severity: Finding::SEVERITY_INFO,
				message: sprintf(
					'Relation `%s.%s` -> `%s.%s` uses non-integer keys (%s); integer keys are preferred.',
					$fk->ownerTable,
					$fk->column,
					$fk->referencedTable,
					$fk->referencedColumn,
					$fk->ownerColumnType,
				),
				column: $fk->column,
				target: $fk->referencedTable,
				layer: Finding::LAYER_TYPE,
			);
		}

		return $findings;
	}

	/**
	 * Pure cascade-rule layer: compares each dependent association's ORM intent against the
	 * matched DB FK's `ON DELETE` rule. Divergence is reported as info, not an error, because
	 * relying on ORM-level cascades with a `NO ACTION` DB rule is a deliberate, common CakePHP
	 * setup. Only hasMany/hasOne carry a dependent intent; belongsTo and DB-only keys are
	 * skipped. `ON UPDATE` has no ORM equivalent and is not compared.
	 *
	 * @param array<\TestHelper\Utility\Association\ForeignKey> $codeKeys
	 * @param array<\TestHelper\Utility\Association\ForeignKey> $dbKeys
	 * @return array<\TestHelper\Utility\Association\Finding>
	 */
	public function ruleFindings(array $codeKeys, array $dbKeys): array {
		$dbByKey = [];
		foreach ($dbKeys as $fk) {
			$dbByKey[$fk->key()] = $fk;
		}

		$findings = [];
		foreach ($codeKeys as $fk) {
			// Each association is checked on its own: aliases sharing one physical FK can carry
			// different `dependent` settings, so deduping by key() here would drop real findings.
			// Reciprocal belongsTo declarations carry a null intent and fall through below.
			if ($fk->dependent === null) {
				continue;
			}

			$dbFk = $dbByKey[$fk->key()] ?? null;
			if ($dbFk === null || $dbFk->onDelete === null) {
				// No comparable DB FK: the constraint layer already reports the missing FK.
				continue;
			}

			$cascades = $dbFk->onDelete === TableSchema::ACTION_CASCADE;

			if ($fk->dependent && !$cascades) {
				$findings[] = new Finding(
					table: $fk->declaringTable ?? $fk->ownerTable,
					direction: Finding::DIRECTION_RULE,
					associationType: $fk->associationType ?? 'hasMany',
					severity: Finding::SEVERITY_INFO,
					message: sprintf(
						'Association `%s` is `dependent` (app-level cascade delete) but the DB FK `%s.%s` uses ON DELETE %s; a delete issued directly in SQL will not cascade.',
						$fk->alias ?? $fk->referencedTable,
						$fk->ownerTable,
						$fk->column,
						$this->ruleLabel($dbFk->onDelete),
					),
					column: $fk->column,
					target: $fk->referencedTable,
					fixSnippet: $this->fixSuggester->cascadeMigrationLine($fk, $dbFk->onUpdate),
					layer: Finding::LAYER_RULE,
				);

				continue;
			}

			if (!$fk->dependent && $cascades) {
				$findings[] = new Finding(
					table: $fk->declaringTable ?? $fk->ownerTable,
					direction: Finding::DIRECTION_RULE,
					associationType: $fk->associationType ?? 'hasMany',
					severity: Finding::SEVERITY_INFO,
					message: sprintf(
						'DB FK `%s.%s` uses ON DELETE CASCADE but association `%s` is not `dependent`; rows the DB removes will not trigger ORM callbacks.',
						$fk->ownerTable,
						$fk->column,
						$fk->alias ?? $fk->referencedTable,
					),
					column: $fk->column,
					target: $fk->referencedTable,
					fixSnippet: $this->fixSuggester->dependentOption($fk),
					layer: Finding::LAYER_RULE,
				);
			}
		}

		return $findings;
	}

	/**
	 * Human-readable SQL label for an internal cascade-action string.
	 *
	 * @param string $action One of the TableSchema::ACTION_* values.
	 * @return string
	 */
	protected function ruleLabel(string $action): string {
		return match ($action) {
			TableSchema::ACTION_CASCADE => 'CASCADE',
			TableSchema::ACTION_SET_NULL => 'SET NULL',
			TableSchema::ACTION_NO_ACTION => 'NO ACTION',
			TableSchema::ACTION_RESTRICT => 'RESTRICT',
			TableSchema::ACTION_SET_DEFAULT => 'SET DEFAULT',
			default => strtoupper($action),
		};
	}

	/**
	 * Whether to emit the info-level "integer keys are preferred" finding for relations
	 * that use matching non-integer keys. Apps standardized on uuid keys can disable it.
	 *
	 * @return bool
	 */
	protected function preferIntegerKeys(): bool {
		return (bool)Configure::read('TestHelper.associationAudit.preferIntegerKeys', true);
	}

	/**
	 * Collapse the integer family into one bucket; other types keep their own name.
	 *
	 * @param string $type Abstract DB type.
	 * @return string
	 */
	protected function typeBucket(string $type): string {
		return in_array($type, static::INTEGER_TYPES, true) ? static::BUCKET_INTEGER : $type;
	}

	/**
	 * @return array<string>
	 */
	protected function ignoreColumns(): array {
		$configured = (array)Configure::read('TestHelper.associationAudit.ignoreColumns', []);

		return array_merge($this->defaultIgnoreColumns, $configured);
	}

}
