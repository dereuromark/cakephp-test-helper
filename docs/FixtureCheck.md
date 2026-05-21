# Fixture Check

Compare your fixture files against the live database schema — fields and attributes,
constraints and indexes — and get the `bake` commands needed to update any that have
drifted.

::: warning Deprecated
Prefer [Fixture Factories](https://github.com/dereuromark/cakephp-fixture-factories) over
static fixtures for new code. This tool helps maintain existing fixtures.
:::

```bash
bin/cake fixture_check
```

## Useful options

| Option | Meaning |
|--------|---------|
| `-p` | Plugin |
| `-t` | Type to run: `fields`, `constraints`, `indexes` (none = all) |
| `-c` | Connection (switch from the default DB to another) |
| `-f` | Specific fixtures to check (comma-separated list) |
| `-s` | Strict mode, includes collation |

By default it only checks your app level. Wrap it in a composer script to also cover your
important plugins:

```json
{
    "scripts": {
        "fixture_check": [
            "bin/cake fixture_check",
            "bin/cake fixture_check -p MyPlugin"
        ]
    }
}
```

Then run it as `composer fixture_check`.
