<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Query;

use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\ORM\Table;
use Cake\TestSuite\TestCase;
use LogicException;
use RuntimeException;
use TestHelper\Query\QueryBuilderGenerator;
use TestHelper\Query\SqlParser;

/**
 * QueryBuilderGenerator Test Case
 *
 * @uses \TestHelper\Query\QueryBuilderGenerator
 */
class QueryBuilderGeneratorTest extends TestCase {

	protected QueryBuilderGenerator $generator;

	protected SqlParser $parser;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->generator = new QueryBuilderGenerator();
		$this->parser = new SqlParser();
	}

	public function testMultiTableUpdateRequiresConditionsAndUsesCorrectTables(): void {
		$connection = new Connection(['driver' => Sqlite::class, 'database' => ':memory:']);
		$connection->execute('CREATE TABLE audit_records (id INTEGER PRIMARY KEY, title TEXT)');
		$connection->execute('CREATE TABLE audit_notes (id INTEGER PRIMARY KEY, note TEXT)');
		$connection->execute("INSERT INTO audit_records VALUES (1, 'first'), (2, 'untouched')");
		$connection->execute("INSERT INTO audit_notes VALUES (1, 'first'), (2, 'untouched')");
		$table = new Table(['table' => 'audit_records', 'connection' => $connection]);
		try {
			$sql = "UPDATE audit_records r JOIN audit_notes n ON n.id = r.id SET r.title = 'updated', n.note = 'changed' WHERE r.id = 1";
			$code = $this->generator->generate($this->parser->parse($sql));
			try {
				(function () use ($code): void {
					eval($code);
				})->call($table);
				$this->fail('The template must require explicit conditions before executing.');
			} catch (LogicException $exception) {
				$this->assertStringContainsString('table-specific update conditions', $exception->getMessage());
			}
			$this->assertSame('first', $table->get(1)->title);
			$conditionsByTable = ['r' => ['id' => 1], 'n' => ['id' => 1]];
			(function () use ($code, $conditionsByTable): void {
				eval($code);
			})->call($table);
			$this->assertSame(['updated', 'untouched'], $table->find()->orderBy(['id'])->all()->extract('title')->toList());
			$this->assertSame(['changed', 'untouched'], array_column($connection->execute('SELECT note FROM audit_notes ORDER BY id')->fetchAll('assoc'), 'note'));
		} finally {
			$connection->getDriver()->disconnect();
		}
	}

	public function testGeneratedSelectAliasesExecute(): void {
		$connection = new Connection(['driver' => Sqlite::class, 'database' => ':memory:']);
		$connection->execute('CREATE TABLE audit_records (id INTEGER PRIMARY KEY, title TEXT)');
		$connection->execute("INSERT INTO audit_records VALUES (1, 'first')");
		$table = new Table(['table' => 'audit_records', 'connection' => $connection]);
		try {
			foreach (['SELECT r.title AS label FROM audit_records r', 'SELECT title AS label FROM audit_records'] as $sql) {
				$code = $this->generator->generate($this->parser->parse($sql));
				$rows = (function () use ($code): array {
					eval($code);

					return $query->enableHydration(false)->toArray();
				})->call($table);
				$this->assertSame([['label' => 'first']], $rows);
			}
		} finally {
			$connection->getDriver()->disconnect();
		}
	}

	public function testGeneratedFunctionsExecuteWithColumnArguments(): void {
		$connection = new Connection(['driver' => Sqlite::class, 'database' => ':memory:']);
		$connection->execute('CREATE TABLE audit_records (id INTEGER PRIMARY KEY, title TEXT)');
		$connection->execute("INSERT INTO audit_records VALUES (1, '  first  '), (2, NULL)");
		$table = new Table(['table' => 'audit_records', 'connection' => $connection]);
		try {
			$sql = "SELECT UPPER(TRIM(title)) AS label, LENGTH(title) AS size, COALESCE(title, 'fallback') AS value FROM audit_records ORDER BY id";
			$code = $this->generator->generate($this->parser->parse($sql));
			$rows = (function () use ($code): array {
				eval($code);

				return $query->enableHydration(false)->toArray();
			})->call($table);
			$this->assertSame([
				['label' => 'FIRST', 'size' => 9, 'value' => '  first  '],
				['label' => null, 'size' => null, 'value' => 'fallback'],
			], $rows);
		} finally {
			$connection->getDriver()->disconnect();
		}
	}

	public function testGeneratedFunctionSqlLiteralsExecute(): void {
		$connection = new Connection(['driver' => Sqlite::class, 'database' => ':memory:']);
		$connection->execute('CREATE TABLE audit_records (id INTEGER PRIMARY KEY, title TEXT)');
		$connection->execute("INSERT INTO audit_records VALUES (1, 'first')");
		$table = new Table(['table' => 'audit_records', 'connection' => $connection]);
		try {
			$sql = "SELECT COALESCE(NULL, title) AS label, COALESCE(NULL, TRUE) AS enabled, COALESCE(NULL, FALSE) AS disabled, COALESCE(NULL, 'NULL') AS quoted FROM audit_records";
			$code = $this->generator->generate($this->parser->parse($sql));
			$rows = (function () use ($code): array {
				eval($code);

				return $query->enableHydration(false)->toArray();
			})->call($table);
			$this->assertSame([['label' => 'first', 'enabled' => 1, 'disabled' => 0, 'quoted' => 'NULL']], $rows);
		} finally {
			$connection->getDriver()->disconnect();
		}
	}

	public function testDateFormatHasDialectWarning(): void {
		$code = $this->generator->generate($this->parser->parse("SELECT DATE_FORMAT(created, '%Y') AS year FROM audit_records"));
		$this->assertStringContainsString('/* MySQL-specific DATE_FORMAT() */', $code);
	}

	public function testGeneratedMutationAlternativesExecute(): void {
		$connection = new Connection(['driver' => Sqlite::class, 'database' => ':memory:']);
		$connection->execute('CREATE TABLE audit_records (id INTEGER PRIMARY KEY, title TEXT)');
		$table = new Table(['table' => 'audit_records', 'connection' => $connection]);
		$examples = [
			["INSERT INTO audit_records (id, title) VALUES (1, 'first')", '// Alternative: Insert query'],
			["INSERT INTO audit_records (id, title) VALUES (2, 'second'), (3, 'third')", '// Option 2: Bulk insert query'],
			["UPDATE audit_records SET title = 'updated' WHERE id = 1", '// Option 2: Update query (bulk update)'],
			['DELETE FROM audit_records WHERE id = 2', '// Option 2: Delete query (bulk delete)'],
		];
		try {
			foreach ($examples as [$sql, $marker]) {
				$parsed = $this->parser->parse($sql);
				$code = $this->generator->generate($parsed);
				$this->assertStringContainsString($marker, $code);
				$alternative = substr($code, strpos($code, $marker) + strlen($marker));
				(function () use ($alternative): void {
					eval($alternative);
				})->call($table);
			}
			$this->assertSame([
				['id' => 1, 'title' => 'updated'],
				['id' => 3, 'title' => 'third'],
			], $table->find()->orderBy(['id' => 'ASC'])->enableHydration(false)->toArray());
		} finally {
			$connection->getDriver()->disconnect();
		}
	}

	/**
	 * Test constructor with dialect
	 *
	 * @return void
	 */
	public function testConstructorWithDialect(): void {
		$generator = new QueryBuilderGenerator('postgres');
		$this->assertInstanceOf(QueryBuilderGenerator::class, $generator);
	}

	/**
	 * Test simple SELECT generation
	 *
	 * @return void
	 */
	public function testGenerateSimpleSelect(): void {
		$sql = 'SELECT * FROM users WHERE active = 1';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('$query = $this->find()', $code);
		$this->assertStringContainsString('->where([', $code);
	}

	/**
	 * Test SELECT with specific fields
	 *
	 * @return void
	 */
	public function testGenerateSelectWithFields(): void {
		$sql = 'SELECT id, name, email FROM users';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->select([', $code);
		$this->assertStringContainsString("'id'", $code);
		$this->assertStringContainsString("'name'", $code);
		$this->assertStringContainsString("'email'", $code);
	}

	/**
	 * Test SELECT with DISTINCT
	 *
	 * @return void
	 */
	public function testGenerateSelectWithDistinct(): void {
		$sql = 'SELECT DISTINCT user_id FROM posts';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->distinct()', $code);
	}

	/**
	 * Test SELECT with JOINs
	 *
	 * @return void
	 */
	public function testGenerateSelectWithJoin(): void {
		$sql = 'SELECT * FROM users LEFT JOIN posts ON posts.user_id = users.id';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->leftJoin(', $code);
		$this->assertStringContainsString('posts', $code);
	}

	/**
	 * Test SELECT with ORDER BY
	 *
	 * @return void
	 */
	public function testGenerateSelectWithOrderBy(): void {
		$sql = 'SELECT * FROM users ORDER BY created DESC';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->orderBy([', $code);
		$this->assertStringContainsString("'created' => 'DESC'", $code);
	}

	/**
	 * Test SELECT with LIMIT
	 *
	 * @return void
	 */
	public function testGenerateSelectWithLimit(): void {
		$sql = 'SELECT * FROM users LIMIT 10';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->limit(10)', $code);
	}

	/**
	 * Test SELECT with LIMIT and OFFSET
	 *
	 * @return void
	 */
	public function testGenerateSelectWithLimitAndOffset(): void {
		$sql = 'SELECT * FROM users LIMIT 10 OFFSET 20';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->limit(10)', $code);
		$this->assertStringContainsString('->offset(20)', $code);
	}

	/**
	 * Test SELECT with GROUP BY
	 *
	 * @return void
	 */
	public function testGenerateSelectWithGroupBy(): void {
		$sql = 'SELECT user_id, COUNT(*) FROM posts GROUP BY user_id';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->groupBy([', $code);
		$this->assertStringContainsString("'user_id'", $code);
	}

	/**
	 * Test SELECT with HAVING
	 *
	 * @return void
	 */
	public function testGenerateSelectWithHaving(): void {
		$sql = 'SELECT user_id, COUNT(*) FROM posts GROUP BY user_id HAVING COUNT(*) > 10';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->having([', $code);
	}

	/**
	 * Test INSERT generation
	 *
	 * @return void
	 */
	public function testGenerateInsert(): void {
		$sql = "INSERT INTO users (username, email) VALUES ('john', 'john@example.com')";
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('newEmptyEntity', $code);
		$this->assertStringContainsString("'username'", $code);
		$this->assertStringContainsString("'email'", $code);
	}

	/**
	 * Test bulk INSERT generation
	 *
	 * @return void
	 */
	public function testGenerateBulkInsert(): void {
		$sql = "INSERT INTO users (name, email) VALUES ('Alice', 'alice@example.com'), ('Bob', 'bob@example.com')";
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('Bulk INSERT', $code);
		$this->assertStringContainsString('saveMany', $code);
	}

	/**
	 * Test UPDATE generation
	 *
	 * @return void
	 */
	public function testGenerateUpdate(): void {
		$sql = 'UPDATE users SET active = 0 WHERE id = 5';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('patchEntity', $code);
		$this->assertStringContainsString("'active'", $code);
		$this->assertStringContainsString('->set([', $code);
	}

	/**
	 * Test DELETE generation
	 *
	 * @return void
	 */
	public function testGenerateDelete(): void {
		$sql = 'DELETE FROM users WHERE active = 0';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('$this->delete($entity)', $code);
		$this->assertStringContainsString('$this->deleteQuery()', $code);
		$this->assertStringContainsString('->where([', $code);
	}

	/**
	 * Test UNION generation
	 *
	 * @return void
	 */
	public function testGenerateUnion(): void {
		$sql = 'SELECT id, name FROM users UNION SELECT id, name FROM admins';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->union(', $code);
	}

	/**
	 * Test UNION ALL generation
	 *
	 * @return void
	 */
	public function testGenerateUnionAll(): void {
		$sql = 'SELECT id FROM users UNION ALL SELECT id FROM deleted_users';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->unionAll(', $code);
	}

	/**
	 * Test unsupported query type throws exception
	 *
	 * @return void
	 */
	public function testGenerateUnsupportedType(): void {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Unsupported query type');

		$this->generator->generate(['type' => 'TRUNCATE']);
	}

	/**
	 * Test optimization suggestions
	 *
	 * @return void
	 */
	public function testGetOptimizations(): void {
		$sql = 'SELECT * FROM users';
		$parsed = $this->parser->parse($sql);
		$this->generator->generate($parsed);

		$optimizations = $this->generator->getOptimizations();
		$this->assertIsArray($optimizations);
		// Should suggest LIMIT for large result sets
		$this->assertNotEmpty($optimizations);
	}

	/**
	 * Test CTE (Common Table Expression) generation
	 *
	 * @return void
	 */
	public function testGenerateCte(): void {
		$parsed = [
			'type' => 'CTE',
			'ctes' => [
				['raw' => 'active_users AS (SELECT * FROM users WHERE active = 1)'],
			],
			'mainQuery' => [
				'type' => 'SELECT',
				'fields' => ['*'],
				'from' => 'active_users',
				'fromAlias' => null,
				'joins' => [],
				'where' => null,
				'groupBy' => [],
				'having' => null,
				'orderBy' => [],
				'limit' => null,
				'offset' => null,
				'distinct' => false,
			],
		];
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('CTE', $code);
		$this->assertStringContainsString('Common Table Expression', $code);
		$this->assertStringContainsString('WITH', $code);
	}

	/**
	 * Test SELECT with field aliases
	 *
	 * @return void
	 */
	public function testGenerateSelectWithFieldAliases(): void {
		$sql = 'SELECT name AS username FROM users';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->select([', $code);
	}

	/**
	 * Test SELECT with INNER JOIN
	 *
	 * @return void
	 */
	public function testGenerateSelectWithInnerJoin(): void {
		$sql = 'SELECT * FROM users INNER JOIN posts ON posts.user_id = users.id';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->innerJoin(', $code);
	}

	/**
	 * Test SELECT with RIGHT JOIN
	 *
	 * @return void
	 */
	public function testGenerateSelectWithRightJoin(): void {
		$sql = 'SELECT * FROM users RIGHT JOIN posts ON posts.user_id = users.id';
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('->rightJoin(', $code);
	}

	/**
	 * Test PostgreSQL dialect generation
	 *
	 * @return void
	 */
	public function testGenerateWithPostgresDialect(): void {
		$generator = new QueryBuilderGenerator('postgres');
		$parser = new SqlParser('postgres');
		$sql = 'SELECT * FROM users WHERE active = true';
		$parsed = $parser->parse($sql);
		$code = $generator->generate($parsed);

		$this->assertStringContainsString('$query = $this->find()', $code);
	}

	/**
	 * Test SELECT with ORM-style aliases
	 *
	 * @return void
	 */
	public function testGenerateSelectWithOrmAliases(): void {
		$sql = "SELECT authors.id AS Authors__id, authors.name AS Authors__name
			FROM authors AS Authors
			LEFT JOIN articles AS Articles ON articles.author_id = authors.id";
		$parsed = $this->parser->parse($sql);
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('ORM-style aliases', $code);
		$this->assertStringContainsString('contain()', $code);
	}

	/**
	 * Test empty UNION queries
	 *
	 * @return void
	 */
	public function testGenerateEmptyUnion(): void {
		$parsed = [
			'type' => 'UNION',
			'queries' => [],
			'unionAll' => false,
		];
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('ERROR: No queries found', $code);
	}

	/**
	 * Test multi-table UPDATE
	 *
	 * @return void
	 */
	public function testGenerateMultiTableUpdate(): void {
		$parsed = [
			'type' => 'UPDATE',
			'table' => 'users',
			'set' => [
				'users.last_login' => 'NOW()',
				'sessions.updated_at' => 'NOW()',
			],
			'where' => 'users.id = sessions.user_id',
			'isMultiTable' => true,
		];
		$code = $this->generator->generate($parsed);

		$this->assertStringContainsString('Multi-table UPDATE', $code);
		$this->assertStringContainsString('transactional', $code);
	}

	/**
	 * Test optimization for SELECT without LIMIT
	 *
	 * @return void
	 */
	public function testOptimizationForMissingLimit(): void {
		$sql = 'SELECT * FROM users';
		$parsed = $this->parser->parse($sql);
		$this->generator->generate($parsed);

		$optimizations = $this->generator->getOptimizations();
		$found = false;
		foreach ($optimizations as $opt) {
			if (str_contains($opt, 'LIMIT') || str_contains($opt, 'pagination')) {
				$found = true;

				break;
			}
		}
		$this->assertTrue($found, 'Should suggest adding LIMIT');
	}

	/**
	 * Test optimization for JOINs
	 *
	 * @return void
	 */
	public function testOptimizationForJoins(): void {
		$sql = 'SELECT * FROM users LEFT JOIN posts ON posts.user_id = users.id';
		$parsed = $this->parser->parse($sql);
		$this->generator->generate($parsed);

		$optimizations = $this->generator->getOptimizations();
		$found = false;
		foreach ($optimizations as $opt) {
			if (str_contains($opt, 'JOIN') || str_contains($opt, 'contain')) {
				$found = true;

				break;
			}
		}
		$this->assertTrue($found, 'Should suggest using contain() for JOINs');
	}

	/**
	 * Test optimization for SELECT *
	 *
	 * @return void
	 */
	public function testOptimizationForSelectStar(): void {
		$sql = 'SELECT * FROM users LIMIT 10';
		$parsed = $this->parser->parse($sql);
		$this->generator->generate($parsed);

		$optimizations = $this->generator->getOptimizations();
		$found = false;
		foreach ($optimizations as $opt) {
			if (str_contains($opt, 'SELECT *') || str_contains($opt, 'needed fields')) {
				$found = true;

				break;
			}
		}
		$this->assertTrue($found, 'Should suggest avoiding SELECT *');
	}

	/**
	 * Test optimization for DISTINCT
	 *
	 * @return void
	 */
	public function testOptimizationForDistinct(): void {
		$sql = 'SELECT DISTINCT user_id FROM posts LIMIT 10';
		$parsed = $this->parser->parse($sql);
		$this->generator->generate($parsed);

		$optimizations = $this->generator->getOptimizations();
		$found = false;
		foreach ($optimizations as $opt) {
			if (str_contains($opt, 'DISTINCT')) {
				$found = true;

				break;
			}
		}
		$this->assertTrue($found, 'Should warn about DISTINCT usage');
	}

}
