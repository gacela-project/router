<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

final class InvalidEnumValueException extends RuntimeException
{
    /**
     * @param class-string $enumClass
     */
    public static function forEnum(string $enumClass, string $value): self
    {
        return new self("Invalid value '{$value}' for backed enum '{$enumClass}'.");
    }
}
