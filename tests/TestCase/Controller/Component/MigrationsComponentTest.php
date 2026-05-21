<?php

namespace TestHelper\Test\TestCase\Controller\Component;

use Cake\Controller\ComponentRegistry;
use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Http\ServerRequest;
use Cake\TestSuite\TestCase;
use ReflectionClass;
use TestHelper\Controller\Component\MigrationsComponent;

class MigrationsComponentTest extends TestCase {

	/**
	 * @var array<string>
	 */
	protected array $fixtures = [
		'plugin.TestHelper.Posts',
	];

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
	protected function tearDown(): void {
		parent::tearDown();

		Configure::delete('Migrations.legacyTables');
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

	/**
	 * The detected drift should report no difference when comparing the schema
	 * of a connection against itself.
	 *
	 * @return void
	 */
	public function testCompareSchemasNoDriftAgainstSelf(): void {
		$connection = ConnectionManager::get('test');
		$schema = $this->component->getStructuredSchema($connection);

		$drift = $this->component->compareSchemas($schema, $schema);

		$this->assertFalse($this->component->hasDrift($drift));
	}

	/**
	 * @return void
	 */
	public function testHasDriftWithIndexDiffs(): void {
		$drift = [
			'extra_tables' => [],
			'missing_tables' => [],
			'column_diffs' => [],
			'index_diffs' => [['type' => 'extra', 'table' => 'users', 'index' => 'idx_temp']],
			'constraint_diffs' => [],
		];

		$this->assertTrue($this->component->hasDrift($drift));
	}

	/**
	 * @return void
	 */
	public function testHasDriftWithConstraintDiffs(): void {
		$drift = [
			'extra_tables' => [],
			'missing_tables' => [],
			'column_diffs' => [],
			'index_diffs' => [],
			'constraint_diffs' => [['type' => 'missing', 'table' => 'users', 'constraint' => 'fk_author']],
		];

		$this->assertTrue($this->component->hasDrift($drift));
	}

	/**
	 * The extra table entry must list all of its column names.
	 *
	 * @return void
	 */
	public function testCompareSchemasExtraTableListsColumns(): void {
		$expected = [];
		$actual = [
			'temp_data' => [
				'columns' => ['id' => ['type' => 'integer'], 'data' => ['type' => 'text']],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertCount(1, $drift['extra_tables']);
		$this->assertSame(['id', 'data'], $drift['extra_tables'][0]['columns']);
	}

	/**
	 * The missing table entry must list all of its column names.
	 *
	 * @return void
	 */
	public function testCompareSchemasMissingTableListsColumns(): void {
		$expected = [
			'posts' => [
				'columns' => ['id' => ['type' => 'integer'], 'title' => ['type' => 'string']],
				'indexes' => [],
				'constraints' => [],
			],
		];
		$actual = [];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertCount(1, $drift['missing_tables']);
		$this->assertSame(['id', 'title'], $drift['missing_tables'][0]['columns']);
	}

	/**
	 * Each tracked column attribute (length, null, default, unsigned, precision,
	 * scale, autoIncrement) must be detected as a mismatch difference.
	 *
	 * @return void
	 */
	public function testCompareSchemasColumnAttributeMismatches(): void {
		$expected = [
			'users' => [
				'columns' => [
					'amount' => [
						'type' => 'decimal',
						'length' => 8,
						'null' => false,
						'default' => null,
						'unsigned' => false,
						'precision' => 2,
						'scale' => 1,
						'autoIncrement' => false,
					],
				],
				'indexes' => [],
				'constraints' => [],
			],
		];
		$actual = [
			'users' => [
				'columns' => [
					'amount' => [
						'type' => 'decimal',
						'length' => 10,
						'null' => true,
						'default' => '0',
						'unsigned' => true,
						'precision' => 4,
						'scale' => 2,
						'autoIncrement' => true,
					],
				],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertCount(1, $drift['column_diffs']);
		$this->assertSame('mismatch', $drift['column_diffs'][0]['type']);

		$differences = $drift['column_diffs'][0]['differences'];
		$this->assertArrayHasKey('length', $differences);
		$this->assertArrayHasKey('null', $differences);
		$this->assertArrayHasKey('default', $differences);
		$this->assertArrayHasKey('unsigned', $differences);
		$this->assertArrayHasKey('precision', $differences);
		$this->assertArrayHasKey('scale', $differences);
		$this->assertArrayHasKey('autoIncrement', $differences);
		$this->assertArrayNotHasKey('type', $differences);

		$this->assertSame(8, $differences['length']['expected']);
		$this->assertSame(10, $differences['length']['actual']);
	}

	/**
	 * Columns differing only by an attribute outside the compared set must not
	 * be reported as drift.
	 *
	 * @return void
	 */
	public function testCompareSchemasColumnIgnoresUntrackedAttributes(): void {
		$expected = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer', 'collate' => 'utf8_bin']],
				'indexes' => [],
				'constraints' => [],
			],
		];
		$actual = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer', 'collate' => 'latin1_swedish']],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertFalse($this->component->hasDrift($drift));
	}

	/**
	 * @return void
	 */
	public function testCompareSchemasMissingIndex(): void {
		$expected = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => ['idx_email' => ['type' => 'index', 'columns' => ['email']]],
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

		$this->assertCount(1, $drift['index_diffs']);
		$this->assertSame('missing', $drift['index_diffs'][0]['type']);
		$this->assertSame('idx_email', $drift['index_diffs'][0]['index']);
	}

	/**
	 * @return void
	 */
	public function testCompareSchemasIndexMismatch(): void {
		$expected = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => ['idx_name' => ['type' => 'index', 'columns' => ['name']]],
				'constraints' => [],
			],
		];
		$actual = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => ['idx_name' => ['type' => 'index', 'columns' => ['name', 'email']]],
				'constraints' => [],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertCount(1, $drift['index_diffs']);
		$this->assertSame('mismatch', $drift['index_diffs'][0]['type']);
		$this->assertSame('idx_name', $drift['index_diffs'][0]['index']);
	}

	/**
	 * @return void
	 */
	public function testCompareSchemasExtraConstraint(): void {
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
				'constraints' => ['fk_author' => ['type' => 'foreign', 'columns' => ['author_id']]],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertCount(1, $drift['constraint_diffs']);
		$this->assertSame('extra', $drift['constraint_diffs'][0]['type']);
		$this->assertSame('fk_author', $drift['constraint_diffs'][0]['constraint']);
	}

	/**
	 * @return void
	 */
	public function testCompareSchemasMissingConstraint(): void {
		$expected = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => [],
				'constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
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

		$this->assertCount(1, $drift['constraint_diffs']);
		$this->assertSame('missing', $drift['constraint_diffs'][0]['type']);
		$this->assertSame('primary', $drift['constraint_diffs'][0]['constraint']);
	}

	/**
	 * Multiple kinds of drift across several tables must all be reported in a
	 * single comparison.
	 *
	 * @return void
	 */
	public function testCompareSchemasMixedDrift(): void {
		$expected = [
			'users' => [
				'columns' => ['id' => ['type' => 'integer'], 'email' => ['type' => 'string']],
				'indexes' => [],
				'constraints' => [],
			],
			'posts' => [
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
			'logs' => [
				'columns' => ['id' => ['type' => 'integer']],
				'indexes' => [],
				'constraints' => [],
			],
		];

		$drift = $this->component->compareSchemas($expected, $actual);

		$this->assertTrue($this->component->hasDrift($drift));
		$this->assertCount(1, $drift['missing_tables']);
		$this->assertSame('posts', $drift['missing_tables'][0]['table']);
		$this->assertCount(1, $drift['extra_tables']);
		$this->assertSame('logs', $drift['extra_tables'][0]['table']);
		$this->assertCount(1, $drift['column_diffs']);
		$this->assertSame('missing', $drift['column_diffs'][0]['type']);
		$this->assertSame('email', $drift['column_diffs'][0]['column']);
	}

	/**
	 * The structured schema must contain the real columns and constraints of a
	 * live connection and skip migration bookkeeping tables.
	 *
	 * @return void
	 */
	public function testGetStructuredSchemaFromConnection(): void {
		$connection = ConnectionManager::get('test');

		$schema = $this->component->getStructuredSchema($connection);

		$this->assertArrayHasKey('posts', $schema);
		$this->assertArrayNotHasKey('phinxlog', $schema);
		$this->assertArrayNotHasKey('cake_migrations', $schema);

		$this->assertArrayHasKey('columns', $schema['posts']);
		$this->assertArrayHasKey('indexes', $schema['posts']);
		$this->assertArrayHasKey('constraints', $schema['posts']);

		$this->assertArrayHasKey('id', $schema['posts']['columns']);
		$this->assertArrayHasKey('title', $schema['posts']['columns']);
		$this->assertArrayHasKey('primary', $schema['posts']['constraints']);
	}

	/**
	 * Volatile column attributes such as comments must be stripped so they do
	 * not surface as false drift.
	 *
	 * @return void
	 */
	public function testGetStructuredSchemaStripsComment(): void {
		$connection = ConnectionManager::get('test');

		$schema = $this->component->getStructuredSchema($connection);

		foreach ($schema['posts']['columns'] as $column) {
			$this->assertArrayNotHasKey('comment', $column);
		}
	}

	/**
	 * The structured schema must be ordered alphabetically by table name.
	 *
	 * @return void
	 */
	public function testGetStructuredSchemaIsSortedByTable(): void {
		$connection = ConnectionManager::get('test');

		$schema = $this->component->getStructuredSchema($connection);

		$tables = array_keys($schema);
		$sorted = $tables;
		sort($sorted);
		$this->assertSame($sorted, $tables);
	}

	/**
	 * @return void
	 */
	public function testGetApplicationTablesForConnection(): void {
		$result = $this->component->getApplicationTables('test');

		$this->assertContains('posts', $result);
		$this->assertNotContains('phinxlog', $result);
	}

	/**
	 * Without a legacy flag, the table name is auto-detected from the presence
	 * of the modern cake_migrations table. The expectation is derived from the
	 * live schema so the test stays valid regardless of ambient database state.
	 *
	 * @return void
	 */
	public function testGetMigrationTableNameAutoDetects(): void {
		Configure::delete('Migrations.legacyTables');

		$tables = ConnectionManager::get('test')->getSchemaCollection()->listTables();
		$expected = in_array('cake_migrations', $tables, true) ? 'cake_migrations' : 'phinxlog';

		$result = $this->component->getMigrationTableName('test');

		$this->assertSame($expected, $result);
	}

	/**
	 * An explicit legacy flag must short-circuit auto-detection.
	 *
	 * @return void
	 */
	public function testGetMigrationTableNameRespectsLegacyConfigTrue(): void {
		Configure::write('Migrations.legacyTables', true);

		$result = $this->component->getMigrationTableName('test');

		$this->assertSame('phinxlog', $result);
	}

	/**
	 * @return void
	 */
	public function testGetMigrationTableNameRespectsLegacyConfigFalse(): void {
		Configure::write('Migrations.legacyTables', false);

		$result = $this->component->getMigrationTableName('test');

		$this->assertSame('cake_migrations', $result);
	}

	/**
	 * The detected plugin list must be a sorted, de-duplicated string array.
	 * Its membership depends on migration bookkeeping tables that may exist on a
	 * persistent test database, so only the contract is asserted.
	 *
	 * @return void
	 */
	public function testGetPluginsWithMigrationsForConnection(): void {
		$result = $this->component->getPluginsWithMigrations('test');

		foreach ($result as $plugin) {
			$this->assertIsString($plugin);
		}

		$sorted = $result;
		sort($sorted);
		$this->assertSame($sorted, $result);
		$this->assertSame(array_values(array_unique($result)), $result);
	}

	/**
	 * @return void
	 */
	public function testNormalizeColumnNull(): void {
		$result = $this->invokeMethod($this->component, 'normalizeColumn', [null]);

		$this->assertSame([], $result);
	}

	/**
	 * @return void
	 */
	public function testNormalizeColumnStripsComment(): void {
		$column = ['type' => 'string', 'length' => 255, 'comment' => 'some note'];

		$result = $this->invokeMethod($this->component, 'normalizeColumn', [$column]);

		$this->assertArrayNotHasKey('comment', $result);
		$this->assertSame('string', $result['type']);
		$this->assertSame(255, $result['length']);
	}

	/**
	 * Helper to invoke protected/private methods.
	 *
	 * @param object $object
	 * @param string $methodName
	 * @param array<mixed> $parameters
	 * @return mixed
	 */
	protected function invokeMethod(object $object, string $methodName, array $parameters = []): mixed {
		$method = (new ReflectionClass($object::class))->getMethod($methodName);

		return $method->invokeArgs($object, $parameters);
	}

}
