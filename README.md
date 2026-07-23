# Gacela Router

A minimalistic HTTP router, ideal for your proof-of-concept projects and decoupled controllers.

<p align="center">
  <a href="https://github.com/gacela-project/router/actions">
    <img src="https://github.com/gacela-project/router/workflows/CI/badge.svg" alt="GitHub Build Status">
  </a>
  <a href="https://scrutinizer-ci.com/g/gacela-project/router/?branch=main">
    <img src="https://scrutinizer-ci.com/g/gacela-project/router/badges/quality-score.png?b=main" alt="Scrutinizer Code Quality">
  </a>
  <a href="https://scrutinizer-ci.com/g/gacela-project/router/?branch=main">
    <img src="https://scrutinizer-ci.com/g/gacela-project/router/badges/coverage.png?b=main" alt="Scrutinizer Code Coverage">
  </a>
  <a href="https://shepherd.dev/github/gacela-project/router">
    <img src="https://shepherd.dev/github/gacela-project/router/coverage.svg" alt="Psalm Type-coverage Status">
  </a>
  <a href="https://dashboard.stryker-mutator.io/reports/github.com/gacela-project/router/main">
    <img src="https://img.shields.io/endpoint?style=flat&url=https%3A%2F%2Fbadge-api.stryker-mutator.io%2Fgithub.com%2Fgacela-project%2Frouter%2Fmain" alt="Mutation testing badge">
  </a>
  <a href="https://github.com/gacela-project/router/blob/main/LICENSE">
    <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="MIT Software License">
  </a>
</p>

### Why?

There are many other routers out there. Eg: using Symfony Framework, Laravel, etc... however, these are really rich in features which means they add a lot of accidental complexity and dependencies to your vendor, that you might want to avoid. At least for your proof-of-concept project.

Gacela Router doesn't aim to be the best router that can do everything, but a light router to have the bare minimum code, ideal for your simple ideas to emerge.

For a POC, we value simplicity over a rich-feature library.

### Requirements

- PHP `>=8.1`

### Installation

```bash
composer require gacela-project/router
```

### Example

```php
# Request only the parameters you need: Routes, Bindings, Handlers, Middlewares
# All except Routes are optional, and you can place them in any order.

$router = new Router(function (Routes $routes, Bindings $bindings, Handlers $handlers, Middlewares $middlewares) {

    // Custom redirections
    $routes->redirect('docs', 'https://gacela-project.com/');

    // Matching a route coming from a particular or any custom HTTP methods
    $routes->get('custom', CustomController::class, '__invoke');
    $routes->post('custom', CustomController::class, 'customAction');
    $routes->any('custom', CustomController::class);

    // Matching a route coming from multiple HTTP methods
    $routes->match(['GET', 'POST'], '/', CustomController::class);

    // Binding custom dependencies on your controllers
    $routes->get('custom/{number}', CustomControllerWithDependencies::class, 'customAction');
    $bindings->bind(SomeDependencyInterface::class, SomeDependencyConcrete::class);

    // Handle custom Exceptions with class-string|callable
    $handlers->handle(NotFound404Exception::class, NotFound404ExceptionHandler::class);

    // Apply middleware to all routes
    $middlewares->add(new GlobalMiddleware());

    // Use individual middleware to a route
    $routes->get('admin', AdminController::class)->middleware(new AuthMiddleware());

    // Or define a middleware group
    $middlewares->group('web', [
        new SessionMiddleware(),
        new CsrfMiddleware(),
    ]);

    // And apply the group to the route
    $routes->get('/', Controller::class)->middleware('web');

});

$router->run();
```

### HTTP methods

Every HTTP verb has a helper on `Routes`, plus `any()` (matches all verbs) and `match()` (matches a given list):

```php
$routes->get($path, $controller, $action = '__invoke');
$routes->post(...);  $routes->put(...);    $routes->patch(...);
$routes->delete(...); $routes->head(...);  $routes->options(...);
$routes->connect(...); $routes->trace(...);
$routes->any($path, $controller, $action = '__invoke');
$routes->match(['GET', 'POST'], $path, $controller, $action = '__invoke');
```

The `$controller` can be a `class-string` (its dependencies are auto-resolved) or an already-built object. The `$action` defaults to `__invoke`.

### Route parameters

Use `{name}` for mandatory and `{name?}` for optional parameters. The matched values are passed to the action by name, and cast to the action's typed argument (`string`, `int`, `float`, `bool`):

```php
$routes->get('users/{id}', UserController::class, 'show');
// -> public function show(int $id): string

$routes->get('archive/{year?}', ArchiveController::class, 'list');
// -> public function list(int $year = 2023): string
```

Optional parameters must come after all mandatory ones; an invalid path throws `MalformedPathException`.

### Responses

An action may return a `string`, any `Stringable`, or one of the built-in response entities:

```php
use Gacela\Router\Entities\Response;
use Gacela\Router\Entities\JsonResponse;

// Plain string
return 'Hello world';

// Response with custom headers
return new Response('<h1>Hi</h1>', ['Content-Type: text/html']);

// JsonResponse (adds "Content-Type: application/json" for you)
return new JsonResponse(['hello' => 'world']);
```

Returning anything else throws `UnsupportedResponseTypeException`.

### Accessing the request

Type-hint `Request` in your controller constructor and it will be injected:

```php
use Gacela\Router\Entities\Request;

final class Controller
{
    public function __construct(private Request $request) {}

    public function __invoke(): string
    {
        // POST params take precedence over GET
        return (string) $this->request->get('name', 'default');
    }
}
```

### Error handling

Map an exception `class-string` to a handler (`class-string` or `callable`). Two handlers are registered out of the box: `NotFound404Exception` and a fallback for any `Exception`.

```php
$handlers->handle(NotFound404Exception::class, NotFound404ExceptionHandler::class);
$handlers->handle(MyException::class, static fn (MyException $e): string => "Oops: {$e->getMessage()}");
```

### Middleware

A middleware implements `MiddlewareInterface` and wraps the next handler:

```php
use Gacela\Router\Middleware\MiddlewareInterface;
use Gacela\Router\Entities\Request;

final class TimingMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, Closure $next): string
    {
        $start = microtime(true);
        $response = $next($request);
        header('X-Response-Time: ' . round((microtime(true) - $start) * 1000, 2) . 'ms');

        return $response;
    }
}
```

Register it globally (`$middlewares->add(...)`), per route (`->middleware(...)`), or as a reusable group (`$middlewares->group('web', [...])` then `->middleware('web')`). Global middlewares run before route middlewares.

### Use it within Gacela

The package ships a `RouterGacelaConfig` adapter that binds `Router` and `RouterInterface` into the [Gacela](https://gacela-project.com/) container (using `addBindingIf`, so your app can override them). Extend it from your `gacela.php` and resolve the router anywhere:

```php
use Gacela\Framework\Gacela;
use Gacela\Router\Config\RouterGacelaConfig;
use Gacela\Router\Router;

Gacela::bootstrap($appRootDir, static function (GacelaConfig $config): void {
    $config->extendGacelaConfig(RouterGacelaConfig::class);
});

Gacela::get(Router::class)->run();
```

Because the router is a shared instance, plugins/modules can add their own routes in separate steps via `RouterInterface::configure()`.

### Working demo

For a working example run `composer serve` and check the `example/example.php`

> TIP: `composer serve` is equivalent to:
> ```bash
> php -S localhost:8081 example/example.php
> ```
