<?php

declare(strict_types=1);

namespace Gacela\Router;

final class Redirect
{
    public function __construct(
        public string $uri,
        public string $destination,
        public int $status = 302,
        public string $method = Request::METHOD_GET,
    ) {
    }
}
