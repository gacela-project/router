<?php

declare(strict_types=1);

namespace Gacela\Router;

final class Redirect
{
    public function __construct(
        private string $uri,
        private string $destination,
        private int $status = 302,
        private string $method = Request::METHOD_GET,
    ) {
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function destination(): string
    {
        return $this->destination;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function method(): string
    {
        return $this->method;
    }
}
