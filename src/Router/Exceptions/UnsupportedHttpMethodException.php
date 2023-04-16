<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

final class UnsupportedHttpMethodException extends RuntimeException
{
    public function __construct(string $name)
    {
        parent::__construct("Unsupported HTTP method '{$name}'");
    }
}
