# Responses

An action may return a `string`, an `array`, any `Stringable`, or one of the built-in
response entities. Returning anything else throws `UnsupportedResponseTypeException`.

## Plain string

```php
public function __invoke(): string
{
    return 'Hello world';
}
```

## Response

`Response` implements `Stringable`. Any headers you pass are sent (via `header()`)
when the response is rendered.

```php
use Gacela\Router\Entities\Response;

public function __invoke(): Response
{
    return new Response('<h1>Hi</h1>', [
        'Content-Type: text/html',
    ]);
}
```

## JsonResponse

`JsonResponse` extends `Response`. It JSON-encodes the given array and adds
`Content-Type: application/json` automatically (unless you already supplied it).

```php
use Gacela\Router\Entities\JsonResponse;

public function __invoke(): JsonResponse
{
    return new JsonResponse(['hello' => 'world']);
}
```

Encoding uses `JSON_THROW_ON_ERROR`, so invalid data throws a `JsonException`.

## Returning an array

Returning a plain `array` is the same thing without the ceremony: it is wrapped in a
`JsonResponse`, so the body is JSON and `Content-Type: application/json` is set.

```php
public function __invoke(): array
{
    return ['hello' => 'world'];   // {"hello":"world"}
}
```

Reach for `JsonResponse` directly when you need extra headers alongside the JSON:

```php
public function __invoke(): JsonResponse
{
    return new JsonResponse(['hello' => 'world'], ['Cache-Control: no-store']);
}
```

An empty array encodes as `[]`, not `{}`, since PHP cannot tell an empty list from an
empty map. `JsonResponse` only accepts an `array`, so to emit `{}` return a
`Response` with the literal body and header instead.

## Custom Stringable

Any object implementing `Stringable` works as a response:

```php
final class Xml implements Stringable
{
    public function __construct(private string $body) {}

    public function __toString(): string
    {
        header('Content-Type: application/xml');

        return $this->body;
    }
}
```
