<?php

declare(strict_types=1);

namespace Gacela\Router\Entities;

class Response
{
    public function __construct(
        private string $body,
    ) {
    }

    public function __toString(): string
    {
        return $this->body;
    }
}
