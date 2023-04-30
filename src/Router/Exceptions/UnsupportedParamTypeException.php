<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

final class UnsupportedParamTypeException extends RuntimeException
{
    public static function fromType(string $type): self
    {
        return new self("Unsupported param type '{$type}'. Must be a scalar.");
    }

    public static function nonTyped(): self
    {
        return new self('Unsupported non-typed param. Must be a scalar.');
    }
}
