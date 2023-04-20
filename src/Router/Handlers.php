<?php

declare(strict_types=1);

namespace Gacela\Router;

final class Handlers
{
    /** @var array<class-string, callable> */
    private array $handlers = [];

    /**
     * @param class-string $exception
     */
    public function handle(string $exception, callable $handler): self
    {
        $this->handlers[$exception] = $handler;
        return $this;
    }

    /**
     * @return array<class-string, callable>
     */
    public function getAllHandlers(): array
    {
        return $this->handlers;
    }
}
