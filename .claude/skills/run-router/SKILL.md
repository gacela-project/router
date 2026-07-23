---
name: run-router
description: Build, run, and drive gacela-project/router. Use when asked to start the router, serve the example app, run its tests, issue a request against a route, or check that a routing/middleware/response change actually works in the running app.
---

`gacela-project/router` is a PHP library, so "running it" means driving a
`Router` instance rather than opening an app. Do that through
`.claude/skills/run-router/driver.php`, which gives you two handles: `call`
(in-process, ~60ms, shows the body and every `header()` the router emitted) and
`request`/`smoke` (a real `php -S` server, real status line and headers).

All paths below are relative to the repo root.

## Prerequisites

PHP >= 8.1 with `mbstring`, plus Composer. Verified on PHP 8.5.8 (Homebrew,
with Xdebug 3.5.3 loaded); CI pins 8.1.

```bash
php --version
composer --version
```

## Setup

```bash
composer install --no-interaction
```

`composer.lock` is **gitignored and untracked**, so a fresh clone has no lock
file and `composer install` performs a full dependency resolve (verified on a
clean `git clone` — it resolves and installs 81 packages, no error).

## Run (agent path)

### `call` — in-process, no server

Fastest way to see what a route does. Builds a `Router` from a route-definition
file, fakes `$_SERVER`/`$_GET`/`$_POST`, buffers the echoed body, and captures
`header()` calls via the namespaced stubs the test suite already ships in
`tests/Feature/header.php`.

```bash
php .claude/skills/run-router/driver.php call GET /custom/123
```

```
call GET /custom/123
routes: /Users/.../.claude/skills/run-router/routes.default.php
headers (0):
body (25 bytes):
customAction(number: 123)
```

Point it at your own routes to exercise a change. The file must **`return` a
Closure** — the same shape you pass to `new Router(...)`:

```bash
cat > /tmp/my-routes.php <<'PHP'
<?php
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\JsonResponse;
use Gacela\Router\Entities\Request;

final class ScratchController
{
    public function __construct(private Request $request) {}

    public function show(int $id): JsonResponse
    {
        return new JsonResponse(['id' => $id, 'q' => $this->request->get('q', 'none')]);
    }
}

return static function (Routes $routes): void {
    $routes->get('items/{id}', ScratchController::class, 'show');
};
PHP

php .claude/skills/run-router/driver.php call GET '/items/7?q=hello' --routes=/tmp/my-routes.php
```

```
headers (1):
  Content-Type: application/json
body (20 bytes):
{"id":7,"q":"hello"}
```

Header-emitting routes are observable here — this is how you check a redirect or
a 404 without a server:

```bash
php .claude/skills/run-router/driver.php call GET /docs
#   Location: https://gacela-project.com/   [code=302]
php .claude/skills/run-router/driver.php call GET /nope
#   HTTP/1.0 404 Not Found
```

### `smoke` — real HTTP, whole assertion table

Boots `php -S` on `example/example.php`, runs 8 checks (status + body + specific
headers), tears the server down. Exit 0 all green, exit 1 otherwise. Use this to
confirm you didn't break the example app.

```bash
php .claude/skills/run-router/driver.php smoke
```

```
  PASS  GET /                   200
  PASS  GET /?number=456        200
  PASS  POST /                  200
  PASS  GET /custom/123         200
  PASS  GET /custom             200
  PASS  GET /headers            200
  PASS  GET /docs               302
  PASS  GET /nope               404

smoke: 8/8 passed
```

### `request` — real HTTP, one request

When you need the actual status line and the actual response headers (`call`
cannot give you either — see Gotchas).

```bash
php .claude/skills/run-router/driver.php request GET /headers
php .claude/skills/run-router/driver.php request POST / --data=number=99
php .claude/skills/run-router/driver.php request GET '/items/7?q=hi' --routes=/tmp/my-routes.php
```

```
request GET /headers  ->  200
  Host: 127.0.0.1:60750
  Date: Thu, 23 Jul 2026 12:24:58 GMT
  Connection: close
  X-Powered-By: PHP/8.5.8
  Access-Control-Allow-Origin: *
  Content-Type: application/json
  X-Response-Time: 1.47ms
body (21 bytes):
{"custom": "headers"}
```

With `--routes`, the driver writes a throwaway front controller wrapping your
Closure and serves that instead of `example/example.php`.

### Driver reference

| command | what it does |
|---|---|
| `call <METHOD> <PATH> [--routes=FILE] [--data=k=v]` | In-process. Body + captured `header()` calls. No status code. |
| `request <METHOD> <PATH> [--routes=FILE] [--data=k=v] [--port=N]` | One real HTTP request. Real status + headers. |
| `smoke [--routes=FILE] [--port=N]` | Assertion table over `example/example.php`. Exit 1 on failure. |
| `serve [--routes=FILE] [--port=8081]` | Foreground server, Ctrl-C to stop. |

Without `--port`, the driver grabs a free ephemeral port, so parallel runs don't
collide. Without `--routes`, `call` uses
`.claude/skills/run-router/routes.default.php` (a copy of the example app's
routes that returns the Closure instead of running it).

## Run (human path)

```bash
composer serve   # → http://localhost:8081, Ctrl-C to stop
```

Equivalent to `php -d error_reporting='E_ALL & ~E_DEPRECATED' -S localhost:8081 example/example.php`.
Routes worth hitting: `/`, `/?number=456`, `/custom`, `/custom/123`, `/headers`,
`/docs` (302 off-site), anything else (404).

## Test

```bash
composer test          # php-cs-fixer --dry-run + psalm + phpstan + phpunit
composer test-phpunit  # phpunit only — 177 tests, 189 assertions, ~0.04s
```

Narrow while iterating:

```bash
XDEBUG_MODE=off php -d error_reporting='E_ALL & ~E_DEPRECATED' ./vendor/bin/phpunit --filter=RouterRedirect
```

Mutation testing (`composer infection`, ~13s) currently reports **MSI 96% with a
`--min-msi=96` floor** — it sits exactly on the threshold, so a single newly
survived mutant fails the run.

## Gotchas

- **`call` cannot report a status code.** In the CLI SAPI `headers_list()`
  always returns `[]` and `headers_sent()` is already `true`, so real headers are
  unobservable. The driver works around this with the namespaced `header()`
  stubs from `tests/Feature/header.php` — which is why `call` prints
  `HTTP/1.0 404 Not Found` as a *captured header line*, not as a status. For an
  actual status code use `request`.

- **Those stubs work because the router calls `header()` unqualified.**
  `NotFound404ExceptionHandler`, `RedirectController` and `Response::__toString`
  all live in different namespaces and call `header(...)` without a leading `\`,
  so PHP resolves to a same-namespace function when one exists.
  `tests/Feature/header.php` defines one in each of `Gacela\Router`,
  `Gacela\Router\Handlers`, `Gacela\Router\Controllers` and
  `Gacela\Router\Entities`. **If you add a `header()` call from a new namespace,
  that file needs a matching stub or the call silently escapes the capture.**

- **`Router::run()` catches `Exception`, not `Throwable`.** Any `Error` subclass
  — `TypeError`, `ArgumentCountError`, `DivisionByZeroError` — blows straight
  past the `Handlers` mechanism. Over HTTP that surfaces as **HTTP 200 with a PHP
  fatal-error page in the body**, not a 500 (verified with and without Xdebug).
  A common way to trigger it: register a controller action with a required
  parameter on a route whose path has no matching `{placeholder}` — route params
  come from the path pattern only, never from the query string or POST body.

- **The dev vendor tree floods stderr on PHP >= 8.4.** `thecodingmachine/safe`
  (transitive via psalm) is eagerly loaded by Composer's `files` autoloader and
  emits deprecation notices. Measured on a bare `require "vendor/autoload.php"`:
  **~440KB of output with Xdebug loaded** (each notice carries a stack trace),
  ~130KB with `XDEBUG_MODE=off` — before your program prints a byte. The driver
  sets `error_reporting(E_ALL & ~E_DEPRECATED)` before touching the autoloader;
  run raw `vendor/bin/*` tools with `php -d error_reporting='E_ALL & ~E_DEPRECATED'`.

- **One registration is one `Route`, whatever the method count.**
  `$routes->match(['GET','POST'], ...)` and `$routes->any(...)` build a single
  `Route` holding every declared method, so a chained `->middleware()` covers all
  of them. It used to build one `Route` per method and return only the first,
  which silently dropped the middleware on every method but the first. If you
  touch `Routes::addRoute` or `Route::methodMatches`, check both methods of a
  multi-method route, not just the first:

  ```bash
  # /tmp/mw-routes.php: match(['GET','POST'], 'multi', C::class)->middleware(new TagMiddleware())
  php .claude/skills/run-router/driver.php call GET  /multi --routes=/tmp/mw-routes.php   # → [mw]base
  php .claude/skills/run-router/driver.php call POST /multi --routes=/tmp/mw-routes.php   # → [mw]base
  ```

- **`'/'` is stored as `''`.** `Routes::addRoute` rewrites it. Match against
  `Request::path()` output when debugging a route that "should" match.

- **Nothing in `.claude/` is linted.** php-cs-fixer scans `example/`, `src/`,
  `tests/`; phpstan and psalm scan `src/` only. The driver won't break
  `composer test`, and equally won't be checked by it.

## Troubleshooting

- **`routes file must \`return\` a Closure, got: int`**: your `--routes` file
  builds and runs the router itself instead of returning the configure Closure
  (a `require` of a file with no `return` yields `int(1)`). `example/example.php`
  cannot be used as `--routes` for this reason — it calls `$router->run()` at top
  level, so you'll also see its output echoed just before the error.
  `routes.default.php` is the reusable copy of those same routes.

- **`port N is already in use; drop --port to get a free one`**: exactly that.
  The driver refuses to start rather than let your requests land on whatever
  else is listening — which is what happened before this check existed.

- **`Fatal error: Uncaught ArgumentCountError: Too few arguments to function
  X::y(), 0 passed`** from a `call`/`request`: the action needs a parameter the
  route path doesn't provide. Add the `{placeholder}` to the path or give the
  parameter a default.

- **Deprecation wall from `thecodingmachine/safe` when running phpunit
  directly**: prefix with `php -d error_reporting='E_ALL & ~E_DEPRECATED'`, or
  use the `composer test-phpunit` script.
