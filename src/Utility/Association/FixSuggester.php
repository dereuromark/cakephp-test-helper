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
		$belongsTo = sprintf(
			"// On %sTable::initialize():\n\$this->belongsTo('%s', ['foreignKey' => '%s']);",
			$ownerAlias,
			$alias,
			$fk->column,
		);

		$hasMany = sprintf(
			"// Reciprocal, on %sTable::initialize():\n\$this->hasMany('%s', ['foreignKey' => '%s']);",
			$alias,
			$ownerAlias,
			$fk->column,
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
			"\$table->addForeignKey('%s', '%s', '%s', [\n"
			. "    'update' => 'NO_ACTION', 'delete' => 'NO_ACTION',\n"
			. ']);',
			$fk->column,
			$fk->referencedTable,
			$fk->referencedColumn,
		);
	}

}
