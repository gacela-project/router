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
    public function __construct(
        private Routes $routes,
        private Bindings $bindings,
        private Handlers $handlers,
    ) {
    }

    /**
     * Shortcut to create, add and run all routes at once.
     */
    public static function configure(Closure $fn): void
    {
        $self = self::create();
        $self->addRoutes($fn);
        $self->run();
    }

    public static function create(): self
    {
        return new self(new Routes(), new Bindings(), new Handlers());
    }

    public function addRoutes(Closure $fn): self
    {
        $params = array_map(fn ($param) => match ((string)$param->getType()) {
            Routes::class => $this->routes,
            Bindings::class => $this->bindings,
            Handlers::class => $this->handlers,
            default => null,
        }, (new ReflectionFunction($fn))->getParameters());

        $fn(...$params);

        return $this;
    }

    public function run(): void
    {
        try {
            echo $this->findRoute()->run($this->bindings);
        } catch (Exception $exception) {
            echo self::handleException($this->handlers, $exception);
        }
    }

    private function findRoute(): Route
    {
        foreach ($this->routes->getAllRoutes() as $route) {
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

        /** @var mixed $instance */
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
