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
final class Routing
{
    /**
     * @param callable(RoutingConfigurator):void $fn
     */
    public static function configure(callable $fn): void
    {
        $routingConfigurator = new RoutingConfigurator();
        $fn($routingConfigurator);

        $route = self::findRoute($routingConfigurator);

        if ($route) {
            echo $route->run($routingConfigurator);
        }
    }

    private static function findRoute(RoutingConfigurator $routingConfigurator): ?Route
    {
        foreach ($routingConfigurator->routes() as $route) {
            $redirect = $routingConfigurator->redirects()[$route->path()] ?? null;
            if ($redirect !== null) {
                return self::findRedirectRoute($redirect, $routingConfigurator);
            }

            if ($route->requestMatches()) {
                return $route;
            }
        }

        return null;
    }

    private static function findRedirectRoute(Redirect $redirect, RoutingConfigurator $routingConfigurator): ?Route
    {
        foreach ($routingConfigurator->routes() as $route) {
            if ($route->path() === $redirect->destination()) {
                return $route;
            }
        }
        return null;
    }
}
