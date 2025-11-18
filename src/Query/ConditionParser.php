<?php

namespace TestHelper\Query;

/**
 * SQL Condition Parser
 *
 * Parses SQL WHERE/HAVING conditions into CakePHP Query Builder format
 */
class ConditionParser {

	/**
	 * Parse SQL conditions into CakePHP array format
	 *
	 * @param string $conditions SQL conditions string
	 * @return array<string, mixed>|string Parsed conditions array or string for complex cases
	 */
	public function parse(string $conditions): array|string {
		$conditions = trim($conditions);

		if ($conditions === '') {
			return [];
		}

		// Try to parse the conditions
		try {
			return $this->parseExpression($conditions);
		} catch (\Exception $e) {
			// If parsing fails, return as TODO comment
			return $conditions;
		}
	}

	/**
	 * Parse a full expression (handles AND/OR at top level)
	 *
	 * @param string $expression Expression to parse
	 * @return array<string, mixed> Parsed expression
	 */
	protected function parseExpression(string $expression): array {
		$expression = trim($expression);

		// Split by OR first (lower precedence)
		$orParts = $this->splitByLogicalOperator($expression, 'OR');
		if (count($orParts) > 1) {
			$conditions = [];
			foreach ($orParts as $part) {
				$parsed = $this->parseExpression($part);
				if (!empty($parsed)) {
					$conditions[] = $parsed;
				}
			}

			return ['OR' => $conditions];
		}

		// Split by AND
		$andParts = $this->splitByLogicalOperator($expression, 'AND');
		if (count($andParts) > 1) {
			$conditions = [];
			foreach ($andParts as $part) {
				$parsed = $this->parseExpression($part);
				$conditions = array_merge($conditions, $parsed);
			}

			return $conditions;
		}

		// Remove outer parentheses if they wrap the entire expression
		if ($this->hasOuterParentheses($expression)) {
			$expression = trim(substr($expression, 1, -1));

			return $this->parseExpression($expression);
		}

		// Parse single condition
		return $this->parseCondition($expression);
	}

	/**
	 * Split expression by logical operator (AND/OR) respecting parentheses
	 *
	 * @param string $expression Expression to split
	 * @param string $operator Operator (AND or OR)
	 * @return array<string> Split parts
	 */
	protected function splitByLogicalOperator(string $expression, string $operator): array {
		$parts = [];
		$current = '';
		$depth = 0;
		$length = strlen($expression);
		$operatorLength = strlen($operator);

		for ($i = 0; $i < $length; $i++) {
			$char = $expression[$i];

			if ($char === '(') {
				$depth++;
				$current .= $char;
			} elseif ($char === ')') {
				$depth--;
				$current .= $char;
			} elseif ($depth === 0) {
				// Check if we're at the operator
				$remaining = substr($expression, $i);
				$pattern = '/^' . $operator . '\s+/i';
				if (preg_match($pattern, $remaining, $matches)) {
					// Check if this is part of a BETWEEN clause
					if ($operator === 'AND') {
						// Check if current part contains BETWEEN
						if (preg_match('/\bBETWEEN\b/i', $current)) {
							// This is BETWEEN...AND, not a logical AND
							$current .= $char;

							continue;
						}
					}
					// Found operator at depth 0
					$parts[] = trim($current);
					$current = '';
					$i += strlen($matches[0]) - 1; // Skip the operator
				} else {
					$current .= $char;
				}
			} else {
				$current .= $char;
			}
		}

		if ($current !== '') {
			$parts[] = trim($current);
		}

		// If no split occurred, return the original expression
		return count($parts) > 1 ? $parts : [$expression];
	}

	/**
	 * Check if expression has outer parentheses wrapping the entire thing
	 *
	 * @param string $expression Expression to check
	 * @return bool True if has outer parentheses
	 */
	protected function hasOuterParentheses(string $expression): bool {
		$expression = trim($expression);
		if (!str_starts_with($expression, '(') || !str_ends_with($expression, ')')) {
			return false;
		}

		// Check if the closing paren matches the opening one
		$depth = 0;
		$length = strlen($expression);
		for ($i = 0; $i < $length; $i++) {
			if ($expression[$i] === '(') {
				$depth++;
			} elseif ($expression[$i] === ')') {
				$depth--;
				if ($depth === 0 && $i < $length - 1) {
					// Closed before the end, so outer parens don't wrap everything
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Parse a single condition (e.g., "field = value")
	 *
	 * @param string $condition Single condition
	 * @return array<string, mixed> Parsed condition
	 */
	protected function parseCondition(string $condition): array {
		$condition = trim($condition);

		// Handle IS NULL / IS NOT NULL
		if (preg_match('/^(.+?)\s+IS\s+NOT\s+NULL$/i', $condition, $matches)) {
			$field = $this->cleanFieldName($matches[1]);

			return [$field . ' IS NOT' => null];
		}
		if (preg_match('/^(.+?)\s+IS\s+NULL$/i', $condition, $matches)) {
			$field = $this->cleanFieldName($matches[1]);

			return [$field . ' IS' => null];
		}

		// Handle BETWEEN
		if (preg_match('/^(.+?)\s+BETWEEN\s+(.+?)\s+AND\s+(.+)$/i', $condition, $matches)) {
			$field = $this->cleanFieldName($matches[1]);
			$value1 = $this->parseValue($matches[2]);
			$value2 = $this->parseValue($matches[3]);

			return [$field . ' BETWEEN' => [$value1, $value2]];
		}

		// Handle IN / NOT IN
		if (preg_match('/^(.+?)\s+(NOT\s+)?IN\s*\((.+?)\)$/i', $condition, $matches)) {
			$field = $this->cleanFieldName($matches[1]);
			$operator = trim($matches[2]) ? 'NOT IN' : 'IN';
			$values = $this->parseInValues($matches[3]);

			return [$field . ' ' . $operator => $values];
		}

		// Handle LIKE / NOT LIKE
		if (preg_match('/^(.+?)\s+(NOT\s+)?LIKE\s+(.+)$/i', $condition, $matches)) {
			$field = $this->cleanFieldName($matches[1]);
			$operator = trim($matches[2]) ? 'NOT LIKE' : 'LIKE';
			$value = $this->parseValue($matches[3]);

			return [$field . ' ' . $operator => $value];
		}

		// Handle EXISTS / NOT EXISTS
		if (preg_match('/^(NOT\s+)?EXISTS\s*\((.+)\)$/is', $condition, $matches)) {
			$operator = trim($matches[1]) ? 'NOT EXISTS' : 'EXISTS';
			$subquery = trim($matches[2]);

			return [$operator => $subquery]; // TODO: Parse subquery
		}

		// Handle comparison operators: !=, <>, >=, <=, >, <, =
		$operators = ['!=', '<>', '>=', '<=', '>', '<', '='];
		foreach ($operators as $op) {
			$escapedOp = preg_quote($op, '/');
			if (preg_match('/^(.+?)\s*' . $escapedOp . '\s*(.+)$/i', $condition, $matches)) {
				$field = $this->cleanFieldName($matches[1]);
				$value = $this->parseValue($matches[2]);

				// Map <> to !=
				if ($op === '<>') {
					$op = '!=';
				}

				// For equality, use just the field name
				if ($op === '=') {
					return [$field => $value];
				}

				return [$field . ' ' . $op => $value];
			}
		}

		// If we can't parse it, return it as a string (will be shown as TODO)
		return ['_raw' => $condition];
	}

	/**
	 * Clean field name (remove backticks, quotes, trim)
	 *
	 * @param string $field Field name
	 * @return string Clean field name
	 */
	protected function cleanFieldName(string $field): string {
		return trim($field, '`"\' ');
	}

	/**
	 * Parse a value (string, number, NULL, function call, etc.)
	 *
	 * @param string $value Value string
	 * @return mixed Parsed value
	 */
	protected function parseValue(string $value): mixed {
		$value = trim($value);

		// NULL
		if (strtoupper($value) === 'NULL') {
			return null;
		}

		// Boolean
		if (strtoupper($value) === 'TRUE') {
			return true;
		}
		if (strtoupper($value) === 'FALSE') {
			return false;
		}

		// String (quoted)
		if ((str_starts_with($value, "'") && str_ends_with($value, "'")) ||
			(str_starts_with($value, '"') && str_ends_with($value, '"'))) {
			return substr($value, 1, -1);
		}

		// Number (integer or float)
		if (is_numeric($value)) {
			return str_contains($value, '.') ? (float)$value : (int)$value;
		}

		// Function call (e.g., NOW(), CURRENT_DATE) or field reference
		// Return as-is for both function calls and field references
		return $value;
	}

	/**
	 * Parse values in an IN clause
	 *
	 * @param string $valuesStr Values string (e.g., "1, 2, 3" or "'a', 'b', 'c'")
	 * @return array Parsed values
	 */
	protected function parseInValues(string $valuesStr): array {
		$values = [];
		$parts = explode(',', $valuesStr);

		foreach ($parts as $part) {
			$values[] = $this->parseValue(trim($part));
		}

		return $values;
	}

	/**
	 * Format parsed conditions as PHP array code
	 *
	 * @param array<string, mixed>|string $conditions Parsed conditions
	 * @param int $indent Indentation level
	 * @return string Formatted PHP array code
	 */
	public function formatAsPhpArray(array|string $conditions, int $indent = 2): string {
		if (is_string($conditions)) {
			// Return as TODO comment
			return "\n" . str_repeat(' ', $indent * 4) . "// TODO: Convert these conditions to CakePHP format:\n"
				. str_repeat(' ', $indent * 4) . '// ' . $conditions . "\n"
				. str_repeat(' ', $indent * 4) . "// Example: 'Users.id' => \$id, 'Users.active' => true\n"
				. str_repeat(' ', $indent * 4 - 4);
		}

		if (empty($conditions)) {
			return '';
		}

		return $this->formatArray($conditions, $indent);
	}

	/**
	 * Format array recursively
	 *
	 * @param array<int|string, mixed> $array Array to format
	 * @param int $indent Indentation level
	 * @return string Formatted array
	 */
	protected function formatArray(array $array, int $indent = 2): string {
		$lines = [];
		$baseIndent = str_repeat(' ', $indent * 4);

		foreach ($array as $key => $value) {
			if ($key === '_raw') {
				// This is a raw condition we couldn't parse
				return "\n" . $baseIndent . "// TODO: Convert these conditions to CakePHP format:\n"
					. $baseIndent . '// ' . $value . "\n"
					. $baseIndent . "// Example: 'Users.id' => \$id, 'Users.active' => true\n"
					. str_repeat(' ', ($indent - 1) * 4);
			}

			if (is_int($key)) {
				// Numeric key (for OR conditions)
				if (is_array($value)) {
					$lines[] = $this->formatArray($value, $indent + 1);
				} else {
					$lines[] = $this->formatValue($value);
				}
			} else {
				// String key
				$formattedKey = "'" . $key . "'";
				if (is_array($value)) {
					if ($key === 'OR') {
						// Special handling for OR conditions
						$orLines = [];
						foreach ($value as $orCondition) {
							if (is_array($orCondition)) {
								$condParts = [];
								foreach ($orCondition as $k => $v) {
									$condParts[] = "'" . $k . "' => " . $this->formatValue($v);
								}
								$orLines[] = '[' . implode(', ', $condParts) . ']';
							}
						}
						$lines[] = $formattedKey . ' => [' . implode(', ', $orLines) . ']';
					} else {
						$lines[] = $formattedKey . ' => ' . $this->formatArrayInline($value);
					}
				} else {
					$lines[] = $formattedKey . ' => ' . $this->formatValue($value);
				}
			}
		}

		return "\n" . $baseIndent . implode(",\n" . $baseIndent, $lines) . ",\n" . str_repeat(' ', ($indent - 1) * 4);
	}

	/**
	 * Format array inline (single line)
	 *
	 * @param array $array Array to format
	 * @return string Formatted inline array
	 */
	protected function formatArrayInline(array $array): string {
		$items = [];
		foreach ($array as $value) {
			$items[] = $this->formatValue($value);
		}

		return '[' . implode(', ', $items) . ']';
	}

	/**
	 * Format a value for PHP code
	 *
	 * @param mixed $value Value to format
	 * @return string Formatted value
	 */
	protected function formatValue(mixed $value): string {
		if ($value === null) {
			return 'null';
		}
		if ($value === true) {
			return 'true';
		}
		if ($value === false) {
			return 'false';
		}
		if (is_int($value) || is_float($value)) {
			return (string)$value;
		}
		if (is_string($value)) {
			// Check if it's a function call
			if (preg_match('/^[A-Z_]+\(.+\)$/i', $value)) {
				return $value;
			}

			return "'" . addslashes($value) . "'";
		}
		if (is_array($value)) {
			return $this->formatArrayInline($value);
		}

		return var_export($value, true);
	}

}
