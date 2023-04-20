<?php

declare(strict_types=1);

namespace Gacela\Router;

use Closure;
use Exception;
use Gacela\Router\Entities\Route;
use Gacela\Router\Exceptions\NotFound404Exception;
use ReflectionFunction;

use function get_class;

final class Router
{
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
            echo self::findRoute($routes)
                ->run($bindings);
        } catch (Exception $exception) {
            echo (string) self::findHandler($handlers, $exception)($exception);
        }
    }

    private static function findRoute(Routes $routes): Route
    {
        foreach ($routes->getAllRoutes() as $route) {
            if ($route->requestMatches()) {
                return $route;
            }
        }

        throw new NotFound404Exception();
    }

    private static function findHandler(Handlers $handlers, Exception $exception): callable
    {
        $handler = $handlers->getAllHandlers()[get_class($exception)] ?? null;

        if ($handler === null) {
            return $handlers->getAllHandlers()[Exception::class];
        }

        return $handler;
    }
}
