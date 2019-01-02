<?php
namespace TestHelper\Shell;

use Cake\Console\Shell;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Database\Exception;
use Cake\Datasource\ConnectionManager;
use Cake\Error\Debugger;
use Cake\Filesystem\Folder;
use Cake\ORM\Table;

if (!defined('TESTS')) {
	define('TESTS', ROOT . DS . 'tests' . DS);
}

/**
 * A CakePHP Shell to compare fixtures against a live DB
 *
 * Examples:
 *
 * ```
 * FixtureCheck -p Users -f Users,Roles -c live
 * ```
 *
 * The example above will check for fixtures named Users and Roles in the plugin
 * Users against the live connection.
 *
 * @author Florian Krämer
 * @author Mark Scherer
 * @copyright PSA Publishers Ltd
 * @license MIT
 */
class FixtureCheckShell extends Shell {

	/**
	 * Configuration read from Configure
	 *
	 * @var array
	 */
	protected $_config = [
		'ignoreClasses' => [],
	];

	/**
	 * Collect differences which where detected
	 *
	 * @var array
	 */
	protected $_issuesFound = [];

	/**
	 * @inheritDoc
	 */
	public function initialize() {
		parent::initialize();
		$this->_config = (array)Configure::read('FixtureCheck') + $this->_config;
	}

	/**
	 * @return void
	 */
	public function main() {
		$this->diff();
	}

	/**
	 * @return void
	 */
	public function diff() {
		$fixtures = $this->_getFixtures();
		$this->out(count($fixtures) . ' fixtures found, processing:');
		$this->out();

		$connection = ConnectionManager::get($this->param('connection'));
		$namespace = 'App';
		$plugin = $this->param('plugin');
		if ($plugin) {
			$namespace = str_replace('/', '\\', $plugin);
		}

		foreach ($fixtures as $fixture) {
			$fixtureClass = $namespace . '\Test\Fixture\\' . $fixture;
			if (!class_exists($fixtureClass)) {
				$this->err(sprintf('Fixture %s does not exist.', $fixtureClass));
				continue;
			}

			if (in_array($fixtureClass, $this->_config['ignoreClasses'])) {
				continue;
			}

			$fixture = new $fixtureClass();
			$fixtureFields = $fixture->fields;
			$fixtureConstraints = isset($fixtureFields['_constraints']) ? $fixtureFields['_constraints'] : [];
			$fixtureIndexes = isset($fixtureFields['_indexes']) ? $fixtureFields['_indexes'] : [];

			unset(
				$fixtureFields['_options'],
				$fixtureFields['_constraints'],
				$fixtureFields['_indexes']
			);

			try {
				$table = new Table([
					'table' => $fixture->table,
					'connection' => $connection,
				]);

				$this->info(sprintf('Comparing `%s` with table `%s`', $fixtureClass, $fixture->table));

				$liveFields = [];
				$columns = $table->getSchema()->columns();
				foreach ($columns as $column) {
					$liveFields[$column] = $table->getSchema()->getColumn($column);
				}
				$liveIndexes = [];
				$indexes = $table->getSchema()->indexes();
				foreach ($indexes as $index) {
					$liveIndexes[$index] = $table->getSchema()->getIndex($index);
				}
				$liveConstraints = [];
				$constraints = $table->getSchema()->constraints();
				foreach ($constraints as $constraint) {
					$liveConstraints[$constraint] = $table->getSchema()->getConstraint($constraint);
				}

				ksort($fixtureFields);
				ksort($liveFields);

				if ($this->_isType('fields')) {
					$this->_compareFieldPresence($fixtureFields, $liveFields, $fixtureClass, $fixture->table);
					$this->_compareFields($fixtureFields, $liveFields, $fixture->table);
				}
				if ($this->_isType('constraints')) {
					$this->_compareConstraints($fixtureConstraints, $liveConstraints, $fixture->table);
				}
				if ($this->_isType('indexes')) {
					$this->_compareIndexes($fixtureIndexes, $liveIndexes, $fixture->table);
				}
			} catch (Exception $e) {
				$this->err($e->getMessage());
			}
		}

		if (!empty($this->_config['ignoreClasses'])) {
			$this->info('Ignored fixture classes:');
			foreach ($this->_config['ignoreClasses'] as $ignoredFixture) {
				$this->out(' * ' . $ignoredFixture);
			}
		}

		if ($this->_issuesFound) {
			$this->out();
			if ($this->param('direction') === 'fixture') {
				$this->warn('Copy-paste the following for fixture updating:');
				$tables = array_unique($this->_issuesFound);
				foreach ($tables as $table) {
					$params = '-f';
					if ($this->param('plugin')) {
						$params .= ' -p ' . $this->param('plugin');
					}

					$this->info('bin/cake bake fixture ' . $table . ' ' . $params);
				}
			} elseif ($this->param('direction') === 'db') {
				$this->warn('Copy-paste the following for migration updating:');
				$this->warn('... not implemented yet');
			}

			$this->abort('Differences detected, check your fixtures and DB.');
		}

		$this->out();
		$this->success('All fine :)');
	}

	/**
	 * Compare the fields present.
	 *
	 * @param array $fixtureFields The fixtures fields array.
	 * @param array $liveFields The live DB fields
	 * @param string $fixtureTable
	 *
	 * @return void
	 */
	public function _compareFields(array $fixtureFields, array $liveFields, $fixtureTable) {
		// Warn only about relevant fields
		$list = [
			'autoIncrement',
			'default',
			'length',
			'null',
			'precision',
			'type',
			'unsigned',
		];
		if ($this->param('strict')) {
			$list[] = 'collate';
		}

		$errors = [];
		foreach ($fixtureFields as $fieldName => $fixtureField) {
			if (!isset($liveFields[$fieldName])) {
				$errors[] = ' * ' . 'Field ' . $fieldName . ' is missing from the live DB!';
				continue;
			}

			$liveField = $liveFields[$fieldName];
			if (!$this->param('strict') && $liveField['length'] === 4294967295) {
				$liveField['length'] = null;
			}
			if (!$this->param('strict') && $liveField['length'] === 16777215) {
				$liveField['length'] = null;
			}
			if (!$this->param('strict') && $fixtureField['length'] === 4294967295) {
				$fixtureField['length'] = null;
			}
			if (!$this->param('strict') && $fixtureField['length'] === 16777215) {
				$fixtureField['length'] = null;
			}

			if (!$this->param('strict') && $fixtureField['type'] === 'json') {
				$fixtureField['type'] = 'text';
			}

			foreach ($fixtureField as $key => $value) {
				if (!in_array($key, $list)) {
					continue;
				}

				if (!isset($liveField[$key]) && $value !== null) {
					$errors[] = ' * ' . sprintf('Field attribute `%s` is missing from the live DB!', $fieldName . ':' . $key);
					continue;
				}
				if (!isset($liveField[$key])) {
					$liveField[$key] = null;
				}
				if ($liveField[$key] !== $value) {
					$errors[] = ' * ' . sprintf(
						'Field attribute `%s` differs from live DB! (`%s` vs `%s` live)',
						$fieldName . ':' . $key,
						Debugger::exportVar($value, true),
						Debugger::exportVar($liveField[$key], true)
					);
				}
			}
		}

		if (!$errors) {
			return;
		}

		$this->warn('The following field attributes mismatch:');

		$this->out($errors);
		$this->_issuesFound[] = $fixtureTable;
		$this->out($this->nl(0));
	}

	/**
	 * Compare the constraints present.
	 *
	 * @param array $fixtureConstraints
	 * @param array $liveConstraints
	 * @param string $fixtureTable
	 *
	 * @return void
	 */
	public function _compareConstraints(array $fixtureConstraints, array $liveConstraints, $fixtureTable) {
		if (!$fixtureConstraints && !$liveConstraints) {
			return;
		}

		if ($fixtureConstraints === $liveConstraints) {
			return;
		}

		$errors = [];
		foreach ($liveConstraints as $key => $liveConstraint) {
			if (!isset($fixtureConstraints[$key])) {
				$errors[] = ' * ' . sprintf('Constraint %s is missing in fixture, but in live DB.', $this->_buildKey($key, $liveConstraint));
				continue;
			}

			if ($liveConstraint === $fixtureConstraints[$key]) {
				unset($fixtureConstraints[$key]);
				continue;
			}

			$errors[] = ' * ' . sprintf('Live DB constraint %s is not matching fixture one.', $this->_buildKey($key, $liveConstraint));
			unset($fixtureConstraints[$key]);
		}

		foreach ($fixtureConstraints as $key => $fixtureConstraint) {
			$errors[] = ' * ' . sprintf('Constraint %s is in fixture, but not live DB.', $this->_buildKey($key, $fixtureConstraint));
		}

		if (!$errors) {
			return;
		}

		$this->warn('The following constraints mismatch:');

		$this->out($errors);
		$this->_issuesFound[] = $fixtureTable;
		$this->out($this->nl(0));
	}

	/**
	 * Compare the indexes present.
	 *
	 * @param array $fixtureIndexes
	 * @param array $liveIndexes
	 * @param string $fixtureTable
	 *
	 * @return void
	 */
	public function _compareIndexes(array $fixtureIndexes, array $liveIndexes, $fixtureTable) {
		if (!$fixtureIndexes && !$liveIndexes) {
			return;
		}

		if ($fixtureIndexes === $liveIndexes) {
			return;
		}

		$errors = [];
		foreach ($liveIndexes as $key => $liveIndex) {
			if (!isset($fixtureIndexes[$key])) {
				$errors[] = ' * ' . sprintf('Index %s is missing in fixture', $this->_buildKey($key, $liveIndex));
				continue;
			}

			if ($liveIndex === $fixtureIndexes[$key]) {
				unset($fixtureIndexes[$key]);
				continue;
			}

			$errors[] = ' * ' . sprintf('Live DB index %s is not matching fixture one.', $this->_buildKey($key, $liveIndex));
			unset($fixtureIndexes[$key]);
		}

		foreach ($fixtureIndexes as $key => $fixtureIndex) {
			$errors[] = ' * ' . sprintf('Index %s is in fixture, but not live DB.', $this->_buildKey($key, $fixtureIndex));
		}

		if (!$errors) {
			return;
		}

		$this->warn('The following indexes mismatch:');

		$this->out($errors);
		$this->_issuesFound[] = $fixtureTable;
		$this->out($this->nl(0));
	}

	/**
	 * Get the fixture files
	 *
	 * @return array
	 */
	protected function _getFixtures() {
		$fixtures = $this->_getFixturesFromOptions();
		if ($fixtures) {
			return $fixtures;
		}

		return $this->_getFixtureFiles();
	}

	/**
	 * Compare if the fields are present in the fixtures.
	 *
	 * @param array $one Array one
	 * @param array $two Array two
	 * @param string $fixtureClass Fixture class name.
	 * @param string $message Message to display.
	 * @param string $fixtureTable
	 *
	 * @return void
	 */
	protected function _doCompareFieldPresence($one, $two, $fixtureClass, $message, $fixtureTable) {
		$diff = array_diff_key($one, $two);
		if (!empty($diff)) {
			$this->warn(sprintf($message, $fixtureClass));
			foreach ($diff as $missingField => $type) {
				$this->out(' * ' . $missingField);
			}
			$this->out($this->nl(0));
			$this->_issuesFound[] = $fixtureTable;
		}
	}

	/**
	 * @return array|bool
	 */
	protected function _getFixturesFromOptions() {
		$fixtureString = $this->param('fixtures');
		if (!empty($fixtureString)) {
			$fixtures = explode(',', $fixtureString);
			foreach ($fixtures as $key => $fixture) {
				$fixtures[$key] = $fixture . 'Fixture';
			}
			return $fixtures;
		}

		return false;
	}

	/**
	 * Gets a list of fixture files.
	 *
	 * @return array Array of fixture files
	 */
	protected function _getFixtureFiles() {
		$fixtureFolder = TESTS . 'Fixture' . DS;
		$plugin = $this->param('plugin');
		if ($plugin) {
			$fixtureFolder = Plugin::path($plugin) . 'tests' . DS . 'Fixture' . DS;
		}

		$folder = new Folder($fixtureFolder);
		$content = $folder->read();

		$fixtures = [];
		foreach ($content[1] as $file) {
			$fixture = substr($file, 0, -4);
			if (substr($fixture, -7) !== 'Fixture') {
				continue;
			}
			$fixtures[] = $fixture;
		}

		return $fixtures;
	}

	/**
	 * Compare the fields present.
	 *
	 * @param array $fixtureFields The fixtures fields array.
	 * @param array $liveFields The live DB fields
	 * @param string $fixtureClass Fixture class name.
	 * @param string $fixtureTable
	 *
	 * @return void
	 */
	protected function _compareFieldPresence($fixtureFields, $liveFields, $fixtureClass, $fixtureTable) {
		$message = '%s has fields that are not in the live DB:';
		$this->_doCompareFieldPresence($fixtureFields, $liveFields, $fixtureClass, $message, $fixtureTable);

		$message = 'Live DB has fields that are not in %s';
		$this->_doCompareFieldPresence($liveFields, $fixtureFields, $fixtureClass, $message, $fixtureTable);
	}

	/**
	 * @inheritDoc
	 */
	public function getOptionParser() {
		return parent::getOptionParser()
			->setDescription('Compare DB and fixture schema columns.')
			->addOption('connection', [
				'short' => 'c',
				'default' => 'default',
				'help' => 'Connection to compare against.',
			])
			->addOption('plugin', [
				'short' => 'p',
				'help' => 'Plugin',
			])
			->addOption('direction', [
				'short' => 'd',
				'default' => 'fixture',
				'help' => 'Direction of diff generation: `fixture` or `db`.',
			])
			->addOption('type', [
				'short' => 't',
				'default' => null,
				'help' => 'Type to run: fields, constraints, indexes (none = all).',
			])
			->addOption('strict', [
				'short' => 's',
				'boolean' => true,
				'help' => 'Strict comparison (including collate and text type/length).',
			])
			->addOption('fixtures', [
				'help' => 'Fixtures to check (comma separated list).',
				'short' => 'f',
				'default' => null,
			]);
	}

	/**
	 * @param string $string
	 * @return bool
	 */
	protected function _isType($string) {
		$map = [
			'f' => 'fields',
			'c' => 'constraints',
			'i' => 'indexes',
		];
		if (!$this->param('type')) {
			return true;
		}

		$types = explode(',', $this->param('type'));

		$whitelist = [];
		foreach ($types as $type) {
			if (!isset($map[$type]) && !in_array($type, $map, true)) {
				continue;
			}
			$whitelist[] = isset($map[$type]) ? $map[$type] : $type;
		}

		return in_array($string, $whitelist, true);
	}

	/**
	 * @param string $key
	 * @param array $field
	 *
	 * @return string
	 */
	protected function _buildKey($key, array $field) {
		$details = json_encode($field);

		return '"' . $key . '" ' . $details . '';
	}

}
