<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

class NonCallableHandlerException extends RuntimeException
{
    /**
     * @param class-string $class
     *
     * @return self
     */
    public static function fromException(string $class): self
    {
        return new self("Handler assigned to '{$class}' exception cannot be called.");
    }
}
