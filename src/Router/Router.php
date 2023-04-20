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
    /**
     * @throws ReflectionException
     */
    public static function configure(Closure $fn): void
    {
        $routes = new Routes();
        $bindings = new Bindings();
        $handlers = new Handlers();

        $params = array_map(static fn ($param) => match ((string)$param->getType()) {
            Routes::class => $routes,
            Bindings::class => $bindings,
            Handlers::class => $handlers,
            default => null,
        }, (new ReflectionFunction($fn))->getParameters());

        $fn(...$params);

        try {
            echo self::findRoute($routes)->run($bindings);
        } catch (Exception $exception) {
            echo self::handleException($handlers, $exception);
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

    private static function handleException(Handlers $handlers, Exception $exception): string
    {
        $handler = $handlers->getByException($exception);
        if ($handler === null) {
            header('HTTP/1.1 500 Internal Server Error');
            return '';
        }

        return $handler($exception);
    }
}
