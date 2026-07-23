# Middleware

A middleware wraps the handling of a request: it receives the `Request` and a
`$next` callable, and returns the response string. Call `$next($request)` to continue
the pipeline, or return early to short-circuit it.

```php
use Gacela\Router\Middleware\MiddlewareInterface;
use Gacela\Router\Entities\Request;
use Closure;

final class TimingMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): string
    {
        $start = microtime(true);

        $response = $next($request);

        $ms = round((microtime(true) - $start) * 1000, 2);
        header("X-Response-Time: {$ms}ms");

        return $response;
    }
}
```

## Registering middleware

Global middleware runs for every route:

```php
$middlewares->add(new TimingMiddleware());
```

Per-route middleware runs only for that route (chain it after the route definition):

```php
$routes->get('admin', AdminController::class)->middleware(new AuthMiddleware());
```

## Groups

Define a reusable stack once and reference it by name:

```php
$middlewares->group('web', [
    new SessionMiddleware(),
    new CsrfMiddleware(),
]);

$routes->get('/', HomeController::class)->middleware('web');
```

## Resolution and order

- A middleware can be given as an **instance** or as a **`class-string`** — a class
  string is resolved through the container when the pipeline runs.
- A per-route `->middleware('name')` referencing a defined group expands to that
  group's middlewares.
- **Global middlewares run before route middlewares**, each in registration order.
