# Custom Linter Tasks

The TestHelper plugin provides a flexible linter system for running project-specific code quality checks. Unlike general-purpose tools (phpcs, phpstan, rector), these linters are designed for quick validation of project-specific conventions that don't fit into standard static analysis tools.

## Why Custom Linter Tasks?

* **Project-specific rules**: Enforce conventions unique to your codebase
* **Fast execution**: Simple checks that run in seconds
* **Easy to extend**: Create custom tasks without complex tool configuration
* **Integration-friendly**: Can be run in CI pipelines alongside other quality tools

## Usage

### Basic Commands

Run all available linter tasks:
```bash
bin/cake linter
```

List available tasks:
```bash
bin/cake linter --list
```

Run a specific task:
```bash
bin/cake linter --task use-orm-query
```

Check specific paths:
```bash
bin/cake linter src/,tests/
```

Show paths being checked (verbose mode):
```bash
bin/cake linter -v
```

### Exit Codes

* `0` - All checks passed, no issues found
* `1` - Issues were found

## Included Default Tasks

The plugin includes four default linter tasks that are active by default:

### no-mixed-in-templates

Ensures template variables have specific type annotations, not `mixed`.

**Checks:** `templates/` directory
**Purpose:** Enforce proper type hints in template files for better IDE support and type safety

**Example violation:**
```php
<?php
/**
 * @var mixed $user
 */
?>
```

**Should be:**
```php
<?php
/**
 * @var \App\Model\Entity\User $user
 */
?>
```

### use-orm-query

Checks for incorrect `use Cake\Database\Query;` imports which should be `use Cake\ORM\Query\SelectQuery;`

**Checks:** `src/`, `tests/`, `plugins/` directories
**Purpose:** Enforce correct Query class imports for CakePHP 5.x

**Example violation:**
```php
use Cake\Database\Query;
```

**Should be:**
```php
use Cake\ORM\Query\SelectQuery;
```

### use-base-migration

Flags deprecated `AbstractMigration` and `AbstractSeed` usage, recommending `BaseMigration` and `BaseSeed`

**Checks:** `config/Migrations/`, `config/Seeds/` directories
**Purpose:** Ensure migrations use non-deprecated base classes

**Example violation:**
```php
use Migrations\AbstractMigration;

class CreateUsersTable extends AbstractMigration
```

**Should be:**
```php
use Migrations\BaseMigration;

class CreateUsersTable extends BaseMigration
```

### single-request-per-test

Validates that controller test methods contain only one `get()` or `post()` call

**Checks:** `tests/TestCase/Controller/` directory
**Purpose:** Enforce test isolation - each test method should test a single request

**Example violation:**
```php
public function testUserFlow(): void
{
    $this->get('/users/login');
    $this->post('/users/login', ['username' => 'test']);  // Second request - violation!
}
```

**Should be:**
```php
public function testLoginPage(): void
{
    $this->get('/users/login');
}

public function testLoginSubmit(): void
{
    $this->post('/users/login', ['username' => 'test']);
}
```

## Creating Custom Tasks

### Step 1: Create Task Class

Create your linter task in `src/Command/Linter/Task/`:

```php
<?php
declare(strict_types=1);

namespace App\Command\Linter\Task;

use Cake\Console\ConsoleIo;
use TestHelper\Command\Linter\AbstractLinterTask;

class MyCustomTask extends AbstractLinterTask
{
    /**
     * Task name (used for --task option)
     */
    public function name(): string
    {
        return 'my-custom-task';
    }

    /**
     * Task description (shown in --list)
     */
    public function description(): string
    {
        return 'Check for custom project conventions';
    }

    /**
     * Default paths to check
     */
    public function defaultPaths(): array
    {
        return ['src/', 'tests/'];
    }

    /**
     * Run the linter task
     *
     * @param \Cake\Console\ConsoleIo $io Console IO
     * @param array<string, mixed> $options Options including paths, fix mode
     * @return int Number of issues found
     */
    public function run(ConsoleIo $io, array $options = []): int
    {
        $paths = $options['paths'] ?? $this->defaultPaths();
        $files = $this->getFiles($paths, '*.php');
        $issues = 0;

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            // Your custom validation logic here
            $lines = explode("\n", $content);
            foreach ($lines as $lineNumber => $line) {
                // Example: Check for TODO comments
                if (preg_match('/TODO:/i', $line)) {
                    $this->outputIssue(
                        $io,
                        $file,
                        $lineNumber + 1,
                        'TODO comment found',
                        trim($line)
                    );
                    $issues++;
                }
            }
        }

        return $issues;
    }
}
```

### Step 2: Register Task (Optional)

Tasks in `App\Command\Linter\Task\` are **not** auto-discovered. You must register them in configuration:

```php
// config/app.php
return [
    'TestHelper' => [
        'Linter' => [
            'tasks' => [
                \App\Command\Linter\Task\MyCustomTask::class,
            ],
        ],
    ],
];
```

### Step 3: Run Your Task

```bash
bin/cake linter --task my-custom-task
```

## Configuration

Configure tasks in `config/app.php`:

```php
return [
    'TestHelper' => [
        'Linter' => [
            'tasks' => [
                // Add custom tasks (simple list)
                \App\Command\Linter\Task\MyCustomTask::class,
                \App\Command\Linter\Task\AnotherTask::class,

                // Disable default tasks (associative array with false)
                \TestHelper\Command\Linter\Task\UseOrmQueryTask::class => false,
                \TestHelper\Command\Linter\Task\SingleRequestPerTestTask::class => false,
            ],
        ],
    ],
];
```

### Configuration Behavior

* **Default tasks** are always loaded unless explicitly disabled with `=> false`
* **Custom tasks** must be registered to be available
* **Simple list format** (`[Task::class]`) adds tasks
* **Associative format** (`[Task::class => false]`) disables tasks

## Helper Methods

The `AbstractLinterTask` base class provides helpful methods:

### getFiles()

Scan directories for files matching a pattern:

```php
$files = $this->getFiles(['src/', 'tests/'], '*.php');
```

### outputIssue()

Report an issue with file path, line number, and context:

```php
$this->outputIssue(
    $io,
    $file,          // File path
    $lineNumber,    // Line number (1-indexed)
    'Issue description',
    'Code context'  // Optional: the problematic code
);
```

### resolvePath()

Convert relative paths to absolute:

```php
$fullPath = $this->resolvePath('src/Controller');
// Returns: /var/www/app/src/Controller
```

### getRelativePath()

Convert absolute paths to relative (from ROOT):

```php
$relativePath = $this->getRelativePath('/var/www/app/src/Controller/UsersController.php');
// Returns: src/Controller/UsersController.php
```

## Advanced Examples

### Checking for Deprecated Functions

```php
public function run(ConsoleIo $io, array $options = []): int
{
    $paths = $options['paths'] ?? $this->defaultPaths();
    $files = $this->getFiles($paths, '*.php');
    $issues = 0;

    $deprecated = [
        'mysql_query',
        'ereg',
        'split',
    ];

    foreach ($files as $file) {
        $content = file_get_contents($file);
        $lines = explode("\n", $content);

        foreach ($lines as $lineNum => $line) {
            foreach ($deprecated as $func) {
                if (preg_match("/\b{$func}\s*\(/", $line)) {
                    $this->outputIssue(
                        $io,
                        $file,
                        $lineNum + 1,
                        "Deprecated function '{$func}' found",
                        trim($line)
                    );
                    $issues++;
                }
            }
        }
    }

    return $issues;
}
```

### Checking File Naming Conventions

```php
public function run(ConsoleIo $io, array $options = []): int
{
    $paths = $options['paths'] ?? $this->defaultPaths();
    $files = $this->getFiles($paths, '*.php');
    $issues = 0;

    foreach ($files as $file) {
        $basename = basename($file, '.php');

        // Controller files must end with "Controller"
        if (str_contains($file, '/Controller/') && !str_ends_with($basename, 'Controller')) {
            $this->outputIssue(
                $io,
                $file,
                1,
                "Controller file must end with 'Controller' suffix",
                "Found: {$basename}.php"
            );
            $issues++;
        }
    }

    return $issues;
}
```

### Validating Documentation

```php
public function run(ConsoleIo $io, array $options = []): int
{
    $paths = $options['paths'] ?? $this->defaultPaths();
    $files = $this->getFiles($paths, '*.php');
    $issues = 0;

    foreach ($files as $file) {
        $content = file_get_contents($file);

        // Find all public methods
        preg_match_all('/public function (\w+)\(/', $content, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[1] as $match) {
            $methodName = $match[0];
            $position = $match[1];

            // Check if there's a docblock before this method
            $beforeMethod = substr($content, 0, $position);
            if (!preg_match('/\/\*\*.*?\*\/\s*$/s', $beforeMethod)) {
                $line = substr_count($beforeMethod, "\n") + 1;
                $this->outputIssue(
                    $io,
                    $file,
                    $line,
                    "Public method '{$methodName}()' missing docblock",
                );
                $issues++;
            }
        }
    }

    return $issues;
}
```

## Integration with CI/CD

Add to your CI pipeline to enforce code quality:

```yaml
# .github/workflows/ci.yml
- name: Run Linter
  run: bin/cake linter
```

Or in composer scripts:

```json
{
    "scripts": {
        "check": [
            "phpcs",
            "phpstan",
            "bin/cake linter"
        ]
    }
}
```

## Troubleshooting

### Task Not Found

If you get "Task not found", ensure:
1. Task class is registered in `TestHelper.Linter.tasks` config
2. Task implements `LinterTaskInterface`
3. Task is in correct namespace

### No Files Checked

If verbose mode shows no files:
```bash
bin/cake linter -v
```

Check that:
1. Default paths exist in your project
2. Paths are correct (relative to ROOT)
3. File pattern matches files (`*.php` by default)

### Performance Issues

If linting is slow:
* Limit paths checked: `bin/cake linter src/Controller/`
* Check fewer files with specific task: `--task single-request-per-test`
* Consider caching results or running specific tasks in CI

## Best Practices

1. **Keep tasks focused**: Each task should check one specific thing
2. **Provide context**: Use `outputIssue()` with code context for better debugging
3. **Use verbose mode**: Document paths in verbose output
4. **Fast execution**: Aim for tasks that complete in seconds
5. **Clear names**: Use descriptive task names (`use-orm-query`, not `query-check`)
6. **Document rules**: Add clear descriptions explaining what each task checks
7. **Test your tasks**: Write unit tests for custom linter tasks
8. **CI integration**: Run linters in CI to catch issues early

## See Also

* [Main README](../README.md)
* [CakePHP Commands Documentation](https://book.cakephp.org/5/en/console-commands.html)
* [PHPStan](https://phpstan.org/) - For complex static analysis
* [PHP CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) - For code style
