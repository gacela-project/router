<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

/**
 * A path segment that does not fit the type its action argument declares.
 */
class InvalidParamValueException extends RuntimeException
{
    public static function forParam(string $name, string $type, string $value): self
    {
        return new self("Invalid value '{$value}' for route parameter '{$name}' of type '{$type}'.");
    }
}
