<?php

namespace TestHelper\Utility\Association;

/**
 * A canonical foreign-key expectation.
 *
 * Both a declared association (normalized so the FK always lands on its real owner
 * table) and a real DB FK constraint reduce to this same shape, which is what makes
 * the symmetric diff possible. The reciprocal halves of a belongsTo/hasMany pair
 * normalize to an identical instance and therefore dedupe naturally.
 */
class ForeignKey {

	/**
     * @var string
     */
	public const SOURCE_DB = 'db';

	/**
     * @var string
     */
	public const SOURCE_CODE = 'code';

	/**
	 * Owner FK column(s), in order. A composite FK has more than one.
	 *
	 * @var array<string>
	 */
	public readonly array $columns;

	/**
	 * Referenced column(s), positionally aligned with $columns.
	 *
	 * @var array<string>
	 */
	public readonly array $referencedColumns;

	/**
	 * Display form of the owner column(s): the single name, or comma-joined for composite.
	 *
	 * @var string
	 */
	public readonly string $column;

	/**
	 * Display form of the referenced column(s).
	 *
	 * @var string
	 */
	public readonly string $referencedColumn;

	/**
	 * @param string $connection Connection name (same table name on another connection is a different target).
	 * @param string $ownerTable Table that physically holds the FK column.
	 * @param array<string>|string $column FK column(s) on the owner table.
	 * @param string $referencedTable Table the FK points at.
	 * @param array<string>|string $referencedColumn Referenced column(s) (usually the PK; honors bindingKey).
	 * @param string $source self::SOURCE_DB or self::SOURCE_CODE.
	 * @param string|null $associationType Association type for code-sourced keys.
	 * @param string|null $declaringTable Table whose class declared the association (code-sourced).
	 * @param string|null $alias Association alias (code-sourced).
	 * @param bool $columnExists Whether the owner column(s) physically exist (code-sourced).
	 * @param string|null $ownerColumnType Abstract DB type of the owner column (code-sourced, single-column only), or null.
	 * @param string|null $referencedColumnType Abstract DB type of the referenced column (code-sourced, single-column only), or null.
	 * @param bool|null $dependent ORM cascade-delete intent (code-sourced hasMany/hasOne); null when not applicable.
	 * @param string|null $onUpdate DB `ON UPDATE` rule (DB-sourced), or null.
	 * @param string|null $onDelete DB `ON DELETE` rule (DB-sourced), or null.
	 */
	public function __construct(
		public readonly string $connection,
		public readonly string $ownerTable,
		array|string $column,
		public readonly string $referencedTable,
		array|string $referencedColumn,
		public readonly string $source,
		public readonly ?string $associationType = null,
		public readonly ?string $declaringTable = null,
		public readonly ?string $alias = null,
		public readonly bool $columnExists = true,
		public readonly ?string $ownerColumnType = null,
		public readonly ?string $referencedColumnType = null,
		public readonly ?bool $dependent = null,
		public readonly ?string $onUpdate = null,
		public readonly ?string $onDelete = null,
	) {
		$this->columns = is_array($column) ? array_values($column) : [$column];
		$this->referencedColumns = is_array($referencedColumn) ? array_values($referencedColumn) : [$referencedColumn];
		$this->column = implode(', ', $this->columns);
		$this->referencedColumn = implode(', ', $this->referencedColumns);
	}

	/**
	 * Whether this FK spans more than one column.
	 *
	 * @return bool
	 */
	public function isComposite(): bool {
		return count($this->columns) > 1;
	}

	/**
	 * Full identity: two keys with the same value describe the exact same FK.
	 *
	 * @return string
	 */
	public function key(): string {
		return implode('|', [
			$this->connection,
			$this->ownerTable,
			$this->column,
			$this->referencedTable,
			$this->referencedColumn,
		]);
	}

	/**
	 * Owner-side identity: same owner column, target may differ. Used to spot mismatches.
	 *
	 * @return string
	 */
	public function ownerKey(): string {
		return implode('|', [$this->connection, $this->ownerTable, $this->column]);
	}

}
