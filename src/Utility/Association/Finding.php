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
	 * An association is declared, but its owner FK column does not exist in the DB at all
	 * (distinct from a column that exists but lacks the FK constraint).
     * @var string
	 */
	public const DIRECTION_COLUMN_MISSING = 'column_missing';

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
	 * Key column type observation: a type disagreement (error) or non-integer keys (info).
     * @var string
	 */
	public const DIRECTION_TYPE = 'type';

	/**
	 * Cascade-rule observation: the ORM `dependent` intent and the DB FK rule disagree.
     * @var string
	 */
	public const DIRECTION_RULE = 'rule';

	/**
	 * Index-presence observation: a foreign-key-style column is not the leading column of
	 * any index, so lookups/joins on it table-scan.
     * @var string
	 */
	public const DIRECTION_INDEX = 'index';

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
	 * Severity sort weight, highest = most severe. Orders findings worst-first.
	 *
	 * @var array<string, int>
	 */
	public const SEVERITY_RANK = [
		self::SEVERITY_ERROR => 3,
		self::SEVERITY_WARNING => 2,
		self::SEVERITY_INFO => 1,
	];

	/**
     * @var string
     */
	public const LAYER_CONSTRAINT = 'constraint';

	/**
     * @var string
     */
	public const LAYER_COLUMN = 'column';

	/**
     * @var string
     */
	public const LAYER_TYPE = 'type';

	/**
     * @var string
     */
	public const LAYER_RULE = 'rule';

	/**
     * @var string
     */
	public const LAYER_INDEX = 'index';

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
	 * Coarse subject of the finding, used to group/filter the flat scan: the layer
	 * (constraint / column / type) for regular findings, or "unsupported" for the
	 * not-auto-verifiable ones (which carry no meaningful layer).
	 *
	 * @return string
	 */
	public function topic(): string {
		return $this->direction === static::DIRECTION_UNSUPPORTED ? static::DIRECTION_UNSUPPORTED : $this->layer;
	}

	/**
	 * Display labels for every value topic() can return, used by the flat-scan topic filter.
	 * Single source of truth: every layer (plus the unsupported direction) must have a label
	 * here, otherwise that category becomes unfilterable in the UI. FindingTest enforces this.
	 *
	 * @return array<string, string>
	 */
	public static function topicLabels(): array {
		return [
			static::LAYER_CONSTRAINT => 'Constraints',
			static::LAYER_COLUMN => 'Columns',
			static::LAYER_TYPE => 'Key types',
			static::LAYER_RULE => 'Cascade rules',
			static::LAYER_INDEX => 'Indexes',
			static::DIRECTION_UNSUPPORTED => 'Not verifiable',
		];
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
