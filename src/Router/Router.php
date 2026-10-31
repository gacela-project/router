<?php

declare(strict_types=1);

namespace Gacela\Router;

use Closure;
use Gacela\Container\Container;
use Gacela\Router\Configure\Bindings;
use Gacela\Router\Configure\Handlers;
use Gacela\Router\Configure\Middlewares;
use Gacela\Router\Configure\Routes;
use Gacela\Router\Entities\Request;
use Gacela\Router\Entities\ResponseNormalizer;
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

        // Routes is mutable and shared, so a generator built now still sees
        // every route registered later by the configure closure.
        $this->bindings->bind(UrlGenerator::class, new UrlGenerator($this->routes));

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

        if (!is_callable($handler)) {
            /** @psalm-suppress MixedAssignment */
            $handler = Container::create($handler);
        }

        if (!is_callable($handler)) {
            throw NonCallableHandlerException::fromException($throwable::class);
        }

        return (string)ResponseNormalizer::normalize($handler($throwable));
    }

    /**
     * The most specific registered handler: the throwable's own class, then each
     * parent class, then the interfaces it implements, and Throwable last, which
     * always has one. An Error has no Exception among its parents, so it never
     * reaches a handler registered for Exception, which may type-hint Exception.
     *
     * @return callable|class-string
     */
    private function findHandler(Throwable $throwable): string|callable
    {
        $handlers = $this->handlers->getAllHandlers();

        foreach (self::handlerCandidates($throwable) as $class) {
            if (isset($handlers[$class])) {
                return $handlers[$class];
            }
        }

        return $handlers[Throwable::class];
    }

    /**
     * Most specific first, without Throwable itself: it is the fallback.
     *
     * @return list<class-string>
     */
    private static function handlerCandidates(Throwable $throwable): array
    {
        // Both return false only for a class that does not exist, never for an instance.
        /** @var array<class-string, class-string> $parents */
        $parents = class_parents($throwable);
        /** @var array<class-string, class-string> $interfaces */
        $interfaces = class_implements($throwable);

        return [
            $throwable::class,
            ...array_values($parents),
            ...array_values(array_diff($interfaces, [Throwable::class])),
        ];
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
