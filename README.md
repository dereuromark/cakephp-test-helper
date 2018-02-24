# TestHelper plugin for CakePHP

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

