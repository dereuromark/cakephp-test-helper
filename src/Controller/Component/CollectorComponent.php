<?php

namespace TestHelper\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Utility\Inflector;
use DirectoryIterator;
use Exception;
use Throwable;

/**
 * @method \App\Controller\AppController getController()
 */
class CollectorComponent extends Component {

	/**
	 * @var array<string, mixed>
	 */
	protected array $_defaultConfig = [
		'blacklist' => [
			'DebugKit',
		],
		'ignoredTables' => [],
		'ignoredEntities' => [],
		'ignoredDbTables' => ['i18n', 'cake_sessions', 'sessions', '/phinxlog/'],
		'connection' => 'default',
	];

	/**
	 * @param array<string> $plugins
	 * @return array
	 */
	public function modelComparison(array $plugins): array {
		$result = [];

		$dbTables = $this->dbTables();

		$tablePath = ROOT . DS . 'src' . DS . 'Model' . DS . 'Table' . DS;
		$entityPath = ROOT . DS . 'src' . DS . 'Model' . DS . 'Entity' . DS;
		$tables = $this->tables('', $tablePath);
		$entities = $this->entities('', $entityPath);
		$result['app'] = $this->comparison($tables, $entities, $dbTables);

		foreach ($plugins as $plugin) {
			if (in_array($plugin, $this->getConfig('blacklist'), true)) {
				continue;
			}

			$path = Plugin::path($plugin);
			$tablePath = $path . 'src' . DS . 'Model' . DS . 'Table' . DS;
			$entityPath = $path . 'src' . DS . 'Model' . DS . 'Entity' . DS;

			$tables = $this->tables($plugin, $tablePath);
			$entities = $this->entities($plugin, $entityPath);

			$pluginResult = $this->comparison($entities, $tables, $dbTables, true);
			if (!$pluginResult) {
				continue;
			}

			$result[$plugin] = $pluginResult;
		}

		return $result;
	}

	/**
	 * @param array<string> $plugins
	 * @return array
	 */
	public function fixtureComparison(array $plugins): array {
		$result = [];

		$fixturePath = ROOT . DS . 'tests' . DS . 'Fixture' . DS;
		$tablePath = ROOT . DS . 'src' . DS . 'Model' . DS . 'Table' . DS;
		$fixtures = $this->fixtures('', $fixturePath);
		$tables = $this->tables('', $tablePath);
		$fixtureFactories = $this->fixtureFactories('', ROOT . DS . 'tests' . DS . 'Factory' . DS);

		$result['app'] = $this->fixtureResult($fixtures, $tables, $fixtureFactories);

		foreach ($plugins as $plugin) {
			if (in_array($plugin, $this->getConfig('blacklist'), true)) {
				continue;
			}

			$path = Plugin::path($plugin);
			$fixturePath = $path . 'tests' . DS . 'Fixture' . DS;
			$tablePath = $path . 'src' . DS . 'Model' . DS . 'Table' . DS;

			$fixtures = $this->fixtures($plugin, $fixturePath);
			$tables = $this->tables($plugin, $tablePath);

			$pluginResult = $this->fixtureResult($fixtures, $tables);
			if (!$pluginResult) {
				continue;
			}

			$result[$plugin] = $pluginResult;
		}

		return $result;
	}

	/**
	 * @param string $plugin
	 * @param string $path
	 *
	 * @return array
	 */
	protected function fixtures(string $plugin, string $path): array {
		if (!is_dir($path)) {
			return [];
		}

		$result = [];

		$iterator = new DirectoryIterator($path);
		foreach ($iterator as $file) {
			if ($file->isDot()) {
				continue;
			}

			$className = $file->getFileInfo()->getBasename('.php');
			$fullClassName = ($plugin ?: Configure::read('App.namespace')) . '\\Test\\Fixture\\' . $className;
			try {
				/** @var \Cake\TestSuite\Fixture\TestFixture $fixture */
				$fixture = new $fullClassName();
				$table = $fixture->table;
			} catch (Exception $exception) {
				$table = '';
			} catch (Throwable $exception) {
				$table = '';
			}

			$name = substr($className, 0, -strlen('Fixture'));
			$result[$name] = $table;
		}

		return $result;
	}

	/**
	 * @param string $plugin
	 * @param string $path
	 *
	 * @return array
	 */
	protected function tables(string $plugin, string $path): array {
		if (!is_dir($path)) {
			return [];
		}

		$result = [];

		$iterator = new DirectoryIterator($path);
		foreach ($iterator as $file) {
			if ($file->isDot() || !preg_match('/.+Table.php$/', (string)$file)) {
				continue;
			}

			$tableName = $file->getFileInfo()->getBasename('Table.php');
			$fullTableName = ($plugin ? $plugin . '.' : '') . $tableName;
			$entity = null;
			try {
				/** @var \Cake\ORM\Table $tableObject */
				$tableObject = $this->getController()->getTableLocator()->get($fullTableName);
				$table = $tableObject->getTable();
				$entityClass = $tableObject->getEntityClass();
				$entity = $this->shortEntityName($entityClass);

			} catch (Exception $exception) {
				$table = '';
			} catch (Throwable $exception) {
				$table = '';
			}

			$result[$tableName] = [
				'dbTable' => $table,
				'entity' => $entity,
			];
		}

		return $result;
	}

	/**
	 * @param string $plugin
	 * @param string $path
	 *
	 * @return array
	 */
	protected function entities(string $plugin, string $path): array {
		if (!is_dir($path)) {
			return [];
		}

		$result = [];

		$iterator = new DirectoryIterator($path);
		foreach ($iterator as $file) {
			if ($file->isDot() || !preg_match('/.+.php$/', (string)$file)) {
				continue;
			}

			$name = $file->getFileInfo()->getBasename('.php');
			$fullName = ($plugin ? $plugin . '.' : '') . $name;

			$result[$name] = $fullName;
		}

		return $result;
	}

	/**
	 * @param array<string> $fixtures
	 * @param array<string, array<string, string>> $tables
	 * @param array<string, string> $fixtureFactories
	 * @return array
	 */
	protected function fixtureResult(array $fixtures, array $tables, array $fixtureFactories = []): array {
		$result = [];

		$processedTables = [];
		foreach ($fixtures as $fixtureName => $table) {
			$models = array_keys($tables, $table, true);
			$result[$fixtureName] = [
				'table' => $table,
				'models' => $models,
				'factory' => $fixtureFactories[$fixtureName] ?? null,
				'missing' => false,
			];
			$processedTables[] = $table;
		}

		foreach ($tables as $modelName => $tableDetails) {
			if (in_array($tableDetails['dbTable'], $processedTables, true)) {
				continue;
			}

			$result[$modelName] = [
				'table' => $tableDetails['dbTable'],
				'models' => [$modelName],
				'factory' => $fixtureFactories[$tableDetails['entity']] ?? null,
				'missing' => true,
			];
		}

		ksort($result);

		return $result;
	}

	/**
	 * @return array<string, string>
	 */
	protected function dbTables(): array {
		/** @var \Cake\Database\Connection $connection */
		$connection = ConnectionManager::get((string)$this->getConfig('connection'));
		/** @var \Cake\Database\Schema\Collection $schemaCollection */
		$schemaCollection = $connection->getSchemaCollection();
		$tables = $schemaCollection->listTables();

		foreach ($tables as $key => $table) {
			if ($this->shouldSkip($table)) {
				unset($tables[$key]);
			}
		}

		$tables = array_combine($tables, $tables);
		ksort($tables);

		return $tables;
	}

	/**
	 * @param string $table Table name.
	 * @return bool
	 */
	protected function shouldSkip(string $table): bool {
		foreach ($this->getConfig('ignoredDbTables') as $ignore) {
			if (str_starts_with($ignore, '/')) {
				if ((bool)preg_match($ignore, $table)) {
					return true;
				}
			}

			if ($ignore === $table) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string $fqcn
	 * @return string
	 */
	protected function shortEntityName(string $fqcn): string {
		$pattern = '/^(.*)\\\Model\\\Entity\\\([^\\\]+)$/';

		if (preg_match($pattern, $fqcn, $matches)) {
			[$full, $ns, $class] = $matches;

			if ($ns === Configure::read('App.namespace')) {
				return $class;
			}

			$plugin = str_replace('\\', '/', $ns);

			return "{$plugin}.{$class}";
		}

		return $fqcn;
	}

	/**
	 * @param array $tables
	 * @param array $entities
	 * @param array $dbTables
	 * @param bool $ignoreMissing
	 * @return array
	 */
	protected function comparison(array $tables, array $entities, array $dbTables, bool $ignoreMissing = false): array {
		$result = [];

		foreach ($tables as $tableName => $tableDetails) {
			$entity = $tableDetails['entity'] ?? null;
			if (!$entity) {
				$entity = $this->shortEntityName(Configure::read('App.namespace') . '\\Model\\Entity\\' . $tableName);
			}

			if (!is_array($tableDetails)) {
				continue;
			}

			$result[$tableName] = [
				'table' => $tableName,
				'dbTable' => $tableDetails['dbTable'],
				'entity' => $entity,
			];

			unset($entities[$entity]);
			unset($dbTables[$tableDetails['dbTable']]);
		}

		if (!$ignoreMissing) {
			foreach ($entities as $entityName => $entity) {
				$result[Inflector::camelize($entityName)] = [
					'table' => '',
					'dbTable' => '',
					'entity' => $entity,
				];
			}
			foreach ($dbTables as $tableName => $table) {
				$result[Inflector::camelize($tableName)] = [
					'table' => '',
					'dbTable' => $tableName,
					'entity' => '',
				];
			}
		}

		ksort($result);

		return $result;
	}

	/**
	 * @param string $plugin
	 * @param string $path
	 * @return array<string, string>
	 */
	protected function fixtureFactories(string $plugin, string $path): array {
		if (!is_dir($path)) {
			return [];
		}

		$result = [];

		$iterator = new DirectoryIterator($path);
		foreach ($iterator as $file) {
			if ($file->isDot()) {
				continue;
			}

			$className = $file->getFileInfo()->getBasename('.php');
			$name = substr($className, 0, -strlen('Factory'));
			$result[$name] = $name;
		}

		return $result;
	}

}
