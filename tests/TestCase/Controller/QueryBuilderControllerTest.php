<?php

namespace TestHelper\Test\TestCase\Controller;

use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * @uses \TestHelper\Controller\QueryBuilderController
 */
class QueryBuilderControllerTest extends TestCase {

	use IntegrationTestTrait;

	/**
	 * The empty form renders with no result and no error.
	 *
	 * @return void
	 */
	public function testIndexEmpty() {
		$this->disableErrorHandlerMiddleware();

		$this->get(['plugin' => 'TestHelper', 'controller' => 'QueryBuilder', 'action' => 'index']);

		$this->assertResponseCode(200);
		$this->assertSame('', $this->viewVariable('sqlQuery'));
		$this->assertNull($this->viewVariable('result'));
		$this->assertNull($this->viewVariable('error'));
	}

	/**
	 * SQL passed via the query string (the "Try It" links) is parsed and converted.
	 *
	 * @return void
	 */
	public function testIndexConvertsSqlFromQueryString() {
		$this->disableErrorHandlerMiddleware();

		$this->get([
			'plugin' => 'TestHelper',
			'controller' => 'QueryBuilder',
			'action' => 'index',
			'?' => ['sql' => 'SELECT id, title FROM posts'],
		]);

		$this->assertResponseCode(200);
		$result = $this->viewVariable('result');
		$this->assertIsArray($result);
		$this->assertArrayHasKey('code', $result);
		$this->assertArrayHasKey('parsed', $result);
		$this->assertNotEmpty($result['code']);
		$this->assertNull($this->viewVariable('error'));
	}

	/**
	 * A posted query is parsed using the chosen dialect and converted to builder code.
	 *
	 * @return void
	 */
	public function testIndexConvertsPostedSql() {
		$this->disableErrorHandlerMiddleware();

		$this->post(
			['plugin' => 'TestHelper', 'controller' => 'QueryBuilder', 'action' => 'index'],
			['sql_query' => 'SELECT id, title FROM posts', 'dialect' => 'mysql'],
		);

		$this->assertResponseCode(200);
		$result = $this->viewVariable('result');
		$this->assertIsArray($result);
		$this->assertNotEmpty($result['code']);
		$this->assertSame('mysql', $this->viewVariable('dialect'));
	}

	/**
	 * Unparseable SQL surfaces an error and a flash message instead of throwing.
	 *
	 * @return void
	 */
	public function testIndexInvalidSqlShowsError() {
		$this->enableRetainFlashMessages();
		$this->disableErrorHandlerMiddleware();

		$this->post(
			['plugin' => 'TestHelper', 'controller' => 'QueryBuilder', 'action' => 'index'],
			['sql_query' => 'not a valid query'],
		);

		$this->assertResponseCode(200);
		$this->assertNull($this->viewVariable('result'));
		$this->assertNotNull($this->viewVariable('error'));
		$this->assertFlashElement('flash/error');
	}

}
