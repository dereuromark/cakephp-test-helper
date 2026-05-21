---
layout: home

hero:
  name: cakephp-test-helper
  text: Developer backend for CakePHP
  tagline: A browseable /test-helper backend with an association vs DB foreign-key audit, a SQL to Query Builder converter, fixture and test helpers, and custom linter tasks.
  image:
    src: /logo.svg
    alt: cakephp-test-helper
  actions:
    - theme: brand
      text: Get Started
      link: /guide
    - theme: alt
      text: Association Audit
      link: /Associations
    - theme: alt
      text: View on GitHub
      link: https://github.com/dereuromark/cakephp-test-helper

features:
  - icon: 🔗
    title: Association vs DB Audit
    details: Compares your declared associations against the real database foreign keys across four layers — constraint, key-type, cascade-rule and loose-column — with copy-paste fixes.
    link: /Associations
    linkText: Read the guide
  - icon: 🧰
    title: SQL to Query Builder
    details: Converts raw SQL (SELECT/INSERT/UPDATE/DELETE, joins, subqueries, CTEs, functions) into clean CakePHP Query Builder code.
    link: /SqlConverter
    linkText: Read the guide
  - icon: ✅
    title: Custom Linter Tasks
    details: Run project-specific code-quality checks via bin/cake linter, with configurable and custom tasks.
    link: /Linter
    linkText: Read the guide
  - icon: 🧪
    title: Test & Fixture Helpers
    details: Run tests and view results or coverage in the backend, check tests and fixtures per file, and bake the missing ones with a click.
    link: /guide
    linkText: Read the guide
  - icon: 🧭
    title: URL Tools
    details: Generate URL arrays from string URLs and reverse-look-up routes from the backend.
    link: /guide
    linkText: Read the guide
  - icon: 🧩
    title: Plugin Info & Hooks
    details: Inspect your plugins, their hooks and more — with auto-suggested improvements.
    link: /guide
    linkText: Read the guide
---
