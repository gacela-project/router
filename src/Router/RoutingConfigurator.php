<?php

declare(strict_types=1);

namespace Gacela\Router;

/**
 * @method get(string $path, object|string $controller, string $action = '__invoke')
 * @method head(string $path, object|string $controller, string $action = '__invoke')
 * @method connect(string $path, object|string $controller, string $action = '__invoke')
 * @method post(string $path, object|string $controller, string $action = '__invoke')
 * @method delete(string $path, object|string $controller, string $action = '__invoke')
 * @method options(string $path, object|string $controller, string $action = '__invoke')
 * @method patch(string $path, object|string $controller, string $action = '__invoke')
 * @method put(string $path, object|string $controller, string $action = '__invoke')
 * @method trace(string $path, object|string $controller, string $action = '__invoke')
 */
final class RoutingConfigurator
{
    /** @var list<Route> */
    private array $routes = [];

    /**
     * @psalm-suppress MixedArgument
     */
    public function __call(string $name, array $arguments): void
    {
        $this->routes[] = match ($name) {
            'head' => $this->route(Request::METHOD_HEAD, ...$arguments),
            'connect' => $this->route(Request::METHOD_CONNECT, ...$arguments),
            'post' => $this->route(Request::METHOD_POST, ...$arguments),
            'delete' => $this->route(Request::METHOD_DELETE, ...$arguments),
            'options' => $this->route(Request::METHOD_OPTIONS, ...$arguments),
            'patch' => $this->route(Request::METHOD_PATCH, ...$arguments),
            'put' => $this->route(Request::METHOD_PUT, ...$arguments),
            'trace' => $this->route(Request::METHOD_TRACE, ...$arguments),
            default => $this->route(Request::METHOD_GET, ...$arguments),
        };
    }

    /**
     * @return list<Route>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * @param object|class-string $controller
     */
    private function route(
        string $method,
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): Route {
        $path = ($path === '/') ? '' : $path;

        return new Route($method, $path, $controller, $action);
    }
}
