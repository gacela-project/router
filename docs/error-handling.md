# Error handling

Any `Throwable` thrown while the router runs is caught and dispatched to a handler,
`Error` as well as `Exception`. Map a `class-string` to a handler with
`Handlers::handle()`:

```php
$handlers->handle(MyException::class, MyExceptionHandler::class);
$handlers->handle(OtherException::class, static fn (OtherException $e): string => "Oops: {$e->getMessage()}");
$handlers->handle(TypeError::class, static fn (TypeError $e): string => "Bad type: {$e->getMessage()}");
```

## How a handler is chosen

The most specific handler wins. The router looks, in order, for a handler registered for:

1. the thrown throwable's exact class,
2. each of its parent classes, nearest first,
3. the interfaces it implements, in the order PHP lists them,
4. `Throwable`, which always has one.

A handler for a base class therefore also catches its subclasses:

```php
$handlers->handle(RuntimeException::class, MyRuntimeHandler::class);   // also UnexpectedValueException, ...
```

Four handlers are registered out of the box:

- `NotFound404Exception` → `NotFound404ExceptionHandler`
- `InvalidParamValueException` → `InvalidParamValueExceptionHandler` (404, see [Routing](routing.md#parameters-passed-to-the-action))
- `MethodNotAllowed405Exception` → `MethodNotAllowed405ExceptionHandler`
- `Throwable` (catch-all) → `FallbackExceptionHandler`

So an unmatched route (which throws `NotFound404Exception`) produces a 404 response
without any configuration, and an uncaught `TypeError` produces a 500 rather than
escaping the router.

## 404 vs 405

The two are distinguished by whether the **path** is known:

- No route matches the path under **any** method → `NotFound404Exception` → **404**.
- The path matches, but not for this method → `MethodNotAllowed405Exception` → **405**,
  with an `Allow` header listing every method the path does accept.

```php
$routes->get('users/{id}', UserController::class, 'show');

// DELETE /users/7  ->  405, Allow: GET, HEAD
// HEAD   /users/7  ->  200, served by the GET route, empty body
// GET    /nothing  ->  404
```

Methods in `Allow` are de-duplicated and listed in a canonical order, not in
registration order. A path that serves `GET` also serves `HEAD`
(see [Routing](routing.md#head-requests)), so `HEAD` is listed alongside it. `MethodNotAllowed405Exception::allowedMethods()` returns the same
list if you replace the handler:

```php
$handlers->handle(
    MethodNotAllowed405Exception::class,
    static fn (MethodNotAllowed405Exception $e): string => 'Try: ' . implode(', ', $e->allowedMethods()),
);
```

An `Error` never reaches a handler registered for `Exception::class`, since it is not an
`Exception`. That handler is free to type-hint `Exception`, so handing it an `Error` would
fail inside the handler itself. Register `Throwable::class` to catch everything that has no
closer handler, replacing the built-in 500:

```php
$handlers->handle(Throwable::class, static fn (Throwable $t): string => "Oops: {$t->getMessage()}");
```

## Handler shape

A handler is either:

- a **`callable`** that receives the exception and returns the response, or
- a **`class-string`** of an invokable class, which is resolved through the container
  and invoked with the exception.

If the resolved handler is not callable, a `NonCallableHandlerException` is thrown.

A handler returns what an action may return: a `string`, an `array` (sent as JSON), any
`Stringable`, or a [`Response`](responses.md) with its headers. Anything else throws
`UnsupportedResponseTypeException`, which is not caught again, so it ends the request.

```php
$handlers->handle(
    NotFound404Exception::class,
    static fn (): Response => new Response('{"error":"not found"}', [
        'HTTP/1.1 404 Not Found',
        'Content-Type: application/json',
    ]),
);
```

```php
final class MyExceptionHandler
{
    public function __invoke(MyException $exception): string
    {
        return "Handled: {$exception->getMessage()}";
    }
}
```
