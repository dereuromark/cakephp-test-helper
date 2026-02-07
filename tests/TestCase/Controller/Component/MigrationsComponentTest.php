<?php

namespace TestHelper\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use TestHelper\Controller\Component\MigrationsComponent;

class MigrationsComponentTest extends TestCase {

	protected MigrationsComponent $component;

	/**
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->component = new MigrationsComponent(new ComponentRegistry(new Controller(new ServerRequest())));
	}

	/**
	 * @return void
	 */
	public function testHasDriftEmpty(): void {
		$drift = [
			'extra_tables' => [],
			'missing_tables' => [],
			'column_diffs' => [],
			'index_diffs' => [],
			'constraint_diffs' => [],
		];

		$this->assertFalse($this->component->hasDrift($drift));
	}

	/**
	 * @return void
	 */
	public function testHasDriftWithExtraTables(): void {
		$drift = [
			'extra_tables' => [['table' => 'temp_table', 'columns' => ['id']]],
			'missing_tables' => [],
			'column_diffs' => [],
			'index_diffs' => [],
			'constraint_diffs' => [],
		];

		$this->assertTrue($this->component->hasDrift($drift));
	}

	/**
	 * @return void
	 */
	public function testHasDriftWithMissingTables(): void {
		$drift = [
			'extra_tables' => [],
			'missing_tables' => [['table' => 'users', 'columns' => ['id', 'name']]],
			'column_diffs' => [],
			'index_diffs' => [],
			'constraint_diffs' => [],
		];

		$this->assertTrue($this->component->hasDrift($drift));
	}

	/**
	 * @return void
	 */
	public function testHasDriftWithColumnDiffs(): void {
		$drift = [
			'extra_tables' => [],
			'missing_tables' => [],
			'column_diffs' => [['type' => 'extra', 'table' => 'users', 'column' => 'temp']],
			'index_diffs' => [],
			'constraint_diffs' => [],
		];

		$this->assertTrue($this->component->hasDrift($drift));
	}

	/**
	 * @return void
	 */
	public function testCompareSchemasIdentical(): void {
		$schema = [
			'users' => [
				'columns' => [
					'id' => ['type' => 'integer', 'null' => false],
					'name' => ['type' => 'string', 'length' => 255, 'null' => true],
				],
				'indexes' => [],
				'constraints' => [
					'primary' => ['type' => 'primary', 'columns' => ['id']],
				],
			],
		];

		$drift = $this->component->compareSchemas($schema, $schema);

		$this->assertFalse($this->component->hasDrift($drift));
		$this->assertEmpty($drift['extra_tables']);
		$this->assertEmpty($drift['missing_tables']);
		$this->assertEmpty($drift['column_diffs']);
	}

	/**
	 * @return void
	 */
	public function testCompareSchemasExtraTable(): void {
		$expected = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$actual = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => [],
				'constraints' => [],
			],
			'temp_data' => [
				'columns' => ['id' => ['type' => 'integer'], 'data' => ['type' => 'text']],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertTrue($this->component->hasDrift($drift));
		$this->assertCount(1, $drift['extra_tables']);
		$this->assertSame('temp_data', $drift['extra_tables'][0]['table']);
		$this->assertEmpty($drift['missing_tables']);
	}

	/**
	 * @return void
	 */
	public function testCompareSchemasMissingTable(): void {
		$expected = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => [],
				'constraints' => [],
			],
			'posts' => [
				'columns' => ['id' => ['type' => 'integer'], 'title' => ['type' => 'string']],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$actual = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertTrue($this->component->hasDrift($drift));
		$this->assertEmpty($drift['extra_tables']);
		$this->assertCount(1, $drift['missing_tables']);
		$this->assertSame('posts', $drift['missing_tables'][0]['table']);
	}

	/**
	 * @return void
	 */
	public function testCompareSchemasExtraColumn(): void {
		$expected = [
			'users' => [
				'columns' => [
					'id' => ['type' => 'integer'],
				],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$actual = [
			'users' => [
				'columns' => [
					'id' => ['type' => 'integer'],
					'temp_field' => ['type' => 'string'],
				],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertTrue($this->component->hasDrift($drift));
		$this->assertCount(1, $drift['column_diffs']);
		$this->assertSame('extra', $drift['column_diffs'][0]['type']);
		$this->assertSame('temp_field', $drift['column_diffs'][0]['column']);
	}

	/**
	 * @return void
	 */
	public function testCompareSchemasMissingColumn(): void {
		$expected = [
			'users' => [
				'columns' => [
					'id' => ['type' => 'integer'],
					'email' => ['type' => 'string'],
				],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$actual = [
			'users' => [
				'columns' => [
					'id' => ['type' => 'integer'],
				],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertTrue($this->component->hasDrift($drift));
		$this->assertCount(1, $drift['column_diffs']);
		$this->assertSame('missing', $drift['column_diffs'][0]['type']);
		$this->assertSame('email', $drift['column_diffs'][0]['column']);
	}

	/**
	 * @return void
	 */
	public function testCompareSchemasColumnTypeMismatch(): void {
		$expected = [
			'users' => [
				'columns' => [
					'id' => ['type' => 'integer'],
					'age' => ['type' => 'integer', 'null' => false],
				],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$actual = [
			'users' => [
				'columns' => [
					'id' => ['type' => 'integer'],
					'age' => ['type' => 'string', 'null' => false],
				],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertTrue($this->component->hasDrift($drift));
		$this->assertCount(1, $drift['column_diffs']);
		$this->assertSame('mismatch', $drift['column_diffs'][0]['type']);
		$this->assertSame('age', $drift['column_diffs'][0]['column']);
		$this->assertArrayHasKey('differences', $drift['column_diffs'][0]);
		$this->assertArrayHasKey('type', $drift['column_diffs'][0]['differences']);
	}

	/**
	 * @return void
	 */
	public function testCompareSchemasExtraIndex(): void {
		$expected = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$actual = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => [
					'idx_temp' => ['type' => 'index', 'columns' => ['id']],
				],
				'constraints' => [],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertTrue($this->component->hasDrift($drift));
		$this->assertCount(1, $drift['index_diffs']);
		$this->assertSame('extra', $drift['index_diffs'][0]['type']);
		$this->assertSame('idx_temp', $drift['index_diffs'][0]['index']);
	}

	/**
	 * @return void
	 */
	public function testCompareSchemasConstraintMismatch(): void {
		$expected = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => [],
				'constraints' => [
					'primary' => ['type' => 'primary', 'columns' => ['id']],
				],
			],
		];

		$actual = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => [],
				'constraints' => [
					'primary' => ['type' => 'primary', 'columns' => ['id', 'other']],
				],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertTrue($this->component->hasDrift($drift));
		$this->assertCount(1, $drift['constraint_diffs']);
		$this->assertSame('mismatch', $drift['constraint_diffs'][0]['type']);
	}

	/**
	 * @return void
	 */
	public function testIsIgnoredTablePhinxlog(): void {
		$this->assertTrue($this->component->isIgnoredTable('phinxlog'));
	}

	/**
	 * @return void
	 */
	public function testIsIgnoredTableCakeMigrations(): void {
		$this->assertTrue($this->component->isIgnoredTable('cake_migrations'));
	}

	/**
	 * @return void
	 */
	public function testIsIgnoredTableCakeSeeds(): void {
		$this->assertTrue($this->component->isIgnoredTable('cake_seeds'));
	}

	/**
	 * @return void
	 */
	public function testIsIgnoredTablePluginPhinxlog(): void {
		$this->assertTrue($this->component->isIgnoredTable('queue_phinxlog'));
		$this->assertTrue($this->component->isIgnoredTable('file_storage_phinxlog'));
	}

	/**
	 * @return void
	 */
	public function testIsIgnoredTableRegularTable(): void {
		$this->assertFalse($this->component->isIgnoredTable('users'));
		$this->assertFalse($this->component->isIgnoredTable('articles'));
	}

	/**
	 * @return void
	 */
	public function testGetPluginsWithMigrations(): void {
		$result = $this->component->getPluginsWithMigrations();

		$this->assertIsArray($result);
		// Result may be empty if no plugins with migrations are detected
	}

	/**
	 * @return void
	 */
	public function testGetApplicationTables(): void {
		$result = $this->component->getApplicationTables();

		$this->assertIsArray($result);
		// Verify migration tracking tables are not included
		$this->assertNotContains('phinxlog', $result);
		$this->assertNotContains('cake_migrations', $result);
		$this->assertNotContains('cake_seeds', $result);
	}

}
