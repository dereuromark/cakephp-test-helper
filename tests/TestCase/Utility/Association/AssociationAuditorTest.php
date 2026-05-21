<?php

namespace TestHelper\Test\TestCase\Utility\Association;

use Cake\Core\Configure;
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
	 * A wider owner key than the referenced key (biginteger FK -> integer PK) holds every
	 * referenced value, so it is clean.
	 *
	 * @return void
	 */
	public function testTypeWiderOwnerIntegerIsClean() {
		$findings = $this->auditor->typeFindings([$this->typedKey('biginteger', 'integer')]);

		$this->assertSame([], $findings);
	}

	/**
	 * A narrower owner key than the referenced key (integer FK -> biginteger PK) cannot
	 * hold every referenced value, so it is a warning.
	 *
	 * @return void
	 */
	public function testTypeNarrowerOwnerIntegerIsWarning() {
		$findings = $this->auditor->typeFindings([$this->typedKey('integer', 'biginteger')]);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::DIRECTION_TYPE, $findings[0]->direction);
		$this->assertSame(Finding::SEVERITY_WARNING, $findings[0]->severity);
		$this->assertStringContainsString('narrower', strtolower($findings[0]->message));
	}

	/**
	 * The info-level "integer keys are preferred" finding can be silenced via config, for
	 * apps that deliberately standardize on non-integer (e.g. uuid) keys.
	 *
	 * @return void
	 */
	public function testTypeNonIntegerInfoSuppressedByConfig() {
		Configure::write('TestHelper.associationAudit.preferIntegerKeys', false);
		$findings = $this->auditor->typeFindings([$this->typedKey('uuid', 'uuid')]);
		Configure::delete('TestHelper.associationAudit.preferIntegerKeys');

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
	 * A type mismatch carries a changeColumn fix aligning the FK column to its target.
	 *
	 * @return void
	 */
	public function testTypeMismatchCarriesFixSnippet() {
		$findings = $this->auditor->typeFindings([$this->typedKey('integer', 'uuid')]);

		$this->assertCount(1, $findings);
		$this->assertStringContainsString("\$table->changeColumn('author_id', 'uuid', [", (string)$findings[0]->fixSnippet);
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
	 * The non-integer info advisory is not an actionable migration, so it carries no fix.
	 *
	 * @return void
	 */
	public function testTypeInfoHasNoFixSnippet() {
		$findings = $this->auditor->typeFindings([$this->typedKey('uuid', 'uuid')]);

		$this->assertCount(1, $findings);
		$this->assertNull($findings[0]->fixSnippet);
	}

	/**
	 * With preferIntegerKeys off, the non-integer info advisory is suppressed.
	 *
	 * @return void
	 */
	public function testTypePreferIntegerKeysSuppressesInfo() {
		$findings = $this->auditor->typeFindings([$this->typedKey('uuid', 'uuid')], false);

		$this->assertSame([], $findings);
	}

	/**
	 * Even with preferIntegerKeys off, a real cross-type mismatch is still an error.
	 *
	 * @return void
	 */
	public function testTypeMismatchStillErrorsWhenPreferIntegerKeysOff() {
		$findings = $this->auditor->typeFindings([$this->typedKey('integer', 'uuid')], false);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::SEVERITY_ERROR, $findings[0]->severity);
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
	 * A dependent association whose DB FK does not cascade is an info rule finding with a fix.
	 *
	 * @return void
	 */
	public function testRuleDependentButDbDoesNotCascade() {
		$findings = $this->auditor->ruleFindings(
			[$this->dependentKey(true, 'hasMany')],
			[$this->dbRuleKey('noAction')],
		);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::DIRECTION_RULE, $findings[0]->direction);
		$this->assertSame(Finding::SEVERITY_INFO, $findings[0]->severity);
		$this->assertStringContainsString('cascade', strtolower($findings[0]->message));
		$this->assertStringContainsString('addForeignKey', (string)$findings[0]->fixSnippet);
	}

	/**
	 * A dependent association backed by ON DELETE CASCADE is consistent: no finding.
	 *
	 * @return void
	 */
	public function testRuleDependentAndDbCascadesClean() {
		$findings = $this->auditor->ruleFindings(
			[$this->dependentKey(true, 'hasMany')],
			[$this->dbRuleKey('cascade')],
		);

		$this->assertSame([], $findings);
	}

	/**
	 * A DB cascade with a non-dependent association is flagged: the ORM won't fire callbacks.
	 *
	 * @return void
	 */
	public function testRuleDbCascadesButNotDependent() {
		$findings = $this->auditor->ruleFindings(
			[$this->dependentKey(false, 'hasMany')],
			[$this->dbRuleKey('cascade')],
		);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::DIRECTION_RULE, $findings[0]->direction);
		$this->assertStringContainsString('dependent', strtolower($findings[0]->message));
	}

	/**
	 * With no matching DB FK there is nothing to compare against (constraint layer covers it).
	 *
	 * @return void
	 */
	public function testRuleNoDbFkSkipped() {
		$findings = $this->auditor->ruleFindings([$this->dependentKey(true, 'hasMany')], []);

		$this->assertSame([], $findings);
	}

	/**
	 * belongsTo carries no dependent intent, so it is never rule-checked.
	 *
	 * @return void
	 */
	public function testRuleBelongsToSkipped() {
		$findings = $this->auditor->ruleFindings(
			[$this->dependentKey(null, 'belongsTo')],
			[$this->dbRuleKey('cascade')],
		);

		$this->assertSame([], $findings);
	}

	/**
	 * Two hasMany aliases on the same FK with different dependent settings are checked
	 * independently; the dependent intent of a later alias must not be dropped by dedupe.
	 *
	 * @return void
	 */
	public function testRuleChecksEachAliasIndependently() {
		$findings = $this->auditor->ruleFindings(
			[
				$this->dependentKey(true, 'hasMany', 'Comments'),
				$this->dependentKey(false, 'hasMany', 'ApprovedComments'),
			],
			[$this->dbRuleKey('cascade')],
		);

		// dependent=true + cascade is consistent; dependent=false + cascade is flagged.
		$this->assertCount(1, $findings);
		$this->assertStringContainsString('ApprovedComments', $findings[0]->message);
	}

	/**
	 * An unindexed foreign-key column is flagged info with an addIndex fix.
	 *
	 * @return void
	 */
	public function testIndexUnindexedFkFlagged() {
		$fk = new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_DB);

		$findings = $this->auditor->indexFindings([$fk], [], [], true);

		$this->assertCount(1, $findings);
		$this->assertSame(Finding::DIRECTION_INDEX, $findings[0]->direction);
		$this->assertSame(Finding::LAYER_INDEX, $findings[0]->layer);
		$this->assertSame(Finding::SEVERITY_INFO, $findings[0]->severity);
		$this->assertSame('post_id', $findings[0]->column);
		$this->assertStringContainsString('table-scan', $findings[0]->message);
		$this->assertSame("\$table->addIndex(['post_id']);", $findings[0]->fixSnippet);
	}

	/**
	 * A loose column uses the "looks like a foreign key" wording.
	 *
	 * @return void
	 */
	public function testIndexLooseColumnWording() {
		$loose = new LooseColumn('default', 'comments', 'editor_id');

		$findings = $this->auditor->indexFindings([$loose], [], [], true);

		$this->assertCount(1, $findings);
		$this->assertSame('looseColumn', $findings[0]->associationType);
		$this->assertStringContainsString('looks like a foreign key', $findings[0]->message);
		$this->assertSame("\$table->addIndex(['editor_id']);", $findings[0]->fixSnippet);
	}

	/**
	 * A column that is the leading column of an index is clean.
	 *
	 * @return void
	 */
	public function testIndexIndexedColumnClean() {
		$fk = new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_DB);
		$indexed = ['default|comments' => ['post_id']];

		$findings = $this->auditor->indexFindings([$fk], $indexed, [], true);

		$this->assertSame([], $findings);
	}

	/**
	 * A column buried as a non-leading member of a composite index is NOT counted as
	 * indexed, so it is still flagged.
	 *
	 * @return void
	 */
	public function testIndexNonLeadingCompositeMemberFlagged() {
		$fk = new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_DB);
		// Only the leading column of the composite index counts; post_id is buried second.
		$indexed = ['default|comments' => ['author_id']];

		$findings = $this->auditor->indexFindings([$fk], $indexed, [], true);

		$this->assertCount(1, $findings);
		$this->assertSame('post_id', $findings[0]->column);
	}

	/**
	 * For a composite foreign key the leading column is checked and the fix indexes all
	 * columns in order.
	 *
	 * @return void
	 */
	public function testIndexCompositeFkChecksLeadingColumn() {
		$fk = new ForeignKey('default', 'memberships', ['tenant_id', 'company_id'], 'companies', ['tenant_id', 'id'], ForeignKey::SOURCE_DB);

		$findings = $this->auditor->indexFindings([$fk], [], [], true);

		$this->assertCount(1, $findings);
		$this->assertSame('tenant_id', $findings[0]->column);
		$this->assertSame("\$table->addIndex(['tenant_id', 'company_id']);", $findings[0]->fixSnippet);
	}

	/**
	 * A composite foreign key whose leading column is indexed is clean.
	 *
	 * @return void
	 */
	public function testIndexCompositeFkLeadingIndexedClean() {
		$fk = new ForeignKey('default', 'memberships', ['tenant_id', 'company_id'], 'companies', ['tenant_id', 'id'], ForeignKey::SOURCE_DB);
		$indexed = ['default|memberships' => ['tenant_id']];

		$findings = $this->auditor->indexFindings([$fk], $indexed, [], true);

		$this->assertSame([], $findings);
	}

	/**
	 * Ignored columns (e.g. polymorphic) are not flagged by the index layer.
	 *
	 * @return void
	 */
	public function testIndexIgnoredColumnSkipped() {
		$loose = new LooseColumn('default', 'comments', 'commentable_id');

		$findings = $this->auditor->indexFindings([$loose], [], ['commentable_id'], true);

		$this->assertSame([], $findings);
	}

	/**
	 * With checkIndexes false the layer emits nothing.
	 *
	 * @return void
	 */
	public function testIndexCheckIndexesFalseSuppresses() {
		$fk = new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_DB);

		$findings = $this->auditor->indexFindings([$fk], [], [], false);

		$this->assertSame([], $findings);
	}

	/**
	 * The checkIndexes flag falls back to config when not passed explicitly.
	 *
	 * @return void
	 */
	public function testIndexCheckIndexesNullReadsConfig() {
		$fk = new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_DB);

		Configure::write('TestHelper.associationAudit.checkIndexes', false);
		$findings = $this->auditor->indexFindings([$fk], [], []);
		Configure::delete('TestHelper.associationAudit.checkIndexes');

		$this->assertSame([], $findings);
	}

	/**
	 * The same column reaching the layer via several sources (a DB FK, a code FK and a
	 * loose column) yields a single finding.
	 *
	 * @return void
	 */
	public function testIndexDedupesPerColumn() {
		$candidates = [
			new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_DB),
			new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Comments', 'Posts', true),
			new LooseColumn('default', 'comments', 'post_id'),
		];

		$findings = $this->auditor->indexFindings($candidates, [], [], true);

		$this->assertCount(1, $findings);
		$this->assertSame('post_id', $findings[0]->column);
	}

	/**
	 * DB-sourced index findings display under the registry alias, keyed by connection.
	 *
	 * @return void
	 */
	public function testIndexMapsToAliasByConnection() {
		$fk = new ForeignKey('default', 'animals', 'owner_id', 'owners', 'id', ForeignKey::SOURCE_DB);
		$map = ['default|animals' => 'Sandbox.Animals'];

		$findings = $this->auditor->indexFindings([$fk], [], [], true, $map);

		$this->assertCount(1, $findings);
		$this->assertSame('Sandbox.Animals', $findings[0]->table);
	}

	/**
	 * A code-side FK on a table whose schema was never inspected (no entry in the indexed
	 * map) is dropped from the index candidates, so a non-introspectable table reports only
	 * its unsupported warning, not a false "missing index" for every code FK column on it.
	 *
	 * @return void
	 */
	public function testIndexCandidatesSkipNonIntrospectedTable() {
		$codeKeys = [
			new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Comments', 'Posts', true),
		];
		// posts was inspected; comments was not (e.g. introspection threw).
		$indexedColumns = ['default|posts' => ['id']];

		$candidates = $this->auditor->indexCandidates([], $codeKeys, [], $indexedColumns);
		$findings = $this->auditor->indexFindings($candidates, $indexedColumns, [], true);

		$this->assertSame([], $candidates);
		$this->assertSame([], $findings);
	}

	/**
	 * The framework-injected target-side hasMany to a belongsToMany junction shares the
	 * junction's physical FK with the belongsToMany expansion. It is generated by CakePHP
	 * (dependent=false), not declared by the user, and the belongsToMany owns junction-row
	 * cleanup — so it must not produce a cascade-rule finding (it would be misleading noise,
	 * and it is the source of the scan-vs-detail-view discrepancy).
	 *
	 * @return void
	 */
	public function testRuleSkipsBelongsToManyJunctionHasMany() {
		$junctionFk = fn (string $type, ?bool $dependent = null): ForeignKey => new ForeignKey(
			connection: 'default',
			ownerTable: 'logistic_partners_transport_vehicles',
			column: 'transport_vehicle_id',
			referencedTable: 'transport_vehicles',
			referencedColumn: 'id',
			source: ForeignKey::SOURCE_CODE,
			associationType: $type,
			declaringTable: $type === 'belongsToMany' ? 'LogisticPartners' : 'TransportVehicles',
			alias: $type === 'belongsToMany' ? 'TransportVehicles' : 'LogisticPartnersTransportVehicles',
			dependent: $dependent,
		);
		$codeKeys = [
			$junctionFk('belongsToMany'),
			$junctionFk('hasMany', false),
		];
		$dbKeys = [
			new ForeignKey('default', 'logistic_partners_transport_vehicles', 'transport_vehicle_id', 'transport_vehicles', 'id', ForeignKey::SOURCE_DB, onDelete: 'cascade'),
		];

		$findings = $this->auditor->ruleFindings($codeKeys, $dbKeys);

		$this->assertSame([], $findings);
	}

	/**
	 * Candidates on inspected tables are kept and a missing column is dropped (the constraint
	 * layer owns that), so the index layer only checks columns that physically exist.
	 *
	 * @return void
	 */
	public function testIndexCandidatesKeepInspectedAndDropMissingColumn() {
		$codeKeys = [
			new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Comments', 'Posts', true),
			new ForeignKey('default', 'comments', 'ghost_id', 'ghosts', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Comments', 'Ghosts', false),
		];
		$indexedColumns = ['default|comments' => []];

		$candidates = $this->auditor->indexCandidates([], $codeKeys, [], $indexedColumns);

		$this->assertCount(1, $candidates);
		$this->assertInstanceOf(ForeignKey::class, $candidates[0]);
		$this->assertSame('post_id', $candidates[0]->column);
	}

	/**
	 * A hasMany over a belongsToMany junction FK is skipped regardless of its alias (a custom
	 * `through` alias names the injected hasMany after the junction, so a key-based skip — not an
	 * alias heuristic — is what reliably catches it). The belongsToMany owns junction-row
	 * lifecycle, so this is intentional even for an explicitly declared junction hasMany.
	 *
	 * @return void
	 */
	public function testRuleSkipsHasManyOverBelongsToManyJunctionRegardlessOfAlias() {
		$btm = new ForeignKey(
			connection: 'default',
			ownerTable: 'logistic_partners_transport_vehicles',
			column: 'transport_vehicle_id',
			referencedTable: 'transport_vehicles',
			referencedColumn: 'id',
			source: ForeignKey::SOURCE_CODE,
			associationType: 'belongsToMany',
			declaringTable: 'LogisticPartners',
			alias: 'TransportVehicles',
		);
		$junctionHasMany = new ForeignKey(
			connection: 'default',
			ownerTable: 'logistic_partners_transport_vehicles',
			column: 'transport_vehicle_id',
			referencedTable: 'transport_vehicles',
			referencedColumn: 'id',
			source: ForeignKey::SOURCE_CODE,
			associationType: 'hasMany',
			declaringTable: 'TransportVehicles',
			alias: 'PartnerLinks',
			dependent: true,
		);
		$dbKeys = [
			new ForeignKey('default', 'logistic_partners_transport_vehicles', 'transport_vehicle_id', 'transport_vehicles', 'id', ForeignKey::SOURCE_DB, onDelete: 'noAction'),
		];

		$findings = $this->auditor->ruleFindings([$btm, $junctionHasMany], $dbKeys);

		$this->assertSame([], $findings);
	}

	/**
	 * The junction skip is hasMany-only: CakePHP injects a hasMany (not a hasOne) for a
	 * belongsToMany, so a hasOne on the same FK is genuine intent and is still rule-checked.
	 *
	 * @return void
	 */
	public function testRuleStillChecksHasOneOverBelongsToManyJunction() {
		$btm = new ForeignKey(
			connection: 'default',
			ownerTable: 'logistic_partners_transport_vehicles',
			column: 'transport_vehicle_id',
			referencedTable: 'transport_vehicles',
			referencedColumn: 'id',
			source: ForeignKey::SOURCE_CODE,
			associationType: 'belongsToMany',
			declaringTable: 'LogisticPartners',
			alias: 'TransportVehicles',
		);
		$hasOne = new ForeignKey(
			connection: 'default',
			ownerTable: 'logistic_partners_transport_vehicles',
			column: 'transport_vehicle_id',
			referencedTable: 'transport_vehicles',
			referencedColumn: 'id',
			source: ForeignKey::SOURCE_CODE,
			associationType: 'hasOne',
			declaringTable: 'TransportVehicles',
			alias: 'LatestLink',
			dependent: true,
		);
		$dbKeys = [
			new ForeignKey('default', 'logistic_partners_transport_vehicles', 'transport_vehicle_id', 'transport_vehicles', 'id', ForeignKey::SOURCE_DB, onDelete: 'noAction'),
		];

		$findings = $this->auditor->ruleFindings([$btm, $hasOne], $dbKeys);

		$this->assertCount(1, $findings);
		$this->assertStringContainsString('LatestLink', $findings[0]->message);
	}

	/**
	 * Build a code-sourced ForeignKey carrying a dependent intent.
	 *
	 * @param bool|null $dependent
	 * @param string $associationType
	 * @param string $alias
	 * @return \TestHelper\Utility\Association\ForeignKey
	 */
	protected function dependentKey(?bool $dependent, string $associationType, string $alias = 'Comments'): ForeignKey {
		return new ForeignKey(
			connection: 'default',
			ownerTable: 'comments',
			column: 'post_id',
			referencedTable: 'posts',
			referencedColumn: 'id',
			source: ForeignKey::SOURCE_CODE,
			associationType: $associationType,
			declaringTable: 'Posts',
			alias: $alias,
			dependent: $dependent,
		);
	}

	/**
	 * Build a DB-sourced ForeignKey carrying an ON DELETE rule, matching dependentKey().
	 *
	 * @param string $onDelete
	 * @return \TestHelper\Utility\Association\ForeignKey
	 */
	protected function dbRuleKey(string $onDelete): ForeignKey {
		return new ForeignKey(
			connection: 'default',
			ownerTable: 'comments',
			column: 'post_id',
			referencedTable: 'posts',
			referencedColumn: 'id',
			source: ForeignKey::SOURCE_DB,
			onDelete: $onDelete,
		);
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
