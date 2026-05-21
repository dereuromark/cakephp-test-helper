<?php

namespace TestHelper\Utility\Association;

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
		// Composite: some component columns may already exist, so don't blindly re-add them
		// all - point at the missing one(s) and let the constraint follow.
		if ($fk->isComposite()) {
			return sprintf(
				"// Some of `%s` (%s) are missing - add the missing component column(s) to match\n"
				. "// `%s` (%s), then add the constraint:\n"
				. "\$table->addForeignKey(%s, '%s', %s, [\n"
				. "    'update' => 'NO_ACTION', 'delete' => 'NO_ACTION',\n"
				. ']);',
				$fk->ownerTable,
				$fk->column,
				$fk->referencedTable,
				$fk->referencedColumn,
				$this->columnArg($fk->columns),
				$fk->referencedTable,
				$this->columnArg($fk->referencedColumns),
			);
		}

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
			$fk->columns[0],
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
