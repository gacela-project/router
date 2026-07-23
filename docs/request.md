# The request

`Gacela\Router\Entities\Request` is a thin, immutable snapshot of the incoming
request, built from PHP's superglobals (`$_GET`, `$_POST`, `$_SERVER`). The router
builds it once per `run()` and passes the same instance through route matching.

## Injecting it into a controller

Type-hint `Request` in your controller constructor and it is injected automatically:

```php
use Gacela\Router\Entities\Request;

final class SearchController
{
    public function __construct(private Request $request) {}

    public function __invoke(): string
    {
        $term = (string) $this->request->get('q', '');

        return "Searching: {$term}";
    }
}
```

## Reading input

```php
$request->get('name');            // POST value, else GET value, else null
$request->get('name', 'guest');   // ... else the provided default
```

`get()` looks in the request body (`$_POST`) **first**, then the query string
(`$_GET`). This means POST values take precedence over GET values with the same key.

Other methods used internally by the router:

- `Request::fromGlobals(): self` — build a request from the current superglobals.
- `path(): string` — the URL path (`REQUEST_URI` without query string).
- `isMethod(string $method): bool` — whether the request method equals `$method`.

The HTTP method constants (`Request::METHOD_GET`, `METHOD_POST`, …) and
`Request::ALL_METHODS` are also available.
