# The request

`Gacela\Router\Entities\Request` is a thin, immutable snapshot of the incoming
request, built from PHP's superglobals (`$_GET`, `$_POST`, `$_SERVER`). The router
builds it once per `run()` and passes the same instance through route matching.

## Injecting it into a controller

Type-hint `Request` in your controller constructor and it is injected automatically. It is
the request of the current `run()`, so a router built once and run per request (a worker
process) never hands a controller an earlier one. Binding your own `Request` with
`$bindings->bind(Request::class, ...)` takes precedence:

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

## Method and path

```php
$request->method();   // 'GET'
$request->path();     // '/users/7'
$request->isMethod(Request::METHOD_POST);
```

Both are defensive about a hostile or incomplete environment, so the router never
crashes on the way in:

- A missing or non-string `REQUEST_METHOD` yields `''`, which matches no route.
- `path()` returns `'/'` whenever there is nothing usable to read: `REQUEST_URI`
  absent or not a string, a uri carrying no path at all (`https://example.org`,
  `?a=1`), or one `parse_url()` cannot parse (`//`, `http://:80`).

Query strings and fragments are stripped, so `/users/7?a=1#top` gives `/users/7`.

A `REQUEST_URI` that starts with `/` is read as sent: the path is everything before `?` or
`#`, untouched. In particular `//evil.example/admin` stays `//evil.example/admin` and matches
no route, rather than being read as host `evil.example` plus `/admin`. A front proxy that
guards `/admin` sees that request under a different path, so serving the `admin` route would
bypass it. Dot segments and repeated slashes are not normalised here, since the proxy already
decides those. A path made only of slashes (`//`, `///`) resolves to `/`. An absolute-form
uri such as `https://host/path` is still parsed for its path.

## Reading input

```php
$request->get('name');            // POST value, else GET value, else null
$request->get('name', 'guest');   // ... else the provided default
```

`get()` looks in the request body (`$_POST`) **first**, then the query string
(`$_GET`). This means POST values take precedence over GET values with the same key.

Other methods used internally by the router:

- `Request::fromGlobals(): self` builds a request from the current superglobals.
- `path(): string` is the URL path (`REQUEST_URI` without the query string).
- `isMethod(string $method): bool` tells whether the request method equals `$method`.

The HTTP method constants (`Request::METHOD_GET`, `METHOD_POST`, …) and
`Request::ALL_METHODS` are also available.
