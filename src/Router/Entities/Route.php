<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use Gacela\Container\Container;
use Gacela\Router\Configure\Bindings;
use Gacela\Router\Middleware\MiddlewareInterface;
use Gacela\Router\Validators\PathPatternGenerator;
use Stringable;

use function is_object;
use function is_string;

final class Route
{
    /** @var list<MiddlewareInterface|class-string<MiddlewareInterface>|string> */
    private array $middlewares = [];

    private ?string $name = null;

    /** @var array<string> */
    private readonly array $methods;

    /**
     * @param string|array<string> $methods
     * @param object|class-string $controller
     */
    public function __construct(
        string|array $methods,
        private readonly string $path,
        private readonly object|string $controller,
        private readonly string $action = '__invoke',
        private ?string $pathPattern = null,
    ) {
        $this->methods = is_string($methods) ? [$methods] : $methods;
    }

    /**
     * @psalm-suppress MixedMethodCall
     */
    public function run(Bindings $bindings, Request $request): string|Stringable
    {
        $params = (new RouteParams($this, $request))->getAll();

        if (is_object($this->controller)) {
            $controller = $this->controller;
        } else {
            // The request of this run, unless the user bound their own. Building it
            // here keeps a long-lived router from handing every controller the
            // request that existed when the router was constructed.
            $creator = new Container($bindings->getAllBindings() + [Request::class => $request]);
            /** @var object $controller */
            $controller = $creator->get($this->controller);
        }

        /** @var mixed $response */
        $response = $controller->{$this->action}(...$params);

        return ResponseNormalizer::normalize($response);
    }

    public function path(): string
    {
        return $this->path;
    }

    /**
     * @return object|class-string
     */
    public function controller(): object|string
    {
        return $this->controller;
    }

    public function action(): string
    {
        return $this->action;
    }

    /**
     * @param MiddlewareInterface|class-string<MiddlewareInterface>|string $middleware
     */
    public function middleware(MiddlewareInterface|string $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return list<MiddlewareInterface|class-string<MiddlewareInterface>|string>
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    public function getPathPattern(): string
    {
        if ($this->pathPattern === null) {
            $this->pathPattern = PathPatternGenerator::generate($this->path);
        }

        return $this->pathPattern;
    }

    public function requestMatches(Request $request): bool
    {
        return $this->methodMatches($request) && $this->pathMatches($request);
    }

    public function pathMatches(Request $request): bool
    {
        return (bool)preg_match($this->getPathPattern(), $request->path());
    }

    private function methodMatches(Request $request): bool
    {
        foreach ($this->methods as $method) {
            if ($request->isMethod($method)) {
                return true;
            }
        }

        return false;
    }
}
