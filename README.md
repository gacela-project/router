# Gacela Router

A minimalistic HTTP router, ideal for your proof-of-concept projects and decoupled controllers.

<p align="center">
  <a href="https://github.com/c/actions">
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
  <a href="https://github.com/gacela-project/router/blob/master/LICENSE">
    <img src="https://img.shields.io/badge/License-MIT-green.svg" alt="MIT Software License">
  </a>
</p>

### Why?

There are many other routers out there. Eg: using Symfony Framework, Laravel, etc... however, these are really rich in features which means they add a lot of accidental complexity and dependencies to your vendor, that you might want to avoid. At least for your proof-of-concept project.

Gacela Router doesn't aim to be the best router that can do everything, but a light router to have the bare minimum code, ideal for your simple ideas to emerge.

For a POC, we value simplicity over a rich-feature library.

### Installation

```bash
composer require gacela-project/router
```

### Example

```php
# `Bindings` and `Handlers` are optional, and you can place them in any order.

$router = new Router(static function (Routes $routes, Bindings $bindings, Handlers $handlers) void {

    // Custom redirections
    $routes->redirect('docs', 'https://gacela-project.com/');
    
    // Matching a route coming from a particular or any custom HTTP methods
    $routes->get('custom', CustomController::class, '__invoke');
    $routes->...('custom', CustomController::class, 'customAction');
    $routes->any('custom', CustomController::class);

    // Matching a route coming from multiple HTTP methods
    $routes->match(['GET', 'POST'], '/', CustomController::class);
    
    // Binding custom dependencies on your controllers
    $routes->get('custom/{number}', CustomControllerWithDependencies::class, 'customAction');
    $bindings->bind(SomeDependencyInterface::class, SomeDependencyConcrete::class)

    // Handle custom Exceptions with class-string|callable
    $handlers->handle(NotFound404Exception::class, NotFound404ExceptionHandler::class);

});

$router->run();
```

### Working demo

For a working example run `composer serve` and check the `example/example.php`

> TIP: `composer serve` is equivalent to:
> ```bash
> php -S localhost:8081 example/example.php
> ```
