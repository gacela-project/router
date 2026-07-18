<?php

declare(strict_types=1);

namespace Gacela\Router\Configure;

use Gacela\Router\Middleware\MiddlewareInterface;

/**
 * @psalm-import-type RawMiddleware from MiddlewareInterface
 * @psalm-import-type ResolvedMiddleware from MiddlewareInterface
 *
 * @phpstan-import-type RawMiddleware from MiddlewareInterface
 * @phpstan-import-type ResolvedMiddleware from MiddlewareInterface
 */
final class Middlewares
{
    /** @var list<RawMiddleware> */
    private array $globalMiddlewares = [];

    /** @var array<string, list<ResolvedMiddleware>> */
    private array $groups = [];

    /**
     * @param RawMiddleware $middleware
     */
    public function add(MiddlewareInterface|string $middleware): self
    {
        $this->globalMiddlewares[] = $middleware;
        return $this;
    }

    /**
     * @param list<ResolvedMiddleware> $middlewares
     */
    public function group(string $name, array $middlewares): self
    {
        $this->groups[$name] = $middlewares;
        return $this;
    }

    /**
     * @return list<RawMiddleware>
     */
    public function getAll(): array
    {
        return $this->globalMiddlewares;
    }

    /**
     * @return array<string, list<ResolvedMiddleware>>
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param RawMiddleware $middleware
     *
     * @return list<ResolvedMiddleware>
     */
    public function resolve(MiddlewareInterface|string $middleware): array
    {
        if ($middleware instanceof MiddlewareInterface) {
            return [$middleware];
        }

        if (isset($this->groups[$middleware])) {
            return $this->groups[$middleware];
        }

        /** @var class-string<MiddlewareInterface> $middleware */
        return [$middleware];
    }
}
