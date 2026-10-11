---

description: Install and load TestHelper for a CakePHP development application.
---

# Installation

This development branch requires PHP 8.5 and CakePHP 6. The application must allow development stability for unreleased framework and companion packages. Keep stable packages preferred.

Install the plugin as a development dependency from your CakePHP application's root:

```bash
composer config minimum-stability dev
composer config prefer-stable true
composer require --dev dereuromark/cakephp-test-helper:dev-cake6
```

Composer checks the supported PHP and CakePHP versions. See the [version map](https://github.com/dereuromark/cakephp-test-helper/wiki#cakephp-version-map) for other CakePHP branches.

Load the plugin only while debug is enabled:

```bash
bin/cake plugin load TestHelper --only-debug
```

Keep existing plugin registrations instead of adding a duplicate. This registration enables the plugin's routes under `/test-helper`. The package is development-only and must not be loaded unconditionally after `composer install --no-dev`.

Continue with [Getting started](/guide) to open the dashboard and try a route lookup. Application authentication middleware may require you to sign in first; the plugin's own controller base does not inherit AppController callbacks.

## Optional tools

| Feature | Application dependency or capability |
|---|---|
| Generate tests and fixtures | `cakephp/bake`, loaded for CLI use |
| Run browser-selected tests | A compatible PHPUnit installation and permission for the web PHP process to launch CLI PHP |
| Coverage reports | Xdebug or PCOV enabled for CLI PHP |
| Migration and schema comparison | `cakephp/migrations`, the database driver, and a configured connection |
| Text diffs in migration comparison and recommended plugin classes | `sebastian/diff` |

Development dependencies from this repository are not automatically installed in consuming applications. Install the packages needed by your selected tools, for example:

```bash
composer require --dev 'cakephp/bake:^4.0@dev' cakephp/migrations:dev-fix/6.x-cakephp-compatibility sebastian/diff
```

Register Bake and Migrations as appropriate for the application, preserving other `config/plugins.php` entries. Their development-only registrations must be optional or restricted to debug mode so a production installation without those packages can boot.

The backend executes tests as the web-server user. That process needs access to the test files, configured database, and report output directory. Install PHPUnit using the application's own test setup rather than assuming the plugin supplies it.

## CLI use outside development

If you deliberately need CLI functionality in an environment installed without development dependencies, install TestHelper as a regular dependency and register it with `['routes' => false]`. This keeps its web backend disabled. Do not expose developer routes as part of that registration.

## Custom extensions on CakePHP 6

Custom linter tasks implement `run(ConsoleIoInterface $io, array $options = []): int`. Replace the concrete `ConsoleIo` parameter in existing task overrides with the interface. Custom commands implement `execute(): int` and use the inherited argument and console properties. Tests use PHPUnit 13.
