<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

final class JsonResponse
{
    public function __construct(
        private array $json,
    ) {
    }

    public function __toString(): string
    {
        header('Content-Type: application/json');

        return json_encode($this->json, JSON_THROW_ON_ERROR);
    }
}
