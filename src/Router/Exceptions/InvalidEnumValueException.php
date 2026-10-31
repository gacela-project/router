<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

final class InvalidEnumValueException extends InvalidParamValueException
{
    /**
     * @param class-string $enumClass
     */
    public static function forEnum(string $enumClass, string $value): self
    {
        return new self("Invalid value '{$value}' for backed enum '{$enumClass}'.");
    }
}
