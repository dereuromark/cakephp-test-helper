<?php

declare(strict_types=1);

namespace TestHelper\Command;

use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;
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
		$fix = (bool)$args->getOption('fix');

		$totalIssues = 0;
		$autoFixableIssues = 0;
		foreach ($tasks as $task) {
			$io->out('');
			$io->out("<info>Running task: {$task->name()}</info>");
			$io->verbose("  {$task->description()}");

			$options = [
				'fix' => $fix,
				'verbose' => $io->level() >= ConsoleIo::VERBOSE,
			];
			if ($pathsArray !== null) {
				$options['paths'] = $pathsArray;
			}

			// Show paths in verbose mode
			$checkPaths = $pathsArray ?? $task->defaultPaths();
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

}
