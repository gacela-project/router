<?php

declare(strict_types=1);

namespace Gacela\Router;

use Closure;
use Exception;
use Gacela\Router\Controllers\NotFound404Controller;
use Gacela\Router\Entities\Route;
use ReflectionException;
use ReflectionFunction;

final class Router
{
    // @param callable(Routes $routes, Bindings $bindings):void $fn
    /**
     * @throws ReflectionException
     */
    public static function configure(Closure $fn): void
    {
        $routes = new Routes();
        $bindings = new Bindings();

        $params = array_map(static fn ($param) => match ((string) $param->getType()) {
            Routes::class => $routes,
            Bindings::class => $bindings,
            default => null,
        }, (new ReflectionFunction($fn))->getParameters());

        $fn(...$params);

        $route = self::findRoute($routes);

        try {
            echo $route->run($bindings);
        } catch (Exception $exception) {
            header('HTTP/1.1 500 Internal Server Error');
        }
    }

    private static function findRoute(Routes $routes): Route
    {
        foreach ($routes->getAllRoutes() as $route) {
            if ($route->requestMatches()) {
                return $route;
            }
        }

        return new Route('', '/', NotFound404Controller::class);
    }
}
