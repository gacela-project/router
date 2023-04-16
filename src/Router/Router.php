<?php

declare(strict_types=1);

namespace Gacela\Router;

use Gacela\Router\Entities\Route;

final class Router
{
    /**
     * @param callable(RouterConfigurator):void $fn
     */
    public static function configure(callable $fn): void
    {
        $routerConfigurator = new RouterConfigurator();
        $fn($routerConfigurator);

        $route = self::findRoute($routerConfigurator);

        if ($route) {
            echo $route->run($routerConfigurator);
        }
    }

    private static function findRoute(RouterConfigurator $routerConfigurator): ?Route
    {
        foreach ($routerConfigurator->routes() as $route) {
            if ($route->requestMatches()) {
                return $route;
            }
        }

        return null;
    }
}
