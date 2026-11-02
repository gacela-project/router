---
name: router-tests
description: Regression tests and HTTP observation for the router.
globs: ["tests/**"]
---

Tests use `GacelaTest` namespaces. Reuse `tests/Feature/Router/Fixtures/` and `tests/Feature/Router/Fake/` before adding fixtures. Match PHPUnit attributes and assertions in sibling tests.

Unit: path validation, patterns, values. Feature: configured routes, invocation, bindings, middleware. Header assertions use `GacelaTest\Feature\HeaderTestCase` and `tests/Feature/header.php`; a new header-emitting namespace needs its own capture function.

Set the request globals each test needs. `phpunit.xml` backs up globals and randomizes order but does not reset static properties, so caches must not make assertions order-dependent. Assert output, headers, exceptions or return values, not private cache layout. Verify real status codes with `run-router request`.
