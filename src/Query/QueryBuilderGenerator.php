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
			'CTE' => $this->generateCTE($parsed),
			default => throw new RuntimeException('Unsupported query type: ' . $parsed['type']),
		};
	}

	/**
	 * Generate CTE (Common Table Expression) query
	 *
	 * @param array<string, mixed> $parsed Parsed structure
	 * @return string Generated code
	 */
	protected function generateCTE(array $parsed): string {
		$code = "// CTE (Common Table Expression) with WITH clause\n";
		$code .= "// Note: CakePHP doesn't have native CTE support\n";
		$code .= "// Options:\n\n";

		$code .= "// Option 1: Use subqueries instead (recommended)\n";
		$code .= "// Convert CTEs to subqueries in FROM or WHERE clauses\n\n";

		$code .= "// Option 2: Use raw SQL\n";
		$code .= "// \$connection = \$this->getConnection();\n";
		$code .= "// \$statement = \$connection->execute(\"\n";
		$code .= '//     WITH ' . ($parsed['ctes'][0]['raw'] ?? 'cte_name AS (...)') . "\n";
		if (!empty($parsed['mainQuery'])) {
			$code .= "//     /* main query follows */\n";
		}
		$code .= "// \");\n";
		$code .= "// \$results = \$statement->fetchAll('assoc');\n\n";

		$code .= "// Option 3: Break into separate queries\n";
		$code .= "// Execute CTE query first, then use results in main query\n";

		if (!empty($parsed['mainQuery'])) {
			$code .= "\n\n// Main query (after CTE):\n";
			$code .= $this->generate($parsed['mainQuery']);
		}

		return $code;
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
		// Check if multi-table update
		if (!empty($parsed['isMultiTable'])) {
			return $this->generateMultiTableUpdate($parsed);
		}

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
	 * Generate multi-table UPDATE query
	 *
	 * @param array<string, mixed> $parsed Parsed structure
	 * @return string Generated code
	 */
	protected function generateMultiTableUpdate(array $parsed): string {
		$code = "// Multi-table UPDATE with JOINs\n";
		$code .= "// Note: CakePHP doesn't support multi-table updates directly\n";
		$code .= "// Recommended approach: Update tables separately within a transaction\n\n";

		$code .= "// Option 1: Transaction with separate updates (recommended)\n";
		$code .= "\$this->getConnection()->transactional(function () {\n";

		// Group SET clauses by table
		$tableUpdates = [];
		foreach ($parsed['set'] as $field => $value) {
			// Extract table from field (e.g., "users.last_login" -> "users")
			if (str_contains($field, '.')) {
				[$table, $fieldName] = explode('.', $field, 2);
				$tableUpdates[$table][$fieldName] = $value;
			} else {
				$tableUpdates[$parsed['table']][$field] = $value;
			}
		}

		foreach ($tableUpdates as $table => $fields) {
			$code .= '    // Update ' . $table . "\n";
			$code .= '    $this->' . $this->tableNameToAssociation($table) . "Table = \$this->fetchTable('" . $this->tableNameToAssociation($table) . "');\n";
			$code .= '    $this->' . $this->tableNameToAssociation($table) . "Table->updateAll([\n";
			foreach ($fields as $field => $value) {
				$code .= "        '" . $field . "' => " . $value . ",\n";
			}
			$code .= "    ], [/* conditions */]);\n\n";
		}

		$code .= "});\n\n";

		$code .= "// Option 2: Raw SQL (if absolutely necessary)\n";
		$code .= "// \$connection = \$this->getConnection();\n";
		$code .= '// $connection->execute("UPDATE ... JOIN ... SET ... WHERE ...");';

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
			return $this->formatConditionsWithSubqueries($conditions, $tableAliasMap);
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
	 * Format conditions that contain subqueries
	 *
	 * @param array<string, mixed> $conditionsWithSubqueries Conditions with subquery placeholders
	 * @param array<string, string> $tableAliasMap Table alias mapping
	 * @return string Formatted conditions
	 */
	protected function formatConditionsWithSubqueries(array $conditionsWithSubqueries, array $tableAliasMap = []): string {
		$condStr = $conditionsWithSubqueries['conditions'] ?? '';
		$subqueries = $conditionsWithSubqueries['subqueries'] ?? [];

		if (empty($subqueries)) {
			return $this->formatConditions($condStr, $tableAliasMap);
		}

		// Generate subquery variables
		$code = "\n";
		foreach ($subqueries as $placeholder => $subquerySql) {
			$varName = '$subquery' . str_replace(['__SUBQUERY_', '__'], ['', ''], $placeholder);
			$code .= "\n            " . $varName . ' = $this->find()';

			// Parse and generate subquery
			$subqueryParsed = (new SqlParser())->parse($subquerySql);
			if ($subqueryParsed['type'] === 'SELECT') {
				$code .= $this->generateSubqueryParts($subqueryParsed);
			}

			$code .= ';';
		}

		// Replace placeholders with subquery variables in conditions
		foreach ($subqueries as $placeholder => $subquerySql) {
			$varName = '$subquery' . str_replace(['__SUBQUERY_', '__'], ['', ''], $placeholder);
			$condStr = str_replace($placeholder, $varName, $condStr);
		}

		$code .= "\n\n            ";

		// Parse and format the modified conditions
		$parsed = $this->conditionParser->parse($condStr);
		$code .= $this->conditionParser->formatAsPhpArray($parsed, 2);

		return $code;
	}

	/**
	 * Generate subquery parts (SELECT, WHERE, etc.)
	 *
	 * @param array<string, mixed> $parsed Parsed subquery structure
	 * @return string Generated code parts
	 */
	protected function generateSubqueryParts(array $parsed): string {
		$code = '';
		$originalIndent = $this->indentLevel;
		$this->indentLevel = 4;

		// Select fields
		if (!empty($parsed['fields']) && $parsed['fields'] !== ['*']) {
			$code .= "\n" . $this->indent() . '->select([';
			$fields = [];
			foreach ($parsed['fields'] as $field) {
				if (is_array($field)) {
					$fields[] = "'" . $field['field'] . "'";
				} else {
					$fields[] = "'" . $field . "'";
				}
			}
			$code .= implode(', ', $fields) . '])';
		}

		// FROM - subqueries typically don't need explicit from()
		if (!empty($parsed['from']) && $parsed['from'] !== 'dual') {
			$code .= "\n" . $this->indent() . "->from('" . $parsed['from'] . "')";
		}

		// WHERE
		if (!empty($parsed['where'])) {
			$code .= "\n" . $this->indent() . '->where([';
			$code .= $this->formatConditions($parsed['where']);
			$code .= '])';
		}

		$this->indentLevel = $originalIndent;

		return $code;
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
			case 'window_func':
				return $this->formatWindowFunction($fieldExpr);
			default:
				return "'" . $fieldExpr . "'";
		}
	}

	/**
	 * Format window function
	 *
	 * @param string $expr Window function expression
	 * @return string CakePHP code with guidance
	 */
	protected function formatWindowFunction(string $expr): string {
		$code = "'" . $expr . "'\n";
		$code .= str_repeat(' ', 16) . '// TODO: Window functions have limited support in CakePHP 5.x\n';
		$code .= str_repeat(' ', 16) . '// Options:\n';
		$code .= str_repeat(' ', 16) . '// 1. Use raw SQL: $query->select(["row_num" => $query->newExpr("' . $expr . '")])\n';
		$code .= str_repeat(' ', 16) . '// 2. Consider using subquery or post-processing in PHP\n';
		$code .= str_repeat(' ', 16) . '// 3. For RANK/ROW_NUMBER, consider fetching and processing results';

		return $code;
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
		// Parse function name and arguments
		if (!preg_match('/^([A-Z_]+)\s*\((.*)\)\s*$/is', $expr, $matches)) {
			return "'" . $expr . "' // TODO: Complex function - convert manually";
		}

		$funcName = strtoupper(trim($matches[1]));
		$argsStr = trim($matches[2]);

		// Map SQL functions to CakePHP equivalents
		$result = match ($funcName) {
			'CONCAT' => $this->formatConcatFunction($argsStr),
			'CONCAT_WS' => $this->formatConcatWsFunction($argsStr),
			'COALESCE' => $this->formatCoalesceFunction($argsStr),
			'IFNULL' => $this->formatIfNullFunction($argsStr),
			'SUBSTRING', 'SUBSTR' => $this->formatSubstringFunction($argsStr),
			'UPPER' => $this->formatSimpleStringFunction('upper', $argsStr),
			'LOWER' => $this->formatSimpleStringFunction('lower', $argsStr),
			'TRIM' => $this->formatSimpleStringFunction('trim', $argsStr),
			'LTRIM' => $this->formatSimpleStringFunction('ltrim', $argsStr),
			'RTRIM' => $this->formatSimpleStringFunction('rtrim', $argsStr),
			'LENGTH', 'CHAR_LENGTH' => $this->formatSimpleStringFunction('length', $argsStr),
			'NOW', 'CURDATE', 'CURTIME' => $this->formatNowFunction($funcName),
			'YEAR', 'MONTH', 'DAY', 'HOUR', 'MINUTE', 'SECOND' => $this->formatDatePartFunction($funcName, $argsStr),
			'DATE_FORMAT' => $this->formatDateFormatFunction($argsStr),
			'DATEDIFF' => $this->formatDateDiffFunction($argsStr),
			default => "'" . $expr . "' // TODO: " . $funcName . ' - no direct CakePHP equivalent',
		};

		return $result;
	}

	/**
	 * Format CASE expression
	 *
	 * @param string $expr CASE expression
	 * @return string CakePHP code
	 */
	protected function formatCaseExpression(string $expr): string {
		// Parse CASE statement
		$parsed = $this->parseCaseExpression($expr);
		if (!$parsed) {
			return "'" . $expr . "' // TODO: Complex CASE - convert manually";
		}

		$code = "\$query->newExpr()->case()\n";
		$indent = str_repeat(' ', 16); // Adjust based on context

		// Add WHEN clauses
		foreach ($parsed['when'] as $when) {
			$condition = $when['condition'];
			$result = $when['result'];
			$code .= $indent . '->when([' . $this->formatCaseCondition($condition) . "])\n";
			$code .= $indent . '->then(' . $this->formatCaseValue($result) . ")\n";
		}

		// Add ELSE clause if present
		if ($parsed['else']) {
			$code .= $indent . '->else(' . $this->formatCaseValue($parsed['else']) . ')';
		}

		return $code;
	}

	/**
	 * Parse CASE expression into structure
	 *
	 * @param string $expr CASE expression
	 * @return array<string, mixed>|null Parsed structure or null if failed
	 */
	protected function parseCaseExpression(string $expr): ?array {
		// Remove CASE and END keywords
		$expr = (string)preg_replace('/^CASE\s+/i', '', $expr);
		$expr = (string)preg_replace('/\s+END$/i', '', $expr);

		if (!$expr) {
			return null;
		}

		$result = [
			'when' => [],
			'else' => null,
		];

		// Extract ELSE clause first
		if (preg_match('/\s+ELSE\s+(.+)$/is', $expr, $matches)) {
			$result['else'] = trim($matches[1]);
			$expr = (string)preg_replace('/\s+ELSE\s+.+$/is', '', $expr);
		}

		// Extract WHEN clauses
		if (!preg_match_all('/WHEN\s+(.+?)\s+THEN\s+(.+?)(?=\s+WHEN|\s+ELSE|$)/is', $expr, $matches, PREG_SET_ORDER)) {
			return null;
		}

		foreach ($matches as $match) {
			$result['when'][] = [
				'condition' => trim($match[1]),
				'result' => trim($match[2]),
			];
		}

		return $result;
	}

	/**
	 * Format CASE condition for QueryExpression
	 *
	 * @param string $condition Condition string
	 * @return string Formatted condition
	 */
	protected function formatCaseCondition(string $condition): string {
		// Try to parse as simple comparison
		if (preg_match('/^(\w+)\s*(=|!=|<>|>|<|>=|<=)\s*(.+)$/', $condition, $matches)) {
			$field = trim($matches[1]);
			$operator = trim($matches[2]);
			$value = trim($matches[3]);

			// Map operators
			$op = match ($operator) {
				'=' => '',
				'!=' => ' !=',
				'<>' => ' !=',
				'>' => ' >',
				'<' => ' <',
				'>=' => ' >=',
				'<=' => ' <=',
				default => '',
			};

			return "'" . $field . $op . "' => " . $this->formatCaseValue($value);
		}

		// For complex conditions, return as-is with TODO
		return '/* TODO: Complex condition: ' . $condition . ' */';
	}

	/**
	 * Format CASE result value
	 *
	 * @param string $value Result value
	 * @return string Formatted value
	 */
	protected function formatCaseValue(string $value): string {
		$value = trim($value);

		// If it's a quoted string, return as-is
		if (preg_match('/^[\'"].*[\'"]$/', $value)) {
			return $value;
		}

		// If it's a number, return as-is
		if (is_numeric($value)) {
			return $value;
		}

		// Otherwise, it's a field name
		return "'" . trim($value, '`"\'') . "'";
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

	/**
	 * Parse function arguments from comma-separated string
	 *
	 * Handles nested functions and quoted strings
	 *
	 * @param string $argsStr Arguments string
	 * @return array<int, string> Array of arguments
	 */
	protected function parseFunctionArguments(string $argsStr): array {
		$args = [];
		$current = '';
		$depth = 0;
		$inQuote = false;
		$quoteChar = '';

		for ($i = 0; $i < strlen($argsStr); $i++) {
			$char = $argsStr[$i];

			// Handle quotes
			if (($char === '"' || $char === "'") && ($i === 0 || $argsStr[$i - 1] !== '\\')) {
				if (!$inQuote) {
					$inQuote = true;
					$quoteChar = $char;
				} elseif ($char === $quoteChar) {
					$inQuote = false;
					$quoteChar = '';
				}
			}

			// Handle parentheses depth
			if (!$inQuote) {
				if ($char === '(') {
					$depth++;
				} elseif ($char === ')') {
					$depth--;
				}
			}

			// Split on comma at depth 0
			if ($char === ',' && $depth === 0 && !$inQuote) {
				$args[] = trim($current);
				$current = '';
			} else {
				$current .= $char;
			}
		}

		// Add last argument
		if ($current !== '') {
			$args[] = trim($current);
		}

		return $args;
	}

	/**
	 * Format argument for CakePHP func() call
	 *
	 * @param string $arg Raw argument
	 * @return string Formatted argument
	 */
	protected function formatFunctionArg(string $arg): string {
		$arg = trim($arg);

		// If it's a string literal, keep the quotes
		if (preg_match('/^[\'"].*[\'"]$/', $arg)) {
			return $arg;
		}

		// If it's a number, use as-is
		if (is_numeric($arg)) {
			return $arg;
		}

		// If it contains a function call, it's a nested function
		if (preg_match('/^[A-Z_]+\s*\(/i', $arg)) {
			return $arg; // Let it be handled as nested
		}

		// Otherwise, it's a field name - quote it
		return "'" . trim($arg, '`"\'') . "'";
	}

	/**
	 * Format CONCAT function
	 *
	 * @param string $argsStr Arguments string
	 * @return string CakePHP code
	 */
	protected function formatConcatFunction(string $argsStr): string {
		$args = $this->parseFunctionArguments($argsStr);
		$formattedArgs = array_map([$this, 'formatFunctionArg'], $args);

		return '$query->func()->concat([' . implode(', ', $formattedArgs) . '])';
	}

	/**
	 * Format CONCAT_WS function
	 *
	 * @param string $argsStr Arguments string
	 * @return string CakePHP code
	 */
	protected function formatConcatWsFunction(string $argsStr): string {
		$args = $this->parseFunctionArguments($argsStr);
		if (empty($args)) {
			return "'CONCAT_WS()' // TODO: Invalid arguments";
		}

		$separator = array_shift($args);
		$formattedArgs = array_map([$this, 'formatFunctionArg'], $args);

		return '$query->func()->concat([' . implode(', ' . $separator . ', ', $formattedArgs) . '])';
	}

	/**
	 * Format COALESCE function
	 *
	 * @param string $argsStr Arguments string
	 * @return string CakePHP code
	 */
	protected function formatCoalesceFunction(string $argsStr): string {
		$args = $this->parseFunctionArguments($argsStr);
		$formattedArgs = array_map([$this, 'formatFunctionArg'], $args);

		return '$query->func()->coalesce([' . implode(', ', $formattedArgs) . '])';
	}

	/**
	 * Format IFNULL function
	 *
	 * @param string $argsStr Arguments string
	 * @return string CakePHP code
	 */
	protected function formatIfNullFunction(string $argsStr): string {
		// IFNULL is similar to COALESCE with 2 arguments
		return $this->formatCoalesceFunction($argsStr);
	}

	/**
	 * Format SUBSTRING function
	 *
	 * @param string $argsStr Arguments string
	 * @return string CakePHP code
	 */
	protected function formatSubstringFunction(string $argsStr): string {
		$args = $this->parseFunctionArguments($argsStr);

		if (count($args) < 2) {
			return "'SUBSTRING(...)' // TODO: Invalid arguments";
		}

		$field = $this->formatFunctionArg($args[0]);
		$start = $args[1];
		$length = $args[2] ?? null;

		if ($length) {
			return "\$query->func()->substring([$field, $start, $length])";
		}

		return "\$query->func()->substring([$field, $start])";
	}

	/**
	 * Format simple string function (UPPER, LOWER, TRIM, etc.)
	 *
	 * @param string $funcName Function name
	 * @param string $argsStr Arguments string
	 * @return string CakePHP code
	 */
	protected function formatSimpleStringFunction(string $funcName, string $argsStr): string {
		$arg = $this->formatFunctionArg($argsStr);

		return '$query->func()->' . $funcName . '(' . $arg . ')';
	}

	/**
	 * Format NOW/CURDATE/CURTIME function
	 *
	 * @param string $funcName Function name
	 * @return string CakePHP code
	 */
	protected function formatNowFunction(string $funcName): string {
		return match (strtoupper($funcName)) {
			'NOW' => '$query->func()->now()',
			'CURDATE' => '$query->func()->curdate()',
			'CURTIME' => '$query->func()->curtime()',
			default => "'$funcName()' // TODO: Use FrozenTime helper",
		};
	}

	/**
	 * Format date part extraction function (YEAR, MONTH, DAY, etc.)
	 *
	 * @param string $funcName Function name
	 * @param string $argsStr Arguments string
	 * @return string CakePHP code
	 */
	protected function formatDatePartFunction(string $funcName, string $argsStr): string {
		$arg = $this->formatFunctionArg($argsStr);
		$funcName = strtolower($funcName);

		return '$query->func()->' . $funcName . '(' . $arg . ')';
	}

	/**
	 * Format DATE_FORMAT function
	 *
	 * @param string $argsStr Arguments string
	 * @return string CakePHP code
	 */
	protected function formatDateFormatFunction(string $argsStr): string {
		$args = $this->parseFunctionArguments($argsStr);

		if (count($args) < 2) {
			return "'DATE_FORMAT(...)' // TODO: Invalid arguments";
		}

		$field = $this->formatFunctionArg($args[0]);
		$format = $args[1];

		return "\$query->func()->datePart('format', [$field, $format]) // TODO: Verify format string";
	}

	/**
	 * Format DATEDIFF function
	 *
	 * @param string $argsStr Arguments string
	 * @return string CakePHP code
	 */
	protected function formatDateDiffFunction(string $argsStr): string {
		$args = $this->parseFunctionArguments($argsStr);

		if (count($args) < 2) {
			return "'DATEDIFF(...)' // TODO: Invalid arguments";
		}

		$date1 = $this->formatFunctionArg($args[0]);
		$date2 = $this->formatFunctionArg($args[1]);

		return "\$query->func()->dateDiff([$date1, $date2])";
	}

}
