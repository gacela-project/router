<?php

declare(strict_types=1);

namespace Gacela\Router;

use Exception;

use function get_class;

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

    public function getByException(Exception $exception): ?callable
    {
        return $this->handlers[get_class($exception)] ?? null;
    }
}
