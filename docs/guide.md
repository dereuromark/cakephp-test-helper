# Overview

TestHelper adds a browseable developer backend at `/test-helper` for your CakePHP app,
collecting several day-to-day tools in one place.

::: tip
Everything here is a development aid — mount it behind your admin/dev gate. See
[Configuration](/Configuration) for the authorization and back-link options.
:::

## Tools

| Tool | What it does |
|------|--------------|
| [Association vs DB Audit](/Associations) | Compare declared associations against the real database foreign keys, with copy-paste fixes |
| [SQL to Query Builder](/SqlConverter) | Convert raw SQL into CakePHP Query Builder code |
| [Test Runner](/TestRunner) | Run tests and view results/coverage in the backend; bake missing test files |
| [Fixture Check](/FixtureCheck) | Compare fixtures against the live DB schema |
| [URL Tools](/UrlTools) | Generate URL arrays from string URLs (reverse lookup) |
| [Plugin Info](/Plugins) | Inspect plugins, their hooks, and suggested improvements |
| [Custom Linter Tasks](/Linter) | Project-specific code-quality checks via `bin/cake linter` |

## Installation

```bash
composer require --dev dereuromark/cakephp-test-helper
```

Load the plugin (development only):

```php
// src/Application.php
public function bootstrap(): void
{
    parent::bootstrap();

    if (Configure::read('debug')) {
        $this->addPlugin('TestHelper');
    }
}
```

Then browse to `/test-helper`.

See [Configuration](/Configuration) for the available options and
[Troubleshooting](/Troubleshooting) if something doesn't render.
