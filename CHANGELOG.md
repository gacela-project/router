# Changelog

## [Unreleased]

### Added
- `Routes::group()` registers routes under a shared path prefix, so it no longer has to be repeated per route. Groups nest and compose outermost-first, `'/'` inside a group resolves to the prefix itself, and a prefix may carry `{params}` that reach the action. The composed path is validated, so a malformed prefix throws `MalformedPathException` naming the full path
- Routes can be named with `->name('user.show')`, and `UrlGenerator::generate()` builds their urls, filling `{params}` by name and omitting absent optionals. `UrlGenerator` is bound automatically, so a controller can type-hint it. Unknown names, duplicate names, missing mandatory params and unsupported param types all throw `UrlGenerationException`
- A path parameter binds to a backed enum when the action argument is typed as one, for both string- and int-backed enums. A URL value with no matching case throws `InvalidEnumValueException` rather than a raw `ValueError`, and an int-backed enum rejects a non-numeric segment instead of coercing it to `0`. Pure enums remain unsupported, having no value to match against
- A controller action may return an `array`, which is wrapped in a `JsonResponse`: the body is JSON-encoded and `Content-Type: application/json` is set. `string`, `Stringable`, `Response` and `JsonResponse` returns are unchanged, and anything else still throws `UnsupportedResponseTypeException`
- `HEAD` requests are served by the matching `GET` route when no `HEAD` route is registered, per HTTP. The route and its middlewares run so their headers are sent, and the body is withheld from every `HEAD` response, error pages included. An explicit `HEAD` route still takes precedence, and `HEAD` is now advertised in the `Allow` header of a 405 for any path serving `GET`
- Requesting a known path with an unregistered HTTP method now responds `405 Method Not Allowed` with an `Allow` header, instead of `404`. Backed by a new `MethodNotAllowed405Exception` (carrying `allowedMethods()`) and a built-in `MethodNotAllowed405ExceptionHandler`, both overridable through `Handlers::handle()`. A path that matches no route under any method is still a `404`
- `Throwable::class` can be registered with `Handlers::handle()` as a catch-all for anything the router does not have a more specific handler for
- `Request::method()` returns the current HTTP method

### Changed
- Controller action signatures are reflected once per `Controller::action` and cached for the process, instead of on every matched request. Resolving the parameters of a 3-argument action is ~2.5x faster
- Routes with no `{param}` now resolve by an exact map lookup keyed by HTTP method, running no regex at all, and dynamic routes are only scanned within the request's method bucket. With 100 competing routes and the match registered last, a static lookup goes from 101 regex evaluations to 0 (see `tests/benchmark-routing.php`)
- A static route now wins over a dynamic one that would also match, whatever the registration order. Between two dynamic routes the first registered still wins
- A regex metacharacter in a static path is now matched literally. `$routes->get('a.c', ...)` previously also matched `/abc`, because the path was compiled into a regex where `.` was a wildcard

### Fixed
- A path segment of `0` is no longer rejected as malformed. `PathValidator` tested segments with `!$part`, and `'0'` is falsy in PHP, so `products/0`, `page/0/items` and `0` all threw `MalformedPathException`
- `Request::path()` no longer throws a `TypeError` when `REQUEST_URI` is missing or malformed. `parse_url()` returns `null` for a uri with no path and `false` for one it cannot parse, neither of which satisfies the `string` return type; all such cases now resolve to `/`. `Request::method()` had the same flaw and returns `''` when `REQUEST_METHOD` is absent or not a string, which matches no route
- `Error` thrown while handling a request is now caught and dispatched to a handler like any `Exception`. `Router::run()` caught only `Exception`, so a `TypeError`, `ArgumentCountError` or `DivisionByZeroError` escaped the `Handlers` mechanism and surfaced as a PHP fatal error page with a 200 status instead of a 500
- `->middleware()` on a route declared with several HTTP methods now applies to all of them. `$routes->match(['GET', 'POST'], ...)` and `$routes->any(...)` used to register one route per method while returning only the first, so the chained middleware silently covered the first method only

## [0.13.0](https://github.com/gacela-project/router/compare/0.12.1...0.13.0) - 2026-07-23

### Added
- Middleware support with `MiddlewareInterface`
- Middleware groups for reusable middleware stacks

### Changed
- Upgrade `gacela-project/gacela` to `^1.19` and `gacela-project/container` to `^0.10`
- `RouterGacelaConfig` now uses `addBindingIf`, so an application can override the `Router`/`RouterInterface` bindings
- Resolve the incoming `Request` once per `run()` and pass it through route matching (removes redundant `Request::fromGlobals()` calls)
- Extract `PathPatternGenerator` and harden `PathValidator`
- Upgrade dev tooling: PHPUnit `^10.5`, PHPStan `^2.2`, Psalm `^6.16`, php-cs-fixer `^3.95`; migrate test metadata to PHP 8 attributes

## [0.12.1](https://github.com/gacela-project/router/compare/0.12.0...0.12.1) - 2023-12-21

- Require PHP `>=8.1`
- Allow an optional default value on `Request::get()`

## [0.12.0](https://github.com/gacela-project/router/compare/0.11.0...0.12.0) - 2023-06-16

- Fix combining mandatory and optional GET arguments

## [0.11.0](https://github.com/gacela-project/router/compare/0.10.0...0.11.0) - 2023-05-20

- Upgrade gacela:^1.4 and container:^0.5


## [0.10.0](https://github.com/gacela-project/router/compare/0.10.0...0.11.0) - 2023-04-27

- Add Infection
- Create RouterInterface
- Group Configure classes in the same dir
- Add adapter RouterGacelaConfig

## [0.9.0](https://github.com/gacela-project/router/compare/0.9.0...0.10.0) - 2023-04-27

- Allow adding routes in different steps
- Unify Router use
- Refactor Router Static Methods

## [0.8.0](https://github.com/gacela-project/router/compare/0.8.0...0.9.0) - 2023-04-24

- Require response to be a string or implements Stringable
- Simplify Route::requestMatches implementation

## [0.7.0](https://github.com/gacela-project/router/compare/0.7.0...0.8.0) - 2023-04-22

- Create JsonResponse
- Allow headers in Response

## [0.6.0](https://github.com/gacela-project/router/compare/0.6.0...0.7.0) - 2023-04-21

- Move and fix 404 tests
- Error handler
- Introspective handling
- Support class handlers
- Add named constructor for UnsupportedHttpMethodException
- Improve readme example

## [0.5.0](https://github.com/gacela-project/router/compare/0.4.0...0.5.0) - 2023-04-18

- Trigger 404 if no Route was found

## [0.4.0](https://github.com/gacela-project/router/compare/0.3.0...0.4.0) - 2023-04-17

- Inject Request in Controller's constructor
- Match route

## [0.3.0](https://github.com/gacela-project/router/compare/0.2.0...0.3.0) - 2023-04-16

- Refactoring structure

## [0.2.0](https://github.com/gacela-project/router/compare/0.1.0...0.2.0) - 2023-04-16

- Create RoutingConfigurator
- Refactor extract class RouteParams
- UnsupportedHttpMethodException if HTTP verb is invalid
- Support all routes with "any"
- Automatically resolve controller dependencies
- Allow redirect routes

## [0.1.0](https://github.com/gacela-project/router/releases/tag/0.1.0) - 2023-04-10

- Initial release
