<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

final class MalformedPathException extends RuntimeException
{
    public static function withPath(string $path): self
    {
        return new self("Malformed path: '{$path}'. Optional parameters must be at the end of the path");
    }
}
