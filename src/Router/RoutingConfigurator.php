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

    /**
     * @psalm-suppress MixedArgument
     */
    public function __call(string $name, array $arguments): void
    {
        if ($name === 'any') {
            foreach (Request::ALL_METHODS as $methodName) {
                $this->routes[] = $this->route($methodName, ...$arguments);
            }
        } else {
            $this->routes[] =  match ($name) {
                'head' => $this->route(Request::METHOD_HEAD, ...$arguments),
                'connect' => $this->route(Request::METHOD_CONNECT, ...$arguments),
                'get' => $this->route(Request::METHOD_GET, ...$arguments),
                'post' => $this->route(Request::METHOD_POST, ...$arguments),
                'put' => $this->route(Request::METHOD_PUT, ...$arguments),
                'patch' => $this->route(Request::METHOD_PATCH, ...$arguments),
                'delete' => $this->route(Request::METHOD_DELETE, ...$arguments),
                'options' => $this->route(Request::METHOD_OPTIONS, ...$arguments),
                'trace' => $this->route(Request::METHOD_TRACE, ...$arguments),
                default => throw new UnsupportedHttpMethodException($name),
            };
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
