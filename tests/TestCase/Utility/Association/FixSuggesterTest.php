<?php

namespace TestHelper\Test\TestCase\Utility\Association;

use Cake\TestSuite\TestCase;
use TestHelper\Utility\Association\FixSuggester;
use TestHelper\Utility\Association\ForeignKey;

class FixSuggesterTest extends TestCase {

	protected FixSuggester $suggester;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->suggester = new FixSuggester();
	}

	/**
	 * @return void
	 */
	public function testAssociationCall() {
		$fk = new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_DB);

		$result = $this->suggester->associationCall($fk);

		$this->assertStringContainsString("\$this->belongsTo('Authors', ['foreignKey' => 'author_id']);", $result);
		$this->assertStringContainsString("\$this->hasMany('Posts', ['foreignKey' => 'author_id']);", $result);
	}

	/**
	 * @return void
	 */
	public function testMigrationLine() {
		$fk = new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Posts', 'Authors', true);

		$result = $this->suggester->migrationLine($fk);

		$this->assertStringContainsString("\$table->addForeignKey('author_id', 'authors', 'id'", $result);
	}

	/**
	 * A composite FK renders its columns as arrays in the migration line.
	 *
	 * @return void
	 */
	public function testMigrationLineComposite() {
		$fk = new ForeignKey('default', 'memberships', ['tenant_id', 'company_id'], 'companies', ['tenant_id', 'id'], ForeignKey::SOURCE_CODE, 'belongsTo', 'Memberships', 'Companies', true);

		$result = $this->suggester->migrationLine($fk);

		$this->assertStringContainsString("\$table->addForeignKey(['tenant_id', 'company_id'], 'companies', ['tenant_id', 'id']", $result);
	}

	/**
	 * A composite stray FK suggests a belongsTo with an array foreignKey.
	 *
	 * @return void
	 */
	public function testAssociationCallComposite() {
		$fk = new ForeignKey('default', 'memberships', ['tenant_id', 'company_id'], 'companies', ['tenant_id', 'id'], ForeignKey::SOURCE_DB);

		$result = $this->suggester->associationCall($fk);

		$this->assertStringContainsString("'foreignKey' => ['tenant_id', 'company_id']", $result);
		// Composite/non-default referenced columns must be pinned with bindingKey.
		$this->assertStringContainsString("'bindingKey' => ['tenant_id', 'id']", $result);
	}

	/**
	 * A FK referencing the plain `id` PK needs no explicit bindingKey.
	 *
	 * @return void
	 */
	public function testAssociationCallDefaultIdNeedsNoBindingKey() {
		$fk = new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_DB);

		$result = $this->suggester->associationCall($fk);

		$this->assertStringNotContainsString('bindingKey', $result);
	}

	/**
	 * The fix aligns the FK column to its referenced (PK) column's type.
	 *
	 * @return void
	 */
	public function testTypeAlignmentLine() {
		$fk = new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_CODE, 'belongsTo', 'Posts', 'Authors', true, 'integer', 'uuid');

		$result = $this->suggester->typeAlignmentLine($fk);

		$this->assertStringContainsString("\$table->changeColumn('author_id', 'uuid', [", $result);
		$this->assertStringContainsString('authors.id', $result);
		// changeColumn replaces the whole definition; the snippet must warn about preserving options.
		$this->assertStringContainsString('null', $result);
	}

	/**
	 * The cascade fix preserves the FK's existing ON UPDATE rule instead of forcing NO_ACTION.
	 *
	 * @return void
	 */
	public function testCascadeMigrationLinePreservesExistingUpdateRule() {
		$fk = new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_CODE, 'hasMany', 'Posts', 'Comments');

		$result = $this->suggester->cascadeMigrationLine($fk, 'cascade');

		$this->assertStringContainsString("'delete' => 'CASCADE'", $result);
		$this->assertStringContainsString("'update' => 'CASCADE'", $result);
	}

	/**
	 * Without a known current rule the cascade fix defaults ON UPDATE to NO_ACTION.
	 *
	 * @return void
	 */
	public function testCascadeMigrationLineDefaultsUpdateToNoAction() {
		$fk = new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_CODE, 'hasMany', 'Posts', 'Comments');

		$result = $this->suggester->cascadeMigrationLine($fk);

		$this->assertStringContainsString("'update' => 'NO_ACTION'", $result);
		$this->assertStringContainsString("'delete' => 'CASCADE'", $result);
	}

	/**
	 * The dependent-option fix sets cascadeCallbacks too, since dependent alone skips callbacks.
	 *
	 * @return void
	 */
	public function testDependentOptionEnablesCascadeCallbacks() {
		$fk = new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_CODE, 'hasMany', 'Posts', 'Comments');

		$result = $this->suggester->dependentOption($fk);

		$this->assertStringContainsString("'dependent' => true", $result);
		$this->assertStringContainsString("'cascadeCallbacks' => true", $result);
		// Default id binding needs no explicit bindingKey.
		$this->assertStringNotContainsString('bindingKey', $result);
	}

	/**
	 * The dependent fix preserves a non-default binding key so it isn't dropped on paste.
	 *
	 * @return void
	 */
	public function testDependentOptionPreservesCustomBindingKey() {
		$fk = new ForeignKey('default', 'comments', 'post_uuid', 'posts', 'uuid', ForeignKey::SOURCE_CODE, 'hasMany', 'Posts', 'Comments');

		$result = $this->suggester->dependentOption($fk);

		$this->assertStringContainsString("'bindingKey' => 'uuid'", $result);
	}

	/**
	 * The index line renders an addIndex with the single column in array form.
	 *
	 * @return void
	 */
	public function testIndexLineSingleColumnString() {
		$result = $this->suggester->indexLine('post_id');

		$this->assertSame("\$table->addIndex(['post_id']);", $result);
	}

	/**
	 * A single-column foreign key indexes just its column, in array form.
	 *
	 * @return void
	 */
	public function testIndexLineSingleColumnFk() {
		$fk = new ForeignKey('default', 'comments', 'post_id', 'posts', 'id', ForeignKey::SOURCE_DB);

		$result = $this->suggester->indexLine($fk);

		$this->assertSame("\$table->addIndex(['post_id']);", $result);
	}

	/**
	 * A composite foreign key indexes all of its columns in order.
	 *
	 * @return void
	 */
	public function testIndexLineComposite() {
		$fk = new ForeignKey('default', 'memberships', ['tenant_id', 'company_id'], 'companies', ['tenant_id', 'id'], ForeignKey::SOURCE_DB);

		$result = $this->suggester->indexLine($fk);

		$this->assertSame("\$table->addIndex(['tenant_id', 'company_id']);", $result);
	}

}
