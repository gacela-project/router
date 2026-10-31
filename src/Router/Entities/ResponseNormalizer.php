<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use Gacela\Router\Exceptions\UnsupportedResponseTypeException;
use Stringable;

use function is_array;
use function is_string;

/**
 * What an action or an exception handler may return, turned into something the
 * router can print.
 */
final class ResponseNormalizer
{
    public static function normalize(mixed $response): string|Stringable
    {
        // Returning an array is the common JSON case, so encode it rather than
        // making every action wrap it by hand.
        if (is_array($response)) {
            return new JsonResponse($response);
        }

        if (!is_string($response) && !($response instanceof Stringable)) {
            throw UnsupportedResponseTypeException::fromType($response);
        }

        return $response;
    }
}
