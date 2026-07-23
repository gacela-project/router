# Responses

An action may return a `string`, any `Stringable`, or one of the built-in response
entities. Returning anything else throws `UnsupportedResponseTypeException`.

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
