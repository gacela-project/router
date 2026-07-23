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
use Gacela\Router\Exceptions\MethodNotAllowed405Exception;
use Gacela\Router\Exceptions\NonCallableHandlerException;
use Gacela\Router\Exceptions\NotFound404Exception;
use Gacela\Router\Exceptions\UnsupportedRouterConfigureCallableParamException;
use Gacela\Router\Middleware\MiddlewarePipeline;
use Override;
use ReflectionFunction;
use Throwable;

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
        $request = Request::fromGlobals();
        $output = $this->resolveOutput($request);

        // HTTP forbids a body on a HEAD response, error responses included. The
        // route and its middlewares still ran, so their headers are already out.
        if (!$request->isMethod(Request::METHOD_HEAD)) {
            echo $output;
        }
    }

    private function resolveOutput(Request $request): string
    {
        try {
            $route = $this->findRoute($request);

            $resolvedGlobalMiddlewares = $this->resolveMiddlewares($this->middlewares->getAll());
            $resolvedRouteMiddlewares = $this->resolveMiddlewares($route->getMiddlewares());

            $allMiddlewares = array_merge($resolvedGlobalMiddlewares, $resolvedRouteMiddlewares);

            $pipeline = new MiddlewarePipeline($allMiddlewares);

            return $pipeline->handle($request, function () use ($route, $request): string {
                return (string) $route->run($this->bindings, $request);
            });
        } catch (Throwable $throwable) {
            return $this->handleThrowable($throwable);
        }
    }

    private function findRoute(Request $request): Route
    {
        $route = $this->routes->findMatching($request);
        if ($route !== null) {
            return $route;
        }

        // The path exists, just not for this method: that is a 405, not a 404.
        $allowedMethods = $this->routes->allowedMethodsFor($request);
        if ($allowedMethods !== []) {
            throw MethodNotAllowed405Exception::fromAllowedMethods($allowedMethods);
        }

        throw new NotFound404Exception();
    }

    private function handleThrowable(Throwable $throwable): string
    {
        $handler = $this->findHandler($throwable);

        if (is_callable($handler)) {
            /** @var string $result */
            $result = $handler($throwable);
            return $result;
        }

        /** @psalm-suppress MixedAssignment */
        $instance = Container::create($handler);

        if (is_callable($instance)) {
            /** @var string $result */
            $result = $instance($throwable);
            return $result;
        }

        throw NonCallableHandlerException::fromException($throwable::class);
    }

    /**
     * A handler registered for Exception::class is free to type-hint Exception,
     * so an Error must never be routed to it. Errors fall back to Throwable.
     *
     * @return callable|class-string
     */
    private function findHandler(Throwable $throwable): string|callable
    {
        $handlers = $this->handlers->getAllHandlers();

        $fallback = $throwable instanceof Exception
            ? Exception::class
            : Throwable::class;

        return $handlers[$throwable::class] ?? $handlers[$fallback];
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
