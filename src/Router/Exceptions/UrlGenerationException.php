<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

final class UrlGenerationException extends RuntimeException
{
    public static function duplicateName(string $name): self
    {
        return new self("Route name '{$name}' is already taken.");
    }

    public static function unknownName(string $name): self
    {
        return new self("No route is named '{$name}'.");
    }

    public static function missingParam(string $name, string $param): self
    {
        return new self("Missing param '{$param}' to generate the url for route '{$name}'.");
    }

    public static function unsupportedParamType(string $name, string $param, string $type): self
    {
        return new self(
            "Unsupported type '{$type}' for param '{$param}' generating the url for route '{$name}'."
            . ' Must be a scalar or a backed enum.',
        );
    }
}
