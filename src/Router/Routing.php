<?php

declare(strict_types=1);

namespace Gacela\Router;

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
            if ($route->requestMatches()) {
                return $route;
            }
        }

        return null;
    }
}
