<?php

namespace TestHelper\Controller;

use App\Controller\AppController;
use Cake\Datasource\ConnectionManager;
use Cake\Event\EventInterface;
use SebastianBergmann\Diff\Differ;
use SebastianBergmann\Diff\Output\DiffOnlyOutputBuilder;
use Shim\Filesystem\Folder;

/**
 * @property \TestHelper\Controller\Component\MigrationsComponent $Migrations
 */
class MigrationsController extends AppController {

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

		if ($this->components()->has('Security')) {
			$this->components()->get('Security')->setConfig('validatePost', false);
		}

		if ($this->components()->has('Auth') && method_exists($this->components()->get('Auth'), 'allow')) {
			$this->components()->get('Auth')->allow();
		} elseif ($this->components()->has('Authentication') && method_exists($this->components()->get('Authentication'), 'addUnauthenticatedActions')) {
			$this->components()->get('Authentication')->addUnauthenticatedActions(['index']);
		}

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
		$x = (new Folder(CONFIG . 'MigrationsTmp'))->read();
		$files = $x[1] ?? [];
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

		$x = (new Folder(CONFIG . 'MigrationsTmp'))->read();
		$files = $x[1] ?? [];

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

		$x = (new Folder(CONFIG . 'Seeds'))->read();
		$seeds = $x[1] ?? [];

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
			$folder = (new Folder(CONFIG . 'Migrations'))->read();
			$files = $folder[1] ?? [];
			foreach ($files as $file) {
				unlink(CONFIG . 'Migrations' . DS . $file);
			}
			$folder = (new Folder(CONFIG . 'MigrationsTmp'))->read();
			$files = $folder[1] ?? [];
			foreach ($files as $file) {
				copy(CONFIG . 'MigrationsTmp' . DS . $file, CONFIG . 'Migrations' . DS . $file);
				unlink(CONFIG . 'MigrationsTmp' . DS . $file);
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

}
