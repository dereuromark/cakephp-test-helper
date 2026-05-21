---
description: Fixes for common TestHelper issues — Authorization plugin errors and black-and-white coverage reports.
---

# Troubleshooting

## Authorization plugin errors

If you use the [CakePHP Authorization plugin](https://github.com/cakephp/authorization) and
hit `AuthorizationRequiredException` errors on TestHelper routes, add this to your
`config/bootstrap.php`:

```php
Configure::write('TestHelper.ignoreAuthorization', true);
```

This skips authorization checks for all TestHelper routes, similar to how DebugKit handles
authorization.

## Coverage report is black & white

If the assets don't load, your web server may be blocking hidden (dot) files — common on
Nginx/Apache setups (e.g. the CakeBox Vagrant VM by default).

In your `sites-available` configuration, find and remove the following for local
development:

```nginx
# deny access to hidden
location ~ /\. {
    deny all;
}
```

Afterwards the coverage report should render in color.
