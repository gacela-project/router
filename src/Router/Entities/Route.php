<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use Gacela\Container\Container;
use Gacela\Router\Configure\Bindings;
use Gacela\Router\Exceptions\UnsupportedResponseTypeException;
use Gacela\Router\Middleware\MiddlewareInterface;
use Gacela\Router\Validators\PathPatternGenerator;
use Stringable;

use function is_object;
use function is_string;

final class Route
{
    /** @var list<MiddlewareInterface|class-string<MiddlewareInterface>|string> */
    private array $middlewares = [];

    /**
     * @param object|class-string $controller
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly object|string $controller,
        private readonly string $action = '__invoke',
        private ?string $pathPattern = null,
    ) {
    }

    /**
     * @psalm-suppress MixedMethodCall
     */
    public function run(Bindings $bindings): string|Stringable
    {
        $params = (new RouteParams($this))->getAll();

        if (!is_object($this->controller)) {
            $creator = new Container($bindings->getAllBindings());
            /** @var object $controller */
            $controller = $creator->get($this->controller);
            $response = $controller->{$this->action}(...$params);
        } else {
            /** @var string|Stringable $response */
            $response = $this->controller->{$this->action}(...$params);
        }

        if (!is_string($response) && !($response instanceof Stringable)) {
            throw UnsupportedResponseTypeException::fromType($response);
        }

        return $response;
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

    public function requestMatches(): bool
    {
        return $this->methodMatches() && $this->pathMatches();
    }

    private function methodMatches(): bool
    {
        return Request::fromGlobals()->isMethod($this->method);
    }

    private function pathMatches(): bool
    {
        $path = Request::fromGlobals()->path();

        return (bool)preg_match($this->getPathPattern(), $path);
    }
}
