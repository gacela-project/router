<?php

declare(strict_types=1);

namespace Gacela\Router;

use Gacela\Router\Entities\Route;

final class Router
{
    /**
     * @param callable(Routes, MappingInterfaces):void $fn
     */
    public static function configure(callable $fn): void
    {
        $routerConfigurator = new Routes();
        $mappingInterfaces = new MappingInterfaces();

        $fn($routerConfigurator, $mappingInterfaces);

        $route = self::findRoute($routerConfigurator);

        if ($route) {
            echo $route->run($mappingInterfaces);
        }
    }

    private static function findRoute(Routes $routerConfigurator): ?Route
    {
        foreach ($routerConfigurator->routes() as $route) {
            if ($route->requestMatches()) {
                return $route;
            }
        }

        return null;
    }
}
