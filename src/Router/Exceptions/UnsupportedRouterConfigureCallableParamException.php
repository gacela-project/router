<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use ReflectionParameter;
use RuntimeException;

final class UnsupportedRouterConfigureCallableParamException extends RuntimeException
{
    public static function fromName(ReflectionParameter $name): self
    {
        return new self("'{$name->getName()}' parameter in configuration Closure for Router must be from types Routes, Bindings or Handlers.");
    }
}
