# Routing

## HTTP methods

Every HTTP verb has a helper on `Routes`, plus `any()` (matches every verb) and
`match()` (matches a given list):

```php
$routes->get($path, $controller, $action = '__invoke');
$routes->post(...);    $routes->put(...);      $routes->patch(...);
$routes->delete(...);  $routes->head(...);     $routes->options(...);
$routes->connect(...); $routes->trace(...);

$routes->any($path, $controller, $action = '__invoke');
$routes->match(['GET', 'POST'], $path, $controller, $action = '__invoke');
```

An unknown HTTP method throws `UnsupportedHttpMethodException`.

## Controllers

`$controller` can be either:

- a **`class-string`** — the class is instantiated through the container and its
  constructor dependencies are auto-resolved (see [Use it within Gacela](gacela-integration.md)
  and [The request](request.md)), or
- an already-built **object**.

`$action` is the method to call, defaulting to `__invoke`. A controller can be a
plain invokable class or expose several actions:

```php
$routes->get('users', UserController::class);            // UserController::__invoke()
$routes->get('users/list', UserController::class, 'all'); // UserController::all()
```

## Path patterns

Paths are matched without a leading slash. `'/'` is the home path. A path is invalid
(throws `MalformedPathException`) when it has a leading or trailing slash, or empty
segments (e.g. `a//b`).

Segments:

- **Static** — `users/active`
- **Mandatory parameter** — `{name}`
- **Optional parameter** — `{name?}`

All optional parameters must come after every mandatory one, otherwise the path is
rejected.

```php
$routes->get('users/{id}', UserController::class, 'show');
$routes->get('archive/{year?}', ArchiveController::class, 'list');
$routes->get('posts/{id}/comments/{commentId?}', CommentController::class, 'show');
```

## Parameters passed to the action

Matched parameters are passed to the action **by name** and cast to the action's
declared argument type. Supported types: `string`, `int`, `float`, `bool`.

```php
$routes->get('users/{id}', UserController::class, 'show');

final class UserController
{
    public function show(int $id): string
    {
        return "User #{$id}";
    }
}
```

- An optional parameter that is absent from the URL falls back to the argument's
  default value.
- An action argument without a type throws `UnsupportedParamTypeException`.
- A parameter typed as something other than the four supported scalars throws
  `UnsupportedParamTypeException`.

## Redirects

```php
$routes->redirect('docs', 'https://gacela-project.com/');       // 302 for any method
$routes->redirect('old', '/new', 301);                          // custom status
$routes->redirect('form', '/thanks', 302, 'POST');              // limited to one method
```

## Match order

A path with no `{param}` is **static** and resolves by an exact map lookup, keyed by
HTTP method. Anything with a parameter is **dynamic** and is matched by regex, scanning
only the routes registered for the request's method, in registration order.

Two consequences worth knowing:

- A static route wins over a dynamic one that would also match, regardless of which was
  registered first. `$routes->get('users/{id}', ...)` followed by
  `$routes->get('users/me', ...)` resolves `/users/me` to the second.
- Between two dynamic routes that both match, the first registered wins.

```php
$routes->get('users/{id}', UserController::class);   // dynamic
$routes->get('users/me', ProfileController::class);  // static, wins for /users/me
```

## What happens on no match

If no route matches the current request, the router throws `NotFound404Exception`,
which is handled by the built-in 404 handler. See [Error handling](error-handling.md).
