---
description: All TestHelper configuration keys, authorization bypass, the admin back-link, test runner options, and templates.
---

# Configuration

All keys live under the `TestHelper` config prefix. See `config/app.example.php` for the
canonical, copy-paste reference.

## Authorization

If you use the [CakePHP Authorization plugin](https://github.com/cakephp/authorization),
TestHelper skips its checks automatically while debug is enabled. To deliberately skip them outside debug, use:

```php
Configure::write('TestHelper.ignoreAuthorization', true);
```

## Back-to-app link

Show a "back" link in the TestHelper navbar that returns to your app's admin area:

```php
// config/app.php
'TestHelper' => [
    // Anything Router::url() accepts; 'plugin' => false anchors it to the host app.
    'adminBackUrl' => ['plugin' => false, 'prefix' => 'Admin', 'controller' => 'Overview', 'action' => 'index'],
    'adminBackLabel' => 'Back to admin', // Optional. Defaults to "Back to App".
],
```

Leave `adminBackUrl` unset to hide the link (the default).

## Test runner

```php
'TestHelper' => [
    // Custom phpunit command. Both `php phpunit.phar` and `vendor/bin/phpunit` work out of the box.
    'command' => null,
    // Custom PHP binary path, e.g. '/usr/bin/php8.2'.
    'php' => null,
],
```

PHPUnit selects the available coverage driver in the CLI process. TestHelper enables Xdebug coverage mode for that process; PCOV must be installed and enabled in CLI PHP. The legacy `coverage` key does not select or install a driver.

## Your own templates

The default templates ship with Bootstrap 5 and Font Awesome icons. You can swap any view
template for your own at the project level, as documented in the CakePHP docs.

## Tool-specific configuration

Some tools have their own keys, documented on their pages:

* [Association vs DB Audit](/associations#configuration), `associationAudit.ignoreColumns`, `associationAudit.preferIntegerKeys`
