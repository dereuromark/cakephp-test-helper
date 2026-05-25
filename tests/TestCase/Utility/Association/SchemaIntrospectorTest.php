<?php

namespace TestHelper\Test\TestCase\Utility\Association;

use Cake\Database\Connection;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use TestHelper\Utility\Association\SchemaIntrospector;
use Throwable;

class SchemaIntrospectorTest extends TestCase {

	protected Connection $connection;

	protected SchemaIntrospector $introspector;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$connection = ConnectionManager::get('test');
		if (!$connection instanceof Connection) {
			$this->markTestSkipped('Test connection is not a database connection.');
		}
		$this->connection = $connection;
		$this->introspector = new SchemaIntrospector();

		$this->dropTables();
		$this->createTables();
	}

	/**
	 * @return void
	 */
	protected function tearDown(): void {
		$this->dropTables();

		parent::tearDown();
	}

	/**
	 * The introspector captures the real ON DELETE / ON UPDATE rule of a DB foreign key.
	 *
	 * @return void
	 */
	public function testForeignKeysCaptureCascadeRules() {
		$keys = $this->introspector->foreignKeys($this->connection, 'th_audit_children');

		$this->assertCount(1, $keys);
		$key = $keys[0];
		$this->assertSame('parent_id', $key->column);
		$this->assertSame('th_audit_parents', $key->referencedTable);
		$this->assertSame(TableSchema::ACTION_CASCADE, $key->onDelete);
	}

	/**
	 * A composite foreign key is introspected as ordered column arrays, not skipped.
	 *
	 * @return void
	 */
	public function testForeignKeysCaptureCompositeColumns() {
		$this->createCompositeTables();

		$keys = $this->introspector->foreignKeys($this->connection, 'th_audit_memberships');

		$this->assertCount(1, $keys);
		$this->assertSame(['region', 'parent_code'], $keys[0]->columns);
		$this->assertSame('th_audit_cparents', $keys[0]->referencedTable);
		$this->assertSame(['region', 'code'], $keys[0]->referencedColumns);
		$this->assertTrue($keys[0]->isComposite());
	}

	/**
	 * indexedColumns() returns the leading column of the primary key, of unique constraints
	 * and of regular indexes, but not a column buried as a non-leading member of a composite
	 * index.
	 *
	 * @return void
	 */
	public function testIndexedColumnsReturnsLeadingColumns() {
		$this->createIndexedTable();

		$indexed = $this->introspector->indexedColumns($this->connection, 'th_audit_indexed');
		sort($indexed);

		// id (primary), slug (unique), author_id (regular index leading column),
		// region (composite index leading column). post_id is buried second in the
		// composite index and must not appear.
		$this->assertContains('id', $indexed);
		$this->assertContains('slug', $indexed);
		$this->assertContains('author_id', $indexed);
		$this->assertContains('region', $indexed);
		$this->assertNotContains('post_id', $indexed);
	}

	/**
	 * @return void
	 */
	protected function createIndexedTable(): void {
		$table = (new TableSchema('th_audit_indexed'))
			->addColumn('id', ['type' => 'integer', 'null' => false])
			->addColumn('slug', ['type' => 'string', 'length' => 50, 'null' => false])
			->addColumn('author_id', ['type' => 'integer', 'null' => true])
			->addColumn('region', ['type' => 'integer', 'null' => true])
			->addColumn('post_id', ['type' => 'integer', 'null' => true])
			->addConstraint('primary', ['type' => 'primary', 'columns' => ['id']])
			->addConstraint('uq_slug', ['type' => 'unique', 'columns' => ['slug']])
			->addIndex('idx_author', ['type' => 'index', 'columns' => ['author_id']])
			->addIndex('idx_region_post', ['type' => 'index', 'columns' => ['region', 'post_id']]);

		foreach ($table->createSql($this->connection) as $sql) {
			$this->connection->execute($sql);
		}
	}

	/**
	 * @return void
	 */
	protected function createTables(): void {
		$parents = (new TableSchema('th_audit_parents'))
			->addColumn('id', ['type' => 'integer', 'null' => false])
			->addConstraint('primary', ['type' => 'primary', 'columns' => ['id']]);

		$children = (new TableSchema('th_audit_children'))
			->addColumn('id', ['type' => 'integer', 'null' => false])
			->addColumn('parent_id', ['type' => 'integer', 'null' => true])
			->addConstraint('primary', ['type' => 'primary', 'columns' => ['id']])
			->addConstraint('fk_audit_parent', [
				'type' => 'foreign',
				'columns' => ['parent_id'],
				'references' => ['th_audit_parents', 'id'],
				'delete' => TableSchema::ACTION_CASCADE,
				'update' => TableSchema::ACTION_NO_ACTION,
			]);

		foreach ($parents->createSql($this->connection) as $sql) {
			$this->connection->execute($sql);
		}
		foreach ($children->createSql($this->connection) as $sql) {
			$this->connection->execute($sql);
		}
	}

	/**
	 * @return void
	 */
	protected function createCompositeTables(): void {
		$parents = (new TableSchema('th_audit_cparents'))
			->addColumn('region', ['type' => 'integer', 'null' => false])
			->addColumn('code', ['type' => 'integer', 'null' => false])
			->addConstraint('primary', ['type' => 'primary', 'columns' => ['region', 'code']]);

		$memberships = (new TableSchema('th_audit_memberships'))
			->addColumn('id', ['type' => 'integer', 'null' => false])
			->addColumn('region', ['type' => 'integer', 'null' => true])
			->addColumn('parent_code', ['type' => 'integer', 'null' => true])
			->addConstraint('primary', ['type' => 'primary', 'columns' => ['id']])
			->addConstraint('fk_audit_membership', [
				'type' => 'foreign',
				'columns' => ['region', 'parent_code'],
				'references' => ['th_audit_cparents', ['region', 'code']],
				'delete' => TableSchema::ACTION_NO_ACTION,
				'update' => TableSchema::ACTION_NO_ACTION,
			]);

		foreach ($parents->createSql($this->connection) as $sql) {
			$this->connection->execute($sql);
		}
		foreach ($memberships->createSql($this->connection) as $sql) {
			$this->connection->execute($sql);
		}
	}

	/**
	 * @return void
	 */
	protected function dropTables(): void {
		foreach (['th_audit_children', 'th_audit_memberships', 'th_audit_parents', 'th_audit_cparents', 'th_audit_indexed'] as $table) {
			$schema = new TableSchema($table);
			foreach ($schema->dropSql($this->connection) as $sql) {
				try {
					$this->connection->execute($sql);
				} catch (Throwable) {
					// Table may not exist yet; ignore.
				}
			}
		}
	}

}
