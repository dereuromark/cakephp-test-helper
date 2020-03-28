<?php

namespace TestHelper\Controller\Component;

use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use DirectoryIterator;
use Exception;
use Throwable;

class TestFixturesComponent extends Component {

	/**
	 * @var array
	 */
	protected $_defaultConfig = [
		'blacklist' => [
			'DebugKit',
		],
	];

	/**
	 * @param string[] $plugins
	 * @return array
	 */
	public function all(array $plugins): array {
		$result = [];
		foreach ($plugins as $plugin) {
			if (in_array($plugin, $this->getConfig('blacklist'), true)) {
				continue;
			}

			$path = Plugin::path($plugin);
			$fixturePath = $path . 'tests' . DS . 'Fixture' . DS;
			$tablePath = $path . 'src' . DS . 'Model' . DS . 'Table' . DS;

			$fixtures = $this->fixtures($plugin, $fixturePath);
			$tables = $this->tables($plugin, $tablePath);

			$pluginResult = $this->result($fixtures, $tables);
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
	protected function fixtures($plugin, $path) {
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
	protected function tables($plugin, $path) {
		if (!is_dir($path)) {
			return [];
		}

		$result = [];

		$iterator = new DirectoryIterator($path);
		foreach ($iterator as $file) {
			if ($file->isDot()) {
				continue;
			}

			$tableName = $file->getFileInfo()->getBasename('Table.php');
			$fullTableName = ($plugin ? $plugin . '.' : '') . $tableName;
			try {
				/** @var \Cake\ORM\Table $tableObject */
				$tableObject = $this->getController()->getTableLocator()->get($fullTableName);
				$table = $tableObject->getTable();
			} catch (Exception $exception) {
				$table = '';
			} catch (Throwable $exception) {
				$table = '';
			}

			$result[$tableName] = $table;
		}

		return $result;
	}

	/**
	 * @param string[] $fixtures
	 * @param string[] $tables
	 *
	 * @return array
	 */
	protected function result(array $fixtures, array $tables) {
		$result = [];

		$processedTables = [];
		foreach ($fixtures as $fixtureName => $table) {
			$models = array_keys($tables, $table, true);
			$result[$fixtureName] = [
				'table' => $table,
				'models' => $models,
				'missing' => false,
			];
			$processedTables[] = $table;
		}

		foreach ($tables as $modelName => $table) {
			if (in_array($table, $processedTables, true)) {
				continue;
			}

			$result[$modelName] = [
				'table' => $table,
				'models' => [$modelName],
				'missing' => true,
			];
		}

		return $result;
	}

}
