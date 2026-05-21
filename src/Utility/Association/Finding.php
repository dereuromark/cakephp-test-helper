<?php

namespace TestHelper\Utility\Association;

/**
 * One association/DB mismatch, ready to display.
 */
class Finding {

	/**
	 * DB has the FK/column, but no association declares it.
     * @var string
	 */
	public const DIRECTION_CODE_MISSING = 'code_missing';

	/**
	 * An association is declared, but the DB has no matching FK constraint.
     * @var string
	 */
	public const DIRECTION_DB_MISSING = 'db_missing';

	/**
	 * Both sides exist but disagree (different target table / referenced column).
     * @var string
	 */
	public const DIRECTION_MISMATCH = 'mismatch';

	/**
	 * Association config that has no clean DB-FK equivalent; reported for awareness only.
     * @var string
	 */
	public const DIRECTION_UNSUPPORTED = 'unsupported';

	/**
     * @var string
     */
	public const SEVERITY_ERROR = 'error';

	/**
     * @var string
     */
	public const SEVERITY_WARNING = 'warning';

	/**
     * @var string
     */
	public const SEVERITY_INFO = 'info';

	/**
     * @var string
     */
	public const LAYER_CONSTRAINT = 'constraint';

	/**
     * @var string
     */
	public const LAYER_COLUMN = 'column';

	/**
	 * @param string $table Table to display this finding under.
	 * @param string $direction One of the DIRECTION_* constants.
	 * @param string $associationType belongsTo|hasMany|hasOne|belongsToMany|looseColumn
	 * @param string $severity One of the SEVERITY_* constants.
	 * @param string $message Human-readable description.
	 * @param string|null $column FK column involved, if any.
	 * @param string|null $target Referenced/target table, if any.
	 * @param string|null $fixSnippet Copy-paste fix, if any.
	 * @param string $layer One of the LAYER_* constants.
	 */
	public function __construct(
		public readonly string $table,
		public readonly string $direction,
		public readonly string $associationType,
		public readonly string $severity,
		public readonly string $message,
		public readonly ?string $column = null,
		public readonly ?string $target = null,
		public readonly ?string $fixSnippet = null,
		public readonly string $layer = self::LAYER_CONSTRAINT,
	) {
	}

	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return [
			'table' => $this->table,
			'direction' => $this->direction,
			'associationType' => $this->associationType,
			'severity' => $this->severity,
			'message' => $this->message,
			'column' => $this->column,
			'target' => $this->target,
			'fixSnippet' => $this->fixSnippet,
			'layer' => $this->layer,
		];
	}

}
