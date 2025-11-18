<?php

namespace TestHelper\Test\TestCase\Query;

use Cake\TestSuite\TestCase;
use TestHelper\Query\SqlParser;

/**
 * SqlParser Test Case
 */
class SqlParserTest extends TestCase {

	/**
	 * @var \TestHelper\Query\SqlParser
	 */
	protected SqlParser $parser;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->parser = new SqlParser();
	}

	/**
	 * Test simple SELECT parsing
	 *
	 * @return void
	 */
	public function testSimpleSelect(): void {
		$sql = 'SELECT * FROM users WHERE active = 1';
		$result = $this->parser->parse($sql);

		$this->assertSame('SELECT', $result['type']);
		$this->assertSame(['*'], $result['fields']);
		$this->assertSame('users', $result['from']);
		$this->assertSame('active = 1', $result['where']);
	}

	/**
	 * Test DISTINCT SELECT
	 *
	 * @return void
	 */
	public function testDistinctSelect(): void {
		$sql = 'SELECT DISTINCT user_id FROM posts';
		$result = $this->parser->parse($sql);

		$this->assertTrue($result['distinct']);
		$this->assertSame(['user_id'], $result['fields']);
	}

	/**
	 * Test SELECT with table alias
	 *
	 * @return void
	 */
	public function testSelectWithTableAlias(): void {
		$sql = 'SELECT u.id, u.name FROM users u WHERE u.active = 1';
		$result = $this->parser->parse($sql);

		$this->assertSame('users', $result['from']);
		$this->assertSame('u', $result['fromAlias']);
		$this->assertSame(['u.id', 'u.name'], $result['fields']);
	}

	/**
	 * Test SELECT with field aliases
	 *
	 * @return void
	 */
	public function testSelectWithFieldAliases(): void {
		$sql = 'SELECT id, name AS username, COUNT(*) AS total FROM users';
		$result = $this->parser->parse($sql);

		$this->assertSame('id', $result['fields'][0]);
		$this->assertSame('username', $result['fields'][1]['alias']);
		$this->assertSame('name', $result['fields'][1]['field']);
		$this->assertSame('total', $result['fields'][2]['alias']);
	}

	/**
	 * Test SELECT with JOIN
	 *
	 * @return void
	 */
	public function testSelectWithJoin(): void {
		$sql = 'SELECT * FROM users LEFT JOIN posts ON posts.user_id = users.id';
		$result = $this->parser->parse($sql);

		$this->assertNotEmpty($result['joins']);
		$this->assertSame('LEFT', $result['joins'][0]['type']);
		$this->assertSame('posts', $result['joins'][0]['table']);
		$this->assertSame('posts.user_id = users.id', $result['joins'][0]['conditions']);
	}

	/**
	 * Test SELECT with JOIN and alias
	 *
	 * @return void
	 */
	public function testSelectWithJoinAndAlias(): void {
		$sql = 'SELECT * FROM users u LEFT JOIN posts p ON p.user_id = u.id';
		$result = $this->parser->parse($sql);

		$this->assertSame('p', $result['joins'][0]['alias']);
		$this->assertSame('posts', $result['joins'][0]['table']);
	}

	/**
	 * Test SELECT with multiple JOINs
	 *
	 * @return void
	 */
	public function testSelectWithMultipleJoins(): void {
		$sql = 'SELECT * FROM users u LEFT JOIN posts p ON p.user_id = u.id INNER JOIN comments c ON c.post_id = p.id';
		$result = $this->parser->parse($sql);

		$this->assertCount(2, $result['joins']);
		$this->assertSame('LEFT', $result['joins'][0]['type']);
		$this->assertSame('INNER', $result['joins'][1]['type']);
		$this->assertSame('comments', $result['joins'][1]['table']);
	}

	/**
	 * Test SELECT with GROUP BY
	 *
	 * @return void
	 */
	public function testSelectWithGroupBy(): void {
		$sql = 'SELECT user_id, COUNT(*) FROM posts GROUP BY user_id';
		$result = $this->parser->parse($sql);

		$this->assertSame(['user_id'], $result['groupBy']);
	}

	/**
	 * Test SELECT with HAVING
	 *
	 * @return void
	 */
	public function testSelectWithHaving(): void {
		$sql = 'SELECT user_id, COUNT(*) FROM posts GROUP BY user_id HAVING COUNT(*) > 10';
		$result = $this->parser->parse($sql);

		$this->assertSame('COUNT(*) > 10', $result['having']);
	}

	/**
	 * Test SELECT with ORDER BY
	 *
	 * @return void
	 */
	public function testSelectWithOrderBy(): void {
		$sql = 'SELECT * FROM users ORDER BY created DESC, name ASC';
		$result = $this->parser->parse($sql);

		$this->assertSame(['created' => 'DESC', 'name' => 'ASC'], $result['orderBy']);
	}

	/**
	 * Test SELECT with LIMIT
	 *
	 * @return void
	 */
	public function testSelectWithLimit(): void {
		$sql = 'SELECT * FROM users LIMIT 10';
		$result = $this->parser->parse($sql);

		$this->assertSame(10, $result['limit']);
	}

	/**
	 * Test SELECT with LIMIT and OFFSET
	 *
	 * @return void
	 */
	public function testSelectWithLimitAndOffset(): void {
		$sql = 'SELECT * FROM users LIMIT 10 OFFSET 20';
		$result = $this->parser->parse($sql);

		$this->assertSame(10, $result['limit']);
		$this->assertSame(20, $result['offset']);
	}

	/**
	 * Test INSERT parsing
	 *
	 * @return void
	 */
	public function testInsertParsing(): void {
		$sql = "INSERT INTO users (username, email) VALUES ('john', 'john@example.com')";
		$result = $this->parser->parse($sql);

		$this->assertSame('INSERT', $result['type']);
		$this->assertSame('users', $result['table']);
		$this->assertSame(['username', 'email'], $result['fields']);
		$this->assertCount(1, $result['values']);
		$this->assertContains("'john'", $result['values'][0]);
	}

	/**
	 * Test UPDATE parsing
	 *
	 * @return void
	 */
	public function testUpdateParsing(): void {
		$sql = 'UPDATE users SET active = 0 WHERE id = 5';
		$result = $this->parser->parse($sql);

		$this->assertSame('UPDATE', $result['type']);
		$this->assertSame('users', $result['table']);
		$this->assertSame('0', $result['set']['active']);
		$this->assertSame('id = 5', $result['where']);
	}

	/**
	 * Test DELETE parsing
	 *
	 * @return void
	 */
	public function testDeleteParsing(): void {
		$sql = 'DELETE FROM users WHERE active = 0';
		$result = $this->parser->parse($sql);

		$this->assertSame('DELETE', $result['type']);
		$this->assertSame('users', $result['from']);
		$this->assertSame('active = 0', $result['where']);
	}

	/**
	 * Test complex SELECT query
	 *
	 * @return void
	 */
	public function testComplexSelectQuery(): void {
		$sql = "SELECT DISTINCT u.id, u.name, COUNT(p.id) AS post_count
				FROM users u
				LEFT JOIN posts p ON p.user_id = u.id
				WHERE u.active = 1
				GROUP BY u.id
				HAVING COUNT(p.id) > 5
				ORDER BY post_count DESC
				LIMIT 20";
		$result = $this->parser->parse($sql);

		$this->assertTrue($result['distinct']);
		$this->assertSame('users', $result['from']);
		$this->assertSame('u', $result['fromAlias']);
		$this->assertNotEmpty($result['joins']);
		$this->assertSame(['u.id'], $result['groupBy']);
		$this->assertSame(20, $result['limit']);
	}

	/**
	 * Test UNION query parsing
	 *
	 * @return void
	 */
	public function testUnionQuery(): void {
		$sql = 'SELECT id, name FROM users UNION SELECT id, name FROM admins';
		$result = $this->parser->parse($sql);

		$this->assertSame('UNION', $result['type']);
		$this->assertFalse($result['unionAll']);
		$this->assertCount(2, $result['queries']);
	}

	/**
	 * Test UNION ALL query parsing
	 *
	 * @return void
	 */
	public function testUnionAllQuery(): void {
		$sql = 'SELECT id FROM users UNION ALL SELECT id FROM deleted_users';
		$result = $this->parser->parse($sql);

		$this->assertSame('UNION', $result['type']);
		$this->assertTrue($result['unionAll']);
		$this->assertCount(2, $result['queries']);
	}

	/**
	 * Test bulk INSERT parsing
	 *
	 * @return void
	 */
	public function testBulkInsertParsing(): void {
		$sql = "INSERT INTO users (name, email) VALUES ('Alice', 'alice@example.com'), ('Bob', 'bob@example.com')";
		$result = $this->parser->parse($sql);

		$this->assertSame('INSERT', $result['type']);
		$this->assertSame('users', $result['table']);
		$this->assertCount(2, $result['values']);
		$this->assertSame(['name', 'email'], $result['fields']);
	}

	/**
	 * Test complex field expressions with aggregates
	 *
	 * @return void
	 */
	public function testSelectWithAggregates(): void {
		$sql = 'SELECT user_id, COUNT(*) AS total FROM posts GROUP BY user_id';
		$result = $this->parser->parse($sql);

		$this->assertSame('SELECT', $result['type']);
		$this->assertIsArray($result['fields']);
		$this->assertCount(2, $result['fields']);
	}

	/**
	 * Test SELECT with mathematical expression
	 *
	 * @return void
	 */
	public function testSelectWithMathExpression(): void {
		$sql = 'SELECT price * quantity AS total FROM line_items';
		$result = $this->parser->parse($sql);

		$this->assertSame('SELECT', $result['type']);
		$this->assertIsArray($result['fields']);
	}

	/**
	 * Test SELECT with ORM-style aliases
	 *
	 * @return void
	 */
	public function testSelectWithOrmStyleAliases(): void {
		$sql = "SELECT
			authors.id AS Authors__id,
			authors.name AS Authors__name,
			articles.id AS Articles__id,
			articles.title AS Articles__title
		FROM authors AS Authors
		LEFT JOIN articles AS Articles ON articles.author_id = authors.id";

		$result = $this->parser->parse($sql);

		$this->assertSame('SELECT', $result['type']);
		$this->assertTrue($result['hasOrmAliases']);
		$this->assertCount(4, $result['fields']);

		// Check that ORM aliases are flagged
		$this->assertTrue($result['fields'][0]['isOrmAlias']);
		$this->assertSame('Authors__id', $result['fields'][0]['alias']);
		$this->assertTrue($result['fields'][1]['isOrmAlias']);
		$this->assertSame('Authors__name', $result['fields'][1]['alias']);
		$this->assertTrue($result['fields'][2]['isOrmAlias']);
		$this->assertSame('Articles__id', $result['fields'][2]['alias']);
		$this->assertTrue($result['fields'][3]['isOrmAlias']);
		$this->assertSame('Articles__title', $result['fields'][3]['alias']);
	}

	/**
	 * Test SELECT without ORM-style aliases
	 *
	 * @return void
	 */
	public function testSelectWithoutOrmStyleAliases(): void {
		$sql = 'SELECT id, name AS username, email FROM users';
		$result = $this->parser->parse($sql);

		$this->assertSame('SELECT', $result['type']);
		$this->assertFalse($result['hasOrmAliases']);

		// Check that normal aliases are not flagged as ORM aliases
		$this->assertFalse($result['fields'][1]['isOrmAlias']);
		$this->assertSame('username', $result['fields'][1]['alias']);
	}

	/**
	 * Test mixed ORM and non-ORM aliases
	 *
	 * @return void
	 */
	public function testSelectWithMixedAliases(): void {
		$sql = 'SELECT users.id AS Users__id, name AS username, email FROM users';
		$result = $this->parser->parse($sql);

		$this->assertSame('SELECT', $result['type']);
		$this->assertTrue($result['hasOrmAliases']);

		// First field has ORM alias
		$this->assertTrue($result['fields'][0]['isOrmAlias']);
		// Second field has normal alias
		$this->assertFalse($result['fields'][1]['isOrmAlias']);
	}

	/**
	 * Test ORM aliases with JOINs and WHERE
	 *
	 * @return void
	 */
	public function testSelectWithOrmAliasesAndJoins(): void {
		$sql = "SELECT
			authors.id AS Authors__id,
			articles.title AS Articles__title
		FROM authors AS Authors
		LEFT JOIN articles AS Articles ON articles.author_id = authors.id
		WHERE authors.country = 'US'";

		$result = $this->parser->parse($sql);

		$this->assertSame('SELECT', $result['type']);
		$this->assertTrue($result['hasOrmAliases']);
		$this->assertSame('Authors', $result['fromAlias']);
		$this->assertCount(1, $result['joins']);
		$this->assertSame('Articles', $result['joins'][0]['alias']);
	}

}
