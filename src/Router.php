<?php

declare(strict_types=1);

namespace GacelaRouter;

final class Router
{
    /**
     * @param object|class-string $controller
     */
    public static function get(
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): void {
        self::runRoute(Request::METHOD_GET, $path, $controller, $action);
    }

    /**
     * @param object|class-string $controller
     */
    public static function head(
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): void {
        self::runRoute(Request::METHOD_HEAD, $path, $controller, $action);
    }

    /**
     * @param object|class-string $controller
     */
    public static function connect(
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): void {
        self::runRoute(Request::METHOD_CONNECT, $path, $controller, $action);
    }

    /**
     * @param object|class-string $controller
     */
    public static function delete(
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): void {
        self::runRoute(Request::METHOD_DELETE, $path, $controller, $action);
    }

    /**
     * @param object|class-string $controller
     */
    public static function options(
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): void {
        self::runRoute(Request::METHOD_OPTIONS, $path, $controller, $action);
    }

    /**
     * @param object|class-string $controller
     */
    public static function patch(
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): void {
        self::runRoute(Request::METHOD_PATCH, $path, $controller, $action);
    }

    /**
     * @param object|class-string $controller
     */
    public static function post(
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): void {
        self::runRoute(Request::METHOD_POST, $path, $controller, $action);
    }

    /**
     * @param object|class-string $controller
     */
    public static function put(
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): void {
        self::runRoute(Request::METHOD_PUT, $path, $controller, $action);
    }

    /**
     * @param object|class-string $controller
     */
    public static function trace(
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): void {
        self::runRoute(Request::METHOD_TRACE, $path, $controller, $action);
    }

    /**
     * @param object|class-string $controller
     */
    private static function runRoute(
        string $method,
        string $path,
        object|string $controller,
        string $action = '__invoke',
    ): void {
        $path = ($path === '/') ? '' : $path;

        $route = new Route($method, $path, $controller, $action);

        if ($route->requestMatches()) {
            echo $route->run();
        }
    }
}
