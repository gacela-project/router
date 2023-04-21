<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

final class UnsupportedHttpMethodException extends RuntimeException
{
    public static function withName(string $name): self
    {
        return new self("Unsupported HTTP method '{$name}'");
    }
}
