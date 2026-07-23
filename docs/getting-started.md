# Getting started

## Install

```bash
composer require gacela-project/router
```

Requires PHP `>=8.1`. The only runtime dependency is `gacela-project/container`.

## A minimal app

Point your web server at a single entry file (e.g. `public/index.php`):

```php
<?php

require __DIR__ . '/../vendor/autoload.php';

use Gacela\Router\Configure\Routes;
use Gacela\Router\Router;

$router = new Router(static function (Routes $routes): void {
    $routes->get('/', static fn (): string => 'Hello world');
});

$router->run();
```

The closure passed to `Router` receives only the configurators you type-hint:
`Routes`, `Bindings`, `Handlers`, `Middlewares`. All except `Routes` are optional,
and they can appear in any order.

You can also configure the router in several steps:

```php
$router = new Router();

$router->configure(static function (Routes $routes): void {
    $routes->get('/', HomeController::class);
});

$router->configure(static function (Routes $routes): void {
    $routes->get('about', AboutController::class);
});

$router->run();
```

## Run the bundled example

```bash
composer serve
# equivalent to: php -S localhost:8081 example/example.php
```

Then open <http://localhost:8081>. See [`example/example.php`](../example/example.php).

## Next

- [Routing](routing.md)
- [Responses](responses.md)
- [Middleware](middleware.md)
