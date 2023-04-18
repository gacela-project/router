<?php

declare(strict_types=1);

namespace Gacela\Router;

use Exception;
use Gacela\Router\Controllers\NotFound404Controller;
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

        try {
            echo $route->run($mappingInterfaces);
        } catch (Exception $exception) {
            header('HTTP/1.1 500 Internal Server Error');
        }
    }

    private static function findRoute(Routes $routerConfigurator): Route
    {
        foreach ($routerConfigurator->routes() as $route) {
            if ($route->requestMatches()) {
                return $route;
            }
        }

        return new Route('', '/', NotFound404Controller::class);
    }
}
