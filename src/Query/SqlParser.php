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
		];

		// Check for DISTINCT
		if (preg_match('/SELECT\s+DISTINCT\s+/i', $sql)) {
			$result['distinct'] = true;
		}

		// Extract SELECT fields
		if (preg_match('/SELECT\s+(?:DISTINCT\s+)?(.*?)\s+FROM/is', $sql, $matches)) {
			$result['fields'] = $this->parseSelectFields($matches[1]);
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
				$fields[] = [
					'field' => trim($matches[1]),
					'alias' => trim($matches[2], '`"\''),
				];
			} elseif (preg_match('/^(.*?)\s+([^\s\(]+)$/', $part, $matches) && !$this->isFunction($part)) {
				// Space-separated alias (but not for functions without AS)
				$fields[] = [
					'field' => trim($matches[1]),
					'alias' => trim($matches[2], '`"\''),
				];
			} else {
				$fields[] = trim($part, '`"\'');
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
	 * @return string Conditions (simplified for now)
	 */
	protected function parseConditions(string $conditionsStr): string {
		return trim($conditionsStr);
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

		// Extract values
		if (preg_match('/VALUES\s+\(([^)]+)\)/i', $sql, $matches)) {
			$result['values'] = $this->splitByComma($matches[1]);
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
			'set' => [],
			'where' => [],
		];

		// Extract table name
		if (preg_match('/UPDATE\s+([^\s]+)/i', $sql, $matches)) {
			$result['table'] = $this->parseTableName($matches[1]);
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

}
