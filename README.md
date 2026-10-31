# Gacela Router

A minimalistic HTTP router for proof-of-concept projects and decoupled controllers.

<p align="center">
  <a href="https://github.com/gacela-project/router/actions">
    <img src="https://github.com/gacela-project/router/workflows/CI/badge.svg" alt="GitHub Build Status">
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

## Quick start

```bash
composer require gacela-project/router
```

Put this in `index.php`:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Gacela\Router\Configure\Routes;
use Gacela\Router\Router;

$router = new Router(static function (Routes $routes): void {
    $routes->get('/', static fn (): string => 'Hello world');
    $routes->get('users/{id}', static fn (int $id): string => "User #{$id}");
});

$router->run();
```

Then start PHP's built-in server and open <http://localhost:8000/users/7>:

```bash
php -S localhost:8000 index.php
```

Requires PHP `>=8.3`. The only runtime dependency is `gacela-project/container`.

## Why

Routers that come with a framework, such as Symfony or Laravel, bring many features and dependencies that a proof of concept does not need. Gacela Router keeps the minimum: routes, controllers, middleware and error handlers.

## What you get

- **Routes** for every HTTP verb, plus `any()` and `match()`, with `{param}` and `{optional?}` path segments. [Routing](docs/routing.md)
- **Typed parameters.** A path value reaches the action as `string`, `int`, `float`, `bool` or a backed enum, and a value that does not fit gives a 404.
- **Controllers** as a class (its dependencies are wired for you), an object or a closure. Returning an array sends JSON. [Responses](docs/responses.md)
- **Route groups** with a shared prefix, and **named routes** that build their own URLs. [Named routes and URLs](docs/url-generation.md)
- **Middleware** for every route, one route, or a named group. [Middleware](docs/middleware.md)
- **Error handlers** chosen by exception class. A known path with the wrong method answers 405 with an `Allow` header, and `HEAD` is served by the matching `GET` route. [Error handling](docs/error-handling.md)
- **Gacela integration**, optional, through `gacela-project/gacela: ^2.4`. [Use it within Gacela](docs/gacela-integration.md)

## A fuller example

```php
// Ask only for the configurators you need: Routes, Bindings, Handlers, Middlewares.
// All except Routes are optional, and they can come in any order.

$router = new Router(function (Routes $routes, Bindings $bindings, Handlers $handlers, Middlewares $middlewares) {

    // Redirect
    $routes->redirect('docs', 'https://gacela-project.com/');

    // One HTTP method, or any
    $routes->get('custom', CustomController::class, '__invoke');
    $routes->post('custom', CustomController::class, 'customAction');
    $routes->any('custom', CustomController::class);

    // Several HTTP methods
    $routes->match(['GET', 'POST'], '/', CustomController::class);

    // Give your controllers their dependencies
    $routes->get('custom/{number}', CustomControllerWithDependencies::class, 'customAction');
    $bindings->bind(SomeDependencyInterface::class, SomeDependencyConcrete::class);

    // Answer an exception with a class-string or a callable
    $handlers->handle(NotFound404Exception::class, NotFound404ExceptionHandler::class);

    // Middleware for every route
    $middlewares->add(new GlobalMiddleware());

    // Middleware for one route
    $routes->get('admin', AdminController::class)->middleware(new AuthMiddleware());

    // A named group of middleware, applied to a route
    $middlewares->group('web', [
        new SessionMiddleware(),
        new CsrfMiddleware(),
    ]);
    $routes->get('/', Controller::class)->middleware('web');

});

$router->run();
```

## Documentation

All pages are in [`docs/`](docs/README.md):

- [Getting started](docs/getting-started.md): install, a minimal app, how to run it
- [Routing](docs/routing.md): HTTP methods, controllers, path patterns, parameters, groups
- [Responses](docs/responses.md): strings, arrays, `Stringable`, `Response`, `JsonResponse`
- [Named routes and URLs](docs/url-generation.md): naming a route and building its URL
- [The request](docs/request.md): reading input and injecting `Request`
- [Middleware](docs/middleware.md): global, per-route and grouped
- [Error handling](docs/error-handling.md): mapping exceptions to handlers
- [Use it within Gacela](docs/gacela-integration.md): the `RouterGacelaConfig` adapter and plugins

## Try the example app

```bash
composer serve
```

This serves [`example/example.php`](example/example.php) on <http://localhost:8081>. Each route has a comment with a URL to try.

## Contributing

Bug reports and pull requests are welcome. [CONTRIBUTING.md](.github/CONTRIBUTING.md) lists the setup, the checks to run, and the commit style.

## License

MIT. See [LICENSE](LICENSE).
