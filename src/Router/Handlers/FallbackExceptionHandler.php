<?php

declare(strict_types=1);

namespace Gacela\Router\Handlers;

use Throwable;

final class FallbackExceptionHandler
{
    public function __invoke(Throwable $throwable): string
    {
        header('HTTP/1.1 500 Internal Server Error');

        return '';
    }
}
