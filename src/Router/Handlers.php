<?php

declare(strict_types=1);

namespace Gacela\Router;

use Exception;
use Gacela\Router\Exceptions\NotFound404Exception;
use Gacela\Router\Handlers\FallbackExceptionHandler;
use Gacela\Router\Handlers\NotFound404ExceptionHandler;

final class Handlers
{
    /** @var array<class-string, callable> */
    private array $handlers = [];

    public function __construct()
    {
        $this->addBuiltInHandlers();
    }
    /**
     * @param class-string<Exception> $exception
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

    private function addBuiltInHandlers(): void
    {
        $this->handle(
            NotFound404Exception::class,
            static fn (NotFound404Exception $exception) => (new NotFound404ExceptionHandler())->__invoke($exception),
        );
        $this->handle(
            Exception::class,
            static fn (Exception $exception) => (new FallbackExceptionHandler())->__invoke($exception),
        );
    }
}
