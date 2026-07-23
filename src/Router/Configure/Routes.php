<?php

declare(strict_types=1);

namespace Gacela\Router\Configure;

use Gacela\Router\Controllers\RedirectController;
use Gacela\Router\Entities\Request;
use Gacela\Router\Entities\Route;
use Gacela\Router\Exceptions\MalformedPathException;
use Gacela\Router\Exceptions\UnsupportedHttpMethodException;
use Gacela\Router\Validators\PathValidator;
use RuntimeException;

use function in_array;
use function is_array;

/**
 * @method Route head(string $path, object|string $controller, string $action = '__invoke', ?string $pathPattern = null)
 * @method Route connect(string $path, object|string $controller, string $action = '__invoke', ?string $pathPattern = null)
 * @method Route get(string $path, object|string $controller, string $action = '__invoke', ?string $pathPattern = null)
 * @method Route post(string $path, object|string $controller, string $action = '__invoke', ?string $pathPattern = null)
 * @method Route put(string $path, object|string $controller, string $action = '__invoke', ?string $pathPattern = null)
 * @method Route patch(string $path, object|string $controller, string $action = '__invoke', ?string $pathPattern = null)
 * @method Route delete(string $path, object|string $controller, string $action = '__invoke', ?string $pathPattern = null)
 * @method Route options(string $path, object|string $controller, string $action = '__invoke', ?string $pathPattern = null)
 * @method Route trace(string $path, object|string $controller, string $action = '__invoke', ?string $pathPattern = null)
 * @method Route any(string $path, object|string $controller, string $action = '__invoke', ?string $pathPattern = null)
 */
final class Routes
{
    /** @var list<Route> */
    private array $routes = [];

    /**
     * Paths with no {param}, keyed for an O(1) lookup.
     *
     * @var array<string, array<string, Route>> method => path => route
     */
    private array $staticRoutes = [];

    /**
     * Paths with at least one {param}, still matched by regex, but only the
     * bucket for the request's method is ever scanned.
     *
     * @var array<string, list<Route>> method => routes
     */
    private array $dynamicRoutes = [];

    /**
     * @param array<mixed> $arguments
     *
     * @psalm-suppress MixedArgument
     */
    public function __call(string $method, array $arguments): Route
    {
        if ($method === 'any') {
            /** @phpstan-ignore-next-line argument.type */
            return $this->addRoute(Request::ALL_METHODS, ...$arguments);
        }
        /** @phpstan-ignore-next-line argument.type */
        return $this->addRoute($method, ...$arguments);
    }

    /**
     * @psalm-suppress MixedArgument
     *
     * @param string[] $methods
     * @param object|class-string $controller
     */
    public function match(
        array $methods,
        string $path,
        object|string $controller,
        string $action = '__invoke',
        ?string $pathPattern = null,
    ): Route {
        return $this->addRoute($methods, $path, $controller, $action, $pathPattern);
    }

    public function redirect(
        string $uri,
        string $destination,
        int $status = 302,
        ?string $method = null,
    ): void {
        if ($method === null) {
            $this->addRoute(Request::ALL_METHODS, $uri, new RedirectController($destination, $status));
        } else {
            $this->addRoute([$method], $uri, new RedirectController($destination, $status));
        }
    }

    /**
     * @return list<Route>
     */
    public function getAllRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Static paths resolve by map lookup, so the common case runs no regex at
     * all. Anything with a {param} falls back to scanning that method's bucket
     * in registration order.
     */
    public function findMatching(Request $request): ?Route
    {
        $method = $request->method();
        $path = $request->path();

        // Registered paths carry no leading slash, request paths do. The root
        // route is stored under '/', and an empty request path means the root.
        $key = $path === '' ? '/' : $path;

        $staticRoute = $this->staticRoutes[$method][$key] ?? null;
        if ($staticRoute !== null) {
            return $staticRoute;
        }

        foreach ($this->dynamicRoutes[$method] ?? [] as $route) {
            if ($route->requestMatches($request)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * @param string[]|string $methods
     * @param object|class-string $controller
     */
    private function addRoute(
        array|string $methods,
        string $path,
        object|string $controller,
        string $action = '__invoke',
        ?string $pathPattern = null,
    ): Route {
        if (!PathValidator::isValid($path)) {
            throw MalformedPathException::withPath($path);
        }

        if (!is_array($methods)) {
            $methods = [$methods];
        }
        $methods = array_map(static fn ($method) => strtoupper($method), $methods);

        if ($methods === []) {
            throw new RuntimeException('No routes were created');
        }

        foreach ($methods as $method) {
            if (!in_array($method, Request::ALL_METHODS, true)) {
                throw UnsupportedHttpMethodException::withName($method);
            }
        }

        $path = ($path === '/') ? '' : $path;

        // One Route per registration, not per method: a route returned for
        // ->middleware() chaining has to cover every method it was declared with.
        $route = new Route($methods, $path, $controller, $action, $pathPattern);
        $this->routes[] = $route;
        $this->index($route, $methods, $path);

        return $route;
    }

    /**
     * @param array<string> $methods
     */
    private function index(Route $route, array $methods, string $path): void
    {
        $isDynamic = str_contains($path, '{');
        $key = self::staticKey($path);

        foreach ($methods as $method) {
            if ($isDynamic) {
                $this->dynamicRoutes[$method][] = $route;
                continue;
            }

            // First registration wins, matching the previous linear scan.
            $this->staticRoutes[$method][$key] ??= $route;
        }
    }

    /**
     * '' (the root) becomes '/', 'a/b' becomes '/a/b'.
     */
    private static function staticKey(string $path): string
    {
        return '/' . $path;
    }
}
