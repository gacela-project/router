<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

final class MalformedPathException extends RuntimeException
{
    public static function withPath(string $path): self
    {
        return new self("Malformed path: '{$path}'. A path cannot be empty or start or end with a slash. A segment with a brace must be exactly one parameter, {name} or {name?}, and optional parameters must come last.");
    }
}
