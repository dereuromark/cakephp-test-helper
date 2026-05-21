# Configuration

All keys live under the `TestHelper` config prefix. See `config/app.example.php` for the
canonical, copy-paste reference.

## Authorization

If you use the [CakePHP Authorization plugin](https://github.com/cakephp/authorization),
bypass its checks for the TestHelper routes:

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
    // Set to 'xdebug' if enabled; otherwise pcov is used by default.
    'coverage' => null,
    // Custom PHP binary path, e.g. '/usr/bin/php8.2'.
    'php' => null,
],
```

## Your own templates

The default templates ship with Bootstrap 5 and Font Awesome icons. You can swap any view
template for your own at the project level, as documented in the CakePHP docs.

## Tool-specific configuration

Some tools have their own keys, documented on their pages:

* [Association vs DB Audit](/Associations#configuration) — `associationAudit.ignoreColumns`, `associationAudit.preferIntegerKeys`
