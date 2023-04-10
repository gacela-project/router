<?php

declare(strict_types=1);

namespace Gacela\Router;

/**
 * @method static get(string $path, object|string $controller, string $action = '__invoke')
 * @method static head(string $path, object|string $controller, string $action = '__invoke')
 * @method static connect(string $path, object|string $controller, string $action = '__invoke')
 * @method static post(string $path, object|string $controller, string $action = '__invoke')
 * @method static delete(string $path, object|string $controller, string $action = '__invoke')
 * @method static options(string $path, object|string $controller, string $action = '__invoke')
 * @method static patch(string $path, object|string $controller, string $action = '__invoke')
 * @method static put(string $path, object|string $controller, string $action = '__invoke')
 * @method static trace(string $path, object|string $controller, string $action = '__invoke')
 */
final class Route
{
    /**
     * @psalm-suppress MixedArgument
     */
    public static function __callStatic(string $name, array $arguments): void
    {
        match ($name) {
            'head' => self::route(Request::METHOD_HEAD, ...$arguments),
            'connect' => self::route(Request::METHOD_CONNECT, ...$arguments),
            'post' => self::route(Request::METHOD_POST, ...$arguments),
            'delete' => self::route(Request::METHOD_DELETE, ...$arguments),
            'options' => self::route(Request::METHOD_OPTIONS, ...$arguments),
            'patch' => self::route(Request::METHOD_PATCH, ...$arguments),
            'put' => self::route(Request::METHOD_PUT, ...$arguments),
            'trace' => self::route(Request::METHOD_TRACE, ...$arguments),
            default => self::route(Request::METHOD_GET, ...$arguments),
        };
    }

    /**
     * @param object|class-string $controller
     */
    private static function route(
        string $method,
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): void {
        $path = ($path === '/') ? '' : $path;

        $route = new RouteEntity($method, $path, $controller, $action);

        if ($route->requestMatches()) {
            echo $route->run();
        }
    }
}
