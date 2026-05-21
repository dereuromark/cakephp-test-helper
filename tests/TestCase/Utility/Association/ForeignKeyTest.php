<?php

namespace TestHelper\Test\TestCase\Utility\Association;

use Cake\TestSuite\TestCase;
use TestHelper\Utility\Association\ForeignKey;

class ForeignKeyTest extends TestCase {

	/**
	 * A composite FK exposes its columns as ordered arrays plus a joined display form.
	 *
	 * @return void
	 */
	public function testCompositeColumnsExposedAsArrays() {
		$fk = new ForeignKey('default', 'memberships', ['tenant_id', 'user_id'], 'users', ['tenant_id', 'id'], ForeignKey::SOURCE_DB);

		$this->assertSame(['tenant_id', 'user_id'], $fk->columns);
		$this->assertSame(['tenant_id', 'id'], $fk->referencedColumns);
		$this->assertSame('tenant_id, user_id', $fk->column);
		$this->assertSame('tenant_id, id', $fk->referencedColumn);
		$this->assertTrue($fk->isComposite());
	}

	/**
	 * A single-column FK keeps the string accessors and is not composite (backward compat).
	 *
	 * @return void
	 */
	public function testSingleColumnBackwardCompatible() {
		$fk = new ForeignKey('default', 'posts', 'author_id', 'authors', 'id', ForeignKey::SOURCE_DB);

		$this->assertSame(['author_id'], $fk->columns);
		$this->assertSame('author_id', $fk->column);
		$this->assertSame('id', $fk->referencedColumn);
		$this->assertFalse($fk->isComposite());
	}

	/**
	 * The identity key folds the ordered columns in, so composite keys compare correctly.
	 *
	 * @return void
	 */
	public function testCompositeKeyIdentity() {
		$a = new ForeignKey('default', 'memberships', ['tenant_id', 'user_id'], 'users', ['tenant_id', 'id'], ForeignKey::SOURCE_CODE);
		$b = new ForeignKey('default', 'memberships', ['tenant_id', 'user_id'], 'users', ['tenant_id', 'id'], ForeignKey::SOURCE_DB);

		$this->assertSame($a->key(), $b->key());
	}

}
