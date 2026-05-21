<?php

namespace TestHelper\Utility\Association;

use Cake\Database\Schema\TableSchema;
use Cake\Utility\Inflector;

/**
 * Builds copy-paste fix snippets for findings. Read-only: never applies anything.
 */
class FixSuggester {

	/**
	 * Association call to add on the declaring table when the DB has the FK but the
	 * code does not. The reciprocal `belongsTo` is the most common fix for a stray FK.
	 *
	 * @param \TestHelper\Utility\Association\ForeignKey $fk DB-sourced foreign key.
	 * @return string
	 */
	public function associationCall(ForeignKey $fk): string {
		$alias = Inflector::camelize($fk->referencedTable);
		$ownerAlias = Inflector::camelize($fk->ownerTable);

		// A stray FK on owner -> referenced means the owner is missing a belongsTo,
		// and the referenced table is missing the reciprocal hasMany.
		$foreignKey = $this->columnArg($fk->columns);
		$bindingKey = $this->bindingKeyOption($fk);
		$belongsTo = sprintf(
			"// On %sTable::initialize():\n\$this->belongsTo('%s', ['foreignKey' => %s%s]);",
			$ownerAlias,
			$alias,
			$foreignKey,
			$bindingKey,
		);

		$hasMany = sprintf(
			"// Reciprocal, on %sTable::initialize():\n\$this->hasMany('%s', ['foreignKey' => %s%s]);",
			$alias,
			$ownerAlias,
			$foreignKey,
			$bindingKey,
		);

		return $belongsTo . "\n" . $hasMany;
	}

	/**
	 * Migration line to align a FK column's type with its referenced (PK) column when the
	 * two disagree. The referenced column is treated as canonical, so the FK column is the
	 * one changed; flip it if the PK is the side that should move.
	 *
	 * @param \TestHelper\Utility\Association\ForeignKey $fk Code-sourced foreign key with a type mismatch.
	 * @return string
	 */
	public function typeAlignmentLine(ForeignKey $fk): string {
		return sprintf(
			"// On the `%s` table migration: align `%s` with `%s.%s`.\n"
			. "// changeColumn replaces the whole definition, so carry over the column's existing\n"
			. "// null/default/limit options below or they revert to defaults.\n"
			. "\$table->changeColumn('%s', '%s', [\n"
			. "    // 'null' => false, 'default' => null, ...\n"
			. ']);',
			$fk->ownerTable,
			$fk->column,
			$fk->referencedTable,
			$fk->referencedColumn,
			$fk->column,
			(string)$fk->referencedColumnType,
		);
	}

	/**
	 * Migration to align a DB FK with a `dependent` association: drop and re-add it with
	 * `ON DELETE CASCADE` so a direct SQL delete cascades like the ORM already does. The
	 * existing `ON UPDATE` rule is preserved so only the delete behavior changes.
	 *
	 * @param \TestHelper\Utility\Association\ForeignKey $fk Code-sourced foreign key.
	 * @param string|null $onUpdate Current DB `ON UPDATE` rule (TableSchema action), preserved as-is.
	 * @return string
	 */
	public function cascadeMigrationLine(ForeignKey $fk, ?string $onUpdate = null): string {
		$columns = $this->columnArg($fk->columns);

		return sprintf(
			"// On the `%s` table migration: match the ORM's dependent cascade.\n"
			. "\$table->dropForeignKey(%s)\n"
			. "    ->addForeignKey(%s, '%s', %s, [\n"
			. "        'update' => '%s', 'delete' => 'CASCADE',\n"
			. '    ]);',
			$fk->ownerTable,
			$columns,
			$columns,
			$fk->referencedTable,
			$this->columnArg($fk->referencedColumns),
			$this->migrationAction($onUpdate),
		);
	}

	/**
	 * Association-side hint: mark the association `dependent` and `cascadeCallbacks` so the
	 * ORM loads and deletes children (firing their callbacks) when the DB already cascades.
	 * `dependent` alone uses a bulk `deleteAll()` that skips child callbacks.
	 *
	 * @param \TestHelper\Utility\Association\ForeignKey $fk Code-sourced foreign key.
	 * @return string
	 */
	public function dependentOption(ForeignKey $fk): string {
		return sprintf(
			"// On %sTable::initialize(), if the DB cascade is intended:\n"
			. "\$this->%s('%s', ['foreignKey' => %s%s, 'dependent' => true, 'cascadeCallbacks' => true]);",
			$fk->declaringTable ?? Inflector::camelize($fk->referencedTable),
			$fk->associationType ?? 'hasMany',
			$fk->alias ?? Inflector::camelize($fk->ownerTable),
			$this->columnArg($fk->columns),
			$this->bindingKeyOption($fk),
		);
	}

	/**
	 * Map an internal TableSchema cascade-action string to the Phinx migration option value
	 * this plugin uses (e.g. `setNull` -> `SET_NULL`). Unknown/null defaults to `NO_ACTION`.
	 *
	 * @param string|null $action TableSchema::ACTION_* value, or null.
	 * @return string
	 */
	protected function migrationAction(?string $action): string {
		return match ($action) {
			TableSchema::ACTION_CASCADE => 'CASCADE',
			TableSchema::ACTION_RESTRICT => 'RESTRICT',
			TableSchema::ACTION_SET_NULL => 'SET_NULL',
			TableSchema::ACTION_SET_DEFAULT => 'SET_DEFAULT',
			default => 'NO_ACTION',
		};
	}

	/**
	 * Migration line to add when an association is declared but the DB has no FK constraint.
	 *
	 * @param \TestHelper\Utility\Association\ForeignKey $fk Code-sourced foreign key.
	 * @return string
	 */
	public function migrationLine(ForeignKey $fk): string {
		return sprintf(
			"\$table->addForeignKey(%s, '%s', %s, [\n"
			. "    'update' => 'NO_ACTION', 'delete' => 'NO_ACTION',\n"
			. ']);',
			$this->columnArg($fk->columns),
			$fk->referencedTable,
			$this->columnArg($fk->referencedColumns),
		);
	}

	/**
	 * Migration snippet when the association's owner column does not exist yet: add the
	 * column first (a foreign key cannot be placed on a missing column), then the
	 * constraint. The new column adopts the referenced key's abstract type when known;
	 * only the type is introspected, so the snippet reminds you to match length/options.
	 *
	 * @param \TestHelper\Utility\Association\ForeignKey $fk Code-sourced foreign key.
	 * @return string
	 */
	public function columnLine(ForeignKey $fk): string {
		// The first missing column carries the example addColumn; composite keys add the rest by hand.
		$firstColumn = $fk->columns[0];

		return sprintf(
			"// `%s.%s` is missing - add it to match `%s.%s` (copy its type/length), then the constraint:\n"
			. "\$table->addColumn('%s', '%s', ['null' => true]);\n"
			. "\$table->addForeignKey(%s, '%s', %s, [\n"
			. "    'update' => 'NO_ACTION', 'delete' => 'NO_ACTION',\n"
			. ']);',
			$fk->ownerTable,
			$fk->column,
			$fk->referencedTable,
			$fk->referencedColumn,
			$firstColumn,
			$fk->referencedColumnType ?? 'integer',
			$this->columnArg($fk->columns),
			$fk->referencedTable,
			$this->columnArg($fk->referencedColumns),
		);
	}

	/**
	 * A `, 'bindingKey' => ...` association option, but only when the referenced column(s)
	 * are non-default (composite, or a single column other than `id`). The default `id`
	 * binding needs no explicit option, keeping the common snippet clean.
	 *
	 * @param \TestHelper\Utility\Association\ForeignKey $fk
	 * @return string
	 */
	protected function bindingKeyOption(ForeignKey $fk): string {
		if ($fk->referencedColumns === ['id']) {
			return '';
		}

		return ", 'bindingKey' => " . $this->columnArg($fk->referencedColumns);
	}

	/**
	 * Render FK column(s) as a migration/association argument: a quoted name for a single
	 * column, or a bracketed array for a composite key.
	 *
	 * @param array<string> $columns
	 * @return string
	 */
	protected function columnArg(array $columns): string {
		if (count($columns) === 1) {
			return "'" . $columns[0] . "'";
		}

		return "['" . implode("', '", $columns) . "']";
	}

}
