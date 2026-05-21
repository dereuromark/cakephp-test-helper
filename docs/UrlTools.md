# URL Tools

Generate URL arrays from string URLs — a reverse lookup that turns a path back into the
CakePHP routing array.

Use the form on the backend entry page (`/test-helper`): paste a string URL and get the
matching array form (`['controller' => ..., 'action' => ..., ...]`).

![URL array generation](img/url_array_generation.png)

::: tip
Handy when migrating string URLs to array URLs in tests and templates — paste `/admin/posts/edit/5`
and copy the array straight into your code.
:::
