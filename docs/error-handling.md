# Error handling

Any `Exception` thrown while the router runs is caught and dispatched to a handler.
Map an exception `class-string` to a handler with `Handlers::handle()`:

```php
$handlers->handle(MyException::class, MyExceptionHandler::class);
$handlers->handle(OtherException::class, static fn (OtherException $e): string => "Oops: {$e->getMessage()}");
```

## How a handler is chosen

1. The handler registered for the thrown exception's exact class, or
2. the fallback handler registered for `Exception::class`.

Two handlers are registered out of the box:

- `NotFound404Exception` → `NotFound404ExceptionHandler`
- `Exception` (fallback) → `FallbackExceptionHandler`

So an unmatched route (which throws `NotFound404Exception`) produces a 404 response
without any configuration.

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
