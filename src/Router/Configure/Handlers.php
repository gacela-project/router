<?php

declare(strict_types=1);

namespace Gacela\Router\Configure;

use Exception;
use Gacela\Router\Exceptions\NotFound404Exception;
use Gacela\Router\Handlers\FallbackExceptionHandler;
use Gacela\Router\Handlers\NotFound404ExceptionHandler;

final class Handlers
{
    /** @var array<class-string, callable|class-string> */
    private array $handlers = [];

    public function __construct()
    {
        $this->addBuiltInHandlers();
    }
    /**
     * @param class-string<Exception> $exception
     * @param callable|class-string $handler
     */
    public function handle(string $exception, callable|string $handler): self
    {
        $this->handlers[$exception] = $handler;
        return $this;
    }

    /**
     * @return array<class-string, callable|class-string>
     */
    public function getAllHandlers(): array
    {
        return $this->handlers;
    }

    private function addBuiltInHandlers(): void
    {
        $this->handle(NotFound404Exception::class, NotFound404ExceptionHandler::class);
        $this->handle(Exception::class, FallbackExceptionHandler::class);
    }
}
