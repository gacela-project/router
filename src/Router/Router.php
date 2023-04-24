<?php

declare(strict_types=1);

namespace Gacela\Router;

use Closure;
use Exception;
use Gacela\Container\Container;
use Gacela\Router\Entities\Route;
use Gacela\Router\Exceptions\NotFound404Exception;
use ReflectionFunction;

use function get_class;
use function is_callable;

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

        throw new NotFound404Exception();
    }

    private static function handleException(Handlers $handlers, Exception $exception): string
    {
        $handler = self::findHandler($handlers, $exception);

        if (is_callable($handler)) {
            return $handler($exception);
        }

        $instance = Container::create($handler);

        if (is_callable($instance)) {
            return $instance($exception);
        }

        return '';
    }

    /**
     * @return callable|class-string
     */
    private static function findHandler(Handlers $handlers, Exception $exception): string|callable
    {
        return $handlers->getAllHandlers()[get_class($exception)]
            ?? $handlers->getAllHandlers()[Exception::class];
    }
}
