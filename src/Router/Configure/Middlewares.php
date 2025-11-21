<?php

declare(strict_types=1);

namespace Gacela\Router\Configure;

use Gacela\Router\Middleware\MiddlewareInterface;

final class Middlewares
{
    /** @var list<MiddlewareInterface|class-string<MiddlewareInterface>|string> */
    private array $globalMiddlewares = [];

    /** @var array<string, list<MiddlewareInterface|class-string<MiddlewareInterface>>> */
    private array $groups = [];

    /**
     * @param MiddlewareInterface|class-string<MiddlewareInterface>|string $middleware
     */
    public function add(MiddlewareInterface|string $middleware): self
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * @param list<MiddlewareInterface|class-string<MiddlewareInterface>> $middlewares
     */
    public function group(string $name, array $middlewares): self
    {
        $this->groups[$name] = $middlewares;
        return $this;
    }

    /**
     * @return list<MiddlewareInterface|class-string<MiddlewareInterface>|string>
     */
    public function getAll(): array
    {
        return $this->globalMiddlewares;
    }

    /**
     * @return array<string, list<MiddlewareInterface|class-string<MiddlewareInterface>>>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param MiddlewareInterface|class-string<MiddlewareInterface>|string $middleware
     *
     * @return list<MiddlewareInterface|class-string<MiddlewareInterface>>
     */
    public function resolve(MiddlewareInterface|string $middleware): array
    {
        // If it's already an instance, return it as-is
        if ($middleware instanceof MiddlewareInterface) {
            return [$middleware];
        }

        // If it's a group name, return the group's middlewares
        if (isset($this->groups[$middleware])) {
            return $this->groups[$middleware];
        }

        // Otherwise, treat it as a class string
        /** @var class-string<MiddlewareInterface> $middleware */
        return [$middleware];
    }
}
