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

        $path = ($path === '/') ? '' : $path;

        $firstRoute = null;
        foreach ($methods as $method) {
            if (!in_array($method, Request::ALL_METHODS, true)) {
                throw UnsupportedHttpMethodException::withName($method);
            }

            $route = new Route($method, $path, $controller, $action, $pathPattern);
            $this->routes[] = $route;

            // Store the first route to return for middleware chaining
            if ($firstRoute === null) {
                $firstRoute = $route;
            }
        }

        return $firstRoute ?? throw new RuntimeException('No routes were created');
    }
}
