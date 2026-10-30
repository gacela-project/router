---
name: router-tests
description: Regression tests and HTTP observation for the router.
globs: ["tests/**"]
---

Tests use GacelaTest namespaces. Reuse tests/Feature/Router/Fixtures/ and tests/Feature/Router/Fake/ before adding fixtures. Match PHPUnit attributes and assertions in sibling tests.

Use Unit for path validation, patterns and values; Feature for configured routes, invocation, bindings and middleware. Header assertions use GacelaTest\Feature\HeaderTestCase and tests/Feature/header.php. A new header-emitting namespace needs a corresponding capture function.

Set the request globals each test needs. phpunit.xml backs up globals and randomizes order, but does not reset static properties; caches must not make assertions depend on test order. Assert output, headers, exceptions or returned values rather than private cache layout. Verify actual status codes through run-router request.
