<?php

declare(strict_types=1);

namespace Gacela\Router;

use Closure;
use Exception;
use Gacela\Container\Container;
use Gacela\Router\Entities\Route;
use Gacela\Router\Exceptions\NotFound404Exception;
use Gacela\Router\Exceptions\UnsupportedRouterConfigureCallableParamException;
use ReflectionFunction;

use function get_class;
use function is_callable;
use function is_null;

final class Router
{
    private Routes $routes;
    private Bindings $bindings;
    private Handlers $handlers;

    public function __construct(
        Closure $fn = null,
    ) {
        $this->routes = new Routes();
        $this->bindings = new Bindings();
        $this->handlers = new Handlers();

        if (!is_null($fn)) {
            $this->configure($fn);
        }
    }

    public function configure(Closure $fn): self
    {
        $params = array_map(fn ($param) => match ((string)$param->getType()) {
            Routes::class => $this->routes,
            Bindings::class => $this->bindings,
            Handlers::class => $this->handlers,
            default => throw UnsupportedRouterConfigureCallableParamException::fromName($param),
        }, (new ReflectionFunction($fn))->getParameters());

        $fn(...$params);

        return $this;
    }

    public function run(): void
    {
        try {
            echo $this->findRoute()->run($this->bindings);
        } catch (Exception $exception) {
            echo $this->handleException($exception);
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

    private function handleException(Exception $exception): string
    {
        $handler = $this->findHandler($exception);

        if (is_callable($handler)) {
            return $handler($exception);
        }

        /** @psalm-suppress MixedAssignment */
        $instance = Container::create($handler);

        if (is_callable($instance)) {
            return $instance($exception);
        }

        return '';
    }

    /**
     * @return callable|class-string
     */
    private function findHandler(Exception $exception): string|callable
    {
        return $this->handlers->getAllHandlers()[get_class($exception)]
            ?? $this->handlers->getAllHandlers()[Exception::class];
    }
}
