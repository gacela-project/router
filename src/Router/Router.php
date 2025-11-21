<?php

declare(strict_types=1);

namespace Gacela\Router;

use Closure;
use Exception;
use Gacela\Container\Container;
use Gacela\Router\Configure\Bindings;
use Gacela\Router\Configure\Handlers;
use Gacela\Router\Configure\Middlewares;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Entities\Route;
use Gacela\Router\Exceptions\NonCallableHandlerException;
use Gacela\Router\Exceptions\NotFound404Exception;
use Gacela\Router\Exceptions\UnsupportedRouterConfigureCallableParamException;
use Gacela\Router\Middleware\MiddlewarePipeline;
use Override;
use ReflectionFunction;

use function get_class;
use function is_callable;
use function is_null;

final class Router implements RouterInterface
{
    private Routes $routes;
    private Bindings $bindings;
    private Handlers $handlers;
    private Middlewares $middlewares;

    public function __construct(?Closure $fn = null)
    {
        $this->routes = new Routes();
        $this->bindings = new Bindings();
        $this->handlers = new Handlers();
        $this->middlewares = new Middlewares();

        if (!is_null($fn)) {
            $this->configure($fn);
        }
    }

    #[Override]
    public function configure(Closure $fn): self
    {
        $params = array_map(fn ($param) => match ((string) $param->getType()) {
            Routes::class => $this->routes,
            Bindings::class => $this->bindings,
            Handlers::class => $this->handlers,
            Middlewares::class => $this->middlewares,
            default => throw UnsupportedRouterConfigureCallableParamException::fromName($param),
        }, (new ReflectionFunction($fn))->getParameters());

        $fn(...$params);

        return $this;
    }

    #[Override]
    public function run(): void
    {
        try {
            $route = $this->findRoute();
            $request = Request::fromGlobals();

            $resolvedGlobalMiddlewares = $this->resolveMiddlewares($this->middlewares->getAll());
            $resolvedRouteMiddlewares = $this->resolveMiddlewares($route->getMiddlewares());

            $allMiddlewares = array_merge($resolvedGlobalMiddlewares, $resolvedRouteMiddlewares);

            $pipeline = new MiddlewarePipeline($allMiddlewares);

            echo $pipeline->handle($request, function () use ($route): string {
                return (string) $route->run($this->bindings);
            });
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
            /** @var string $result */
            $result = $handler($exception);
            return $result;
        }

        /** @psalm-suppress MixedAssignment */
        $instance = Container::create($handler);

        if (is_callable($instance)) {
            /** @var string $result */
            $result = $instance($exception);
            return $result;
        }

        throw NonCallableHandlerException::fromException($exception::class);
    }

    /**
     * @return callable|class-string
     */
    private function findHandler(Exception $exception): string|callable
    {
        return $this->handlers->getAllHandlers()[get_class($exception)]
            ?? $this->handlers->getAllHandlers()[Exception::class];
    }

    /**
     * @param list<Middleware\MiddlewareInterface|class-string<Middleware\MiddlewareInterface>|string> $middlewares
     *
     * @return list<Middleware\MiddlewareInterface|class-string<Middleware\MiddlewareInterface>>
     */
    private function resolveMiddlewares(array $middlewares): array
    {
        $resolved = [];

        foreach ($middlewares as $middleware) {
            array_push($resolved, ...$this->middlewares->resolve($middleware));
        }

        return $resolved;
    }
}
