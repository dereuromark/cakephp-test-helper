<?php

namespace TestHelper\Utility\Association;

/**
 * A `*_id`-style column that exists in the DB without a real FK constraint.
 */
class LooseColumn {

	/**
	 * @param string $connection Connection name.
	 * @param string $table Table holding the column.
	 * @param string $column Column name.
	 */
	public function __construct(
		public readonly string $connection,
		public readonly string $table,
		public readonly string $column,
	) {
	}

}
