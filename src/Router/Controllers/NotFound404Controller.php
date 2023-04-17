<?php

declare(strict_types=1);

namespace Gacela\Router\Controllers;

final class NotFound404Controller
{
    public function __invoke(): void
    {
        header('HTTP/1.0 404 Not Found');
    }
}
