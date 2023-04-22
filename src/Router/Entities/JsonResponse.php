<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

use function in_array;

final class JsonResponse extends Response
{
    /**
     * @param list<string> $headers
     */
    public function __construct(array $json, array $headers = [])
    {
        if (!in_array('Content-Type: application/json', $headers, true)) {
            $headers[] = 'Content-Type: application/json';
        }
        parent::__construct(json_encode($json, JSON_THROW_ON_ERROR), $headers);
    }
}
