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
	 * @param string $connection Connection name (same table name on another connection is a different target).
	 * @param string $ownerTable Table that physically holds the FK column.
	 * @param string $column FK column on the owner table.
	 * @param string $referencedTable Table the FK points at.
	 * @param string $referencedColumn Referenced column (usually the PK; honors bindingKey).
	 * @param string $source self::SOURCE_DB or self::SOURCE_CODE.
	 * @param string|null $associationType Association type for code-sourced keys.
	 * @param string|null $declaringTable Table whose class declared the association (code-sourced).
	 * @param string|null $alias Association alias (code-sourced).
	 * @param bool $columnExists Whether the owner column physically exists (code-sourced).
	 */
	public function __construct(
		public readonly string $connection,
		public readonly string $ownerTable,
		public readonly string $column,
		public readonly string $referencedTable,
		public readonly string $referencedColumn,
		public readonly string $source,
		public readonly ?string $associationType = null,
		public readonly ?string $declaringTable = null,
		public readonly ?string $alias = null,
		public readonly bool $columnExists = true,
	) {
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
