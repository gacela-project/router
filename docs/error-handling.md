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

1. The handler registered for the thrown throwable's exact class, or
2. the fallback handler: `Exception::class` when an exception was thrown,
   `Throwable::class` when an `Error` was.

Four handlers are registered out of the box:

- `NotFound404Exception` → `NotFound404ExceptionHandler`
- `MethodNotAllowed405Exception` → `MethodNotAllowed405ExceptionHandler`
- `Exception` (fallback for exceptions) → `FallbackExceptionHandler`
- `Throwable` (fallback for errors) → `FallbackExceptionHandler`

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

// DELETE /users/7  ->  405, Allow: GET
// GET    /nothing  ->  404
```

Methods in `Allow` are de-duplicated and listed in a canonical order, not in
registration order. `MethodNotAllowed405Exception::allowedMethods()` returns the same
list if you replace the handler:

```php
$handlers->handle(
    MethodNotAllowed405Exception::class,
    static fn (MethodNotAllowed405Exception $e): string => 'Try: ' . implode(', ', $e->allowedMethods()),
);
```

An `Error` never falls back to the `Exception::class` handler. A handler registered
there is free to type-hint `Exception`, so handing it an `Error` would fail inside
the handler itself. Register `Throwable::class` if you want one true catch-all:

```php
$handlers->handle(Throwable::class, static fn (Throwable $t): string => "Oops: {$t->getMessage()}");
```

## Handler shape

A handler is either:

- a **`callable`** that receives the exception and returns a string, or
- a **`class-string`** of an invokable class — it is resolved through the container
  and invoked with the exception.

If the resolved handler is not callable, a `NonCallableHandlerException` is thrown.

```php
final class MyExceptionHandler
{
    public function __invoke(MyException $exception): string
    {
        return "Handled: {$exception->getMessage()}";
    }
}
```
