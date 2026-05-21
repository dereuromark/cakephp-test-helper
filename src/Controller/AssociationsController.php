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
	 * Association types shown as matrix columns.
	 *
	 * @var array<string>
	 */
	protected array $columns = ['belongsTo', 'hasMany', 'hasOne', 'belongsToMany', 'looseColumn'];

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

		// Scanned tables drill in via their registry alias (connection-correct). An
		// implicit junction row carries only a physical name and is re-audited on the
		// default connection; a same-named table on a non-default connection cannot be
		// disambiguated from the alias alone (known v1 limitation).
		$findings = $model ? (new AssociationAuditor())->audit([$model]) : [];
		$grouped = $this->groupByDirection($findings);

		$this->set(compact('model', 'findings', 'grouped'));
	}

	/**
	 * Flat findings list across all in-scope tables (CI-style).
	 *
	 * @return void
	 */
	public function scan(): void {
		$includeVendor = (bool)$this->request->getQuery('vendor');

		$tables = (new TableResolver())->tables($includeVendor);
		$findings = (new AssociationAuditor())->audit($tables);
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
			$column = in_array($finding->associationType, $this->columns, true) ? $finding->associationType : 'belongsTo';

			$cell = $matrix[$finding->table][$column];
			$cell['count']++;
			$cell['severity'] = $this->worst($cell['severity'], $finding->severity);
			$matrix[$finding->table][$column] = $cell;
		}

		return $matrix;
	}

	/**
	 * @param array<\TestHelper\Utility\Association\Finding> $findings
	 * @return array<string, array<\TestHelper\Utility\Association\Finding>>
	 */
	protected function groupByDirection(array $findings): array {
		$grouped = [
			Finding::DIRECTION_CODE_MISSING => [],
			Finding::DIRECTION_DB_MISSING => [],
			Finding::DIRECTION_MISMATCH => [],
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
		$rank = ['info' => 1, 'warning' => 2, 'error' => 3];
		if ($current === null) {
			return $candidate;
		}

		return ($rank[$candidate] ?? 0) > ($rank[$current] ?? 0) ? $candidate : $current;
	}

}
