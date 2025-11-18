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

		// Distinct
		if (!empty($parsed['distinct'])) {
			$code .= "\n" . $this->indent() . '->distinct()';
		}

		// Select fields
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

				$code .= "\n" . $this->indent() . '->' . $joinMethod . "('" . $tableName . "'";

				if ($join['alias']) {
					$code .= ', [';
					$code .= "'alias' => '" . $join['alias'] . "', ";
					$code .= "'conditions' => '" . $join['conditions'] . "'";
					$code .= ']';
				} else {
					$code .= ', [';
					$code .= "'conditions' => '" . $join['conditions'] . "'";
					$code .= ']';
				}

				$code .= ')';
			}
		}

		// Where conditions
		if (!empty($parsed['where'])) {
			$code .= "\n" . $this->indent() . '->where([';
			$code .= $this->formatConditions($parsed['where']);
			$code .= '])';
		}

		// Group By
		if (!empty($parsed['groupBy'])) {
			$code .= "\n" . $this->indent() . '->groupBy([';
			$code .= "'" . implode("', '", $parsed['groupBy']) . "'";
			$code .= '])';
		}

		// Having
		if (!empty($parsed['having'])) {
			$code .= "\n" . $this->indent() . '->having([';
			$code .= $this->formatConditions($parsed['having']);
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
	 * @param string $conditions Conditions string
	 * @return string Formatted conditions
	 */
	protected function formatConditions(string $conditions): string {
		$parsed = $this->conditionParser->parse($conditions);

		return $this->conditionParser->formatAsPhpArray($parsed, 2);
	}

	/**
	 * Generate indentation
	 *
	 * @return string Indentation string
	 */
	protected function indent(): string {
		return str_repeat('    ', $this->indentLevel);
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

}
