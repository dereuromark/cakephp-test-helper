<?php

declare(strict_types=1);

namespace TestHelper\Test\TestCase\Query;

use Cake\TestSuite\TestCase;
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

		$this->assertStringContainsString('newEntity', $code);
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
		$this->assertStringContainsString('$query->delete()', $code);
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
