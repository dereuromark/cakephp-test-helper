# CakePHP TestHelper plugin
[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-test-helper.svg?branch=master)](https://travis-ci.org/dereuromark/cakephp-test-helper)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-test-helper/master.svg)](https://codecov.io/github/dereuromark/cakephp-test-helper?branch=master)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%207.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-test-helper/license)](https://packagist.org/packages/dereuromark/cakephp-test-helper)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-test-helper/d/total)](https://packagist.org/packages/dereuromark/cakephp-test-helper)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

Browser based addons for your test driven development.

Note: This branch is for **CakePHP 4.0+**. See [version map](https://github.com/dereuromark/cakephp-test-helper/wiki#cakephp-version-map) for details.

## Motivation
After 2.x=>3.x, the "web-tester" has been removed. It was, for certain cases, however, quite useful.
This aims to bring back a part of it.

The CLI also doesn't allow a good overview. Even with auto-complete, you have to type almost everything out.
With a browser backend generating tests or running them is just a simple mouse click.

You have an overview of your classes and the test classes to it. If there is one missing, you can easily "bake" it from this web backend. It internally uses [Bake](https://github.com/cakephp/bake/) plugin as well as your preferred theme.

### Further useful addons
- URL array generation from string URLs (respects routing, so it is basically also a reverse lookup)
- Fixture validation tool (compares actual DB with the schema files: fields and attributes, constraints and indexes)

## Installation

You can install this plugin into your CakePHP application using [composer](https://getcomposer.org):
```
composer require --dev dereuromark/cakephp-test-helper
```

Note: This is not meant for production, so make sure you use the `--dev` flag and install it as development-only tool.

## Setup

Don't forget to load it under your bootstrap function in `Application.php`:
```php
$this->addPlugin('TestHelper');
```

This will also load the routes.

### Deprecated way
In older applications you used the bootstrap file:
```php
Plugin::load('TestHelper', ['routes' => true]);
```

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
- [x] Shells
- [x] Tasks
- [ ] Cells
- [ ] ShellHelpers
- [ ] Forms
- [ ] Mailers

Feel free to help out improving and completing this test helper plugin.

- [Full Documentation](docs/README.md)

## Limitations
Executing the tests and coverage from the web backend usually can not work for long running tests due to the timeout issues.
Make sure you raise the apache/nginx settings here if you want to use this functionality here.

The focus is on providing an overview and quickly generating the desired classes with a single mouse click.
