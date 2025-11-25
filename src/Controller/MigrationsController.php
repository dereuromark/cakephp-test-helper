<?php

namespace TestHelper\Controller;

use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\DiffOnlyOutputBuilder;

/**
 * @property \TestHelper\Controller\Component\MigrationsComponent $Migrations
 */
class MigrationsController extends TestHelperAppController {

	protected ?string $defaultTable = '';

	/**
	 * @return void
	 */
	public function initialize(): void {
		parent::initialize();

		$this->loadComponent('TestHelper.Migrations');
	}

	/**
	 * @param \Cake\Event\EventInterface $event
	 * @return void
	 */
	public function beforeFilter(EventInterface $event): void {
		parent::beforeFilter($event);

		if (!file_exists(ROOT . DS . 'vendor/cakephp/migrations/composer.json')) {
			$this->Flash->error('It seems the Migrations plugin is missing.');

			$event->setResult($this->redirect(['controller' => 'TestHelper']));
		}
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function index() {
		$dbConfig = ConnectionManager::getConfig('default');
		$database = $dbConfig['database'] ?? [];

		$tmpDatabase = $database . '_tmp';

		$this->set(compact('database', 'tmpDatabase'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function tmpDb() {
		$dbConfig = ConnectionManager::getConfig('default');
		$database = $dbConfig['database'] ?? [];

		$tmpDatabase = $database . '_tmp';

		/** @var \Cake\Database\Connection $connection */
		$connection = ConnectionManager::get('default');
		$result = $connection->execute('SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = \'' . $tmpDatabase . '\';')->fetch();
		if ($result) {
			return $this->redirect(['action' => 'snapshot']);
		}
		if ($this->request->is('post')) {
			$connection->execute('CREATE DATABASE IF NOT EXISTS ' . $tmpDatabase . ';')->closeCursor();

			$this->Flash->success('Tmp DB created');

			return $this->redirect([]);
		}

		$this->set((compact('database', 'tmpDatabase', 'dbConfig')));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function snapshot() {
		$files = [];
		$migrationsTmpPath = CONFIG . 'MigrationsTmp';
		if (is_dir($migrationsTmpPath)) {
			$files = array_values(array_diff(scandir($migrationsTmpPath), ['.', '..']));
			$files = array_filter($files, fn ($file) => is_file($migrationsTmpPath . DS . $file));
		}

		if ($this->request->is('post') && $this->request->getData('clear')) {
			foreach ($files as $file) {
				unlink(CONFIG . 'MigrationsTmp' . DS . $file);
			}

			$this->Flash->success('Files cleared');

			return $this->redirect([]);
		}

		if ($this->request->is('post') && $this->request->getData('generate')) {
			$command = 'bin/cake bake migration_snapshot ReInit -s MigrationsTmp';
			exec('cd ' . ROOT . ' && ' . $command, $output, $code);
			$this->Flash->info(print_r($output, true) . ' (code ' . $code . ')');
			if ($code === 0) {
				/** @var \Cake\Database\Connection $connection */
				$connection = ConnectionManager::get('default');
				$connection->execute('DELETE FROM phinxlog WHERE `migration_name` = "Tmp";')->closeCursor();

				$this->Flash->success('Tmp Migration file created');

				return $this->redirect(['action' => 'snapshotTest']);
			}

			$this->Flash->error('Something went wrong');
		}

		$this->set(compact('files'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function snapshotTest() {
		$dbConfig = ConnectionManager::getConfig('default');
		$database = $dbConfig['database'] ?? [];
		$tmpDatabase = $database . '_tmp';

		$files = [];
		$migrationsTmpPath = CONFIG . 'MigrationsTmp';
		if (is_dir($migrationsTmpPath)) {
			$files = array_values(array_diff(scandir($migrationsTmpPath), ['.', '..']));
			$files = array_filter($files, fn ($file) => is_file($migrationsTmpPath . DS . $file));
		}

		if ($this->request->is('post') && $this->request->getData('test')) {
			$connectionConfig = [
				'database' => $tmpDatabase,
			] + $dbConfig;
			$connectionName = 'tmp';
			if (!ConnectionManager::getConfig($connectionName)) {
				ConnectionManager::setConfig($connectionName, $connectionConfig);
			}

			/** @var \Cake\Database\Connection $connection */
			$connection = ConnectionManager::get('tmp');

			/** @var \Cake\Database\Schema\Collection $schemaCollection */
			$schemaCollection = $connection->getSchemaCollection();
			$sources = $schemaCollection->listTables();
			if ($sources) {
				$tableTruncates = 'DROP TABLE ' . implode(';' . PHP_EOL . 'DROP TABLE ', $sources) . ';';

				$sql = <<<SQL
SET FOREIGN_KEY_CHECKS = 0;

$tableTruncates

SET FOREIGN_KEY_CHECKS = 1;
SQL;
				$connection->execute($sql);
			}

			$command = 'bin/cake migrations migrate -s MigrationsTmp -c "mysql://root@127.0.0.1/' . $tmpDatabase . '" --no-lock';
			exec('cd ' . ROOT . ' && ' . $command, $output, $code);
			$this->Flash->info(print_r($output, true) . ' (code ' . $code . ')');
			if ($code === 0) {
				$this->Flash->success('Tmp Migration has been run.');

				return $this->redirect(['action' => 'seedTest']);
			}

			$this->Flash->error('Something went wrong');
		}

		$this->set(compact('files'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function seedTest() {
		$dbConfig = ConnectionManager::getConfig('default');
		$database = $dbConfig['database'] ?? [];
		$tmpDatabase = $database . '_tmp';

		$seeds = [];
		$seedsPath = CONFIG . 'Seeds';
		if (is_dir($seedsPath)) {
			$seeds = array_values(array_diff(scandir($seedsPath), ['.', '..']));
			$seeds = array_filter($seeds, fn ($file) => is_file($seedsPath . DS . $file));
		}

		if ($this->request->is('post') && $this->request->getData('test')) {
			$command = 'bin/cake migrations seed -c "mysql://root@127.0.0.1/' . $tmpDatabase . '"';
			exec('cd ' . ROOT . ' && ' . $command, $output, $code);
			$this->Flash->info(print_r($output, true) . ' (code ' . $code . ')');
			if ($code === 0) {
				$this->Flash->success('Seeds have been run successfully.');

				return $this->redirect(['action' => 'confirm']);
			}

			$this->Flash->error('Something went wrong');
		}

		$this->set(compact('seeds'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function confirm() {
		$dbConfig = ConnectionManager::getConfig('default');
		$database = $dbConfig['database'] ?? [];
		$tmpDatabase = $database . '_tmp';

		if ($this->request->is('post') && $this->request->getData('confirm')) {
			// Remove old migration files
			$migrationsPath = CONFIG . 'Migrations';
			if (is_dir($migrationsPath)) {
				$files = array_values(array_diff(scandir($migrationsPath), ['.', '..']));
				$files = array_filter($files, fn ($file) => is_file($migrationsPath . DS . $file));
				foreach ($files as $file) {
					unlink($migrationsPath . DS . $file);
				}
			}

			// Copy tmp migration files to Migrations folder
			$migrationsTmpPath = CONFIG . 'MigrationsTmp';
			if (is_dir($migrationsTmpPath)) {
				$files = array_values(array_diff(scandir($migrationsTmpPath), ['.', '..']));
				$files = array_filter($files, fn ($file) => is_file($migrationsTmpPath . DS . $file));
				foreach ($files as $file) {
					copy($migrationsTmpPath . DS . $file, $migrationsPath . DS . $file);
					unlink($migrationsTmpPath . DS . $file);
				}
			}

			/** @var \Cake\Database\Connection $connection */
			$connection = ConnectionManager::get('default');
			$connection->execute('DELETE FROM phinxlog WHERE 1=1')->closeCursor();

			$command = 'bin/cake migrations mark_migrated';
			exec('cd ' . ROOT . ' && ' . $command, $output, $code);
			if ($code !== 0) {
				$this->Flash->error(print_r($output, true));
			} else {
				$this->Flash->info(implode(';' . PHP_EOL, $output));

				$this->Flash->success('Done!');

				return $this->redirect(['action' => 'cleanup']);
			}
		}

		$contentBefore = $this->Migrations->getSchema($database);
		$contentAfter = $this->Migrations->getSchema($tmpDatabase);

		$differ = new Differ(new DiffOnlyOutputBuilder());
		$diffArray = $differ->diffToArray($contentBefore, $contentAfter);

		$this->set(compact('contentBefore', 'contentAfter', 'diffArray'));
	}

	/**
	 * @return \Cake\Http\Response|null|void
	 */
	public function cleanup() {
	}

	/**
	 * Detect schema drift between migrations and actual database.
	 *
	 * Uses the `test` database as shadow to run migrations and compare
	 * the resulting schema against the actual database.
	 *
	 * @return \Cake\Http\Response|null|void
	 */
	public function driftCheck() {
		$testConfig = ConnectionManager::getConfig('test');
		$defaultConfig = ConnectionManager::getConfig('default');

		$drift = null;
		$error = null;

		// Validate test connection exists and is different from default
		if (!$testConfig) {
			$error = 'No "test" connection configured. A test database is required as shadow database.';
		} else {
			$testDatabase = $testConfig['database'] ?? null;
			$defaultDatabase = $defaultConfig['database'] ?? null;
			if ($testDatabase === $defaultDatabase) {
				$error = 'Test database is the same as default database. This is not safe for drift detection.';
			}
		}

		// Get available connections (exclude test, debug_kit, database_log, etc.)
		$excludedConnections = ['test', 'debug_kit', 'database_log'];
		$availableConnections = [];
		$configured = ConnectionManager::configured();
		foreach ($configured as $name) {
			if (in_array($name, $excludedConnections, true)) {
				continue;
			}
			$config = ConnectionManager::getConfig($name);
			$database = $config['database'] ?? null;
			// Skip if same database as test (would compare to itself)
			if ($testConfig && $database === ($testConfig['database'] ?? null)) {
				continue;
			}
			$availableConnections[] = $name;
		}

		$connectionName = $this->request->getQuery('connection', 'default');
		if (!in_array($connectionName, $availableConnections, true)) {
			$connectionName = $availableConnections[0] ?? 'default';
		}

		$dbConfig = ConnectionManager::getConfig($connectionName);
		$database = $dbConfig['database'] ?? '';
		$shadowDatabase = $testConfig['database'] ?? '';

		$driver = $dbConfig['driver'] ?? '';
		$isPostgres = str_contains($driver, 'Postgres');
		$isMysql = str_contains($driver, 'Mysql');

		if (!$isPostgres && !$isMysql && !$error) {
			$error = 'Drift check currently only supports MySQL and PostgreSQL.';
		}

		/** @var \Cake\Database\Connection $connection */
		$connection = ConnectionManager::get($connectionName);

		// Detect which plugins need migrations by checking *_phinxlog tables in actual DB
		$pluginsToMigrate = [];
		if (!$error) {
			/** @var \Cake\Database\Schema\Collection $actualSchemaCollection */
			$actualSchemaCollection = $connection->getSchemaCollection();
			$actualTables = $actualSchemaCollection->listTables();
			foreach ($actualTables as $table) {
				if (str_ends_with($table, '_phinxlog') && $table !== 'phinxlog') {
					// Check if phinxlog table has entries (migrations were run)
					$count = $connection->execute('SELECT COUNT(*) FROM ' . $table)->fetch();
					if (!$count || $count[0] == 0) {
						continue;
					}

					// Convert table name to plugin name (e.g., queue_phinxlog -> Queue)
					$pluginName = str_replace('_phinxlog', '', $table);
					$pluginName = str_replace('_', '', ucwords($pluginName, '_'));
					$pluginsToMigrate[] = $pluginName;
				}
			}
			sort($pluginsToMigrate);
		}

		// Handle POST actions
		if ($this->request->is('post') && !$error) {
			$action = $this->request->getData('action');

			if ($action === 'run_migrations') {
				/** @var \Cake\Database\Connection $testConnection */
				$testConnection = ConnectionManager::get('test');

				// Clear any existing tables in test DB
				/** @var \Cake\Database\Schema\Collection $schemaCollection */
				$schemaCollection = $testConnection->getSchemaCollection();
				$existingTables = $schemaCollection->listTables();

				if ($existingTables) {
					if ($isMysql) {
						$testConnection->execute('SET FOREIGN_KEY_CHECKS = 0')->closeCursor();
						foreach ($existingTables as $table) {
							$testConnection->execute('DROP TABLE IF EXISTS `' . $table . '`')->closeCursor();
						}
						$testConnection->execute('SET FOREIGN_KEY_CHECKS = 1')->closeCursor();
					} elseif ($isPostgres) {
						foreach ($existingTables as $table) {
							$testConnection->execute('DROP TABLE IF EXISTS "' . $table . '" CASCADE')->closeCursor();
						}
					}
				}

				// Run app migrations on test database
				$command = 'bin/cake migrations migrate -c test --no-lock';
				exec('cd ' . ROOT . ' && ' . $command, $output, $code);

				if ($code !== 0) {
					$error = 'App migration failed: ' . implode("\n", $output);
					$this->Flash->error($error);
				} else {
					// Run plugin migrations for detected plugins
					$pluginMigrationErrors = [];
					foreach ($pluginsToMigrate as $plugin) {
						$pluginCommand = 'bin/cake migrations migrate -p ' . $plugin . ' -c test --no-lock';
						$pluginOutput = [];
						exec('cd ' . ROOT . ' && ' . $pluginCommand, $pluginOutput, $pluginCode);

						if ($pluginCode !== 0) {
							$pluginMigrationErrors[$plugin] = implode("\n", $pluginOutput);
						}
					}

					if ($pluginMigrationErrors) {
						$this->Flash->warning('Some plugin migrations failed: ' . implode(', ', array_keys($pluginMigrationErrors)));
					}

					$migratedCount = count($pluginsToMigrate) - count($pluginMigrationErrors);
					$message = 'App migrations applied to test database.';
					if ($pluginsToMigrate) {
						$message .= ' Plugin migrations: ' . $migratedCount . '/' . count($pluginsToMigrate);
					}
					$this->Flash->success($message);

					return $this->redirect(['?' => ['connection' => $connectionName, 'compare' => '1']]);
				}
			}
		}

		// Compare schemas if requested
		if ($this->request->getQuery('compare') && !$error) {
			/** @var \Cake\Database\Connection $testConnection */
			$testConnection = ConnectionManager::get('test');

			$expectedSchema = $this->Migrations->getStructuredSchema($testConnection);
			$actualSchema = $this->Migrations->getStructuredSchema($connection);

			$drift = $this->Migrations->compareSchemas($expectedSchema, $actualSchema);
		}

		$hasDrift = $drift !== null && $this->Migrations->hasDrift($drift);

		$this->set(compact(
			'availableConnections',
			'connectionName',
			'database',
			'shadowDatabase',
			'pluginsToMigrate',
			'drift',
			'hasDrift',
			'error',
			'isMysql',
			'isPostgres',
		));
	}

}
