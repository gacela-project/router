<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

final class JsonResponse extends Response
{
    public function __construct(array $json)
    {
        parent::__construct(json_encode($json, JSON_THROW_ON_ERROR));
    }

    public function __toString(): string
    {
        header('Content-Type: application/json');

        return parent::__toString();
    }
}