# Gacela Router

A minimalistic HTTP router ideal for your proof-of-concept projects.


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

## Why?

There are many other routers out there. Eg: using Symfony Framework, Laravel, etc... however, these are really rich in features which means they add a lot of accidental complexity and dependencies to your vendor, that you might want to avoid. At least for your proof-of-concept project.

Gacela Router doesn't aim to be the best router that can do everything, but a light router to have the bare minimum code, ideal for your simple ideas to emerge.

For a POC, we value simplicity over a rich-feature library.

## Example

Start the example local server:
```bash
php -S localhost:8081 example/example.php
```

You can access the example routes:
```php
# file: example/example.php
Routing::configure(static function (RoutingConfigurator $routes): void {
    $routes->redirect('docs', 'https://gacela-project.com/');

    # localhost:8081/custom/123
    $routes->get('custom/{number}', $controller, 'customAction');

    # localhost:8081/custom
    $routes->get('custom', $controller);

    # localhost:8081?number=456
    $routes->get('/', $controller);
});
```

