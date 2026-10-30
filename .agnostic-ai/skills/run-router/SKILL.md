---
name: run-router
description: Run the Gacela Router example or drive routing, middleware and response behavior with the bundled PHP driver.
---

Run commands from the repository root. Requires PHP >=8.3, Composer and mbstring. Run composer install --no-interaction; a fresh clone resolves dependencies because composer.lock is ignored.

```bash
php .agnostic-ai/skills/run-router/driver.php call GET /custom/123
php .agnostic-ai/skills/run-router/driver.php request GET /headers
php .agnostic-ai/skills/run-router/driver.php smoke
composer serve
```

- call runs in process and captures the body and namespaced header() calls. It cannot observe a real HTTP status code.
- request starts a temporary HTTP server and reports real status, headers and body. Use --data=number=99 for form data.
- smoke checks the example routes over HTTP and stops its server. Without --port, temporary servers use a free port.
- serve keeps a foreground server running, default port 8081.

call defaults to .agnostic-ai/skills/run-router/routes.default.php; request, smoke and serve default to example/example.php. Each accepts --routes=FILE, which must return a configure Closure rather than run a Router. Do not pass example/example.php as --routes.

Header capture uses tests/Feature/header.php. A new namespace calling header() needs a matching stub there. Use request when verifying status codes or actual emitted headers.

The driver suppresses dependency deprecations before loading Composer. If raw tools flood stderr on newer PHP versions, use php -d error_reporting='E_ALL & ~E_DEPRECATED' for diagnosis. The formatter and analyzers exclude the driver. After editing PHP helpers, lint them and run call and smoke.
