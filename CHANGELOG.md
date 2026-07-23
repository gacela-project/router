# Changelog

## [Unreleased]

### Added
- `Throwable::class` can be registered with `Handlers::handle()` as a catch-all for anything the router does not have a more specific handler for

### Fixed
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
