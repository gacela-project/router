<?php

declare(strict_types=1);

namespace Gacela\Router;

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
final class RoutingConfigurator
{
    /** @var list<Route> */
    private array $routes = [];

    /** @var array<class-string, callable|class-string|object> */
    private array $mappingInterfaces = [];

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
     * @return list<Route>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * @param array<class-string, callable|class-string|object> $array
     */
    public function setMappingInterfaces(array $array): self
    {
        $this->mappingInterfaces = $array;
        return $this;
    }

    /**
     * @return array<class-string, callable|class-string|object>
     */
    public function getMappingInterfaces(): array
    {
        return $this->mappingInterfaces;
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
    private function addRouteByName(string $name, array $arguments): void
    {
        $this->routes[] = match ($name) {
            'head' => $this->createRoute(Request::METHOD_HEAD, ...$arguments),
            'connect' => $this->createRoute(Request::METHOD_CONNECT, ...$arguments),
            'get' => $this->createRoute(Request::METHOD_GET, ...$arguments),
            'post' => $this->createRoute(Request::METHOD_POST, ...$arguments),
            'delete' => $this->createRoute(Request::METHOD_DELETE, ...$arguments),
            'options' => $this->createRoute(Request::METHOD_OPTIONS, ...$arguments),
            'patch' => $this->createRoute(Request::METHOD_PATCH, ...$arguments),
            'put' => $this->createRoute(Request::METHOD_PUT, ...$arguments),
            'trace' => $this->createRoute(Request::METHOD_TRACE, ...$arguments),
            default => throw new UnsupportedHttpMethodException($name),
        };
    }

    /**
     * @param object|class-string $controller
     */
    private function createRoute(
        string $method,
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): Route {
        $path = ($path === '/') ? '' : $path;

        return new Route($method, $path, $controller, $action);
    }
}
