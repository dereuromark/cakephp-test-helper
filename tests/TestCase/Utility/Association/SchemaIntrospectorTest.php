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
	 * A single-column foreign key is introspected with its owner and referenced column.
	 *
	 * @return void
	 */
	public function testForeignKeysCaptureSingleColumn() {
		$keys = $this->introspector->foreignKeys($this->connection, 'th_audit_children');

		$this->assertCount(1, $keys);
		$key = $keys[0];
		$this->assertSame('parent_id', $key->column);
		$this->assertSame('th_audit_parents', $key->referencedTable);
		$this->assertSame('id', $key->referencedColumn);
		$this->assertFalse($key->isComposite());
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
				'delete' => TableSchema::ACTION_NO_ACTION,
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
		foreach (['th_audit_children', 'th_audit_memberships', 'th_audit_parents', 'th_audit_cparents'] as $table) {
			$schema = new TableSchema($table);
			foreach ($schema->dropSql($this->connection) as $sql) {
				try {
					$this->connection->execute($sql);
				} catch (Throwable $e) {
					// Table may not exist yet; ignore.
				}
			}
		}
	}

}
