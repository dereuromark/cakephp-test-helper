# CakePHP TestHelper plugin
[![CI](https://github.com/dereuromark/cakephp-test-helper/actions/workflows/ci.yml/badge.svg?branch=cake6)](https://github.com/dereuromark/cakephp-test-helper/actions/workflows/ci.yml?query=branch%3Amaster)
[![Coverage Status](https://img.shields.io/codecov/c/github/dereuromark/cakephp-test-helper/master.svg)](https://codecov.io/github/dereuromark/cakephp-test-helper/branch/master)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen.svg?style=flat)](https://phpstan.org/)
[![Latest Stable Version](https://poser.pugx.org/dereuromark/cakephp-test-helper/v/stable.svg)](https://packagist.org/packages/dereuromark/cakephp-test-helper)
[![Minimum PHP Version](https://img.shields.io/badge/php-%3E%3D%208.2-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-test-helper/license.svg)](LICENSE)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-test-helper/d/total)](https://packagist.org/packages/dereuromark/cakephp-test-helper)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-purple.svg?style=flat-square)](https://github.com/php-fig-rectified/fig-rectified-standards)

This branch targets CakePHP 6 and PHP 8.5 or later. Use the `cake6` branches of companion CakePHP plugins during development.

Composer applications consuming this development branch must set `minimum-stability` to `dev` and `prefer-stable` to `true`, then require this plugin as `dev-cake6`. Companion plugins also use their `cake6` branches. The published documentation website covers stable CakePHP 5 releases; use this branch's `docs/` directory for the upgrade documentation.

Developer tools for browsing application classes, generating tests and fixtures, running PHPUnit, and checking database associations.

Read the [documentation website](https://dereuromark.github.io/cakephp-test-helper/), starting with [Installation](https://dereuromark.github.io/cakephp-test-helper/installation) and [Getting started](https://dereuromark.github.io/cakephp-test-helper/guide). See the [version map](https://github.com/dereuromark/cakephp-test-helper/wiki#cakephp-version-map) for other CakePHP branches.

The web backend uses its own controller base. Keep it on a private development instance or behind application middleware access controls.

### Further useful addons
* **SQL to Query Builder Converter** - Convert supported SQL statements to CakePHP Query Builder code
* URL array generation from string URLs (respects routing, so it is basically also a reverse lookup)
* Fixture validation tool (compares actual DB with the schema files: fields and attributes, constraints and indexes)
* Model/entity/table comparison overview.
* GUI for fixture comparison and generation of missing ones per mouse click.
* Association vs DB foreign-key audit (declared associations compared against actual DB constraints).
* Custom linter tasks for project-specific code quality checks.

![Dashboard](https://raw.githubusercontent.com/dereuromark/cakephp-test-helper/master/docs/img/dashboard.png)

## Documentation

- [Installation](https://dereuromark.github.io/cakephp-test-helper/installation)
- [Getting started](https://dereuromark.github.io/cakephp-test-helper/guide)
- [Configuration](https://dereuromark.github.io/cakephp-test-helper/configuration)
- [Test runner](https://dereuromark.github.io/cakephp-test-helper/test-runner)
- [Custom linter tasks](https://dereuromark.github.io/cakephp-test-helper/linter)
