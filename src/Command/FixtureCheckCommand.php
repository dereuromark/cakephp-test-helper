<?php

namespace TestHelper\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Core\Exception\CakeException;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\Error\Debugger;
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
class FixtureCheckCommand extends Command {

	/**
	 * @return string
	 */
	public static function getDescription(): string {
		return 'Compare DB and fixture schema columns.';
	}

	/**
	 * Configuration read from Configure
	 *
	 * @var array<string, mixed>
	 */
	protected array $_config = [
		'ignoreClasses' => [],
	];

	/**
	 * Collect differences which where detected
	 *
	 * @var array
	 */
	protected array $_issuesFound = [];

	/**
	 * @var array
	 */
	protected array $_missingFields = [];

	/**
	 * @var \Cake\Console\Arguments
	 */
	protected Arguments $args;

	/**
	 * @var \Cake\Console\ConsoleIo
	 */
	protected ConsoleIo $io;

	/**
	 * @inheritDoc
	 */
	public function initialize(): void {
		parent::initialize();

		$this->_config = (array)Configure::read('FixtureCheck') + $this->_config;
	}

	/**
	 * @phpcsSuppress SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingNativeTypeHint
	 * @param \Cake\Console\Arguments $args The command arguments.
	 * @param \Cake\Console\ConsoleIo $io The console io
	 * @return int|null|void The exit code or null for success
	 */
	public function execute(Arguments $args, ConsoleIo $io) {
		parent::execute($args, $io);

		$this->args = $args;
		$this->io = $io;

		$fixtures = $this->_getFixtures();
		$this->io->out(count($fixtures) . ' fixtures found, processing:');
		$this->io->out('');

		$connection = ConnectionManager::get((string)$this->args->getOption('connection') ?: 'default');
		$namespace = 'App';
		/** @var string|null $plugin */
		$plugin = $this->args->getOption('plugin');
		if ($plugin) {
			$namespace = str_replace('/', '\\', $plugin);
		}

		foreach ($fixtures as $fixture) {
			$fixtureClass = $namespace . '\Test\Fixture\\' . $fixture;
			if (!class_exists($fixtureClass)) {
				$this->io->error(sprintf('Fixture %s does not exist.', $fixtureClass));

				continue;
			}

			if (in_array($fixtureClass, $this->_config['ignoreClasses'])) {
				continue;
			}

			/** @var \Cake\TestSuite\Fixture\TestFixture $fixture */
			$fixture = new $fixtureClass();
			$fixtureFields = [];
			if (property_exists($fixture, 'fields')) {
				$fixtureFields = $fixture->fields;
			}
			$fixtureConstraints = $fixtureFields['_constraints'] ?? [];
			$fixtureIndexes = $fixtureFields['_indexes'] ?? [];

			unset(
				$fixtureFields['_options'],
				$fixtureFields['_constraints'],
				$fixtureFields['_indexes'],
			);

			try {
				$table = new Table([
					'table' => $fixture->table,
					'connection' => $connection,
				]);

				$this->io->info(sprintf('Comparing `%s` with table `%s`', $fixtureClass, $fixture->table));

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
			} catch (CakeException $e) {
				$this->io->error($e->getMessage());
			}
		}

		if (!empty($this->_config['ignoreClasses'])) {
			$this->io->info('Ignored fixture classes:');
			foreach ($this->_config['ignoreClasses'] as $ignoredFixture) {
				$this->io->out(' * ' . $ignoredFixture);
			}
		}

		if ($this->_issuesFound) {
			$this->io->out('');
			if ($this->args->getOption('direction') === 'fixture') {
				$this->io->warning('Copy-paste the following for fixture updating:');
				$tables = array_unique($this->_issuesFound);
				foreach ($tables as $table) {
					$params = '-f';
					if ($this->args->getOption('plugin')) {
						$params .= ' -p ' . $this->args->getOption('plugin');
					}

					$this->io->info('bin/cake bake fixture ' . $table . ' ' . $params);
				}
			} elseif ($this->args->getOption('direction') === 'db') {
				$this->io->warning('Copy-paste the following for migration updating:');
				$this->io->warning('... not implemented yet');
			}

			$this->io->abort('Differences detected, check your fixtures and DB.');
		}

		$this->io->out('');
		$this->io->success('All fine :)');
	}

	/**
	 * Compare the fields present.
	 *
	 * @param array<string, mixed> $fixtureFields The fixtures fields array.
	 * @param array<string, mixed> $liveFields The live DB fields
	 * @param string $fixtureTable
	 *
	 * @return void
	 */
	protected function _compareFields(array $fixtureFields, array $liveFields, $fixtureTable) {
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
		if ($this->args->getOption('strict')) {
			$list[] = 'collate';
		}

		$errors = [];
		foreach ($fixtureFields as $fieldName => $fixtureField) {
			if (!empty($this->_missingFields[$fixtureTable]) && in_array($fieldName, $this->_missingFields[$fixtureTable], true)) {
				continue;
			}

			if (!isset($liveFields[$fieldName])) {
				$errors[] = ' * ' . 'Field ' . $fieldName . ' is missing from the live DB!';

				continue;
			}

			$liveField = $liveFields[$fieldName];

			$fixtureField = $this->normalizeField($fixtureField);
			$liveField = $this->normalizeField($liveField);

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
						Debugger::exportVar($value, 5),
						Debugger::exportVar($liveField[$key], 5),
					);
				}
			}
		}

		if (!$errors) {
			return;
		}

		$this->io->warning('The following field attributes mismatch:');

		$this->io->out($errors);
		$this->_issuesFound[] = $fixtureTable;
		$this->io->out($this->io->nl(0));
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
	protected function _compareConstraints(array $fixtureConstraints, array $liveConstraints, $fixtureTable) {
		if (!$fixtureConstraints && !$liveConstraints) {
			return;
		}

		$fixtureConstraints = $this->normalizeConstraints($fixtureConstraints);
		$liveConstraints = $this->normalizeConstraints($liveConstraints);

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

			$errors[] = ' * ' . sprintf('Live DB constraint %s is not matching fixture one: %s.', $this->_buildKey($key, $liveConstraint), json_encode($fixtureConstraints[$key]));
			unset($fixtureConstraints[$key]);
		}

		foreach ($fixtureConstraints as $key => $fixtureConstraint) {
			$errors[] = ' * ' . sprintf('Constraint %s is in fixture, but not live DB.', $this->_buildKey($key, $fixtureConstraint));
		}

		if (!$errors) {
			return;
		}

		$this->io->warning('The following constraints mismatch:');

		$this->io->out($errors);
		$this->_issuesFound[] = $fixtureTable;
		$this->io->out($this->io->nl(0));
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
	protected function _compareIndexes(array $fixtureIndexes, array $liveIndexes, $fixtureTable) {
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

		$this->io->warning('The following indexes mismatch:');

		$this->io->out($errors);
		$this->_issuesFound[] = $fixtureTable;
		$this->io->out($this->io->nl(0));
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
			$this->io->warning(sprintf($message, $fixtureClass));
			foreach ($diff as $missingField => $type) {
				$this->_missingFields[$fixtureTable][] = $missingField;
				$this->io->out(' * ' . $missingField);
			}
			$this->io->out($this->io->nl(0));
			$this->_issuesFound[] = $fixtureTable;
		}
	}

	/**
	 * @return array|null
	 */
	protected function _getFixturesFromOptions() {
		/** @var string|null $fixtureString */
		$fixtureString = $this->args->getOption('fixtures');
		if ($fixtureString) {
			$fixtures = explode(',', $fixtureString);
			foreach ($fixtures as $key => $fixture) {
				$fixtures[$key] = $fixture . 'Fixture';
			}

			return $fixtures;
		}

		return null;
	}

	/**
	 * Gets a list of fixture files.
	 *
	 * @return array Array of fixture files
	 */
	protected function _getFixtureFiles() {
		$fixtureFolder = TESTS . 'Fixture' . DS;
		/** @var string|null $plugin */
		$plugin = $this->args->getOption('plugin');
		if ($plugin) {
			$fixtureFolder = Plugin::path($plugin) . 'tests' . DS . 'Fixture' . DS;
		}

		$fixtures = [];
		if (!is_dir($fixtureFolder)) {
			return $fixtures;
		}

		$files = array_values(array_diff(scandir($fixtureFolder), ['.', '..']));
		foreach ($files as $file) {
			if (!is_file($fixtureFolder . $file) || pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
				continue;
			}

			$fixture = pathinfo($file, PATHINFO_FILENAME);
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
	public function getOptionParser(): ConsoleOptionParser {
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
		if (!$this->args->getOption('type')) {
			return true;
		}

		$types = explode(',', (string)$this->args->getOption('type'));

		$whitelist = [];
		foreach ($types as $type) {
			if (!isset($map[$type]) && !in_array($type, $map, true)) {
				continue;
			}
			$whitelist[] = $map[$type] ?? $type;
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

	/**
	 * @param array $field
	 *
	 * @return array
	 */
	protected function normalizeField(array $field) {
		if ($this->args->getOption('strict')) {
			return $field;
		}

		if (isset($field['length']) && $field['length'] === 4294967295) {
			$field['length'] = null;
		}
		if (isset($field['length']) && $field['length'] === 16777215) {
			$field['length'] = null;
		}
		if ($field['type'] === 'json') {
			$field['type'] = 'text';
		}

		return $field;
	}

	/**
	 * @param array $constaints
	 *
	 * @return array
	 */
	protected function normalizeConstraints(array $constaints): array {
		$defaults = [
			'length' => [],
		];

		foreach ($constaints as $key => $constraint) {
			$constraint += $defaults;

			$constaints[$key] = $constraint;
		}

		return $constaints;
	}

}
