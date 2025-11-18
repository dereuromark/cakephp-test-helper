<?php

namespace TestHelper\Query;

use RuntimeException;

/**
 * SQL Parser
 *
 * Parses SQL queries into structured arrays for conversion to CakePHP Query Builder
 */
class SqlParser {

	/**
	 * Parse SQL query into structured array
	 *
     * @param string $sql SQL query string
     * @throws \RuntimeException When SQL cannot be parsed
     * @return array<string, mixed> Parsed query structure
	 */
	public function parse(string $sql): array {
		$sql = trim($sql);

		// Remove trailing semicolon if present
		$sql = rtrim($sql, ';');

		// Check for CTE (WITH clause) first
		if (preg_match('/^WITH\s+/i', $sql)) {
			return $this->parseCTE($sql);
		}

		// Check for UNION queries
		if ($this->isUnionQuery($sql)) {
			return $this->parseUnion($sql);
		}

		// Determine query type
		$type = $this->getQueryType($sql);

		return match ($type) {
			'SELECT' => $this->parseSelect($sql),
			'INSERT' => $this->parseInsert($sql),
			'UPDATE' => $this->parseUpdate($sql),
			'DELETE' => $this->parseDelete($sql),
			default => throw new RuntimeException('Unsupported query type: ' . $type),
		};
	}

	/**
	 * Get query type from SQL
	 *
	 * @param string $sql SQL query
	 * @return string Query type (SELECT, INSERT, UPDATE, DELETE)
	 */
	protected function getQueryType(string $sql): string {
		$sql = strtoupper(trim($sql));

		if (str_starts_with($sql, 'SELECT')) {
			return 'SELECT';
		}
		if (str_starts_with($sql, 'INSERT')) {
			return 'INSERT';
		}
		if (str_starts_with($sql, 'UPDATE')) {
			return 'UPDATE';
		}
		if (str_starts_with($sql, 'DELETE')) {
			return 'DELETE';
		}

		throw new RuntimeException('Unknown query type');
	}

	/**
	 * Parse SELECT query
	 *
	 * @param string $sql SELECT query
	 * @return array<string, mixed> Parsed structure
	 */
	protected function parseSelect(string $sql): array {
		$result = [
			'type' => 'SELECT',
			'distinct' => false,
			'fields' => [],
			'from' => null,
			'fromAlias' => null,
			'joins' => [],
			'where' => [],
			'groupBy' => [],
			'having' => [],
			'orderBy' => [],
			'limit' => null,
			'offset' => null,
			'hasOrmAliases' => false,
		];

		// Check for DISTINCT
		if (preg_match('/SELECT\s+DISTINCT\s+/i', $sql)) {
			$result['distinct'] = true;
		}

		// Extract SELECT fields
		if (preg_match('/SELECT\s+(?:DISTINCT\s+)?(.*?)\s+FROM/is', $sql, $matches)) {
			$result['fields'] = $this->parseSelectFields($matches[1]);

			// Check if any fields have ORM-style aliases
			foreach ($result['fields'] as $field) {
				if (is_array($field) && !empty($field['isOrmAlias'])) {
					$result['hasOrmAliases'] = true;

					break;
				}
			}
		}

		// Extract FROM table with optional alias
		// Use lookahead to avoid consuming JOIN keywords
		if (preg_match('/FROM\s+([^\s,]+)(?:\s+(?:AS\s+)?([^\s,]+?))?(?=\s+(?:WHERE|JOIN|LEFT\s+JOIN|RIGHT\s+JOIN|INNER\s+JOIN|FULL\s+(?:OUTER\s+)?JOIN|GROUP\s+BY|HAVING|ORDER\s+BY|LIMIT)|$)/i', $sql, $matches)) {
			$result['from'] = $this->parseTableName($matches[1]);
			if (isset($matches[2]) && trim($matches[2]) !== '') {
				$alias = trim($matches[2]);
				$result['fromAlias'] = $this->parseTableName($alias);
			}
		}

		// Extract JOINs
		$result['joins'] = $this->parseJoins($sql);

		// Extract WHERE clause
		if (preg_match('/WHERE\s+(.*?)(?:\s+GROUP\s+BY|\s+HAVING|\s+ORDER\s+BY|\s+LIMIT|$)/is', $sql, $matches)) {
			$result['where'] = $this->parseConditions($matches[1]);
		}

		// Extract GROUP BY
		if (preg_match('/GROUP\s+BY\s+(.*?)(?:\s+HAVING|\s+ORDER\s+BY|\s+LIMIT|$)/is', $sql, $matches)) {
			$result['groupBy'] = $this->parseGroupBy($matches[1]);
		}

		// Extract HAVING
		if (preg_match('/HAVING\s+(.*?)(?:\s+ORDER\s+BY|\s+LIMIT|$)/is', $sql, $matches)) {
			$result['having'] = $this->parseConditions($matches[1]);
		}

		// Extract ORDER BY
		if (preg_match('/ORDER\s+BY\s+(.*?)(?:\s+LIMIT|$)/is', $sql, $matches)) {
			$result['orderBy'] = $this->parseOrderBy($matches[1]);
		}

		// Extract LIMIT
		if (preg_match('/LIMIT\s+(\d+)(?:\s+OFFSET\s+(\d+))?/i', $sql, $matches)) {
			$result['limit'] = (int)$matches[1];
			if (isset($matches[2])) {
				$result['offset'] = (int)$matches[2];
			}
		}

		// Alternative OFFSET syntax (before LIMIT in some dialects)
		if (preg_match('/OFFSET\s+(\d+)/i', $sql, $matches)) {
			$result['offset'] = (int)$matches[1];
		}

		return $result;
	}

	/**
	 * Parse SELECT fields
	 *
	 * @param string $fieldsStr Fields string
	 * @return array<int, mixed> Parsed fields
	 */
	protected function parseSelectFields(string $fieldsStr): array {
		$fieldsStr = trim($fieldsStr);

		if ($fieldsStr === '*') {
			return ['*'];
		}

		$fields = [];
		$parts = $this->splitByComma($fieldsStr);

		foreach ($parts as $part) {
			$part = trim($part);

			// Check for alias (AS keyword or space-separated)
			if (preg_match('/^(.*?)\s+AS\s+([^\s]+)$/i', $part, $matches)) {
				$fieldExpr = trim($matches[1]);
				$alias = trim($matches[2], '`"\'');
				$isOrmAlias = $this->isOrmStyleAlias($alias);
				$fields[] = [
					'field' => $fieldExpr,
					'alias' => $alias,
					'type' => $this->detectFieldType($fieldExpr),
					'isOrmAlias' => $isOrmAlias,
				];
			} elseif (preg_match('/^(.*?)\s+([^\s\(]+)$/', $part, $matches) && !$this->isFunction($part)) {
				// Space-separated alias (but not for functions without AS)
				$fieldExpr = trim($matches[1]);
				$alias = trim($matches[2], '`"\'');
				$isOrmAlias = $this->isOrmStyleAlias($alias);
				$fields[] = [
					'field' => $fieldExpr,
					'alias' => $alias,
					'type' => $this->detectFieldType($fieldExpr),
					'isOrmAlias' => $isOrmAlias,
				];
			} else {
				$cleanField = trim($part, '`"\'');
				$fieldType = $this->detectFieldType($part);
				if ($fieldType !== 'column') {
					$fields[] = [
						'field' => $cleanField,
						'type' => $fieldType,
					];
				} else {
					$fields[] = $cleanField;
				}
			}
		}

		return $fields;
	}

	/**
	 * Check if string contains a SQL function
	 *
	 * @param string $str String to check
	 * @return bool True if contains function
	 */
	protected function isFunction(string $str): bool {
		return str_contains($str, '(') && str_contains($str, ')');
	}

	/**
	 * Check if alias follows ORM-style convention (TableName__field_name)
	 *
	 * @param string $alias Alias to check
	 * @return bool True if ORM-style alias
	 */
	protected function isOrmStyleAlias(string $alias): bool {
		// Pattern: PascalCase table name + __ + snake_case field name
		return (bool)preg_match('/^[A-Z][a-zA-Z0-9]*__[a-z][a-z0-9_]*$/', $alias);
	}

	/**
	 * Extract table name from ORM-style alias
	 *
	 * @param string $alias ORM-style alias (e.g., "Authors__id")
	 * @return string Table name (e.g., "Authors")
	 */
	protected function getTableFromOrmAlias(string $alias): string {
		$parts = explode('__', $alias);

		return $parts[0];
	}

	/**
	 * Extract field name from ORM-style alias
	 *
	 * @param string $alias ORM-style alias (e.g., "Authors__id")
	 * @return string Field name (e.g., "id")
	 */
	protected function getFieldFromOrmAlias(string $alias): string {
		$parts = explode('__', $alias);

		return $parts[1] ?? '';
	}

	/**
	 * Detect the type of field expression
	 *
	 * @param string $field Field expression
	 * @return string Field type (aggregate|string_func|date_func|case|math|column)
	 */
	protected function detectFieldType(string $field): string {
		$upperField = strtoupper(trim($field));

		// CASE expression
		if (str_starts_with($upperField, 'CASE')) {
			return 'case';
		}

		// Aggregate functions
		$aggregateFunctions = ['COUNT', 'SUM', 'AVG', 'MIN', 'MAX', 'GROUP_CONCAT'];
		foreach ($aggregateFunctions as $func) {
			if (preg_match('/^' . $func . '\s*\(/i', $upperField)) {
				return 'aggregate';
			}
		}

		// String functions
		$stringFunctions = [
			'CONCAT', 'CONCAT_WS', 'SUBSTRING', 'SUBSTR', 'TRIM', 'LTRIM', 'RTRIM',
			'UPPER', 'LOWER', 'REPLACE', 'COALESCE', 'IFNULL', 'NULLIF', 'LENGTH', 'CHAR_LENGTH',
		];
		foreach ($stringFunctions as $func) {
			if (preg_match('/^' . $func . '\s*\(/i', $upperField)) {
				return 'string_func';
			}
		}

		// Date functions
		$dateFunctions = [
			'NOW', 'CURDATE', 'CURTIME', 'DATE', 'TIME', 'YEAR', 'MONTH', 'DAY',
			'HOUR', 'MINUTE', 'SECOND', 'DATE_FORMAT', 'DATE_ADD', 'DATE_SUB', 'DATEDIFF',
			'TIMESTAMPDIFF', 'FROM_UNIXTIME', 'UNIX_TIMESTAMP',
		];
		foreach ($dateFunctions as $func) {
			if (preg_match('/^' . $func . '\s*\(/i', $upperField)) {
				return 'date_func';
			}
		}

		// Window functions
		$windowFunctions = [
			'ROW_NUMBER', 'RANK', 'DENSE_RANK', 'NTILE', 'LAG', 'LEAD',
			'FIRST_VALUE', 'LAST_VALUE', 'NTH_VALUE',
		];
		foreach ($windowFunctions as $func) {
			if (preg_match('/^' . $func . '\s*\(/i', $upperField)) {
				return 'window_func';
			}
		}

		// Check for OVER clause (window function indicator)
		if (preg_match('/\s+OVER\s*\(/i', $field)) {
			return 'window_func';
		}

		// Mathematical expression (contains +, -, *, /, %)
		if (preg_match('/[\+\-\*\/\%]/', $field) && !preg_match('/^[\'"].*[\'"]$/', $field)) {
			return 'math';
		}

		return 'column';
	}

	/**
	 * Parse table name, removing backticks and quotes
	 *
	 * @param string $tableName Raw table name
	 * @return string Clean table name
	 */
	protected function parseTableName(string $tableName): string {
		return trim($tableName, '`"\' ');
	}

	/**
	 * Parse JOINs from SQL
	 *
	 * @param string $sql SQL query
	 * @return array<array<string, mixed>> Parsed joins
	 */
	protected function parseJoins(string $sql): array {
		$joins = [];

		// Match individual JOINs directly in the full SQL
		// Pattern matches: (LEFT|RIGHT|INNER)? JOIN table (alias)? ON conditions
		preg_match_all(
			'/((?:INNER|LEFT|RIGHT|FULL(?:\s+OUTER)?)\s+)?JOIN\s+([^\s]+)(?:\s+(?:AS\s+)?([^\s]+))?\s+ON\s+(.+?)(?=\s+(?:(?:INNER|LEFT|RIGHT|FULL(?:\s+OUTER)?)\s+)?JOIN|\s+WHERE|\s+GROUP\s+BY|\s+HAVING|\s+ORDER\s+BY|\s+LIMIT|$)/is',
			$sql,
			$matches,
			PREG_SET_ORDER,
		);

		foreach ($matches as $match) {
			$joinType = trim($match[1]) ?: 'INNER';
			$table = $this->parseTableName($match[2]);
			$alias = null;
			if (trim($match[3]) !== '' && !str_starts_with(strtoupper(trim($match[3])), 'ON')) {
				$alias = $this->parseTableName($match[3]);
			}
			$conditions = trim($match[4]);

			$joins[] = [
				'type' => strtoupper(trim($joinType)),
				'table' => $table,
				'alias' => $alias,
				'conditions' => $conditions,
			];
		}

		return $joins;
	}

	/**
	 * Parse WHERE/HAVING conditions
	 *
	 * @param string $conditionsStr Conditions string
	 * @return array|string Conditions (can include subqueries)
	 */
	protected function parseConditions(string $conditionsStr): array|string {
		$conditionsStr = trim($conditionsStr);

		// Check for subqueries
		if ($this->containsSubquery($conditionsStr)) {
			return $this->parseConditionsWithSubqueries($conditionsStr);
		}

		return $conditionsStr;
	}

	/**
	 * Check if string contains a subquery
	 *
	 * @param string $str String to check
	 * @return bool True if contains SELECT subquery
	 */
	protected function containsSubquery(string $str): bool {
		// Look for SELECT keyword within parentheses
		return (bool)preg_match('/\(\s*SELECT\s+/i', $str);
	}

	/**
	 * Parse conditions that contain subqueries
	 *
	 * @param string $conditionsStr Conditions string
	 * @return array Parsed conditions with subqueries
	 */
	protected function parseConditionsWithSubqueries(string $conditionsStr): array {
		// Extract subqueries and replace with placeholders
		$subqueries = [];
		$placeholder = 0;
		$conditionsStr = preg_replace_callback(
			'/\(\s*(SELECT\s+.+?)\s*\)/is',
			function ($matches) use (&$subqueries, &$placeholder) {
				$subqueryPlaceholder = '__SUBQUERY_' . $placeholder . '__';
				$subqueries[$subqueryPlaceholder] = trim($matches[1]);
				$placeholder++;

				return $subqueryPlaceholder;
			},
			$conditionsStr,
		);

		return [
			'conditions' => $conditionsStr,
			'subqueries' => $subqueries,
		];
	}

	/**
	 * Parse GROUP BY clause
	 *
	 * @param string $groupByStr GROUP BY string
	 * @return array<string> Group by fields
	 */
	protected function parseGroupBy(string $groupByStr): array {
		$parts = $this->splitByComma($groupByStr);

		return array_map(fn ($part) => trim($part, '`"\' '), $parts);
	}

	/**
	 * Parse ORDER BY clause
	 *
	 * @param string $orderByStr ORDER BY string
	 * @return array<string, string> Order by fields with direction
	 */
	protected function parseOrderBy(string $orderByStr): array {
		$orderBy = [];
		$parts = $this->splitByComma($orderByStr);

		foreach ($parts as $part) {
			$part = trim($part);

			if (preg_match('/^(.+?)\s+(ASC|DESC)$/i', $part, $matches)) {
				$field = trim($matches[1], '`"\' ');
				$direction = strtoupper($matches[2]);
				$orderBy[$field] = $direction;
			} else {
				$field = trim($part, '`"\' ');
				$orderBy[$field] = 'ASC';
			}
		}

		return $orderBy;
	}

	/**
	 * Parse INSERT query
	 *
	 * @param string $sql INSERT query
	 * @return array<string, mixed> Parsed structure
	 */
	protected function parseInsert(string $sql): array {
		$result = [
			'type' => 'INSERT',
			'table' => null,
			'fields' => [],
			'values' => [],
		];

		// Extract table name
		if (preg_match('/INSERT\s+INTO\s+([^\s\(]+)/i', $sql, $matches)) {
			$result['table'] = $this->parseTableName($matches[1]);
		}

		// Extract field names
		if (preg_match('/\(([^)]+)\)\s+VALUES/i', $sql, $matches)) {
			$result['fields'] = array_map(
				fn ($field) => trim($field, '`"\' '),
				$this->splitByComma($matches[1]),
			);
		}

		// Extract values - support multiple rows
		if (preg_match('/VALUES\s+(.+)$/is', $sql, $matches)) {
			$valuesStr = $matches[1];
			// Extract all parenthesized value sets
			preg_match_all('/\(([^)]+)\)/i', $valuesStr, $valueSets);
			if (!empty($valueSets[1])) {
				foreach ($valueSets[1] as $valueSet) {
					$result['values'][] = $this->splitByComma($valueSet);
				}
			}
		}

		return $result;
	}

	/**
	 * Parse UPDATE query
	 *
	 * @param string $sql UPDATE query
	 * @return array<string, mixed> Parsed structure
	 */
	protected function parseUpdate(string $sql): array {
		$result = [
			'type' => 'UPDATE',
			'table' => null,
			'tableAlias' => null,
			'joins' => [],
			'set' => [],
			'where' => [],
			'isMultiTable' => false,
		];

		// Check for multi-table UPDATE with JOINs
		$hasJoin = preg_match('/UPDATE\s+.+?\s+(?:INNER\s+)?(?:LEFT\s+)?(?:RIGHT\s+)?JOIN/i', $sql);
		$result['isMultiTable'] = $hasJoin;

		// Extract table name and alias
		if (preg_match('/UPDATE\s+([^\s]+)(?:\s+(?:AS\s+)?([^\s]+))?/i', $sql, $matches)) {
			$result['table'] = $this->parseTableName($matches[1]);
			if (isset($matches[2]) && !preg_match('/^(INNER|LEFT|RIGHT|JOIN|SET)/i', $matches[2])) {
				$result['tableAlias'] = trim($matches[2]);
			}
		}

		// Extract JOINs if present
		if ($hasJoin) {
			$result['joins'] = $this->parseJoins($sql);
		}

		// Extract SET clause
		if (preg_match('/SET\s+(.*?)(?:\s+WHERE|$)/is', $sql, $matches)) {
			$setParts = $this->splitByComma($matches[1]);
			foreach ($setParts as $setPart) {
				if (preg_match('/^([^=]+)=(.+)$/', trim($setPart), $setMatch)) {
					$field = trim($setMatch[1], '`"\' ');
					$value = trim($setMatch[2]);
					$result['set'][$field] = $value;
				}
			}
		}

		// Extract WHERE clause
		if (preg_match('/WHERE\s+(.+)$/is', $sql, $matches)) {
			$result['where'] = $this->parseConditions($matches[1]);
		}

		return $result;
	}

	/**
	 * Parse DELETE query
	 *
	 * @param string $sql DELETE query
	 * @return array<string, mixed> Parsed structure
	 */
	protected function parseDelete(string $sql): array {
		$result = [
			'type' => 'DELETE',
			'from' => null,
			'where' => [],
		];

		// Extract table name
		if (preg_match('/DELETE\s+FROM\s+([^\s]+)/i', $sql, $matches)) {
			$result['from'] = $this->parseTableName($matches[1]);
		}

		// Extract WHERE clause
		if (preg_match('/WHERE\s+(.+)$/is', $sql, $matches)) {
			$result['where'] = $this->parseConditions($matches[1]);
		}

		return $result;
	}

	/**
	 * Check if query is a UNION query
	 *
	 * @param string $sql SQL query
	 * @return bool True if UNION query
	 */
	protected function isUnionQuery(string $sql): bool {
		return (bool)preg_match('/\bUNION(?:\s+ALL)?\s+SELECT\b/i', $sql);
	}

	/**
	 * Parse UNION query
	 *
	 * @param string $sql UNION query
	 * @return array<string, mixed> Parsed structure
	 */
	protected function parseUnion(string $sql): array {
		$result = [
			'type' => 'UNION',
			'queries' => [],
			'unionAll' => false,
		];

		// Split by UNION or UNION ALL
		$parts = preg_split('/\bUNION(?:\s+ALL)?\s+/i', $sql, -1, PREG_SPLIT_DELIM_CAPTURE);
		if ($parts === false) {
			return $result;
		}

		// Check if it's UNION ALL
		$result['unionAll'] = (bool)preg_match('/\bUNION\s+ALL\b/i', $sql);

		// Parse each SELECT query
		foreach ($parts as $part) {
			$part = trim($part);
			if (str_starts_with(strtoupper($part), 'SELECT')) {
				$result['queries'][] = $this->parseSelect($part);
			}
		}

		return $result;
	}

	/**
	 * Split string by comma, respecting parentheses and quotes
	 *
	 * @param string $str String to split
	 * @return array<string> Split parts
	 */
	protected function splitByComma(string $str): array {
		$parts = [];
		$current = '';
		$depth = 0;
		$inQuote = false;
		$quoteChar = '';
		$length = strlen($str);

		for ($i = 0; $i < $length; $i++) {
			$char = $str[$i];

			if (($char === '"' || $char === "'" || $char === '`') && ($i === 0 || $str[$i - 1] !== '\\')) {
				if (!$inQuote) {
					$inQuote = true;
					$quoteChar = $char;
				} elseif ($char === $quoteChar) {
					$inQuote = false;
					$quoteChar = '';
				}
			}

			if (!$inQuote) {
				if ($char === '(') {
					$depth++;
				} elseif ($char === ')') {
					$depth--;
				}
			}

			if ($char === ',' && $depth === 0 && !$inQuote) {
				$parts[] = trim($current);
				$current = '';
			} else {
				$current .= $char;
			}
		}

		if ($current !== '') {
			$parts[] = trim($current);
		}

		return $parts;
	}

	/**
	 * Parse CTE (Common Table Expression) query
	 *
	 * @param string $sql SQL with WITH clause
	 * @return array<string, mixed> Parsed structure
	 */
	protected function parseCTE(string $sql): array {
		$result = [
			'type' => 'CTE',
			'ctes' => [],
			'mainQuery' => null,
		];

		// Extract the main query after all CTEs
		// Pattern: WITH cte1 AS (...), cte2 AS (...) SELECT/INSERT/UPDATE/DELETE ...
		if (preg_match('/^WITH\s+(.+?)(?=\s+(?:SELECT|INSERT|UPDATE|DELETE)\s+)/is', $sql, $matches)) {
			$cteSection = $matches[1];

			// Extract main query
			$mainQuerySql = (string)preg_replace('/^WITH\s+.+?(?=\s+(?:SELECT|INSERT|UPDATE|DELETE)\s+)/is', '', $sql);
			$result['mainQuery'] = $this->parse(trim($mainQuerySql));

			// Store raw CTE section
			// Full CTE parsing is complex due to nested parentheses and multiple CTEs
			$result['ctes'][] = [
				'raw' => trim($cteSection),
			];
		}

		return $result;
	}

}
