<?php

declare(strict_types=1);

namespace Gacela\Router\Exceptions;

use RuntimeException;

use function get_class;
use function gettype;

final class UnsupportedResponseTypeException extends RuntimeException
{
    public static function fromType(mixed $var): self
    {
        $type = self::inferType($var);

        return new self("Unsupported response type '{$type}'. Must be a string or implement Stringable interface.");
    }

    private static function inferType(mixed $var): string
    {
        $type = gettype($var);

        if ($type === 'object') {
            /** @var object $var */
            return get_class($var);
        }

        return $type;
    }
}
