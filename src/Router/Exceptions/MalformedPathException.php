<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

final class MalformedPathException extends RuntimeException
{
    public static function withPath(string $path): self
    {
        return new self("Malformed path: '{$path}'. Path cannot be empty or start/end with slash and optional arguments must be at the end of the path");
    }
}
