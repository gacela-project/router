<?php

declare(strict_types=1);

namespace Gacela\Router;

use Gacela\Router\Controllers\RedirectController;
use Gacela\Router\Entities\Request;
use Gacela\Router\Entities\Route;
use Gacela\Router\Exceptions\UnsupportedHttpMethodException;

use function in_array;

/**
 * @method head(string $path, object|string $controller, string $action = '__invoke')
 * @method connect(string $path, object|string $controller, string $action = '__invoke')
 * @method get(string $path, object|string $controller, string $action = '__invoke')
 * @method post(string $path, object|string $controller, string $action = '__invoke')
 * @method put(string $path, object|string $controller, string $action = '__invoke')
 * @method patch(string $path, object|string $controller, string $action = '__invoke')
 * @method delete(string $path, object|string $controller, string $action = '__invoke')
 * @method options(string $path, object|string $controller, string $action = '__invoke')
 * @method trace(string $path, object|string $controller, string $action = '__invoke')
 * @method any(string $path, object|string $controller, string $action = '__invoke')
 */
final class Routes
{
    /** @var list<Route> */
    private array $routes = [];

    /**
     * @psalm-suppress MixedArgument
     */
    public function __call(string $name, array $arguments): void
    {
        if ($name === 'any') {
            $this->addRoutesForAllMethods($arguments);
        } else {
            $this->addRouteByName($name, $arguments);
        }
    }

    /**
     * @psalm-suppress MixedArgument
     *
     * @param string[] $httpMethods
     * @param object|class-string $controller
     */
    public function match(array $httpMethods, string $path, object|string $controller, string $action = '__invoke'): void
    {
        foreach ($httpMethods as $methodName) {
            $this->routes[] = $this->createRoute($methodName, $path, $controller, $action);
        }
    }

    /**
     * @return list<Route>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    public function redirect(
        string $uri,
        string $destination,
        int $status = 302,
        string $method = null,
    ): void {
        if ($method === null) {
            $this->addRoutesForAllMethods([$uri, new RedirectController($destination, $status)]);
        } else {
            $this->addRouteByName($method, [$uri, new RedirectController($destination, $status)]);
        }
    }

    /**
     * @psalm-suppress MixedArgument
     */
    private function addRoutesForAllMethods(array $arguments): void
    {
        foreach (Request::ALL_METHODS as $methodName) {
            $this->routes[] = $this->createRoute($methodName, ...$arguments);
        }
    }

    /**
     * @psalm-suppress MixedArgument
     */
    private function addRouteByName(string $httpMethod, array $arguments): void
    {
        $route = $this->createRoute(strtoupper(trim($httpMethod)), ...$arguments);

        $this->routes[] = $route;
    }

    /**
     * @param object|class-string $controller
     */
    private function createRoute(
        string $httpMethod,
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): Route {
        if (!in_array($httpMethod, Request::ALL_METHODS)) {
            throw new UnsupportedHttpMethodException($httpMethod);
        }
        $path = ($path === '/') ? '' : $path;

        return new Route($httpMethod, $path, $controller, $action);
    }
}
