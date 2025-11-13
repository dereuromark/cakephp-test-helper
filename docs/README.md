# TestHelper Plugin Documentation

Browse `/test-helper` to see all functionality available.
* URL generation and reverse lookup
* Check plugin hooks
* Check fixtures against tables (+ bake missing ones via click)
* Check tests per file available (+ bake missing ones via click)
* Run tests and display results or coverage in backend.
* [Custom Linter Tasks](Linter.md) - Project-specific code quality checks

## Configuration
- **TestHelper.command**: If you need a custom phpunit command to run with. Both `php phpunit.phar` and `vendor/bin/phpunit` work out of the box.
- **TestHelper.coverage**: Set to `xdebug` if you have this enabled, it otherwise uses pcov by default.
- **TestHelper.ignoreAuthorization**: Set to `true` to bypass authorization checks when using the CakePHP Authorization plugin. Default: `false`.

### Your own template
The default template ships with bootstrap (5) and fontawesome icons.
You can switch out the view templates with your own on project level as documented in the CakePHP docs.

Overwrite the `test_cases` element if you want to support e.g. foundation and their modals.


## Troubleshooting

### Authorization Plugin Errors

If you are using the [CakePHP Authorization plugin](https://github.com/cakephp/authorization) and encounter `AuthorizationRequiredException` errors when accessing TestHelper routes, add this to your `config/bootstrap.php`:

```php
Configure::write('TestHelper.ignoreAuthorization', true);
```

This will skip authorization checks for all TestHelper routes, similar to how DebugKit handles authorization.

### Generated code coverage is black&white
If the assets don't work, make sure your Nginx/Apache (like CakeBox Vagrant VM by default) doesn't block hidden files.

In your /sites-available/ configuration find and remove the following for your local development:

    # deny access to hidden
    location ~ /\. {
        deny all;
    }

Afterwards your coverage should be colorful.

### Missing CSRF Token Cookie 
If you are using the CsrfProtectionMiddleware or alike, make sure to deactivate for such admin backends, as those are not supposed to be part of it.
They also should only be accessible locally, anyway.

## Other tools

### URL array generation from string URLs
Check the backend entry page for the form to make reverse lookups for URL strings.

![URL array generation](img/url_array_generation.png)

### Plugin info/check
Check your own plugins on hooks and more.
It can also auto suggest you some improvements here.

Navigate to
```
/test-helper/plugins
```
for details.

### Custom Linter Tasks

Run project-specific code quality checks:

```
bin/cake linter
```

See [Linter Documentation](Linter.md) for complete details on:
* Creating custom linter tasks
* Configuration options
* Included default tasks
* Advanced examples

### Fixture validation tool

Note: Deprecated - Use [Fixture Factories](https://github.com/dereuromark/cakephp-fixture-factories) instead.

Compare actual DB with the schema files: fields and attributes, constraints and indexes.
It will also give you a list of bake commands you need to update the outdated fixtures.

```
bin/cake fixture_check
```

Most useful options:
* 'p': Plugin
* 't': Type to run: fields, constraints, indexes (none = all).
* 'c': Connection (you can switch from default DB to also another one)
* 'f': Specific fixtures to check (comma separated list)
* 's': Strict mode, includes collation

By default it will only check your app level. You can use a combined composer command for convenience to check also your important plugins as convenience wrapper:
```json
"scripts": {
    ...
    "fixture_check": [
        "bin/cake fixture_check",
        "bin/cake fixture_check -p MyPlugin"
    ]
}
```
Then run it as `composer fixture_check`.
