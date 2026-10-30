<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use function stripos;

final class JsonResponse extends Response
{
    /**
     * @param array<mixed> $json
     * @param list<string> $headers
     */
    public function __construct(array $json, array $headers = [])
    {
        if (!self::hasContentType($headers)) {
            $headers[] = 'Content-Type: application/json';
        }
        parent::__construct(json_encode($json, JSON_THROW_ON_ERROR), $headers);
    }

    /**
     * Header names are case-insensitive and the value may carry parameters
     * ('content-type: application/json; charset=utf-8'), so an exact string match
     * would let the default overwrite the caller's header.
     *
     * @param list<string> $headers
     */
    private static function hasContentType(array $headers): bool
    {
        foreach ($headers as $header) {
            if (stripos(ltrim($header), 'Content-Type:') === 0) {
                return true;
            }
        }

        return false;
    }
}
