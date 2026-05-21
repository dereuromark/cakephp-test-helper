---
description: Compare your CakePHP table associations against the real database foreign keys across four layers, with copy-paste fixes and composite-key support.
---

# Association vs DB Foreign-Key Audit

Navigate to:

```
/test-helper/associations
```

Audits whether your declared table associations (`belongsTo`, `hasMany`, `hasOne`,
`belongsToMany`) agree with the actual database foreign keys — in both directions — and
flags a few related consistency problems. It is **read-only**: it never changes your schema
or code, it only suggests copy-paste fixes.

App and first-party plugin tables are scanned by default; vendor tables can be folded in via
the toggle on the matrix page.

## What it checks

The audit runs in several layers.

### Constraint layer (the core diff)

A symmetric diff between the foreign keys your associations imply and the foreign keys that
actually exist in the database:

* an association whose **owner column does not exist at all** — error; suggests an `addColumn()` migration line (a foreign key cannot be placed on a missing column)
* an association whose **column exists but has no matching DB foreign-key constraint** — warning; suggests an `addForeignKey()` migration line
* a **DB foreign key with no matching association** — suggests the `belongsTo`/`hasMany` call
* a **target/column disagreement** between the two

### Key-type layer

Compares each declared foreign key's column type against the referenced (primary) key:

* a **different type family** (e.g. `integer` referencing `uuid`) is an **error** — suggests a `changeColumn()` migration line aligning the column to its target
* an **owner key narrower than the referenced key** (e.g. `integer` referencing `biginteger`) is a **warning** — it cannot hold every referenced value; same `changeColumn()` fix
* **matching non-integer keys** are an **info** hint that integer keys are generally preferred

Silence the non-integer info hint with `TestHelper.associationAudit.preferIntegerKeys => false`
(the error and narrowing warning still report).

> [!NOTE]
> The key-type layer only applies to single-column foreign keys. Composite keys are diffed
> structurally but not type-checked.

### Cascade-rule layer

Compares the ORM `dependent` intent of a `hasMany`/`hasOne` against the matching DB foreign
key's `ON DELETE` rule (reported as **info**, since either side can legitimately own the
cascade):

* a `dependent` association whose DB FK uses `ON DELETE NO ACTION` won't cascade a delete issued **directly in SQL** (outside the ORM) — suggests switching the FK to `ON DELETE CASCADE`, preserving the existing `ON UPDATE` rule
* a DB `ON DELETE CASCADE` with a **non-`dependent`** association means the ORM won't fire child callbacks — suggests adding `'dependent' => true, 'cascadeCallbacks' => true` (both are needed; `dependent` alone uses a bulk `deleteAll()` that skips child callbacks)

`ON UPDATE` has no ORM-level equivalent and is not compared.

### Loose-column layer

Flags `*_id` columns that have **neither** a foreign-key constraint **nor** an association
(reported as **info**). The built-in ignore list covers common polymorphic columns
(`foreign_id`, `parent_id`, `related_id`); extend it via
`TestHelper.associationAudit.ignoreColumns`.

### Index-presence layer

Flags foreign-key-style columns that are **not the leading column of any index** (reported as
**info**), because joins and lookups on them table-scan. This matters most on **PostgreSQL**,
where a foreign-key constraint does **not** auto-create an index on the referencing column, and
for loose `*_id` columns managed only at the ORM level.

A column counts as indexed only when it is the **first (leading) column** of some index or key:
a regular index, a `unique` constraint, or the `primary` constraint. A column buried as a
non-first member of a composite index does **not** count, since such an index cannot serve a
lookup or join on that column alone. For a composite foreign key the **first** column is
checked, and the suggested `addIndex()` covers all of the key's columns in order.

The candidates are the union of every foreign-key-semantic column the audit already knows: DB
foreign keys, existing code-side association foreign keys, and loose `*_id` columns. The
loose-column ignore list applies here too, and at most one finding is emitted per column even
when it surfaces via several sources. Each finding suggests an `addIndex()` migration line, e.g.:

``` php
$table->addIndex(['post_id']);
```

Silence the whole layer with `TestHelper.associationAudit.checkIndexes => false`, for apps where
the heuristic is more noise than value (e.g. write-heavy or denormalized schemas, or tiny lookup
tables that do not need the index).

## Composite foreign keys

Multi-column (composite) foreign keys are fully diffed in the constraint layer — both for
`belongsTo`/`hasMany`/`hasOne` and for `belongsToMany` junctions. Fix snippets render the
columns as arrays and pin a non-default `bindingKey`, e.g.:

``` php
$table->addForeignKey(['tenant_id', 'company_id'], 'companies', ['tenant_id', 'id'], [
    'update' => 'NO_ACTION', 'delete' => 'NO_ACTION',
]);
```

A composite association whose foreign-key columns and binding columns do not line up is
reported as "not auto-verifiable" rather than guessed at. Composite keys are diffed
structurally but not type-checked.

## The matrix

The summary matrix shows every table against each association type plus the cross-cutting
layers (`Key type`, `Cascade` and `Index`) as their own columns, color-coded by status:

![Association audit matrix](img/associations_matrix.png)

Each table opens to a per-direction detail view:

![Association audit detail](img/associations_detail.png)

Every finding includes a copy-paste fix:

![Association audit finding with fix](img/associations_fix.png)

## Flat scan

A flat scan lists every finding across all in-scope tables at once, ordered worst-first
(errors, then warnings, then info) and grouped by table within each severity. Topic chips at
the top (Constraints, Columns, Key types, Not verifiable) toggle whole categories of finding
in or out, so you can mute, say, the not-verifiable noise and focus on real constraint
problems:

![Association audit flat scan](img/associations_scan.png)

## Configuration

| Key | Default | Description |
|-----|---------|-------------|
| `TestHelper.associationAudit.ignoreColumns` | `[]` | Extra `*_id` column names to ignore in the loose-column layer (merged with the built-in polymorphic defaults). |
| `TestHelper.associationAudit.preferIntegerKeys` | `true` | When `false`, suppress the "integer keys are preferred" info hint. Type-family errors and narrowing warnings still report. |
| `TestHelper.associationAudit.checkIndexes` | `true` | When `false`, disable the index-presence layer entirely (no "foreign key with no index" info findings). |

See `config/app.example.php` for the canonical reference.

## Limitations

* `ON UPDATE` rules are captured but not compared (no ORM equivalent).
* Composite foreign keys are diffed structurally but not type-checked.
* A same-named table on a non-default connection cannot be disambiguated from its alias alone when drilling into the detail view.
