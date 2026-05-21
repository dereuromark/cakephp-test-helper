<?php

namespace TestHelper\Test\TestCase\Utility\Association;

use Cake\TestSuite\TestCase;
use TestHelper\Utility\Association\AssociationAuditor;
use TestHelper\Utility\Association\Finding;
use TestHelper\Utility\Association\ForeignKey;
use TestHelper\Utility\Association\LooseColumn;

class AssociationAuditorTest extends TestCase {

	protected AssociationAuditor $auditor;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->auditor = new AssociationAuditor();
	}

	/**
	 * A code FK with no DB match (column exists) is a warning db_missing with a migration fix.
	 *
	 * @return void
	 */
	public function testDiffDbMissing() {
		$code = [
			new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Posts', 'Authors', true),
		];

		$findings = $this->auditor->diffForeignKeys($code, []);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::DIRECTION_DB_MISSING, $findings[0]->direction);
		$this->assertSame(Finding::SEVERITY_WARNING, $findings[0]->severity);
		$this->assertSame('author_id', $findings[0]->column);
		$this->assertStringContainsString('addForeignKey', (string)$findings[0]->fixSnippet);
	}

	/**
	 * A code FK whose owner column does not exist is a column-missing error (not a
	 * missing-constraint warning), and the fix adds the column: you cannot put a foreign
	 * key on a column that does not exist.
	 *
	 * @return void
	 */
	public function testDiffColumnMissing() {
		$code = [
			new ForeignKey('default', 'posts', 'ghost_id', 'ghosts', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Posts', 'Ghosts', false),
		];

		$findings = $this->auditor->diffForeignKeys($code, []);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::DIRECTION_COLUMN_MISSING, $findings[0]->direction);
		$this->assertSame(Finding::SEVERITY_ERROR, $findings[0]->severity);
		$this->assertStringContainsString('does not exist', $findings[0]->message);
		$this->assertStringContainsString('addColumn', (string)$findings[0]->fixSnippet);
	}

	/**
	 * The column-missing fix uses the referenced column's type when known, so the added
	 * column matches the key it points at instead of defaulting to integer.
	 *
	 * @return void
	 */
	public function testDiffColumnMissingFixUsesReferencedType() {
		$code = [
			new ForeignKey(
				connection: 'default',
				ownerTable: 'posts',
				column: 'ghost_id',
				referencedTable: 'ghosts',
				referencedColumn: 'id',
				source: ForeignKey::SOURCE_CODE,
				associationType: 'belongsTo',
				declaringTable: 'Posts',
				alias: 'Ghosts',
				columnExists: false,
				referencedColumnType: 'uuid',
			),
		];

		$findings = $this->auditor->diffForeignKeys($code, []);

		$this->assertCount(1, $findings);
		$this->assertStringContainsString("addColumn('ghost_id', 'uuid'", (string)$findings[0]->fixSnippet);
	}

	/**
	 * A DB FK with no matching association is code_missing with an association-call fix.
	 *
	 * @return void
	 */
	public function testDiffCodeMissing() {
		$db = [
			new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_DB),
		];

		$findings = $this->auditor->diffForeignKeys([], $db);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::DIRECTION_CODE_MISSING, $findings[0]->direction);
		$this->assertStringContainsString('belongsTo', (string)$findings[0]->fixSnippet);
		$this->assertStringContainsString('hasMany', (string)$findings[0]->fixSnippet);
	}

	/**
	 * Matching code and DB keys produce no findings (and the reciprocal dedupes).
	 *
	 * @return void
	 */
	public function testDiffNoFindingsWhenAligned() {
		$code = [
			new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Posts', 'Authors', true),
			// Reciprocal hasMany: normalizes to the SAME owner key/key as the belongsTo.
			new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_CODE, 'hasMany', 'Authors', 'Posts', true),
		];
		$db = [
			new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_DB),
		];

		$findings = $this->auditor->diffForeignKeys($code, $db);

		$this->assertSame([], $findings);
	}

	/**
	 * Reciprocal declarations of one FK (belongsTo + hasMany) report a single finding,
	 * not one per declaration.
	 *
	 * @return void
	 */
	public function testDiffDedupesReciprocalDeclarations() {
		$code = [
			new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Posts', 'Authors', true),
			new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_CODE, 'hasMany', 'Authors', 'Posts', true),
		];

		$findings = $this->auditor->diffForeignKeys($code, []);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::DIRECTION_DB_MISSING, $findings[0]->direction);
	}

	/**
	 * Same owner column but different target is a mismatch.
	 *
	 * @return void
	 */
	public function testDiffMismatch() {
		$code = [
			new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Posts', 'Authors', true),
		];
		$db = [
			new ForeignKey('default', 'posts', 'author_id', 'users', 'id', ForeignKey::SOURCE_DB),
		];

		$findings = $this->auditor->diffForeignKeys($code, $db);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::DIRECTION_MISMATCH, $findings[0]->direction);
		$this->assertSame(Finding::SEVERITY_ERROR, $findings[0]->severity);
		$this->assertStringContainsString('users', $findings[0]->message);
	}

	/**
	 * Cross-connection: same table name on another connection is a different target.
	 *
	 * @return void
	 */
	public function testDiffConnectionAware() {
		$code = [
			new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Posts', 'Authors', true),
		];
		$db = [
			new ForeignKey('other', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_DB),
		];

		$findings = $this->auditor->diffForeignKeys($code, $db);

		// No match across connections: one db_missing (code) + one code_missing (db).
		$this->assertCount(2, $findings);
	}

	/**
	 * DB-sourced findings are displayed under the registry alias, keyed by connection.
	 *
	 * @return void
	 */
	public function testDiffMapsDbFindingToAliasByConnection() {
		$db = [
			new ForeignKey('default', 'animals', 'owner_id', 'owners', 'id', ForeignKey::SOURCE_DB),
		];
		$map = ['default|animals' => 'Sandbox.Animals'];

		$findings = $this->auditor->diffForeignKeys([], $db, $map);

		$this->assertCount(1, $findings);
		$this->assertSame('Sandbox.Animals', $findings[0]->table);
	}

	/**
	 * The alias map keeps the connection dimension: a same-named table on another
	 * connection is not collapsed onto the wrong alias.
	 *
	 * @return void
	 */
	public function testDiffAliasMapIsConnectionAware() {
		$db = [
			new ForeignKey('other', 'animals', 'owner_id', 'owners', 'id', ForeignKey::SOURCE_DB),
		];
		// Map only knows the `default` connection's animals table.
		$map = ['default|animals' => 'Sandbox.Animals'];

		$findings = $this->auditor->diffForeignKeys([], $db, $map);

		$this->assertCount(1, $findings);
		// No alias for the `other` connection -> falls back to the physical name, not the wrong alias.
		$this->assertSame('animals', $findings[0]->table);
	}

	/**
	 * A loose *_id column with no constraint and no association is an info finding.
	 *
	 * @return void
	 */
	public function testLooseColumnReported() {
		$loose = [
			new LooseColumn('default', 'posts', 'editor_id'),
		];

		$findings = $this->auditor->looseColumnFindings($loose, [], []);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::DIRECTION_CODE_MISSING, $findings[0]->direction);
		$this->assertSame('looseColumn', $findings[0]->associationType);
		$this->assertSame(Finding::LAYER_COLUMN, $findings[0]->layer);
	}

	/**
	 * A loose column claimed by a code association is not reported.
	 *
	 * @return void
	 */
	public function testLooseColumnClaimedByCode() {
		$loose = [
			new LooseColumn('default', 'posts', 'author_id'),
		];
		$code = [
			new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Posts', 'Authors', true),
		];

		$findings = $this->auditor->looseColumnFindings($loose, $code, []);

		$this->assertSame([], $findings);
	}

	/**
	 * A loose column claimed by an unsupported association is not also flagged as loose.
	 *
	 * @return void
	 */
	public function testLooseColumnClaimedByUnsupportedAssociation() {
		$loose = [
			new LooseColumn('default', 'posts', 'author_id'),
		];

		$findings = $this->auditor->looseColumnFindings($loose, [], [], [], ['default|posts|author_id']);

		$this->assertSame([], $findings);
	}

	/**
	 * Ignored columns (e.g. polymorphic) are skipped.
	 *
	 * @return void
	 */
	public function testLooseColumnIgnored() {
		$loose = [
			new LooseColumn('default', 'comments', 'commentable_id'),
		];

		$findings = $this->auditor->looseColumnFindings($loose, [], ['commentable_id']);

		$this->assertSame([], $findings);
	}

	/**
	 * Both sides integer: the ideal case, no finding.
	 *
	 * @return void
	 */
	public function testTypeBothIntegerClean() {
		$findings = $this->auditor->typeFindings([$this->typedKey('integer', 'integer')]);

		$this->assertSame([], $findings);
	}

	/**
	 * Integer family width differences (integer vs biginteger) are treated as agreement.
	 *
	 * @return void
	 */
	public function testTypeIntegerFamilyWidthIsClean() {
		$findings = $this->auditor->typeFindings([$this->typedKey('integer', 'biginteger')]);

		$this->assertSame([], $findings);
	}

	/**
	 * Different types are an error.
	 *
	 * @return void
	 */
	public function testTypeMismatchIsError() {
		$findings = $this->auditor->typeFindings([$this->typedKey('integer', 'uuid')]);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::DIRECTION_TYPE, $findings[0]->direction);
		$this->assertSame(Finding::SEVERITY_ERROR, $findings[0]->severity);
		$this->assertStringContainsString('type mismatch', strtolower($findings[0]->message));
	}

	/**
	 * Matching non-integer types (both uuid) are info, since integer is preferred.
	 *
	 * @return void
	 */
	public function testTypeBothUuidIsInfo() {
		$findings = $this->auditor->typeFindings([$this->typedKey('uuid', 'uuid')]);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::DIRECTION_TYPE, $findings[0]->direction);
		$this->assertSame(Finding::SEVERITY_INFO, $findings[0]->severity);
		$this->assertStringContainsString('non-integer', $findings[0]->message);
	}

	/**
	 * Unknown types (schema not introspectable) are skipped.
	 *
	 * @return void
	 */
	public function testTypeNullTypesSkipped() {
		$findings = $this->auditor->typeFindings([$this->typedKey(null, null)]);

		$this->assertSame([], $findings);
	}

	/**
	 * Reciprocal declarations of the same FK yield a single type finding.
	 *
	 * @return void
	 */
	public function testTypeReciprocalDeduped() {
		$keys = [
			$this->typedKey('uuid', 'uuid', 'belongsTo'),
			$this->typedKey('uuid', 'uuid', 'hasMany'),
		];

		$findings = $this->auditor->typeFindings($keys);

		$this->assertCount(1, $findings);
	}

	/**
	 * Build a code-sourced ForeignKey carrying owner/referenced column types.
	 *
	 * @param string|null $ownerType
	 * @param string|null $referencedType
	 * @param string $associationType
	 * @return \TestHelper\Utility\Association\ForeignKey
	 */
	protected function typedKey(?string $ownerType, ?string $referencedType, string $associationType = 'belongsTo'): ForeignKey {
		return new ForeignKey(
			connection: 'default',
			ownerTable: 'posts',
			column: 'author_id',
			referencedTable: 'authors',
			referencedColumn: 'id',
			source: ForeignKey::SOURCE_CODE,
			associationType: $associationType,
			declaringTable: 'Posts',
			alias: 'Authors',
			columnExists: true,
			ownerColumnType: $ownerType,
			referencedColumnType: $referencedType,
		);
	}

	/**
	 * Findings sort worst-first: error, then warning, then info.
	 *
	 * @return void
	 */
	public function testSortFindingsBySeverity() {
		$findings = [
			new Finding('Posts', Finding::DIRECTION_CODE_MISSING, 'looseColumn', Finding::SEVERITY_INFO, 'info'),
			new Finding('Posts', Finding::DIRECTION_MISMATCH, 'belongsTo', Finding::SEVERITY_ERROR, 'error'),
			new Finding('Posts', Finding::DIRECTION_DB_MISSING, 'belongsTo', Finding::SEVERITY_WARNING, 'warning'),
		];

		$sorted = $this->auditor->sortFindings($findings);

		$this->assertSame(
			[Finding::SEVERITY_ERROR, Finding::SEVERITY_WARNING, Finding::SEVERITY_INFO],
			array_map(fn (Finding $finding): string => $finding->severity, $sorted),
		);
	}

	/**
	 * Within a severity band, findings group by table (alphabetical).
	 *
	 * @return void
	 */
	public function testSortFindingsSecondaryByTable() {
		$findings = [
			new Finding('Zebras', Finding::DIRECTION_MISMATCH, 'belongsTo', Finding::SEVERITY_ERROR, 'msg'),
			new Finding('Apples', Finding::DIRECTION_MISMATCH, 'belongsTo', Finding::SEVERITY_ERROR, 'msg'),
			new Finding('Mangos', Finding::DIRECTION_MISMATCH, 'belongsTo', Finding::SEVERITY_ERROR, 'msg'),
		];

		$sorted = $this->auditor->sortFindings($findings);

		$this->assertSame(
			['Apples', 'Mangos', 'Zebras'],
			array_map(fn (Finding $finding): string => $finding->table, $sorted),
		);
	}

	/**
	 * Same severity and table fall back to column order, so output is deterministic
	 * regardless of audit-phase emission order.
	 *
	 * @return void
	 */
	public function testSortFindingsTertiaryByColumn() {
		$findings = [
			new Finding('Posts', Finding::DIRECTION_DB_MISSING, 'belongsTo', Finding::SEVERITY_WARNING, 'msg', 'z_id'),
			new Finding('Posts', Finding::DIRECTION_DB_MISSING, 'belongsTo', Finding::SEVERITY_WARNING, 'msg', 'a_id'),
		];

		$sorted = $this->auditor->sortFindings($findings);

		$this->assertSame(
			['a_id', 'z_id'],
			array_map(fn (Finding $finding): ?string => $finding->column, $sorted),
		);
	}

	/**
	 * Findings sharing severity, table and (null) column fall back to message order, so
	 * e.g. several unsupported associations on one table stay content-deterministic.
	 *
	 * @return void
	 */
	public function testSortFindingsFinalTiebreakByMessage() {
		$findings = [
			new Finding('Posts', Finding::DIRECTION_UNSUPPORTED, 'belongsToMany', Finding::SEVERITY_INFO, 'Beta unsupported'),
			new Finding('Posts', Finding::DIRECTION_UNSUPPORTED, 'belongsToMany', Finding::SEVERITY_INFO, 'Alpha unsupported'),
		];

		$sorted = $this->auditor->sortFindings($findings);

		$this->assertSame(
			['Alpha unsupported', 'Beta unsupported'],
			array_map(fn (Finding $finding): string => $finding->message, $sorted),
		);
	}

}
