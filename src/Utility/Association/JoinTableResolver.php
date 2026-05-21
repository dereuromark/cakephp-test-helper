<?php

namespace TestHelper\Utility\Association;

use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Table;
use Throwable;

/**
 * Expands a belongsToMany into the two foreign-key expectations on its junction table.
 */
class JoinTableResolver {

	/**
	 * @param \Cake\ORM\Association\BelongsToMany $association
	 * @return array{0: array<\TestHelper\Utility\Association\ForeignKey>, 1: array<\TestHelper\Utility\Association\Finding>}
	 */
	public function resolve(BelongsToMany $association): array {
		$source = $association->getSource();
		$target = $association->getTarget();

		try {
			$junction = $association->junction();
		} catch (Throwable $e) {
			$finding = new Finding(
				table: $source->getRegistryAlias(),
				direction: Finding::DIRECTION_UNSUPPORTED,
				associationType: 'belongsToMany',
				severity: Finding::SEVERITY_INFO,
				message: sprintf('belongsToMany `%s`: junction table not resolvable (%s).', $association->getName(), $e->getMessage()),
				target: $target->getAlias(),
			);

			return [[], [$finding]];
		}

		$sourceForeignKey = $association->getForeignKey();
		$targetForeignKey = $association->getTargetForeignKey();
		if (!is_string($sourceForeignKey) || !is_string($targetForeignKey)) {
			$finding = new Finding(
				table: $source->getRegistryAlias(),
				direction: Finding::DIRECTION_UNSUPPORTED,
				associationType: 'belongsToMany',
				severity: Finding::SEVERITY_INFO,
				message: sprintf('belongsToMany `%s`: composite junction keys are not auto-verified.', $association->getName()),
				target: $target->getAlias(),
			);

			return [[], [$finding]];
		}

		// Resolve referenced columns from the actual binding keys (honoring custom bindingKey).
		$sourceBindingKey = $this->bindingColumn($association->getBindingKey(), $source->getPrimaryKey());
		$targetBindingKey = $this->bindingColumn($this->targetBindingKey($junction, $target->getAlias()), $target->getPrimaryKey());
		if ($sourceBindingKey === null || $targetBindingKey === null) {
			$finding = new Finding(
				table: $source->getRegistryAlias(),
				direction: Finding::DIRECTION_UNSUPPORTED,
				associationType: 'belongsToMany',
				severity: Finding::SEVERITY_INFO,
				message: sprintf('belongsToMany `%s`: composite binding key is not auto-verified.', $association->getName()),
				target: $target->getAlias(),
			);

			return [[], [$finding]];
		}

		$junctionConnection = $junction->getConnection()->configName();
		$junctionTable = $junction->getTable();
		$declaringTable = $source->getRegistryAlias();
		$junctionColumns = $this->safeColumns($junction);

		$keys = [
			new ForeignKey(
				connection: $junctionConnection,
				ownerTable: $junctionTable,
				column: $sourceForeignKey,
				referencedTable: $source->getTable(),
				referencedColumn: $sourceBindingKey,
				source: ForeignKey::SOURCE_CODE,
				associationType: 'belongsToMany',
				declaringTable: $declaringTable,
				alias: $association->getName(),
				columnExists: $junctionColumns === null || in_array($sourceForeignKey, $junctionColumns, true),
				ownerColumnType: $this->safeColumnType($junction, $sourceForeignKey),
				referencedColumnType: $this->safeColumnType($source, $sourceBindingKey),
			),
			new ForeignKey(
				connection: $junctionConnection,
				ownerTable: $junctionTable,
				column: $targetForeignKey,
				referencedTable: $target->getTable(),
				referencedColumn: $targetBindingKey,
				source: ForeignKey::SOURCE_CODE,
				associationType: 'belongsToMany',
				declaringTable: $declaringTable,
				alias: $association->getName(),
				columnExists: $junctionColumns === null || in_array($targetForeignKey, $junctionColumns, true),
				ownerColumnType: $this->safeColumnType($junction, $targetForeignKey),
				referencedColumnType: $this->safeColumnType($target, $targetBindingKey),
			),
		];

		return [$keys, []];
	}

	/**
	 * The binding key on the junction's belongsTo to the target, if resolvable.
	 *
	 * @param \Cake\ORM\Table $junction
	 * @param string $targetAlias
	 * @return array<string>|string|null
	 */
	protected function targetBindingKey(Table $junction, string $targetAlias): array|string|null {
		try {
			if (!$junction->hasAssociation($targetAlias)) {
				return null;
			}

			return $junction->getAssociation($targetAlias)->getBindingKey();
		} catch (Throwable $e) {
			return null;
		}
	}

	/**
	 * Resolve a single binding column. Composite (multi-column) binding keys return
	 * null so the caller can flag the association as unsupported rather than guessing.
	 *
	 * @param array<string>|string|null $bindingKey
	 * @param array<string>|string $primaryKey
	 * @return string|null
	 */
	protected function bindingColumn(array|string|null $bindingKey, array|string $primaryKey): ?string {
		$value = $bindingKey ?: $primaryKey;
		if (is_array($value)) {
			return count($value) === 1 ? (string)reset($value) : null;
		}

		return $value;
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

}
