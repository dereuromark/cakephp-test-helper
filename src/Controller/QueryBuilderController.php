<?php

namespace TestHelper\Controller;

use TestHelper\Query\QueryBuilderGenerator;
use TestHelper\Query\SqlParser;

class QueryBuilderController extends TestHelperAppController {

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$sqlQuery = '';
		$result = null;
		$error = null;
		$dialect = 'mysql';

		// Check for SQL in query string (from "Try It" links)
		if ($this->request->getQuery('sql')) {
			$sqlQuery = (string)$this->request->getQuery('sql');
		}

		if ($this->request->is('post')) {
			$sqlQuery = (string)$this->request->getData('sql_query');
			$dialect = (string)($this->request->getData('dialect') ?: 'mysql');
		}

		// Process SQL if provided
		if ($sqlQuery) {
			try {
				$parser = new SqlParser($dialect);
				$parsed = $parser->parse($sqlQuery);

				$generator = new QueryBuilderGenerator($dialect);
				$cakePhpCode = $generator->generate($parsed);

				$result = [
					'parsed' => $parsed,
					'code' => $cakePhpCode,
					'optimizations' => $generator->getOptimizations(),
				];
			} catch (\Exception $e) {
				$error = $e->getMessage();
				$this->Flash->error('Error parsing SQL: ' . $e->getMessage());
			}
		}

		$this->set(compact('sqlQuery', 'result', 'error', 'dialect'));
	}

}
