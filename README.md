# CakePHP TestHelper plugin 
[![Build Status](https://api.travis-ci.org/dereuromark/cakephp-test-helper.svg?branch=master)](https://travis-ci.org/dereuromark/cakephp-test-helper)
[![Minimum PHP Version](http://img.shields.io/badge/php-%3E%3D%205.6-8892BF.svg)](https://php.net/)
[![License](https://poser.pugx.org/dereuromark/cakephp-test-helper/license)](https://packagist.org/packages/dereuromark/cakephp-test-helper)
[![Total Downloads](https://poser.pugx.org/dereuromark/cakephp-test-helper/d/total)](https://packagist.org/packages/dereuromark/cakephp-test-helper)
[![Coding Standards](https://img.shields.io/badge/cs-PSR--2--R-yellow.svg)](https://github.com/php-fig-rectified/fig-rectified-standards)

Browser Addons for your test driven development.

## Motivation
After 2.x=>3.x, the "web-tester" has been removed. It was, for certain cases, however, quite useful.
This aims to bring back a part of it.

The CLI also doesn't allow a good overview. Even with auto-complete, you have to type almost everything out.
With a browser backend generating tests or running them is just a simple mouse click.

### Further useful addons
- URL array generation from string URLs

## Installation

You can install this plugin into your CakePHP application using [composer](http://getcomposer.org):
```
composer require --dev dereuromark/cakephp-test-helper
```

Note: This is not meant for production, so make sure you use the `--dev` flag and install it as development-only tool.

## Setup

Make sure you load the plugin with routes enabled:
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
- [ ] Models (Tables/Entities)
- [ ] Components
- [ ] Behavior
- [ ] Helpers
- [ ] Shells
- [ ] Tasks

Feel free to help out improving and completing this test helper plugin.


## Configuration
- TestHelper.command: If you need a custom phpunit command to run with. 
Both `php phpunit.phar` and `vendor/bin/phpunit` work out of the box.

### Your own template
The default template ships with bootstrap (3) and fontawesome icons.
You can switch out the view templates with your own on project level as documented in the CakePHP docs.


## Limitations
Executing the tests and coverage from the web backend usually can not work for long running tests due to the timeout issues.
Make sure you raise the apache/nginx settings here if you want to use this functionality here.
