<?php

namespace TestHelper\Utility\Association;

use Cake\ORM\Association;
use Cake\ORM\Association\BelongsTo;
use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Association\HasMany;
use Cake\ORM\Association\HasOne;
use Cake\ORM\Table;
use Throwable;

/**
 * Reads the code side: normalizes a table's associations into canonical foreign keys.
 *
 * The FK always lands on its real owner table: belongsTo keeps it on the source,
 * hasMany/hasOne move it to the target, belongsToMany puts both on the junction.
 */
class AssociationReader {

	protected JoinTableResolver $joinResolver;

	public function __construct() {
		$this->joinResolver = new JoinTableResolver();
	}

	/**
	 * @param \Cake\ORM\Table $table
	 * @return array{0: array<\TestHelper\Utility\Association\ForeignKey>, 1: array<\TestHelper\Utility\Association\Finding>}
	 */
	public function read(Table $table): array {
		$keys = [];
		$unsupported = [];

		foreach ($table->associations() as $association) {
			try {
				if ($association instanceof BelongsToMany) {
					[$bmKeys, $bmUnsupported] = $this->joinResolver->resolve($association);
					$keys = array_merge($keys, $bmKeys);
					$unsupported = array_merge($unsupported, $bmUnsupported);

					continue;
				}

				$foreignKey = $association->getForeignKey();
				if ($foreignKey === false) {
					$unsupported[] = $this->unsupported($table, $association, 'uses `foreignKey => false` (conditions-only join).');

					continue;
				}
				if (is_array($foreignKey)) {
					$unsupported[] = $this->unsupported($table, $association, 'uses a composite foreign key (not auto-verified).');

					continue;
				}

				$key = $this->normalize($association, $foreignKey);
				if ($key === null) {
					$unsupported[] = $this->unsupported($table, $association, 'has a composite binding key (not auto-verified).');

					continue;
				}

				$keys[] = $key;
			} catch (Throwable $e) {
				// A single misconfigured / non-introspectable association must never abort the audit.
				$unsupported[] = $this->unsupported($table, $association, 'could not be introspected: ' . $e->getMessage());
			}
		}

		return [$keys, $unsupported];
	}

	/**
	 * Every FK column any association touches, supported or not, as
	 * `connection|physical_table|column` identifiers. The loose-column layer uses this
	 * to avoid double-reporting a column that an unsupported association (composite /
	 * conditions-only) already explains.
	 *
	 * @param \Cake\ORM\Table $table
	 * @return array<string>
	 */
	public function claimedColumns(Table $table): array {
		$claimed = [];
		foreach ($table->associations() as $association) {
			try {
				foreach ($this->associationColumns($association) as $owner => $columns) {
					[$connection, $physicalTable] = explode('|', $owner, 2);
					foreach ($columns as $column) {
						$claimed[] = $connection . '|' . $physicalTable . '|' . $column;
					}
				}
			} catch (Throwable $e) {
				// Non-introspectable association: nothing to claim.
			}
		}

		return array_values(array_unique($claimed));
	}

	/**
	 * Owner (`connection|physical_table`) => FK column(s) for one association.
	 *
	 * @param \Cake\ORM\Association $association
	 * @return array<string, array<string>>
	 */
	protected function associationColumns(Association $association): array {
		$source = $association->getSource();
		$target = $association->getTarget();

		if ($association instanceof BelongsToMany) {
			$junction = $association->junction();
			$ownerKey = $junction->getConnection()->configName() . '|' . $junction->getTable();

			return [
				$ownerKey => array_merge(
					$this->columnList($association->getForeignKey()),
					$this->columnList($association->getTargetForeignKey()),
				),
			];
		}

		$foreignKey = $association->getForeignKey();
		if ($foreignKey === false) {
			return [];
		}

		$owner = $association instanceof BelongsTo ? $source : $target;
		$ownerKey = $owner->getConnection()->configName() . '|' . $owner->getTable();

		return [$ownerKey => $this->columnList($foreignKey)];
	}

	/**
	 * @param array<string>|string|false $foreignKey
	 * @return array<string>
	 */
	protected function columnList(array|string|false $foreignKey): array {
		if ($foreignKey === false) {
			return [];
		}

		return is_array($foreignKey) ? array_values($foreignKey) : [$foreignKey];
	}

	/**
	 * @param \Cake\ORM\Association $association
	 * @param string $foreignKey
	 * @return \TestHelper\Utility\Association\ForeignKey|null Null when the binding key is composite.
	 */
	protected function normalize(Association $association, string $foreignKey): ?ForeignKey {
		$source = $association->getSource();
		$target = $association->getTarget();

		if ($association instanceof BelongsTo) {
			// FK lives on the source table, references the target's binding key.
			$ownerTable = $source;
			$column = $foreignKey;
			$referenced = $target;
			$bindingKey = $association->getBindingKey();
		} elseif ($association instanceof HasMany || $association instanceof HasOne) {
			// FK lives on the target table, references the source's binding key.
			$ownerTable = $target;
			$column = $foreignKey;
			$referenced = $source;
			$bindingKey = $association->getBindingKey();
		} else {
			return null;
		}

		if (is_array($bindingKey)) {
			return null;
		}

		$referencedColumn = $bindingKey ?: 'id';
		$ownerColumns = $this->safeColumns($ownerTable);

		return new ForeignKey(
			connection: $ownerTable->getConnection()->configName(),
			ownerTable: $ownerTable->getTable(),
			column: $column,
			referencedTable: $referenced->getTable(),
			referencedColumn: $referencedColumn,
			source: ForeignKey::SOURCE_CODE,
			associationType: $this->type($association),
			declaringTable: $source->getRegistryAlias(),
			alias: $association->getName(),
			columnExists: $ownerColumns === null || in_array($column, $ownerColumns, true),
			ownerColumnType: $this->safeColumnType($ownerTable, $column),
			referencedColumnType: $this->safeColumnType($referenced, $referencedColumn),
		);
	}

	/**
	 * @param \Cake\ORM\Table $table
	 * @return array<string>|null Null when the schema cannot be described.
	 */
	protected function safeColumns(Table $table): ?array {
		try {
			return $table->getSchema()->columns();
		} catch (Throwable $e) {
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
		} catch (Throwable $e) {
			return null;
		}
	}

	/**
	 * @param \Cake\ORM\Table $table
	 * @param \Cake\ORM\Association $association
	 * @param string $reason
	 * @return \TestHelper\Utility\Association\Finding
	 */
	protected function unsupported(Table $table, Association $association, string $reason): Finding {
		// Target resolution may itself be the cause of the failure (e.g. an unloaded
		// target class), so it must not be allowed to throw again here.
		$target = null;
		try {
			$target = $association->getTarget()->getAlias();
		} catch (Throwable $e) {
			// Leave target unresolved.
		}

		return new Finding(
			table: $table->getRegistryAlias(),
			direction: Finding::DIRECTION_UNSUPPORTED,
			associationType: $this->type($association),
			severity: Finding::SEVERITY_INFO,
			message: sprintf('Association `%s` %s', $association->getName(), $reason),
			target: $target,
		);
	}

	/**
	 * @param \Cake\ORM\Association $association
	 * @return string
	 */
	protected function type(Association $association): string {
		return match (true) {
			$association instanceof BelongsTo => 'belongsTo',
			$association instanceof HasMany => 'hasMany',
			$association instanceof HasOne => 'hasOne',
			$association instanceof BelongsToMany => 'belongsToMany',
			default => 'unknown',
		};
	}

}
