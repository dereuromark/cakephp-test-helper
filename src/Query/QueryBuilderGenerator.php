<?php

namespace TestHelper\Query;

use RuntimeException;

/**
 * Query Builder Generator
 *
 * Generates CakePHP Query Builder code from parsed SQL structure
 */
class QueryBuilderGenerator {

	/**
	 * @var \TestHelper\Query\ConditionParser
	 */
	protected ConditionParser $conditionParser;

	/**
	 * @var int Indentation level for code formatting
	 */
	protected int $indentLevel = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->conditionParser = new ConditionParser();
	}

	/**
	 * Generate CakePHP Query Builder code from parsed SQL
	 *
     * @param array<string, mixed> $parsed Parsed SQL structure
     * @throws \RuntimeException When query type is not supported
     * @return string Generated PHP code
	 */
	public function generate(array $parsed): string {
		$this->indentLevel = 0;

		return match ($parsed['type']) {
			'SELECT' => $this->generateSelect($parsed),
			'INSERT' => $this->generateInsert($parsed),
			'UPDATE' => $this->generateUpdate($parsed),
			'DELETE' => $this->generateDelete($parsed),
			'UNION' => $this->generateUnion($parsed),
			default => throw new RuntimeException('Unsupported query type: ' . $parsed['type']),
		};
	}

	/**
	 * Generate SELECT query
	 *
	 * @param array<string, mixed> $parsed Parsed structure
	 * @return string Generated code
	 */
	protected function generateSelect(array $parsed): string {
		$code = "use Cake\ORM\Query\SelectQuery;\n\n";
		$code .= "// In your Table class:\n";
		$code .= '$query = $this->find()';

		$this->indentLevel = 1;

		// Build table-to-alias mapping when ORM aliases are present
		$tableAliasMap = [];
		if (!empty($parsed['hasOrmAliases'])) {
			$tableAliasMap = $this->buildTableAliasMap($parsed);
		}

		// Distinct
		if (!empty($parsed['distinct'])) {
			$code .= "\n" . $this->indent() . '->distinct()';
		}

		// Select fields
		if (!empty($parsed['fields']) && $parsed['fields'] !== ['*']) {
			$hasComplexFields = $this->hasComplexFields($parsed['fields']);
			$hasOrmAliases = !empty($parsed['hasOrmAliases']);

			// Add comment if ORM-style aliases detected
			if ($hasOrmAliases && !$hasComplexFields) {
				$code .= "\n" . $this->indent() . '// Note: ORM-style aliases (TableName__field) detected';
				$code .= "\n" . $this->indent() . '// These are automatically handled by CakePHP - no need to specify manually';
				$code .= "\n" . $this->indent() . '// TIP: Consider using contain() or leftJoinWith() instead of manual JOINs';
			}

			if ($hasComplexFields) {
				$code .= "\n" . $this->indent() . '->select(function (\\Cake\\ORM\\Query\\SelectQuery $query) {';
				$code .= "\n" . $this->indent(2) . 'return [';
				$this->indentLevel = 3;
				$fields = [];
				foreach ($parsed['fields'] as $field) {
					$fields[] = $this->formatSelectField($field, $hasOrmAliases);
				}
				$code .= implode(',' . "\n" . $this->indent(), $fields);
				$this->indentLevel = 2;
				$code .= "\n" . $this->indent() . '];';
				$this->indentLevel = 1;
				$code .= "\n" . $this->indent() . '})';
			} else {
				$code .= "\n" . $this->indent() . '->select([';
				$fields = [];
				foreach ($parsed['fields'] as $field) {
					if (is_array($field)) {
						// Strip ORM-style aliases
						if ($hasOrmAliases && !empty($field['isOrmAlias'])) {
							// Convert authors.id with alias Authors__id to just Authors.id
							$cleanField = $this->convertOrmAliasedField($field);
							$fields[] = "'" . $cleanField . "'";
						} else {
							$fields[] = "'" . $field['field'] . "' => '" . $field['alias'] . "'";
						}
					} else {
						$fields[] = "'" . $field . "'";
					}
				}
				$code .= implode(', ', $fields) . '])';
			}
		}

		// Joins
		if (!empty($parsed['joins'])) {
			$hasJoins = false;
			foreach ($parsed['joins'] as $join) {
				$joinMethod = $this->getJoinMethod($join['type']);
				$tableName = $join['table'];
				$associationName = $this->tableNameToAssociation($tableName);

				// Add comment suggesting association-based join if it looks like a standard foreign key join
				if ($this->looksLikeAssociationJoin($join)) {
					if (!$hasJoins) {
						$code .= "\n" . $this->indent() . '// TIP: If you have a ' . $associationName . ' association, consider using:';
						$code .= "\n" . $this->indent() . "// ->contain(['" . $associationName . "']) or ->leftJoinWith('" . $associationName . "')";
						$code .= "\n" . $this->indent() . '// Otherwise, use manual join:';
						$hasJoins = true;
					}
				}

				// Normalize JOIN conditions if we have ORM aliases
				$joinConditions = $join['conditions'];
				if (!empty($tableAliasMap)) {
					$joinConditions = $this->normalizeConditionReferences($joinConditions, $tableAliasMap);
				}

				$code .= "\n" . $this->indent() . '->' . $joinMethod . "('" . $tableName . "'";

				if ($join['alias']) {
					$code .= ', [';
					$code .= "'alias' => '" . $join['alias'] . "', ";
					$code .= "'conditions' => '" . $joinConditions . "'";
					$code .= ']';
				} else {
					$code .= ', [';
					$code .= "'conditions' => '" . $joinConditions . "'";
					$code .= ']';
				}

				$code .= ')';
			}
		}

		// Where conditions
		if (!empty($parsed['where'])) {
			$code .= "\n" . $this->indent() . '->where([';
			$code .= $this->formatConditions($parsed['where'], $tableAliasMap);
			$code .= '])';
		}

		// Group By
		if (!empty($parsed['groupBy'])) {
			$code .= "\n" . $this->indent() . '->groupBy([';
			$groupByFields = array_map(function ($field) use ($tableAliasMap) {
				return $this->normalizeFieldReference($field, $tableAliasMap);
			}, $parsed['groupBy']);
			$code .= "'" . implode("', '", $groupByFields) . "'";
			$code .= '])';
		}

		// Having
		if (!empty($parsed['having'])) {
			$code .= "\n" . $this->indent() . '->having([';
			$code .= $this->formatConditions($parsed['having'], $tableAliasMap);
			$code .= '])';
		}

		// Order By
		if (!empty($parsed['orderBy'])) {
			$code .= "\n" . $this->indent() . '->orderBy([';
			$orderParts = [];
			foreach ($parsed['orderBy'] as $field => $direction) {
				$normalizedField = $this->normalizeFieldReference($field, $tableAliasMap);
				$orderParts[] = "'" . $normalizedField . "' => '" . $direction . "'";
			}
			$code .= implode(', ', $orderParts);
			$code .= '])';
		}

		// Limit
		if ($parsed['limit'] !== null) {
			$code .= "\n" . $this->indent() . '->limit(' . $parsed['limit'] . ')';
		}

		// Offset
		if ($parsed['offset'] !== null) {
			$code .= "\n" . $this->indent() . '->offset(' . $parsed['offset'] . ')';
		}

		$code .= ';';

		// Add note about result conversion
		$code .= "\n\n// Execute and get results:\n";
		$code .= "// \$results = \$query->toArray(); // For collection of entities\n";
		$code .= "// \$result = \$query->first(); // For single entity\n";
		$code .= '// $count = $query->count(); // For count only';

		return $code;
	}

	/**
	 * Generate INSERT query
	 *
	 * @param array<string, mixed> $parsed Parsed structure
	 * @return string Generated code
	 */
	protected function generateInsert(array $parsed): string {
		$isBulkInsert = isset($parsed['values'][0]) && is_array($parsed['values'][0]);

		if ($isBulkInsert) {
			return $this->generateBulkInsert($parsed);
		}

		// Single row insert
		$code = "// In your Table class:\n";
		$code .= "\$entity = \$this->newEmptyEntity();\n";
		$code .= "\$entity = \$this->patchEntity(\$entity, [\n";

		$data = [];
		foreach ($parsed['fields'] as $index => $field) {
			$value = $parsed['values'][$index] ?? 'null';
			$data[] = "    '" . $field . "' => " . $value;
		}

		$code .= implode(",\n", $data);
		$code .= ",\n]);\n\n";
		$code .= "if (\$this->save(\$entity)) {\n";
		$code .= "    // Success\n";
		$code .= "} else {\n";
		$code .= "    // Error: \$entity->getErrors()\n";
		$code .= '}';

		$code .= "\n\n// Alternative: Insert query\n";
		$code .= "\$query = \$this->query();\n";
		$code .= "\$query->insert(['" . implode("', '", $parsed['fields']) . "'])\n";
		$code .= "    ->values([\n";

		foreach ($parsed['fields'] as $index => $field) {
			$value = $parsed['values'][$index] ?? 'null';
			$code .= "        '" . $field . "' => " . $value . ",\n";
		}

		$code .= "    ])\n";
		$code .= '    ->execute();';

		return $code;
	}

	/**
	 * Generate bulk INSERT query
	 *
	 * @param array<string, mixed> $parsed Parsed structure
	 * @return string Generated code
	 */
	protected function generateBulkInsert(array $parsed): string {
		$code = "// Bulk INSERT - multiple rows\n";
		$code .= "// In your Table class:\n\n";

		$code .= "// Option 1: Using saveMany() with entities (recommended)\n";
		$code .= "\$entities = [];\n";
		foreach ($parsed['values'] as $index => $valueSet) {
			$code .= "\$entities[] = \$this->newEntity([\n";
			foreach ($parsed['fields'] as $fieldIndex => $field) {
				$value = $valueSet[$fieldIndex] ?? 'null';
				$code .= "    '" . $field . "' => " . $value . ",\n";
			}
			$code .= "]);\n";
		}
		$code .= "\n";
		$code .= "if (\$this->saveMany(\$entities)) {\n";
		$code .= "    // Success\n";
		$code .= "} else {\n";
		$code .= "    // Check errors: foreach (\$entities as \$entity) { \$entity->getErrors(); }\n";
		$code .= "}\n\n";

		$code .= "// Option 2: Bulk insert query\n";
		$code .= "\$query = \$this->query();\n";
		$code .= "\$query->insert(['" . implode("', '", $parsed['fields']) . "']);\n\n";

		foreach ($parsed['values'] as $index => $valueSet) {
			$code .= "\$query->values([\n";
			foreach ($parsed['fields'] as $fieldIndex => $field) {
				$value = $valueSet[$fieldIndex] ?? 'null';
				$code .= "    '" . $field . "' => " . $value . ",\n";
			}
			$code .= "]);\n";
		}

		$code .= "\n\$query->execute();";

		return $code;
	}

	/**
	 * Generate UPDATE query
	 *
	 * @param array<string, mixed> $parsed Parsed structure
	 * @return string Generated code
	 */
	protected function generateUpdate(array $parsed): string {
		$code = "// Option 1: Update via entity (recommended)\n";
		$code .= "\$entity = \$this->get(\$id);\n";
		$code .= "\$entity = \$this->patchEntity(\$entity, [\n";

		foreach ($parsed['set'] as $field => $value) {
			$code .= "    '" . $field . "' => " . $value . ",\n";
		}

		$code .= "]);\n\n";
		$code .= "if (\$this->save(\$entity)) {\n";
		$code .= "    // Success\n";
		$code .= "}\n\n";

		$code .= "// Option 2: Update query (bulk update)\n";
		$code .= "\$query = \$this->query();\n";
		$code .= "\$query->update()\n";
		$code .= "    ->set([\n";

		foreach ($parsed['set'] as $field => $value) {
			$code .= "        '" . $field . "' => " . $value . ",\n";
		}

		$code .= '    ])';

		if (!empty($parsed['where'])) {
			$code .= "\n    ->where([";
			$code .= $this->formatConditions($parsed['where']);
			$code .= '])';
		}

		$code .= "\n    ->execute();";

		return $code;
	}

	/**
	 * Generate DELETE query
	 *
	 * @param array<string, mixed> $parsed Parsed structure
	 * @return string Generated code
	 */
	protected function generateDelete(array $parsed): string {
		$code = "// Option 1: Delete via entity (recommended)\n";
		$code .= "\$entity = \$this->get(\$id);\n";
		$code .= "if (\$this->delete(\$entity)) {\n";
		$code .= "    // Success\n";
		$code .= "}\n\n";

		$code .= "// Option 2: Delete query (bulk delete)\n";
		$code .= "\$query = \$this->query();\n";
		$code .= '$query->delete()';

		if (!empty($parsed['where'])) {
			$code .= "\n    ->where([";
			$code .= $this->formatConditions($parsed['where']);
			$code .= '])';
		}

		$code .= "\n    ->execute();";

		return $code;
	}

	/**
	 * Generate UNION query
	 *
	 * @param array<string, mixed> $parsed Parsed structure
	 * @return string Generated code
	 */
	protected function generateUnion(array $parsed): string {
		$code = "use Cake\ORM\Query\SelectQuery;\n\n";
		$code .= "// In your Table class:\n";

		if (empty($parsed['queries'])) {
			$code .= '// ERROR: No queries found in UNION';

			return $code;
		}

		$method = $parsed['unionAll'] ? 'unionAll' : 'union';

		// Generate first query
		$code .= '$query = $this->find()';
		$this->indentLevel = 1;

		// Generate the first SELECT query parts
		$firstQuery = $parsed['queries'][0];
		$code .= $this->generateSelectQueryParts($firstQuery);

		// Generate union calls for remaining queries
		for ($i = 1; $i < count($parsed['queries']); $i++) {
			$unionQuery = $parsed['queries'][$i];
			$code .= "\n" . $this->indent() . '->' . $method . '(function (\\Cake\\ORM\\Query\\SelectQuery $query) {';
			$code .= "\n" . $this->indent(2) . 'return $query';
			$this->indentLevel = 3;
			$code .= $this->generateSelectQueryParts($unionQuery);
			$code .= ';';
			$this->indentLevel = 1;
			$code .= "\n" . $this->indent() . '})';
		}

		$code .= ';';

		$code .= "\n\n// Execute and get results:\n";
		$code .= '// $results = $query->toArray();';

		return $code;
	}

	/**
	 * Generate SELECT query parts (without the initial find())
	 *
	 * @param array<string, mixed> $parsed Parsed SELECT structure
	 * @return string Generated code
	 */
	protected function generateSelectQueryParts(array $parsed): string {
		$code = '';

		// Distinct
		if (!empty($parsed['distinct'])) {
			$code .= "\n" . $this->indent() . '->distinct()';
		}

		// Select fields (simplified for UNION - no complex expressions in closure)
		if (!empty($parsed['fields']) && $parsed['fields'] !== ['*']) {
			$code .= "\n" . $this->indent() . '->select([';
			$fields = [];
			foreach ($parsed['fields'] as $field) {
				if (is_array($field)) {
					$fields[] = "'" . $field['field'] . "' => '" . $field['alias'] . "'";
				} else {
					$fields[] = "'" . $field . "'";
				}
			}
			$code .= implode(', ', $fields) . '])';
		}

		// Where conditions
		if (!empty($parsed['where'])) {
			$code .= "\n" . $this->indent() . '->where([';
			$code .= $this->formatConditions($parsed['where']);
			$code .= '])';
		}

		// Order By
		if (!empty($parsed['orderBy'])) {
			$code .= "\n" . $this->indent() . '->orderBy([';
			$orderParts = [];
			foreach ($parsed['orderBy'] as $field => $direction) {
				$orderParts[] = "'" . $field . "' => '" . $direction . "'";
			}
			$code .= implode(', ', $orderParts);
			$code .= '])';
		}

		// Limit
		if ($parsed['limit'] !== null) {
			$code .= "\n" . $this->indent() . '->limit(' . $parsed['limit'] . ')';
		}

		return $code;
	}

	/**
	 * Get appropriate join method name
	 *
	 * @param string $joinType SQL join type
	 * @return string CakePHP join method
	 */
	protected function getJoinMethod(string $joinType): string {
		return match (strtoupper($joinType)) {
			'INNER', 'INNER JOIN' => 'innerJoin',
			'LEFT', 'LEFT JOIN', 'LEFT OUTER JOIN' => 'leftJoin',
			'RIGHT', 'RIGHT JOIN', 'RIGHT OUTER JOIN' => 'rightJoin',
			default => 'join',
		};
	}

	/**
	 * Format conditions for code output
	 *
	 * @param array|string $conditions Conditions string or array with subqueries
	 * @param array<string, string> $tableAliasMap Mapping of lowercase table names to PascalCase aliases
	 * @return string Formatted conditions
	 */
	protected function formatConditions(array|string $conditions, array $tableAliasMap = []): string {
		// Handle subqueries
		if (is_array($conditions) && isset($conditions['subqueries'])) {
			// TODO: Generate subquery code
			$condStr = $conditions['conditions'] ?? '';

			return "\n            // TODO: Contains subqueries - convert manually\n            // " . str_replace("\n", "\n            // ", $condStr) . "\n        ";
		}

		// At this point, $conditions should be a string
		if (!is_string($conditions)) {
			return '';
		}

		// Normalize table references if we have ORM aliases
		if (!empty($tableAliasMap)) {
			$conditions = $this->normalizeConditionReferences($conditions, $tableAliasMap);
		}

		$parsed = $this->conditionParser->parse($conditions);

		return $this->conditionParser->formatAsPhpArray($parsed, 2);
	}

	/**
	 * Generate indentation
	 *
	 * @param int|null $level Override indentation level
	 * @return string Indentation string
	 */
	protected function indent(?int $level = null): string {
		$actualLevel = $level ?? $this->indentLevel;

		return str_repeat('    ', $actualLevel);
	}

	/**
	 * Convert table name to likely association name
	 *
	 * @param string $tableName Table name
	 * @return string Association name (PascalCase, plural)
	 */
	protected function tableNameToAssociation(string $tableName): string {
		// Convert snake_case to PascalCase and singularize/pluralize as appropriate
		$parts = explode('_', $tableName);
		$pascalCase = implode('', array_map('ucfirst', $parts));

		return $pascalCase;
	}

	/**
	 * Check if a join looks like a standard association join
	 *
	 * @param array<string, mixed> $join Join information
	 * @return bool True if it looks like an association join
	 */
	protected function looksLikeAssociationJoin(array $join): bool {
		$conditions = $join['conditions'];

		// Look for patterns like: table.foreign_key = other_table.id
		// or: foreign_key = id
		if (preg_match('/(\w+)\.(\w+)\s*=\s*(\w+)\.(\w+)/i', $conditions, $matches)) {
			$field1 = $matches[2];
			$field2 = $matches[4];

			// Check if one ends with _id and the other is 'id'
			if ((str_ends_with($field1, '_id') && $field2 === 'id') ||
				(str_ends_with($field2, '_id') && $field1 === 'id')) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Convert ORM-aliased field to clean format
	 *
	 * Converts "authors.id AS Authors__id" to "Authors.id"
	 *
	 * @param array<string, mixed> $field Field data with ORM alias
	 * @return string Clean field name
	 */
	protected function convertOrmAliasedField(array $field): string {
		if (empty($field['isOrmAlias']) || empty($field['alias'])) {
			return $field['field'];
		}

		// Extract table name from ORM alias (Authors__id -> Authors)
		$parts = explode('__', $field['alias']);
		$tableName = $parts[0];
		$fieldName = $parts[1] ?? '';

		// Return TableName.field_name format
		if ($tableName && $fieldName) {
			return $tableName . '.' . $fieldName;
		}

		return $field['field'];
	}

	/**
	 * Check if fields contain complex expressions
	 *
	 * @param array<int, mixed> $fields Parsed fields
	 * @return bool True if contains complex expressions
	 */
	protected function hasComplexFields(array $fields): bool {
		foreach ($fields as $field) {
			if (is_array($field) && isset($field['type']) && $field['type'] !== 'column') {
				return true;
			}
		}

		return false;
	}

	/**
	 * Format a SELECT field for code generation
	 *
	 * @param mixed $field Field data
	 * @param bool $hasOrmAliases Whether the query has ORM-style aliases
	 * @return string Formatted field code
	 */
	protected function formatSelectField(mixed $field, bool $hasOrmAliases = false): string {
		if (is_string($field)) {
			return "'" . $field . "'";
		}

		if (!isset($field['type']) || $field['type'] === 'column') {
			// Simple column field
			if (isset($field['alias'])) {
				// Strip ORM-style aliases
				if ($hasOrmAliases && !empty($field['isOrmAlias'])) {
					$cleanField = $this->convertOrmAliasedField($field);

					return "'" . $cleanField . "'";
				}

				return "'" . $field['field'] . "' => '" . $field['alias'] . "'";
			}

			return "'" . $field['field'] . "'";
		}

		// Complex field expression
		$expr = $this->formatFieldExpression($field);
		if (isset($field['alias'])) {
			// Strip ORM-style aliases from complex expressions too
			if ($hasOrmAliases && !empty($field['isOrmAlias'])) {
				$cleanField = $this->convertOrmAliasedField($field);

				return $expr . " => '" . $cleanField . "'";
			}

			return $expr . " => '" . $field['alias'] . "'";
		}

		return $expr;
	}

	/**
	 * Format a field expression using CakePHP's func() or expression syntax
	 *
	 * @param array<string, mixed> $field Field data with type
	 * @return string Formatted expression
	 */
	protected function formatFieldExpression(array $field): string {
		$fieldExpr = $field['field'];
		$type = $field['type'];

		switch ($type) {
			case 'aggregate':
				return $this->formatAggregateFunction($fieldExpr);
			case 'string_func':
			case 'date_func':
				return $this->formatFunction($fieldExpr);
			case 'case':
				return $this->formatCaseExpression($fieldExpr);
			case 'math':
				return $this->formatMathExpression($fieldExpr);
			default:
				return "'" . $fieldExpr . "'";
		}
	}

	/**
	 * Format aggregate function using func()
	 *
	 * @param string $expr SQL function expression
	 * @return string CakePHP func() code
	 */
	protected function formatAggregateFunction(string $expr): string {
		// Parse function name and arguments
		if (preg_match('/^([A-Z_]+)\s*\((.*)\)\s*$/i', $expr, $matches)) {
			$funcName = strtolower($matches[1]);
			$args = trim($matches[2]);

			// Handle COUNT(*) specially
			if ($funcName === 'count' && $args === '*') {
				return "\$query->func()->count('*')";
			}

			// Remove quotes and backticks from field names
			$args = trim($args, '`"\'');

			return '$query->func()->' . $funcName . "('" . $args . "')";
		}

		// Fallback
		return "'" . $expr . "' // TODO: Complex aggregate - convert manually";
	}

	/**
	 * Format string/date function
	 *
	 * @param string $expr SQL function expression
	 * @return string CakePHP code
	 */
	protected function formatFunction(string $expr): string {
		// For now, return as TODO comment - full function parsing is complex
		return "'" . $expr . "' // TODO: Convert function to CakePHP syntax";
	}

	/**
	 * Format CASE expression
	 *
	 * @param string $expr CASE expression
	 * @return string CakePHP code
	 */
	protected function formatCaseExpression(string $expr): string {
		return "'" . $expr . "' // TODO: Convert CASE to QueryExpression";
	}

	/**
	 * Format mathematical expression
	 *
	 * @param string $expr Math expression
	 * @return string CakePHP code
	 */
	protected function formatMathExpression(string $expr): string {
		// Simple case: field * field or field * number
		if (preg_match('/^([a-z0-9_.`]+)\s*([\+\-\*\/])\s*([a-z0-9_.`]+|\d+)$/i', $expr, $matches)) {
			$left = trim($matches[1], '`"\'');
			$op = $matches[2];
			$right = trim($matches[3], '`"\'');

			// If right side is numeric, use it as-is, otherwise quote it
			if (is_numeric($right)) {
				return "\$query->newExpr('" . $left . ' ' . $op . ' ' . $right . "')";
			}

			return "\$query->newExpr('" . $left . ' ' . $op . ' ' . $right . "')";
		}

		return "'" . $expr . "' // TODO: Complex math expression";
	}

	/**
	 * Build a mapping of lowercase table names to their PascalCase aliases
	 *
	 * @param array<string, mixed> $parsed Parsed SQL structure
	 * @return array<string, string> Mapping of lowercase table => PascalCase alias
	 */
	protected function buildTableAliasMap(array $parsed): array {
		$map = [];

		// Add FROM table alias if present
		if (!empty($parsed['from']) && !empty($parsed['fromAlias'])) {
			$map[strtolower($parsed['from'])] = $parsed['fromAlias'];
		}

		// Add JOIN table aliases
		if (!empty($parsed['joins'])) {
			foreach ($parsed['joins'] as $join) {
				if (!empty($join['table']) && !empty($join['alias'])) {
					$map[strtolower($join['table'])] = $join['alias'];
				}
			}
		}

		return $map;
	}

	/**
	 * Normalize a field reference to use PascalCase aliases
	 *
	 * Converts "table.field" to "Alias.field" when a mapping exists
	 *
	 * @param string $field Field reference (e.g., "users.id")
	 * @param array<string, string> $tableAliasMap Table to alias mapping
	 * @return string Normalized field reference (e.g., "Users.id")
	 */
	protected function normalizeFieldReference(string $field, array $tableAliasMap): string {
		if (empty($tableAliasMap)) {
			return $field;
		}

		// Check if field contains table.field pattern
		if (preg_match('/^([a-z][a-z0-9_]*)\\.(.+)$/i', $field, $matches)) {
			$tableName = strtolower($matches[1]);
			$fieldName = $matches[2];

			// If we have an alias mapping for this table, use it
			if (isset($tableAliasMap[$tableName])) {
				return $tableAliasMap[$tableName] . '.' . $fieldName;
			}
		}

		return $field;
	}

	/**
	 * Normalize all table references in a condition string
	 *
	 * Replaces lowercase table names with PascalCase aliases throughout the condition
	 *
	 * @param string $conditions Condition string
	 * @param array<string, string> $tableAliasMap Table to alias mapping
	 * @return string Normalized condition string
	 */
	protected function normalizeConditionReferences(string $conditions, array $tableAliasMap): string {
		if (empty($tableAliasMap)) {
			return $conditions;
		}

		// Replace each table.field reference with Alias.field
		foreach ($tableAliasMap as $tableName => $alias) {
			// Match table.field but not within quoted strings
			$conditions = (string)preg_replace(
				'/\b' . preg_quote($tableName, '/') . '\.([a-z_][a-z0-9_]*)\b/i',
				$alias . '.$1',
				$conditions,
			);
		}

		return $conditions;
	}

}
