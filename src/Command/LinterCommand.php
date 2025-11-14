<?php

declare(strict_types=1);

namespace TestHelper\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\TestSuite\ConnectionHelper;
use ReflectionClass;
use Shim\Command\Command;
use TestHelper\Command\Linter\LinterTaskInterface;
use TestHelper\Command\Linter\Task\ArrayUrlsInControllersTask;
use TestHelper\Command\Linter\Task\ArrayUrlsInTestsTask;
use TestHelper\Command\Linter\Task\DeprecatedFindOptionsTask;
use TestHelper\Command\Linter\Task\DuplicateTemplateAnnotationsTask;
use TestHelper\Command\Linter\Task\NoMixedInTemplatesTask;
use TestHelper\Command\Linter\Task\PostLinkWithinFormsTask;
use TestHelper\Command\Linter\Task\SingleRequestPerTestTask;
use TestHelper\Command\Linter\Task\UseBaseMigrationTask;
use TestHelper\Command\Linter\Task\UseOrmQueryTask;

/**
 * Custom linter command for running project-specific linting tasks.
 *
 * Default tasks are provided by the plugin and can be customized
 * via TestHelper.Linter.tasks configuration.
 */
class LinterCommand extends Command {

	/**
     * The name of this command.
     *
     * @var string
     */
	protected string $name = 'linter';

	/**
     * Default tasks provided by the plugin
     *
     * @var array<string, string>
     */
	protected array $defaultTasks = [
		ArrayUrlsInControllersTask::class => ArrayUrlsInControllersTask::class,
		ArrayUrlsInTestsTask::class => ArrayUrlsInTestsTask::class,
		DeprecatedFindOptionsTask::class => DeprecatedFindOptionsTask::class,
		DuplicateTemplateAnnotationsTask::class => DuplicateTemplateAnnotationsTask::class,
		NoMixedInTemplatesTask::class => NoMixedInTemplatesTask::class,
		PostLinkWithinFormsTask::class => PostLinkWithinFormsTask::class,
		SingleRequestPerTestTask::class => SingleRequestPerTestTask::class,
		UseBaseMigrationTask::class => UseBaseMigrationTask::class,
		UseOrmQueryTask::class => UseOrmQueryTask::class,
	];

	/**
     * Cached tasks
     *
     * @var array<\TestHelper\Command\Linter\LinterTaskInterface>|null
     */
	protected ?array $tasks = null;

	/**
     * Get the command description.
     *
     * @return string
     */
	public static function getDescription(): string {
		return 'Run custom linting tasks for code quality checks.';
	}

	/**
     * Hook method for defining this command's option parser.
     *
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     *
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
	public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser {
		return parent::buildOptionParser($parser)
			->addOption('task', [
				'help' => 'Run a specific task by name or FQCN. If not provided, all tasks will run.',
				'short' => 't',
			])
			->addOption('list', [
				'help' => 'List all available tasks',
				'short' => 'l',
				'boolean' => true,
			])
			->addOption('fix', [
				'help' => 'Auto-fix issues where possible',
				'short' => 'f',
				'boolean' => true,
			])
			->addOption('ci', [
				'help' => 'Enable CI mode (aliases test database connections)',
				'boolean' => true,
			])
			->addOption('plugin', [
				'help' => 'The plugin(s) to run. Defaults to the application otherwise. Supports wildcard `*` for partial match, `all` for all app plugins.',
				'short' => 'p',
			])
			->addArgument('paths', [
				'help' => 'Comma-separated paths to check. If not provided, uses task defaults.',
				'required' => false,
			])
			->setDescription(static::getDescription());
	}

	/**
     * Implement this method with your command's logic.
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     *
     * @return int The exit code
     */
	public function execute(Arguments $args, ConsoleIo $io): int {
		// Enable CI mode if requested
		if ($args->getOption('ci')) {
			ConnectionHelper::addTestAliases();
		}

		$tasks = $this->discoverTasks();

		if ($args->getOption('list')) {
			return $this->listTasks($io, $tasks);
		}

		$taskFilter = (string)$args->getOption('task');
		if ($taskFilter) {
			$tasks = $this->filterTasks($tasks, $taskFilter);
			if (!$tasks) {
				$io->error("Task not found: {$taskFilter}");

				return static::CODE_ERROR;
			}
		}

		$paths = $args->getArgument('paths');
		$pathsArray = $paths ? array_map('trim', explode(',', $paths)) : null;

		// Handle plugin option
		$plugin = $args->getOption('plugin');
		$pluginNames = null;
		if ($plugin) {
			$pluginNames = $this->resolvePlugins((string)$plugin, $io);
			if ($pluginNames === null) {
				return static::CODE_ERROR;
			}
		}

		$fix = (bool)$args->getOption('fix');

		$totalIssues = 0;
		$autoFixableIssues = 0;
		foreach ($tasks as $task) {
			$io->out('');
			$io->out("<info>Running task: {$task->name()}</info>");
			$io->verbose("  {$task->description()}");

			// Skip tasks that don't support plugin mode when --plugin is used
			if ($pluginNames !== null && !$task->supportsPluginMode()) {
				$io->verbose('  Skipped: Task does not support plugin mode');

				continue;
			}

			$options = [
				'fix' => $fix,
				'verbose' => $io->level() >= ConsoleIo::VERBOSE,
			];

			// Determine paths to check
			if ($pathsArray !== null) {
				// User provided explicit paths
				$options['paths'] = $pathsArray;
				$checkPaths = $pathsArray;
			} elseif ($pluginNames !== null) {
				// Plugin mode: prepend plugin paths to task defaults
				$taskPaths = $task->defaultPaths();
				$options['paths'] = $this->prependPluginPaths($pluginNames, $taskPaths);
				$checkPaths = $options['paths'];
			} else {
				// Default: use task's default paths
				$checkPaths = $task->defaultPaths();
			}

			// Show paths in verbose mode
			$io->verbose('  Checking paths: ' . implode(', ', $checkPaths));

			$issues = $task->run($io, $options);
			$totalIssues += $issues;

			if ($issues === 0) {
				$io->success('  ✓ No issues found');
			} else {
				$message = "  ✗ Found {$issues} issue(s)";
				if ($task->supportsAutoFix()) {
					if (!$fix) {
						$message .= ' (auto-fixable with --fix)';
						$autoFixableIssues += $issues;
					}
				}
				$io->warning($message);
			}
		}

		$io->out('');
		$io->out('---');
		if ($totalIssues === 0) {
			$io->success('All linter tasks passed! No issues found.');

			return static::CODE_SUCCESS;
		}

		$message = "Linting failed with {$totalIssues} total issue(s).";
		if ($autoFixableIssues > 0 && !$fix) {
			$message .= " {$autoFixableIssues} can be auto-fixed with --fix.";
		}
		$io->error($message);

		return static::CODE_ERROR;
	}

	/**
     * List all available tasks
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param array<\TestHelper\Command\Linter\LinterTaskInterface> $tasks Tasks
     *
     * @return int Exit code
     */
	protected function listTasks(ConsoleIo $io, array $tasks): int {
		$io->out('<info>Available linter tasks:</info>');
		$io->out('');

		foreach ($tasks as $task) {
			$io->out("  <warning>{$task->name()}</warning>");
			$io->out("    {$task->description()}");
			$io->out('    Class: ' . get_class($task));
			$io->out('    Default paths: ' . implode(', ', $task->defaultPaths()));
			if ($task->supportsAutoFix()) {
				$io->out('    <info>Supports auto-fix</info>');
			}
			if (!$task->supportsPluginMode()) {
				$io->out('    <comment>Does not support plugin mode</comment>');
			}
			$io->out('');
		}

		return static::CODE_SUCCESS;
	}

	/**
     * Filter tasks by name or FQCN
     *
     * @param array<\TestHelper\Command\Linter\LinterTaskInterface> $tasks All tasks
     * @param string $filter Task name or FQCN
     *
     * @return array<\TestHelper\Command\Linter\LinterTaskInterface>
     */
	protected function filterTasks(array $tasks, string $filter): array {
		return array_filter($tasks, function ($task) use ($filter) {
			// Match by name
			if ($task->name() === $filter) {
				return true;
			}

			// Match by FQCN
			if (get_class($task) === $filter) {
				return true;
			}

			// Match by class name (without namespace)
			$shortName = (new ReflectionClass($task))->getShortName();
			if ($shortName === $filter) {
				return true;
			}

			return false;
		});
	}

	/**
     * Get default tasks
     *
     * Merges plugin defaults with configuration.
     * Tasks can be customized via TestHelper.Linter.tasks config.
     *
     * @return array<string, string>
     */
	protected function defaultTasks(): array {
		$tasks = $this->defaultTasks;

		$configuredTasks = (array)Configure::read('TestHelper.Linter.tasks');
		foreach ($configuredTasks as $key => $task) {
			if (is_int($key)) {
				// Simple list: ['Task1', 'Task2']
				$tasks[$task] = $task;
			} else {
				// Associative: ['Task1' => 'Task1', 'Task2' => false]
				if ($task === false) {
					unset($tasks[$key]);
				} else {
					$tasks[$key] = $task;
				}
			}
		}

		return $tasks;
	}

	/**
     * Discover all available linter tasks
     *
     * @return array<\TestHelper\Command\Linter\LinterTaskInterface>
     */
	protected function discoverTasks(): array {
		if ($this->tasks !== null) {
			return $this->tasks;
		}

		$tasks = [];
		$defaultTasks = $this->defaultTasks();

		foreach ($defaultTasks as $class) {
			if (!class_exists($class)) {
				continue;
			}

			$task = new $class();
			if ($task instanceof LinterTaskInterface) {
				$tasks[$class] = $task;
			}
		}

		$this->tasks = $tasks;

		return $tasks;
	}

	/**
     * Resolve plugin names based on plugin option
     *
     * @param string $pluginOption Plugin name, wildcard pattern, or "all"
     * @param \Cake\Console\ConsoleIo $io Console IO
     *
     * @return array<string>|null Array of plugin names or null on error
     */
	protected function resolvePlugins(string $pluginOption, ConsoleIo $io): ?array {
		// Get all app plugins (exclude vendor plugins)
		$loadedPlugins = Plugin::loaded();
		$appPlugins = [];
		foreach ($loadedPlugins as $name) {
			$path = Plugin::path($name);
			$rootPath = str_replace(ROOT . DS, '', $path);
			if (str_starts_with($rootPath, 'vendor' . DS)) {
				continue;
			}
			$appPlugins[] = $name;
		}

		// Handle "all" - all app plugins
		if ($pluginOption === 'all') {
			if (empty($appPlugins)) {
				$io->warning('No app plugins loaded.');

				return [];
			}

			$io->verbose('Checking all plugins: ' . implode(', ', $appPlugins));

			return $appPlugins;
		}

		// Handle wildcard pattern
		if (str_contains($pluginOption, '*')) {
			$matchedPlugins = array_filter($appPlugins, function ($plugin) use ($pluginOption) {
				return fnmatch($pluginOption, $plugin);
			});

			if (empty($matchedPlugins)) {
				$io->error("No plugins found matching pattern '{$pluginOption}'.");
				$io->out('');
				$io->out('Available app plugins:');
				foreach ($appPlugins as $plugin) {
					$io->out("  - {$plugin}");
				}

				return null;
			}

			$io->verbose('Checking plugins matching pattern: ' . implode(', ', $matchedPlugins));

			return $matchedPlugins;
		}

		// Handle specific plugin
		if (!Plugin::isLoaded($pluginOption)) {
			$io->error("Plugin '{$pluginOption}' is not loaded.");
			$io->out('');
			$io->out('Available app plugins:');
			foreach ($appPlugins as $plugin) {
				$io->out("  - {$plugin}");
			}

			return null;
		}

		$io->verbose("Checking plugin: {$pluginOption}");

		return [$pluginOption];
	}

	/**
     * Prepend plugin paths to task paths
     *
     * For each plugin, prepend "plugins/PluginName/" to each task path.
     * Example: ['Board'] + ['templates/', 'src/'] => ['plugins/Board/templates/', 'plugins/Board/src/']
     *
     * @param array<string> $pluginNames Plugin names
     * @param array<string> $taskPaths Task default paths
     *
     * @return array<string> Combined paths
     */
	protected function prependPluginPaths(array $pluginNames, array $taskPaths): array {
		$paths = [];
		foreach ($pluginNames as $pluginName) {
			foreach ($taskPaths as $taskPath) {
				$paths[] = 'plugins/' . $pluginName . '/' . $taskPath;
			}
		}

		return $paths;
	}

}
