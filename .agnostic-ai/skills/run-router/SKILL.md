---
name: run-router
description: Use to run the Gacela Router example or drive routing, middleware and response behavior with the bundled PHP driver.
---

Needs PHP >=8.3, Composer and mbstring. Run `composer install --no-interaction` first (`composer.lock` is ignored).

```bash
php .agnostic-ai/skills/run-router/driver.php call GET /custom/123
php .agnostic-ai/skills/run-router/driver.php request GET /headers
php .agnostic-ai/skills/run-router/driver.php smoke
composer serve
```

- `call`: in process; captures body and namespaced `header()` calls; cannot see a real HTTP status.
- `request`: starts a temporary HTTP server; reports real status, headers, body. `--data=number=99` sends form data.
- `smoke`: checks the example routes over HTTP, then stops its server. Without `--port`, temporary servers use a free port.
- `serve`: foreground server, default port 8081.

`call` defaults to `.agnostic-ai/skills/run-router/routes.default.php`; the others default to `example/example.php`. Each accepts `--routes=FILE`, which must return a configure Closure, not run a Router. Do not pass `example/example.php`.

Header capture uses `tests/Feature/header.php`; a new namespace calling `header()` needs a stub there. Use `request` for real status codes and emitted headers.

The driver suppresses dependency deprecations before loading Composer. If raw tools flood stderr on newer PHP, use `php -d error_reporting='E_ALL & ~E_DEPRECATED'`. Formatter and analyzers exclude the driver; after editing helpers, lint them and run `call` and `smoke`.
