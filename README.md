# CakePHP TestHelper plugin
[![CI](https://github.com/dereuromark/cakephp-test-helper/actions/workflows/ci.yml/badge.svg?branch=master)](https://github.com/dereuromark/cakephp-test-helper/actions/workflows/ci.yml?query=branch%3Amaster)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-test-helper/master.svg)](https://codecov.io/github/dereuromark/cakephp-test-helper/branch/master)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-test-helper/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-test-helper)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.1-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-test-helper/license.svg)](LICENSE)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-test-helper/d/total)](https://packagist.org/packages/dereuromark/cakephp-test-helper)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

Browser based addons for your test driven development.

Note: This branch is for **CakePHP 5.1+**. See [version map](https://github.com/dereuromark/cakephp-test-helper/wiki#cakephp-version-map) for details.

## Motivation
After 2.x=>3.x, the "web-tester" has been removed. It was, for certain cases, however, quite useful.
This aims to bring back a part of it.

The CLI also doesn't allow a good overview. Even with auto-complete, you have to type almost everything out.
With a browser backend generating tests or running them is just a simple mouse click.

You have an overview of your classes and the test classes to it. If there is one missing, you can easily "bake" it from this web backend. It internally uses [Bake](https://github.com/cakephp/bake/) plugin as well as your preferred theme.

### Further useful addons
- URL array generation from string URLs (respects routing, so it is basically also a reverse lookup)
- Fixture validation tool (compares actual DB with the schema files: fields and attributes, constraints and indexes)
- Model/entity/table comparison overview.
- GUI for fixture comparison and generation of missing ones per mouse click.

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org):
```
composer require --dev dereuromark/cakephp-test-helper
```

Note: This is not meant for production, so make sure you use the `--dev` flag and install it as development-only tool.

## Setup

Load the plugin:
```
bin/cake plugin load TestHelper --only-debug
```

This will also load the routes.

### Authorization Plugin

If you are using the [CakePHP Authorization plugin](https://github.com/cakephp/authorization), you need to configure TestHelper to bypass authorization checks. Add this to your `config/bootstrap.php`:

```php
Configure::write('TestHelper.ignoreAuthorization', true);
```

This is similar to how DebugKit handles authorization and is necessary to prevent `AuthorizationRequiredException` errors when accessing TestHelper routes.

### non-dev mode
In certain apps it can be useful to have some of the helper functionality available also for staging and prod.
Here you must make sure then to not load the routes, though:
```php
$this->addPlugin('TestHelper', ['routes' => Configure::read('debug')]);
```
And here you must use `composer require` without `--dev` flag then.

## Usage
Navigate to `/test-helper` backend and select the app or plugin you want to check.
You can then with a single click
- check what classes do not yet have a test case
- generate a test case for them (or copy and paste a generated code into CLI)
- run test case
- check coverage on a tested class, as overall and in detail

Supported class types:

- [x] Controllers
- [x] Models (Tables/Entities)
- [x] Components
- [x] Behavior
- [x] Helpers
- [x] Commands
- [x] Tasks
- [ ] Cells
- [ ] CommandHelpers
- [ ] Forms
- [ ] Mailers

Feel free to help out improving and completing this test helper plugin.

- [Full Documentation](docs/README.md)

## Limitations
Executing the tests and coverage from the web backend usually can not work for long-running tests due to the timeout issues.
Make sure you raise the apache/nginx settings here if you want to use this functionality here.

The focus is on providing an overview and quickly generating the desired classes with a single mouse click.
