<?php

declare(strict_types=1);

namespace Gacela\Router\Handlers;

use Exception;

final class FallbackExceptionHandler
{
    public function __invoke(Exception $exception): string
    {
        header('HTTP/1.1 500 Internal Server Error');

        return '';
    }
}
