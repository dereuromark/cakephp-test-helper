<?php

namespace TestHelper\Controller;

use TestHelper\Utility\Association\AssociationAuditor;
use TestHelper\Utility\Association\Finding;
use TestHelper\Utility\Association\TableResolver;

/**
 * Audits agreement between declared table associations and the actual DB foreign keys.
 */
class AssociationsController extends TestHelperAppController {

	protected ?string $defaultTable = '';

	/**
	 * Matrix columns: the association types, then the cross-cutting audit layers (key-type,
	 * cascade-rule and index-presence) as their own dimensions.
	 *
	 * @var array<string>
	 */
	protected array $columns = ['belongsTo', 'hasMany', 'hasOne', 'belongsToMany', 'looseColumn', 'keyType', 'cascadeRule', 'index'];

	/**
	 * Summary matrix across all in-scope tables.
	 *
	 * @return void
	 */
	public function index(): void {
		$includeVendor = (bool)$this->request->getQuery('vendor');

		$tables = (new TableResolver())->tables($includeVendor);
		$findings = (new AssociationAuditor())->audit($tables);

		// Append any table a finding references that isn't a scanned alias (e.g. an
		// implicit belongsToMany junction table with no *Table class), so the matrix
		// never hides real problems behind an all-green grid.
		$tables = $this->rowsFromFindings($tables, $findings);

		$matrix = $this->buildMatrix($tables, $findings);
		$totals = $this->totals($findings);

		$this->set(compact('tables', 'findings', 'matrix', 'totals', 'includeVendor'));
		$this->set('columns', $this->columns);
	}

	/**
	 * Scanned tables plus any extra table referenced by a finding (preserving order).
	 *
	 * @param array<string> $tables
	 * @param array<\TestHelper\Utility\Association\Finding> $findings
	 * @return array<string>
	 */
	protected function rowsFromFindings(array $tables, array $findings): array {
		$known = array_fill_keys($tables, true);
		$extra = [];
		foreach ($findings as $finding) {
			if (!isset($known[$finding->table]) && !isset($extra[$finding->table])) {
				$extra[$finding->table] = true;
			}
		}
		$extraRows = array_keys($extra);
		sort($extraRows);

		return array_merge($tables, $extraRows);
	}

	/**
	 * Per-table detail, grouped by direction.
	 *
	 * @param string|null $model Table alias (plugin-dotted), e.g. "Sandbox.Animals".
	 * @return void
	 */
	public function view(?string $model = null): void {
		$model = $model ?? (string)$this->request->getQuery('model');

		// Audit the full in-scope set (not just $model) and filter to this table. CakePHP
		// injects associations onto a table when *another* table is loaded — e.g. a
		// belongsToMany elsewhere adds a junction hasMany here via the shared registry — so
		// auditing $model alone misses them and the detail view disagrees with the flat scan.
		// A same-named table on a non-default connection still cannot be disambiguated from
		// the alias alone (known v1 limitation).
		$includeVendor = (bool)$this->request->getQuery('vendor');
		$auditor = new AssociationAuditor();
		$findings = [];
		if ($model) {
			$tables = (new TableResolver())->tables($includeVendor);
			if (!in_array($model, $tables, true)) {
				$tables[] = $model;
			}
			$all = $auditor->sortFindings($auditor->audit($tables));
			$findings = array_values(array_filter($all, fn (Finding $finding): bool => $finding->table === $model));
		}

		$grouped = $this->groupByDirection($findings);

		$this->set(compact('model', 'findings', 'grouped', 'includeVendor'));
	}

	/**
	 * Flat findings list across all in-scope tables (CI-style).
	 *
	 * @return void
	 */
	public function scan(): void {
		$includeVendor = (bool)$this->request->getQuery('vendor');

		$tables = (new TableResolver())->tables($includeVendor);
		$auditor = new AssociationAuditor();
		$findings = $auditor->sortFindings($auditor->audit($tables));
		$totals = $this->totals($findings);

		$this->set(compact('findings', 'totals', 'includeVendor'));
	}

	/**
	 * @param array<string> $tables
	 * @param array<\TestHelper\Utility\Association\Finding> $findings
	 * @return array<string, array<string, array{severity: string|null, count: int}>>
	 */
	protected function buildMatrix(array $tables, array $findings): array {
		$matrix = [];
		foreach ($tables as $alias) {
			foreach ($this->columns as $column) {
				$matrix[$alias][$column] = ['severity' => null, 'count' => 0];
			}
		}

		foreach ($findings as $finding) {
			// Findings carry the registry alias (the auditor maps physical names to it).
			if (!isset($matrix[$finding->table])) {
				continue;
			}
			$column = $this->columnFor($finding);

			$cell = $matrix[$finding->table][$column];
			$cell['count']++;
			$cell['severity'] = $this->worst($cell['severity'], $finding->severity);
			$matrix[$finding->table][$column] = $cell;
		}

		return $matrix;
	}

	/**
	 * Matrix column a finding belongs in: the cross-cutting layers get their own column,
	 * everything else stays under its association type.
	 *
	 * @param \TestHelper\Utility\Association\Finding $finding
	 * @return string
	 */
	protected function columnFor(Finding $finding): string {
		return match ($finding->layer) {
			Finding::LAYER_TYPE => 'keyType',
			Finding::LAYER_RULE => 'cascadeRule',
			Finding::LAYER_INDEX => 'index',
			default => in_array($finding->associationType, $this->columns, true) ? $finding->associationType : 'belongsTo',
		};
	}

	/**
	 * @param array<\TestHelper\Utility\Association\Finding> $findings
	 * @return array<string, array<\TestHelper\Utility\Association\Finding>>
	 */
	protected function groupByDirection(array $findings): array {
		$grouped = [
			Finding::DIRECTION_MISMATCH => [],
			Finding::DIRECTION_COLUMN_MISSING => [],
			Finding::DIRECTION_TYPE => [],
			Finding::DIRECTION_RULE => [],
			Finding::DIRECTION_INDEX => [],
			Finding::DIRECTION_DB_MISSING => [],
			Finding::DIRECTION_CODE_MISSING => [],
			Finding::DIRECTION_UNSUPPORTED => [],
		];

		foreach ($findings as $finding) {
			$grouped[$finding->direction][] = $finding;
		}

		return $grouped;
	}

	/**
	 * @param array<\TestHelper\Utility\Association\Finding> $findings
	 * @return array<string, int>
	 */
	protected function totals(array $findings): array {
		$totals = ['error' => 0, 'warning' => 0, 'info' => 0];
		foreach ($findings as $finding) {
			$totals[$finding->severity] = ($totals[$finding->severity] ?? 0) + 1;
		}

		return $totals;
	}

	/**
	 * @param string|null $current
	 * @param string $candidate
	 * @return string
	 */
	protected function worst(?string $current, string $candidate): string {
		if ($current === null) {
			return $candidate;
		}

		return (Finding::SEVERITY_RANK[$candidate] ?? 0) > (Finding::SEVERITY_RANK[$current] ?? 0) ? $candidate : $current;
	}

}
