<?php

namespace TestHelper\Test\TestCase\Query;

use Cake\TestSuite\TestCase;
use TestHelper\Query\ConditionParser;

/**
 * ConditionParser Test Case
 */
class ConditionParserTest extends TestCase {

	/**
	 * @var \TestHelper\Query\ConditionParser
	 */
	protected ConditionParser $parser;

	/**
	 * setUp method
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->parser = new ConditionParser();
	}

	/**
	 * Test simple equality condition
	 *
	 * @return void
	 */
	public function testSimpleEquality(): void {
		$result = $this->parser->parse('id = 5');
		$expected = ['id' => 5];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test multiple AND conditions
	 *
	 * @return void
	 */
	public function testMultipleAndConditions(): void {
		$result = $this->parser->parse("active = 1 AND role = 'admin'");
		$expected = [
			'active' => 1,
			'role' => 'admin',
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test OR conditions
	 *
	 * @return void
	 */
	public function testOrConditions(): void {
		$result = $this->parser->parse("status = 'active' OR status = 'pending'");
		$expected = [
			'OR' => [
				['status' => 'active'],
				['status' => 'pending'],
			],
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test comparison operators
	 *
	 * @return void
	 */
	public function testComparisonOperators(): void {
		$result = $this->parser->parse('age > 18');
		$expected = ['age >' => 18];
		$this->assertSame($expected, $result);

		$result = $this->parser->parse('score >= 100');
		$expected = ['score >=' => 100];
		$this->assertSame($expected, $result);

		$result = $this->parser->parse("status != 'banned'");
		$expected = ['status !=' => 'banned'];
		$this->assertSame($expected, $result);

		$result = $this->parser->parse("status <> 'banned'");
		$expected = ['status !=' => 'banned'];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test LIKE condition
	 *
	 * @return void
	 */
	public function testLikeCondition(): void {
		$result = $this->parser->parse("name LIKE '%john%'");
		$expected = ['name LIKE' => '%john%'];
		$this->assertSame($expected, $result);

		$result = $this->parser->parse("name NOT LIKE '%admin%'");
		$expected = ['name NOT LIKE' => '%admin%'];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test IN condition
	 *
	 * @return void
	 */
	public function testInCondition(): void {
		$result = $this->parser->parse('status IN (1, 2, 3)');
		$expected = ['status IN' => [1, 2, 3]];
		$this->assertSame($expected, $result);

		$result = $this->parser->parse("role IN ('admin', 'editor')");
		$expected = ['role IN' => ['admin', 'editor']];
		$this->assertSame($expected, $result);

		$result = $this->parser->parse('status NOT IN (0, 9)');
		$expected = ['status NOT IN' => [0, 9]];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test IS NULL condition
	 *
	 * @return void
	 */
	public function testIsNullCondition(): void {
		$result = $this->parser->parse('deleted IS NULL');
		$expected = ['deleted IS' => null];
		$this->assertSame($expected, $result);

		$result = $this->parser->parse('banned_at IS NOT NULL');
		$expected = ['banned_at IS NOT' => null];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test BETWEEN condition
	 *
	 * @return void
	 */
	public function testBetweenCondition(): void {
		$result = $this->parser->parse("created BETWEEN '2023-01-01' AND '2023-12-31'");
		$expected = ['created BETWEEN' => ['2023-01-01', '2023-12-31']];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test complex OR/AND combination
	 *
	 * @return void
	 */
	public function testComplexOrAnd(): void {
		$result = $this->parser->parse("(status = 'active' OR status = 'pending') AND role = 'user'");
		$expected = [
			'OR' => [
				['status' => 'active'],
				['status' => 'pending'],
			],
			'role' => 'user',
		];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test empty condition
	 *
	 * @return void
	 */
	public function testEmptyCondition(): void {
		$result = $this->parser->parse('');
		$expected = [];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test field with table prefix
	 *
	 * @return void
	 */
	public function testFieldWithTablePrefix(): void {
		$result = $this->parser->parse('users.active = 1');
		$expected = ['users.active' => 1];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test boolean values
	 *
	 * @return void
	 */
	public function testBooleanValues(): void {
		$result = $this->parser->parse('active = TRUE');
		$expected = ['active' => true];
		$this->assertSame($expected, $result);

		$result = $this->parser->parse('deleted = FALSE');
		$expected = ['deleted' => false];
		$this->assertSame($expected, $result);
	}

	/**
	 * Test formatAsPhpArray
	 *
	 * @return void
	 */
	public function testFormatAsPhpArray(): void {
		$conditions = ['id' => 5, 'active' => 1];
		$result = $this->parser->formatAsPhpArray($conditions, 2);

		$this->assertStringContainsString("'id' => 5", $result);
		$this->assertStringContainsString("'active' => 1", $result);
	}

	/**
	 * Test formatAsPhpArray with OR
	 *
	 * @return void
	 */
	public function testFormatAsPhpArrayWithOr(): void {
		$conditions = [
			'OR' => [
				['status' => 'active'],
				['status' => 'pending'],
			],
		];
		$result = $this->parser->formatAsPhpArray($conditions, 2);

		$this->assertStringContainsString("'OR'", $result);
		$this->assertStringContainsString("'status' => 'active'", $result);
		$this->assertStringContainsString("'status' => 'pending'", $result);
	}

}
