<?php

namespace TestHelper\Utility\Association;

use Cake\ORM\Association\BelongsToMany;
use Cake\ORM\Table;
use Throwable;

/**
 * Expands a belongsToMany into the two foreign-key expectations on its junction table.
 */
class JoinTableResolver {

	use SchemaColumnAccessTrait;

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

		// Resolve referenced columns from the actual binding keys (honoring custom bindingKey).
		$sourceColumns = $this->columnList($association->getForeignKey());
		$targetColumns = $this->columnList($association->getTargetForeignKey());
		$sourceBindingColumns = $this->bindingColumns($association->getBindingKey(), $source->getPrimaryKey());
		$targetBindingColumns = $this->bindingColumns($this->targetBindingKey($junction, $target->getAlias()), $target->getPrimaryKey());
		if (!$sourceColumns || !$targetColumns) {
			// No usable join column(s): foreignKey/targetForeignKey is false or empty, i.e. a
			// conditions-only join rather than a composite-key misalignment.
			$finding = new Finding(
				table: $source->getRegistryAlias(),
				direction: Finding::DIRECTION_UNSUPPORTED,
				associationType: 'belongsToMany',
				severity: Finding::SEVERITY_INFO,
				message: sprintf('belongsToMany `%s`: has no usable join key (conditions-only join, not auto-verified).', $association->getName()),
				target: $target->getAlias(),
			);

			return [[], [$finding]];
		}
		if (count($sourceColumns) !== count($sourceBindingColumns) || count($targetColumns) !== count($targetBindingColumns)) {
			$finding = new Finding(
				table: $source->getRegistryAlias(),
				direction: Finding::DIRECTION_UNSUPPORTED,
				associationType: 'belongsToMany',
				severity: Finding::SEVERITY_INFO,
				message: sprintf('belongsToMany `%s`: composite junction keys do not line up (not auto-verified).', $association->getName()),
				target: $target->getAlias(),
			);

			return [[], [$finding]];
		}

		$junctionConnection = $junction->getConnection()->configName();
		$junctionTable = $junction->getTable();
		$declaringTable = $source->getRegistryAlias();
		$junctionColumns = $this->safeColumns($junction);
		$sourceComposite = count($sourceColumns) > 1;
		$targetComposite = count($targetColumns) > 1;

		$keys = [
			new ForeignKey(
				connection: $junctionConnection,
				ownerTable: $junctionTable,
				column: $sourceColumns,
				referencedTable: $source->getTable(),
				referencedColumn: $sourceBindingColumns,
				source: ForeignKey::SOURCE_CODE,
				associationType: 'belongsToMany',
				declaringTable: $declaringTable,
				alias: $association->getName(),
				columnExists: $junctionColumns === null || !array_diff($sourceColumns, $junctionColumns),
				ownerColumnType: $sourceComposite ? null : $this->safeColumnType($junction, $sourceColumns[0]),
				referencedColumnType: $sourceComposite ? null : $this->safeColumnType($source, $sourceBindingColumns[0]),
			),
			new ForeignKey(
				connection: $junctionConnection,
				ownerTable: $junctionTable,
				column: $targetColumns,
				referencedTable: $target->getTable(),
				referencedColumn: $targetBindingColumns,
				source: ForeignKey::SOURCE_CODE,
				associationType: 'belongsToMany',
				declaringTable: $declaringTable,
				alias: $association->getName(),
				columnExists: $junctionColumns === null || !array_diff($targetColumns, $junctionColumns),
				ownerColumnType: $targetComposite ? null : $this->safeColumnType($junction, $targetColumns[0]),
				referencedColumnType: $targetComposite ? null : $this->safeColumnType($target, $targetBindingColumns[0]),
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
	 * Normalize a junction foreign key (array/string, possibly empty) into a column list.
	 *
	 * @param array<int|string, string|false>|string|false $foreignKey
	 * @return array<string>
	 */
	protected function columnList(array|string|false $foreignKey): array {
		if ($foreignKey === false) {
			return [];
		}

		return array_values(array_filter(array_map('strval', (array)$foreignKey), fn (string $c): bool => $c !== ''));
	}

	/**
	 * Resolve the binding column(s) for one side of the junction: the explicit binding key,
	 * falling back to the table's primary key.
	 *
	 * @param array<string>|string|null $bindingKey
	 * @param array<string>|string $primaryKey
	 * @return array<string>
	 */
	protected function bindingColumns(array|string|null $bindingKey, array|string $primaryKey): array {
		$value = $bindingKey ?: $primaryKey;

		return array_values(array_filter(array_map('strval', (array)$value), fn (string $c): bool => $c !== ''));
	}

}
